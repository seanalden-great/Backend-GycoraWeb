<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Gycora</title>
    <style>
        /* CSS Reset untuk Klien Email */
        body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
        table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
        img { -ms-interpolation-mode: bicubic; }
        img { border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
        table { border-collapse: collapse !important; }
        body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f9fafb; }
    </style>
</head>
<body style="background-color: #f9fafb; margin: 0; padding: 0;">

    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f9fafb; padding: 40px 0;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.05);">

                    <tr>
                        <td align="center" style="background-color: #059669; padding: 40px 0;">
                            <h1 style="color: #ffffff; font-size: 32px; font-weight: 800; margin: 0; letter-spacing: 4px; text-transform: uppercase;">
                                GYCORA
                            </h1>
                            <p style="color: #a7f3d0; font-size: 12px; font-weight: bold; tracking: 2px; margin: 5px 0 0 0; letter-spacing: 2px; text-transform: uppercase;">
                                Exclusives
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding: 40px 30px;">
                            <h2 style="color: #111827; font-size: 24px; font-weight: 800; margin: 0 0 20px 0;">
                                Selamat Datang di Klub! ✨
                            </h2>
                            <p style="color: #4b5563; font-size: 16px; line-height: 1.6; margin: 0 0 20px 0;">
                                Halo <strong>{{ $email }}</strong>,
                            </p>
                            <p style="color: #4b5563; font-size: 16px; line-height: 1.6; margin: 0 0 30px 0;">
                                Terima kasih telah bergabung dengan Newsletter Gycora! Kamu sekarang masuk dalam daftar eksklusif kami. Bersiaplah untuk menjadi yang pertama tahu tentang produk terbaru, promo rahasia, dan tips terbaik langsung di kotak masukmu.
                            </p>

                            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center">
                                        <table border="0" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td align="center" bgcolor="#D4FF32" style="border-radius: 50px;">
                                                    <a href="{{ env('APP_FRONTEND_URL', 'https://gycoraessence.netlify.app') }}" target="_blank" style="font-size: 14px; font-weight: bold; color: #111827; text-decoration: none; padding: 14px 32px; border-radius: 50px; border: 1px solid #D4FF32; display: inline-block; text-transform: uppercase; letter-spacing: 1px;">
                                                        Jelajahi Gycora
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <p style="color: #6b7280; font-size: 14px; line-height: 1.6; margin: 30px 0 0 0;">
                                Ada pertanyaan? Jangan ragu untuk membalas email ini atau hubungi tim <i>Customer Service</i> kami.
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 30px;">
                            <hr style="border: 0; border-top: 1px solid #f3f4f6; margin: 0;">
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="background-color: #ffffff; padding: 30px; font-size: 12px; color: #9ca3af; line-height: 1.5;">
                            <p style="margin: 0 0 10px 0;">
                                &copy; {{ date('Y') }} Gycora. All rights reserved.
                            </p>
                            <p style="margin: 0 0 10px 0;">
                                Surabaya, Jawa Timur, Indonesia
                            </p>
                            <p style="margin: 0;">
                                Anda menerima email ini karena Anda telah berlangganan newsletter di website kami.<br>
                                <a href="#" style="color: #059669; text-decoration: underline;">Berhenti Berlangganan</a>
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>
