<?php

namespace App\Http\Controllers\Api\V1\Owner;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * الدلالون المفعّلون — لقائمة "إرسال مصيد للدلال".
 */
class DalalController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $dalals = User::where('active', true)
            ->whereHas('appRole', fn ($q) => $q->where('key', Role::DALAL))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->query('search').'%'))
            ->orderBy('name')
            ->get(['id', 'name', 'phone']);

        return response()->json(['data' => $dalals]);
    }
}
