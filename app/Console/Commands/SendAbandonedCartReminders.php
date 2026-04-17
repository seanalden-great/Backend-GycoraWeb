<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Mail\AbandonedCartMail;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SendAbandonedCartReminders extends Command
{
    // Nama perintah yang akan dieksekusi di terminal
    protected $signature = 'cart:send-reminders';

    protected $description = 'Mengirim email notifikasi untuk keranjang yang terabaikan selama 3 hari';

    public function handle()
    {
        // Targetkan waktu tepat 3 hari yang lalu agar tidak mengirim email setiap hari ke orang yang sama
        $targetDate = Carbon::now()->subDays(3)->toDateString();

        // Cari user yang memiliki keranjang yang terakhir diupdate 3 hari yang lalu
        $users = User::whereHas('carts', function ($query) use ($targetDate) {
            $query->whereDate('updated_at', $targetDate);
        })->with(['carts' => function ($query) use ($targetDate) {
            // Ambil isi keranjangnya beserta relasi produk
            $query->whereDate('updated_at', $targetDate)->with('product');
        }])->get();

        $count = 0;

        foreach ($users as $user) {
            if ($user->carts->isNotEmpty()) {
                try {
                    Mail::to($user->email)->send(new AbandonedCartMail($user, $user->carts));
                    $count++;
                } catch (\Exception $e) {
                    Log::error("Gagal mengirim email abandoned cart ke {$user->email}: " . $e->getMessage());
                }
            }
        }

        $this->info("Berhasil mengirim {$count} email notifikasi keranjang terabaikan.");
    }
}
