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
        Schema::create('hanger_inputs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('hanger_number')->index();
            $table->string('part_code', 64)->nullable()->index();
            $table->string('part_name', 128)->nullable();
            $table->unsignedTinyInteger('sensor_pin')->default(25);
            $table->unsignedInteger('wip_at_time')->default(0);
            $table->string('status', 32)->default('in_line'); // in_line, completed
            $table->timestamp('entered_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hanger_inputs');
    }
};

