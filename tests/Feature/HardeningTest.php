<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_responses_include_security_headers(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader('Content-Security-Policy', "base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'");
    }

    public function test_readiness_endpoint_checks_required_services(): void
    {
        DB::shouldReceive('select')->once()->with('select 1')->andReturn([(object) ['1' => 1]]);
        Cache::shouldReceive('get')->once()->with('health:ready')->andReturnNull();

        $this->getJson('/health/ready')
            ->assertOk()
            ->assertExactJson([
                'status' => 'ready',
                'checks' => ['database' => 'ok', 'cache' => 'ok'],
            ]);
    }

    public function test_tls_terminated_by_the_trusted_proxy_enables_hsts(): void
    {
        $this->withHeader('X-Forwarded-Proto', 'https')
            ->get('/')
            ->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_readiness_endpoint_does_not_expose_internal_error_details(): void
    {
        DB::shouldReceive('select')->once()->andThrow(new \RuntimeException('secret connection details'));

        $this->getJson('/health/ready')
            ->assertStatus(503)
            ->assertExactJson(['status' => 'unavailable'])
            ->assertDontSee('secret connection details');
    }
}
