<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Slip Gaji - {{ $employee->full_name }}</title>

    <style>
        @page {
            margin: 12mm;
        }

        @page:first {
            margin: 12mm;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            color: #111827;
            font-size: 10pt;
            line-height: 1.5;
            background: #ffffff;
        }

        .pdf-preview-card {
            width: 100%;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            overflow: hidden;
        }

        /*
         * FIX PAGE BREAK:
         * Page break cukup dipasang di akhir halaman 1.
         * Jangan pakai page-break-before di halaman 2 agar DOMPDF tidak menumpuk header.
         */
        .page-1-card {
            page-break-after: always;
        }

        .page-2-card {
            page-break-before: auto;
        }

        .header-shell {
            position: relative;
            overflow: hidden;
            color: #ffffff;
            background-color: #1e293b;
            padding: 28px 32px;
        }

        .header-gradient-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }

        .header-circle-large,
        .header-circle-small {
            position: absolute;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.05);
        }

        .header-circle-large {
            top: -128px;
            right: -85px;
            width: 256px;
            height: 256px;
            z-index: 1;
        }

        .header-circle-small {
            bottom: -64px;
            left: -48px;
            width: 192px;
            height: 192px;
            z-index: 1;
        }

        .header-table {
            position: relative;
            z-index: 2;
            width: 100%;
            border-collapse: collapse;
        }

        .header-left,
        .header-right {
            vertical-align: top;
        }

        .header-left {
            width: 68%;
        }

        .header-right {
            width: 32%;
            text-align: right;
        }

        .company-table {
            border-collapse: collapse;
        }

        .logo-cell {
            width: 96px;
            vertical-align: middle;
            padding-right: 16px;
        }

        .header-logo-img {
            width: 80px;
            height: 80px;
            object-fit: contain;
        }

        .logo-placeholder {
            width: 80px;
            height: 80px;
            color: #9ca3af;
            font-size: 18px;
            font-weight: 700;
            text-align: center;
            line-height: 80px;
        }

        .company-name {
            font-size: 14pt;
            font-weight: 700;
            letter-spacing: 0;
        }

        .company-address {
            margin-top: 2px;
            color: #bfdbfe;
            font-size: 9pt;
        }

        .company-contact,
        .ref-text {
            color: #bfdbfe;
            font-size: 9pt;
        }

        .company-contact {
            margin-top: 2px;
        }

        .badge {
            display: inline-block;
            padding: 6px 16px;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.15);
            font-size: 9pt;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .ref-text.top {
            margin-top: 8px;
        }

        .content {
            padding: 32px;
        }

        .section {
            margin-top: 28px;
            page-break-inside: avoid;
        }

        .section:first-child {
            margin-top: 0;
        }

        .first-page-payroll-section {
            margin-top: 18px;
            page-break-inside: avoid;
        }

        .first-page-payroll-section .section-heading {
            margin-bottom: 8px;
        }

        .first-page-payroll-section .payslip-table th,
        .first-page-payroll-section .payslip-table td {
            padding: 6px 14px;
            font-size: 9.5pt;
        }

        .company-contribution-section {
            margin-top: 0;
            padding-top: 0;
            page-break-inside: avoid;
        }

        .company-contribution-section .section-heading {
            margin-bottom: 8px;
        }

        .company-contribution-section .payslip-table th,
        .company-contribution-section .payslip-table td {
            padding: 6px 14px;
            font-size: 9.5pt;
        }

        .info-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 24px 0;
            margin-left: -24px;
            background: #f9fafb;
            border: 1px solid #f3f4f6;
            border-radius: 12px;
            padding: 24px 0 24px 24px;
        }

        .info-col {
            width: 50%;
            vertical-align: top;
        }

        .info-col-title {
            margin-bottom: 12px;
            padding-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
            color: #6366f1;
            font-size: 9pt;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
        }

        .info-table td {
            padding: 2px 0;
            font-size: 10.5pt;
        }

        .info-table .label {
            width: 42%;
            color: #6b7280;
        }

        .info-table .value {
            width: 58%;
            color: #111827;
            font-weight: 500;
            text-align: right;
        }

        .info-table .value.strong {
            font-weight: 700;
        }

        .section-heading {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
            page-break-after: avoid;
        }

        .section-heading .accent-cell {
            width: 10px;
            vertical-align: middle;
        }

        .section-accent {
            display: block;
            width: 4px;
            height: 16px;
            border-radius: 2px;
        }

        .accent-income {
            background: #10b981;
        }

        .accent-deduction {
            background: #ef4444;
        }

        .accent-company {
            background: #6366f1;
        }

        .section-title {
            color: #374151;
            font-size: 10.5pt;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .payslip-table {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            page-break-inside: avoid;
        }

        .payslip-table th {
            padding: 10px 16px;
            border-bottom: 1px solid #e5e7eb;
            background: #f9fafb;
            color: #6b7280;
            font-size: 9pt;
            font-weight: 600;
            letter-spacing: 0.05em;
            text-align: left;
            text-transform: uppercase;
        }

        .payslip-table th.amount,
        .payslip-table td.amount {
            width: 30%;
            text-align: right;
        }

        .payslip-table th:first-child,
        .payslip-table td:first-child {
            width: 70%;
        }

        .payslip-table td {
            padding: 10px 16px;
            border-bottom: 1px solid #f3f4f6;
            font-size: 10.5pt;
        }

        .payslip-table tr:last-child td {
            border-bottom: 0;
        }

        .payslip-table td.amount {
            font-weight: 500;
        }

        .payslip-table tr.subtotal td {
            border-top: 1px solid #e5e7eb;
            background: #f9fafb;
            color: #111827;
            font-weight: 700;
        }

        .payslip-table tr.subtotal td.amount {
            color: #2563eb;
        }

        .payslip-table tr.total-red td.amount,
        .payslip-table .text-red {
            color: #dc2626;
        }

        .net-box {
            width: 100%;
            border: 2px solid #10b981;
            border-radius: 12px;
            border-collapse: separate;
            background: #ecfdf5;
        }

        .net-box td {
            padding: 20px 24px;
            vertical-align: middle;
        }

        .net-label {
            color: #065f46;
            font-size: 12pt;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
        }

        .net-value {
            color: #059669;
            font-size: 18pt;
            font-weight: 800;
            text-align: right;
        }

        .signatures {
            width: 100%;
            border-collapse: separate;
            border-spacing: 32px 0;
            margin-left: -32px;
        }

        .sig {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding-top: 18px;
        }

        .sig-city {
            color: #6b7280;
            font-size: 10.5pt;
            text-align: center;
        }

        .sig-line {
            width: 66.67%;
            margin: 0 auto 6px;
            border-bottom: 1px solid #d1d5db;
        }

        .sig-name {
            color: #111827;
            font-size: 10.5pt;
            font-weight: 700;
        }

        .sig-role {
            color: #6b7280;
            font-size: 9pt;
        }

        .signature-mark-space {
            height: 112px;
            text-align: center;
        }

        .director-signature-frame {
            width: 66.67%;
            height: 112px;
            overflow: hidden;
            margin: 0 auto -12px;
            text-align: center;
        }

        .director-signature-img {
            display: block;
            width: 100%;
            height: auto;
            margin: 0 auto;
        }

        .footer {
            padding: 12px 32px;
            border-top: 1px solid #f3f4f6;
            background: #f9fafb;
            color: #9ca3af;
            font-size: 9pt;
            text-align: center;
        }

        .developer-logo {
            display: block;
            width: 112px;
            height: auto;
            object-fit: contain;
            margin: 8px auto 0;
        }

        .developer-text {
            display: block;
            text-align: center;
        }

        .developer-footer {
            background-color: #1e293b;
            background-image: linear-gradient(90deg, #1e293b 0%, #1d4ed8 55%, #7c3aed 100%);
            color: #ffffff;
        }
    </style>
</head>

<body>
@php
    $period = \Carbon\Carbon::parse($payroll->period_start)->format('d M Y') . ' - ' . \Carbon\Carbon::parse($payroll->period_end)->format('d M Y');
    $printDate = now()->format('d F Y');
    $printDateTime = now()->format('d M Y H:i');
    $status = strtoupper($payroll->status->value ?? (string) $payroll->status);
@endphp

<!-- ========================= -->
<!-- HALAMAN 1 -->
<!-- ========================= -->
<div class="pdf-preview-card page-1-card">
    <div class="header-shell">
        <img class="header-gradient-bg" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwMCIgaGVpZ2h0PSIyNDAiIHZpZXdCb3g9IjAgMCAxMjAwIDI0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiBwcmVzZXJ2ZUFzcGVjdFJhdGlvPSJub25lIj48cmVjdCB3aWR0aD0iMTIwMCIgaGVpZ2h0PSIyNDAiIGZpbGw9InVybCgjZykiLz48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImciIHgxPSIwIiB5MT0iMTIwIiB4Mj0iMTIwMCIgeTI9IjEyMCIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiPjxzdG9wIG9mZnNldD0iMCIgc3RvcC1jb2xvcj0iIzFlMjkzYiIvPjxzdG9wIG9mZnNldD0iMC41NSIgc3RvcC1jb2xvcj0iIzFkNGVkOCIvPjxzdG9wIG9mZnNldD0iMSIgc3RvcC1jb2xvcj0iIzdjM2FlZCIvPjwvbGluZWFyR3JhZGllbnQ+PC9kZWZzPjwvc3ZnPg==" alt="">
        <div class="header-circle-large"></div>
        <div class="header-circle-small"></div>

        <table class="header-table">
            <tr>
                <td class="header-left">
                    <table class="company-table">
                        <tr>
                            <td class="logo-cell">
                                @if($company['company_logo'])
                                    <img src="{{ $company['company_logo'] }}" alt="Logo" class="header-logo-img">
                                @else
                                    <div class="logo-placeholder">Logo</div>
                                @endif
                            </td>
                            <td>
                                <div class="company-name">{{ $company['company_name'] }}</div>
                                <div class="company-address">{{ $company['company_address'] }}</div>
                                <div class="company-contact">
                                    Telp: {{ $company['company_phone'] }} &bull; Email: {{ $company['company_email'] }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>

                <td class="header-right">
                    <span class="badge">Slip Gaji</span>
                    <div class="ref-text top">No. {{ $payslipNumber }}</div>
                    <div class="ref-text">Status: {{ $status }} | Hal. 1/2</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        <div class="section">
            <table class="info-grid">
                <tr>
                    <td class="info-col">
                        <div class="info-col-title">Data Karyawan</div>

                        <table class="info-table">
                            <tr>
                                <td class="label">Nama</td>
                                <td class="value strong">{{ $employee->full_name }}</td>
                            </tr>
                            <tr>
                                <td class="label">NIK</td>
                                <td class="value">{{ $employee->nik }}</td>
                            </tr>
                            <tr>
                                <td class="label">NPWP</td>
                                <td class="value">{{ $employee->npwp ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label">Posisi</td>
                                <td class="value">{{ $employee->position }}</td>
                            </tr>
                            <tr>
                                <td class="label">Departemen</td>
                                <td class="value">{{ $employee->department ?: '-' }}</td>
                            </tr>
                        </table>
                    </td>

                    <td class="info-col">
                        <div class="info-col-title">Periode &amp; Pembayaran</div>

                        <table class="info-table">
                            <tr>
                                <td class="label">Periode</td>
                                <td class="value strong">{{ $period }}</td>
                            </tr>
                            <tr>
                                <td class="label">Tgl Cetak</td>
                                <td class="value">{{ $printDate }}</td>
                            </tr>
                            <tr>
                                <td class="label">Bank</td>
                                <td class="value">{{ $employee->bank_name ?: '-' }}</td>
                            </tr>
                            <tr>
                                <td class="label">No. Rekening</td>
                                <td class="value">{{ $employee->bank_account_number ?: '-' }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <div class="section first-page-payroll-section">
            <table class="section-heading">
                <tr>
                    <td class="accent-cell">
                        <span class="section-accent accent-income"></span>
                    </td>
                    <td class="section-title">Pendapatan</td>
                </tr>
            </table>

            <table class="payslip-table">
                <thead>
                    <tr>
                        <th>Keterangan</th>
                        <th class="amount">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Gaji Pokok</td>
                        <td class="amount">{{ number_format($baseSalary, 0, ',', '.') }}</td>
                    </tr>

                    @if($item->allowances_total > 0)
                        <tr>
                            <td>Tunjangan</td>
                            <td class="amount">{{ number_format($item->allowances_total, 0, ',', '.') }}</td>
                        </tr>
                    @endif

                    @if($item->bonuses_total > 0)
                        <tr>
                            <td>Bonus</td>
                            <td class="amount">{{ number_format($item->bonuses_total, 0, ',', '.') }}</td>
                        </tr>
                    @endif

                    @if($item->overtime_pay > 0)
                        <tr>
                            <td>Uang Lembur</td>
                            <td class="amount">{{ number_format($item->overtime_pay, 0, ',', '.') }}</td>
                        </tr>
                    @endif

                    <tr class="subtotal">
                        <td>Total Pendapatan Bruto</td>
                        <td class="amount">{{ number_format($item->gross_salary, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section first-page-payroll-section">
            <table class="section-heading">
                <tr>
                    <td class="accent-cell">
                        <span class="section-accent accent-deduction"></span>
                    </td>
                    <td class="section-title">Potongan</td>
                </tr>
            </table>

            <table class="payslip-table">
                <thead>
                    <tr>
                        <th>Keterangan</th>
                        <th class="amount">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>BPJS Kesehatan (1%)</td>
                        <td class="amount">{{ number_format($item->bpjs_kesehatan_employee, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>BPJS TK - JHT (2%)</td>
                        <td class="amount">{{ number_format($item->bpjs_tk_jht_employee, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>BPJS TK - JP (1%)</td>
                        <td class="amount">{{ number_format($item->bpjs_tk_jp_employee, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>PPh 21 Pajak Penghasilan</td>
                        <td class="amount text-red">{{ number_format($item->pph21, 0, ',', '.') }}</td>
                    </tr>

                    @if($item->deductions_total > 0)
                        <tr>
                            <td>Potongan Lain-lain</td>
                            <td class="amount">{{ number_format($item->deductions_total, 0, ',', '.') }}</td>
                        </tr>
                    @endif

                    <tr class="subtotal total-red">
                        <td>Total Potongan</td>
                        <td class="amount">{{ number_format($totalDeductions, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================= -->
<!-- HALAMAN 2 -->
<!-- ========================= -->
<div class="pdf-preview-card page-2-card">
    <!-- HEADER HALAMAN 2: hanya 1 header besar, tidak ada header kecil -->
    <div class="header-shell">
        <img class="header-gradient-bg" src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTIwMCIgaGVpZ2h0PSIyNDAiIHZpZXdCb3g9IjAgMCAxMjAwIDI0MCIgZmlsbD0ibm9uZSIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIiBwcmVzZXJ2ZUFzcGVjdFJhdGlvPSJub25lIj48cmVjdCB3aWR0aD0iMTIwMCIgaGVpZ2h0PSIyNDAiIGZpbGw9InVybCgjZykiLz48ZGVmcz48bGluZWFyR3JhZGllbnQgaWQ9ImciIHgxPSIwIiB5MT0iMTIwIiB4Mj0iMTIwMCIgeTI9IjEyMCIgZ3JhZGllbnRVbml0cz0idXNlclNwYWNlT25Vc2UiPjxzdG9wIG9mZnNldD0iMCIgc3RvcC1jb2xvcj0iIzFlMjkzYiIvPjxzdG9wIG9mZnNldD0iMC41NSIgc3RvcC1jb2xvcj0iIzFkNGVkOCIvPjxzdG9wIG9mZnNldD0iMSIgc3RvcC1jb2xvcj0iIzdjM2FlZCIvPjwvbGluZWFyR3JhZGllbnQ+PC9kZWZzPjwvc3ZnPg==" alt="">
        <div class="header-circle-large"></div>
        <div class="header-circle-small"></div>

        <table class="header-table">
            <tr>
                <td class="header-left">
                    <table class="company-table">
                        <tr>
                            <td class="logo-cell">
                                @if($company['company_logo'])
                                    <img src="{{ $company['company_logo'] }}" alt="Logo" class="header-logo-img">
                                @else
                                    <div class="logo-placeholder">Logo</div>
                                @endif
                            </td>
                            <td>
                                <div class="company-name">{{ $company['company_name'] }}</div>
                                <div class="company-address">{{ $company['company_address'] }}</div>
                                <div class="company-contact">
                                    Telp: {{ $company['company_phone'] }} &bull; Email: {{ $company['company_email'] }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>

                <td class="header-right">
                    <span class="badge">Slip Gaji</span>
                    <div class="ref-text top">No. {{ $payslipNumber }}</div>
                    <div class="ref-text">Status: {{ $status }} | Hal. 2/2</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        <div class="section company-contribution-section">
            <table class="section-heading">
                <tr>
                    <td class="accent-cell">
                        <span class="section-accent accent-company"></span>
                    </td>
                    <td class="section-title">Iuran Perusahaan</td>
                </tr>
            </table>

            <table class="payslip-table">
                <thead>
                    <tr>
                        <th>Keterangan</th>
                        <th class="amount">Jumlah (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>BPJS Kesehatan - Iuran Pemberi Kerja (4%)</td>
                        <td class="amount">{{ number_format($item->bpjs_kesehatan_company, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>BPJS TK - JHT Pemberi Kerja (3.7%)</td>
                        <td class="amount">{{ number_format($item->bpjs_tk_jht_company, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>BPJS TK - JP Pemberi Kerja (2%)</td>
                        <td class="amount">{{ number_format($item->bpjs_tk_jp_company, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>BPJS TK - JKK (0.24%)</td>
                        <td class="amount">{{ number_format($item->bpjs_tk_jkk, 0, ',', '.') }}</td>
                    </tr>
                    <tr>
                        <td>BPJS TK - JKM (0.3%)</td>
                        <td class="amount">{{ number_format($item->bpjs_tk_jkm, 0, ',', '.') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="section">
            <table class="net-box">
                <tr>
                    <td class="net-label">Take Home Pay</td>
                    <td class="net-value">Rp {{ number_format($item->net_salary, 0, ',', '.') }}</td>
                </tr>
            </table>
        </div>

        <div class="section">
            <div class="sig-city">Bangkalan, {{ $printDate }}</div>
        </div>

        <div class="section">
            <table class="signatures">
                <tr>
                    <td class="sig">
                        <div class="signature-mark-space">
                            @if($company['director_signature'])
                                <div class="director-signature-frame">
                                    <img src="{{ $company['director_signature'] }}" alt="Tanda tangan direktur" class="director-signature-img">
                                </div>
                            @endif
                        </div>

                        <div class="sig-line"></div>
                        <div class="sig-name">{{ $company['company_director'] }}</div>
                        <div class="sig-role">Direktur {{ $company['company_name'] }}</div>
                    </td>

                    <td class="sig">
                        <div class="signature-mark-space"></div>
                        <div class="sig-line"></div>
                        <div class="sig-name">{{ $employee->full_name }}</div>
                        <div class="sig-role">Karyawan</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="footer">
        Dokumen ini diterbitkan secara elektronik oleh {{ $company['company_name'] }} dan sah tanpa tanda tangan basah.<br>
        Dicetak: {{ $printDateTime }} WIB
    </div>

    <div class="footer developer-footer">
        <span class="developer-text">Sistem dikembangkan oleh:</span>

        @if($company['dev_logo'])
            <img src="{{ $company['dev_logo'] }}" alt="Logo" class="developer-logo">
        @endif
    </div>
</div>
</body>
</html>