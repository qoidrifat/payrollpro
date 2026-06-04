<?php

namespace Tests\Unit\Services;

use App\Services\PayrollTrendAnalyzer;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PayrollTrendAnalyzerTest extends TestCase
{
    private PayrollTrendAnalyzer $analyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyzer = new PayrollTrendAnalyzer();
    }

    #[Test]
    public function pct_change_with_zero_old_returns_null(): void
    {
        $result = $this->invokePrivateMethod($this->analyzer, 'pctChange', [0, 100]);
        $this->assertNull($result);
    }

    #[Test]
    public function pct_change_positive(): void
    {
        $result = $this->invokePrivateMethod($this->analyzer, 'pctChange', [100, 150]);
        $this->assertEquals(50.0, $result);
    }

    #[Test]
    public function pct_change_negative(): void
    {
        $result = $this->invokePrivateMethod($this->analyzer, 'pctChange', [200, 150]);
        $this->assertEquals(-25.0, $result);
    }

    #[Test]
    public function pct_change_zero_percent(): void
    {
        $result = $this->invokePrivateMethod($this->analyzer, 'pctChange', [100, 100]);
        $this->assertEquals(0.0, $result);
    }

    #[Test]
    public function pct_change_large_value(): void
    {
        $result = $this->invokePrivateMethod($this->analyzer, 'pctChange', [5000000, 7500000]);
        $this->assertEquals(50.0, $result);
    }

    #[Test]
    public function assessHealth_returns_healthy_for_no_spikes(): void
    {
        $spikes = [];
        $yoy = ['changes' => ['total_net' => 5.0]];

        $health = $this->invokePrivateMethod($this->analyzer, 'assessHealth', [$spikes, $yoy]);
        $this->assertEquals('healthy', $health);
    }

    #[Test]
    public function assessHealth_returns_warning_for_yoy_over_30(): void
    {
        $spikes = [];
        $yoy = ['changes' => ['total_net' => 35.0]];

        $health = $this->invokePrivateMethod($this->analyzer, 'assessHealth', [$spikes, $yoy]);
        $this->assertEquals('warning', $health);
    }

    #[Test]
    public function assessHealth_returns_attention_for_one_spike(): void
    {
        $spikes = [['pct_change' => 40.0]];
        $yoy = ['changes' => ['total_net' => 5.0]];

        $health = $this->invokePrivateMethod($this->analyzer, 'assessHealth', [$spikes, $yoy]);
        $this->assertEquals('attention', $health);
    }

    #[Test]
    public function assessHealth_returns_warning_for_three_spikes(): void
    {
        $spikes = [
            ['pct_change' => 40.0],
            ['pct_change' => 35.0],
            ['pct_change' => 45.0],
        ];
        $yoy = ['changes' => ['total_net' => 5.0]];

        $health = $this->invokePrivateMethod($this->analyzer, 'assessHealth', [$spikes, $yoy]);
        $this->assertEquals('warning', $health);
    }

    #[Test]
    public function assessHealth_returns_critical_for_spikes_over_50(): void
    {
        $spikes = [
            ['pct_change' => 60.0],
            ['pct_change' => 55.0],
        ];
        $yoy = ['changes' => ['total_net' => 5.0]];

        $health = $this->invokePrivateMethod($this->analyzer, 'assessHealth', [$spikes, $yoy]);
        $this->assertEquals('critical', $health);
    }

    #[Test]
    public function enrichWithTrends_single_month_returns_without_trend(): void
    {
        $results = [
            ['month' => '2026-01', 'total_net' => 50000000],
        ];

        $enriched = $this->invokePrivateMethod($this->analyzer, 'enrichWithTrends', [$results]);

        $this->assertCount(1, $enriched);
        // Single element has no previous month to compare, so trend key is not added
        $this->assertArrayNotHasKey('trend', $enriched[0]);
    }

    #[Test]
    public function enrichWithTrends_detects_up_trend(): void
    {
        $results = [
            ['month' => '2026-01', 'total_net' => 50000000],
            ['month' => '2026-02', 'total_net' => 60000000], // +20% = up
        ];

        $enriched = $this->invokePrivateMethod($this->analyzer, 'enrichWithTrends', [$results]);

        $this->assertCount(2, $enriched);
        $this->assertEquals('up', $enriched[1]['trend']);
        $this->assertEqualsWithDelta(20.0, $enriched[1]['pct_change'], 0.1);
    }

    #[Test]
    public function enrichWithTrends_detects_down_trend(): void
    {
        $results = [
            ['month' => '2026-01', 'total_net' => 100000000],
            ['month' => '2026-02', 'total_net' => 80000000], // -20% = down
        ];

        $enriched = $this->invokePrivateMethod($this->analyzer, 'enrichWithTrends', [$results]);

        $this->assertCount(2, $enriched);
        $this->assertEquals('down', $enriched[1]['trend']);
        $this->assertEqualsWithDelta(-20.0, $enriched[1]['pct_change'], 0.1);
    }

    #[Test]
    public function enrichWithTrends_detects_stable_trend(): void
    {
        $results = [
            ['month' => '2026-01', 'total_net' => 50000000],
            ['month' => '2026-02', 'total_net' => 52000000], // +4% = stable
        ];

        $enriched = $this->invokePrivateMethod($this->analyzer, 'enrichWithTrends', [$results]);

        $this->assertCount(2, $enriched);
        $this->assertEquals('stable', $enriched[1]['trend']);
    }

    private function invokePrivateMethod(object $object, string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionMethod($object, $methodName);
        $reflection->setAccessible(true);
        return $reflection->invoke($object, ...$parameters);
    }
}
