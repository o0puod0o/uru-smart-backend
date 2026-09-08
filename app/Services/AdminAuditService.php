<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class AdminAuditService
{
    public function recordModel(Request $request, string $action, Model $model, ?array $before = null): void
    {
        $this->record(
            $request,
            $action,
            $model->getMorphClass(),
            $model->getKey(),
            $before,
            $model->fresh()?->toArray(),
        );
    }

    public function recordEvent(Request $request, string $action, string $entityType, ?int $entityId, array $after = []): void
    {
        $this->record($request, $action, $entityType, $entityId, null, $after);
    }

    private function record(
        Request $request,
        string $action,
        string $entityType,
        ?int $entityId,
        ?array $before,
        ?array $after,
    ): void {
        if (! Schema::hasTable('audit_logs')) {
            return;
        }

        $attributes = [
            'actor_user_id' => null,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'before' => $before,
            'after' => $after,
            'ip_address' => $request->ip(),
            'created_at' => now(),
        ];

        if (Schema::hasColumn('audit_logs', 'actor_admin_id')) {
            $attributes['actor_admin_id'] = $request->user('admin')?->id;
        }

        AuditLog::create($attributes);
    }
}
