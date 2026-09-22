<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\AdminOtpMail;
use App\Models\UserModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AdminAuthController extends Controller
{
    /**
     * Show the dedicated admin login form.
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.auth.login');
    }

    /**
     * Validate admin credentials and email a one-time code.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = UserModel::where('email', $request->email)->first();

        if (!$user
            || !Hash::check($request->password, $user->password)
            || !$user->isAdmin()
            || !$user->isEmailVerified()
            || !$user->isActive()) {
            return back()->withErrors(['email' => 'Invalid email or password.'])->withInput($request->only('email'));
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        session([
            'admin_otp' => [
                'user_id' => $user->id,
                'code' => $code,
                'expires_at' => now()->addMinutes(10),
                'attempts' => 0,
            ],
        ]);

        Mail::to($user->email)->send(new AdminOtpMail($user, $code));

        return redirect()->route('admin.otp');
    }

    /**
     * Show the OTP entry page.
     */
    public function showOtp()
    {
        if (!session('admin_otp')) {
            return redirect()->route('admin.login');
        }

        return view('admin.auth.otp');
    }

    /**
     * Verify the OTP and log the admin in.
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'code' => 'required|numeric|digits:6',
        ]);

        $otp = session('admin_otp');

        if (!$otp) {
            return redirect()->route('admin.login');
        }

        if (now()->greaterThan($otp['expires_at'])) {
            session()->forget('admin_otp');

            return back()->withErrors(['code' => 'Code expired. Please request a new code by signing in again.']);
        }

        $user = UserModel::find($otp['user_id']);

        if (!$user || $user->role !== 'admin') {
            session()->forget('admin_otp');

            return redirect()->route('admin.login');
        }

        if (!hash_equals((string) $otp['code'], $request->code)) {
            $attempts = $otp['attempts'] + 1;

            if ($attempts >= 5) {
                session()->forget('admin_otp');

                return back()->withErrors(['code' => 'Too many incorrect attempts. Please request a new code by signing in again.']);
            }

            session(['admin_otp' => array_merge($otp, ['attempts' => $attempts])]);

            return back()->withErrors(['code' => 'Invalid verification code.']);
        }

        // The guard draws a new session id and a new CSRF token on login.
        // Keep the token that the verification page rendered, so the page and
        // the reply use one token. The new session id still stops a session
        // fixation attack.
        $sessionToken = $request->session()->token();

        Auth::login($user, false);
        $request->session()->put('_token', $sessionToken);
        session(['user_role' => 'admin']);
        session()->forget('admin_otp');

        return redirect()->route('admin.dashboard');
    }
}
