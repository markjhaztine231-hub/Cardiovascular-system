@php
    $d = $data ?? [];
    $bp = function ($sys, $dia) {
        $sys = trim((string) ($sys ?? ''));
        $dia = trim((string) ($dia ?? ''));
        if ($sys === '' && $dia === '') {
            return '';
        }
        return trim($sys.'/'.$dia, '/');
    };
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Ambulatory Blood Pressure Monitoring</title>
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
        <h1 class="header-title">CARDIOVASCULAR <span>CARE CENTER</span></h1>
        <div class="header-sub">
            Rm 207 Mirasol bldg. 854 Apacible St, cor. Taft Ave. Mla., Tel. # 3537182
        </div>
    </div>

    <div class="report-title">AMBULATORY BLOOD PRESSURE MONITORING</div>

    <table class="info-table">
        <tr>
            <td>PATIENT NAME: <span class="blank" style="width: 250px;">{{ $d['patient_name'] ?? $patient }}</span></td>
            <td style="text-align: center;">AGE: <span class="blank blank-md">{{ $d['age'] ?? '' }}</span></td>
            <td style="text-align: right;">DATE: <span class="blank blank-lg">{{ $date }}</span></td>
        </tr>
    </table>

    <p>Patient's blood pressure was monitored for <span class="blank blank-sm"></span> hours and <span class="blank blank-sm"></span> minutes.</p>
    <p>registering blood pressure measurements.</p>

    <ol>
        <li>
            The average awake blood pressure was <span class="blank blank-md">{{ $bp($d['day_sbp'] ?? '', $d['day_dbp'] ?? '') }}</span> mmHg and average heart rate of <span class="blank blank-sm">{{ $d['day_hr'] ?? '' }}</span> bpm.
            <span class="blank blank-sm"></span> % of all awake systolic bp and <span class="blank blank-sm"></span> % of all awake diastolic blood pressure exceeds the threshold of <span class="blank blank-md"></span> mmHg.
        </li>
        <li>
            The average asleep blood pressure is <span class="blank blank-md">{{ $bp($d['night_sbp'] ?? '', $d['night_dbp'] ?? '') }}</span> and average heart rate of <span class="blank blank-sm">{{ $d['night_hr'] ?? '' }}</span> bpm.
            <span class="blank blank-sm"></span> % of all asleep systolic bp and <span class="blank blank-sm"></span> % of all asleep diastolic blood pressure is above threshold of <span class="blank blank-md"></span> mmHg.
        </li>
        <li>
            Overall, the average blood pressure and <span class="blank blank-md">{{ $bp($d['avg_sbp'] ?? '', $d['avg_dbp'] ?? '') }}</span> mmHg and average heart rate of <span class="blank blank-sm">{{ $d['avg_hr'] ?? '' }}</span> bpm.
            <span class="blank blank-sm">{{ $d['systolic_load'] ?? '' }}</span> % of all systolic bp and <span class="blank blank-sm">{{ $d['diastolic_load'] ?? '' }}</span> % of diastolic bp exceeded the threshold of <span class="blank blank-md"></span> mmHg.
        </li>
        <li>
            The change between the average awake and asleep blood pressure was <span class="blank blank-sm"></span> % / <span class="blank blank-sm"></span> %.
        </li>
        <li>
            <span class="blank blank-sm"></span> significant symptoms reported.
        </li>
    </ol>

    <br>

    <p><strong>ANALYSIS / FINDINGS:</strong></p>
    <div class="notes">{{ $d['findings'] ?? '' }}</div>

    <p><strong>INTERPRETATION:</strong></p>
    <div class="notes">{{ $d['interpretation'] ?? '' }}</div>

    <p><strong>RECOMMENDATIONS:</strong></p>
    <div class="notes">{{ $d['recommendations'] ?? '' }}</div>

    <p><strong>CONCLUSION:</strong></p>
    <p>
        Overall 24 hour ambulatory blood pressure monitoring showed <span class="blank blank-xl"></span> response with <span class="blank blank-xl"></span>.
    </p>
    @if (! empty($d['conclusion']))
        <div class="notes">{{ $d['conclusion'] }}</div>
    @endif

    <div class="signature-section">
        <div class="doctor-name">NICY F. NARVAS, MD, FPCP, FPCC</div>
        <div class="doctor-title">Cardiologist</div>
    </div>

</body>
</html>
