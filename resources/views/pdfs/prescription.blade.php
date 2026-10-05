<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Prescription</title>
<style>
@page { size: A5; margin: 0.4in; }
body { font-family: Arial, sans-serif; font-size: 9pt; color: #000; margin: 0; }
table { width: 100%; border-collapse: collapse; }
td, th { padding: 3px 4px; vertical-align: top; }
h2 { text-align: center; font-size: 11pt; margin: 6px 0; text-transform: uppercase; letter-spacing: 1px; }
.clinic-header { text-align: center; margin-bottom: 8px; border-bottom: 2px solid #8B0000; padding-bottom: 6px; }
.clinic-name { font-size: 14pt; font-weight: bold; color: #8B0000; }
.clinic-sub { font-size: 7pt; color: #555; }
.info-table td { border: none; padding: 2px 4px; font-size: 8.5pt; }
.info-table td:first-child { width: 30%; }
.info-table td:last-child { width: 70%; }
.section-title { font-size: 10pt; font-weight: bold; margin: 8px 0 4px; text-transform: uppercase; border-bottom: 1px solid #333; padding-bottom: 2px; }
.prescription-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
.prescription-table th { background: #8B0000; color: #fff; font-size: 8pt; padding: 5px 4px; text-align: left; font-weight: bold; }
.prescription-table td { border-bottom: 1px solid #ccc; padding: 5px 4px; font-size: 8.5pt; }
.prescription-table tr:last-child td { border-bottom: none; }
.signature-area { margin-top: 30px; }
.signature-area td { border: none; }
.signature-line { border-top: 1px solid #000; padding-top: 4px; font-size: 8pt; text-align: center; }
.footer { position: fixed; bottom: 0.4in; left: 0.4in; right: 0.4in; font-size: 6.5pt; color: #888; text-align: center; border-top: 1px solid #ddd; padding-top: 3px; }
</style>
</head>
<body>
<div class="clinic-header">
  <div class="clinic-name">CARDIOVASCULAR CARE CENTER</div>
  <div class="clinic-sub">Rm. 303/305 Mirasol Building, 854 Apacible St., Taft Avenue, Manila &bull; Tel: 8-353-7182</div>
</div>

<h2>Prescription</h2>

<table class="info-table">
  <tr>
    <td><strong>Patient Name:</strong></td>
    <td>{{ $patient }}</td>
  </tr>
  <tr>
    <td><strong>Date:</strong></td>
    <td>{{ $date }}</td>
  </tr>
  <tr>
    <td><strong>Physician:</strong></td>
    <td>{{ $doctor }}</td>
  </tr>
</table>

<div class="section-title">Medications</div>

@php
    $meds = is_string($prescriptions) ? json_decode($prescriptions, true) ?? [] : ($prescriptions ?? []);
@endphp
@if (!empty($meds) && is_array($meds))
<table class="prescription-table">
  <thead>
    <tr>
      <th style="width:5%;">#</th>
      <th style="width:34%;">Medicine</th>
      <th style="width:18%;">Dosage</th>
      <th style="width:43%;">Frequency / Duration</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($meds as $index => $med)
    @php
        $dosage  = $med['dosage'] ?? '';
        $freq    = $med['frequency'] ?? '';
        $dur     = $med['duration'] ?? '';
    @endphp
    <tr>
      <td>{{ $index + 1 }}</td>
      <td><strong>{{ $med['medicine'] ?? '' }}</strong></td>
      <td>{{ $dosage }}</td>
      <td>{{ $freq }}{{ $dur ? ' — ' . $dur : '' }}</td>
    </tr>
    @endforeach
  </tbody>
</table>
@else
<p style="color:#888; font-style:italic; margin-top:6px;">No medications prescribed.</p>
@endif

<div class="signature-area">
  <table>
    <tr>
      <td style="width:50%;"></td>
      <td style="width:50%;" class="signature-line">
        <strong>{{ $doctor }}</strong><br>Cardiologist
      </td>
    </tr>
  </table>
</div>

<div class="footer">
  This prescription is valid only for the patient named above. &bull; {{ $date }}
</div>
</body>
</html>
