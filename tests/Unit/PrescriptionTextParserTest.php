<?php

namespace Tests\Unit;

use App\Services\PrescriptionTextParser;
use Tests\TestCase;

class PrescriptionTextParserTest extends TestCase
{
    private PrescriptionTextParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = app(PrescriptionTextParser::class);
    }

    public function test_it_returns_one_row_per_drug_instead_of_collapsing_the_document(): void
    {
        $text = "Dr. Sharma Clinic\nName: Ramesh Patel\nAge:54/M\nTab Amlodipine 5mg - once daily\nTab Metformin 500mg - twice daily\nFollow up: 2 weeks";

        $medications = $this->parser->parse($text)['medications'];

        $this->assertCount(2, $medications);
        $this->assertSame('Amlodipine', $medications[0]['medication_name']);
        $this->assertSame('Metformin', $medications[1]['medication_name']);
    }

    public function test_it_never_treats_the_clinic_header_as_a_drug_name(): void
    {
        $medications = $this->parser->parse("Dr. Sharma Clinic\nTab Amlodipine 5mg once daily")['medications'];

        $this->assertCount(1, $medications);
        $this->assertSame('Amlodipine', $medications[0]['medication_name']);
    }

    public function test_it_keeps_each_drugs_own_frequency_instead_of_unioning_them(): void
    {
        $text = "Tab Amlodipine 5mg - once daily\nTab Metformin 500mg - twice daily\nTab Atorvastatin 10mg - at bedtime";

        $medications = $this->parser->parse($text)['medications'];

        $this->assertSame('Once daily', $medications[0]['frequency']);
        $this->assertSame('Twice daily', $medications[1]['frequency']);
        $this->assertSame('At bedtime', $medications[2]['frequency']);
    }

    public function test_it_does_not_treat_a_follow_up_interval_as_a_duration(): void
    {
        $medications = $this->parser->parse("Tab Amlodipine 5mg once daily\nFollow up: 2 weeks")['medications'];

        $this->assertNull($medications[0]['duration_days']);
    }

    public function test_it_normalises_duration_units_into_days(): void
    {
        $this->assertSame(14, $this->parser->parseFirst('Tab Amlodipine 5mg once daily for 2 weeks')['duration_days']);
        $this->assertSame(10, $this->parser->parseFirst('Tab Amlodipine 5mg once daily for 10 days')['duration_days']);
        $this->assertSame(30, $this->parser->parseFirst('Tab Amlodipine 5mg once daily for 1 month')['duration_days']);
        $this->assertSame(365, $this->parser->parseFirst('Tab Amlodipine 5mg once daily for 1 year')['duration_days']);
    }

    public function test_it_keeps_a_tablet_count_out_of_the_drug_name(): void
    {
        $text = "Tab Amlodipine 5mg - 1 tab once daily\nTab Metformin 500mg - 2 tabs twice daily";

        $medications = $this->parser->parse($text)['medications'];

        $this->assertSame('Amlodipine', $medications[0]['medication_name']);
        $this->assertSame('5mg', $medications[0]['dosage']);
        $this->assertSame('Once daily', $medications[0]['frequency']);
        $this->assertSame('Metformin', $medications[1]['medication_name']);
        $this->assertSame('500mg', $medications[1]['dosage']);
        $this->assertSame('Twice daily', $medications[1]['frequency']);
    }

    public function test_it_does_not_turn_a_review_interval_into_a_duration(): void
    {
        $text = "Tab Amlodipine 5mg - 1 tab once daily\nAdvice: Low salt diet, review in 4 weeks";

        $medications = $this->parser->parse($text)['medications'];

        $this->assertCount(1, $medications);
        $this->assertNull($medications[0]['duration_days']);
    }

    public function test_it_still_reads_a_real_course_length_on_the_same_line_as_a_review_interval(): void
    {
        $row = $this->parser->parseFirst('Tab Atorvastatin 20mg at night for 30 days, review in 4 weeks');

        $this->assertSame(30, $row['duration_days']);
    }

    public function test_it_ignores_non_duration_numbers(): void
    {
        $this->assertNull($this->parser->parseFirst('Tab Amlodipine 5mg once daily for 3 hrs')['duration_days']);
    }

    public function test_it_merges_continuation_lines_into_the_drug_above_them(): void
    {
        $text = "1. Tab Amlodipine\n5mg, once daily for 10 days\n2. Cap Metformin\n500 mg BD for 5 weeks";

        $medications = $this->parser->parse($text)['medications'];

        $this->assertCount(2, $medications);
        $this->assertSame('Amlodipine', $medications[0]['medication_name']);
        $this->assertSame('5mg', $medications[0]['dosage']);
        $this->assertSame('Once daily', $medications[0]['frequency']);
        $this->assertSame(10, $medications[0]['duration_days']);
        $this->assertSame('Metformin', $medications[1]['medication_name']);
        $this->assertSame(35, $medications[1]['duration_days']);
    }

    public function test_it_extracts_route_and_dosage_per_drug(): void
    {
        $text = "Cap Amoxicillin 500mg PO three times daily with meals for 7 days\nInj Ceftriaxone 1g IV once daily for 3 days";

        $medications = $this->parser->parse($text)['medications'];

        $this->assertSame('Amoxicillin', $medications[0]['medication_name']);
        $this->assertSame('500mg', $medications[0]['dosage']);
        $this->assertSame('Oral', $medications[0]['route']);
        $this->assertSame(7, $medications[0]['duration_days']);

        $this->assertSame('Ceftriaxone', $medications[1]['medication_name']);
        $this->assertSame('1g', $medications[1]['dosage']);
        $this->assertSame('Intravenous', $medications[1]['route']);
        $this->assertSame(3, $medications[1]['duration_days']);
    }

    public function test_it_reports_when_no_medication_lines_were_found(): void
    {
        $result = $this->parser->parse("Dr. Sharma Clinic\nName: Ramesh Patel\nAge: 54\nFollow up: 2 weeks");

        $this->assertSame([], $result['medications']);
        $this->assertNotEmpty($result['warnings']);
    }

    public function test_it_drops_low_confidence_lines_when_given_structured_ocr_output(): void
    {
        $lines = [
            ['text' => 'Tab Amlodipine 5mg - once daily', 'confidence' => 0.94],
            ['text' => 'Tab Metformin 500mg - twice daliy', 'confidence' => 0.31],
            ['text' => 'Tab Atorvastatin 10mg - once daily', 'confidence' => 0.91],
        ];

        $result = $this->parser->parse('', $lines);

        $this->assertCount(2, $result['medications']);
        $this->assertNotEmpty($result['warnings']);
    }

    public function test_it_falls_back_to_text_when_no_structured_lines_are_given(): void
    {
        $result = $this->parser->parse('Tab Amlodipine 5mg once daily', []);

        $this->assertCount(1, $result['medications']);
    }

    public function test_it_fits_the_prescriptions_column_limits(): void
    {
        $row = $this->parser->parseFirst('Tab '.str_repeat('Longdrugname', 20).' 500mg once daily for 30 days');

        $this->assertLessThanOrEqual(150, strlen($row['medication_name']));
        $this->assertLessThanOrEqual(100, strlen($row['dosage']));
        $this->assertLessThanOrEqual(32767, $row['duration_days']);
    }
}
