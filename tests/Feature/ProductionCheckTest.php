<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_configuration_does_not_receive_production_approval(): void
    {
        $this->artisan('enlink:production-check')
            ->expectsOutputToContain('todavía no está listo')
            ->assertFailed();
    }
}
