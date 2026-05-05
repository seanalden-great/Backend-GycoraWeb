<?php

namespace App\Mail;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class LowStockAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public $product;

    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    public function build()
    {
        return $this->subject('Peringatan: Stok Menipis - ' . $this->product->sku)
                    ->view('emails.low_stock') // Kita asumsikan Anda akan membuat file blade sederhana
                    ->with([
                        'productName' => $this->product->name,
                        'productSku' => $this->product->sku,
                        'currentStock' => $this->product->stock,
                    ]);
    }
}
