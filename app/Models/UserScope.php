<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One record a moderator account may reach. The account's level is the type its rows carry
 * — they are all of one type — and the records themselves are the rows' `scope_id`.
 */
class UserScope extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public const TYPE_REGION = 'region';

    public const TYPE_GOVERNORATE = 'governorate';

    public const TYPE_PORT = 'port';

    public const TYPE_MARKET = 'market';

    /** @var array<string, string> */
    public const TYPES = [
        self::TYPE_REGION => 'منطقة',
        self::TYPE_GOVERNORATE => 'محافظة',
        self::TYPE_PORT => 'ميناء',
        self::TYPE_MARKET => 'سوق',
    ];

    protected $fillable = ['user_id', 'scope_type', 'scope_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
