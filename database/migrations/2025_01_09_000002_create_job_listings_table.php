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
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->text('description');
            $table->text('requirements');
            $table->string('location');
            $table->enum('job_type', ['full-time', 'part-time', 'contract', 'internship', 'remote']);
            $table->enum('experience_level', ['entry', 'mid', 'senior', 'executive'])->default('entry');
            $table->decimal('salary_min', 10, 2)->nullable();
            $table->decimal('salary_max', 10, 2)->nullable();
            $table->string('salary_currency', 3)->default('USD');
            
            // Application settings
            $table->date('application_deadline')->nullable();
            $table->integer('positions_available')->default(1);
            
            // Status management
            $table->enum('status', ['pending', 'active', 'declined', 'closed'])->default('pending');
            $table->text('decline_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('declined_at')->nullable();
            
            // SEO and categorization
            $table->string('slug')->unique();
            $table->json('skills')->nullable(); // Store required skills as JSON
            $table->string('category')->nullable(); // Job category/department
            
            // Tracking
            $table->integer('views_count')->default(0);
            $table->integer('applications_count')->default(0);
            
            $table->timestamps();

            // Indexes for performance
            $table->index('status');
            $table->index('job_type');
            $table->index('location');
            $table->index('category');
            $table->index('experience_level');
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_listings');
    }
};
