<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('images', function (Blueprint $table) {
            $table->id();

            // Polymorphic columns (imageable_type + imageable_id)
            $table->morphs('imageable'); // creates: imageable_type (varchar) + imageable_id (bigint unsignedBigInt) + composite index

            $table->string('path');                              // storage path
            $table->string('disk')->default('public');          
            $table->boolean('is_primary')->default(false);
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            // Extra index for most common query: "give me primary image of X"
            $table->index(['imageable_type', 'imageable_id', 'is_primary'], 'images_primary_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('images');
    }
};
