<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('chat_conversations')) {
            Schema::create('chat_conversations', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('title', 255)->default('แชทใหม่');
                $table->text('preview')->nullable();
                $table->boolean('pinned')->default(false)->index();
                $table->timestamps();

                $table->index(['user_id', 'pinned', 'updated_at']);
            });
        }

        if (! Schema::hasTable('chat_messages')) {
            Schema::create('chat_messages', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('conversation_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('role', 20);
                $table->longText('content');
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['conversation_id', 'created_at']);
                $table->index(['user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');
    }
};
