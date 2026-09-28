<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tt_class_base_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tt_setting_id')->constrained('tt_settings')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('tt_room_id')->constrained('tt_rooms')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['tt_setting_id', 'class_id'], 'tt_base_room_setting_class_unique');
            $table->unique(['tt_setting_id', 'tt_room_id'], 'tt_base_room_setting_room_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_class_base_rooms');
    }
};
