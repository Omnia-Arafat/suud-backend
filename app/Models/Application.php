<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Application extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'job_listing_id',
        'status',
        'cover_letter',
        'resume_path',
        'answers',
        'employer_notes',
        'viewed_at',
        'status_changed_at',
        'reviewed_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'viewed_at' => 'datetime',
            'status_changed_at' => 'datetime',
        ];
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        // Update status_changed_at when status changes
        static::updating(function ($application) {
            if ($application->isDirty('status')) {
                $application->status_changed_at = now();
            }
        });

        // Update job applications count when application is created/deleted
        static::created(function ($application) {
            $application->jobListing->updateApplicationsCount();
        });

        static::deleted(function ($application) {
            $application->jobListing->updateApplicationsCount();
        });
    }

    /**
     * Get the student who submitted this application
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the job listing this application is for
     */
    public function jobListing(): BelongsTo
    {
        return $this->belongsTo(JobListing::class);
    }

    /**
     * Get the employer who reviewed this application
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Get the full resume URL
     */
    public function getResumeUrlAttribute(): ?string
    {
        if ($this->resume_path) {
            return Storage::url($this->resume_path);
        }

        // Fall back to user's default CV
        return $this->user->cv_url;
    }

    /**
     * Mark application as viewed
     */
    public function markAsViewed(User $reviewer): void
    {
        if ($this->status === 'submitted') {
            $this->update([
                'status' => 'viewed',
                'viewed_at' => now(),
                'reviewed_by' => $reviewer->id,
                'status_changed_at' => now(),
            ]);
        }
    }

    /**
     * Update application status
     */
    public function updateStatus(string $status, User $reviewer, ?string $notes = null): void
    {
        $updateData = [
            'status' => $status,
            'reviewed_by' => $reviewer->id,
            'status_changed_at' => now(),
        ];

        if ($notes) {
            $updateData['employer_notes'] = $notes;
        }

        if ($status === 'viewed' && !$this->viewed_at) {
            $updateData['viewed_at'] = now();
        }

        $this->update($updateData);
    }

    /**
     * Check if application can be withdrawn
     */
    public function canBeWithdrawn(): bool
    {
        return in_array($this->status, ['submitted', 'viewed']);
    }

    /**
     * Get status color for UI
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'submitted' => 'blue',
            'viewed' => 'yellow',
            'shortlisted' => 'green',
            'rejected' => 'red',
            default => 'gray',
        };
    }

    /**
     * Get status label for display
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'submitted' => 'Submitted',
            'viewed' => 'Under Review',
            'shortlisted' => 'Shortlisted',
            'rejected' => 'Not Selected',
            default => ucfirst($this->status),
        };
    }

    /**
     * Scope for filtering by status
     */
    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for applications by job
     */
    public function scopeForJob($query, JobListing $job)
    {
        return $query->where('job_listing_id', $job->id);
    }

    /**
     * Scope for applications by user
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }
}
