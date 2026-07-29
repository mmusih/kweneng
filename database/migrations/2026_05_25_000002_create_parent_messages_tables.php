<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->string('subject');
            $table->text('body');
            $table->boolean('is_read_by_admin')->default(false);
            $table->boolean('is_read_by_parent')->default(true); // parent wrote it, so they've seen it
            $table->timestamp('last_reply_at')->nullable(); // updated on each reply for sorting
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('parents')->onDelete('cascade');
        });

        Schema::create('parent_message_replies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->enum('sender_role', ['parent', 'admin']);
            $table->unsignedBigInteger('sender_user_id');
            $table->text('body');
            $table->timestamps();

            $table->foreign('message_id')->references('id')->on('parent_messages')->onDelete('cascade');
            $table->foreign('sender_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parent_message_replies');
        Schema::dropIfExists('parent_messages');
    }
};
