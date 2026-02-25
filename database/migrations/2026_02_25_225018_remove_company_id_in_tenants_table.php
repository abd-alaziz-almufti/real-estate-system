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
            //` Remove company_id foreign key and column\\
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            //` Add company_id foreign key and column back\\
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
        });
    }
};
