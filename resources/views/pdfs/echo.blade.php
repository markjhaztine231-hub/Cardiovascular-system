<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Echocardiographic Report</title>
<style>
@page { size: 8.5in 11in; margin: 0.5in; }
body { font-family: Arial, sans-serif; font-size: 8pt; color: #000; margin: 0; }
table { width: 100%; border-collapse: collapse; margin-bottom: 3px; }
td, th { padding: 2px 3px; border: 1px solid #000; vertical-align: middle; font-size: 7pt; }
h2 { text-align: center; font-size: 10pt; margin: 3px 0 2px; text-transform: uppercase; }
h3 { font-size: 8pt; margin: 4px 0 2px; text-transform: uppercase; }
.no-border td { border: none !important; }
.center { text-align: center; }
.clinic-header { text-align: center; margin-bottom: 10px; }
.clinic-name { font-size: 16pt; font-weight: bold; color: #8B0000; }
.clinic-sub { font-size: 8pt; }
.section-head td { background: #e5e5e5; font-weight: bold; }
</style>
</head>
<body>
<div class="clinic-header">
  <div class="clinic-name">CARDIOVASCULAR CARE CENTER</div>
  <div class="clinic-sub">Rm. 303/305 Mirasol Building, 854 Apacible St., Taft Avenue, Manila &bull; Tel: 8-353-7182</div>
</div>

<h2>Echocardiographic Report</h2>

<table>
  <tr>
    <td colspan="3"><strong>Patient Name:</strong> {{ $patient }}</td>
    <td colspan="2"><strong>Contact No:</strong> {{ $data['contact_no'] ?? '' }}</td>
  </tr>
  <tr>
    <td colspan="5"><strong>Requesting Physician:</strong> {{ $data['requesting_physician'] ?? '' }}</td>
  </tr>
  <tr>
    <td><strong>File #:</strong> {{ $data['file_no'] ?? '' }}</td>
    <td><strong>BSA:</strong> {{ $data['bsa'] ?? '' }}</td>
    <td colspan="3"><strong>Date:</strong> {{ $date }}</td>
  </tr>
  <tr>
    <td><strong>Age:</strong> {{ $data['age'] ?? '' }}</td>
    <td><strong>Sex:</strong> {{ $data['sex'] ?? '' }}</td>
    <td><strong>Height:</strong> {{ $data['height'] ?? '' }} cm</td>
    <td colspan="2"><strong>Weight:</strong> {{ $data['weight'] ?? '' }} kgs.</td>
  </tr>
</table>

<table>
  <colgroup>
    <col style="width:24%"><col style="width:9%"><col style="width:7%">
    <col style="width:24%"><col style="width:9%"><col style="width:7%">
  </colgroup>
  <tr class="section-head"><td colspan="3">Mitral Valve</td><td colspan="3">Interventricular Septum</td></tr>
  <tr><td>ED Velocity</td><td>{{ $data['mitral_ef'] ?? '' }}</td><td>mm²</td><td>Diastolic diameter</td><td>{{ $data['ivs_dia'] ?? '' }}</td><td>mm</td></tr>
  <tr><td>DE Excursion</td><td>{{ $data['mitral_de'] ?? '' }}</td><td>mm</td><td>Systolic diameter</td><td>{{ $data['ivs_sys'] ?? '' }}</td><td>mm</td></tr>
  <tr><td>EPSS</td><td>{{ $data['mitral_epss'] ?? '' }}</td><td>mm</td><td>Excursion</td><td>{{ $data['ivs_exc'] ?? '' }}</td><td>mm</td></tr>
  <tr><td>Area by planimetry</td><td>{{ $data['mitral_area'] ?? '' }}</td><td>cm²</td><td colspan="3"></td></tr>

  <tr class="section-head"><td colspan="3">Tricuspid Valve: {{ $data['tricuspid_valve'] ?? 'normal' }}</td><td colspan="3">LV Posterior Wall</td></tr>
  <tr><td colspan="3"></td><td>Diastolic diameter</td><td>{{ $data['lvpw_dia'] ?? '' }}</td><td>mm</td></tr>

  <tr class="section-head"><td colspan="3">Aortic Valve</td><td>Systolic diameter</td><td>{{ $data['lvpw_sys'] ?? '' }}</td><td>mm</td></tr>
  <tr><td>Root Diameter</td><td>{{ $data['aortic_root'] ?? '' }}</td><td>mm</td><td>Excursion</td><td>{{ $data['lvpw_exc'] ?? '' }}</td><td>mm</td></tr>
  <tr><td>Valve Opening</td><td>{{ $data['aortic_valve'] ?? '' }}</td><td>mm</td><td colspan="3"><strong>Left Ventricle</strong></td></tr>
  <tr><td>Excursion</td><td>{{ $data['aortic_excursion'] ?? '' }}</td><td>mm</td><td>Diastolic diameter</td><td>{{ $data['lv_dia'] ?? '' }}</td><td>mm</td></tr>

  <tr class="section-head"><td colspan="3">Right Atrium</td><td>Systolic diameter</td><td>{{ $data['lv_sys'] ?? '' }}</td><td>mm</td></tr>
  <tr><td>RL Diameter</td><td>{{ $data['ra_rl'] ?? '' }}</td><td>mm</td><td>LVEDV</td><td>{{ $data['lv_lvedv'] ?? '' }}</td><td>mL</td></tr>

  <tr class="section-head"><td colspan="3">Pulmonic Valve</td><td>LVESV</td><td>{{ $data['lv_lvesv'] ?? '' }}</td><td>mL</td></tr>
  <tr><td>Root Diameter</td><td>{{ $data['pulmonic_root'] ?? '' }}</td><td>mm</td><td>LV mass index</td><td>{{ $data['lv_mass'] ?? '' }}</td><td>gm/m²</td></tr>
  <tr><td>Systolic Notching</td><td colspan="2">{{ $data['pulmonic_notching'] ?? '' }}</td><td>Relative wall thickness</td><td>{{ $data['lv_rwt'] ?? '' }}</td><td>mm</td></tr>

  <tr class="section-head"><td colspan="3">Left Atrium</td><td>Ejection Fraction</td><td>{{ $data['lv_ef'] ?? '' }}</td><td>%</td></tr>
  <tr><td>AP</td><td>{{ $data['la_ap'] ?? '' }}</td><td>mm</td><td>Fractional Shortening</td><td>{{ $data['lv_fs'] ?? '' }}</td><td>%</td></tr>
  <tr><td>RL Diameter</td><td>{{ $data['la_rl'] ?? '' }}</td><td>mm</td><td>Heart Rate</td><td>{{ $data['hr'] ?? '' }}</td><td>bpm</td></tr>
  <tr><td>LAVI</td><td>{{ $data['la_lavi'] ?? '' }}</td><td>/mL m²</td><td>Simpson's</td><td>{{ $data['simpsons'] ?? '' }}</td><td>%</td></tr>

  <tr class="section-head"><td colspan="3">Right Ventricle</td><td>Tapse</td><td>{{ $data['tapse'] ?? '' }}</td><td>cm</td></tr>
  <tr><td>AP Diameter</td><td>{{ $data['rv_ap'] ?? '' }}</td><td>mm</td><td colspan="3"><strong>Pericardial Space</strong></td></tr>
  <tr><td>RL Diameter</td><td>{{ $data['rv_rl'] ?? '' }}</td><td>mm</td><td>Anterior Effusion</td><td>{{ $data['peri_ant'] ?? '' }}</td><td>mm</td></tr>
  <tr><td></td><td></td><td></td><td>Posterior Effusion</td><td>{{ $data['peri_post'] ?? '' }}</td><td>mm</td></tr>

  <tr><td><strong>RVOT</strong></td><td>{{ $data['rvot'] ?? '' }}</td><td>mm</td><td colspan="3"></td></tr>
  <tr><td><strong>LVOT</strong></td><td>{{ $data['lvot'] ?? '' }}</td><td>mm</td><td colspan="3"></td></tr>
</table>

<h3>Echocardiographic Findings:</h3>
<p style="white-space:pre-wrap;min-height:60px;border:1px solid #ccc;padding:8px;">{{ $data['findings'] ?? '' }}</p>

<table class="no-border" style="margin-top:35px;">
  <tr>
    <td style="width:58%;border:none;"></td>
    <td style="width:42%;border-top:1px solid #000;text-align:center;border:none;border-top:1px solid #000;padding-top:5px;">
      <strong>{{ $doctor }}</strong><br><strong>Cardiologist</strong>
    </td>
  </tr>
</table>
</body>
</html>
