<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomeRedirectTest extends TestCase
{
    public function test_guest_is_redirected_from_home_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login'));
    }
}
