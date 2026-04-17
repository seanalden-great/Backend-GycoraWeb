<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f9f9f9; padding: 20px; }
        .container { background-color: #ffffff; padding: 30px; border-radius: 10px; max-width: 600px; margin: 0 auto; }
        .header { color: #059669; font-size: 24px; font-weight: bold; margin-bottom: 20px; }
        .product-list { margin-bottom: 30px; }
        .product-item { padding: 10px; border-bottom: 1px solid #eeeeee; }
        .btn { background-color: #059669; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">Hai {{ $user->first_name }},</div>
        <p>Kami melihat Anda meninggalkan beberapa barang luar biasa di keranjang Gycora Anda. Jangan sampai kehabisan, stok kami sangat terbatas!</p>

        <div class="product-list">
            @foreach($carts as $cart)
                <div class="product-item">
                    <strong>{{ $cart->product->name }}</strong>
                    (Qty: {{ $cart->quantity }})
                </div>
            @endforeach
        </div>

        <p>Klik tombol di bawah ini untuk menyelesaikan pesanan Anda sebelum kehabisan.</p>
        <p>
            <a href="https://gycora-web.vercel.app/cart" class="btn">Selesaikan Pembayaran</a>
        </p>

        <p>Terima kasih,<br>Tim Gycora</p>
    </div>
</body>
</html>
