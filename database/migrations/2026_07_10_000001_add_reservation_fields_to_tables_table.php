<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->string('reservation_customer_name')->nullable()->after('status');
            $table->string('reservation_phone')->nullable()->after('reservation_customer_name');
            $table->text('reservation_note')->nullable()->after('reservation_phone');
        });
    }

    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            $table->dropColumn([
                'reservation_customer_name',
                'reservation_phone',
                'reservation_note',
            ]);
        });
    }
};
