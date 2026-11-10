@php
    $d = $data ?? [];
    $stages = is_array($d['stages'] ?? null) ? $d['stages'] : [];
    $lastStage = ! empty($stages) ? end($stages) : [];
    $lastStageName = is_array($lastStage) ? ($lastStage['stage'] ?? '') : '';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Stress Echocardiogram Report</title>
    <style>
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #000;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
        }

        /* Header Styles */
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        .header-title {
            font-size: 22px;
            font-weight: bold;
            color: #a82036;
            margin: 0;
            letter-spacing: 1px;
        }
        .header-title span {
            color: #3b7bb5;
        }
        .header-sub {
            font-size: 11px;
            margin-top: 5px;
            color: #333;
        }

        /* Main Titles */
        .report-title {
            text-align: center;
            font-weight: bold;
            font-size: 14px;
            text-decoration: underline;
            margin: 30px 0 20px 0;
        }

        /* Form Elements */
        .info-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 5px 0;
            vertical-align: bottom;
        }

        /* Blank lines for filling up */
        .blank {
            display: inline-block;
            border-bottom: 1px solid #000;
            min-height: 14px;
            vertical-align: bottom;
            margin: 0 2px;
        }
        .blank-xs { width: 30px; }
        .blank-sm { width: 50px; }
        .blank-md { width: 80px; }
        .blank-lg { width: 120px; }
        .blank-xl { width: 200px; }

        /* Narrative answer boxes */
        .notes {
            border: 1px solid #000;
            min-height: 60px;
            padding: 6px;
            white-space: pre-wrap;
            text-align: left;
            margin: 0 0 10px 0;
        }

        /* Paragraphs and Lists */
        p {
            margin: 0 0 10px 0;
            text-align: justify;
        }
        ol {
            margin: 0 0 15px 0;
            padding-left: 20px;
        }
        ol li {
            margin-bottom: 12px;
            text-align: justify;
        }

        /* Signature Section */
        .signature-section {
            margin-top: 60px;
            text-align: right;
            padding-right: 50px;
        }
        .doctor-name {
            font-weight: bold;
            text-decoration: underline;
            text-transform: uppercase;
        }
        .doctor-title {
            font-size: 12px;
        }
    </style>
</head>
<body>

    <div class="header">
        @include('pdfs.partials.clinic-header', [
            'name' => 'CARDIOVASCULAR <span style="color:#3b7bb5;">CARE CENTER</span>',
            'nameColor' => '#a82036',
            'nameSize' => '22px',
            'sub' => 'Rm 207 Mirasol Bldg. 854 Apacible St, cor. Taft Ave. Mla., Tel. # 3537182',
            'subSize' => '11px',
            'subColor' => '#333',
            'logoHeight' => '0.6in',
        ])
    </div>

    <div class="report-title">STRESS ECHOCARDIOGRAM REPORT</div>

    <table class="info-table">
        <tr>
            <td>NAME: <span class="blank" style="width: 220px;">{{ $d['patient_name'] ?? $patient }}</span></td>
            <td style="text-align: center;">AGE: <span class="blank blank-md">{{ $d['age'] ?? '' }}</span></td>
            <td style="text-align: right;">DATE: <span class="blank blank-xl">{{ $date }}</span></td>
        </tr>
    </table>

    @if (! empty($d['conclusion']))
        <div class="notes">{{ $d['conclusion'] }}</div>
    @endif

    <div class="signature-section">
        <div class="doctor-name">{{ $doctor }}, MD, FPCP, FPCC</div>
        <div class="doctor-title">Cardiologist</div>
    </div>

</body>
</html>
