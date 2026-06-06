<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $report->reportTemplate?->annex_number ?? '-' }} - {{ $report->reportTemplate?->name ?? 'Laporan Arsip' }}</title>
    <style>
        @page portrait {
            size: A4 portrait;
            margin: 1.85cm 1.27cm 1.27cm 1.27cm;
        }

        @page landscape {
            size: A4 landscape;
            margin: 1.85cm 1.27cm 1.27cm 1.27cm;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            margin: 0;
            overflow-x: hidden;
            background: #d1d5db;
            color: #000;
            font-family: Verdana, Geneva, sans-serif;
            font-size: 10pt;
        }

        .toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 8px;
            width: 100%;
            padding: 10px 16px;
            border-bottom: 1px solid #ddd;
            background: #fff;
        }

        .toolbar-title {
            display: flex;
            min-width: 0;
            align-items: center;
            gap: 6px;
            overflow: hidden;
        }

        .toolbar-title a {
            display: flex;
            flex-shrink: 0;
            align-items: center;
            gap: 4px;
            color: #555;
            font-size: 12px;
            text-decoration: none;
            white-space: nowrap;
        }

        .toolbar-title .sep {
            flex-shrink: 0;
            color: #ccc;
        }

        .toolbar-title .title-text {
            font-size: 12px;
            font-weight: 700;
            line-height: 1.35;
            overflow-wrap: anywhere;
            white-space: normal;
            word-break: break-word;
        }

        .toolbar-zoom {
            display: flex;
            align-items: center;
            justify-self: center;
            gap: 4px;
        }

        .toolbar-zoom button {
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid #d1d5db;
            border-radius: 4px;
            background: #f3f4f6;
            cursor: pointer;
        }

        .toolbar-zoom .zoom-btn {
            width: 36px;
            height: 36px;
            font-size: 20px;
        }

        .toolbar-zoom .zoom-reset {
            height: 36px;
            padding: 0 10px;
            color: #555;
            font-size: 12px;
        }

        .toolbar-zoom .zoom-label {
            min-width: 46px;
            color: #374151;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
        }

        .toolbar-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
        }

        .toolbar-print {
            border: 0;
            border-radius: 6px;
            background: #222;
            color: #fff;
            cursor: pointer;
            flex-shrink: 0;
            font-family: Verdana, Geneva, sans-serif;
            font-size: 13px;
            font-weight: 600;
            padding: 9px 18px;
            text-decoration: none;
            white-space: nowrap;
        }

        #zoom-outer {
            width: 100%;
            overflow-x: auto;
            padding-bottom: 32px;
        }

        #zoom-wrap {
            width: fit-content;
            transform-origin: top left;
            transition: transform .12s ease;
        }

        .doc-page {
            position: relative;
            background: #fff;
            box-shadow: 0 2px 14px rgba(0, 0, 0, .2);
            margin: 1rem auto 2rem;
            padding: 18.5mm 12.7mm 18mm;
        }

        .doc-page.portrait {
            page: portrait;
            width: 21cm;
            min-height: 29.7cm;
        }

        .doc-page.landscape {
            page: landscape;
            width: 29.7cm;
            min-height: 21cm;
        }

        .doc-page + .doc-page {
            page-break-before: always;
        }

        .pg-hdr .doc-num {
            color: #6b7280;
            font-size: 8pt;
            line-height: 1.4;
            text-align: right;
        }

        .pg-hdr .doc-title {
            font-size: 9pt;
            font-weight: 700;
            margin-bottom: 0;
            margin-top: 3px;
            padding-bottom: 6px;
            text-align: center;
        }

        .pg-hdr .doc-title-line {
            border: 0;
            border-top: 2.5px solid #000;
            margin: 0 0 8px;
        }

        .pg-footer {
            position: absolute;
            right: 12.7mm;
            bottom: 8mm;
            color: #6b7280;
            font-size: 8pt;
            line-height: 1.4;
            text-align: right;
        }

        table.dt {
            border-collapse: collapse;
            table-layout: fixed;
            width: 100%;
        }

        table.dt-auto {
            table-layout: auto;
        }

        table.dt th,
        table.dt td {
            border: 1px solid #000;
            overflow: visible;
            padding: 2px;
            vertical-align: middle;
            white-space: normal;
            word-break: break-word;
            word-wrap: break-word;
        }

        table.dt th {
            font-size: 7pt;
            font-weight: 700;
            text-align: center;
        }

        table.dt td {
            font-size: 8pt;
        }

        table.dt-auto th,
        table.dt-auto td {
            padding: 15px 8px;
        }

        table.dt-compact th,
        table.dt-compact td {
            padding: 8px;
        }

        .sec-hdr {
            border: 0 !important;
            border-bottom: 1px solid #000 !important;
            font-size: 10pt;
            font-weight: 700;
            padding: 8px 0 4px !important;
        }

        .tc {
            text-align: center;
        }

        .tl {
            text-align: left;
        }

        .fw {
            font-weight: 700;
        }

        .small {
            font-size: 7.5pt;
        }

        .sig-tbl {
            margin-top: 10px;
            table-layout: fixed;
        }

        .sig-tbl td {
            font-size: 8pt;
            padding: 3px 6px;
            text-align: center;
            vertical-align: top;
        }

        .sig-tbl .sig-body {
            height: 76px;
        }

        @media (max-width: 640px) {
            .toolbar {
                grid-template-columns: 1fr auto;
                grid-template-rows: auto auto;
                padding: 10px 12px;
            }

            .toolbar-title {
                grid-column: 1 / -1;
                grid-row: 1;
            }

            .toolbar-zoom {
                grid-column: 1;
                grid-row: 2;
                justify-self: start;
            }

            .toolbar-actions {
                grid-column: 2;
                grid-row: 2;
                justify-self: end;
            }
        }

        @media print {
            * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }

            body {
                background: #fff !important;
            }

            .no-print {
                display: none !important;
            }

            #zoom-outer {
                overflow: visible !important;
                padding: 0 !important;
            }

            #zoom-wrap {
                margin: 0 !important;
                transform: none !important;
                width: 100% !important;
            }

            .doc-page {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 0 !important;
                position: relative !important;
                width: 100% !important;
            }

            .doc-page.portrait {
                min-height: calc(297mm - 31.2mm) !important;
            }

            .doc-page.landscape {
                min-height: calc(210mm - 31.2mm) !important;
            }

            .pg-footer {
                bottom: 0 !important;
                right: 0 !important;
            }

            thead {
                display: table-header-group;
            }
        }
    </style>
