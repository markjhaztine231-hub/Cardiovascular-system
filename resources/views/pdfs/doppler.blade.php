<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Color Flow &amp; Doppler Study</title>
<style>
@page { size: 8.5in 11in; margin: 0.75in; }
body { font-family: 'Times New Roman', serif; font-size: 9pt; color: #000; margin: 0; }
table { width: 100%; border-collapse: collapse; margin-bottom: 5px; }
td, th { padding: 4px 5px; border: 1px solid #000; vertical-align: middle; font-size: 8pt; }
h2 { text-align: center; font-size: 12pt; margin: 6px 0 4px; text-transform: uppercase; }
h3 { font-size: 10pt; margin: 8px 0 2px; text-transform: uppercase; color: #a1282c; }
.no-border td, .no-border th { border: none !important; }
.bg-gray { background-color: #e5e5e5; }
.page-break { page-break-before: always; }
.clinic-header { text-align: center; margin-bottom: 10px; }
.clinic-name { font-size: 16pt; font-weight: bold; color: #8B0000; font-family: Arial, sans-serif; }
.clinic-sub { font-size: 8pt; font-family: Arial, sans-serif; }
/* Echo section on page 2 */
.echo-section td, .echo-section th { font-family: Arial, sans-serif; }
</style>
</head>
<body>
{{-- PAGE 1: Doppler Study --}}
<div class="clinic-header">
  <div class="clinic-name">CARDIOVASCULAR CARE CENTER</div>
  <div class="clinic-sub">Rm. 303/305 Mirasol Building, 854 Apacible St., Taft Avenue, Manila &bull; Tel: 8-353-7182</div>
</div>
<h3 style="text-align:center;">COLOR FLOW &amp; DOPPLER STUDY</h3>
<table>
  <tr>
    <td colspan="3"><strong>Patient:</strong> {{ $patient }}</td>
    <td colspan="2"><strong>File #:</strong> {{ $echoData['file_no'] ?? '' }}</td>
    <td colspan="2"><strong>Date:</strong> {{ $date }}</td>
  </tr>
  <tr class="bg-gray"><td colspan="3"><strong>Mitral Valve</strong></td><td style="text-align:center"><strong>Normal Range<br>(M | F)</strong></td><td colspan="3"><strong>Aortic Valve</strong></td></tr>
  <tr>
    <td><strong>Peak Velocity (cm/sec)</strong></td>
    <td><strong>E</strong> {{ $data['mv_peak_vel_e'] ?? '' }}</td>
    <td><strong>A</strong> {{ $data['mv_peak_vel_a'] ?? '' }}</td>
    <td></td><td></td>
    <td style="text-align:center"><strong>BEFORE</strong></td>
    <td style="text-align:center"><strong>AFTER</strong></td>
  </tr>
  <tr>
    <td><strong>Peak Gradient (mmHg)</strong></td>
    <td><strong>E</strong> {{ $data['mv_peak_grad_e'] ?? '' }}</td>
    <td><strong>A</strong> {{ $data['mv_peak_grad_a'] ?? '' }}</td>
    <td></td>
    <td><strong>Peak velocity</strong></td>
    <td>{{ $data['av_peak_vel_before'] ?? '' }} <strong>cm/s</strong></td>
    <td>{{ $data['av_peak_vel_after'] ?? '' }} <strong>cm/s</strong></td>
  </tr>
  <tr>
    <td colspan="3"><strong>IVRT</strong> {{ $data['mv_ivrt'] ?? '' }}</td>
    <td><strong>60-90 sec</strong></td>
    <td><strong>Pressure Gradient</strong></td>
    <td>{{ $data['av_press_grad_before'] ?? '' }} <strong>mmHg</strong></td>
    <td>{{ $data['av_press_grad_after'] ?? '' }} <strong>mmHg</strong></td>
  </tr>
  <tr>
    <td colspan="3"><strong>Regurgitant Fraction</strong> {{ $data['mv_regurg_frac'] ?? '' }}</td>
    <td><strong>&lt;30%</strong></td>
    <td><strong>Area by Continuity</strong></td>
    <td colspan="2">{{ $data['av_area'] ?? '' }} <strong>&gt;1.5 cm²</strong></td>
  </tr>
  <tr>
    <td colspan="3"><strong>MV DecTime</strong> {{ $data['mv_dectime'] ?? '' }}</td>
    <td><strong>162-230 msec</strong></td>
    <td><strong>Regurgitant Fraction</strong></td>
    <td colspan="2">{{ $data['av_regurg_frac'] ?? '' }} <strong>&lt;30%</strong></td>
  </tr>
  <tr>
    <td colspan="3"><strong>E/A ratio</strong> {{ $data['mv_ea_ratio'] ?? '' }}</td>
    <td></td>
    <td><strong>AR PHT</strong></td>
    <td colspan="2">{{ $data['av_ar_pht'] ?? '' }} <strong>&gt;500 msec</strong></td>
  </tr>
  <tr>
    <td colspan="3"><strong>Area by PHT</strong> {{ $data['mv_area_pht'] ?? '' }}</td>
    <td><strong>&gt;2.0 cm²</strong></td>
    <td><strong>AV Mean Gradient</strong></td>
    <td colspan="2">{{ $data['av_mean_grad'] ?? '' }} <strong>&lt;20 mmHg</strong></td>
  </tr>
  <tr>
    <td colspan="3"><strong>Mean pressure gradient</strong> {{ $data['mv_mean_pressure'] ?? '' }}</td>
    <td><strong>&lt;5 mmHg</strong></td>
    <td><strong>AR jet density</strong></td>
    <td>@if(($data['av_ar_jet'] ?? '') === 'Faint') &#9746; @else &#9744; @endif Faint</td>
    <td>@if(($data['av_ar_jet'] ?? '') === 'Dense') &#9746; @else &#9744; @endif Dense</td>
  </tr>
  <tr class="bg-gray"><td colspan="4"><strong>Tissue Doppler (cm/s)</strong></td><td colspan="3"><strong>Pulmonic Valve</strong></td></tr>
  <tr>
    <td><strong>Septal</strong></td>
    <td><strong>e'</strong> {{ $data['td_septal_e'] ?? '' }}</td>
    <td><strong>a'</strong> {{ $data['td_septal_a'] ?? '' }}</td>
    <td><strong>E/e'</strong> {{ $data['td_septal_ee'] ?? '' }}</td>
    <td colspan="2"><strong>Acceleration time</strong></td>
    <td>{{ $data['pv_accel_time'] ?? '' }} <strong>msec</strong></td>
  </tr>
  <tr>
    <td><strong>Lateral</strong></td>
    <td><strong>e'</strong> {{ $data['td_lateral_e'] ?? '' }}</td>
    <td><strong>a'</strong> {{ $data['td_lateral_a'] ?? '' }}</td>
    <td><strong>E/e'</strong> {{ $data['td_lateral_ee'] ?? '' }}</td>
    <td colspan="2"><strong>Pressure Gradient</strong></td>
    <td>{{ $data['pv_press_grad'] ?? '' }} <strong>mmHg</strong></td>
  </tr>
  <tr class="bg-gray"><td colspan="4"><strong>Tricuspid Valve</strong></td><td colspan="2"><strong>Regurgitation</strong></td><td>{{ $data['pv_regurg'] ?? '' }} %</td></tr>
  <tr>
    <td colspan="3"><strong>E wave velocity</strong> {{ $data['tv_e_wave'] ?? '' }}</td>
    <td><strong>cm/s 0.64</strong></td>
    <td colspan="2"><strong>Systolic PAP by acceleration time</strong></td>
    <td>{{ $data['pv_sys_pap'] ?? '' }} <strong>&gt;25 mmHg</strong></td>
  </tr>
  <tr>
    <td colspan="3"><strong>A wave velocity</strong> {{ $data['tv_a_wave'] ?? '' }}</td>
    <td><strong>cm/s 1.65</strong></td>
    <td><strong>PR jet density</strong></td>
    <td>@if(($data['pv_pr_jet'] ?? '') === 'Faint') &#9746; @else &#9744; @endif Faint</td>
    <td>@if(($data['pv_pr_jet'] ?? '') === 'Dense') &#9746; @else &#9744; @endif Dense</td>
  </tr>
  <tr>
    <td colspan="3"><strong>Regurgitant Fraction</strong> {{ $data['tv_regurg_frac'] ?? '' }}</td>
    <td>%</td>
    <td colspan="2"><strong>RVOT VTI</strong></td>
    <td>{{ $data['pv_rvot_vti'] ?? '' }} <strong>cm</strong></td>
  </tr>
  <tr>
    <td colspan="3"><strong>Peak TR jet velocity</strong> {{ $data['tv_peak_tr'] ?? '' }}</td>
    <td>cm/s</td>
    <td colspan="3"></td>
  </tr>
  <tr>
    <td colspan="3"><strong>Systolic PAP by TR jet</strong> {{ $data['tv_sys_pap'] ?? '' }}</td>
    <td><strong>&gt;25 mmHg</strong></td>
    <td colspan="3"><strong>Qp/Qs =</strong> {{ $data['qp_qs'] ?? '' }}</td>
  </tr>
</table>
<p style="margin-top:15px;"><strong>CONCLUSION:</strong> {{ $data['conclusion'] ?? '' }}</p>
<table class="no-border" style="margin-top:40px;">
  <tr>
    <td style="width:60%;border:none;"></td>
    <td style="width:40%;border-top:1px solid #000;text-align:center;border-left:none;border-right:none;border-bottom:none;"><strong>{{ $doctor }}</strong><br><strong>Cardiologist</strong></td>
  </tr>
</table>

{{-- PAGE 2: Echo Report --}}
<div class="page-break"></div>
<div class="clinic-header">
  <div class="clinic-name">&#9825; CARDIOVASCULAR CARE CENTER</div>
</div>
<h2 class="echo-section">Echocardiographic Report</h2>
<table class="echo-section">
  <tr>
    <td colspan="3"><strong>Patient Name:</strong> {{ $patient }}</td>
    <td colspan="2"><strong>Contact No:</strong> {{ $echoData['contact_no'] ?? '' }}</td>
  </tr>
  <tr><td colspan="5"><strong>Requesting Physician:</strong> {{ $echoData['requesting_physician'] ?? '' }}</td></tr>
  <tr>
    <td><strong>File #:</strong> {{ $echoData['file_no'] ?? '' }}</td>
    <td><strong>BSA:</strong> {{ $echoData['bsa'] ?? '' }}</td>
    <td colspan="3"><strong>Date:</strong> {{ $date }}</td>
  </tr>
  <tr>
    <td><strong>Age:</strong> {{ $echoData['age'] ?? '' }}</td>
    <td><strong>Sex:</strong> {{ $echoData['sex'] ?? '' }}</td>
    <td><strong>Height:</strong> {{ $echoData['height'] ?? '' }} cm</td>
    <td colspan="2"><strong>Weight:</strong> {{ $echoData['weight'] ?? '' }} kgs.</td>
  </tr>
</table>
<table class="echo-section">
  <colgroup><col style="width:24%"><col style="width:9%"><col style="width:7%"><col style="width:24%"><col style="width:9%"><col style="width:7%"></colgroup>
  <tr style="background:#e5e5e5;"><td colspan="3"><strong>Mitral Valve</strong></td><td colspan="3"><strong>Interventricular Septum</strong></td></tr>
  <tr><td>ED Velocity</td><td>{{ $echoData['mitral_ef'] ?? '' }}</td><td>mm²</td><td>Diastolic diameter</td><td>{{ $echoData['ivs_dia'] ?? '' }}</td><td>mm</td></tr>
  <tr><td>DE Excursion</td><td>{{ $echoData['mitral_de'] ?? '' }}</td><td>mm</td><td>Systolic diameter</td><td>{{ $echoData['ivs_sys'] ?? '' }}</td><td>mm</td></tr>
  <tr><td>EPSS</td><td>{{ $echoData['mitral_epss'] ?? '' }}</td><td>mm</td><td>Excursion</td><td>{{ $echoData['ivs_exc'] ?? '' }}</td><td>mm</td></tr>
  <tr><td>Area by planimetry</td><td>{{ $echoData['mitral_area'] ?? '' }}</td><td>cm²</td><td colspan="3"></td></tr>
  <tr style="background:#e5e5e5;"><td colspan="3"><strong>LV Posterior Wall</strong></td><td colspan="3"><strong>Left Ventricle</strong></td></tr>
  <tr><td>Diastolic</td><td>{{ $echoData['lvpw_dia'] ?? '' }}</td><td>mm</td><td>Diastolic</td><td>{{ $echoData['lv_dia'] ?? '' }}</td><td>mm</td></tr>
  <tr><td>Systolic</td><td>{{ $echoData['lvpw_sys'] ?? '' }}</td><td>mm</td><td>Systolic</td><td>{{ $echoData['lv_sys'] ?? '' }}</td><td>mm</td></tr>
  <tr><td>Excursion</td><td>{{ $echoData['lvpw_exc'] ?? '' }}</td><td>mm</td><td>EF</td><td>{{ $echoData['lv_ef'] ?? '' }}</td><td>%</td></tr>
  <tr><td colspan="3"></td><td>FS</td><td>{{ $echoData['lv_fs'] ?? '' }}</td><td>%</td></tr>
</table>
<p style="margin-top:8px;"><strong>ECHOCARDIOGRAPHIC FINDINGS:</strong></p>
<p style="white-space:pre-wrap;min-height:50px;border:1px solid #ccc;padding:6px;">{{ $echoData['findings'] ?? '' }}</p>
<table class="no-border" style="margin-top:30px;">
  <tr>
    <td style="width:60%;border:none;"></td>
    <td style="width:40%;border-top:1px solid #000;text-align:center;border-left:none;border-right:none;border-bottom:none;"><strong>{{ $doctor }}</strong><br><strong>Cardiologist</strong></td>
  </tr>
</table>
</body>
</html>
