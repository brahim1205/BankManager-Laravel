<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test the health check endpoint
     */
    public function test_health_check_endpoint()
    {
        $response = $this->getJson('/api/v1/status');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'API opérationnelle',
                'data' => [
                    'status' => 'healthy',
                    'environment' => config('app.env'),
                ]
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'status',
                    'timestamp',
                    'version',
                    'environment'
                ]
            ]);
    }
}
