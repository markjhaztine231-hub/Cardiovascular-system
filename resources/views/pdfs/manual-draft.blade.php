<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Manual Draft</title>
<style>
@page { size: 8.5in 11in; margin: 0.5in; }
body { font-family: Arial, sans-serif; font-size: 9pt; color: #000; margin: 0; }
table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
td, th { padding: 3px 4px; border: 1px solid #000; vertical-align: top; font-size: 8pt; }
h2 { text-align: center; font-size: 11pt; margin: 3px 0 6px; text-transform: uppercase; }
h3 { font-size: 9pt; margin: 8px 0 3px; text-transform: uppercase; }
.no-border td { border: none !important; }
.clinic-header { text-align: center; margin-bottom: 10px; }
.clinic-name { font-size: 16pt; font-weight: bold; color: #8B0000; }
.clinic-sub { font-size: 8pt; }
.draft-box { white-space: pre-wrap; border: 1px solid #999; padding: 8px; min-height: 80px; }
.scan { text-align: center; margin-bottom: 10px; page-break-inside: avoid; }
.scan img { max-width: 100%; max-height: 3.4in; }
.scan-name { font-size: 7pt; color: #555; }
</style>
</head>
<body>
<div class="clinic-header">
  <div class="clinic-name">CARDIOVASCULAR CARE CENTER</div>
  <div class="clinic-sub">Rm. 303/305 Mirasol Building, 854 Apacible St., Taft Avenue, Manila &bull; Tel: 8-353-7182</div>
</div>

<h2>Manual Draft</h2>

<table>
  <tr>
    <td><strong>Patient Name:</strong> {{ $patient }}</td>
    <td><strong>Date:</strong> {{ $date }}</td>
  </tr>
  <tr>
    <td colspan="2"><strong>Requesting Physician:</strong> {{ $draft->requesting_physician ?? '' }}</td>
  </tr>
  @if(!empty($draft->title))
  <tr>
    <td colspan="2"><strong>Title:</strong> {{ $draft->title }}</td>
  </tr>
  @endif
</table>

@if(!empty($attachments))
<h3>Attached Scans</h3>
@foreach($attachments as $image)
<div class="scan">
  <img src="{{ $image['path'] }}" alt="{{ $image['name'] }}">
  <div class="scan-name">{{ $image['name'] }}</div>
</div>
@endforeach
@endif

<h3>Draft</h3>
<div class="draft-box">{{ $draft->draft ?? '' }}</div>

@if(!empty($draft->conclusion))
<h3>Conclusion</h3>
<div class="draft-box">{{ $draft->conclusion }}</div>
@endif

<table class="no-border" style="margin-top:35px;">
  <tr>
    <td style="width:58%;border:none;"></td>
    <td style="width:42%;text-align:center;border:none;border-top:1px solid #000;padding-top:5px;">
      <strong>{{ $doctor }}</strong><br><strong>Cardiologist</strong>
    </td>
  </tr>
</table>
</body>
</html>
