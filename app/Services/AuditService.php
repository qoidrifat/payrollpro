<?php

namespace App\Services;

use App\Models\ActivityLog;

/**
 * Manual audit logging service for controller-driven actions
 * where the Auditable trait's automatic approach isn't granular enough.
 */
class AuditService
{
    /**
     * Log a manual audit entry with before/after tracking.
     */
    public static function log(
        string $action,
        string $description,
        string $subjectType,
        int $subjectId,
        ?array $before = null,
        ?array $after = null,
    ): void {
        $fingerprint = DeviceFingerprint::context();

        ActivityLog::create([
            'user_id'      => auth()?->id(),
            'action'       => $action,
            'description'  => $description,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'properties'   => [
                'before'  => $before,
                'after'   => $after,
                'changed' => $before
                    ? array_keys(array_diff_assoc($after ?? [], $before))
                    : [],
                'fingerprint' => $fingerprint,
            ],
            'ip_address'   => $fingerprint['ip_address'] ?? request()?->ip(),
            'user_agent'   => $fingerprint['user_agent'] ?? request()?->userAgent(),
        ]);
    }

    /**
     * Log a payroll-specific change (for approval, processing, etc.)
     */
    public static function payrollChange(string $action, int $payrollId, string $description, array $changes = []): void
    {
        static::log(
            action: "payroll_{$action}",
            description: $description,
            subjectType: 'Payroll',
            subjectId: $payrollId,
            after: $changes,
        );
    }

    /**
     * Log an approval action.
     */
    public static function approval(string $action, string $approvableType, int $approvableId, array $context = []): void
    {
        static::log(
            action: "approval_{$action}",
            description: "{$approvableType} #{$approvableId}: {$action}",
            subjectType: $approvableType,
            subjectId: $approvableId,
            after: $context,
        );
    }
}
