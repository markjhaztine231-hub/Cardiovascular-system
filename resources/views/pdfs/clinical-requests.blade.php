<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Clinical Requests</title>
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
.request-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
.request-table th { background: #8B0000; color: #fff; font-size: 8pt; padding: 5px 4px; text-align: left; font-weight: bold; }
.request-table td { border-bottom: 1px solid #ccc; padding: 5px 4px; font-size: 8.5pt; }
.request-table tr:last-child td { border-bottom: none; }
.request-table tr { page-break-inside: avoid; }
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

<h2>Clinical Requests</h2>

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

<div class="section-title">Requests</div>

@if (!empty($requests) && is_array($requests))
<table class="request-table">
  <thead>
    <tr>
      <th style="width:5%;">#</th>
      <th style="width:18%;">Type</th>
      <th style="width:14%;">Priority</th>
      <th style="width:63%;">Request</th>
    </tr>
  </thead>
  <tbody>
    @foreach ($requests as $index => $request)
    <tr>
      <td>{{ $index + 1 }}</td>
      <td>{{ $request['type_label'] ?? ucfirst($request['type'] ?? 'Other') }}</td>
      <td>{{ ucfirst($request['priority'] ?? 'routine') }}</td>
      <td>{!! nl2br(e($request['body'] ?? '')) !!}</td>
    </tr>
    @endforeach
  </tbody>
</table>
@else
<p style="color:#888; font-style:italic; margin-top:6px;">No clinical requests recorded.</p>
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
  This request is valid only for the patient named above. &bull; {{ $date }}
</div>
</body>
</html>
