<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class AuditService
{
    public static function record(string $action, string $module, Model|string|null $record = null, array $old = [], array $new = [], ?string $reason = null): void
    {
        AuditLog::create(['user_id' => auth()->id(), 'action' => $action, 'module' => $module, 'record_type' => $record instanceof Model ? $record::class : null, 'record_id' => $record instanceof Model ? (string) $record->getKey() : (is_string($record) ? $record : null), 'old_values' => $old ?: null, 'new_values' => $new ?: null, 'ip_address' => request()->ip(), 'reason' => $reason]);
    }
}
