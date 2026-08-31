<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wms_salidas', function (Blueprint $table) {
            $table->foreignId('created_id')->nullable()->after('id')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('update_id')->nullable()->after('created_id')
                ->constrained('users')->nullOnDelete();
            $table->foreignId('delete_id')->nullable()->after('update_id')
                ->constrained('users')->nullOnDelete();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('wms_salidas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_id');
            $table->dropConstrainedForeignId('update_id');
            $table->dropConstrainedForeignId('delete_id');
            $table->dropSoftDeletes();
        });
    }
};