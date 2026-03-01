<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use App\Models\User;
use App\Mail\OtpMail;
use Illuminate\Http\Request;
use App\Models\SmsApiInfo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;

class PasswordResetOtpController extends Controller
{
    /**
     * Step 1: Show Forgot Password Form
     */
    public function showRequestForm()
    {
        return Inertia::render('auth/ForgotPassword');
    }

    /**
     * Step 1: Send OTP (via email or SMS)
     */
    public function sendOtp(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        $identifier = $request->identifier;

        // Find user by email or mobile
        $user = User::where('email', $identifier)
            ->orWhere('mobile', $identifier)
            ->first();

        if (!$user) {
            return back()->withErrors(['identifier' => 'User not found with that email or mobile number.']);
        }

        // Generate 6-digit OTP
        $otp = rand(100000, 999999);

        // Store OTP and user ID for 10 minutes
        Cache::put("otp_$identifier", $otp, now()->addMinutes(10));
        Cache::put("otp_user_$identifier", $user->id, now()->addMinutes(10));

        // Send OTP via email or SMS
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            // Send via email using OtpMail Mailable
            Mail::to($identifier)->send(new OtpMail($otp, $user->name));
        } else {
            // Send via SMS
            $this->sendOtpViaSms($identifier, $otp);
        }

        return redirect()->route('password.verifyForm')->with('identifier', $identifier);
    }

    /**
     * Step 2: Show Verify OTP Form
     */
    public function showVerifyForm(Request $request)
    {
        return Inertia::render('auth/VerifyOtp', [
            'identifier' => session('identifier'),
        ]);
    }

    /**
     * Step 2: Verify OTP
     */
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'otp' => 'required|numeric',
        ]);

        $identifier = $request->identifier;
        $otp = $request->otp;
        $storedOtp = Cache::get("otp_$identifier");

        if (!$storedOtp || $storedOtp != $otp) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP.']);
        }

        // OTP verified — redirect to reset password form
        return redirect()->route('password.resetForm')->with('identifier', $identifier);
    }

    /**
     * Step 3: Show Reset Password Form
     */
    public function showResetForm(Request $request)
    {
        return Inertia::render('auth/ResetPassword', [
            'identifier' => session('identifier'),
        ]);
    }

    /**
     * Step 3: Handle Password Reset
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'identifier' => 'required|string',
            'password' => 'required|string|min:5|confirmed',
        ]);

        $identifier = $request->identifier;
        $userId = Cache::get("otp_user_$identifier");

        if (!$userId) {
            return back()->withErrors(['identifier' => 'Session expired. Please try again.']);
        }

        $user = User::find($userId);
        $user->password = Hash::make($request->password);
        $user->save();

        // Clear OTP cache
        Cache::forget("otp_$identifier");
        Cache::forget("otp_user_$identifier");

        return redirect()->route('employee.login')->with('status', 'Password reset successfully!');
    }

    /**
     * Send OTP via SMS (using Mobireach API)
     */
    private function sendOtpViaSms($mobile, $otp)
    {
        $smsApi = SmsApiInfo::first();
        if (!$smsApi) return false;

        $url = $smsApi->smsLinkUrl;
        $data = [
            'Username' => $smsApi->userName,
            'Password' => $smsApi->password,
            'From' => $smsApi->from,
            'To' => $mobile,
            'Message' => "Your password reset OTP is: {$otp}",
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }
}
