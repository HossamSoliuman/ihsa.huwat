<?php

namespace App\Http\Middleware;

use App\Actions\Information\Support\InformationScope;
use App\Models\FishMarket;
use App\Models\FishMarketBroker;
use App\Models\InformationSubmission;
use App\Models\Port;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The one gate a moderator passes through. It answers two questions for every desk route:
 * whether this account opens this section at all, and — once the route has bound its
 * records — whether the record asked for is one of the account's own.
 *
 * A record outside the scope answers 404 rather than 403: a moderator has no business
 * learning that a port they cannot reach exists.
 */
class EnsureInformationScope
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, ?string $section = null): Response
    {
        $scope = $request->user()->informationScope();

        if ($section !== null) {
            abort_unless($scope->allows($section), 403);
        }

        $this->guardBoundRecords($request, $scope);

        return $next($request);
    }

    /**
     * Route model binding has already run by the time this middleware does, so the records
     * are models rather than keys. A unit or a worker is bound through its market, so the
     * market alone is enough to cover the whole tree beneath it.
     */
    private function guardBoundRecords(Request $request, InformationScope $scope): void
    {
        if ($scope->isUnrestricted()) {
            return;
        }

        foreach ($request->route()?->parameters() ?? [] as $parameter) {
            $allowed = match (true) {
                $parameter instanceof Port => $scope->allowsPort($parameter->id),
                $parameter instanceof FishMarket => $scope->allowsMarket($parameter->id),
                $parameter instanceof FishMarketBroker => $scope->allowsMarket($parameter->fish_market_id),
                $parameter instanceof InformationSubmission => $scope->allowsPort($parameter->port_id),
                default => true,
            };

            abort_unless($allowed, 404);
        }
    }
}
