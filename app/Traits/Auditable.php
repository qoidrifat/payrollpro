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

        // Deteksi perubahan dihitung dari nilai asli agar tetap akurat,
        // baru nilai sensitif diredaksi sebelum disimpan ke log.
        $changed = ($beforeScalar && $afterScalar)
            ? $this->changedAuditKeys($beforeScalar, $afterScalar)
            : [];

        ActivityLog::create([
            'user_id' => auth()?->id(),
            'action' => $action,
            'description' => $this->getAuditDescription($action),
            'subject_type' => static::class,
            'subject_id' => $this->getKey(),
            'properties' => [
                'before' => $beforeScalar ? $this->redactAuditValues($beforeScalar) : null,
                'after' => $afterScalar ? $this->redactAuditValues($afterScalar) : null,
                'changed' => $changed,
                'fingerprint' => $fingerprint,
            ],
            'ip_address' => $fingerprint['ip_address'],
            'user_agent' => $fingerprint['user_agent'],
        ]);
    }

    /**
     * Attribute names whose values must never be written to the audit log
     * (PII / secrets / encrypted identity & financial fields). The audit log
     * still records THAT the field changed, but replaces the value with a
     * redaction marker. Override in the model to customize.
     */
    protected function auditRedactedAttributes(): array
    {
        return [];
    }

    private function redactAuditValues(array $values): array
    {
        foreach ($this->auditRedactedAttributes() as $key) {
            if (array_key_exists($key, $values) && $values[$key] !== null) {
                $values[$key] = '[redacted]';
            }
        }

        return $values;
    }

    /**
     * Convert enum objects and other non-scalar values to their scalar
     * equivalents so array_diff_assoc can safely compare them.
     */
    private function scalarizeValues(array $values): array
    {
        return array_map(fn ($value) => $this->scalarizeAuditValue($value), $values);
    }

    private function scalarizeAuditValue(mixed $value): mixed
    {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_array($value)) {
            return $this->scalarizeValues($value);
        }

        if (is_object($value)) {
            return method_exists($value, '__toString')
                ? (string) $value
                : $this->scalarizeValues(get_object_vars($value));
        }

        return $value;
    }

    private function changedAuditKeys(array $before, array $after): array
    {
        $keys = array_unique([...array_keys($before), ...array_keys($after)]);

        return array_values(array_filter($keys, function (string|int $key) use ($before, $after) {
            if (! array_key_exists($key, $before) || ! array_key_exists($key, $after)) {
                return true;
            }

            return $this->comparableAuditValue($before[$key]) !== $this->comparableAuditValue($after[$key]);
        }));
    }

    private function comparableAuditValue(mixed $value): string
    {
        if (is_array($value)) {
            ksort($value);
        }

        $encoded = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return $encoded === false ? serialize($value) : $encoded;
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
