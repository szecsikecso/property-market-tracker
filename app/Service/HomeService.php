<?php


namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class HomeService
{
    private Client $guzzleClient;
    private array $filters;
    private array $propertyData;

    private string $searchCriteria;
    private int $locationIdentifier;

    private int $resultCount;
    private ?object $basicResponseData;
    private ?object $filteredResponseData;

    public const PROPERTY_LIMIT = 5;
    public const POSTCODE = 'POSTCODE';
    public const CERTIFICATE_PATH = 'resources/certificate/cacert.pem';

    public const HOUSE_PRICES_URL = 'https://www.rightmove.co.uk/house-prices';
    public const RESULT_URL = 'https://www.rightmove.co.uk/house-prices/result';
    public const LOCATION_TYPE_POSTCODE_ATTRIBUTE = '?locationType=POSTCODE';
    public const LOCATION_ID_ATTRIBUTE = '&locationId=';

    public const FILTER_PREFIX = '&';
    public const FILTER_ASSIGN = '=';
    public const FILTER_EMPTY = 'ANY';

    public const CURRENCY_SIGN = '£';
    public const THOUSANDS_SEPARATOR = ',';

    public function __construct(?Client $guzzleClient = null)
    {
        if ($guzzleClient) {
            $this->guzzleClient = $guzzleClient;
        } else {
            $this->guzzleClient = new Client([
                'curl' => [
                    CURLOPT_CAINFO => base_path(self::CERTIFICATE_PATH)
                ]
            ]);
        }

        $this->filters = [];
        $this->propertyData = [];

        $this->searchCriteria = '';
        $this->basicResponseData = null;
        $this->filteredResponseData = null;
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

    /**
     * Generating a string, which contains the list filter keys and filters values used as API GET parameters
     *
     * @return string
     */
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
     * @return array
     */
    public function getPropertyData(): array
    {
        return $this->propertyData;
    }

    /**
     * Executes and GET call with postcode in the search parameter
     * To receive HTML and get LocationIdentifier from it
     *
     * Remark:
     * Validity of the received LocationIdentifier relating to the postcode
     * Can be checked after the first API call is finished
     *
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

            $this->searchCriteria = $postcode;
            $this->locationIdentifier = $this->getLocationIdentifierFromHtml($siteContent);
        } catch (GuzzleException $e) {
            $this->locationIdentifier = -1;
        }

        return $this->locationIdentifier;
    }

    /**
     * Use regex pattern to detect the LocationIdentifier
     *
     * Remark:
     * In case of non-matching HTML empty string will be returned
     *
     * @param string $siteContent
     *
     * @return string
     */
    private function getLocationIdentifierFromHtml(string $siteContent): string
    {
        $matchContent = [];
        preg_match(
            '/' . self::POSTCODE . '[\^][0-9]+/',
            $siteContent,
            $matchContent
        );

        if (isset($matchContent[0])) {
            return ltrim($matchContent[0], self::POSTCODE . '^');
        } else {
            return -1;
        }
    }

    /**
     * @return int
     */
    public function getLocationIdentifier(): int
    {
        return $this->locationIdentifier;
    }

    /**
     * Executes an API GET call with LocationIdentifier and without filters to get the result count
     *
     * Remark:
     * Result count means the properties sold at least once at the defined area
     * Transaction made for the same property won't be counted
     *
     * @param int $locationIdentifier
     *
     * @return int
     */
    public function getPropertyCountByLocationIdentifier(int $locationIdentifier): int
    {
        try {
            $response = $this->guzzleClient->get(
                self::RESULT_URL .
                self::LOCATION_TYPE_POSTCODE_ATTRIBUTE .
                self::LOCATION_ID_ATTRIBUTE . $locationIdentifier
            );
            $responseData = json_decode($response->getBody()->getContents());
            if ($responseData) {
                $this->basicResponseData = $responseData;
                $this->resultCount = $responseData->results->resultCount;
            } else {
                $this->basicResponseData = null;
                $this->resultCount = -1;
            }
        } catch (GuzzleException $e) {
            $this->basicResponseData = null;
            $this->resultCount = -1;
        }

        return $this->resultCount;
    }

    /**
     * Executes an API GET call with LocationIdentifier plus filters to get results
     *
     * Remark:
     * Results contain properties sold at least once at the defined area
     * Only one price will be stored with the highest transaction price
     *
     * @param int $locationIdentifier
     *
     * @return void
     */
    public function processPropertyDataByLocationIdentifierAndFilters(int $locationIdentifier): void
    {
        try {
            $response = $this->guzzleClient->get(
                self::RESULT_URL .
                self::LOCATION_TYPE_POSTCODE_ATTRIBUTE .
                self::LOCATION_ID_ATTRIBUTE . $locationIdentifier .
                $this->printFilters()
            );
            $responseData = json_decode($response->getBody()->getContents());
            if ($responseData) {
                $this->filteredResponseData = $responseData;
            } else {
                $this->filteredResponseData = null;
            }
        } catch (GuzzleException $e) {
            $this->filteredResponseData = null;
        }

        $this->handlePropertyData();
    }

    /**
     * Handle property data
     *
     * Building propertyData array contains 4 keys with values:
     * - orderNumber: 1, 2, 3 etc - showing the order of the listed items
     * - address: address of the property
     * - propertyType: can be one these items Detached | Flat | Semi-detached | Terraced | Other
     * - highestTransaction: Price of the highest transaction made for the actual property
     */
    private function handlePropertyData(): void
    {
        if ($this->filteredResponseData && $this->filteredResponseData->results &&
            $this->filteredResponseData->results->properties) {

            $properties = $this->filteredResponseData->results->properties;
            foreach ($properties as $propertyKey => $propertyObject) {
                $highestTransactionPrice = $this->getHighestTransactionPrice($propertyObject->transactions);

                $this->propertyData[] = [
                    'orderNumber' => $propertyKey + 1,
                    'address' => $propertyObject->address,
                    'propertyType' => $propertyObject->propertyType,
                    'highestTransaction' => self::CURRENCY_SIGN . number_format($highestTransactionPrice),
                ];

                if ($propertyKey == self::PROPERTY_LIMIT - 1) {
                    break;
                }
            }
        }
    }

    /**
     * Gets the price of highest transaction made for the property
     *
     * @param array $transactions
     *
     * @return int
     */
    private function getHighestTransactionPrice(array $transactions): int
    {
        $highestTransactionPrice = 0;
        if ($transactions) {
            foreach ($transactions as $transaction) {
                $price = ltrim($transaction->displayPrice, self::CURRENCY_SIGN);
                $price = str_replace(self::THOUSANDS_SEPARATOR, "", $price);
                if ($price > $highestTransactionPrice) {
                    $highestTransactionPrice = $price;
                }
            }
        }

        return $highestTransactionPrice;
    }

    /**
     * @return int
     */
    public function getResultCount(): int
    {
        return $this->resultCount;
    }

    /**
     * @return bool
     */
    public function hasValidBasicResponseData(): bool
    {
        return $this->hasValidResponseData($this->basicResponseData);
    }

    /**
     * @return bool
     */
    public function hasValidFilteredResponseData(): bool
    {
        return $this->hasValidResponseData($this->filteredResponseData);
    }

    /**
     * Checks if provided search criteria is equal to API response contained value
     *
     * @param object|null $responseData
     *
     * @return bool
     */
    private function hasValidResponseData(?object $responseData): bool
    {
        return $this->searchCriteria && $responseData &&
            $responseData->searchLocation && $responseData->searchLocation->displayName &&
            $this->searchCriteria == $responseData->searchLocation->displayName;
    }

    /**
     * @return string
     */
    public function getFirstInvalidCriteriaMessage(): string
    {
        return $this->getInvalidCriteriaMessage($this->basicResponseData, 'first');
    }

    /**
     * @return string
     */
    public function getSecondInvalidCriteriaMessage(): string
    {
        return $this->getInvalidCriteriaMessage($this->filteredResponseData, 'second');
    }

    /**
     * Generates validation message for non-equal search criteria and invalid critera values
     *
     * @param object|null $responseData
     * @param string $messageTag
     *
     * @return string
     */
    private function getInvalidCriteriaMessage(?object $responseData, string $messageTag): string
    {
        $invalidCriteria = "";
        if ($responseData &&
            $responseData->searchLocation &&
            $responseData->searchLocation->displayName
        ) {
            $invalidCriteria = $responseData->searchLocation->displayName;
        }

        return 'The ' . $messageTag . ' API GET call run into failure.' .
            ' Search criteria was: "' . $this->searchCriteria . '"' .
            ' Location Identifier based API call returned invalid criteria: "' . $invalidCriteria . '"';
    }

}
