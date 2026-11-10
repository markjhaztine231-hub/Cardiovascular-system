<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Study exports are signed by the cardiologist who read the study (the
 * attending physician when no reader is assigned). The old "Read by" label is
 * gone; the reader's name is now the signature line itself, followed by the
 * doctor's credentials. Prescription and Clinical Requests stay attending-only
 * documents. The views receive the chosen signer as $doctor from PdfController,
 * so each view is rendered directly with a representative payload.
 */
class PdfReadBySignatureTest extends TestCase
{
    private const READER = 'Dr. Reading Cardiologist';

    private const DOCTOR = 'Dr. Attending Cardiologist';

    private const CREDENTIALS = 'MD, FPCP, FPCC';

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function studyFormProvider(): array
    {
        $base = [
            'patient' => 'Test Patient',
            'date' => 'Jan 01, 2026',
            'doctor' => self::READER,
            // Kept in the payload to prove the views no longer print it.
            'reader' => 'Stale Reader Value',
        ];

        return [
            'echo' => ['pdfs.echo', $base + ['data' => []]],
            'tmt' => ['pdfs.tmt', $base + ['data' => [], 'sex' => 'Female']],
            'doppler' => ['pdfs.doppler', $base + ['data' => [], 'echoData' => []]],
            'manual draft' => ['pdfs.manual-draft', $base + ['draft' => (object) [], 'attachments' => []]],
            'stress echo' => ['pdfs.stress-echo', $base + ['data' => []]],
            'abpm' => ['pdfs.abpm', $base + ['data' => []]],
        ];
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function attendingOnlyFormProvider(): array
    {
        $base = [
            'patient' => 'Test Patient',
            'date' => 'Jan 01, 2026',
            'doctor' => self::DOCTOR,
            'reader' => 'Stale Reader Value',
        ];

        return [
            'prescription' => ['pdfs.prescription', $base + ['prescriptions' => []]],
            'clinical requests' => ['pdfs.clinical-requests', $base + ['requests' => []]],
        ];
    }

    #[DataProvider('studyFormProvider')]
    public function test_study_form_is_signed_by_the_reading_doctor(string $view, array $data): void
    {
        $html = view($view, $data)->render();

        $this->assertStringNotContainsString('Read by', $html);
        $this->assertStringNotContainsString('Stale Reader Value', $html);
        $this->assertStringContainsString(self::READER.', '.self::CREDENTIALS, $html);
        $this->assertStringContainsString('Cardiologist', $html);
    }

    #[DataProvider('studyFormProvider')]
    public function test_study_form_falls_back_to_the_attending_doctor_when_no_reader_is_assigned(string $view, array $data): void
    {
        $data['doctor'] = self::DOCTOR;

        $html = view($view, $data)->render();

        $this->assertStringNotContainsString('Read by', $html);
        $this->assertStringContainsString(self::DOCTOR.', '.self::CREDENTIALS, $html);
    }

    #[DataProvider('attendingOnlyFormProvider')]
    public function test_attending_only_form_has_no_read_by_line(string $view, array $data): void
    {
        $html = view($view, $data)->render();

        $this->assertStringNotContainsString('Read by', $html);
        $this->assertStringNotContainsString('Stale Reader Value', $html);
        $this->assertStringContainsString(self::DOCTOR.', '.self::CREDENTIALS, $html);
        $this->assertStringContainsString('Cardiologist', $html);
    }
}
