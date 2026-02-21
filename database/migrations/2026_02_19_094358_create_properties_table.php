<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('locations')->onDelete('restrict');
            $table->string('name');
            $table->text('address');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'location_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
