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
        Schema::create('tv_products', function (Blueprint $table) {
            $table->id();

            // basic info
            $table->string('title');
            $table->string('brand')->nullable();
            $table->string('shop')->nullable();

            // links
            $table->string('product_url');
            $table->string('image_url')->nullable();

            // price in cents
            $table->unsignedInteger('price_cents')->nullable();
            $table->string('currency', 3)->default('EUR');

            // category (bonus from tasks)
            $table->string('category')->nullable();

            // external id from url
            $table->string('external_id')->nullable()->index();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tv_products');
    }
};
