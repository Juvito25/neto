<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tenant_id')->index();
            $table->enum('type', ['products', 'services', 'both'])->default('products');
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('file_url')->nullable();
            $table->integer('file_size')->nullable();
            $table->enum('status', ['draft', 'processing', 'active', 'error'])->default('draft');
            $table->text('error_message')->nullable();
            $table->integer('total_items')->default(0);
            $table->timestamp('last_sync')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogs');
    }
};