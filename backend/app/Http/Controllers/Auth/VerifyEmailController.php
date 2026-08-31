<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        // Validasi hash email
        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            if ($request->wantsJson()) {
                return response()->json(['message' => 'Link verifikasi tidak valid.'], 400);
            }
            return response('<div style="font-family:sans-serif; text-align:center; padding:50px;"><h2>❌ Link Verifikasi Tidak Valid</h2><p>Pastikan Anda menggunakan link verifikasi terbaru yang dikirimkan ke email Anda.</p></div>', 400);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        // Jika diakses langsung lewat browser (bukan JSON API)
        if (! $request->wantsJson()) {
            return response('
                <!DOCTYPE html>
                <html lang="id">
                <head>
                    <meta charset="UTF-8">
                    <title>Email Berhasil Diverifikasi - CoFund</title>
                    <style>
                        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f8fafc; color: #0f172a; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
                        .card { background: white; padding: 40px; border-radius: 24px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05); text-align: center; max-width: 420px; border: 1px solid #e2e8f0; }
                        .icon { width: 64px; height: 64px; background: #ecfdf5; color: #059669; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 20px; }
                        h1 { font-size: 22px; font-weight: 800; margin: 0 0 8px; }
                        p { font-size: 14px; color: #64748b; margin: 0 0 24px; line-height: 1.5; }
                        .btn { display: inline-block; background: #059669; color: white; text-decoration: none; font-weight: 700; font-size: 14px; padding: 12px 28px; border-radius: 14px; transition: background 0.2s; }
                        .btn:hover { background: #047857; }
                    </style>
                </head>
                <body>
                    <div class="card">
                        <div class="icon">✓</div>
                        <h1>Email Berhasil Diverifikasi!</h1>
                        <p>Akun Anda kini telah aktif sepenuhnya. Anda sekarang dapat membuat kampanye galang dana dan melakukan backing proyek.</p>
                        <a href="http://localhost:5173/login" class="btn">Buka CoFund Sekarang</a>
                    </div>
                </body>
                </html>
            ', 200);
        }

        return response()->json([
            'message' => 'Email berhasil diverifikasi.',
            'user' => $user,
        ], 200);
    }
}
