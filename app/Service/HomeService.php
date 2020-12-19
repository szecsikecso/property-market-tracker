<?php


namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class HomeService
{
    private Client $guzzleClient;
    private array $filters;

    private int $locationIdentifier;
    private int $resultCount;
    private object $responseData;
    private object $filteredResponseData;
    private array $propertyData;

    public const PROPERTY_LIMIT = 5;
    public const POSTCODE = 'POSTCODE';
    public const CERTIFICATE_PATH = 'resources/certificate/cacert.pem';

    public const HOUSE_PRICES_URL = 'https://www.rightmove.co.uk/house-prices';
    public const RESULT_URL = 'https://www.rightmove.co.uk/house-prices/result';
    public const LOCATION_TYPE_POSTCODE_ATTRIBUTE = '?locationType=POSTCODE&locationId=';

    public const FILTER_PREFIX = '&';
    public const FILTER_ASSIGN = '=';
    public const FILTER_EMPTY = 'ANY';

    public const CURRENCY_SIGN = '£';

    public function __construct()
    {
        $this->guzzleClient = new Client([
            'curl' => [
                CURLOPT_CAINFO => base_path(self::CERTIFICATE_PATH)
            ]
        ]);
        $this->filters = [];
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @param array $filters
     */
    public function setFilters(array $filters): void
    {
        $this->filters = $filters;
    }

    public function addFilter($key, $value): void
    {
        $this->filters[$key] = $value;
    }

    public function removeFilter($key): void
    {
        unset($this->filters[$key]);
    }

    public function printFilters(): string
    {
        $filterOutput = '';
        foreach ($this->filters as $filterKey => $filterValue) {
            if ($filterValue != self::FILTER_EMPTY) {
                $filterOutput .= self::FILTER_PREFIX . $filterKey . self::FILTER_ASSIGN . $filterValue;
            }
        }

        return $filterOutput;
    }

    /**
     * @param string $postcode
     *
     * @return int
     */
    public function getLocationIdentifierByPostcode(string $postcode): int
    {
        try {
            $response = $this->guzzleClient->get(
                self::HOUSE_PRICES_URL . '/' . $postcode
            );
            $siteContent = $response->getBody()->getContents();
            $matchContent = "";
            preg_match(
                '/' . self::POSTCODE . '[\^][0-9]+/',
                $siteContent,
                $matchContent
            );
            $this->locationIdentifier = ltrim($matchContent[0], self::POSTCODE . '^');
        } catch (GuzzleException $e) {
            $this->locationIdentifier = -1;
        }

        return (int)$this->locationIdentifier;
    }

    /**
     * @return int
     */
    public function getLocationIdentifier(): int
    {
        return $this->locationIdentifier;
    }

    /**
     * @param int $locationIdentifier
     *
     * @return int
     */
    public function getPropertyCountByLocationIdentifier(int $locationIdentifier): int
    {
        try {
            $response = $this->guzzleClient->get(
                self::RESULT_URL . self::LOCATION_TYPE_POSTCODE_ATTRIBUTE . $locationIdentifier
            );
            $responseData = json_decode($response->getBody()->getContents());
            if ($responseData) {
                $this->responseData = $responseData;
                $this->resultCount = $responseData->results->resultCount;
            }
        } catch (GuzzleException $e) {
            $this->resultCount = -1;
        }

        return (int)$this->resultCount;
    }

    /**
     * @param int $locationIdentifier
     *
     * @return void
     */
    public function processPropertyDataByLocationIdentifierAndFilters(int $locationIdentifier): void
    {
        var_dump($this->printFilters());
        try {
            $response = $this->guzzleClient->get(
                self::RESULT_URL . self::LOCATION_TYPE_POSTCODE_ATTRIBUTE . $locationIdentifier .
                $this->printFilters()
            );
            $responseData = json_decode($response->getBody()->getContents());
            if ($responseData) {
                $this->filteredResponseData = $responseData;
            }
        } catch (GuzzleException $e) {
            $this->resultCount = -1;
        }

        $this->handlePropertyData();
    }

    /**
     * Handle property data
     */
    private function handlePropertyData(): void
    {
        $this->propertyData = [];
        if ($this->filteredResponseData->results &&
            $this->filteredResponseData->results->properties) {

            $properties = $this->filteredResponseData->results->properties;
            foreach ($properties as $propertyKey => $propertyObject) {
                $highestTransaction = 0;
                foreach ($propertyObject->transactions as $transaction) {
                    $price = ltrim($transaction->displayPrice, self::CURRENCY_SIGN);
                    $price = str_replace(",", "", $price);
                    if ($price > $highestTransaction) {
                        $highestTransaction = $price;
                    }
                }

                $this->propertyData[] = [
                    'orderNumber' => $propertyKey + 1,
                    'address' => $propertyObject->address,
                    'propertyType' => $propertyObject->propertyType,
                    'highestTransaction' => self::CURRENCY_SIGN . number_format($highestTransaction),
                ];

                if ($propertyKey == self::PROPERTY_LIMIT - 1) {
                    break;
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getPropertyData(): array
    {
        return $this->propertyData;
    }

    /**
     * @return Object
     */
    public function getResponseData(): object
    {
        return $this->responseData;
    }

    /**
     * @return object
     */
    public function getFilteredResponseData(): object
    {
        return $this->filteredResponseData;
    }

    /**
     * @return int
     */
    public function getResultCount(): int
    {
        return $this->resultCount;
    }

}
