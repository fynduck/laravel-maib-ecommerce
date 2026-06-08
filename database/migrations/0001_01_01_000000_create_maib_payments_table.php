<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maib_payments', function (Blueprint $table) {
            $table->id();
            $table->string('pay_id')->unique();
            $table->string('order_id')->nullable()->index();
            $table->string('biller_id')->nullable()->index();
            $table->string('status')->nullable();
            $table->string('status_message')->nullable();
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->string('card_number')->nullable();
            $table->string('rrn')->nullable();
            $table->string('approval')->nullable();
            $table->json('raw')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maib_payments');
    }
};
