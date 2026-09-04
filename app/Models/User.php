<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * الدور والنطاق مُعرّفان في جدول الصلاحيات لا في جدول المستخدمين: هو مرجع
     * الصلاحيات الذي تحرّره بوابة المعلومات نفسها، والربط بالبريد لأنه مفتاحه الفريد.
     */
    public function permission(): HasOne
    {
        return $this->hasOne(UserPermission::class, 'user_email', 'email');
    }

    /**
     * دور المستخدم — يقرؤه سجل العمليات ليُنسب إليه ما يكتبه. من لا صلاحية
     * مسجّلة له فهو مستخدم عادي، لا مدير.
     */
    public function getRoleAttribute(): string
    {
        return $this->permission?->role ?? 'user';
    }

    /**
     * تسمية الدور بالعربية — تُعرض في ذيل القائمة الجانبية.
     */
    public function getRoleLabelAttribute(): string
    {
        return [
            'admin' => 'مدير النظام',
            'top_management' => 'الإدارة العليا',
            'fisheries_admin' => 'إدارة المصايد',
            'researcher' => 'باحث',
            'supervision' => 'الرقابة',
            'region_manager' => 'مدير منطقة',
            'governorate_manager' => 'مدير محافظة',
            'port_manager' => 'مدير ميناء',
        ][$this->role] ?? 'مستخدم';
    }
}
