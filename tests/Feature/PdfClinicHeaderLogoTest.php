<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Every PDF prints the clinic logo beside the clinic name. The logo is embedded
 * as a JPEG data URI because Dompdf decodes PNGs through the GD extension,
 * which is not guaranteed to be installed.
 */
class PdfClinicHeaderLogoTest extends TestCase
{
    public function test_clinic_header_embeds_the_logo(): void
    {
        $this->assertFileExists(public_path('logo.jpg'));

        $html = view('pdfs.partials.clinic-header')->render();

        $this->assertStringContainsString('data:image/jpeg;base64,', $html);
        $this->assertStringContainsString('CARDIOVASCULAR CARE CENTER', $html);
    }

    public function test_every_pdf_view_uses_the_shared_clinic_header(): void
    {
        $views = glob(resource_path('views/pdfs/*.blade.php')) ?: [];

        $this->assertNotEmpty($views);

        foreach ($views as $view) {
            $this->assertStringContainsString(
                "@include('pdfs.partials.clinic-header'",
                file_get_contents($view),
                basename($view).' does not include the shared clinic header.'
            );
        }
    }
}
