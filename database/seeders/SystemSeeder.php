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
            ['user_email' => 'admin@hawat.gov.sa', 'full_name' => 'مدير النظام', 'role' => 'admin', 'scope_level' => 'المملكة', 'can_approve' => true, 'can_export' => true],
            ['user_email' => 'top@hawat.gov.sa', 'full_name' => 'الإدارة العليا', 'role' => 'top_management', 'scope_level' => 'المملكة', 'can_approve' => true, 'can_export' => true],
            ['user_email' => 'east.manager@hawat.gov.sa', 'full_name' => 'مدير المنطقة الشرقية', 'role' => 'region_manager', 'scope_level' => 'المنطقة', 'region' => 'الشرقية', 'can_approve' => true, 'can_export' => false],
            ['user_email' => 'qatif.port@hawat.gov.sa', 'full_name' => 'مدير ميناء القطيف', 'role' => 'port_manager', 'scope_level' => 'الميناء', 'region' => 'الشرقية', 'governorate' => 'القطيف', 'port' => 'ميناء القطيف', 'can_approve' => false, 'can_export' => false],
            ['user_email' => 'research@hawat.gov.sa', 'full_name' => 'الباحث العلمي', 'role' => 'researcher', 'scope_level' => 'المملكة', 'can_approve' => false, 'can_export' => true],
        ];

        foreach ($permissions as $permission) {
            UserPermission::updateOrCreate(['user_email' => $permission['user_email']], $permission + ['active' => true]);
        }

        $translations = [
            ['source_ar' => 'مركز إدارة النظام', 'target_en' => 'Administration Center', 'context' => 'Admin'],
            ['source_ar' => 'البيانات الجغرافية', 'target_en' => 'Geographic Data', 'context' => 'Admin Tabs'],
            ['source_ar' => 'الأسطول والثروة السمكية', 'target_en' => 'Fleet & Fisheries', 'context' => 'Admin Tabs'],
            ['source_ar' => 'مواسم الصيد', 'target_en' => 'Fishing Seasons', 'context' => 'Admin Tabs'],
            ['source_ar' => 'حوكمة وجودة البيانات', 'target_en' => 'Data Governance & Quality', 'context' => 'Admin Tabs'],
            ['source_ar' => 'سجل العمليات', 'target_en' => 'Audit Log', 'context' => 'Admin Tabs'],
            ['source_ar' => 'إجمالي المصيد', 'target_en' => 'Total Catch', 'context' => 'KPI'],
            ['source_ar' => 'القوارب النشطة', 'target_en' => 'Active Boats', 'context' => 'KPI'],
        ];

        foreach ($translations as $translation) {
            UiTranslation::updateOrCreate(['source_ar' => $translation['source_ar']], $translation + ['status' => 'مترجم']);
        }

        $integrations = [
            ['provider' => 'powerbi', 'enabled' => false, 'settings' => ['workspace_id' => null, 'report_id' => null, 'embed_mode' => 'Embed for your organization']],
            ['provider' => 'arcgis', 'enabled' => true, 'settings' => ['portal_url' => 'https://www.arcgis.com', 'default_basemap' => 'oceans', 'default_zoom' => 6]],
            ['provider' => 'fabric', 'enabled' => false, 'settings' => ['workspace_name' => 'HAWAT-Analytics', 'lakehouse_name' => 'FisheriesLakehouse', 'refresh_mode' => 'يومي']],
            ['provider' => 'hawat_ai', 'enabled' => true, 'settings' => ['model' => 'gpt_5_mini', 'max_context_records' => 500, 'daily_query_limit' => 200, 'enforce_jurisdiction' => true, 'log_queries' => true]],
        ];

        foreach ($integrations as $integration) {
            IntegrationSetting::updateOrCreate(['provider' => $integration['provider']], $integration);
        }

        $logs = [
            ['action' => 'إنشاء', 'entity' => 'Port', 'entity_id' => '7', 'user' => 'admin@hawat.gov.sa', 'user_role' => 'admin', 'details' => 'إضافة ميناء الجبيل إلى المرجع الجغرافي'],
            ['action' => 'تعديل', 'entity' => 'Species', 'entity_id' => '1', 'user' => 'research@hawat.gov.sa', 'user_role' => 'researcher', 'details' => 'تحديث حالة مخزون الهامور إلى ضغط صيد مرتفع'],
            ['action' => 'تعديل', 'entity' => 'Boat', 'entity_id' => '3', 'user' => 'east.manager@hawat.gov.sa', 'user_role' => 'region_manager', 'details' => 'إيقاف القارب ريح البحر لانتهاء الرخصة'],
            ['action' => 'إنشاء', 'entity' => 'SeasonLicense', 'entity_id' => '1', 'user' => 'admin@hawat.gov.sa', 'user_role' => 'admin', 'details' => 'إصدار رخصة موسم الروبيان SL-2026-0001'],
            ['action' => 'تصدير', 'entity' => 'MonthlyReport', 'entity_id' => '2026-07', 'user' => 'top@hawat.gov.sa', 'user_role' => 'top_management', 'details' => 'تصدير تقرير المصايد الشهري يوليو 2026'],
        ];

        foreach ($logs as $index => $log) {
            AuditLog::updateOrCreate(
                ['entity' => $log['entity'], 'entity_id' => $log['entity_id'], 'action' => $log['action']],
                $log + ['timestamp' => now()->subHours($index + 1)]
            );
        }
    }
}