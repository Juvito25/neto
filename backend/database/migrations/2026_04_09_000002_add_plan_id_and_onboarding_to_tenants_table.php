<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Agregar columna plan_id como foreign key
            $table->uuid('plan_id')->nullable()->after('email');
            $table->foreign('plan_id')->references('id')->on('plans')->nullOnDelete();

            // Columnas de onboarding
            $table->string('onboarding_step')->default('business')->after('plan_id');
            $table->boolean('onboarding_completed')->default(false)->after('onboarding_step');
        });

        // Eliminar columnas antiguas en una operación separada
        Schema::table('tenants', function (Blueprint $table) {
            // Eliminar columnas antiguas que serán reemplazadas por la relación con plans
            $table->dropColumn('plan');
            $table->dropColumn('messages_limit');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropForeign(['plan_id']);
            $table->dropColumn(['plan_id', 'onboarding_step', 'onboarding_completed']);

            $table->enum('plan', ['starter', 'pro', 'business'])->default('pro');
            $table->integer('messages_limit')->default(2000);
        });
    }
};
