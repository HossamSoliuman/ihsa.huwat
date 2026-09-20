<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\LookupResource;
use App\Http\Resources\Api\PortResource;
use App\Http\Resources\Api\SpeciesResource;
use App\Models\BoatCategory;
use App\Models\BoatType;
use App\Models\CustomerType;
use App\Models\FisherRole;
use App\Models\GearType;
use App\Models\IdType;
use App\Models\JobTitle;
use App\Models\MaintenanceType;
use App\Models\PaymentMethod;
use App\Models\PaymentStatus;
use App\Models\Port;
use App\Models\Species;
use App\Models\TripType;
use Illuminate\Http\JsonResponse;

/**
 * القوائم المرجعية كلها في ردّ واحد — التطبيق يحمّلها مرة عند الدخول
 * ويملأ منها قوائم الاختيار.
 */
class LookupController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => [
            'species' => SpeciesResource::collection(Species::where('directory_status', 'نشط')->orderBy('name_ar')->get()),
            'ports' => PortResource::collection(Port::with('governorate.region')->orderBy('name')->get()),
            'gear_types' => GearType::where('status', 'نشط')->orderBy('name')->get(['id', 'name']),
            'boat_categories' => LookupResource::collection(BoatCategory::options()),
            'boat_types' => LookupResource::collection(BoatType::options()),
            'maintenance_types' => LookupResource::collection(MaintenanceType::options()),
            'trip_types' => LookupResource::collection(TripType::options()),
            'fisher_roles' => LookupResource::collection(FisherRole::options()),
            'id_types' => LookupResource::collection(IdType::options()),
            'job_titles' => LookupResource::collection(JobTitle::options()),
            'payment_methods' => LookupResource::collection(PaymentMethod::options()),
            'payment_statuses' => LookupResource::collection(PaymentStatus::options()),
            'customer_types' => LookupResource::collection(CustomerType::options()),
        ]]);
    }
}
