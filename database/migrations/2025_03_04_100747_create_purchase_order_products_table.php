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
        Schema::create('purchase_order_products', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_order_id')->index();
            $table->string('product_code');
            $table->unsignedBigInteger('product_type_id')->index();
            $table->unsignedBigInteger('size_scale_id')->index();
            $table->unsignedBigInteger('min_size_id')->index();
            $table->unsignedBigInteger('max_size_id')->index();
            $table->date('delivery_date');
            $table->decimal('price', 8, 2);
            $table->text('short_description')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('product_type_id')->references('id')->on('product_types')->onDelete('cascade');
            $table->foreign('size_scale_id')->references('id')->on('size_scales')->onDelete('cascade');
            $table->foreign('min_size_id')->references('id')->on('sizes')->onDelete('cascade');
            $table->foreign('max_size_id')->references('id')->on('sizes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_products');
    }
};
