<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Stress Echocardiogram Report</title>
<style>
@page { size: 8.5in 11in; margin: 0.5in; }
body { font-family: Arial, sans-serif; font-size: 8pt; color: #000; margin: 0; line-height: 1.5; }
h2 { text-align: center; font-size: 11pt; margin: 3px 0 2px; text-transform: uppercase; }
h3 { font-size: 8pt; margin: 6px 0 2px; text-transform: uppercase; }
p { margin: 3px 0; }
table { width: 100%; border-collapse: collapse; margin-bottom: 3px; }
td, th { padding: 3px 4px; border: 1px solid #000; vertical-align: middle; font-size: 8pt; }
th { background: #e5e5e5; }
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
    border: none;
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

<h2>Stress Echocardiogram</h2>
<h2 style="font-size:9pt;">(Exercise Stress Test with Echocardiography)</h2>

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
        <strong>Medications:</strong> <span class="blank long">{{ $data['medications'] ?? 'NONE' }}</span>
    </div>

    <div class="row">
        <strong>Pre-Test:</strong>
        BP: <span class="blank short">{{ $data['pre_bp'] ?? '' }}</span>
        HR: <span class="blank short">{{ $data['pre_hr'] ?? '' }}</span>
        Resting ECG: <span class="blank medium">{{ $data['pre_ecg'] ?? '' }}</span>
        Symptoms: <span class="blank medium">{{ $data['pre_symptoms'] ?? '' }}</span>
    </div>

    <div class="row">
        <strong>Exercise Protocol:</strong> <span class="blank long">{{ $data['protocol'] ?? '' }}</span>
    </div>

    <h3>Exercise Data</h3>
    <table>
        <tr>
            <th>Stage</th>
            <th>Time (min)</th>
            <th>Speed (mph)</th>
            <th>Grade (%)</th>
            <th>HR (bpm)</th>
            <th>BP (mmHg)</th>
            <th>ECG Changes</th>
            <th>Symptoms / Rhythm</th>
        </tr>
        @php($stages = $data['stages'] ?? [])
        @forelse ($stages as $stage)
        <tr>
            <td>{{ $stage['stage'] ?? '' }}</td>
            <td>{{ $stage['time'] ?? '' }}</td>
            <td>{{ $stage['speed'] ?? '' }}</td>
            <td>{{ $stage['grade'] ?? '' }}</td>
            <td>{{ $stage['hr'] ?? '' }}</td>
            <td>{{ $stage['bp'] ?? '' }}</td>
            <td>{{ $stage['ecg'] ?? '' }}</td>
            <td>{{ $stage['symptoms'] ?? '' }}</td>
        </tr>
        @empty
        <tr>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
            <td>&nbsp;</td>
        </tr>
        @endforelse
    </table>

    <div class="row">
        <strong>Max HR:</strong> <span class="blank short">{{ $data['peak_hr'] ?? '' }}</span> bpm
        <strong>Target HR:</strong> <span class="blank short">{{ $data['target_hr'] ?? '' }}</span> bpm
        <strong>Peak BP:</strong> <span class="blank short">{{ $data['peak_bp'] ?? '' }}</span>
        <strong>METs:</strong> <span class="blank short">{{ $data['mets'] ?? '' }}</span>
        <strong>Reached Target:</strong> <span class="blank short">{{ $data['reached_target'] ?? '' }}</span>
    </div>

    <h3>Echocardiographic Findings</h3>
    <div class="row">
        <strong>At Rest:</strong> EF: <span class="blank short">{{ $data['rest_ef'] ?? '' }}</span>%
        Wall Motion: <span class="blank long">{{ $data['rest_wma'] ?? '' }}</span>
    </div>
    <div class="row">
        <strong>At Peak Stress:</strong> EF: <span class="blank short">{{ $data['peak_ef'] ?? '' }}</span>%
        Wall Motion: <span class="blank long">{{ $data['peak_wma'] ?? '' }}</span>
    </div>
    <div class="row">
        <strong>Ischaemia / Scar / Viability:</strong> <span class="blank long">{{ $data['ischaemia'] ?? '' }}</span>
    </div>

    <h3>Findings / Interpretation</h3>
    <div class="notes">{{ $data['findings'] ?? '' }}</div>

    <h3>Conclusion</h3>
    <div class="notes">{{ $data['conclusion'] ?? '' }}</div>
</div>

<table class="signature-block">
    <tr>
        <td style="width:55%;border:none;"></td>
        <td class="signature-line">
            <strong>{{ $doctor }}</strong><br>
            <strong>MAIDENLOVE RAGUERO-PANER MD, FPCP, FPCC</strong><br>
            <strong>Cardiologist</strong>
        </td>
    </tr>
</table>
</body>
</html>