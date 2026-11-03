<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Every exported clinical form prints the assigned reading cardiologist above
 * the signature as "Read by: <name>". The name is passed to the blade views by
 * PdfController, so render each view directly with a representative payload and
 * assert both the label and the resolved reader name are present.
 */
class PdfReadBySignatureTest extends TestCase
{
    private const READER = 'Dr. Reading Cardiologist';

    private const DOCTOR = 'Dr. Attending Cardiologist';

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function clinicalFormProvider(): array
    {
        $base = [
            'patient' => 'Test Patient',
            'date' => 'Jan 01, 2026',
            'doctor' => self::DOCTOR,
            'reader' => self::READER,
        ];

        return [
            'echo' => ['pdfs.echo', $base + ['data' => []]],
            'tmt' => ['pdfs.tmt', $base + ['data' => [], 'sex' => 'Female']],
            'doppler' => ['pdfs.doppler', $base + ['data' => [], 'echoData' => []]],
            'manual draft' => ['pdfs.manual-draft', $base + ['draft' => (object) [], 'attachments' => []]],
            'stress echo' => ['pdfs.stress-echo', $base + ['data' => []]],
            'abpm' => ['pdfs.abpm', $base + ['data' => []]],
            'prescription' => ['pdfs.prescription', $base + ['prescriptions' => []]],
            'clinical requests' => ['pdfs.clinical-requests', $base + ['requests' => []]],
        ];
    }

    #[DataProvider('clinicalFormProvider')]
    public function test_form_prints_read_by_above_the_signature(string $view, array $data): void
    {
        $html = view($view, $data)->render();

        $this->assertStringContainsString('Read by: '.self::READER, $html);
        $this->assertStringContainsString(self::DOCTOR, $html);
        $this->assertStringContainsString('Cardiologist', $html);
    }

    #[DataProvider('clinicalFormProvider')]
    public function test_form_still_renders_when_no_reader_is_assigned(string $view, array $data): void
    {
        $data['reader'] = null;

        $html = view($view, $data)->render();

        // The label is always present; only the name is left blank.
        $this->assertStringContainsString('Read by:', $html);
        $this->assertStringNotContainsString(self::READER, $html);
    }
}
