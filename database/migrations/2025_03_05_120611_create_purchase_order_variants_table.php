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
        Schema::create('purchase_order_variants', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_product_id')->index();
            $table->string('supplier_color_code');
            $table->string('supplier_color_name');
            $table->unsignedBigInteger('color_id')->index();
            $table->timestamps();

            $table->foreign('purchase_product_id')->references('id')->on('purchase_order_products')->onDelete('cascade');
            $table->foreign('color_id')->references('id')->on('colors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_variants');
    }
};
