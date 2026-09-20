<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * أصل القوائم المرجعية (أنواع القوارب، طرق الدفع…): كلها بالأعمدة نفسها —
 * اسم عربي فريد، اسم إنجليزي، فعّال، ترتيب — وتُقرأ مرتّبةً وفعّالةً.
 */
abstract class LookupModel extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }

    /**
     * القائمة كما تُعرض في نماذج الاختيار وواجهة التطبيق.
     */
    public static function options()
    {
        return static::query()->active()->ordered()->get(['id', 'name', 'name_en']);
    }

    /**
     * السطر باسمه — البذّار يضمن وجوده، والخدمات تستدعيه بالمفتاح لا بالمعرّف.
     */
    public static function named(string $name): static
    {
        return static::query()->firstOrCreate(['name' => $name]);
    }
}
