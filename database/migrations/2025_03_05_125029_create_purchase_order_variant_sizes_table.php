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
        Schema::create('purchase_order_variant_sizes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_product_variant_id')->index();
            $table->unsignedBigInteger('size_id')->index();
            $table->integer('quantity')->default(0);
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('purchase_product_variant_id')->references('id')->on('purchase_order_variant_sizes')->onDelete('cascade');
            $table->foreign('size_id')->references('id')->on('sizes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_variant_sizes');
    }
};
