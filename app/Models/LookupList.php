<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Shared behaviour for the option lists the portal form draws from. Every list owns a
 * table and a model of its own; this base carries only what all of them need — the
 * stored code a submission keeps, the Arabic name the applicant reads, the order the
 * form renders them in, and one cached read per list.
 */
abstract class LookupList extends Model
{
    use HasFactory;

    /**
     * Every editable list, keyed by the segment the reference desk addresses it by.
     *
     * @var array<string, class-string<self>>
     */
    public const LISTS = [
        'departments' => Department::class,
        'job_titles' => JobTitle::class,
        'banks' => Bank::class,
        'nationalities' => Nationality::class,
        'boat_types' => BoatType::class,
        'boat_classifications' => BoatClassification::class,
        'hull_materials' => HullMaterial::class,
        'crew_roles' => CrewRole::class,
        'market_job_titles' => MarketJobTitle::class,
        'fishing_methods' => FishingMethod::class,
        'fishing_tool_types' => FishingToolType::class,
        'fishing_tool_materials' => FishingToolMaterial::class,
        'fishing_tool_conditions' => FishingToolCondition::class,
    ];

    protected $fillable = ['code', 'name', 'sort_order', 'is_active'];

    protected $attributes = ['sort_order' => 0, 'is_active' => true];

    /** Arabic heading this list is maintained under. */
    abstract public static function title(): string;

    protected static function booted(): void
    {
        static::saved(static fn (self $option): bool => Cache::forget($option::cacheKey()));
        static::deleted(static fn (self $option): bool => Cache::forget($option::cacheKey()));
    }

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_active' => 'boolean'];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** The segment this list is addressed by, which is also its table name. */
    public static function key(): string
    {
        return (new static)->getTable();
    }

    /** @return class-string<self> */
    public static function resolve(string $key): string
    {
        return self::LISTS[$key];
    }

    public static function cacheKey(): string
    {
        return 'information.lookup.'.static::key();
    }

    /**
     * Values an applicant may pick right now, as `code => name`.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(static::cached())->where('is_active', true)->pluck('name', 'code')->all();
    }

    /**
     * Every value ever offered, retired ones included, so a submission filed under an
     * option that has since been switched off still renders its Arabic name.
     *
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return collect(static::cached())->pluck('name', 'code')->all();
    }

    /**
     * The whole list in one cached read — the portal form alone touches nine of them.
     *
     * @return list<array{code: string, name: string, is_active: bool}>
     */
    private static function cached(): array
    {
        return Cache::rememberForever(static::cacheKey(), static fn (): array => static::query()
            ->ordered()
            ->get(['code', 'name', 'is_active'])
            ->map(fn (self $option): array => [
                'code' => $option->code,
                'name' => $option->name,
                'is_active' => $option->is_active,
            ])
            ->all());
    }
}
