<?php

namespace Tests\Unit;

use Tests\ActionTestCase;

class HomeControllerIndexTest extends ActionTestCase
{

    public function getRouteName(): string
    {
        return 'home.index';
    }

    /**
     * @test
     */
    public function testMiddleware()
    {
        $this->assertRouteContainsMiddleware(
            "web"
        );
    }

    public function testPageLoaded()
    {
        $this->callRouteAction()
            ->assertSeeText("Property Market Tracker");
    }
}
