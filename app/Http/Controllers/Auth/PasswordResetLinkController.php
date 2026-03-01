<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use App\Services\SmsService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

class PasswordResetLinkController extends Controller
{
    /**
     * Show the password reset link request page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/ForgotPassword', [
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request, SmsService $smsService): RedirectResponse
    {
        $request->validate([
            'identifier' => 'required|string',
        ]);

        $identifier = trim($request->input('identifier'));

        // If email -> use standard email reset link
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $status = Password::sendResetLink(['email' => $identifier]);

            return back()->with('status', __($status));
        }

        // Treat as phone number (you may want to normalize the phone number here)
        $user = User::where('mobile', $identifier)->first();

        // We do not reveal whether user exists; show same message regardless
        if (! $user) {
            return back()->with('status', __('If the account exists, an OTP has been sent.'));
        }

        // Generate OTP (6 digits)
        $otp = random_int(100000, 999999);

        $user->otp_code = (string) $otp;
        $user->otp_expires_at = now()->addMinutes(10);
        $user->save();

        // Build message - localize as needed
        $message = "Your password reset OTP is: {$otp}. It will expire in 10 minutes.";

        $result = $smsService->send($user->mobile, $message);

        if (! $result['success']) {
            // Optional: log or show a different message
            return back()->with('status', __('Failed to send OTP, please try again later.'));
        }

        // Redirect to OTP page for UX
        return redirect()->route('otp.create')->with('status', __('If the account exists, an OTP has been sent.'));
    }
}
