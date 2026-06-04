<?php

namespace Tests\Unit\Services;

use App\Services\PayrollAnomalyDetector;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PayrollAnomalyDetectorTest extends TestCase
{
    private PayrollAnomalyDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new PayrollAnomalyDetector();
    }

    #[Test]
    public function standard_deviation_of_identical_values_is_zero(): void
    {
        $values = [5000000, 5000000, 5000000, 5000000, 5000000];

        $stdDev = $this->invokePrivateMethod($this->detector, 'standardDeviation', [$values, 5000000]);

        $this->assertEquals(0.0, $stdDev);
    }

    #[Test]
    public function standard_deviation_of_increasing_values(): void
    {
        $values = [1, 2, 3, 4, 5];

        $stdDev = $this->invokePrivateMethod($this->detector, 'standardDeviation', [$values, 3.0]);

        // Population stddev of [1,2,3,4,5] = sqrt(2) ≈ 1.414
        $this->assertEqualsWithDelta(1.414, $stdDev, 0.01);
    }

    #[Test]
    public function standard_deviation_with_null_mean_calculates_automatically(): void
    {
        $values = [10, 20, 30, 40, 50];

        $stdDev = $this->invokePrivateMethod($this->detector, 'standardDeviation', [$values]);

        // Mean = 30, squared diffs = [400,100,0,100,400], var = 200, stddev = 14.142
        $this->assertEqualsWithDelta(14.142, $stdDev, 0.01);
    }

    #[Test]
    public function median_of_odd_count(): void
    {
        $values = [1, 3, 5, 7, 9];

        $median = $this->invokePrivateMethod($this->detector, 'median', [$values]);

        $this->assertEquals(5.0, $median);
    }

    #[Test]
    public function median_of_even_count(): void
    {
        $values = [1, 2, 3, 4];

        $median = $this->invokePrivateMethod($this->detector, 'median', [$values]);

        $this->assertEquals(2.5, $median);
    }

    #[Test]
    public function median_of_unsorted_values(): void
    {
        $values = [9, 1, 7, 3, 5];

        $median = $this->invokePrivateMethod($this->detector, 'median', [$values]);

        $this->assertEquals(5.0, $median);
    }

    #[Test]
    public function median_of_single_value(): void
    {
        $values = [42];

        $median = $this->invokePrivateMethod($this->detector, 'median', [$values]);

        $this->assertEquals(42.0, $median);
    }

    #[Test]
    public function median_of_two_values(): void
    {
        $values = [10, 20];

        $median = $this->invokePrivateMethod($this->detector, 'median', [$values]);

        $this->assertEquals(15.0, $median);
    }

    #[Test]
    public function median_absolute_deviation_of_identical_values(): void
    {
        $values = [5, 5, 5, 5, 5];

        $mad = $this->invokePrivateMethod($this->detector, 'medianAbsoluteDeviation', [$values, 5.0]);

        $this->assertEquals(0.0, $mad);
    }

    #[Test]
    public function median_absolute_deviation_of_distributed_values(): void
    {
        $values = [1, 2, 3, 4, 100]; // 100 is obvious outlier
        // Median = 3, deviations = [2,1,0,1,97], median of deviations = 1

        $mad = $this->invokePrivateMethod($this->detector, 'medianAbsoluteDeviation', [$values, 3.0]);

        $this->assertEquals(1.0, $mad);
    }

    #[Test]
    public function standard_deviation_handles_large_salary_values(): void
    {
        // Simulate typical salary distribution
        $values = [5000000, 5500000, 4800000, 5200000, 5100000, 5300000, 4900000];
        $mean = array_sum($values) / count($values);

        $stdDev = $this->invokePrivateMethod($this->detector, 'standardDeviation', [$values, $mean]);

        $this->assertGreaterThan(0, $stdDev);
        $this->assertLessThan(500000, $stdDev); // Should be in reasonable range
    }

    #[Test]
    public function standard_deviation_is_not_nan_for_identical_values(): void
    {
        $values = [5000000, 5000000, 5000000, 5000000, 5000000];

        $stdDev = $this->invokePrivateMethod($this->detector, 'standardDeviation', [$values]);

        $this->assertTrue(is_float($stdDev));
        $this->assertFalse(is_nan($stdDev));
    }

    private function invokePrivateMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionMethod($object, $methodName);
        $reflection->setAccessible(true);
        return $reflection->invoke($object, ...$parameters);
    }
}
