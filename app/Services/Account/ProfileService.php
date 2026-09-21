<?php

namespace App\Services\Account;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * ملف الحساب: البيانات وكلمة المرور والصورة — يستدعيه الويب والتطبيق.
 */
class ProfileService
{
    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    /**
     * تغيير كلمة المرور يُخرج بقية الأجهزة؛ الجهاز الحالي (رمز API أو جلسة
     * الويب) يبقى داخلًا.
     */
    public function changePassword(User $user, string $password, ?int $keepTokenId = null): void
    {
        $user->update(['password' => $password]);

        $user->tokens()->when($keepTokenId !== null, fn ($q) => $q->whereKeyNot($keepTokenId))->delete();
    }

    public function storeAvatar(User $user, UploadedFile $file): User
    {
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->update(['avatar_path' => $file->store('avatars', 'public')]);

        return $user;
    }

    public function removeAvatar(User $user): User
    {
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $user->update(['avatar_path' => null]);
        }

        return $user;
    }
}
