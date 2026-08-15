<?php

namespace Database\Seeders;

use App\Models\BusinessGlossaryTerm;
use App\Models\DataCatalogAsset;
use App\Models\DataLineageEdge;
use App\Models\DataQualityIssue;
use App\Models\FaoStandardMapping;
use App\Models\KpiRegistry;
use Illuminate\Database\Seeder;

class GovernanceSeeder extends Seeder
{
    public function run(): void
    {
        $assets = [
            ['asset_key' => 'entity.trip', 'name_ar' => 'رحلات الصيد', 'name_en' => 'Trips', 'asset_type' => 'Operational Entity', 'domain' => 'Trips', 'system' => 'HAWAT', 'source_of_truth' => true, 'sensitivity' => 'داخلي'],
            ['asset_key' => 'entity.catch_record', 'name_ar' => 'سجلات المصيد', 'name_en' => 'Catch Records', 'asset_type' => 'Operational Entity', 'domain' => 'Catch', 'system' => 'HAWAT', 'source_of_truth' => true, 'sensitivity' => 'داخلي'],
            ['asset_key' => 'master.species', 'name_ar' => 'دليل الأنواع', 'name_en' => 'Species Directory', 'asset_type' => 'Master Data', 'domain' => 'Species', 'system' => 'HAWAT', 'source_of_truth' => true, 'sensitivity' => 'عام'],
            ['asset_key' => 'feed.powerbi_production', 'name_ar' => 'تغذية إنتاج Power BI', 'name_en' => 'Power BI Production Feed', 'asset_type' => 'Analytics Feed', 'domain' => 'Analytics', 'system' => 'Power BI', 'sensitivity' => 'داخلي'],
            ['asset_key' => 'report.executive_dashboard', 'name_ar' => 'اللوحة التنفيذية', 'name_en' => 'Executive Dashboard', 'asset_type' => 'Report', 'domain' => 'Analytics', 'system' => 'HAWAT', 'sensitivity' => 'مقيد'],
        ];

        foreach ($assets as $item) {
            DataCatalogAsset::updateOrCreate(['asset_key' => $item['asset_key']], $item);
        }

        $edges = [
            ['source_asset' => 'entity.trip', 'target_asset' => 'entity.catch_record', 'transform' => 'ربط الرحلة بسجلات الإحصاء'],
            ['source_asset' => 'entity.catch_record', 'target_asset' => 'feed.powerbi_production', 'transform' => 'تجميع شهري حسب النوع والميناء'],
            ['source_asset' => 'feed.powerbi_production', 'target_asset' => 'report.executive_dashboard', 'transform' => 'مؤشرات معتمدة'],
        ];

        foreach ($edges as $item) {
            DataLineageEdge::updateOrCreate(
                ['source_asset' => $item['source_asset'], 'target_asset' => $item['target_asset']],
                $item
            );
        }

        $terms = [
            ['term_ar' => 'المصيد المعتمد', 'term_en' => 'Approved Catch', 'definition' => 'كمية المصيد الموثقة من موظف الإحصاء والمعتمدة رسميًا بعد المطابقة', 'domain' => 'Catch'],
            ['term_ar' => 'فرق الإحصاء', 'term_en' => 'Statistics Discrepancy', 'definition' => 'الفرق بين إدخال الكابتن والوزن الفعلي عند التفريغ', 'domain' => 'Trips'],
            ['term_ar' => 'الصيد العرضي', 'term_en' => 'Bycatch', 'definition' => 'الكائنات غير المستهدفة التي تُصطاد أثناء رحلة الصيد', 'domain' => 'Catch'],
            ['term_ar' => 'الحصة الموسمية', 'term_en' => 'Seasonal Quota', 'definition' => 'الحد الأقصى المسموح باصطياده من نوع معين خلال موسم محدد', 'domain' => 'Licenses'],
        ];

        foreach ($terms as $item) {
            BusinessGlossaryTerm::updateOrCreate(['term_ar' => $item['term_ar']], $item);
        }

        $kpis = [
            ['kpi_key' => 'total_approved_catch', 'name_ar' => 'إجمالي المصيد المعتمد', 'formula' => 'SUM(trips.approved_kg) / 1000', 'owner' => 'إدارة الإحصاء'],
            ['kpi_key' => 'avg_statistics_discrepancy', 'name_ar' => 'متوسط فرق الإحصاء', 'formula' => 'AVG(ABS(diff_kg) / actual_weight_kg)', 'owner' => 'إدارة الإحصاء'],
            ['kpi_key' => 'traceability_completeness', 'name_ar' => 'اكتمال التتبع', 'formula' => 'COUNT(approved) / COUNT(trips)', 'owner' => 'حوكمة البيانات'],
            ['kpi_key' => 'avg_fish_price', 'name_ar' => 'متوسط سعر السمك', 'formula' => 'AVG(market_auctions.avg_price_per_kg)', 'owner' => 'إدارة الأسواق'],
            ['kpi_key' => 'violations_count', 'name_ar' => 'المخالفات المسجلة', 'formula' => 'COUNT(violations)', 'owner' => 'الرقابة والامتثال'],
        ];

        foreach ($kpis as $item) {
            KpiRegistry::updateOrCreate(['kpi_key' => $item['kpi_key']], $item);
        }

        $mappings = [
            ['local_entity_type' => 'Species', 'local_name_ar' => 'الهامور', 'fao_system' => 'ASFIS', 'fao_code' => 'EPCO', 'fao_name' => 'Orange-spotted grouper', 'verification_status' => 'محقق'],
            ['local_entity_type' => 'Species', 'local_name_ar' => 'الكنعد', 'fao_system' => 'ASFIS', 'fao_code' => 'COM', 'fao_name' => 'Narrow-barred Spanish mackerel', 'verification_status' => 'محقق'],
            ['local_entity_type' => 'FishingGear', 'local_name_ar' => 'شبك خيشوم', 'fao_system' => 'ISSCFG', 'fao_code' => '07.1', 'fao_name' => 'Set gillnets', 'verification_status' => 'مقترح'],
            ['local_entity_type' => 'FishingSite', 'local_name_ar' => 'الخليج العربي', 'fao_system' => 'FAO_MAJOR_FISHING_AREA', 'fao_code' => '51', 'fao_name' => 'Indian Ocean, Western', 'verification_status' => 'محقق'],
        ];

        foreach ($mappings as $item) {
            FaoStandardMapping::updateOrCreate(
                ['local_entity_type' => $item['local_entity_type'], 'local_name_ar' => $item['local_name_ar'], 'fao_system' => $item['fao_system']],
                $item
            );
        }

        $issues = [
            ['category' => 'completeness', 'severity' => 'warning', 'entity_name' => 'Boat', 'record_label' => 'شراع تبوك', 'issue_message' => 'حقل طول القارب مفقود في سجل التسجيل', 'priority' => 'متوسطة', 'assigned_to' => 'مسؤول الأسطول'],
            ['category' => 'relationship', 'severity' => 'critical', 'entity_name' => 'Trip', 'record_label' => 'TR-2026-0009', 'issue_message' => 'رحلة بلا موظف إحصاء رغم حالة تحت الإحصاء', 'priority' => 'عالية', 'assigned_to' => 'إدارة الإحصاء'],
            ['category' => 'duplicate', 'severity' => 'warning', 'entity_name' => 'Fisher', 'record_label' => 'F-503', 'issue_message' => 'اشتباه تكرار رقم رخصة صياد', 'priority' => 'متوسطة', 'assigned_to' => 'حوكمة البيانات'],
        ];

        foreach ($issues as $item) {
            DataQualityIssue::updateOrCreate(
                ['entity_name' => $item['entity_name'], 'issue_message' => $item['issue_message']],
                $item
            );
        }
    }
}