<?php

namespace App\Enums;

enum ServiceStatus: string
{
    case Operational = 'operational';
    case DegradedPerformance = 'degraded_performance';
    case PartialOutage = 'partial_outage';
    case MajorOutage = 'major_outage';
    case Maintenance = 'maintenance';

    public function label(): string
    {
        return match ($this) {
            self::Operational         => 'Operational',
            self::DegradedPerformance => 'Degraded Performance',
            self::PartialOutage       => 'Partial Outage',
            self::MajorOutage         => 'Major Outage',
            self::Maintenance         => 'Under Maintenance',
        };
    }

    public function severity(): int
    {
        return match ($this) {
            self::Operational         => 0,
            self::DegradedPerformance => 1,
            self::PartialOutage       => 2,
            self::MajorOutage         => 3,
            self::Maintenance         => 2,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Operational         => 'emerald',
            self::DegradedPerformance => 'amber',
            self::PartialOutage       => 'orange',
            self::MajorOutage         => 'red',
            self::Maintenance         => 'blue',
        };
    }
}
