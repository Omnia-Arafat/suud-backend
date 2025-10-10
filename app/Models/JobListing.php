<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class JobListing extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'title',
        'description',
        'requirements',
        'location',
        'job_type',
        'experience_level',
        'salary_min',
        'salary_max',
        'salary_currency',
        'application_deadline',
        'positions_available',
        'status',
        'decline_reason',
        'approved_at',
        'declined_at',
        'slug',
        'skills',
        'category',
        'views_count',
        'applications_count',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'skills' => 'array',
            'application_deadline' => 'date',
            'approved_at' => 'datetime',
            'declined_at' => 'datetime',
            'salary_min' => 'decimal:2',
            'salary_max' => 'decimal:2',
            'positions_available' => 'integer',
            'views_count' => 'integer',
            'applications_count' => 'integer',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Generate slug when creating
        static::creating(function ($job) {
            if (empty($job->slug)) {
                $job->slug = Str::slug($job->title . '-' . Str::random(8));
            }
        });

        // Update slug when title changes
        static::updating(function ($job) {
            if ($job->isDirty('title')) {
                $job->slug = Str::slug($job->title . '-' . Str::random(8));
            }
        });
    }

    /**
     * Get the company that owns this job listing
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all applications for this job listing
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    /**
     * Get applications with specific status
     */
    public function applicationsByStatus(string $status): HasMany
    {
        return $this->hasMany(Application::class)->where('status', $status);
    }

    /**
     * Check if job is active and accepting applications
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               (!$this->application_deadline || $this->application_deadline->isFuture());
    }

    /**
     * Check if job is expired
     */
    public function isExpired(): bool
    {
        return $this->application_deadline && $this->application_deadline->isPast();
    }

    /**
     * Get formatted salary range
     */
    public function getFormattedSalaryAttribute(): ?string
    {
        if (!$this->salary_min && !$this->salary_max) {
            return null;
        }

        $currency = $this->salary_currency ?? 'USD';
        
        if ($this->salary_min && $this->salary_max) {
            return "{$currency} " . number_format($this->salary_min) . " - " . number_format($this->salary_max);
        }

        if ($this->salary_min) {
            return "{$currency} " . number_format($this->salary_min) . "+";
        }

        return "Up to {$currency} " . number_format($this->salary_max);
    }

    /**
     * Increment view count
     */
    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    /**
     * Update applications count
     */
    public function updateApplicationsCount(): void
    {
        $this->update(['applications_count' => $this->applications()->count()]);
    }

    /**
     * Approve the job listing
     */
    public function approve(): void
    {
        $this->update([
            'status' => 'active',
            'approved_at' => now(),
            'declined_at' => null,
            'decline_reason' => null,
        ]);
    }

    /**
     * Decline the job listing
     */
    public function decline(string $reason): void
    {
        $this->update([
            'status' => 'declined',
            'declined_at' => now(),
            'decline_reason' => $reason,
            'approved_at' => null,
        ]);
    }

    /**
     * Close the job listing
     */
    public function close(): void
    {
        $this->update(['status' => 'closed']);
    }

    /**
     * Scope for active jobs
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for pending jobs
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for search by keyword
     */
    public function scopeSearch($query, ?string $keyword)
    {
        if (!$keyword) return $query;

        return $query->where(function ($q) use ($keyword) {
            $q->where('title', 'LIKE', "%{$keyword}%")
              ->orWhere('description', 'LIKE', "%{$keyword}%")
              ->orWhere('requirements', 'LIKE', "%{$keyword}%")
              ->orWhere('category', 'LIKE', "%{$keyword}%")
              ->orWhereHas('company', function ($companyQuery) use ($keyword) {
                  $companyQuery->where('company_name', 'LIKE', "%{$keyword}%");
              });
        });
    }

    /**
     * Scope for filtering by location
     */
    public function scopeLocation($query, ?string $location)
    {
        if (!$location) return $query;

        return $query->where('location', 'LIKE', "%{$location}%");
    }

    /**
     * Scope for filtering by job type
     */
    public function scopeJobType($query, ?string $jobType)
    {
        if (!$jobType) return $query;

        return $query->where('job_type', $jobType);
    }

    /**
     * Get route key name for model binding
     */
    public function getRouteKeyName(): string
    {
        return 'id';
    }
}
