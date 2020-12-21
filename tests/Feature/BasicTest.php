<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BasicTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testPageLoad()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
