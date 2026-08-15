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
            ['asset_key' => 'hawat.regions', 'name_ar' => 'المناطق', 'name_en' => 'Regions', 'asset_type' => 'Master Data', 'domain' => 'Geography', 'system' => 'HAWAT', 'source_of_truth' => true, 'owner_email' => 'data.owner@hawat.gov.sa', 'steward_email' => 'geo.steward@hawat.gov.sa', 'sensitivity' => 'داخلي', 'refresh_mode' => 'يومي', 'purpose' => 'المرجع الجغرافي الأساسي للمناطق الساحلية.'],
            ['asset_key' => 'hawat.ports', 'name_ar' => 'الموانئ', 'name_en' => 'Ports', 'asset_type' => 'Master Data', 'domain' => 'Geography', 'system' => 'HAWAT', 'source_of_truth' => true, 'owner_email' => 'data.owner@hawat.gov.sa', 'steward_email' => 'geo.steward@hawat.gov.sa', 'sensitivity' => 'داخلي', 'refresh_mode' => 'كل ساعة', 'purpose' => 'مرجع الموانئ والمراسي وإحداثياتها.'],
            ['asset_key' => 'hawat.species', 'name_ar' => 'الأنواع السمكية', 'name_en' => 'Species Directory', 'asset_type' => 'Master Data', 'domain' => 'Species', 'system' => 'HAWAT', 'source_of_truth' => true, 'owner_email' => 'research@hawat.gov.sa', 'steward_email' => 'species.steward@hawat.gov.sa', 'sensitivity' => 'عام', 'refresh_mode' => 'يومي', 'purpose' => 'دليل الأنواع المدقق مع الأسماء العلمية.'],
            ['asset_key' => 'hawat.catch_records', 'name_ar' => 'سجلات المصيد', 'name_en' => 'Catch Records', 'asset_type' => 'Operational Entity', 'domain' => 'Catch', 'system' => 'HAWAT', 'source_of_truth' => true, 'owner_email' => 'ops@hawat.gov.sa', 'steward_email' => 'catch.steward@hawat.gov.sa', 'sensitivity' => 'مقيد', 'refresh_mode' => 'لحظي', 'purpose' => 'المصيد المعتمد بعد الإحصاء الميداني.'],
            ['asset_key' => 'fabric.lakehouse.fisheries', 'name_ar' => 'بحيرة بيانات المصايد', 'name_en' => 'Fisheries Lakehouse', 'asset_type' => 'Lakehouse', 'domain' => 'Analytics', 'system' => 'Microsoft Fabric', 'owner_email' => 'bi@hawat.gov.sa', 'steward_email' => 'bi@hawat.gov.sa', 'sensitivity' => 'مقيد', 'refresh_mode' => 'يومي', 'purpose' => 'تجميع بيانات المصايد للتحليل المتقدم.'],
            ['asset_key' => 'powerbi.exec_dashboard', 'name_ar' => 'لوحة المؤشرات التنفيذية', 'name_en' => 'Executive KPI Dashboard', 'asset_type' => 'Report', 'domain' => 'Analytics', 'system' => 'Power BI', 'owner_email' => 'bi@hawat.gov.sa', 'steward_email' => 'bi@hawat.gov.sa', 'sensitivity' => 'مقيد', 'refresh_mode' => 'يومي', 'purpose' => 'عرض المؤشرات الوطنية للإدارة العليا.'],
            ['asset_key' => 'arcgis.marine_layers', 'name_ar' => 'طبقات الخرائط البحرية', 'name_en' => 'Marine GIS Layers', 'asset_type' => 'GIS Layer', 'domain' => 'GIS', 'system' => 'ArcGIS', 'owner_email' => 'gis@hawat.gov.sa', 'steward_email' => 'gis@hawat.gov.sa', 'sensitivity' => 'داخلي', 'refresh_mode' => 'يومي', 'purpose' => 'مواقع الصيد ومناطق الحظر وكثافة المصيد.'],
            ['asset_key' => 'fao.asfis', 'name_ar' => 'قائمة ASFIS', 'name_en' => 'FAO ASFIS List', 'asset_type' => 'External Standard', 'domain' => 'FAO', 'system' => 'FAO', 'sensitivity' => 'عام', 'refresh_mode' => 'شهري', 'purpose' => 'مرجع الأسماء العلمية وأكواد الأنواع الدولية.'],
        ];

        foreach ($assets as $asset) {
            DataCatalogAsset::updateOrCreate(['asset_key' => $asset['asset_key']], $asset);
        }

        $edges = [
            ['source_asset_key' => 'hawat.catch_records', 'target_asset_key' => 'fabric.lakehouse.fisheries', 'transformation' => 'Direct Copy', 'refresh_mode' => 'يومي'],
            ['source_asset_key' => 'hawat.ports', 'target_asset_key' => 'fabric.lakehouse.fisheries', 'transformation' => 'Direct Copy', 'refresh_mode' => 'يومي'],
            ['source_asset_key' => 'fabric.lakehouse.fisheries', 'target_asset_key' => 'powerbi.exec_dashboard', 'transformation' => 'Aggregation', 'refresh_mode' => 'يومي'],
            ['source_asset_key' => 'hawat.species', 'target_asset_key' => 'fao.asfis', 'transformation' => 'Enrichment', 'refresh_mode' => 'شهري'],
            ['source_asset_key' => 'hawat.ports', 'target_asset_key' => 'arcgis.marine_layers', 'transformation' => 'Direct Copy', 'refresh_mode' => 'يومي'],
        ];

        foreach ($edges as $edge) {
            DataLineageEdge::updateOrCreate(
                ['source_asset_key' => $edge['source_asset_key'], 'target_asset_key' => $edge['target_asset_key']],
                $edge + ['active' => true]
            );
        }

        $terms = [
            ['term_ar' => 'المصيد المعتمد', 'term_en' => 'Approved Catch', 'domain' => 'Catch', 'definition_ar' => 'كمية المصيد بعد اعتمادها من موظف الإحصاء ومراجعة الفروقات.', 'definition_en' => 'Catch quantity approved by the statistics officer after discrepancy review.', 'owner_email' => 'ops@hawat.gov.sa', 'related_entities' => 'CatchRecord, Trip'],
            ['term_ar' => 'الرحلة', 'term_en' => 'Fishing Trip', 'domain' => 'Trips', 'definition_ar' => 'خروج القارب من الميناء وعودته مع تسجيل المصيد.', 'definition_en' => 'A boat departure and return cycle with recorded catch.', 'owner_email' => 'ops@hawat.gov.sa', 'related_entities' => 'Trip, Boat'],
            ['term_ar' => 'ضغط الصيد', 'term_en' => 'Fishing Pressure', 'domain' => 'Species', 'definition_ar' => 'مستوى الاستغلال لموقع أو نوع مقارنة بالطاقة المستدامة.', 'definition_en' => 'Exploitation level of a site or species relative to sustainable capacity.', 'owner_email' => 'research@hawat.gov.sa', 'related_entities' => 'FishingSite, Species'],
            ['term_ar' => 'الحصة الموسمية', 'term_en' => 'Seasonal Quota', 'domain' => 'Licenses', 'definition_ar' => 'الكمية القصوى المسموح صيدها خلال الموسم للرخصة الواحدة.', 'definition_en' => 'Maximum catch allowed per license during a season.', 'owner_email' => 'licensing@hawat.gov.sa', 'related_entities' => 'SeasonLicense, FishingSeason'],
        ];

        foreach ($terms as $term) {
            BusinessGlossaryTerm::updateOrCreate(['term_ar' => $term['term_ar']], $term + ['status' => 'معتمد']);
        }

        $kpis = [
            ['kpi_key' => 'total_catch_tons', 'name_ar' => 'إجمالي المصيد', 'name_en' => 'Total Catch', 'domain' => 'Production', 'unit' => 'طن', 'formula' => 'SUM(CatchRecord.approved_kg) / 1000', 'source_entities' => 'CatchRecord', 'owner_email' => 'bi@hawat.gov.sa', 'refresh_mode' => 'يومي', 'certification_status' => 'معتمد'],
            ['kpi_key' => 'cpue', 'name_ar' => 'المصيد لوحدة الجهد', 'name_en' => 'CPUE', 'domain' => 'Sustainability', 'unit' => 'كجم', 'formula' => 'SUM(approved_kg) / COUNT(DISTINCT trip_id)', 'source_entities' => 'CatchRecord, Trip', 'owner_email' => 'research@hawat.gov.sa', 'refresh_mode' => 'يومي', 'certification_status' => 'معتمد'],
            ['kpi_key' => 'active_fleet_ratio', 'name_ar' => 'نسبة الأسطول النشط', 'name_en' => 'Active Fleet Ratio', 'domain' => 'Fleet', 'unit' => 'نسبة %', 'formula' => 'COUNT(Boat WHERE status = نشط) / COUNT(Boat)', 'source_entities' => 'Boat', 'owner_email' => 'ops@hawat.gov.sa', 'refresh_mode' => 'يومي', 'certification_status' => 'معتمد'],
            ['kpi_key' => 'compliance_rate', 'name_ar' => 'معدل الامتثال', 'name_en' => 'Compliance Rate', 'domain' => 'Compliance', 'unit' => 'نسبة %', 'formula' => '1 - (COUNT(Violation) / COUNT(Trip))', 'source_entities' => 'Violation, Trip', 'owner_email' => 'supervision@hawat.gov.sa', 'refresh_mode' => 'يومي', 'certification_status' => 'مسودة'],
            ['kpi_key' => 'avg_auction_price', 'name_ar' => 'متوسط سعر المزاد', 'name_en' => 'Average Auction Price', 'domain' => 'Markets', 'unit' => 'ريال', 'formula' => 'AVG(MarketAuction.avg_price)', 'source_entities' => 'MarketAuction', 'owner_email' => 'markets@hawat.gov.sa', 'refresh_mode' => 'يومي', 'certification_status' => 'معتمد'],
        ];

        foreach ($kpis as $kpi) {
            KpiRegistry::updateOrCreate(['kpi_key' => $kpi['kpi_key']], $kpi);
        }

        $mappings = [
            ['local_entity_type' => 'Species', 'local_id' => '1001', 'local_name_ar' => 'هامور', 'local_name_en' => 'Orange-spotted grouper', 'fao_system' => 'ASFIS', 'fao_code' => 'EEC', 'fao_name' => 'Orange-spotted grouper', 'scientific_name' => 'Epinephelus coioides', 'verification_status' => 'محقق'],
            ['local_entity_type' => 'Species', 'local_id' => '1003', 'local_name_ar' => 'كنعد', 'local_name_en' => 'Narrow-barred Spanish mackerel', 'fao_system' => 'ASFIS', 'fao_code' => 'COM', 'fao_name' => 'Narrow-barred Spanish mackerel', 'scientific_name' => 'Scomberomorus commerson', 'verification_status' => 'محقق'],
            ['local_entity_type' => 'Species', 'local_id' => '2001', 'local_name_ar' => 'روبيان أخضر', 'local_name_en' => 'Green tiger prawn', 'fao_system' => 'ISSCAAP', 'fao_code' => '45', 'fao_name' => 'Shrimps, prawns', 'scientific_name' => 'Penaeus semisulcatus', 'verification_status' => 'مقترح'],
            ['local_entity_type' => 'FishingGear', 'local_id' => 'GN', 'local_name_ar' => 'شبك خيشوم', 'local_name_en' => 'Gillnet', 'fao_system' => 'ISSCFG', 'fao_code' => 'GNS', 'fao_name' => 'Set gillnets (anchored)', 'verification_status' => 'محقق'],
            ['local_entity_type' => 'FishingSite', 'local_id' => 'ابو علي', 'local_name_ar' => 'أبو علي', 'local_name_en' => 'Abu Ali', 'fao_system' => 'FAO_MAJOR_FISHING_AREA', 'fao_code' => '51', 'fao_name' => 'Indian Ocean, Western', 'verification_status' => 'يحتاج مراجعة'],
        ];

        foreach ($mappings as $mapping) {
            FaoStandardMapping::updateOrCreate(
                ['local_entity_type' => $mapping['local_entity_type'], 'local_id' => $mapping['local_id'], 'fao_system' => $mapping['fao_system']],
                $mapping
            );
        }

        $issues = [
            ['fingerprint' => 'dup-port-name-001', 'run_id' => 'DQ-2026-08-14', 'category' => 'duplicate', 'severity' => 'warning', 'entity_name' => 'Port', 'record_label' => 'ميناء القطيف', 'field_name' => 'name', 'issue_message' => 'يوجد اسم ميناء مشابه بفروق في المسافات البيضاء.', 'status' => 'مفتوحة', 'priority' => 'متوسطة', 'assigned_to' => 'geo.steward@hawat.gov.sa', 'due_date' => '2026-08-20'],
            ['fingerprint' => 'rel-boat-port-014', 'run_id' => 'DQ-2026-08-14', 'category' => 'relationship', 'severity' => 'critical', 'entity_name' => 'Boat', 'record_label' => 'ريح البحر', 'field_name' => 'port', 'issue_message' => 'القارب مرتبط بميناء غير مطابق لمحافظته المسجلة.', 'status' => 'قيد المعالجة', 'priority' => 'عالية', 'assigned_to' => 'ops@hawat.gov.sa', 'due_date' => '2026-08-17'],
            ['fingerprint' => 'comp-species-sci-022', 'run_id' => 'DQ-2026-08-14', 'category' => 'completeness', 'severity' => 'warning', 'entity_name' => 'Species', 'record_label' => 'شربة', 'field_name' => 'corrected_name_sci', 'issue_message' => 'الاسم العلمي المصحّح غير مكتمل لهذا النوع.', 'status' => 'مفتوحة', 'priority' => 'منخفضة', 'assigned_to' => 'species.steward@hawat.gov.sa', 'due_date' => '2026-08-25'],
            ['fingerprint' => 'geo-site-coords-031', 'run_id' => 'DQ-2026-08-14', 'category' => 'geography', 'severity' => 'critical', 'entity_name' => 'FishingSite', 'record_label' => 'مصب عتود', 'field_name' => 'lat', 'issue_message' => 'الإحداثيات تقع خارج النطاق البحري للمنطقة المسجلة.', 'status' => 'بانتظار التحقق', 'priority' => 'حرجة', 'assigned_to' => 'gis@hawat.gov.sa', 'due_date' => '2026-08-16'],
        ];

        foreach ($issues as $issue) {
            DataQualityIssue::updateOrCreate(['fingerprint' => $issue['fingerprint']], $issue);
        }
    }
}