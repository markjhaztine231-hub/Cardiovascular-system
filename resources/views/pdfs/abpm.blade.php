<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>24-Hour Ambulatory Blood Pressure Monitoring Report</title>
<style>
@page { size: 8.5in 11in; margin: 0.5in; }
body { font-family: Arial, sans-serif; font-size: 8pt; color: #000; margin: 0; line-height: 1.5; }
h2 { text-align: center; font-size: 11pt; margin: 3px 0 2px; text-transform: uppercase; }
h3 { font-size: 8pt; margin: 6px 0 2px; text-transform: uppercase; }
p { margin: 3px 0; }
table { width: 100%; border-collapse: collapse; margin-bottom: 3px; }
td, th { padding: 3px 4px; vertical-align: middle; font-size: 8pt; }
.clinic-header { text-align: center; margin-bottom: 8px; }
.clinic-name { font-size: 16pt; font-weight: bold; color: #8B0000; }
.clinic-sub { font-size: 8pt; }
.report-box { border: 1px solid #000; padding: 8px; }
.row { margin-bottom: 4px; }
.blank {
    display: inline-block;
    border-bottom: 1px solid #000;
    padding: 0 2px;
    min-height: 12px;
    line-height: 1.4;
    white-space: pre-wrap;
}
.blank.short { min-width: 70px; }
.blank.medium { min-width: 130px; }
.blank.long { min-width: 210px; }
.blank.full { min-width: 100%; }
textarea-fix { white-space: pre-wrap; }
.notes {
    border: 1px solid #000;
    min-height: 80px;
    padding: 6px;
    white-space: pre-wrap;
}
.signature-block { width: 100%; margin-top: 30px; border-collapse: collapse; }
.signature-block td { border: none; }
.signature-line {
    border-top: 1px solid #000;
    text-align: center;
    padding-top: 5px;
    width: 45%;
}
</style>
</head>
<body>
<div class="clinic-header">
    <div class="clinic-name">CARDIOVASCULAR CARE CENTER</div>
    <div class="clinic-sub">Rm 207 Mirasol bldg. 854 Apacible St, cor. Taft Ave. Mla. &bull; Tel. # 3537182</div>
</div>

<h2>24-Hour Ambulatory Blood Pressure Monitoring (ABPM)</h2>

<div class="report-box">
    <div class="row">
        <strong>Patient Name:</strong> <span class="blank long">{{ $data['patient_name'] ?? $patient }}</span>
        <strong>Age:</strong> <span class="blank short">{{ $data['age'] ?? '' }}</span>
        <strong>Sex:</strong> <span class="blank short">{{ $data['sex'] ?? '' }}</span>
        <strong>Date:</strong> <span class="blank short">{{ $date }}</span>
    </div>

    <div class="row">
        <strong>File No.:</strong> <span class="blank short">{{ $data['file_no'] ?? '' }}</span>
        <strong>Requesting Physician:</strong> <span class="blank long">{{ $data['requesting_physician'] ?? '' }}</span>
    </div>

    <div class="row">
        <strong>Indication:</strong> <span class="blank full">{{ $data['indication'] ?? '' }}</span>
    </div>

    <div class="row">
        <strong>Monitor:</strong>
        Device: <span class="blank medium">{{ $data['monitor_device'] ?? '' }}</span>
        Model: <span class="blank medium">{{ $data['monitor_model'] ?? '' }}</span>
        Cuff Size: <span class="blank short">{{ $data['cuff_size'] ?? '' }}</span>
    </div>

    <div class="row">
        <strong>Blood Pressure Medication(s):</strong> <span class="blank long">{{ $data['bp_medications'] ?? 'NONE' }}</span>
    </div>

    <div class="row">
        <strong>Monitoring Started:</strong> <span class="blank medium">{{ $data['start_date'] ?? '' }} {{ $data['start_time'] ?? '' }}</span>
        <strong>Monitoring Ended:</strong> <span class="blank medium">{{ $data['end_date'] ?? '' }} {{ $data['end_time'] ?? '' }}</span>
        <strong>Total Duration:</strong> <span class="blank short">{{ $data['total_duration'] ?? '' }}</span>
    </div>

    <h3>Blood Pressure Data</h3>
    <table>
        <tr>
            <th></th>
            <th>Mean SBP (mmHg)</th>
            <th>Mean DBP (mmHg)</th>
            <th>Mean HR (bpm)</th>
            <th>Time Range</th>
        </tr>
        <tr>
            <td><strong>Daytime (Waking)</strong></td>
            <td>{{ $data['day_sbp'] ?? '' }}</td>
            <td>{{ $data['day_dbp'] ?? '' }}</td>
            <td>{{ $data['day_hr'] ?? '' }}</td>
            <td>{{ $data['wake_from'] ?? '' }} – {{ $data['wake_to'] ?? '' }}</td>
        </tr>
        <tr>
            <td><strong>Nighttime (Sleeping)</strong></td>
            <td>{{ $data['night_sbp'] ?? '' }}</td>
            <td>{{ $data['night_dbp'] ?? '' }}</td>
            <td>{{ $data['night_hr'] ?? '' }}</td>
            <td>{{ $data['sleep_from'] ?? '' }} – {{ $data['sleep_to'] ?? '' }}</td>
        </tr>
        <tr>
            <td><strong>24-Hour Average</strong></td>
            <td>{{ $data['avg_sbp'] ?? '' }}</td>
            <td>{{ $data['avg_dbp'] ?? '' }}</td>
            <td>{{ $data['avg_hr'] ?? '' }}</td>
            <td></td>
        </tr>
    </table>

    <div class="row">
        <strong>Systolic Load:</strong> <span class="blank short">{{ $data['systolic_load'] ?? '' }}</span>%
        <strong>Diastolic Load:</strong> <span class="blank short">{{ $data['diastolic_load'] ?? '' }}</span>%
        <strong>Baseline BP:</strong> <span class="blank medium">{{ $data['baseline_bp'] ?? '' }}</span>
        <strong>Dipping Status:</strong> <span class="blank medium">{{ $data['dipping'] ?? '' }}</span>
    </div>

    <h3>Analysis / Findings</h3>
    <div class="notes">{{ $data['findings'] ?? '' }}</div>

    <h3>Interpretation</h3>
    <div class="notes">{{ $data['interpretation'] ?? '' }}</div>

    <h3>Recommendations</h3>
    <div class="notes">{{ $data['recommendations'] ?? '' }}</div>

    <h3>Conclusion</h3>
    <div class="notes">{{ $data['conclusion'] ?? '' }}</div>
</div>

<table class="signature-block">
    <tr>
        <td style="width:55%;border:none;"></td>
        <td class="signature-line">
            <strong>{{ $doctor }}</strong><br>
            <strong>NICY F. NARVAS MD, FPCP, FPCC</strong><br>
            <strong>Cardiologist</strong>
        </td>
    </tr>
</table>
</body>
</html>