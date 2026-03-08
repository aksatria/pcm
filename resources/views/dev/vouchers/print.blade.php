{{-- resources/views/dev/vouchers/print.blade.php --}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher {{ $voucher->voucher_number }}</title>
    <style>
        @media print {
            body { margin: 0; padding: 0; }
            .no-print { display: none !important; }
            .page-break { page-break-before: always; }
            @page { margin: 0.5cm; }
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
            color: #333;
        }
        
        .voucher-container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px double #333;
            padding-bottom: 20px;
        }
        
        .company-info {
            margin-bottom: 10px;
        }
        
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .company-address {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .voucher-title {
            font-size: 20px;
            font-weight: bold;
            margin: 20px 0 10px;
        }
        
        .voucher-number {
            font-size: 16px;
            background-color: #f8f9fa;
            padding: 5px 15px;
            border-radius: 5px;
            display: inline-block;
        }
        
        .voucher-info {
            margin: 20px 0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .info-item {
            padding: 10px;
            border: 1px solid #dee2e6;
            border-radius: 5px;
        }
        
        .info-label {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #333;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .items-table th {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            text-align: left;
            font-weight: bold;
            color: #2c3e50;
        }
        
        .items-table td {
            border: 1px solid #dee2e6;
            padding: 10px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .total-section {
            margin-top: 30px;
            border-top: 2px solid #333;
            padding-top: 20px;
        }
        
        .total-table {
            width: 300px;
            margin-left: auto;
            border-collapse: collapse;
        }
        
        .total-table td {
            padding: 8px 15px;
            border: 1px solid #dee2e6;
        }
        
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        
        .grand-total {
            font-size: 18px;
            color: #e74c3c;
        }
        
        .signature-section {
            margin-top: 60px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }
        
        .signature-box {
            text-align: center;
            padding-top: 50px;
            border-top: 1px solid #333;
        }
        
        .signature-label {
            font-weight: bold;
            margin-top: 10px;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
            border-top: 1px solid #dee2e6;
            padding-top: 20px;
        }
        
        .status-badge {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-draft { background-color: #6c757d; color: white; }
        .status-submitted { background-color: #0dcaf0; color: white; }
        .status-approved { background-color: #198754; color: white; }
        .status-paid { background-color: #ffc107; color: black; }
        .status-completed { background-color: #198754; color: white; }
        .status-rejected { background-color: #dc3545; color: white; }
        
        .print-controls {
            margin-bottom: 20px;
            text-align: center;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        
        .terms {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            font-size: 12px;
        }
        
        .terms h6 {
            margin-bottom: 10px;
            color: #2c3e50;
        }
        
        .page-break {
            page-break-before: always;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <div class="print-controls no-print">
        <button onclick="window.print()" class="btn btn-primary">Print Voucher</button>
        <button onclick="window.history.back()" class="btn btn-secondary">Kembali</button>
    </div>
    
    <div class="voucher-container">
        <!-- Header -->
        <div class="header">
            <div class="company-info">
                <div class="company-name">PT. PRODEV CONSULTANT MANAGEMENT</div>
                <div class="company-address">
                    Jl. Mampang Prapatan Raya No. 100, Jakarta Selatan 12760<br>
                    Telp: (021) 12345678 | Email: info@prodev-cm.com
                </div>
            </div>
            
            <div class="voucher-title">VOUCHER PEMBAYARAN</div>
            <div class="voucher-number">{{ $voucher->voucher_number }}</div>
            
            <div style="margin-top: 15px;">
                <span class="status-badge status-{{ $voucher->status }}">
                    {{ strtoupper($voucher->status) }}
                </span>
            </div>
        </div>
        
        <!-- Voucher Information -->
        <div class="voucher-info">
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Project</div>
                    <div class="info-value">{{ $project->name }} ({{ $project->code }})</div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Tanggal Voucher</div>
                    <div class="info-value">{{ $voucher->tanggal->format('d F Y') }}</div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Vendor</div>
                    <div class="info-value">
                        @if($voucher->vendor)
                            {{ $voucher->vendor->nama }}<br>
                            {{ $voucher->vendor->perusahaan }}
                        @else
                            -
                        @endif
                    </div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Metode Pembayaran</div>
                    <div class="info-value">{{ strtoupper($voucher->pembayaran) }}</div>
                </div>
                
                @if($voucher->jatuh_tempo)
                <div class="info-item">
                    <div class="info-label">Jatuh Tempo</div>
                    <div class="info-value">{{ $voucher->jatuh_tempo->format('d F Y') }}</div>
                </div>
                @endif
                
                <div class="info-item">
                    <div class="info-label">Bank</div>
                    <div class="info-value">{{ $voucher->bank ?? '-' }}</div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">No. Rekening</div>
                    <div class="info-value">{{ $voucher->no_rekening ?? '-' }}</div>
                </div>
                
                <div class="info-item">
                    <div class="info-label">Nama Rekening</div>
                    <div class="info-value">{{ $voucher->nama_rekening ?? '-' }}</div>
                </div>
            </div>
        </div>
        
        <!-- Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th width="50">#</th>
                    <th width="100">KODE</th>
                    <th>URAIAN</th>
                    <th width="80">SAT</th>
                    <th width="100" class="text-right">QTY</th>
                    <th width="120" class="text-right">HARGA SATUAN</th>
                    <th width="120" class="text-right">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                @foreach($voucher->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->kode }}</td>
                    <td>
                        {{ $item->uraian }}
                        @if($item->keterangan)
                        <br><small class="text-muted">{{ $item->keterangan }}</small>
                        @endif
                    </td>
                    <td>{{ $item->satuan }}</td>
                    <td class="text-right">{{ number_format($item->qty, 2, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->total_harga, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Totals -->
        <div class="total-section">
            <table class="total-table">
                <tr>
                    <td>Subtotal</td>
                    <td class="text-right">Rp {{ number_format($voucher->total_tagihan, 0, ',', '.') }}</td>
                </tr>
                
                @if($voucher->ppn > 0)
                <tr>
                    <td>PPN (11%)</td>
                    <td class="text-right">Rp {{ number_format($voucher->ppn, 0, ',', '.') }}</td>
                </tr>
                @endif
                
                @if($voucher->ongkir > 0)
                <tr>
                    <td>Ongkir</td>
                    <td class="text-right">Rp {{ number_format($voucher->ongkir, 0, ',', '.') }}</td>
                </tr>
                @endif
                
                <tr class="total-row grand-total">
                    <td><strong>TOTAL BAYAR</strong></td>
                    <td class="text-right"><strong>Rp {{ number_format($voucher->total_bayar, 0, ',', '.') }}</strong></td>
                </tr>
            </table>
        </div>
        
        <!-- Terms and Conditions -->
        <div class="terms">
            <h6>Terms and Conditions:</h6>
            <ol style="margin: 0; padding-left: 20px;">
                <li>Pembayaran dilakukan sesuai dengan ketentuan yang telah disepakati</li>
                <li>Voucher ini berlaku sebagai bukti transaksi yang sah</li>
                <li>Segala bentuk koreksi terhadap voucher ini harus disertai dengan bukti yang sah</li>
                <li>Voucher ini tidak dapat diuangkan kembali</li>
            </ol>
        </div>
        
        <!-- Signatures -->
        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-label">Diajukan Oleh</div>
                <div style="margin-top: 60px;">___________________________</div>
                <div style="margin-top: 5px;">{{ $voucher->diajukan_oleh ?? '-' }}</div>
                <div style="margin-top: 5px; font-size: 11px;">Tanggal: {{ $voucher->created_at->format('d/m/Y') }}</div>
            </div>
            
            <div class="signature-box">
                <div class="signature-label">Disetujui Oleh</div>
                <div style="margin-top: 60px;">___________________________</div>
                <div style="margin-top: 5px;">{{ $voucher->disetujui_oleh ?? '-' }}</div>
                <div style="margin-top: 5px; font-size: 11px;">
                    @if($voucher->tanggal_persetujuan)
                    Tanggal: {{ $voucher->tanggal_persetujuan->format('d/m/Y') }}
                    @else
                    Tanggal: _________
                    @endif
                </div>
            </div>
            
            <div class="signature-box">
                <div class="signature-label">Diterima Oleh</div>
                <div style="margin-top: 60px;">___________________________</div>
                <div style="margin-top: 5px;">{{ $voucher->vendor->nama ?? '-' }}</div>
                <div style="margin-top: 5px; font-size: 11px;">Tanggal: _________</div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p>Voucher ini dicetak secara otomatis oleh sistem Prodev CM pada {{ now()->format('d F Y H:i:s') }}</p>
            <p>Halaman 1 dari 1</p>
        </div>
    </div>
    
    <!-- Additional Pages for History -->
    @if($voucher->status_history && count($voucher->status_history) > 0)
    <div class="page-break">
        <div class="header">
            <div class="company-info">
                <div class="company-name">PT. PRODEV CONSULTANT MANAGEMENT</div>
            </div>
            <div class="voucher-title">VOUCHER HISTORY</div>
            <div class="voucher-number">{{ $voucher->voucher_number }}</div>
        </div>
        
        <div class="voucher-info">
            <h5>Status History</h5>
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Status</th>
                        <th>Tanggal</th>
                        <th>Oleh</th>
                        <th>Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Draft</td>
                        <td>{{ $voucher->created_at->format('d/m/Y H:i') }}</td>
                        <td>System</td>
                        <td>Voucher dibuat</td>
                    </tr>
                    
                    @if($voucher->tanggal_pengajuan)
                    <tr>
                        <td>Submitted</td>
                        <td>{{ $voucher->tanggal_pengajuan->format('d/m/Y H:i') }}</td>
                        <td>{{ $voucher->diajukan_oleh ?? 'System' }}</td>
                        <td>Diajukan untuk persetujuan</td>
                    </tr>
                    @endif
                    
                    @if($voucher->status === 'approved' && $voucher->tanggal_persetujuan)
                    <tr>
                        <td>Approved</td>
                        <td>{{ $voucher->tanggal_persetujuan->format('d/m/Y H:i') }}</td>
                        <td>{{ $voucher->disetujui_oleh ?? 'System' }}</td>
                        <td>Disetujui untuk pembayaran</td>
                    </tr>
                    @endif
                    
                    @if($voucher->status === 'rejected' && $voucher->tanggal_persetujuan)
                    <tr>
                        <td>Rejected</td>
                        <td>{{ $voucher->tanggal_persetujuan->format('d/m/Y H:i') }}</td>
                        <td>{{ $voucher->disetujui_oleh ?? 'System' }}</td>
                        <td>{{ $voucher->catatan_reject ?? 'Ditolak' }}</td>
                    </tr>
                    @endif
                    
                    @if($voucher->tanggal_pembayaran)
                    <tr>
                        <td>Paid</td>
                        <td>{{ $voucher->tanggal_pembayaran->format('d/m/Y H:i') }}</td>
                        <td>System</td>
                        <td>Voucher telah dibayar</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        
        <div class="footer">
            <p>Halaman 2 dari 2</p>
        </div>
    </div>
    @endif
    
    <script>
        // Auto print if needed
        @if(request()->has('autoprint'))
        window.onload = function() {
            window.print();
        }
        @endif
        
        // Add page break for printing
        window.addEventListener('beforeprint', function() {
            // Add any pre-print adjustments here
        });
    </script>
</body>
</html>
