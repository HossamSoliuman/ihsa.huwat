<?php

namespace App\Services\Dalal;

use App\Models\DalalProfile;
use App\Models\DalalWorker;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * إعدادات الدلال: ملفه التجاري (الموقع والدكة والسجل التجاري والضريبي)،
 * ومعلومات الشركة وشعارها الذي يُطبع على الفواتير، وعمالة الدكة. الاسم
 * والبريد والصورة وكلمة المرور في الملف الشخصي المشترك بين الأدوار.
 */
class DalalProfileService
{
    public function update(User $dalal, array $data): DalalProfile
    {
        $profile = DalalProfile::forUser($dalal);
        $profile->update($data);

        return $profile;
    }

    public function storeLogo(User $dalal, UploadedFile $file): DalalProfile
    {
        $profile = DalalProfile::forUser($dalal);

        if ($profile->logo_path) {
            Storage::disk('public')->delete($profile->logo_path);
        }

        $profile->update(['logo_path' => $file->store('dalal-logos', 'public')]);

        return $profile;
    }

    public function removeLogo(User $dalal): DalalProfile
    {
        $profile = DalalProfile::forUser($dalal);

        if ($profile->logo_path) {
            Storage::disk('public')->delete($profile->logo_path);
            $profile->update(['logo_path' => null]);
        }

        return $profile;
    }

    public function addWorker(User $dalal, array $data): DalalWorker
    {
        return DalalWorker::create($data + ['dalal_id' => $dalal->id]);
    }

    /**
     * يستبدل قائمة العمالة كلها — نموذج التطبيق يرسلها سطورًا دفعة واحدة.
     *
     * @param  array<int, array{dalal_worker_type_id:int, nationality?:string|null, count:int, notes?:string|null}>  $rows
     */
    public function syncWorkers(User $dalal, array $rows): void
    {
        DalalWorker::forDalal($dalal)->delete();

        foreach ($rows as $row) {
            $this->addWorker($dalal, $row);
        }
    }
}
