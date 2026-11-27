{{-- PDF layout for App\Services\Documents\Document (rendered by dompdf: plain CSS, no external files). --}}
@php
    /** @var \App\Services\Documents\Document $document */
    $navy = \App\Support\Brand::NAVY;
    $gold = \App\Support\Brand::YELLOW;
@endphp
<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ $document->title }}</title>
    <style>
        @page { margin: 22mm 18mm 20mm; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9.5pt; line-height: 1.5; color: #1f2937; }
        .brand { font-size: 7.5pt; font-weight: bold; letter-spacing: 0.14em; text-transform: uppercase; color: {{ $navy }}; }
        .brand span { display: inline-block; width: 8pt; height: 8pt; margin-right: 4pt; border: 2pt solid {{ $gold }}; vertical-align: -1pt; }
        h1 { margin: 10pt 0 2pt; font-size: 18pt; line-height: 1.2; color: {{ $navy }}; }
        .subtitle { margin: 0; font-size: 10.5pt; color: #4b5563; }
        .rule { height: 3pt; margin: 10pt 0 14pt; background: {{ $gold }}; }
        .notice { margin: 0 0 12pt; padding: 7pt 9pt; border: 1pt solid {{ $gold }}; background: #fffaeb; font-weight: bold; font-size: 8.5pt; color: {{ $navy }}; }
        table.meta { margin-bottom: 6pt; border-collapse: collapse; }
        table.meta td { padding: 1.5pt 12pt 1.5pt 0; vertical-align: top; }
        table.meta td.label { width: 42mm; font-weight: bold; color: #4b5563; }
        h2 { margin: 16pt 0 6pt; padding-bottom: 3pt; border-bottom: 1pt solid #e5e7eb; font-size: 12pt; color: {{ $navy }}; page-break-after: avoid; }
        h3, h4 { margin: 10pt 0 4pt; font-size: 10.5pt; color: {{ $navy }}; }
        p { margin: 0 0 6pt; }
        .muted { color: #6b7280; font-size: 8.5pt; }
        blockquote { margin: 6pt 0; padding-left: 8pt; border-left: 2pt solid {{ $gold }}; color: #374151; }
        ul, ol { margin: 0 0 6pt 14pt; padding: 0; }
        table.data { width: 100%; border-collapse: collapse; margin: 2pt 0 8pt; page-break-inside: auto; }
        table.data th { background: {{ $navy }}; color: #fff; text-align: left; font-size: 8pt; padding: 4pt 6pt; }
        table.data td { padding: 3.5pt 6pt; border-bottom: 0.6pt solid #e5e7eb; font-size: 8.5pt; }
        table.data tr:nth-child(even) td { background: #f9fafb; }
        table.data th.num, table.data td.num { text-align: right; }
        table.figures { width: 100%; border-collapse: separate; border-spacing: 5pt 0; margin: 4pt -5pt 8pt; }
        table.figures td { background: #f3f4f6; padding: 8pt 9pt; vertical-align: top; }
        table.figures .value { display: block; font-size: 17pt; font-weight: bold; color: {{ $navy }}; }
        table.figures .label { display: block; font-size: 7.5pt; color: #4b5563; }
        .comment { margin: 0 0 8pt; padding-left: 8pt; border-left: 2pt solid {{ $gold }}; page-break-inside: avoid; }
        .comment .byline { font-weight: bold; font-size: 8pt; color: {{ $navy }}; }
    </style>
</head>
<body>
    <div class="brand"><span></span>{{ config('app.name') }}</div>
    <h1>{{ $document->title }}</h1>
    @if ($document->subtitle)
        <p class="subtitle">{{ $document->subtitle }}</p>
    @endif
    <div class="rule"></div>

    @if ($document->notice)
        <div class="notice">{{ $document->notice }}</div>
    @endif

    @if ($document->meta !== [])
        <table class="meta">
            @foreach ($document->meta as $label => $value)
                <tr><td class="label">{{ $label }}</td><td>{{ $value }}</td></tr>
            @endforeach
        </table>
    @endif

    @foreach ($document->blocks as $block)
        @switch($block['type'])
            @case('heading')
                <h2>{{ $block['text'] }}</h2>
                @break
            @case('paragraph')
                <p @class(['muted' => $block['muted']])>{{ $block['text'] }}</p>
                @break
            @case('html')
                {{-- Cleaned in Document::cleanHtml(): simple tags only, no attributes. --}}
                {!! $block['html'] !!}
                @break
            @case('comment')
                <div class="comment">
                    <div class="byline">{{ $block['byline'] }}</div>
                    <div>{!! nl2br(e($block['text'])) !!}</div>
                </div>
                @break
            @case('figures')
                <table class="figures"><tr>
                    @foreach ($block['figures'] as $label => $value)
                        <td><span class="value">{{ $value }}</span><span class="label">{{ $label }}</span></td>
                    @endforeach
                </tr></table>
                @break
            @case('table')
                @if ($block['rows'] === [])
                    <p class="muted">{{ $block['empty'] ?? '—' }}</p>
                @else
                    <table class="data">
                        <thead><tr>
                            @foreach ($block['head'] as $index => $label)
                                <th @class(['num' => $index > 0])>{{ $label }}</th>
                            @endforeach
                        </tr></thead>
                        <tbody>
                            @foreach ($block['rows'] as $row)
                                <tr>
                                    @foreach ($row as $index => $value)
                                        <td @class(['num' => $index > 0])>{{ $value ?? '—' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
                @break
        @endswitch
    @endforeach
</body>
</html>
