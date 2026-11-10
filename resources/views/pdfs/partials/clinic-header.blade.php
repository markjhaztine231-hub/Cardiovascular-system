@php
    // Embed the logo as a data URI so Dompdf never depends on a reachable URL
    // or filesystem path (remote loading is disabled by default).
    //
    // Prefer the JPEG version: Dompdf decodes PNGs through the GD extension,
    // which is not installed everywhere, while its JPEG path is pure PHP. The
    // JPEG is the same mark composited on white and cropped to its content.
    $logoFile = public_path('logo.jpg');
    $logoMime = 'image/jpeg';

    if (! is_file($logoFile)) {
        $logoFile = public_path('logo.png');
        $logoMime = 'image/png';
    }

    $logoSrc = is_file($logoFile)
        ? 'data:'.$logoMime.';base64,'.base64_encode(file_get_contents($logoFile))
        : null;

    $logoHeight = $logoHeight ?? '0.55in';
    $name = $name 'CARDIOVASCULAR CARE CENTER';
    $nameColor = $nameColor ?? '#8B0000';
    $nameSize = $nameSize ?? '16pt';
    $sub = $sub ?? '<span style="color:#0000FF;">Rm. 303/305 Mirasol Building, 854 Apacible St., Taft Avenue, Manila &bull; Tel: 8-353-7182</span>';
    $subColor = $subColor ?? '#000';
    $subSize = $subSize ?? '8pt';
@endphp
<table style="width:auto; margin:0 auto; border-collapse:collapse;">
    <tr>
        @if ($logoSrc)
            <td style="border:none; padding:0 8px 0 0; vertical-align:middle;">
                <img src="{{ $logoSrc }}" alt="Cardiovascular Care Center logo" style="height:{{ $logoHeight }}; width:auto;">
            </td>
        @endif
        <td style="border:none; padding:0; vertical-align:middle; text-align:left;">
            <div style="font-size:{{ $nameSize }}; font-weight:bold; color:{{ $nameColor }};">{!! $name !!}</div>
            @if ($sub !== '')
                <div style="font-size:{{ $subSize }}; color:{{ $subColor }};">{!! $sub !!}</div>
            @endif
        </td>
    </tr>
</table>
