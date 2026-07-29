<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmploymentJob extends Model
{
    use HasFactory;

    protected $attributes = ['status' => 'draft', 'employment_type' => 'full_time', 'vacancies' => 1];

    protected $fillable = [
        'reference_no', 'title_ar', 'department', 'summary', 'description',
        'responsibilities', 'requirements', 'employment_type', 'vacancies',
        'port_id', 'city', 'salary_min', 'salary_max', 'application_deadline',
        'status', 'published_at', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'application_deadline' => 'date',
            'published_at' => 'datetime',
            'salary_min' => 'decimal:2',
            'salary_max' => 'decimal:2',
        ];
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', 'open')
            ->where(fn (Builder $query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn (Builder $query) => $query->whereNull('application_deadline')->orWhereDate('application_deadline', '>=', today()));
    }

    public function port(): BelongsTo
    {
        return $this->belongsTo(Port::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(EmploymentApplication::class, 'job_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
