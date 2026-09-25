<?php

namespace App\Http\Controllers\Api\V1\Dalal;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dalal\PayoutRequest;
use App\Services\Dalal\DalalAccounts;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * الصيّادون المرتبطون بحساب كلٍّ منهم، ودفعة لمالك من مستحقه.
 */
class OwnerController extends Controller
{
    public function __construct(private readonly DalalAccounts $accounts) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->accounts->owners($request->user(), $request->query('search'), $request->integer('region_id') ?: null)]);
    }

    public function payout(PayoutRequest $request, int $owner): JsonResponse
    {
        $dalal = $request->user();
        $model = $this->accounts->linkedOwner($dalal, $owner);
        $payout = $this->accounts->recordPayout($dalal, $model, $request->validated());

        return response()->json(['data' => [
            'id' => $payout->id,
            'owner' => ['id' => $model->id, 'name' => $model->name],
            'amount' => (float) $payout->amount,
            'paid_at' => $payout->paid_at?->toIso8601String(),
            'due' => $this->accounts->dueTo($dalal, $model),
        ]], 201);
    }
}
