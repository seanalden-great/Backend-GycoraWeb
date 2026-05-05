<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Peringatan Stok Menipis</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 30px;
            border-radius: 12px;
            border-top: 5px solid #ef4444; /* Merah untuk alert */
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }
        .header {
            font-size: 22px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 20px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .content {
            font-size: 15px;
            color: #4b5563;
            line-height: 1.6;
        }
        .details-box {
            background-color: #fef2f2;
            padding: 20px;
            border-radius: 8px;
            margin: 25px 0;
            border: 1px solid #fecaca;
        }
        .details-box p {
            margin: 8px 0;
            color: #7f1d1d;
        }
        .stock-highlight {
            font-size: 28px;
            font-weight: 900;
            color: #991b1b;
            display: block;
            margin-top: 5px;
        }
        .button-container {
            text-align: center;
            margin-top: 30px;
        }
        .btn {
            background-color: #111827;
            color: #ffffff;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-block;
        }
        .footer {
            margin-top: 40px;
            font-size: 12px;
            color: #9ca3af;
            text-align: center;
            border-top: 1px solid #e5e7eb;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            Peringatan Stok Kritis
        </div>
        <div class="content">
            <p>Halo Tim Admin,</p>
            <p>Sistem mendeteksi bahwa salah satu produk di katalog Gycora saat ini hampir habis. Segera lakukan pengecekan fisik dan <strong>restock</strong> agar tidak kehilangan potensi penjualan.</p>

            <div class="details-box">
                <p><strong>Nama Produk:</strong><br> {{ $productName }}</p>
                <p><strong>SKU:</strong><br> <span style="font-family: monospace;">{{ $productSku }}</span></p>
                <p><strong>Sisa Stok Saat Ini:</strong></p>
                <span class="stock-highlight">{{ $currentStock }} Unit</span>
            </div>

            <div class="button-container">
                <!-- Sesuaikan URL di bawah dengan URL dashboard admin Anda -->
                <a href="{{ config('app.frontend_url') }}/admin/products" class="btn">Buka Panel Admin</a>
            </div>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Gycora System. Email ini dibuat otomatis oleh Gycora Web Platform.
        </div>
    </div>
</body>
</html>
