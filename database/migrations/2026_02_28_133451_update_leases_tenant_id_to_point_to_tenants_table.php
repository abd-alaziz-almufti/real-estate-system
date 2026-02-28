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
        Schema::table('tenants', function (Blueprint $table) {
              // Drop old foreign key (if exists)
            $table->dropForeign(['tenant_id']);
            
            // Change tenant_id to point to tenants table instead of users
            $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants') // ✅ NOW points to tenants, not users
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
            
            // Revert to old structure
            $table->foreign('tenant_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }
};
