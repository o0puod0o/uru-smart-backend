<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->default('user')->index();
        });

        Schema::table('has_journals', function (Blueprint $table) {
            $table->string('status', 30)->default('draft')->index();
            $table->decimal('budget', 14, 2)->nullable();
            $table->index(['user_id', 'year']);
        });

        Schema::create('proposals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_user_id')->index();
            $table->string('title');
            $table->text('summary')->nullable();
            $table->string('year', 4)->index();
            $table->string('status', 30)->default('draft')->index();
            $table->decimal('budget', 14, 2)->nullable();
            $table->timestamps();
            $table->index(['owner_user_id', 'created_at']);
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_user_id')->index();
            $table->unsignedBigInteger('proposal_id')->nullable()->index();
            $table->string('title');
            $table->longText('content')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->timestamps();
            $table->index(['owner_user_id', 'created_at']);
        });

        Schema::create('entity_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('owner_user_id')->index();
            $table->string('entity_type', 30)->index();
            $table->unsignedBigInteger('entity_id')->index();
            $table->string('original_name');
            $table->string('stored_name');
            $table->string('path');
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('size');
            $table->timestamps();
            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_user_id')->nullable()->index();
            $table->string('action', 50)->index();
            $table->string('entity_type', 50)->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('entity_files');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('proposals');
        Schema::table('has_journals', fn (Blueprint $table) => $table->dropColumn(['status', 'budget']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('role'));
    }
};
