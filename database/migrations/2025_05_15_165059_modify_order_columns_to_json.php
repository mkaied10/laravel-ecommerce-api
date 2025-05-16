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
        Schema::table('orders', function (Blueprint $table) {
            $table->json('delivery_address')->change();
            $table->json('order_status')->change();
            $table->json('payment_status')->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->json('delivery_address')->change(); // JSON لأنه كان array
            $table->string('order_status')->change();
            $table->string('payment_status')->change();
        });
    }
};
