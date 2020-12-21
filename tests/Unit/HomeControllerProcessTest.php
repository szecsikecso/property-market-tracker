<?php

namespace Tests\Unit;

use Tests\ActionTestCase;

class HomeControllerProcessTest extends ActionTestCase
{

    public function getRouteName(): string
    {
        return 'home.process';
    }

    /**
     * @test
     */
    public function testMiddleware()
    {
        $this->assertRouteContainsMiddleware(
            'web'
        );
    }

    /**
     * @test
     */
    public function testPostcodeIsRequired()
    {
        $this->callRouteAction()
            ->assertJsonValidationErrors([
                'postcode' => 'The postcode field is required.',
            ]);
    }

    /**
     * @test
     */
    public function testPostcodeIsString()
    {
        $this->callRouteAction([
            'postcode' => 123
        ])
            ->assertJsonValidationErrors([
                'postcode' => 'The postcode field must be filled with a valid UK postcode.',
            ]);
    }

    /**
     * @test
     */
    public function testPostcodeIsValid()
    {
        $this->callRouteAction([
            'postcode' => 'test123'
        ])
            ->assertJsonValidationErrors([
                'postcode' => 'The postcode field must be filled with a valid UK postcode.',
            ]);
    }

    /**
     * Using examples provided:
     * https://en.wikipedia.org/wiki/Postcodes_in_the_United_Kingdom
     *
     * @test
     */
    public function testValidPostcodeEmptyResult()
    {
        $this->callRouteAction([
            'postcode' => 'EC1A 1BB'
        ])
            //->assertSeeText('asd')
            ->assertSeeText('Property Market Tracker')
            ->assertSeeText('Postcode identified!')
            ->assertSeeText('Result count for the provided postcode without filtering: 0')
            ->assertDontSeeText('EC1A 1BB');
    }

    /**
     * Using examples provided:
     * https://en.wikipedia.org/wiki/Postcodes_in_the_United_Kingdom
     *
     * @test
     */
    public function testValidPostcodeNonEmptyResult()
    {
        $this->callRouteAction([
            'postcode' => 'M1 1AE'
        ])
            ->assertSeeText('Property Market Tracker')
            ->assertSeeText('Postcode identified!')
            ->assertSeeText('Result count for the provided postcode without filtering: 20')
            ->assertSeeText('M1 1AE');
    }

    /**
     * Using examples provided:
     * https://en.wikipedia.org/wiki/Postcodes_in_the_United_Kingdom
     *
     * @test
     */
    public function testValidPostcodeNonEmptyResult_UsingAllFilters()
    {
        $this->callRouteAction([
            'postcode' => 'M1 1AE',
            'radius' => 0.1,
            'soldIn' => 29,
            'propertyType' => 'FLAT',
            'tenure' => 'LEASEHOLD',
            'sortBy' => 'ADDRESS',
        ])
            ->assertSeeText('Property Market Tracker')
            ->assertSeeText('Postcode identified!')
            ->assertSeeText('Result count for the provided postcode without filtering: 20')
            ->assertSeeText('M1 1AE');
    }

    /**
     * Using examples provided:
     * https://en.wikipedia.org/wiki/Postcodes_in_the_United_Kingdom
     *
     * @test
     */
    public function testValidPostcodeNonEmptyResult_UsingInvalidRadius()
    {
        $this->callRouteAction([
            'postcode' => 'M1 1AE',
            'radius' => 16,
        ])
            ->assertJsonValidationErrors([
                'radius' => 'The radius must be between 0 and 15.',
            ]);
    }

    /**
     * Using examples provided:
     * https://en.wikipedia.org/wiki/Postcodes_in_the_United_Kingdom
     *
     * @test
     */
    public function testValidPostcodeNonEmptyResult_UsingInvalidRadius2()
    {
        $this->callRouteAction([
            'postcode' => 'M1 1AE',
            'radius' => 'e',
        ])
            ->assertJsonValidationErrors([
                'radius' => 'The radius must be a number',
            ]);
    }

    /**
     * Using examples provided:
     * https://en.wikipedia.org/wiki/Postcodes_in_the_United_Kingdom
     *
     * @test
     */
    public function testValidPostcodeNonEmptyResult_UsingInvalidSoldIn()
    {
        $this->callRouteAction([
            'postcode' => 'M1 1AE',
            'soldIn' => 32,
        ])
            ->assertJsonValidationErrors([
                'soldIn' => 'The sold in must be between 0 and 30.',
            ]);
    }

    /**
     * Using examples provided:
     * https://en.wikipedia.org/wiki/Postcodes_in_the_United_Kingdom
     *
     * @test
     */
    public function testValidPostcodeNonEmptyResult_UsingInvalidSoldIn2()
    {
        $this->callRouteAction([
            'postcode' => 'M1 1AE',
            'soldIn' => 29.99,
        ])
            ->assertJsonValidationErrors([
                'soldIn' => 'The sold in must be an integer.',
            ]);
    }
}
