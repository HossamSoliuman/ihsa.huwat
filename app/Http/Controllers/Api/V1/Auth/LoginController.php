<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * دخول التطبيق: جوال وكلمة مرور، والردّ رمز Sanctum يُحمل في Authorization.
 *
 * كل جهاز رمزه: اسم الجهاز يُسجَّل مع الرمز، والخروج يُلغي رمز الجهاز الحالي
 * وحده فيبقى الهاتف الآخر داخلًا. رمز FCM إن أُرسل يُحفظ مع الدخول مباشرة.
 */
class LoginController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->merge(['phone' => User::normalizePhone($request->input('phone'))]);

        $data = $request->validate([
            'phone' => ['required', 'string', 'regex:/^05\d{8}$/'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
            'fcm_token' => ['nullable', 'string', 'max:500'],
        ], [
            'phone.regex' => 'رقم الجوال يجب أن يكون بصيغة 05XXXXXXXX.',
        ]);

        $user = User::with('appRole')->where('phone', $data['phone'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            // رسالة واحدة للحالتين: وجود الجوال من عدمه لا يُستدلّ عليه من الردّ.
            throw ValidationException::withMessages(['phone' => 'بيانات الدخول غير صحيحة.']);
        }

        if ($user->app_role_key === null) {
            throw ValidationException::withMessages(['phone' => 'ليس لهذا الحساب دور في التطبيق.']);
        }

        if (! $user->active) {
            throw ValidationException::withMessages(['phone' => 'هذا الحساب معطّل — راجع الإدارة.']);
        }

        $user->forceFill([
            'last_login_at' => now(),
            'fcm_token' => $data['fcm_token'] ?? $user->fcm_token,
        ])->save();

        $token = $user->createToken($data['device_name'] ?? 'mobile')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => new UserResource($user->load('owner')),
            ],
            'message' => 'تم تسجيل الدخول.',
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'تم تسجيل الخروج.']);
    }
}
