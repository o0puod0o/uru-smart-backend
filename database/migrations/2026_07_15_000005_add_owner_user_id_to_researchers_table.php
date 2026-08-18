<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addOwnerToResearchersTable();
        $this->addOwnerToHasResearchesTable();
    }

    private function addOwnerToResearchersTable(): void
    {
        if (! Schema::hasTable('researchers') || Schema::hasColumn('researchers', 'owner_user_id')) {
            return;
        }

        Schema::table('researchers', function (Blueprint $table) {
            $table->unsignedBigInteger('owner_user_id')->nullable()->after('email')->index();
        });

        DB::table('researchers')
            ->whereNull('owner_user_id')
            ->whereNotNull('email')
            ->orderBy('id')
            ->chunkById(100, function ($researchers) {
                foreach ($researchers as $researcher) {
                    $ownerId = DB::table('users')
                        ->where('email', $researcher->email)
                        ->value('id');

                    if ($ownerId) {
                        DB::table('researchers')
                            ->where('id', $researcher->id)
                            ->update(['owner_user_id' => $ownerId]);
                    }
                }
            });

        $this->tryAddOwnerForeignKey('researchers');
    }

    private function addOwnerToHasResearchesTable(): void
    {
        if (! Schema::hasTable('has_researches') || Schema::hasColumn('has_researches', 'owner_user_id')) {
            return;
        }

        Schema::table('has_researches', function (Blueprint $table) {
            $table->unsignedBigInteger('owner_user_id')->nullable()->after('user_id')->index();
        });

        DB::table('has_researches')
            ->whereNull('owner_user_id')
            ->whereNotNull('user_id')
            ->orderBy('id')
            ->chunkById(100, function ($researches) {
                foreach ($researches as $research) {
                    $ownerId = DB::table('users')
                        ->where('id', $research->user_id)
                        ->value('id');

                    if ($ownerId) {
                        DB::table('has_researches')
                            ->where('id', $research->id)
                            ->update(['owner_user_id' => $ownerId]);
                    }
                }
            });

        $this->tryAddOwnerForeignKey('has_researches');
    }

    private function tryAddOwnerForeignKey(string $table): void
    {
        try {
            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreign('owner_user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            });
        } catch (Throwable $e) {
            report($e);
        }
    }

    public function down(): void
    {
        $this->dropOwnerFromResearchersTable();
        $this->dropOwnerFromHasResearchesTable();
    }

    private function dropOwnerFromResearchersTable(): void
    {
        if (! Schema::hasTable('researchers') || ! Schema::hasColumn('researchers', 'owner_user_id')) {
            return;
        }

        Schema::table('researchers', function (Blueprint $table) {
            $table->dropForeign(['owner_user_id']);
            $table->dropColumn('owner_user_id');
        });
    }

    private function dropOwnerFromHasResearchesTable(): void
    {
        if (! Schema::hasTable('has_researches') || ! Schema::hasColumn('has_researches', 'owner_user_id')) {
            return;
        }

        Schema::table('has_researches', function (Blueprint $table) {
            $table->dropForeign(['owner_user_id']);
            $table->dropColumn('owner_user_id');
        });
    }
};
