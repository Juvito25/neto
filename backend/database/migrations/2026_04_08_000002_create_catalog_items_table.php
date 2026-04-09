<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('catalog_id')->index();
            $table->uuid('tenant_id')->index();
            
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('sku')->nullable();
            
            $table->decimal('price', 10, 2)->nullable();
            $table->integer('quantity')->nullable();
            $table->string('category')->nullable();
            
            $table->integer('duration_minutes')->nullable();
            $table->json('availability_json')->nullable();
            
            $table->string('image_url')->nullable();
            $table->json('metadata_json')->nullable();
            
            $table->timestamps();

            $table->foreign('catalog_id')->references('id')->on('catalogs')->onDelete('cascade');
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_items');
    }
};