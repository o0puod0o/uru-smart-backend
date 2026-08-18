<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('push_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('push_token', 512);
            $table->string('provider', 20)->default('expo');
            $table->string('platform', 20)->nullable();
            $table->string('app_version', 50)->nullable();
            $table->string('app_ownership', 50)->nullable();
            $table->string('device_name', 100)->nullable();
            $table->string('device_model', 100)->nullable();
            $table->string('device_brand', 100)->nullable();
            $table->string('os_name', 50)->nullable();
            $table->string('os_version', 50)->nullable();
            $table->uuid('expo_project_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['user_id', 'push_token']);
            $table->index('user_id');
            $table->index(['provider', 'is_active']);
        });

        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->json('settings');
            $table->string('platform', 20)->nullable();
            $table->string('app_version', 50)->nullable();
            $table->uuid('expo_project_id')->nullable();
            $table->timestamps();
        });

        // Preserve tokens saved by the previous single-device implementation.
        if (Schema::hasColumn('users', 'push_token')) {
            $now = now();

            DB::table('users')
                ->whereNotNull('push_token')
                ->where('push_token', '!=', '')
                ->orderBy('id')
                ->each(function ($user) use ($now): void {
                    DB::table('push_tokens')->insertOrIgnore([
                        'user_id' => $user->id,
                        'push_token' => $user->push_token,
                        'provider' => 'expo',
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
        Schema::dropIfExists('push_tokens');
    }
};
