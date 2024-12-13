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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->morphs('agentable');
            $table->nullableMorphs('clientable');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['agentable_id', 'agentable_type', 'start_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
