<?php
// database/migrations/xxxx_create_tenants_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            
            // 🔥 Relationships
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            // 🔥 Emergency Contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            
            // 🔥 Employment Information
            $table->string('employer_name')->nullable();
            $table->string('employer_phone')->nullable();
            $table->string('employer_address')->nullable();
            $table->string('job_title')->nullable();
            $table->decimal('monthly_income', 10, 2)->nullable();
            $table->date('employment_start_date')->nullable();
            
            // 🔥 Previous Address
            $table->text('previous_address')->nullable();
            $table->string('previous_landlord_name')->nullable();
            $table->string('previous_landlord_phone')->nullable();
            $table->date('previous_tenancy_start')->nullable();
            $table->date('previous_tenancy_end')->nullable();
            
            // 🔥 Identification
            $table->string('id_type')->nullable(); // passport, national_id, driver_license
            $table->string('id_number')->nullable();
            $table->date('id_expiry_date')->nullable();
            
            // 🔥 Move-in Information
            $table->date('move_in_date')->nullable();
            $table->integer('number_of_occupants')->default(1);
            $table->boolean('has_pets')->default(false);
            $table->text('pet_details')->nullable();
            
            // 🔥 References (JSON for multiple references)
            $table->json('references')->nullable();
            
            // 🔥 Background Check
            $table->enum('background_check_status', ['pending', 'approved', 'rejected', 'not_required'])
                ->default('pending');
            $table->date('background_check_date')->nullable();
            $table->text('background_check_notes')->nullable();
            
            // 🔥 Additional Information
            $table->enum('status', ['active', 'inactive', 'blacklisted'])->default('active');
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // 🔥 PERFORMANCE: Indexes
            $table->index(['company_id', 'status']);
            $table->index('user_id');
            $table->index('move_in_date');
            $table->index('background_check_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};