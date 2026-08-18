<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservation_requests', function (Blueprint $table) {
            $table->foreignId('table_id')
                ->nullable()
                ->after('branch_id')
                ->constrained('tables')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservation_requests', function (Blueprint $table) {
            $table->dropForeign(['table_id']);
            $table->dropColumn('table_id');
        });
    }
};
