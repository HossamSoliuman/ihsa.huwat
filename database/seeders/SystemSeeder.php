<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\IntegrationSetting;
use App\Models\UiTranslation;
use App\Models\UserPermission;
use Illuminate\Database\Seeder;

class SystemSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['user_email' => 'admin@hawat.sa', 'role' => 'admin'],
            ['user_email' => 'dg@hawat.sa', 'role' => 'top_management'],
            ['user_email' => 'fisheries@hawat.sa', 'role' => 'fisheries_admin'],
            ['user_email' => 'east@hawat.sa', 'role' => 'region_manager', 'region' => 'المنطقة الشرقية'],
            ['user_email' => 'qatif@hawat.sa', 'role' => 'port_manager', 'region' => 'المنطقة الشرقية', 'governorate' => 'القطيف', 'port' => 'ميناء القطيف'],
        ];

        foreach ($permissions as $item) {
            UserPermission::updateOrCreate(['user_email' => $item['user_email']], $item);
        }

        $translations = [
            ['key' => 'nav.home', 'ar' => 'الرئيسية', 'en' => 'Home', 'context' => 'navigation'],
            ['key' => 'nav.production', 'ar' => 'الإنتاج السمكي', 'en' => 'Fish Production', 'context' => 'navigation'],
            ['key' => 'status.active', 'ar' => 'نشط', 'en' => 'Active', 'context' => 'status'],
            ['key' => 'status.approved', 'ar' => 'معتمدة', 'en' => 'Approved', 'context' => 'status'],
            ['key' => 'kpi.total_catch', 'ar' => 'إجمالي المصيد المعتمد', 'en' => 'Total Approved Catch', 'context' => 'kpi'],
        ];

        foreach ($translations as $item) {
            UiTranslation::updateOrCreate(['key' => $item['key']], $item);
        }

        $integrations = [
            ['provider' => 'powerbi', 'enabled' => false, 'settings' => ['workspace_id' => '', 'report_id' => '', 'embed_mode' => 'app_owns_data']],
            ['provider' => 'arcgis', 'enabled' => false, 'settings' => ['portal_url' => 'https://www.arcgis.com', 'webmap_id' => '', 'default_basemap' => 'oceans']],
            ['provider' => 'fabric', 'enabled' => false, 'settings' => ['lakehouse' => '', 'warehouse' => '', 'sql_endpoint' => '']],
            ['provider' => 'hawat_ai', 'enabled' => true, 'settings' => ['model' => 'gpt_5_mini', 'context_limit' => 12000, 'enforce_jurisdiction' => true]],
        ];

        foreach ($integrations as $item) {
            IntegrationSetting::updateOrCreate(['provider' => $item['provider']], $item);
        }

        $logs = [
            ['user_email' => 'admin@hawat.sa', 'role' => 'admin', 'action' => 'تهيئة النظام', 'entity' => 'System', 'record_label' => 'الإصدار الأول', 'details' => 'تشغيل الترحيلات والبيانات الأولية'],
            ['user_email' => 'fisheries@hawat.sa', 'role' => 'fisheries_admin', 'action' => 'إنشاء', 'entity' => 'FishingSeason', 'record_label' => 'موسم الروبيان 2026', 'details' => 'فتح موسم الروبيان بحصة 4500 طن'],
            ['user_email' => 'qatif@hawat.sa', 'role' => 'port_manager', 'action' => 'اعتماد', 'entity' => 'Trip', 'record_label' => 'TR-2026-0001', 'details' => 'اعتماد كمية 765 كجم بعد الإحصاء'],
        ];

        foreach ($logs as $item) {
            AuditLog::updateOrCreate(
                ['action' => $item['action'], 'record_label' => $item['record_label']],
                $item
            );
        }
    }
}