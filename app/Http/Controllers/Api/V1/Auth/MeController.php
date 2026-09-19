<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * حساب المستخدم الداخل: عرضه، وتعديل بياناته، وتغيير كلمة مروره، ورمز إشعاراته.
 */
class MeController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user()->load(['appRole', 'owner']));
    }

    public function update(Request $request): UserResource
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user)],
            'locale' => ['sometimes', 'required', Rule::in(['ar', 'en'])],
        ]);

        $user->update($data);

        return new UserResource($user->load(['appRole', 'owner']));
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => 'كلمة المرور الحالية غير صحيحة.']);
        }

        $request->user()->update(['password' => $data['password']]);

        // تغيير كلمة المرور يُخرج بقية الأجهزة؛ الجهاز الحالي يبقى داخلًا.
        $request->user()->tokens()->whereKeyNot($request->user()->currentAccessToken()->id)->delete();

        return response()->json(['message' => 'تم تغيير كلمة المرور.']);
    }

    public function updateFcmToken(Request $request): JsonResponse
    {
        $data = $request->validate(['fcm_token' => ['required', 'string', 'max:500']]);

        $request->user()->update(['fcm_token' => $data['fcm_token']]);

        return response()->json(['message' => 'تم حفظ رمز الإشعارات.']);
    }
}
