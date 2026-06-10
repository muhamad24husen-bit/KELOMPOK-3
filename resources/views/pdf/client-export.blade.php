<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Building Directory Report</title>
    <style>
        /* SAKLAR UKURAN KERTAS & MARGIN STANDAR LAPORAN FORMAL */
        @page {
            margin: 1.2cm 1.2cm 1.5cm 1.2cm;
            size: a4 portrait;
        }

        /* RESET & BASE STYLING */
        body { 
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
            font-size: 11px; 
            color: #1e293b;
            line-height: 1.5;
        }

        /* HEADER KOP SURAT LAPORAN */
        .header-container { 
            border-bottom: 2px solid #0f172a; 
            padding-bottom: 12px; 
            margin-bottom: 25px; 
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            color: #1e3a8a;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .report-title {
            font-size: 12px;
            color: #475569;
            margin: 4px 0 0 0;
        }
        .meta-print {
            float: right;
            text-align: right;
            font-size: 9px;
            color: #64748b;
            line-height: 1.4;
        }

        /* OPTIMASI STRUKTUR TABEL UNTUK METODE CETAK MULTI-HALAMAN */
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-top: 10px; 
            page-break-inside: auto; /* Membiarkan tabel berlanjut ke halaman baru secara dinamis */
        }
        
        tr { 
            /* FITUR UTAMA: Mencegah baris data terbelah/terpotong di tengah batas kertas */
            page-break-inside: avoid; 
            page-break-after: auto; 
        }

        thead { 
            /* FITUR UTAMA: Memaksa header tabel otomatis muncul kembali di halaman 2, 3, dst */
            display: table-header-group; 
        }

        th { 
            background-color: #0f172a; 
            color: #ffffff; 
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            padding: 8px 10px;
            border: 1px solid #0f172a;
        }

        td { 
            padding: 8px 10px; 
            border: 1px solid #e2e8f0; 
            vertical-align: middle;
        }

        /* ZEBRA STRIPING UNTUK KEMUDAHAN MEMBACA DATA */
        tbody tr:nth-child(even) {
            background-color: #f8fafc;
        }

        /* STATUS BADGE STYLING */
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 9px;
            font-weight: bold;
            text-align: center;
        }
        .badge-active { 
            background-color: #dcfce7; 
            color: #15803d; 
        }
        .badge-expired { 
            background-color: #fee2e2; 
            color: #b91c1c; 
        }

        /* UTILITY CLASSES */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-semibold { font-weight: 600; }
        
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        /* FITUR UTAMA: PENOMORAN HALAMAN DINAMIS OTOMATIS */
        .footer {
            position: fixed;
            bottom: -0.8cm;
            left: 0px;
            right: 0px;
            height: 0.5cm;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
        .page-number:before {
            content: counter(page);
        }
    </style>
</head>
<body>

    <div class="header-container clearfix">
        <div class="meta-print">
            <strong>BEMS System Portal</strong><br>
            Tanggal Dokumen: {{ now()->translatedFormat('d F Y') }}<br>
            Waktu Cetak: {{ now()->format('H:i') }} WIB
        </div>
        <div>
            <h1 class="company-name">Kelompok 3 Infrastructure Management</h1>
            <div class="report-title">Data Direktori Kompleks Bangunan dan Status Operasional Client</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="30%">Building Name</th>
                <th width="20%">Code / Zone</th>
                <th width="25%">Address / Gedung</th>
                <th width="12%">Expiry Date</th>
                <th width="8%" class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($clients as $index => $client)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-semibold">{{ $client->name }}</td>
                    <td>{{ $client->code }} {{ $client->kelas ? '('.$client->kelas.')' : '' }}</td>
                    <td>{{ $client->gedung ?? 'N/A' }}</td>
                    <td>{{ $client->expirity ? \Carbon\Carbon::parse($client->expirity)->translatedFormat('d-m-Y') : 'N/A' }}</td>
                    <td class="text-center">
                        @if($client->expirity && \Carbon\Carbon::parse($client->expirity)->isPast())
                            <span class="badge badge-expired">Expired</span>
                        @else
                            <span class="badge badge-active">Active</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center" style="padding: 20px; color: #64748b;">
                        Tidak ada rekaman direktori bangunan yang sesuai dengan kriteria pencarian sistem.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Laporan Direktori Gedung Terintegrasi BEMS - Halaman <span class="page-number"></span>
    </div>

</body>
</html>
