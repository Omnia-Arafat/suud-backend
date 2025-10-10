<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Company extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'company_name',
        'logo_path',
        'website',
        'description',
        'industry',
        'company_size',
        'location',
        'founded_year',
    ];

    /**
     * Get the employer user that owns this company
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all job listings for this company
     */
    public function jobListings(): HasMany
    {
        return $this->hasMany(JobListing::class);
    }

    /**
     * Get active job listings for this company
     */
    public function activeJobListings(): HasMany
    {
        return $this->hasMany(JobListing::class)->where('status', 'active');
    }

    /**
     * Alias for jobListings - for backward compatibility
     */
    public function jobs(): HasMany
    {
        return $this->jobListings();
    }

    /**
     * Get the full logo URL
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? Storage::url($this->logo_path) : null;
    }

    /**
     * Format website URL
     */
    public function getFormattedWebsiteAttribute(): ?string
    {
        if (!$this->website) {
            return null;
        }

        if (!str_starts_with($this->website, 'http')) {
            return 'https://' . $this->website;
        }

        return $this->website;
    }

    /**
     * Get total number of job postings
     */
    public function getTotalJobsAttribute(): int
    {
        return $this->jobListings()->count();
    }

    /**
     * Get total number of applications received
     */
    public function getTotalApplicationsAttribute(): int
    {
        return Application::whereHas('jobListing', function ($query) {
            $query->where('company_id', $this->id);
        })->count();
    }
}
