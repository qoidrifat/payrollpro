<?php

namespace App\Traits;

use App\Models\ActivityLog;
use App\Services\DeviceFingerprint;

/**
 * Trait for models that require audit logging of changes.
 *
 * Add to any Eloquent model to automatically track create/update/delete
 * events with before/after value snapshots stored in ActivityLog.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            $model->auditLog('created', null, $model->getAuditableAttributes());
        });

        static::updated(function ($model) {
            $before = array_intersect_key($model->getOriginal(), $model->getAuditableAttributes());
            $after = $model->getAuditableAttributes();
            $model->auditLog('updated', $before, $after);
        });

        static::deleted(function ($model) {
            $model->auditLog('deleted', $model->getAuditableAttributes(), null);
        });
    }

    /**
     * Attributes to track for changes. Override in model to customize.
     */
    protected function getAuditableAttributes(): array
    {
        $exclude = ['id', 'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token'];
        return array_diff_key($this->getAttributes(), array_flip($exclude));
    }

    protected function auditLog(string $action, ?array $before, ?array $after): void
    {
        $fingerprint = DeviceFingerprint::context();

        $beforeScalar = $before ? $this->scalarizeValues($before) : null;
        $afterScalar = $after ? $this->scalarizeValues($after) : null;

        ActivityLog::create([
            'user_id'      => auth()?->id(),
            'action'       => $action,
            'description'  => $this->getAuditDescription($action),
            'subject_type' => static::class,
            'subject_id'   => $this->getKey(),
            'properties'   => [
                'before'     => $beforeScalar,
                'after'      => $afterScalar,
                'changed'    => ($beforeScalar && $afterScalar)
                    ? array_keys(array_diff_assoc($afterScalar, $beforeScalar))
                    : [],
                'fingerprint'=> $fingerprint,
            ],
            'ip_address'   => $fingerprint['ip_address'],
            'user_agent'   => $fingerprint['user_agent'],
        ]);
    }

    /**
     * Convert enum objects and other non-scalar values to their scalar
     * equivalents so array_diff_assoc can safely compare them.
     */
    private function scalarizeValues(array $values): array
    {
        return array_map(function ($value) {
            if ($value instanceof \BackedEnum) {
                return $value->value;
            }
            if ($value instanceof \UnitEnum) {
                return $value->name;
            }
            if ($value instanceof \DateTimeInterface) {
                return $value->format('Y-m-d H:i:s');
            }
            return $value;
        }, $values);
    }

    protected function getAuditDescription(string $action): string
    {
        $modelName = class_basename(static::class);
        $identifier = $this->getAuditIdentifier();
        return "{$modelName} '{$identifier}' was {$action}";
    }

    /**
     * Human-readable identifier for the model. Override to customize.
     */
    protected function getAuditIdentifier(): string
    {
        return $this->name ?? $this->id ?? 'unknown';
    }
}
