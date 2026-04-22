<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->string('queue')->nullable()->after('id');
            $table->text('payload')->after('queue');
            $table->unsignedTinyInteger('attempts')->nullable()->after('payload');
            $table->unsignedInteger('reserved_at')->nullable()->after('attempts');
            $table->unsignedInteger('available_at')->nullable()->after('reserved_at');
        });
    }

    public function down(): void
    {
        Schema::table('jobs', function (Blueprint $table) {
            $table->dropColumn([
                'queue',
                'payload',
                'attempts',
                'reserved_at',
                'available_at',
            ]);
        });
    }
};