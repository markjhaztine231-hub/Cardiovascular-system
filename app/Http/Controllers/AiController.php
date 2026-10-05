<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\Encounter;
use App\Models\MonthlyReport;
use App\Models\User;
use App\Services\MonthlyReportService;
use Carbon\Carbon;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class AiController extends Controller
{
    // ==========================================
    // 1. PATIENT ABSTRACT (WITH PUBMED & PHA GUIDELINES RAG)
    // ==========================================
    public function generateAbstract(User $patient)
    {
        set_time_limit(180);

        $patientRecord = $patient->patient;
        if (! $patientRecord) {
            return back()->withErrors(['ai' => 'No patient record linked to this account.']);
        }

        $history = $patientRecord->encounters()
            ->with(['soapNote', 'diagnoses'])
            ->orderByDesc('started_at')
            ->get();
        $latestAppt = $history->first();

        if ($history->isEmpty()) {
            return back()->withErrors(['ai' => 'Not enough medical history to generate an abstract.']);
        }

        // ── Anonymize before any data leaves this server (GDPR Article 25) ──
        [$pseudonym, $anonymizedData] = $this->anonymizeForAi($patient, $history);

        // --- 1A. RAG RETRIEVAL: PubMed Literature ---
        $searchQuery = $latestAppt->diagnoses->first()?->description ?? 'Cardiovascular Disease';
        $pubmedLiterature = $this->fetchPubMedLiterature($searchQuery);

        // --- 1B. RAG RETRIEVAL: Philippine Heart Association Guidelines ---
        // Proper chunked retrieval — only the top-3 sections relevant to this
        // patient's diagnosis are injected, not the entire PDF.
        $phaContext = $this->retrieveRelevantGuidelines($searchQuery, 3);

        // --- 1C. GENERATION: Inject ALL knowledge into the Prompt ---
        // NOTE: Real patient name is NEVER sent to the AI. Pseudonym is used instead.
        $prompt = "You are an expert cardiologist practicing in the Philippines. Write a concise, 3-sentence clinical abstract for an anonymized patient identified as {$pseudonym}.\n\n";
        $prompt .= "PATIENT HISTORY (anonymized — PII has been masked):\n".json_encode($anonymizedData)."\n\n";
        $prompt .= "RELEVANT PubMed LITERATURE:\n".$pubmedLiterature."\n\n";
        $prompt .= "RELEVANT PHA GUIDELINE SECTIONS (CAD 2014 — top matches for this diagnosis):\n".$phaContext."\n\n";
        $prompt .= 'Instructions: Ensure the abstract aligns with the provided Philippine Heart Association guidelines and PubMed literature. Do not use markdown asterisks.';

        $summary = $this->callAiApi($prompt);

        if ($latestAppt && ! str_contains($summary, 'error occurred')) {
            $latestAppt->soapNote?->update(['ai_summary' => $summary]);
        }

        if (str_contains($summary, 'error occurred')) {
            return back()->withErrors(['ai_error' => $summary]);
        }

        return back()->with('success', 'AI Abstract Generated Successfully using PubMed & PHA Guidelines!');
    }

    // ==========================================
    // 2. MONTHLY REPORT
    // ==========================================
    public function showMonthlyReport(Request $request)
    {
        $doctorId = Auth::id();

        // Load a specific report if ?report=X, otherwise the latest overall
        if ($request->has('report')) {
            $report = MonthlyReport::forDoctor($doctorId)
                ->where('id', $request->report)
                ->firstOrFail();
        } else {
            $report = MonthlyReport::forDoctor($doctorId)
                ->latest()
                ->first();
        }

        return $this->reportResponse($report);
    }

    public function generateMonthlyReport(Request $request)
    {
        $request->validate(['month' => 'required|date_format:Y-m']);

        $doctorId = Auth::id();
        $month = $request->month;

        // Guard: prevent rapid duplicate generations (within 30 seconds).
        $latest = MonthlyReport::forDoctor($doctorId)
            ->forMonth($month)
            ->latest()
            ->first();

        if ($latest && $latest->created_at->diffInSeconds(now()) < 30) {
            return back()->withErrors(['month' => 'Please wait before generating another report for this month.']);
        }

        // Guard: make sure at least one appointment exists before generating.
        $exists = Appointment::whereHas('provider', fn ($q) => $q->where('user_id', $doctorId))
            ->whereBetween('scheduled_date', [
                Carbon::parse($month.'-01')->startOfMonth()->toDateString(),
                Carbon::parse($month.'-01')->endOfMonth()->toDateString(),
            ])
            ->exists();

        if (! $exists) {
            return back()->withErrors(['month' => 'No appointments found for this month.']);
        }

        // Delegate to the shared service — returns both stats and AI narrative.
        // Always creates a new row (history).
        $result = MonthlyReportService::generate($doctorId, $month);

        $report = MonthlyReport::forDoctor(Auth::id())
            ->forMonth($request->month)
            ->latest()
            ->first();

        return $this->reportResponse($report);
    }

    /**
     * Build the Inertia response with the given report and history.
     */
    private function reportResponse(?MonthlyReport $report): Response
    {
        $doctorId = Auth::id();

        $history = MonthlyReport::forDoctor($doctorId)
            ->latest()
            ->get(['id', 'month', 'stats', 'report_text', 'created_at']);

        return Inertia::render('Dashboard/MonthlyReport', [
            'user' => Auth::user(),
            'patients' => User::where('role', 'patient')->orderBy('name')->get(['id', 'name'])->map(fn ($u) => ['id' => $u->id, 'name' => $u->anonymizedName()]),
            'stats' => $report?->stats,
            'reportText' => $report?->report_text,
            'selectedMonth' => $report?->month ?? date('Y-m'),
            'currentReportId' => $report?->id,
            'reportHistory' => $history,
        ]);
    }

    // ==========================================
    // 3. LEARNING & FEEDBACK LOOP  (stubs — not yet implemented)
    // ==========================================
    // TODO: persist doctor corrections in a `ai_feedback` table and use them
    // as few-shot examples in the next generation call for this patient.
    public function verifyAndLearnReport(Request $request)
    {
        // ⚠ Not implemented — placeholder only.
        return back()->with('info', 'Feedback noted. (Full learning loop is not yet implemented.)');
    }

    /**
     * Persist a doctor-edited version of the narrative report.
     * Only the report_text column is updated — stats remain untouched.
     */
    public function updateNarrative(Request $request)
    {
        $request->validate([
            'report_id' => 'required|integer|exists:monthly_reports,id',
            'report_text' => 'required|string|max:20000',
        ]);

        $report = MonthlyReport::forDoctor(Auth::id())
            ->where('id', $request->report_id)
            ->firstOrFail();

        // Prevent overwriting with identical text.
        if ($report->report_text === $request->report_text) {
            return back()->with('info', 'No changes detected.');
        }

        $report->update(['report_text' => $request->report_text]);

        return back()->with('success', 'Narrative saved successfully.');
    }

    public function submitFeedback(Request $request, User $patient)
    {
        // ⚠ Not implemented — placeholder only.
        return back()->with('info', 'Feedback noted. (Full learning loop is not yet implemented.)');
    }

    // ==========================================
    // 4. LLAMAPARSE DOCUMENT ANALYSIS + OPENROUTER
    // ==========================================
    public function analyzeDocument(Request $request)
    {
        set_time_limit(300);

        $request->validate([
            'document' => 'required|file|mimes:pdf|max:10240',
            'prompt' => 'required|string',
        ]);

        try {
            $file = $request->file('document');
            $apiKey = env('LLAMA_CLOUD_API_KEY');

            if (! $apiKey) {
                return back()->withErrors(['ai_error' => 'LlamaCloud API key is missing from .env']);
            }

            $uploadResponse = Http::withHeaders(['Authorization' => "Bearer {$apiKey}"])
                ->attach('file', file_get_contents($file->getPathname()), $file->getClientOriginalName())
                ->post('https://api.cloud.llamaindex.ai/api/parsing/upload');

            if (! $uploadResponse->successful()) {
                return back()->withErrors(['ai_error' => 'Failed to upload document to LlamaParse.']);
            }

            $jobId = $uploadResponse->json('id');
            $parsedMarkdown = '';
            $attempts = 0;

            while ($attempts < 15) {
                sleep(2);
                $statusResponse = Http::withHeaders(['Authorization' => "Bearer {$apiKey}"])->get("https://api.cloud.llamaindex.ai/api/parsing/job/{$jobId}");

                if ($statusResponse->json('status') === 'SUCCESS') {
                    $resultResponse = Http::withHeaders(['Authorization' => "Bearer {$apiKey}"])->get("https://api.cloud.llamaindex.ai/api/parsing/job/{$jobId}/result/markdown");
                    $parsedMarkdown = $resultResponse->json('markdown');
                    break;
                }

                if ($statusResponse->json('status') === 'ERROR') {
                    return back()->withErrors(['ai_error' => 'LlamaParse encountered an error.']);
                }
                $attempts++;
            }

            if (empty($parsedMarkdown)) {
                return back()->withErrors(['ai_error' => 'LlamaParse timed out.']);
            }

            $finalPrompt = "You are a medical assistant. Based on the following medical record, answer the query.\n\nQuery: {$request->prompt}\n\nMedical Record Data:\n{$parsedMarkdown}";
            $summary = $this->callAiApi($finalPrompt);

            if (str_contains($summary, 'error occurred')) {
                return back()->withErrors(['ai_error' => $summary]);
            }

            return back()->with([
                'success' => 'Document parsed and analyzed successfully!',
                'analysis_result' => $summary,
            ]);

        } catch (\Exception $e) {
            return back()->withErrors(['ai_error' => 'An unexpected error occurred: '.$e->getMessage()]);
        }
    }

    // ==========================================
    // 5. HELPER: FETCH PUBMED (EUROPE PMC API)
    // ==========================================

    /**
     * Fetches up to 5 recent PubMed abstracts for a given clinical query.
     * Results are cached per-query for 24 hours to avoid redundant API calls.
     */
    private function fetchPubMedLiterature(string $query): string
    {
        $cacheKey = 'pubmed_'.md5(strtolower(trim($query)));

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($query) {
            try {
                $encodedQuery = urlencode($query.' AND (SRC:MED)');
                // Sort by publication date descending; fetch 5 papers instead of 2
                $url = 'https://www.ebi.ac.uk/europepmc/webservices/rest/search'
                     ."?query={$encodedQuery}&format=json&resultType=core"
                     .'&pageSize=5&sort=P_PDATE_D%20desc';

                $response = Http::timeout(15)->get($url);

                if (! $response->successful()) {
                    return 'Could not retrieve literature at this time.';
                }

                $results = $response->json('resultList.result') ?? [];
                if (empty($results)) {
                    return 'No recent literature found.';
                }

                $context = '';
                $count = 0;
                foreach ($results as $paper) {
                    $abstract = trim(strip_tags($paper['abstractText'] ?? ''));
                    if (empty($abstract)) {
                        continue;
                    } // skip papers with no abstract
                    $title = $paper['title'] ?? 'Unknown Title';
                    $year = $paper['pubYear'] ?? '';
                    $context .= 'Paper '.(++$count)." ({$year}): {$title}\nAbstract: {$abstract}\n\n";
                }

                return $context ?: 'No abstracts available for this query.';
            } catch (\Exception $e) {
                Log::warning('PubMed retrieval failed: '.$e->getMessage());

                return 'Literature retrieval timed out.';
            }
        });
    }

    // ==========================================
    // 6. HELPERS: PHILHEART GUIDELINES — PROPER CHUNKED RAG
    // ==========================================

    /**
     * Downloads and parses the PHA CAD 2014 PDF via LlamaParse.
     * Returns the raw markdown string, or an error message.
     * Called only by getPhaGuidelineChunks() — not directly.
     */
    private function downloadAndParsePhaGuidelines(): string
    {
        try {
            $apiKey = env('LLAMA_CLOUD_API_KEY');
            if (! $apiKey) {
                return 'Guidelines unavailable: Missing LlamaCloud API Key.';
            }

            $pdfUrl = 'https://www.philheart.org/images/guidelines/cad2014.pdf';
            $pdfContent = Http::timeout(60)->get($pdfUrl)->body();
            if (empty($pdfContent)) {
                return 'Guidelines unavailable: Could not download PDF.';
            }

            $tempPath = storage_path('app/temp_cad2014.pdf');
            file_put_contents($tempPath, $pdfContent);

            $upload = Http::withHeaders(['Authorization' => "Bearer {$apiKey}"])
                ->attach('file', file_get_contents($tempPath), 'cad2014.pdf')
                ->post('https://api.cloud.llamaindex.ai/api/parsing/upload');

            @unlink($tempPath);

            if (! $upload->successful()) {
                return 'Guidelines unavailable: LlamaParse upload failed.';
            }

            $jobId = $upload->json('id');
            $attempts = 0;

            while ($attempts < 30) {
                sleep(2);
                $status = Http::withHeaders(['Authorization' => "Bearer {$apiKey}"])
                    ->get("https://api.cloud.llamaindex.ai/api/parsing/job/{$jobId}");

                if ($status->json('status') === 'SUCCESS') {
                    return Http::withHeaders(['Authorization' => "Bearer {$apiKey}"])
                        ->get("https://api.cloud.llamaindex.ai/api/parsing/job/{$jobId}/result/markdown")
                        ->json('markdown') ?? 'Guidelines unavailable: Empty parse result.';
                }

                if ($status->json('status') === 'ERROR') {
                    return 'Guidelines unavailable: LlamaParse error.';
                }
                $attempts++;
            }

            return 'Guidelines unavailable: Parsing timed out.';
        } catch (\Exception $e) {
            Log::error('PhilHeart Guidelines Error: '.$e->getMessage());

            return 'Guidelines unavailable.';
        }
    }

    /**
     * Returns the PHA guidelines as an array of section chunks.
     * Cached for 30 days — medical guidelines don't change daily.
     * Each chunk is capped at 1 200 chars to prevent token bloat.
     *
     * This is the RAG Index step.
     *
     * @return string[] Array of guideline section strings.
     */
    private function getPhaGuidelineChunks(): array
    {
        return Cache::remember('philheart_cad_2014_chunks_v2', now()->addDays(30), function () {
            $raw = $this->downloadAndParsePhaGuidelines();
            if (str_starts_with($raw, 'Guidelines unavailable')) {
                return [];
            }

            return $this->chunkMarkdown($raw, 1200);
        });
    }

    /**
     * Splits a markdown document into sections by level-2 headers (## …).
     * Sections shorter than 80 chars (e.g. empty placeholders) are discarded.
     * Each retained section is capped at $maxLen characters.
     *
     * @param  int  $maxLen  Maximum characters per chunk.
     * @return string[]
     */
    private function chunkMarkdown(string $markdown, int $maxLen = 1200): array
    {
        $sections = preg_split('/^##\s/m', $markdown);
        $chunks = [];

        foreach ($sections as $section) {
            $trimmed = trim($section);
            if (strlen($trimmed) < 80) {
                continue;
            }
            $chunks[] = strlen($trimmed) > $maxLen
                ? substr($trimmed, 0, $maxLen).'…'
                : $trimmed;
        }

        return $chunks;
    }

    /**
     * RAG Retrieval step: scores every guideline chunk against the query
     * using term-frequency (TF) matching, then returns the top-N chunks.
     *
     * No external embedding service required — pure PHP TF scoring is
     * sufficient for a domain-specific medical document where section
     * headers use precise clinical terminology.
     *
     * @param  string  $query  Clinical query (e.g. patient diagnosis).
     * @param  int  $topN  Number of chunks to return.
     * @return string Newline-separated relevant sections, or a fallback message.
     */
    private function retrieveRelevantGuidelines(string $query, int $topN = 3): string
    {
        $chunks = $this->getPhaGuidelineChunks();
        if (empty($chunks)) {
            return 'PHA guideline sections are currently unavailable.';
        }

        // Tokenise the query: lowercase, remove punctuation, drop stop-words < 4 chars
        $terms = array_unique(array_filter(
            preg_split('/\s+/', strtolower(preg_replace('/[^a-z0-9\s]/i', ' ', $query))),
            fn ($t) => strlen($t) >= 4
        ));

        if (empty($terms)) {
            // No meaningful terms — fall back to the first $topN sections
            return implode("\n\n---\n\n", array_slice($chunks, 0, $topN));
        }

        // Score each chunk by how many query terms it contains
        $scored = array_map(function (string $chunk) use ($terms) {
            $lower = strtolower($chunk);
            $score = array_sum(array_map(fn ($t) => substr_count($lower, $t), $terms));

            return ['score' => $score, 'chunk' => $chunk];
        }, $chunks);

        usort($scored, fn ($a, $b) => $b['score'] - $a['score']);

        $top = array_slice($scored, 0, $topN);

        return implode("\n\n---\n\n", array_column($top, 'chunk'));
    }

    // ==========================================
    // 7. HELPER: AI API CALL (Triple Provider Chain)
    // ==========================================

    /**
     * Tries three providers in order: DeepSeek Direct → HuggingFace → OpenRouter.
     */
    private function callAiApi(string $prompt): string
    {
        $payload = [
            'model' => 'deepseek-v4-flash',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a highly skilled clinical cardiologist assistant.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.2,
            'max_tokens' => 8000,
        ];

        // --- 1. DEEPSEEK DIRECT ---
        $apiKey = env('DEEPSEEK_API_KEY');
        if ($apiKey) {
            try {
                $response = Http::retry(2, 1000)
                    ->withHeaders([
                        'Authorization' => 'Bearer '.$apiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->timeout(100)
                    ->post('https://api.deepseek.com/chat/completions', $payload);

                if ($response->successful()) {
                    $text = $response->json('choices.0.message.content');
                    if ($text) {
                        return $text;
                    }
                }

                Log::warning('DeepSeek API error: '.$response->status().' '.$response->body());
            } catch (\Exception $e) {
                Log::warning('DeepSeek API exception: '.$e->getMessage());
            }
        }

        // --- 2. HUGGING FACE INFERENCE ---
        $hfToken = env('HF_TOKEN');
        if ($hfToken) {
            try {
                $hfPayload = [
                    'model' => 'deepseek-ai/DeepSeek-V4-Flash',
                    'messages' => $payload['messages'],
                    'temperature' => 0.2,
                    'max_tokens' => 8000,
                ];

                $response = Http::retry(3, 5000, function ($exception, $request) {
                    return $exception instanceof RequestException
                        && $exception->response->status() === 503;
                })
                    ->withHeaders([
                        'Authorization' => 'Bearer '.$hfToken,
                        'Content-Type' => 'application/json',
                    ])
                    ->timeout(120)
                    ->post('https://api-inference.huggingface.co/models/deepseek-ai/DeepSeek-V4-Flash/v1/chat/completions', $hfPayload);

                if ($response->successful()) {
                    $data = $response->json();
                    $text = $data['choices'][0]['message']['content'] ?? null;
                    if ($text) {
                        return $text;
                    }
                }

                Log::warning('HuggingFace API error: '.$response->status().' '.$response->body());
            } catch (\Exception $e) {
                Log::warning('HuggingFace API exception: '.$e->getMessage());
            }
        }

        // --- 3. OPENROUTER (FALLBACK) ---
        $orKey = env('OPENROUTER_API_KEY');
        if ($orKey) {
            try {
                $orPayload = $payload;
                $orPayload['model'] = 'nvidia/nemotron-3-super-120b-a12b:free';

                $response = Http::retry(3, 2000, function ($exception, $request) {
                    return $exception instanceof RequestException &&
                        $exception->response->serverError();
                })
                    ->withHeaders([
                        'Authorization' => 'Bearer '.$orKey,
                        'Content-Type' => 'application/json',
                        'HTTP-Referer' => url('/'),
                        'X-Title' => 'CardioCare EMR',
                    ])
                    ->timeout(100)
                    ->post('https://openrouter.ai/api/v1/chat/completions', $orPayload);

                if ($response->successful()) {
                    $data = $response->json();
                    $text = $data['choices'][0]['message']['content'] ?? null;
                    if ($text) {
                        return $text;
                    }
                }

                Log::error('OpenRouter API Error: '.$response->body());

                return 'An error occurred with OpenRouter. Status: '.$response->status();
            } catch (\Exception $e) {
                Log::error('OpenRouter Exception: '.$e->getMessage());

                return 'An unexpected error occurred contacting OpenRouter: '.$e->getMessage();
            }
        }

        return 'An error occurred: No AI provider responded successfully.';
    }

    // ==========================================
    // 8. PRIVACY: PII Masking for AI Prompts
    // ==========================================

    /**
     * Masks personally-identifiable information in free-text fields
     * before they are sent to any third-party AI service.
     * Technique: Data Masking (GDPR Article 25 — Privacy by Design).
     */
    private function maskPii(?string $text): string
    {
        if (empty($text)) {
            return '';
        }

        // Philippine mobile numbers: 09XX-XXX-XXXX or +639XX-XXX-XXXX
        $text = preg_replace('/(\+?63|0)9\d{2}[\s\-]?\d{3}[\s\-]?\d{4}/', '[PHONE REDACTED]', $text);

        // Landline (Manila): 8-XXXX-XXXX or 02-XXXX-XXXX
        $text = preg_replace('/\b(02|8)\-?\d{3,4}\-?\d{4}\b/', '[PHONE REDACTED]', $text);

        // Email addresses
        $text = preg_replace('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', '[EMAIL REDACTED]', $text);

        // Street/barangay addresses (common Filipino pattern)
        $text = preg_replace('/\b\d+\s+[A-Za-z\s]+(St\.|Street|Ave\.|Avenue|Blvd\.|Road|Rd\.|Brgy\.|Barangay)\b/i', '[ADDRESS REDACTED]', $text);

        return $text;
    }

    /**
     * Returns [$pseudonym, $anonymizedRecords] — the pseudonym replaces the
     * real patient name; all free-text fields are PII-masked before being
     * forwarded to any external AI model.
     *
     * Records are drawn from the normalized encounter/clinical tables:
     * diagnoses (ICD-10), SOAP vitals/symptoms, and study timestamps.
     *
     * @return array{0: string, 1: array}
     */
    private function anonymizeForAi(User $patient, Collection $history): array
    {
        // Deterministic pseudonym — stable per patient, meaningless to the AI
        $pseudonym = 'PATIENT-'.strtoupper(
            substr(hash('sha256', $patient->id.config('app.key', 'fallback')), 0, 8)
        );

        $anonymizedRecords = $history->map(function (Encounter $encounter) {
            $soap = $encounter->soapNote;

            return [
                'date' => $encounter->started_at?->format('Y-m'), // month/year only — no exact date
                'diagnosis' => $this->maskPii($encounter->diagnoses->pluck('description')->implode('; ')),
                'vitals' => $this->maskPii($soap?->objective),
                'symptoms' => $this->maskPii($soap?->subjective),
            ];
        })->toArray();

        return [$pseudonym, $anonymizedRecords];
    }
}
