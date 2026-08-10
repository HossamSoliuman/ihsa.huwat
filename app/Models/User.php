<?php

namespace App\Models;

use App\Actions\Information\Support\InformationScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const UPDATED_AT = null;

    protected $fillable = [
        'role_id',
        'full_name',
        'username',
        'email',
        'phone',
        'national_id',
        'password_hash',
        'region_id',
        'governorate_id',
        'port_id',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = ['password_hash'];

    /** Resolved once per request: the same instance answers every screen it is asked on. */
    private ?InformationScope $informationScope = null;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function getAuthPassword(): string
    {
        return (string) $this->password_hash;
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * The records a moderator account was pinned to; desk accounts carry none. Named apart
     * from Eloquent's query scopes, which share the word and mean something else entirely.
     */
    public function assignedScopes(): HasMany
    {
        return $this->hasMany(UserScope::class);
    }

    public function informationScope(): InformationScope
    {
        return $this->informationScope ??= InformationScope::for($this);
    }
}
