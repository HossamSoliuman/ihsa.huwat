<?php

namespace App\Actions\Information\Dashboard\Panels;

use App\Actions\Information\Dashboard\DashboardScope;
use App\Actions\Information\Dashboard\Support\QueueUrl;
use App\Models\FishMarket;
use App\Models\FishMarketUnit;
use App\Models\InformationSubmission;
use Illuminate\Database\Eloquent\Builder;

final class DataCompletenessPanel
{
    private const LIMIT = 6;

    public function __construct(private QueueUrl $queueUrl) {}

    /** @return array<string, mixed> */
    public function build(DashboardScope $scope): array
    {
        $marketsWithoutUnits = $scope->applyMarkets(FishMarket::query())->whereDoesntHave('units');
        $marketsWithoutBrokers = $scope->applyMarkets(FishMarket::query())->whereDoesntHave('brokers');
        $unitsWithoutWorkers = FishMarketUnit::query()
            ->whereDoesntHave('workers')
            ->whereIn('fish_market_id', $scope->applyMarkets(FishMarket::query())->select('id'));
        $inactiveMarketsWithActiveUnits = $scope->applyMarkets(FishMarket::query())
            ->where('is_active', false)
            ->whereHas('units', fn (Builder $query): Builder => $query->where('is_active', true));
        $missingDocuments = $scope->applySubmissions(InformationSubmission::query(), $scope->currentStart, $scope->currentEnd)
            ->where(function (Builder $query): void {
                $query->whereNull('document_paths');

                foreach ($this->requiredDocumentCategories() as $category) {
                    $query->orWhere('document_paths', 'not like', '%"'.$category.'"%');
                }
            });

        return ['dataCompleteness' => [
            $this->marketIssue('أسواق بلا وحدات', $marketsWithoutUnits),
            $this->unitIssue('وحدات بلا عمالة', $unitsWithoutWorkers),
            $this->marketIssue('أسواق بلا دلالين', $marketsWithoutBrokers),
            $this->marketIssue('أسواق متوقفة بوحدات نشطة', $inactiveMarketsWithActiveUnits),
            $this->submissionIssue('طلبات ينقصها مستند', $missingDocuments, $scope),
        ]];
    }

    /** @param  Builder<FishMarket>  $query */
    private function marketIssue(string $label, Builder $query): array
    {
        return [
            'label' => $label,
            'count' => (clone $query)->count(),
            'items' => (clone $query)->orderBy('name')->limit(self::LIMIT)->get(['id', 'name'])
                ->map(fn (FishMarket $market): array => [
                    'label' => $market->name,
                    'href' => route('information.admin.markets.show', $market),
                ])->all(),
        ];
    }

    /** @param  Builder<FishMarketUnit>  $query */
    private function unitIssue(string $label, Builder $query): array
    {
        return [
            'label' => $label,
            'count' => (clone $query)->count(),
            'items' => (clone $query)->with('market:id,name')->orderBy('label')->limit(self::LIMIT)
                ->get(['id', 'fish_market_id', 'label'])
                ->map(fn (FishMarketUnit $unit): array => [
                    'label' => ($unit->label ?: 'وحدة #'.$unit->id).' · '.$unit->market->name,
                    'href' => route('information.admin.markets.show', $unit->market).'#unit-'.$unit->id,
                ])->all(),
        ];
    }

    /** @param  Builder<InformationSubmission>  $query */
    private function submissionIssue(string $label, Builder $query, DashboardScope $scope): array
    {
        return [
            'label' => $label,
            'count' => (clone $query)->count(),
            'href' => $this->queueUrl->submissions($scope),
            'items' => (clone $query)->latest('submitted_at')->limit(self::LIMIT)->get(['id', 'reference_no'])
                ->map(fn (InformationSubmission $submission): array => [
                    'label' => $submission->reference_no,
                    'href' => route('information.admin.show', $submission),
                ])->all(),
        ];
    }

    /** @return list<string> */
    private function requiredDocumentCategories(): array
    {
        return collect(config('information.document_types'))
            ->where('required', true)
            ->keys()
            ->all();
    }
}
