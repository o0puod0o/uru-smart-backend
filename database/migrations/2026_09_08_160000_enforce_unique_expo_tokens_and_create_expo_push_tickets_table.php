<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('push_tokens')) {
            $this->keepMostRecentlyUpdatedTokenOwner();

            if (! $this->indexExists('push_tokens', 'push_tokens_push_token_unique')) {
                Schema::table('push_tokens', function (Blueprint $table): void {
                    $table->unique('push_token', 'push_tokens_push_token_unique');
                });
            }
        }

        if (! Schema::hasTable('expo_push_tickets')) {
            Schema::create('expo_push_tickets', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('push_token_id')->nullable()->index();
                $table->string('ticket_id', 100)->nullable()->unique();
                $table->string('ticket_status', 30)->nullable()->index();
                $table->json('ticket_details')->nullable();
                $table->string('receipt_status', 30)->nullable()->index();
                $table->json('receipt_details')->nullable();
                $table->timestamp('receipt_checked_at')->nullable()->index();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('expo_push_tickets');

        if (Schema::hasTable('push_tokens') && $this->indexExists('push_tokens', 'push_tokens_push_token_unique')) {
            Schema::table('push_tokens', function (Blueprint $table): void {
                $table->dropUnique('push_tokens_push_token_unique');
            });
        }
    }

    /**
     * A device token can only have one owner. If legacy rows contain a duplicate,
     * retain the most recently updated row, which represents the latest owner.
     */
    private function keepMostRecentlyUpdatedTokenOwner(): void
    {
        DB::transaction(function (): void {
            $duplicates = DB::table('push_tokens')
                ->select('push_token')
                ->groupBy('push_token')
                ->havingRaw('COUNT(*) > 1')
                ->pluck('push_token');

            foreach ($duplicates as $pushToken) {
                $rows = DB::table('push_tokens')
                    ->where('push_token', $pushToken)
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->get(['id', 'user_id']);

                $staleIds = $rows->skip(1)->pluck('id');

                if ($staleIds->isNotEmpty()) {
                    $staleOwnerIds = $rows->skip(1)->pluck('user_id')->filter();

                    if (Schema::hasColumn('users', 'push_token') && $staleOwnerIds->isNotEmpty()) {
                        DB::table('users')
                            ->whereIn('id', $staleOwnerIds)
                            ->where('push_token', $pushToken)
                            ->update(['push_token' => null]);
                    }

                    DB::table('push_tokens')->whereIn('id', $staleIds)->delete();
                }
            }
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        return collect(DB::select("SHOW INDEX FROM `{$table}`"))
            ->contains(fn ($row): bool => ($row->Key_name ?? null) === $index);
    }
};
