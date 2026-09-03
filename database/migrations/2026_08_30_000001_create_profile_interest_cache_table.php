<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('profile_interest_cache')) {
            return;
        }

        Schema::create('profile_interest_cache', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('citizen_id', 64)->index();
            $table->unsignedBigInteger('source_interest_id')->nullable()->index();
            $table->string('interest_name', 500);
            $table->string('interest_name_normalized', 191)->index();
            $table->dateTime('source_updated_at')->nullable();
            $table->timestamp('synced_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'source_interest_id'], 'pic_user_source_unique');
            $table->index(['user_id', 'interest_name_normalized'], 'pic_user_interest_name_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_interest_cache');
    }
};
