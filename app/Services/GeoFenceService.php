<?php

namespace App\Services;

use App\Models\OfficeLocation;
use App\Scopes\TenantScope;

class GeoFenceService
{
    /**
     * Check if given coordinates are within any active office location.
     *
     * Returns the matching office, or null if outside all geo-fences.
     */
    public function findMatchingOffice(
        float $latitude,
        float $longitude,
        ?int $companyId = null
    ): ?OfficeLocation {
        $companyId ??= TenantScope::currentCompanyId();

        $locations = OfficeLocation::active()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->get();

        foreach ($locations as $office) {
            $distance = $this->haversineDistance(
                $latitude, $longitude,
                (float) $office->latitude, (float) $office->longitude
            );

            if ($distance <= $office->getEffectiveRadius()) {
                return $office;
            }
        }

        return null;
    }

    /**
     * Validate attendance GPS within office radius.
     * Returns ['valid' => bool, 'distance' => float, 'office' => ?string, 'radius' => int]
     */
    public function validateLocation(
        float $latitude,
        float $longitude,
        ?int $companyId = null
    ): array {
        $office = $this->findMatchingOffice($latitude, $longitude, $companyId);

        if ($office) {
            $distance = $this->haversineDistance(
                $latitude, $longitude,
                (float) $office->latitude, (float) $office->longitude
            );

            return [
                'valid'    => true,
                'distance' => round($distance, 1),
                'office'   => $office->name,
                'radius'   => $office->getEffectiveRadius(),
            ];
        }

        // Find the closest office for error reporting
        $closest = OfficeLocation::active()
            ->when($companyId, fn($q) => $q->where('company_id', $companyId))
            ->get()
            ->map(fn($o) => [
                'office'   => $o->name,
                'distance' => $this->haversineDistance(
                    $latitude, $longitude,
                    (float) $o->latitude, (float) $o->longitude
                ),
            ])
            ->sortBy('distance')
            ->first();

        return [
            'valid'      => false,
            'distance'   => $closest ? round($closest['distance'], 1) : null,
            'office'     => null,
            'closest'    => $closest,
        ];
    }

    /**
     * Haversine formula — calculate distance between two GPS coordinates in meters.
     */
    public function haversineDistance(
        float $lat1, float $lon1,
        float $lat2, float $lon2
    ): float {
        $earthRadius = 6371000; // meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo   = deg2rad($lat2);
        $lonTo   = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2)
            + cos($latFrom) * cos($latTo)
            * sin($lonDelta / 2) * sin($lonDelta / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }
}
