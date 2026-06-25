<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\BaseController;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\MfaVerifyRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\AuditService;
use App\Services\MfaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthController extends BaseController
{
    public function __construct(
        private readonly MfaService $mfaService,
        private readonly AuditService $auditService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only('email', 'password');
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return $this->error('Invalid credentials.', 401);
        }

        if (! $user->isActive()) {
            return $this->error('Account is disabled.', 403);
        }

        if ($user->mfa_enabled) {
            $this->mfaService->generateOtp($user);

            return $this->success([
                'mfa_required' => true,
                'user_id' => $user->id,
            ], 'MFA verification required.');
        }

        $token = auth('api')->login($user);

        $this->auditService->log($user->id, 'login', 'auth', null, ['email' => $user->email]);

        return $this->tokenResponse($token, $user);
    }

    public function verifyMfa(MfaVerifyRequest $request): JsonResponse
    {
        $user = User::findOrFail($request->user_id);

        if (! $this->mfaService->verifyOtp($user, $request->otp)) {
            return $this->error('Invalid or expired OTP.', 401);
        }

        $token = auth('api')->login($user);

        $this->auditService->log($user->id, 'mfa_verified', 'auth', null, ['email' => $user->email]);

        return $this->tokenResponse($token, $user);
    }

    public function logout(): JsonResponse
    {
        $user = auth('api')->user();
        if ($user) {
            $this->auditService->log($user->id, 'logout', 'auth', null, ['email' => $user->email]);
        }

        auth('api')->logout();

        return $this->success(null, 'Logged out successfully.');
    }

    public function refresh(): JsonResponse
    {
        $token = auth('api')->refresh();
        $user = auth('api')->user();

        return $this->tokenResponse($token, $user);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            return $this->error(__($status), 400);
        }

        return $this->success(null, 'Password reset link sent.');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
                $this->auditService->log($user->id, 'password_reset', 'auth', null, ['email' => $user->email]);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return $this->error(__($status), 400);
        }

        return $this->success(null, 'Password has been reset.');
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('roles.permissions');

        return $this->success($user);
    }

    private function tokenResponse(string $token, User $user): JsonResponse
    {
        $user->load('roles.permissions');

        return $this->success([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => $user,
        ], 'Authenticated successfully.');
    }
}
