<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Treadmill Exercise Test</title>
<style>
@page { size: 8.5in 11in; margin: 0.75in; }
body { font-family: Arial, sans-serif; font-size: 9pt; color: #000; margin: 0; }
table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
td, th { padding: 4px 5px; border: 1px solid #000; vertical-align: middle; font-size: 8pt; }
h2 { text-align: center; font-size: 12pt; margin: 6px 0 4px; text-transform: uppercase; }
h3 { font-size: 9pt; margin: 8px 0 2px; text-transform: uppercase; }
.no-border td, .no-border th { border: none !important; }
.clinic-header { text-align: center; margin-bottom: 10px; }
.clinic-name { font-size: 16pt; font-weight: bold; color: #8B0000; }
.clinic-sub { font-size: 8pt; }
.page-break { page-break-before: always; }
.bg-gray { background-color: #e5e5e5; }
.uline { border-bottom: 1px solid #000; display: inline-block; min-width: 120px; }
</style>
</head>
<body>
{{-- PAGE 1: Informed Consent --}}
<div class="clinic-header">
  <div class="clinic-name">CARDIOVASCULAR CARE CENTER</div>
  <div class="clinic-sub">Rm. 303/305 Mirasol Building, 854 Apacible St., Taft Avenue, Manila &bull; Tel: 8-353-7182</div>
</div>
<h3 style="text-align:center;text-decoration:underline;">INFORMED CONSENT FOR TREADMILL EXERCISE TEST</h3>
<table class="no-border">
  <tr>
    <td style="width:50%;">
      <table class="no-border">
        <tr><td style="border:none;">Date:</td><td style="border-bottom:1px solid #000;border-top:none;border-left:none;border-right:none;">&#10140; {{ $date }}</td></tr>
        <tr><td style="border:none;">Age:</td><td style="border-bottom:1px solid #000;border-top:none;border-left:none;border-right:none;">&#10140; {{ $data['age'] ?? '' }}</td></tr>
        <tr><td style="border:none;">Height:</td><td style="border-bottom:1px solid #000;border-top:none;border-left:none;border-right:none;">&#10140; {{ $data['height'] ?? '' }}</td></tr>
        <tr><td style="border:none;">Weight:</td><td style="border-bottom:1px solid #000;border-top:none;border-left:none;border-right:none;">&#10140; {{ $data['weight'] ?? '' }}</td></tr>
        <tr><td style="border:none;">Cell No:</td><td style="border-bottom:1px solid #000;border-top:none;border-left:none;border-right:none;">&#10140; {{ $data['cell_no'] ?? '' }}</td></tr>
        <tr><td style="border:none;">Birthday:</td><td style="border-bottom:1px solid #000;border-top:none;border-left:none;border-right:none;">&#10140; {{ $data['birthday'] ?? '' }}</td></tr>
        <tr><td style="border:none;">Medicines:</td><td style="border-bottom:1px solid #000;border-top:none;border-left:none;border-right:none;">&#10140; {{ $data['medicines'] ?? '' }}</td></tr>
      </table>
    </td>
    <td style="width:50%;">
      <table class="no-border">
        <tr><td style="border:none;">PMHR:</td><td style="border-bottom:1px solid #000;border-top:none;border-left:none;border-right:none;">{{ $data['pmhr'] ?? '' }}</td></tr>
        <tr><td style="border:none;">BP:</td><td style="border-bottom:1px solid #000;border-top:none;border-left:none;border-right:none;">{{ $data['bp'] ?? '' }}</td></tr>
      </table>
    </td>
  </tr>
</table>
<table class="no-border" style="margin-top:10px;">
  <tr>
    <td style="border:none;">Requesting Physician:</td>
    <td style="border-bottom:1px solid #000;border-top:none;border-left:none;border-right:none;">{{ $data['requesting_physician'] ?? '' }}</td>
    <td style="border:none;">Procedure:</td>
    <td style="border-bottom:1px solid #000;border-top:none;border-left:none;border-right:none;">{{ $data['procedure'] ?? 'Treadmill Exercise Test' }}</td>
  </tr>
  <tr>
    <td style="border:none;">Attending Physician:</td>
    <td colspan="3" style="border-bottom:1px solid #000;border-top:none;border-left:none;border-right:none;">{{ $data['attending_physician'] ?? '' }}</td>
  </tr>
</table>
<p><strong>TO WHOM IT MAY CONCERN:</strong></p>
<p style="line-height:1.8;text-indent:30px;">
  I, &#10140; <u><strong>{{ $patient }}</strong></u> of legal age and with residence and postal address at
  <u><strong>{{ $data['residence'] ?? '' }}</strong></u>, hereby consent to the performance of Treadmill Exercise Test on me by
  <u><strong>{{ $data['consent_to'] ?? 'the attending physician' }}</strong></u>.
</p>
<p style="line-height:1.8;text-indent:30px;">This consent was given after the above have been fully explained in simple, adequate, and understandable language to me by the doctors concerned including the risks involved or potential consequence.</p>
<p style="line-height:1.8;text-indent:30px;">
  IN WITNESS WHEREOF, I hereunto affix my signature / right "thumb mark" this
  <u><strong>{{ $data['consent_day'] ?? '______' }}</strong></u> day of
  <u><strong>{{ $data['consent_month'] ?? '____________' }}</strong></u> 20__ at
  <u><strong>{{ $data['consent_place'] ?? '________________' }}</strong></u>.
</p>
<table class="no-border" style="margin-top:40px;">
  <tr>
    <td style="width:40%;border:none;"></td>
    <td style="width:60%;border-top:1px solid #000;text-align:center;border-left:none;border-right:none;border-bottom:none;font-size:8pt;">(Signature over printed name of patient or person giving free consent or their right "thumb mark")</td>
  </tr>
</table>

{{-- PAGE 2: Exercise Testing Report --}}
<div class="page-break"></div>
<div class="clinic-header">
  <div class="clinic-name">&#9825; CARDIOVASCULAR CARE CENTER</div>
  <h3 style="margin:5px 0 15px;">EXERCISE TESTING REPORT</h3>
</div>
<table class="no-border" style="line-height:1.8;">
  <tr>
    <td style="border:none;width:60%;">NAME: <u><strong>{{ $patient }}</strong></u></td>
    <td style="border:none;width:40%;">Age: <u><strong>{{ $data['age'] ?? '' }}</strong></u></td>
  </tr>
  <tr>
    <td colspan="2" style="border:none;">
      Date Performed: <u><strong>{{ $date }}</strong></u> &nbsp;
      Sex: <u><strong>{{ $sex }}</strong></u> &nbsp;
      Weight: <u><strong>{{ $data['weight'] ?? '' }}</strong></u> &nbsp;
      Height: <u><strong>{{ $data['height'] ?? '' }}</strong></u>
    </td>
  </tr>
  <tr>
    <td style="border:none;">Requesting Physician: <u><strong>{{ $data['requesting_physician'] ?? '' }}</strong></u></td>
    <td style="border:none;">Medications: <u><strong>{{ $data['medicines'] ?? '' }}</strong></u></td>
  </tr>
  <tr>
    <td colspan="2" style="border:none;text-align:right;">PMHR for AGE: <u><strong>{{ $data['pmhr'] ?? '' }}</strong></u></td>
  </tr>
</table>
<p style="margin-top:10px;"><strong>Protocol:</strong> BRUCE, MODIFIED BRUCE, NIH &nbsp; | &nbsp; <strong>Indication:</strong> <u>{{ $data['indication'] ?? '' }}</u></p>
<table style="font-size:9pt;margin-top:10px;">
  <thead>
    <tr class="bg-gray">
      <th>Event</th><th>Speed (mph)</th><th>Grade (%)</th><th>METS</th><th>BP</th><th>HR</th><th>Remarks</th>
    </tr>
  </thead>
  <tbody>
    @foreach($data['stages'] ?? [] as $stage)
    @php $isRec = str_contains($stage['name'] ?? '', 'Recovery') || str_contains($stage['name'] ?? '', 'min'); @endphp
    <tr @if(!$isRec) style="background:#e5e5e5;" @endif>
      <td>{{ $stage['name'] ?? '' }}</td>
      <td style="text-align:center;">{{ $stage['speed'] ?? '' }}</td>
      <td style="text-align:center;">{{ $stage['grade'] ?? '' }}</td>
      <td style="text-align:center;">{{ $stage['mets'] ?? '' }}</td>
      <td>{{ $stage['bp'] ?? '' }}</td>
      <td>{{ $stage['hr'] ?? '' }}</td>
      <td>{{ $stage['remarks'] ?? '' }}</td>
    </tr>
    @endforeach
  </tbody>
</table>
<div style="margin-top:15px;font-size:9pt;">
  <p><strong>INTERPRETATION:</strong></p>
  <p>A. Resting ECG: <u>{{ $data['resting_ecg'] ?? '' }}</u></p>
  <p>B. Response to Exercise: <u>{{ $data['response_to_exercise'] ?? '' }}</u></p>
  <p>C. Exercise Capacity: <u>{{ $data['exercise_capacity'] ?? '' }}</u></p>
  <p>D. Arrhythmias: <u>{{ $data['arrhythmias'] ?? '' }}</u></p>
  <p>E. Probability of CAD: <u>{{ $data['probability_cad'] ?? '' }}</u></p>
  <p style="margin-top:10px;"><strong>CONCLUSION:</strong> <u>{{ $data['conclusion'] ?? '' }}</u></p>
</div>
<table class="no-border" style="margin-top:40px;">
  <tr>
    <td style="width:60%;border:none;"></td>
    <td style="width:40%;border-top:1px solid #000;text-align:center;border-left:none;border-right:none;border-bottom:none;"><strong>Cardiologist</strong></td>
  </tr>
</table>
</body>
</html>
