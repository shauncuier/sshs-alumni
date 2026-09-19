<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Personal access token management for mobile clients and integrations.
 *
 * @see docs/10-api.md section 3 & 4
 */
class AuthController extends Controller
{
    /**
     * Issue a personal access token for valid credentials.
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = User::query()->where('email', strtolower($validated['email']))->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        if ($user->status !== UserStatus::Active) {
            return response()->json([
                'message' => 'Your account is suspended or inactive.',
            ], 403);
        }

        $device = $validated['device_name'] ?? $request->header('User-Agent', 'Mobile App');
        $device = substr((string) $device, 0, 100);

        // Abilities mapped from permissions, or wildcard for super admin
        $abilities = $user->hasRole('Super Admin')
            ? ['*']
            : $user->getAllPermissions()->pluck('name')->all();

        $token = $user->createToken($device, $abilities)->plainTextToken;

        $member = $user->member;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status->value,
                'avatar_url' => $user->avatar_path ? asset('storage/'.$user->avatar_path) : null,
            ],
            'member' => $member === null ? null : [
                'ulid' => $member->ulid,
                'full_name' => $member->full_name,
                'membership_no' => $member->membership_no,
                'status' => $member->status->value,
                'is_approved' => $member->isApproved(),
            ],
            'roles' => $user->getRoleNames()->all(),
            'permissions' => $user->getAllPermissions()->pluck('name')->all(),
        ]);
    }

    /**
     * Invalidate the token used for the current request.
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user !== null) {
            /** @var PersonalAccessToken|null $token */
            $token = $user->currentAccessToken();
            $token?->delete();
        }

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Details of the authenticated identity.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user === null, 401);

        $member = $user->member;

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status->value,
                'avatar_url' => $user->avatar_path ? asset('storage/'.$user->avatar_path) : null,
            ],
            'member' => $member === null ? null : [
                'ulid' => $member->ulid,
                'full_name' => $member->full_name,
                'membership_no' => $member->membership_no,
                'status' => $member->status->value,
                'is_approved' => $member->isApproved(),
                'ssc_year' => $member->ssc_year,
                'batch' => $member->batch?->name,
            ],
            'roles' => $user->getRoleNames()->all(),
            'permissions' => $user->getAllPermissions()->pluck('name')->all(),
        ]);
    }
}
