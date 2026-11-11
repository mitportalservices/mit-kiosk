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
        Schema::create('waypoints', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location_code'); // e.g., WP-0104
            $table->text('description')->nullable();
            $table->string('floor')->default('1');
            $table->string('image_url')->nullable();
            $table->string('pano_url')->nullable();
            $table->float('coordinates_x')->default(0);
            $table->float('coordinates_y')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waypoints');
    }
};
