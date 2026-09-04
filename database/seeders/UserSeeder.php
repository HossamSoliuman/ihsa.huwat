<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * حسابات الدخول إلى بوابة المعلومات.
 *
 * بريدُ كلِّ حساب هو نفسه مفتاح جدول الصلاحيات الذي يبذره SystemSeeder، فالدور
 * يُقرأ من هناك لا من جدول المستخدمين (انظر App\Models\User::getRoleAttribute).
 *
 * كلمة المرور واحدة لحسابات البذر وتُقرأ من SEED_PASSWORD — بيانات تهيئة لا
 * بيانات إنتاج: غيّرها في الخادم قبل التسليم.
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('SEED_PASSWORD', 'hawat@2026');

        $users = [
            ['email' => 'admin@hawat.sa', 'name' => 'مدير النظام'],
            ['email' => 'dg@hawat.sa', 'name' => 'وكيل الوزارة'],
            ['email' => 'fisheries@hawat.sa', 'name' => 'مدير إدارة المصايد'],
            ['email' => 'east@hawat.sa', 'name' => 'مدير المنطقة الشرقية'],
            ['email' => 'qatif@hawat.sa', 'name' => 'مدير ميناء القطيف'],
        ];

        foreach ($users as $user) {
            // البذر لا يدهس كلمة مرور غُيّرت بعد التسليم: تُكتب عند الإنشاء وحده.
            User::firstOrCreate(
                ['email' => $user['email']],
                ['name' => $user['name'], 'password' => $password],
            );
        }
    }
}
