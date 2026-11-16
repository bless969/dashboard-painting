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
        Schema::create('hanger_outputs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('hanger_number')->index();
            $table->string('part_code', 64)->nullable()->index();
            $table->string('part_name', 128)->nullable();
            $table->unsignedInteger('target_pcs')->default(4);
            $table->unsignedInteger('detected_pcs')->default(0);
            $table->boolean('is_empty')->default(false);
            $table->unsignedTinyInteger('sensor_pin')->default(27);
            $table->float('confidence', 5, 2)->nullable();
            $table->string('image_path', 255)->nullable();
            $table->timestamp('exited_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hanger_outputs');
    }
};

