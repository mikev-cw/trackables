<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    public function test_protected_api_route_returns_json_unauthorized_instead_of_redirect(): void
    {
        $response = $this->getJson('/api/trackable');

        $response->assertStatus(401);
        $response->assertExactJson([
            'message' => 'Unauthorized',
        ]);
    }
}
