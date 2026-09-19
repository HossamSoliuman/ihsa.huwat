<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * دور من أدوار تطبيق حوات. المفاتيح ثابتة في الشيفرة لأن القائمة الجانبية
 * ووسيط الصلاحية يتفرّعان عليها، والأسماء والترتيب في الجدول لأنها تُعرض.
 */
class Role extends BaseModel
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    public const SUPER_ADMIN = 'super_admin';

    public const OWNER = 'owner';

    public const CAPTAIN = 'captain';

    public const CREW = 'crew';

    public const EMPLOYEE = 'employee';

    public const COUNTER = 'counter';

    public const DALAL = 'dalal';

    public const MERCHANT = 'merchant';

    /**
     * الأدوار التي ينشئها مالك لحسابه: تتبعه في owner_id ولا تُنشأ من المدير العام.
     */
    public const OWNER_MANAGED = [self::CAPTAIN, self::CREW, self::EMPLOYEE];

    protected $casts = [
        'has_portal' => 'boolean',
        'active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public static function key(string $key): self
    {
        return static::where('key', $key)->firstOrFail();
    }
}
