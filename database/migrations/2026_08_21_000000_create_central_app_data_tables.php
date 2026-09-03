<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chatbot_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->text('message');
            $table->longText('reply')->nullable();
            $table->string('provider', 50)->default('uru_ai_space');
            $table->string('model', 100)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('module', 50)->default('app')->index();
            $table->string('key', 100);
            $table->json('value')->nullable();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable()->index();
            $table->timestamps();

            $table->unique(['module', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
        Schema::dropIfExists('chatbot_histories');
    }
};
