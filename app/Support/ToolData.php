<?php

namespace App\Support;

use App\Models\Boat;
use App\Models\Fisher;
use App\Models\FishingSeason;
use App\Models\FishingSite;
use App\Models\Governorate;
use App\Models\Market;
use App\Models\MarketAuction;
use App\Models\Port;
use App\Models\Region;
use App\Models\SeasonLicense;
use App\Models\Species;

class ToolData
{
    public function for(string $tab): array
    {
        return match ($tab) {
            'stats' => $this->productionStats(),
            'import' => $this->importTargets(),
            'powerbi-blueprint' => $this->blueprint(),
            'powerbi-feed' => $this->feedTables(),
            default => [],
        };
    }

    private function productionStats(): array
    {
        return [
            'cards' => [
                ['label' => 'إجمالي المصيد', 'value' => Port::sum('total_catch_tons'), 'unit' => 'طن'],
                ['label' => 'الرحلات الشهرية', 'value' => Port::sum('monthly_trips'), 'unit' => 'رحلة'],
                ['label' => 'القوارب النشطة', 'value' => Boat::where('status', 'نشط')->count(), 'unit' => 'قارب'],
                ['label' => 'الصيادون النشطون', 'value' => Fisher::where('status', 'نشط')->count(), 'unit' => 'صياد'],
                ['label' => 'الأنواع المسجلة', 'value' => Species::count(), 'unit' => 'نوع'],
                ['label' => 'مواقع الصيد', 'value' => FishingSite::count(), 'unit' => 'موقع'],
                ['label' => 'المواسم المفتوحة', 'value' => FishingSeason::where('status', 'مفتوح')->count(), 'unit' => 'موسم'],
                ['label' => 'الرخص السارية', 'value' => SeasonLicense::where('status', 'سارية')->count(), 'unit' => 'رخصة'],
            ],
            'regions' => Region::query()
                ->orderByDesc('total_catch_tons')
                ->get(['name', 'ports_count', 'total_catch_tons', 'active_boats', 'active_fishers']),
            'ports' => Port::query()
                ->with('governorate.region')
                ->orderByDesc('total_catch_tons')
                ->limit(10)
                ->get(['id', 'governorate_id', 'name', 'boats_count', 'monthly_trips', 'total_catch_tons']),
        ];
    }

    private function importTargets(): array
    {
        return [
            'targets' => [
                ['label' => 'المناطق', 'count' => Region::count(), 'columns' => 'name, code, coast_length_km, ports_count'],
                ['label' => 'المحافظات', 'count' => Governorate::count(), 'columns' => 'name, code, region_id, coastal, ports_count'],
                ['label' => 'الموانئ', 'count' => Port::count(), 'columns' => 'name, code, governorate_id, lat, lng, status'],
                ['label' => 'مواقع الصيد', 'count' => FishingSite::count(), 'columns' => 'name, port_id, site_type, depth_m, pressure_level'],
                ['label' => 'الأنواع السمكية', 'count' => Species::count(), 'columns' => 'code, name_ar, name_sci, name_en, category'],
                ['label' => 'القوارب', 'count' => Boat::count(), 'columns' => 'name, boat_number, port_id, captain, license_number'],
                ['label' => 'الصيادون', 'count' => Fisher::count(), 'columns' => 'name, national_id, port_id, boat_id, role'],
                ['label' => 'الأسواق', 'count' => Market::count(), 'columns' => 'name, region, governorate, market_type'],
                ['label' => 'المزادات', 'count' => MarketAuction::count(), 'columns' => 'market_id, auction_date, species_id, quantity_sold_kg, avg_price_per_kg'],
            ],
        ];
    }

    private function blueprint(): array
    {
        return [
            'model' => [
                ['table' => 'DimRegion', 'type' => 'Dimension', 'source' => 'regions', 'keys' => 'region_key'],
                ['table' => 'DimGovernorate', 'type' => 'Dimension', 'source' => 'governorates', 'keys' => 'governorate_key, region_key'],
                ['table' => 'DimPort', 'type' => 'Dimension', 'source' => 'ports', 'keys' => 'port_key, governorate_key'],
                ['table' => 'DimSpecies', 'type' => 'Dimension', 'source' => 'species', 'keys' => 'species_key'],
                ['table' => 'DimBoat', 'type' => 'Dimension', 'source' => 'boats', 'keys' => 'boat_key, port_key'],
                ['table' => 'DimDate', 'type' => 'Dimension', 'source' => 'calendar', 'keys' => 'date_key'],
                ['table' => 'FactCatch', 'type' => 'Fact', 'source' => 'catch_records', 'keys' => 'date_key, port_key, species_key, boat_key'],
                ['table' => 'FactTrip', 'type' => 'Fact', 'source' => 'trips', 'keys' => 'date_key, port_key, boat_key'],
                ['table' => 'FactAuction', 'type' => 'Fact', 'source' => 'market_auctions', 'keys' => 'date_key, market_key, species_key'],
            ],
            'measures' => [
                ['name' => 'Total Catch (Tons)', 'expression' => 'DIVIDE(SUM(FactCatch[approved_kg]), 1000)'],
                ['name' => 'Trips Count', 'expression' => 'DISTINCTCOUNT(FactTrip[trip_id])'],
                ['name' => 'CPUE', 'expression' => 'DIVIDE([Total Catch (Tons)] * 1000, [Trips Count])'],
                ['name' => 'Catch YoY %', 'expression' => 'DIVIDE([Total Catch (Tons)] - [Total Catch LY], [Total Catch LY])'],
                ['name' => 'Avg Auction Price', 'expression' => 'AVERAGE(FactAuction[avg_price])'],
            ],
        ];
    }

    private function feedTables(): array
    {
        return [
            'tables' => [
                ['name' => 'regions', 'rows' => Region::count(), 'refresh' => 'يومي'],
                ['name' => 'governorates', 'rows' => Governorate::count(), 'refresh' => 'يومي'],
                ['name' => 'ports', 'rows' => Port::count(), 'refresh' => 'كل ساعة'],
                ['name' => 'fishing_sites', 'rows' => FishingSite::count(), 'refresh' => 'يومي'],
                ['name' => 'species', 'rows' => Species::count(), 'refresh' => 'يومي'],
                ['name' => 'boats', 'rows' => Boat::count(), 'refresh' => 'كل ساعة'],
                ['name' => 'fishers', 'rows' => Fisher::count(), 'refresh' => 'يومي'],
                ['name' => 'fishing_seasons', 'rows' => FishingSeason::count(), 'refresh' => 'يومي'],
                ['name' => 'season_licenses', 'rows' => SeasonLicense::count(), 'refresh' => 'يومي'],
                ['name' => 'market_auctions', 'rows' => MarketAuction::count(), 'refresh' => 'كل ساعة'],
            ],
        ];
    }
}