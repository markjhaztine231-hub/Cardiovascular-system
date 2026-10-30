<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $reportTitle ?? 'Monthly Clinical Report' }} — {{ $formattedMonth }}</title>
<style>
    @page {
        size: 8.5in 11in;
        margin: 1in 0.9in 0.85in 0.9in;
    }

    * { box-sizing: border-box; }

    body {
        font-family: 'Times New Roman', Times, serif;
        font-size: 10.5pt;
        color: #1a1a1a;
        margin: 0;
        line-height: 1.55;
    }

    /* ── Clinic header ─────────────────────────────────────────── */
    .clinic-header {
        text-align: center;
        border-bottom: 2.5pt solid #1a4e6e;
        padding-bottom: 10px;
        margin-bottom: 18px;
    }
    .clinic-name {
        font-family: Arial, sans-serif;
        font-size: 15pt;
        font-weight: bold;
        color: #1a4e6e;
        letter-spacing: 0.5pt;
    }
    .clinic-sub {
        font-family: Arial, sans-serif;
        font-size: 8pt;
        color: #555;
        margin-top: 2px;
    }
    .report-title {
        font-family: Arial, sans-serif;
        font-size: 13.5pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1pt;
        color: #1a1a1a;
        margin-top: 8px;
    }
    .report-period {
        font-family: Arial, sans-serif;
        font-size: 9pt;
        color: #555;
        font-weight: bold;
        letter-spacing: 0.5pt;
        text-transform: uppercase;
        margin-top: 3px;
    }

    /* ── Section titles ────────────────────────────────────────── */
    .section-title {
        font-family: Arial, sans-serif;
        font-size: 9pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.8pt;
        color: #1a4e6e;
        border-bottom: 1pt solid #1a4e6e;
        padding-bottom: 3px;
        margin: 20px 0 8px;
    }

    /* ── Stats table ───────────────────────────────────────────── */
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9.5pt;
        margin-bottom: 8px;
    }
    th {
        background: #1a4e6e;
        color: white;
        font-family: Arial, sans-serif;
        font-size: 8pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.4pt;
        padding: 5px 8px;
        text-align: left;
        border: 1pt solid #1a4e6e;
    }
    th.center, td.center { text-align: center; }
    td {
        padding: 5px 8px;
        border: 0.5pt solid #ccc;
        vertical-align: middle;
    }
    tr:nth-child(even) td { background: #f4f8fb; }
    .num {
        font-family: Arial, sans-serif;
        font-weight: bold;
        font-size: 11pt;
        color: #1a4e6e;
        text-align: center;
    }
    .label { color: #333; }

    /* ── Metric cards (2-column layout) ────────────────────────── */
    .metric-grid {
        display: table;
        width: 100%;
        border-collapse: separate;
        border-spacing: 5pt 0;
        margin-bottom: 8px;
    }
    .metric-card {
        display: table-cell;
        width: 20%;
        background: #f4f8fb;
        border: 0.5pt solid #bdd4e4;
        border-left: 3pt solid #1a4e6e;
        padding: 7px 8px;
        vertical-align: top;
    }
    .metric-card.green  { border-left-color: #2e7d32; }
    .metric-card.amber  { border-left-color: #e65100; }
    .metric-card.red    { border-left-color: #c62828; }
    .metric-card.gray   { border-left-color: #616161; }
    .metric-label {
        font-family: Arial, sans-serif;
        font-size: 7pt;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.4pt;
        color: #555;
    }
    .metric-value {
        font-family: Arial, sans-serif;
        font-size: 18pt;
        font-weight: bold;
        color: #1a1a1a;
        line-height: 1.1;
        margin-top: 2px;
    }
    .metric-sub {
        font-size: 8pt;
        color: #777;
        margin-top: 2px;
    }

    /* ── Rate bar ───────────────────────────────────────────────── */
    .bar-wrap {
        background: #e0e0e0;
        border-radius: 4pt;
        height: 8pt;
        width: 100%;
        margin: 3px 0;
        overflow: hidden;
    }
    .bar-fill {
        height: 8pt;
        border-radius: 4pt;
    }

    /* ── Narrative section ──────────────────────────────────────── */
    .narrative-header {
        border: 0.5pt solid #bdd4e4;
        background: #eef5fb;
        padding: 10px 14px;
        margin-bottom: 14px;
        border-radius: 3pt;
    }
    .narrative-header p { margin: 0; font-size: 8.5pt; color: #444; }
    .narrative-header strong { color: #1a4e6e; }

    .narrative-body p {
        text-align: justify;
        text-indent: 2em;
        margin: 0 0 10px 0;
        font-size: 10.5pt;
        line-height: 1.65;
    }

    /* ── Signature ──────────────────────────────────────────────── */
    .sig-block {
        margin-top: 32px;
        display: table;
        width: 100%;
    }
    .sig-left, .sig-right {
        display: table-cell;
        vertical-align: bottom;
    }
    .sig-right {
        text-align: right;
        width: 45%;
    }
    .sig-line {
        border-top: 1pt solid #333;
        padding-top: 5px;
        margin-top: 40px;
    }
    .sig-name {
        font-family: 'Times New Roman', serif;
        font-style: italic;
        font-size: 12pt;
        font-weight: bold;
        color: #1a1a1a;
    }
    .sig-title {
        font-family: Arial, sans-serif;
        font-size: 8.5pt;
        font-weight: bold;
        color: #1a4e6e;
        letter-spacing: 0.3pt;
        text-transform: uppercase;
    }

    /* ── Footer ─────────────────────────────────────────────────── */
    .page-footer {
        position: fixed;
        bottom: 0.4in;
        left: 0.9in;
        right: 0.9in;
        border-top: 0.5pt solid #ccc;
        padding-top: 4px;
        font-family: Arial, sans-serif;
        font-size: 7.5pt;
        color: #aaa;
        display: table;
        width: 100%;
    }
    .footer-left  { display: table-cell; text-align: left; }
    .footer-right { display: table-cell; text-align: right; }

    .page-break { page-break-before: always; }
    .no-break   { page-break-inside: avoid; }
</style>
</head>
<body>

<!-- Fixed footer on every page -->
<div class="page-footer">
    <span class="footer-left">Cardiovascular Care Center &mdash; Confidential Clinical Document</span>
    <span class="footer-right">Generated: {{ $generatedAt }}</span>
</div>

<!-- ── CLINIC HEADER ──────────────────────────────────────── -->
<div class="clinic-header">
    <div class="clinic-name">&nbsp; CARDIOVASCULAR CARE CENTER</div>
    <div class="clinic-sub">Rm. 303/305 Mirasol Building, 854 Apacible St., Taft Avenue, Manila &bull; Tel. 8-353-7182</div>
    <div class="report-title">{{ $reportTitle ?? 'Monthly Narrative Report' }}</div>
    <div class="report-period">Period: {{ $formattedMonth }} &nbsp;&bull;&nbsp; Prepared by: {{ $doctor }}</div>
</div>

<!-- ── SECTION 1: OPERATIONAL METRICS ─────────────────────── -->
<div class="section-title">1. Operational Metrics</div>
<div class="metric-grid no-break">
    <div class="metric-card">
        <div class="metric-label">Total Appointments</div>
        <div class="metric-value">{{ $stats['total'] ?? 0 }}</div>
    </div>
    <div class="metric-card green">
        <div class="metric-label">Completed</div>
        <div class="metric-value">{{ $stats['completed'] ?? 0 }}</div>
        <div class="metric-sub">appointments</div>
    </div>
    <div class="metric-card amber">
        <div class="metric-label">Pending / In Progress</div>
        <div class="metric-value">{{ $stats['pending'] ?? 0 }}</div>
    </div>
    <div class="metric-card red">
        <div class="metric-label">No-Shows</div>
        <div class="metric-value">{{ $stats['no_shows'] ?? 0 }}</div>
        <div class="metric-sub">patients</div>
    </div>
    <div class="metric-card gray">
        <div class="metric-label">Cancelled</div>
        <div class="metric-value">{{ $stats['cancelled'] ?? 0 }}</div>
    </div>
</div>

<table class="no-break" style="margin-top:6px;">
    <tr>
        <th style="width:50%">Completed</th>
        <th style="width:50%">No-Shows</th>
    </tr>
    <tr>
        <td class="num">{{ $stats['completed'] ?? 0 }} of {{ $stats['total'] ?? 0 }}</td>
        <td class="num">{{ $stats['no_shows'] ?? 0 }} of {{ $stats['total'] ?? 0 }}</td>
    </tr>
</table>

<!-- ── SECTION 2: DEMOGRAPHIC DISTRIBUTION ────────────────── -->
<div class="section-title">2. Demographic Distribution</div>
<table class="no-break">
    <tr>
        <th>Unique Patients</th>
        <th>Avg. Age</th>
        <th>Male</th>
        <th>Female</th>
        <th>Other / Unset</th>
    </tr>
    <tr>
        <td class="num">{{ $stats['demographics']['total_unique_patients'] ?? '—' }}</td>
        <td class="num">
            @php $avg = $stats['demographics']['avg_age'] ?? '—'; @endphp
            {{ $avg }}@if(is_numeric($avg)) <small>yrs</small>@endif
        </td>
        <td class="num">{{ $stats['demographics']['male_count'] ?? 0 }}</td>
        <td class="num">{{ $stats['demographics']['female_count'] ?? 0 }}</td>
        <td class="num center">{{ $stats['demographics']['other_count'] ?? 0 }}</td>
    </tr>
</table>

<!-- ── SECTION 3: CLINICAL PROCEDURES ─────────────────────── -->
<div class="section-title">3. Clinical Procedures Performed</div>
<table class="no-break">
    <tr>
        <th>Procedure</th>
        <th class="center">Count</th>
    </tr>
    @php
        $procTotal = $stats['procedures']['total'] ?? 0;
        $echo      = $stats['procedures']['echocardiogram']  ?? 0;
        $tmt       = $stats['procedures']['treadmill_tmt']   ?? 0;
        $doppler   = $stats['procedures']['color_doppler']   ?? 0;
    @endphp
    <tr>
        <td class="label">Type 1 &mdash; Echocardiogram</td>
        <td class="num">{{ $echo }}</td>
    </tr>
    <tr>
        <td class="label">Type 2 &mdash; Treadmill Exercise Test (TMT)</td>
        <td class="num">{{ $tmt }}</td>
    </tr>
    <tr>
        <td class="label">Type 3 &mdash; Color Flow &amp; Doppler Study</td>
        <td class="num">{{ $doppler }}</td>
    </tr>
    <tr style="background:#eef5fb;">
        <td class="label" style="font-weight:bold;">Total Procedures</td>
        <td class="num" style="font-size:13pt;">{{ $procTotal }}</td>
    </tr>
</table>

<!-- ── SECTION 4: CLINICAL TRENDS ─────────────────────────── -->
<div style="display:table;width:100%;border-spacing:8pt 0;border-collapse:separate;" class="no-break">
    <div style="display:table-cell;width:50%;vertical-align:top;">
        <div class="section-title" style="margin-top:16px;">4a. Top Diagnoses</div>
        <table>
            <tr>
                <th style="width:30px;">#</th>
                <th>Diagnosis</th>
                <th class="center">Cases</th>
            </tr>
            @forelse($stats['top_diagnoses'] ?? [] as $i => $dx)
            <tr>
                <td class="center" style="color:#777;font-size:9pt;">{{ $i + 1 }}</td>
                <td class="label">{{ $dx['name'] }}</td>
                <td class="num">{{ $dx['count'] }}</td>
            </tr>
            @empty
            <tr><td colspan="3" style="text-align:center;color:#aaa;font-style:italic;padding:8px;">No diagnoses recorded.</td></tr>
            @endforelse
        </table>
    </div>
    <div style="display:table-cell;width:50%;vertical-align:top;padding-left:8pt;">
        <div class="section-title" style="margin-top:16px;">4b. Top Services Rendered</div>
        <table>
            <tr>
                <th style="width:30px;">#</th>
                <th>Service</th>
                <th class="center">Times</th>
            </tr>
            @forelse($stats['top_services'] ?? [] as $i => $svc)
            <tr>
                <td class="center" style="color:#777;font-size:9pt;">{{ $i + 1 }}</td>
                <td class="label">{{ $svc['name'] }}</td>
                <td class="num">{{ $svc['count'] }}</td>
            </tr>
            @empty
            <tr><td colspan="3" style="text-align:center;color:#aaa;font-style:italic;padding:8px;">No service data recorded.</td></tr>
            @endforelse
        </table>
    </div>
</div>

<!-- ── SECTION 5: AI EXECUTIVE NARRATIVE ──────────────────── -->
@if($reportText)
<div class="page-break"></div>

<div class="clinic-header" style="margin-top:0;">
    <div class="clinic-name">&nbsp; CARDIOVASCULAR CARE CENTER</div>
    <div class="report-title">{{ $reportTitle ?? 'Monthly Narrative Report' }}</div>
    <div class="report-period">Period: {{ $formattedMonth }} &nbsp;&bull;&nbsp; Prepared by: {{ $doctor }}</div>
</div>

<div class="section-title">5. AI-Generated Narrative Report</div>

<div class="narrative-header">
    <p>
        <strong>Source &amp; Methodology:</strong> This narrative was generated by an AI clinical assistant
        using <strong>only</strong> the verified database figures shown in Sections 1&ndash;4 above.
        It contains no predictions, estimations, or data not present in the clinic's records.
        Period covered: <strong>{{ $formattedMonth }}</strong>.
    </p>
</div>

<div class="narrative-body">
    @foreach(array_filter(preg_split('/\n{2,}/', $reportText ?? '')) as $paragraph)
    @if(trim($paragraph))
    <p>{{ trim($paragraph) }}</p>
    @endif
    @endforeach
</div>

<!-- Signature block -->
<div class="sig-block">
    <div class="sig-left">
        <p style="font-size:8pt;color:#888;margin:0;">
            This document was electronically prepared by the CardioCare EMR System.<br>
            For official use only. Do not distribute without authorization.
        </p>
    </div>
    <div class="sig-right">
        <div class="sig-line">
            <div class="sig-name">{{ $doctor }}</div>
            <div class="sig-title">Attending Cardiologist</div>
            <div style="font-family:Arial,sans-serif;font-size:8pt;color:#888;margin-top:2px;">{{ $generatedAt }}</div>
        </div>
    </div>
</div>

@else
<div style="text-align:center;padding:30px;color:#aaa;font-style:italic;border:1pt dashed #ccc;margin-top:16px;">
    No AI narrative was generated for this report period.
</div>
@endif

</body>
</html>
