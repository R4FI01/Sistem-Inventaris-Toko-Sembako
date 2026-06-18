<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\OTPMail;
use App\Helper\JWTToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class UserController extends Controller
{
    function LoginPage(): View
    {
        return view('pages.auth.login-page');
    }

    function RegistrationPage(): View
    {
        return view('pages.auth.registration-page');
    }

    function SendOtpPage(): View
    {
        return view('pages.auth.send-otp-page');
    }

    function VerifyOTPPage(): View
    {
        return view('pages.auth.verify-otp-page');
    }

    function ResetPasswordPage(): View
    {
        return view('pages.auth.reset-pass-page');
    }

    function ProfilePage(): View
    {
        return view('pages.dashboard.profile-page');
    }

    private function isPasswordHash($password): bool
    {
        if (!is_string($password) || $password === '') {
            return false;
        }

        return str_starts_with($password, '$2y$') ||
            str_starts_with($password, '$2a$') ||
            str_starts_with($password, '$2b$') ||
            str_starts_with($password, '$argon2i$') ||
            str_starts_with($password, '$argon2id$');
    }

    public function userRegistration(Request $request)
    {
        try {
            if (User::where('email', $request->email)->exists()) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Email sudah terdaftar'
                ], 200);
            }

            User::create([
                'first_name' => $request->firstName,
                'last_name' => $request->lastName,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'mobile' => $request->mobile,
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Registrasi akun berhasil'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Registrasi akun gagal'
            ], 200);
        }
    }

    public function userLogin(Request $request)
    {
        try {
            $email = $request->input('email');
            $inputPassword = (string) $request->input('password');

            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Email atau kata sandi salah'
                ], 200);
            }

            $storedPassword = (string) $user->password;
            $passwordValid = false;

            if ($this->isPasswordHash($storedPassword)) {
                if (Hash::check($inputPassword, $storedPassword)) {
                    $passwordValid = true;
                }
            } else {
                if (hash_equals($storedPassword, $inputPassword)) {
                    $passwordValid = true;

                    User::where('id', $user->id)->update([
                        'password' => Hash::make($inputPassword)
                    ]);

                    $user->password = Hash::make($inputPassword);
                }
            }

            if (!$passwordValid) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'Email atau kata sandi salah'
                ], 200);
            }

            $token = JWTToken::createToken($user->email, $user->id);

            return response()->json([
                'status' => 'success',
                'message' => 'Login berhasil'
            ], 200)->cookie('token', $token, 60 * 24 * 30);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Terjadi kesalahan saat login'
            ], 200);
        }
    }

    public function sendOTP(Request $request)
    {
        try {
            $email = $request->email;
            $otp = rand(1000, 9999);
            $count = User::where('email', $email)->count();

            if ($count == 1) {
                Mail::to($email)->send(new OTPMail($otp));

                User::where('email', $email)->update([
                    'otp' => $otp
                ]);

                return response()->json([
                    'status' => 'success',
                    'message' => 'OTP berhasil dikirim'
                ], 200);
            } else {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Email tidak ditemukan'
                ], 200);
            }
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'failed',
                'message' => 'Gagal mengirim OTP'
            ], 200);
        }
    }

    public function verifyOTP(Request $request)
    {
        $email = $request->email;
        $otp = $request->otp;

        $count = User::where([
            'email' => $email,
            'otp' => $otp
        ])->count();

        if ($count == 1) {
            User::where('email', $email)->update([
                'otp' => 0
            ]);

            $token = JWTToken::createTokenForSetPassword($email);

            return response()->json([
                'status' => 'success',
                'message' => 'OTP berhasil diverifikasi',
            ], 200)->cookie('token', $token, 5);
        } else {
            return response()->json([
                'status' => 'failed',
                'message' => 'OTP salah atau tidak valid'
            ], 200);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $email = $request->header('email');

            User::where('email', $email)->update([
                'password' => Hash::make($request->password)
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Password berhasil direset'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Password gagal direset'
            ], 200);
        }
    }

    public function userlogout(Request $request)
    {
        return redirect('/userLogin')->cookie('token', null, -1);
    }

    function UserProfile(Request $request)
    {
        $email = $request->header('email');

        $user = User::where('email', $email)->first();

        return response()->json([
            'status' => 'success',
            'message' => 'Request Successful',
            'data' => $user
        ], 200);
    }

    function UpdateProfile(Request $request)
    {
        try {
            $email = $request->header('email');

            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json([
                    'status' => 'fail',
                    'message' => 'User tidak ditemukan',
                ], 200);
            }

            $data = [
                'first_name' => $request->input('first_name'),
                'last_name' => $request->input('last_name'),
                'mobile' => $request->input('mobile'),
            ];

            $newPassword = $request->input('password');

            if (!empty($newPassword)) {
                $data['password'] = Hash::make($newPassword);
            } else {
                if (!$this->isPasswordHash($user->password)) {
                    $data['password'] = Hash::make($user->password);
                }
            }

            $user->update($data);

            return response()->json([
                'status' => 'success',
                'message' => 'Profil berhasil diperbarui',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'fail',
                'message' => 'Terjadi kesalahan saat memperbarui profil',
            ], 200);
        }
    }
}