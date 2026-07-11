<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\CarbonImmutable;

class AttendanceOperationalHours
{
    public function timezone(): string
    {
        // Check DB setting first (settable via Settings page), fallback to config
        return Setting::getValue('attendance_timezone')
            ?? config('attendance.operational_hours.timezone', config('app.timezone', 'Asia/Jakarta'));
    }

    public function start(): string
    {
        return Setting::getValue('attendance_operational_start')
            ?? config('attendance.operational_hours.start', '06:30');
    }

    public function end(): string
    {
        return Setting::getValue('attendance_operational_end')
            ?? config('attendance.operational_hours.end', '17:00');
    }

    public function now(): CarbonImmutable
    {
        return CarbonImmutable::now($this->timezone());
    }

    public function isOperational(?CarbonImmutable $now = null): bool
    {
        $now ??= $this->now();

        return $now->greaterThanOrEqualTo($this->timeOnDate($now, $this->start()))
            && $now->lessThanOrEqualTo($this->timeOnDate($now, $this->end()));
    }

    public function nextStart(?CarbonImmutable $now = null): CarbonImmutable
    {
        $now ??= $this->now();
        $startToday = $this->timeOnDate($now, $this->start());

        if ($now->lessThan($startToday)) {
            return $startToday;
        }

        return $startToday->addDay();
    }

    public function props(?CarbonImmutable $now = null): array
    {
        $now ??= $this->now();
        $isOperational = $this->isOperational($now);

        return [
            'is_operational_hours' => $isOperational,
            'server_time' => $now->toIso8601String(),
            'operational_start' => $this->start(),
            'operational_end' => $this->end(),
            'timezone' => $this->timezone(),
            'next_operational_start' => $isOperational ? null : $this->nextStart($now)->toIso8601String(),
        ];
    }

    public function label(): string
    {
        return sprintf('%s - %s %s', $this->start(), $this->end(), $this->timezoneAbbreviation());
    }

    /**
     * Human-friendly timezone abbreviation for the configured timezone.
     * Falls back to the raw timezone identifier for non-Indonesian zones.
     */
    public function timezoneAbbreviation(): string
    {
        return match ($this->timezone()) {
            'Asia/Jakarta', 'Asia/Pontianak' => 'WIB',
            'Asia/Makassar' => 'WITA',
            'Asia/Jayapura' => 'WIT',
            default => $this->timezone(),
        };
    }

    private function timeOnDate(CarbonImmutable $date, string $time): CarbonImmutable
    {
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return $date->setTime($hour, $minute);
    }
}
