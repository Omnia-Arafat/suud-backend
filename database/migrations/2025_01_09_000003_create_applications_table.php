<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Student who applied
            $table->foreignId('job_listing_id')->constrained()->onDelete('cascade'); // Job applied for
            
            // Application status management
            $table->enum('status', ['submitted', 'viewed', 'shortlisted', 'rejected'])->default('submitted');
            
            // Application data
            $table->text('cover_letter')->nullable();
            $table->string('resume_path')->nullable(); // Can override user's default CV
            $table->json('answers')->nullable(); // Store answers to custom application questions
            
            // Employer actions
            $table->text('employer_notes')->nullable(); // Private notes from employer
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users'); // Which employer user reviewed
            
            $table->timestamps();

            // Unique constraint to prevent duplicate applications
            $table->unique(['user_id', 'job_listing_id']);
            
            // Indexes for performance
            $table->index('status');
            $table->index('viewed_at');
            $table->index(['job_listing_id', 'status']);
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
