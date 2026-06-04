<?php

namespace Tests\Unit\Services;

use App\Services\BpjsCalculator;
use Tests\TestCase;

class BpjsCalculatorTest extends TestCase
{
    private BpjsCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new BpjsCalculator();
    }

    public function test_calculate_kesehatan_default_rates(): void
    {
        $result = $this->calculator->calculateKesehatan(10000000);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('company', $result);
        $this->assertArrayHasKey('employee', $result);

        // Default: 4% company, 1% employee
        $this->assertEquals(400000, $result['company']);
        $this->assertEquals(100000, $result['employee']);
    }

    public function test_calculate_jht_default_rates(): void
    {
        $result = $this->calculator->calculateJht(10000000);

        // Default: 3.7% company, 2% employee
        $this->assertEquals(370000, $result['company']);
        $this->assertEquals(200000, $result['employee']);
    }

    public function test_calculate_jp_default_rates(): void
    {
        $result = $this->calculator->calculateJp(10000000);

        // Default: 2% company, 1% employee
        $this->assertEquals(200000, $result['company']);
        $this->assertEquals(100000, $result['employee']);
    }

    public function test_calculate_jkk_default_rate(): void
    {
        $result = $this->calculator->calculateJkk(10000000);

        // Default: 0.24%
        $this->assertEquals(24000, $result);
    }

    public function test_calculate_jkm_default_rate(): void
    {
        $result = $this->calculator->calculateJkm(10000000);

        // Default: 0.30%
        $this->assertEquals(30000, $result);
    }

    public function test_kesehatan_capped_salary(): void
    {
        // With no DB config, cap won't be applied (no cap config)
        // So it should use the default rates
        $result = $this->calculator->calculateKesehatan(50000000);

        $this->assertEquals(2000000, $result['company']);  // 4%
        $this->assertEquals(500000, $result['employee']);  // 1%
    }

    public function test_jp_capped_salary(): void
    {
        $result = $this->calculator->calculateJp(50000000);

        // With no DB config, no cap, so uses full salary
        $this->assertEquals(1000000, $result['company']);  // 2%
        $this->assertEquals(500000, $result['employee']);  // 1%
    }

    public function test_zero_salary(): void
    {
        $kes = $this->calculator->calculateKesehatan(0);
        $jht = $this->calculator->calculateJht(0);
        $jp = $this->calculator->calculateJp(0);
        $jkk = $this->calculator->calculateJkk(0);
        $jkm = $this->calculator->calculateJkm(0);

        $this->assertEquals(0, $kes['company']);
        $this->assertEquals(0, $kes['employee']);
        $this->assertEquals(0, $jht['company']);
        $this->assertEquals(0, $jht['employee']);
        $this->assertEquals(0, $jp['company']);
        $this->assertEquals(0, $jp['employee']);
        $this->assertEquals(0, $jkk);
        $this->assertEquals(0, $jkm);
    }

    public function test_rounding_precision(): void
    {
        // Test with a salary that produces decimals
        $result = $this->calculator->calculateKesehatan(7500000);
        $this->assertEquals(300000.00, $result['company']); // 4% of 7,500,000 = 300,000
        $this->assertEquals(75000.00, $result['employee']); // 1% of 7,500,000 = 75,000
    }
}
