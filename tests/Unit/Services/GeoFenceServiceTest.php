<?php

namespace Tests\Unit\Services;

use App\Services\GeoFenceService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GeoFenceServiceTest extends TestCase
{
    private GeoFenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GeoFenceService();
    }

    #[Test]
    public function haversine_distance_between_same_point_is_zero(): void
    {
        $distance = $this->service->haversineDistance(-7.0456, 112.7654, -7.0456, 112.7654);
        $this->assertEquals(0.0, $distance);
    }

    #[Test]
    public function haversine_distance_kantor_bangkalan_to_surabaya(): void
    {
        // Bangkalan, Madura
        $lat1 = -7.0456;
        $lon1 = 112.7654;

        // Surabaya (Tunjungan)
        $lat2 = -7.2653;
        $lon2 = 112.7428;

        $distance = $this->service->haversineDistance($lat1, $lon1, $lat2, $lon2);

        // Should be approximately 24-25 km
        $this->assertGreaterThan(20000, $distance);
        $this->assertLessThan(30000, $distance);
    }

    #[Test]
    public function haversine_distance_bangkalan_to_jakarta(): void
    {
        // Bangkalan
        $lat1 = -7.0456;
        $lon1 = 112.7654;

        // Jakarta (Monas)
        $lat2 = -6.1754;
        $lon2 = 106.8272;

        $distance = $this->service->haversineDistance($lat1, $lon1, $lat2, $lon2);

        // Should be approximately 660-700 km
        $this->assertGreaterThan(600000, $distance);
        $this->assertLessThan(750000, $distance);
    }

    #[Test]
    public function haversine_distance_equator_to_prime_meridian(): void
    {
        // Equator at 0°, Prime Meridian at 0°
        $distance = $this->service->haversineDistance(0, 0, 0, 0);
        $this->assertEquals(0.0, $distance);
    }

    #[Test]
    public function haversine_distance_north_to_south_pole(): void
    {
        // North Pole to Equator
        $distance = $this->service->haversineDistance(90, 0, 0, 0);
        // Should be approximately 1/4 of Earth's circumference = ~10,000km
        $this->assertGreaterThan(9000000, $distance);
        $this->assertLessThan(11000000, $distance);
    }

    #[Test]
    public function haversine_distance_antar_kota_madura(): void
    {
        // Bangkalan
        $lat1 = -7.0456;
        $lon1 = 112.7654;

        // Sampang
        $lat2 = -7.1911;
        $lon2 = 113.2400;

        $distance = $this->service->haversineDistance($lat1, $lon1, $lat2, $lon2);

        // Approximately 55 km
        $this->assertGreaterThan(45000, $distance);
        $this->assertLessThan(65000, $distance);
    }

    #[Test]
    public function haversine_distance_is_symmetric(): void
    {
        $lat1 = -7.0456;
        $lon1 = 112.7654;
        $lat2 = -6.1754;
        $lon2 = 106.8272;

        $distanceAB = $this->service->haversineDistance($lat1, $lon1, $lat2, $lon2);
        $distanceBA = $this->service->haversineDistance($lat2, $lon2, $lat1, $lon1);

        $this->assertEquals($distanceAB, $distanceBA);
    }

    #[Test]
    public function haversine_distance_small_shift_is_reasonable(): void
    {
        // Shift of ~0.01 degrees (~1.1 km)
        $distance = $this->service->haversineDistance(-7.0456, 112.7654, -7.0456, 112.7754);

        $this->assertGreaterThan(800, $distance);
        $this->assertLessThan(1500, $distance);
    }

    #[Test]
    public function haversine_distance_100_meters(): void
    {
        // Very small shift (~0.0009 degrees ≈ 100m)
        $distance = $this->service->haversineDistance(-7.0456, 112.7654, -7.0456, 112.7663);

        $this->assertGreaterThan(80, $distance);
        $this->assertLessThan(150, $distance);
    }
}