</head>
<body>
    @php
        $backUrl = route('arsip-laporan.index', $activeFolder ? ['folder' => $activeFolder['slug']] : []);
        $totalPages = 2 + count($printSections);
        $pageNumber = 1;
    @endphp

    <div class="no-print toolbar">
        <div class="toolbar-title">
            <a href="{{ $backUrl }}">
                <span>&lsaquo;</span>
                <span>Kembali</span>
            </a>
            <span class="sep">|</span>
            <span class="title-text">{{ $report->reportTemplate?->annex_number ?? '-' }} &mdash; {{ $report->reportTemplate?->name ?? 'Laporan Arsip' }}</span>
        </div>

        <div class="toolbar-zoom">
            <button class="zoom-btn" type="button" onclick="zoomOut()" title="Zoom Out">-</button>
            <span id="zoom-level" class="zoom-label">100%</span>
            <button class="zoom-btn" type="button" onclick="zoomIn()" title="Zoom In">+</button>
            <button class="zoom-reset" type="button" onclick="zoomReset()" title="Reset">R</button>
        </div>

        <div class="toolbar-actions">
            <button class="toolbar-print" type="button" onclick="window.print()">Cetak / Download PDF</button>
        </div>
    </div>

    <div id="zoom-outer">
        <div id="zoom-wrap">
            <section class="doc-page portrait">
                <x-report-print.header :report="$report" />

                <x-report-print.identity
                    :room-monitoring="$roomMonitoring"
                    :instrument-entries="$instrumentEntries"
                    :medium-entries="$mediumEntries"
                />
                <x-report-print.footer :page="$pageNumber" :total="$totalPages" />
            </section>

            <section class="doc-page portrait">
                @php
                    $pageNumber++;
                @endphp

                <x-report-print.header :report="$report" />

                <x-report-print.incubation
                    :incubators="$incubators"
                    :has-swab="$hasSwab"
                />
                <x-report-print.footer :page="$pageNumber" :total="$totalPages" />
            </section>

            @foreach ($printSections as $printSection)
                @php
                    $pageNumber++;
                @endphp

                <section class="doc-page {{ $printSection['orientation'] }}">
                    <x-report-print.header :report="$report" />

                    <x-report-print.section :print-section="$printSection" />
                    <x-report-print.footer :page="$pageNumber" :total="$totalPages" />
                </section>
            @endforeach
        </div>
    </div>

    <script type="application/json" id="print-config">
        {"minLeft":16,"outerPadding":32}
    </script>
    <script>
        const PRINT_CONFIG = JSON.parse(document.getElementById('print-config').textContent);
        let pageZoom = 1.0;
        let userManualZoom = false;

        function applyZoom() {
            pageZoom = Math.round(Math.max(0.15, Math.min(2.0, pageZoom)) * 100) / 100;
            const wrap = document.getElementById('zoom-wrap');
            const label = document.getElementById('zoom-level');

            wrap.style.transform = 'none';
            wrap.style.height = '';
            wrap.style.marginLeft = '';

            const naturalHeight = wrap.scrollHeight;
            const naturalWidth = wrap.scrollWidth;
            const viewportWidth = window.innerWidth || document.documentElement.clientWidth;
            const scaledWidth = naturalWidth * pageZoom;

            wrap.style.transform = `scale(${pageZoom})`;
            wrap.style.height = `${naturalHeight * pageZoom}px`;
            wrap.style.marginLeft = `${Math.max(PRINT_CONFIG.minLeft, (viewportWidth - scaledWidth) / 2)}px`;

            if (label) {
                label.textContent = `${Math.round(pageZoom * 100)}%`;
            }
        }

        function zoomIn() {
            userManualZoom = true;
            pageZoom = Math.min(2.0, pageZoom + 0.1);
            applyZoom();
        }

        function zoomOut() {
            userManualZoom = true;
            pageZoom = Math.max(0.15, pageZoom - 0.1);
            applyZoom();
        }

        function zoomReset() {
            userManualZoom = false;
            autoFit();
        }

        function autoFit() {
            const hasLandscape = document.querySelector('.doc-page.landscape') !== null;
            const maxPageMm = hasLandscape ? 297 : 210;
            const maxPagePx = maxPageMm * 3.7795;
            const totalWidth = maxPagePx + PRINT_CONFIG.outerPadding;
            const viewportWidth = window.innerWidth || document.documentElement.clientWidth;

            pageZoom = viewportWidth < totalWidth
                ? Math.max(0.15, Math.floor((viewportWidth / totalWidth) * 100) / 100)
                : 1.0;

            applyZoom();
        }

        document.addEventListener('DOMContentLoaded', autoFit);

        let resizeTimer;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimer);
            resizeTimer = setTimeout(() => {
                if (!userManualZoom) {
                    autoFit();
                }
            }, 150);
        });
    </script>
</body>
</html>
