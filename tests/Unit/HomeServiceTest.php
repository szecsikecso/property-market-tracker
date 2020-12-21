<?php

namespace Tests\Unit;

use App\Service\HomeService;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Tests\ReflectionFeature;

/**
 * Class HomeServiceTest
 *
 * @TODO Fix required for the mocked Response body remains empty
 *
 * @package Tests\Unit
 */
class HomeServiceTest extends TestCase
{
    use ReflectionFeature;

    private HomeService $service;
    private MockHandler $mock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock = new MockHandler([
            //new Response(200, ['X-Foo' => 'Bar'], 'Hello World'),
            //new RequestException('Error Communicating with Server', new Request('GET', 'test'))
        ]);

        $handler = HandlerStack::create($this->mock);
        $guzzleClient = new Client(['handler' => $handler]);

        $this->service = new HomeService($guzzleClient);
    }

    /**
     * @test
     */
    public function testFilters()
    {
        $filters = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];

        $this->service->setFilters($filters);

        $this->assertEquals(
            $this->service->getFilters(),
            $filters,
        );

        $this->assertEquals(
            "&key1=value1" . "&key2=value2" . "&key3=value3",
            $this->service->printFilters(),
        );
    }

    /**
     * @test
     */
    public function testLocationIdentifierQueryFailure()
    {
        $this->mock->reset();
        $this->mock->append(
            new RequestException('Error Communicating with Server', new Request('GET', 'test'))
        );

        $this->service->getLocationIdentifierByPostcode('M1 1AF');
        $this->assertEquals(-1, $this->service->getLocationIdentifier());
    }

    /**
     * @test
     */
    public function testLocationIdentifierQuerySuccess()
    {
        $this->mock->reset();
        $this->mock->append(
            new Response(200, ['X-Foo' => 'Bar'], 'Hello World')
        );

        $this->service->getLocationIdentifierByPostcode('M1 1AF');
        $this->assertEquals(-1, $this->service->getLocationIdentifier());
    }

    /**
     * @test
     */
    public function testLocationIdentifierPattern_WithEmptyHtml()
    {
        $method = self::getMethodOfClass(
            'getLocationIdentifierFromHtml',
            HomeService::class,
        );

        try {
            $locationIdentifier = $method->invokeArgs($this->service, ['']);

            $this->assertEquals(
                -1,
                $locationIdentifier,
            );
        } catch (\ReflectionException $e) {
            $this->assertTrue(
                false,
                "ReflectionException was thrown with message: " . $e->getMessage(),
            );
        }
    }

    /**
     * @test
     */
    public function testLocationIdentifierPattern_WithNonEmptyHtml()
    {
        $method = self::getMethodOfClass(
            'getLocationIdentifierFromHtml',
            HomeService::class,
        );

        try {
            $locationIdentifier = $method->invokeArgs($this->service, ['Test123']);

            $this->assertEquals(
                -1,
                $locationIdentifier,
            );
        } catch (\ReflectionException $e) {
            $this->assertTrue(
                false,
                "ReflectionException was thrown with message: " . $e->getMessage(),
            );
        }
    }

    /**
     * @test
     */
    public function testLocationIdentifierPattern_WithProperHtml()
    {
        $method = self::getMethodOfClass(
            'getLocationIdentifierFromHtml',
            HomeService::class,
        );

        try {
            $locationIdentifier = $method->invokeArgs($this->service, [
'<script>
{"text":"Properties to let in M1 1AE","url":"/property-to-rent/find.html?locationIdentifier=POSTCODE^3711614"}
</script>'
            ]);

            $this->assertEquals(
                '3711614',
                $locationIdentifier,
            );
        } catch (\ReflectionException $e) {
            $this->assertTrue(
                false,
                "ReflectionException was thrown with message: " . $e->getMessage(),
            );
        }
    }

    /**
     * @test
     */
    public function testPropertyCountQueryFailure()
    {
        $this->mock->reset();
        $this->mock->append(
            new RequestException('Error Communicating with Server', new Request('GET', 'test'))
        );

        $this->service->getPropertyCountByLocationIdentifier(3711614);
        $this->assertEquals(
            -1,
            $this->service->getResultCount(),
        );
    }

    /**
     * @test
     */
    public function testPropertyCountQuerySuccess()
    {
        $this->mock->reset();
        $this->mock->append(
            new Response(200, ['X-Foo' => 'Bar'], 'Hello World'),
        );

        $this->service->getPropertyCountByLocationIdentifier(3711614);
        $this->assertEquals(
            -1,
            $this->service->getResultCount(),
        );
    }

    /**
     * @test
     */
    public function testPropertyDataQueryFailure()
    {
        $this->mock->reset();
        $this->mock->append(
            new RequestException('Error Communicating with Server', new Request('GET', 'test'))
        );

        $this->service->processPropertyDataByLocationIdentifierAndFilters(3711614);
        $this->assertEquals(
            [],
            $this->service->getPropertyData(),
        );
    }

    /**
     * @test
     */
    public function testPropertyDataQuerySuccess()
    {
        $this->mock->reset();
        $this->mock->append(
            new Response(200, ['X-Foo' => 'Bar'], 'Hello World'),
        );

        $this->service->processPropertyDataByLocationIdentifierAndFilters(3711614);
        $this->assertEquals(
            [],
            $this->service->getPropertyData(),
        );
    }

    /**
     * @test
     */
    public function testHandlePropertyData()
    {
        $this->mock->reset();
        $this->mock->append(
            new Response(200, ['X-Foo' => 'Bar'], 'Hello World'),
        );

        $this->service->processPropertyDataByLocationIdentifierAndFilters(3711614);
        $this->assertEquals(
            [],
            $this->service->getPropertyData(),
        );

        $method = self::getMethodOfClass(
            'handlePropertyData',
            HomeService::class,
        );

        try {
            $method->invokeArgs($this->service, []);
        } catch (\ReflectionException $e) {
            $this->assertTrue(
                false,
                "ReflectionException was thrown with message: " . $e->getMessage(),
            );
        }
    }

    /**
     * @test
     */
    public function testGetHighestTransactionPrice_WithEmpty()
    {
        $method = self::getMethodOfClass(
            'getHighestTransactionPrice',
            HomeService::class,
        );

        try {
            $price = $method->invokeArgs($this->service, [[]]);

            $this->assertEquals(
                0,
                $price,
            );
        } catch (\ReflectionException $e) {
            $this->assertTrue(
                false,
                "ReflectionException was thrown with message: " . $e->getMessage(),
            );
        }
    }

    /**
     * @test
     */
    public function testGetHighestTransactionPrice_WithNonEmpty()
    {
        $method = self::getMethodOfClass(
            'getHighestTransactionPrice',
            HomeService::class,
        );

        try {
            $firstTransaction = new \stdClass();
            $firstTransaction->displayPrice = "£1,000";
            $secondTransaction = new \stdClass();
            $secondTransaction->displayPrice = "£3,000";
            $thirdTransaction = new \stdClass();
            $thirdTransaction->displayPrice = "£2,000";
            $transactions = [
                $firstTransaction,
                $secondTransaction,
                $thirdTransaction,
            ];
            $price = $method->invokeArgs($this->service, [$transactions]);

            $this->assertEquals(
                3000,
                $price,
            );
        } catch (\ReflectionException $e) {
            $this->assertTrue(
                false,
                "ReflectionException was thrown with message: " . $e->getMessage(),
            );
        }
    }

    /**
     * @test
     */
    public function testHasValidResponseDataInstances()
    {
        $firstValidity = $this->service->hasValidBasicResponseData();
        $secondValidity = $this->service->hasValidFilteredResponseData();

        $this->assertEquals(
            false,
            $firstValidity,
        );
        $this->assertEquals(
            false,
            $secondValidity,
        );
    }

    /**
     * @test
     */
    public function testHasValidResponseData_WithEmpty()
    {
        $method = self::getMethodOfClass(
            'hasValidResponseData',
            HomeService::class,
        );

        try {
            $validity = $method->invokeArgs($this->service, [null]);

            $this->assertEquals(
                false,
                $validity,
            );
        } catch (\ReflectionException $e) {
            $this->assertTrue(
                false,
                "ReflectionException was thrown with message: " . $e->getMessage(),
            );
        }
    }

    /**
     * @test
     */
    public function testHasValidResponseData_WithNonEmpty()
    {
        $method = self::getMethodOfClass(
            'hasValidResponseData',
            HomeService::class,
        );

        try {
            $parameter = new \stdClass();
            $parameter->searchLocation = new \stdClass();
            $parameter->searchLocation->displayName = 'Invalid Value';
            $validity = $method->invokeArgs($this->service, [$parameter]);

            $this->assertEquals(
                false,
                $validity,
            );
        } catch (\ReflectionException $e) {
            $this->assertTrue(
                false,
                "ReflectionException was thrown with message: " . $e->getMessage(),
            );
        }
    }

    /**
     * @test
     */
    public function testHasValidResponseData_WithSuccess()
    {
        $this->mock->reset();
        $this->mock->append(
            new Response(200, ['X-Foo' => 'Bar'], 'Hello World'),
        );
        $this->service->getLocationIdentifierByPostcode('Valid Value');

        $method = self::getMethodOfClass(
            'hasValidResponseData',
            HomeService::class,
        );

        try {
            $parameter = new \stdClass();
            $parameter->searchLocation = new \stdClass();
            $parameter->searchLocation->displayName = 'Valid Value';
            $validity = $method->invokeArgs($this->service, [$parameter]);

            $this->assertEquals(
                true,
                $validity,
            );
        } catch (\ReflectionException $e) {
            $this->assertTrue(
                false,
                "ReflectionException was thrown with message: " . $e->getMessage(),
            );
        }
    }

    /**
     * @test
     */
    public function testInvalidCriteriaMessageInstances()
    {
        $firstMessage = $this->service->getFirstInvalidCriteriaMessage();
        $secondMessage = $this->service->getSecondInvalidCriteriaMessage();

        $this->assertEquals(
            'The first API GET call run into failure.' .
            ' Search criteria was: "' . '' . '"' .
            ' Location Identifier based API call returned invalid criteria: "' . '' . '"',
            $firstMessage,
        );
        $this->assertEquals(
            'The second API GET call run into failure.' .
            ' Search criteria was: "' . '' . '"' .
            ' Location Identifier based API call returned invalid criteria: "' . '' . '"',
            $secondMessage,
        );
    }

    /**
     * @test
     */
    public function testInvalidCriteriaMessage_WithEmptyParameter()
    {
        $method = self::getMethodOfClass(
            'getInvalidCriteriaMessage',
            HomeService::class,
        );

        try {
            $message = $method->invokeArgs($this->service, [null, 'test']);

            $this->assertEquals(
                'The test API GET call run into failure.' .
                ' Search criteria was: "' . '' . '"' .
                ' Location Identifier based API call returned invalid criteria: "' . '' . '"',
                $message,
            );
        } catch (\ReflectionException $e) {
            $this->assertTrue(
                false,
                "ReflectionException was thrown with message: " . $e->getMessage(),
            );
        }
    }

    /**
     * @test
     */
    public function testInvalidCriteriaMessage_WithNonEmptyParameter()
    {
        $method = self::getMethodOfClass(
            'getInvalidCriteriaMessage',
            HomeService::class,
        );

        try {
            $parameter = new \stdClass();
            $parameter->searchLocation = new \stdClass();
            $parameter->searchLocation->displayName = 'Invalid Value';
            $message = $method->invokeArgs($this->service, [$parameter, 'test']);

            $this->assertEquals(
                'The test API GET call run into failure.' .
                ' Search criteria was: "' . '' . '"' .
                ' Location Identifier based API call returned invalid criteria: "' . 'Invalid Value' . '"',
                $message,
            );
        } catch (\ReflectionException $e) {
            $this->assertTrue(
                false,
                "ReflectionException was thrown with message: " . $e->getMessage(),
            );
        }
    }
}
