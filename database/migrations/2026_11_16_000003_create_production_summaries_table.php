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
        Schema::create('production_summaries', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('shift', 32)->default('Shift 1');
            $table->unsignedInteger('total_input_hangers')->default(0);
            $table->unsignedInteger('total_output_hangers')->default(0);
            $table->unsignedInteger('total_output_pcs')->default(0);
            $table->unsignedInteger('total_empty_hangers')->default(0);
            $table->unsignedInteger('wip_hangers')->default(0);
            $table->boolean('is_active')->default(false);
            $table->timestamps();

            $table->unique(['date', 'shift']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_summaries');
    }
};

