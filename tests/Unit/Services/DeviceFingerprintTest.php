<?php

namespace Tests\Unit\Services;

use App\Services\DeviceFingerprint;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DeviceFingerprintTest extends TestCase
{
    #[Test]
    public function get_returns_consistent_hash_for_same_request(): void
    {
        $request = new Request([], [], [], [], [], [
            'REMOTE_ADDR'       => '192.168.1.1',
            'HTTP_USER_AGENT'   => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US,en;q=0.9',
            'HTTP_ACCEPT_ENCODING' => 'gzip, deflate, br',
        ]);

        $fingerprint1 = DeviceFingerprint::get($request);
        $fingerprint2 = DeviceFingerprint::get($request);

        $this->assertEquals($fingerprint1, $fingerprint2);
    }

    #[Test]
    public function get_returns_different_hash_for_different_ip(): void
    {
        $request1 = new Request([], [], [], [], [], [
            'REMOTE_ADDR'       => '192.168.1.1',
            'HTTP_USER_AGENT'   => 'Mozilla/5.0',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US',
            'HTTP_ACCEPT_ENCODING' => 'gzip',
        ]);

        $request2 = new Request([], [], [], [], [], [
            'REMOTE_ADDR'       => '10.0.0.1',
            'HTTP_USER_AGENT'   => 'Mozilla/5.0',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US',
            'HTTP_ACCEPT_ENCODING' => 'gzip',
        ]);

        $this->assertNotEquals(
            DeviceFingerprint::get($request1),
            DeviceFingerprint::get($request2)
        );
    }

    #[Test]
    public function get_returns_different_hash_for_different_user_agent(): void
    {
        $request1 = new Request([], [], [], [], [], [
            'REMOTE_ADDR'       => '192.168.1.1',
            'HTTP_USER_AGENT'   => 'Chrome/120',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US',
            'HTTP_ACCEPT_ENCODING' => 'gzip',
        ]);

        $request2 = new Request([], [], [], [], [], [
            'REMOTE_ADDR'       => '192.168.1.1',
            'HTTP_USER_AGENT'   => 'Firefox/121',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US',
            'HTTP_ACCEPT_ENCODING' => 'gzip',
        ]);

        $this->assertNotEquals(
            DeviceFingerprint::get($request1),
            DeviceFingerprint::get($request2)
        );
    }

    #[Test]
    public function get_returns_sha256_hash_string(): void
    {
        $request = new Request([], [], [], [], [], [
            'REMOTE_ADDR'       => '192.168.1.1',
            'HTTP_USER_AGENT'   => 'TestAgent',
            'HTTP_ACCEPT_LANGUAGE' => 'id-ID',
            'HTTP_ACCEPT_ENCODING' => 'gzip',
        ]);

        $fingerprint = DeviceFingerprint::get($request);

        // SHA256 hash is 64 hex characters
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $fingerprint);
    }

    #[Test]
    public function get_handles_missing_headers_with_empty_default(): void
    {
        $request = new Request([], [], [], [], [], [
            'REMOTE_ADDR'     => '192.168.1.1',
            'HTTP_USER_AGENT' => 'TestAgent',
        ]);

        $fingerprint = DeviceFingerprint::get($request);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $fingerprint);
    }

    #[Test]
    public function context_returns_expected_keys(): void
    {
        $request = new Request([], [], [], [], [], [
            'REMOTE_ADDR'         => '192.168.1.1',
            'HTTP_USER_AGENT'     => 'TestAgent',
            'HTTP_ACCEPT_LANGUAGE' => 'id-ID',
        ]);

        $context = DeviceFingerprint::context($request);

        $this->assertArrayHasKey('ip_address', $context);
        $this->assertArrayHasKey('user_agent', $context);
        $this->assertArrayHasKey('fingerprint', $context);
        $this->assertArrayHasKey('language', $context);
    }

    #[Test]
    public function context_contains_correct_values(): void
    {
        $request = new Request([], [], [], [], [], [
            'REMOTE_ADDR'         => '10.0.0.1',
            'HTTP_USER_AGENT'     => 'TestAgent/1.0',
            'HTTP_ACCEPT_LANGUAGE' => 'id-ID,en;q=0.9',
        ]);

        $context = DeviceFingerprint::context($request);

        $this->assertEquals('10.0.0.1', $context['ip_address']);
        $this->assertEquals('TestAgent/1.0', $context['user_agent']);
        $this->assertEquals('id-ID,en;q=0.9', $context['language']);
    }

    #[Test]
    public function fingerprint_in_context_matches_get(): void
    {
        $request = new Request([], [], [], [], [], [
            'REMOTE_ADDR'         => '10.0.0.1',
            'HTTP_USER_AGENT'     => 'TestAgent',
            'HTTP_ACCEPT_LANGUAGE' => 'en-US',
            'HTTP_ACCEPT_ENCODING' => 'gzip',
        ]);

        $context = DeviceFingerprint::context($request);
        $direct = DeviceFingerprint::get($request);

        $this->assertEquals($direct, $context['fingerprint']);
    }
}
