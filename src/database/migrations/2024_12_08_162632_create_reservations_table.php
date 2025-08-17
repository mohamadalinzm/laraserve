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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->morphs('provider');
            $table->nullableMorphs('recipient');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['provider_id', 'provider_type', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
