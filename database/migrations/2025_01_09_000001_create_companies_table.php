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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // One-to-one with employer user
            $table->string('company_name');
            $table->string('logo_path')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->string('industry')->nullable();
            $table->string('company_size')->nullable(); // e.g., "1-10", "11-50", "51-200", etc.
            $table->string('location')->nullable();
            $table->string('founded_year')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('company_name');
            $table->index('industry');
            $table->index('location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
