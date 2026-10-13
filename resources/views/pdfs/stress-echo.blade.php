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
        <h1 class="header-title">CARDIOVASCULAR <span>CARE CENTER</span></h1>
        <div class="header-sub">
            Rm 207 Mirasol Bldg. 854 Apacible St, cor. Taft Ave. Mla., Tel. # 3537182
        </div>
    </div>

    <div class="report-title">STRESS ECHOCARDIOGRAM REPORT</div>

    <table class="info-table">
        <tr>
            <td>NAME: <span class="blank" style="width: 220px;">{{ $d['patient_name'] ?? $patient }}</span></td>
            <td style="text-align: center;">AGE: <span class="blank blank-md">{{ $d['age'] ?? '' }}</span> / <span class="blank blank-md"></span></td>
            <td style="text-align: right;">DATE: <span class="blank blank-xl">{{ $date }}</span></td>
        </tr>
    </table>

    <p><strong>Baseline ECG and Echocardiogram</strong></p>
    <p>Baseline Blood Pressure: <span class="blank blank-md">{{ $d['pre_bp'] ?? '' }}</span></p>
    <p>Baseline Heart Rate: <span class="blank blank-sm">{{ $d['pre_hr'] ?? '' }}</span></p>
    <p>Baseline ECG: <span class="blank blank-xl">{{ $d['pre_ecg'] ?? '' }}</span></p>

    <br>

    <p><strong>Stress Findings:</strong></p>
    <p>
        Treadmill stress test was done using the <span class="blank blank-md">{{ $d['protocol'] ?? '' }}</span> Protocol and was carried up to stage <span class="blank blank-sm">{{ $lastStageName }}</span>. The total duration of exercise was <span class="blank blank-sm"></span> minutes achieving a maximum heart rate of <span class="blank blank-sm">{{ $d['peak_hr'] ?? '' }}</span> bpm which is <span class="blank blank-sm"></span> % of the predicted maximum heart rate for age at <span class="blank blank-sm">{{ $d['mets'] ?? '' }}</span> METS. Maximum blood pressure achieved at peak exercise was <span class="blank blank-md">{{ $d['peak_bp'] ?? '' }}</span> mmHg. There were <span class="blank blank-sm"></span> cardiac complaints during and after exercise.
    </p>
    <p>
        Serial Treadmill Stress Electrocardiogram showed <span class="blank blank-md"></span> ST-T wave changes noted. <span class="blank blank-sm"></span> arrhythmia noted during exercise and recovery period. The test was stopped due to <span class="blank blank-md"></span>.
    </p>
    <p>
        At immediate post exercise, the echocardiogram showed <span class="blank blank-md"></span> in contractility in all segments. The left ventricular ejection fraction increased from <span class="blank blank-sm">{{ $d['rest_ef'] ?? '' }}</span> % to <span class="blank blank-sm">{{ $d['peak_ef'] ?? '' }}</span> %.
    </p>

    <p><strong>FINDINGS / INTERPRETATION:</strong></p>
    <div class="notes">{{ $d['findings'] ?? '' }}</div>

    <br>

    <p><strong>Conclusion:</strong></p>
    <p>
        Treadmill exercise test using the <span class="blank blank-md">{{ $d['protocol'] ?? '' }}</span> Protocol was maximal and negative for exercise-induced myocardial ischemia at <span class="blank blank-sm">{{ $d['mets'] ?? '' }}</span> mets.
    </p>
    <p>
        Treadmill Stress Echocardiogram was negative for exercise-induced ischemia at <span class="blank blank-sm">{{ $d['mets'] ?? '' }}</span> METS.
    </p>
    <p>
        Physiologic blood pressure response to exercise.
    </p>
    <p>
        <span class="blank blank-md"></span> exercise capacity was <span class="blank blank-md"></span> for age.
    </p>
    @if (! empty($d['conclusion']))
        <div class="notes">{{ $d['conclusion'] }}</div>
    @endif

    <div class="signature-section">
        <div class="doctor-name">MAIDENLOVE RAGUERO-PANER MD, FPCP, FPCC</div>
        <div class="doctor-title">Cardiologist</div>
    </div>

</body>
</html>
