<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PromoCode;
use Illuminate\Support\Str;
use Carbon\Carbon;

class GeneratePromoCodes extends Command
{
    // Nama perintah yang akan Anda ketik di terminal
    protected $signature = 'promo:generate {count=20}';

    protected $description = 'Generate kode promo unik untuk dibagikan oleh Manajemen';

    public function handle()
    {
        $count = $this->argument('count');
        // Set kedaluwarsa pada 4 Agustus 2026 jam 23:59:59
        $expiresAt = Carbon::create(2026, 8, 4, 23, 59, 59);
        $codes = [];

        $this->info("Memulai generasi {$count} kode promo...");

        for ($i = 0; $i < $count; $i++) {
            // Format: GYC-XXXXXX (6 Karakter Random)
            $code = 'GYC-' . strtoupper(Str::random(6));

            // Validasi: Pastikan kode benar-benar belum pernah ada di database
            while (PromoCode::where('code', $code)->exists()) {
                $code = 'GYC-' . strtoupper(Str::random(6));
            }

            PromoCode::create([
                'code' => $code,
                'discount_value' => 25000,
                'max_uses' => 1,
                'times_used' => 0,
                'expires_at' => $expiresAt,
            ]);

            $codes[] = $code;
        }

        $this->info("Selesai! {$count} kode berhasil dimasukkan ke database.");
        $this->line("==================================================");
        $this->line("Silakan copy daftar kode di bawah ini untuk bos Anda:");
        $this->line("==================================================");

        foreach ($codes as $index => $c) {
            $this->line(($index + 1) . ". " . $c);
        }
    }
}
