<?php


namespace App\Http\Controllers;

use App\Rules\UkPostcode;
use App\Service\HomeService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use GuzzleHttp\Client as GuzzleClient;

class HomeController extends Controller
{

    private string $searchAttribute = 'postcode';

    private array $filterAttributes = [
        'radius',
        'soldIn',
        'propertyType',
        'tenure',
        'sortBy',
    ];

    /**
     * Display the home page.
     *
     * @return Factory|View
     */
    public function index()
    {
        return view('front.index', [
            'postcode' => '',
            'radius' => 0.0,
            'soldIn' => 10,
            'propertyType' => '',
            'tenure' => '',
            'sortBy' => '',
            'data' => [],
        ]);
    }

    /**
     * Processes postcode request
     *
     * @param Request $request
     *
     * @return Application|Factory|View
     */
    public function processPostcode(Request $request)
    {
        $request->validate([
            'postcode' => ['required', 'string', new UkPostcode()],
            'radius' => ['numeric', 'between:0,15'],
            'soldIn' => ['numeric', 'between:0,30'],
        ]);

        $filters = [];
        foreach ($this->filterAttributes as $filterAttribute) {
            $filters[$filterAttribute] = $request->get($filterAttribute) ?? 'ANY';
        }

        $service = new HomeService();
        $service->setFilters($filters);

        $postcode = strtoupper($request->get($this->searchAttribute));
        $locationIdentifier = $service->getLocationIdentifierByPostcode($postcode);

        $resultCount = $service->getPropertyCountByLocationIdentifier($locationIdentifier);
        $service->processPropertyDataByLocationIdentifierAndFilters($locationIdentifier);

        return view('front.index', [
            'postcode' => $request->get('postcode'),
            'radius' => $request->get('radius'),
            'soldIn' => $request->get('soldIn'),
            'propertyType' => $request->get('propertyType'),
            'tenure' => $request->get('tenure'),
            'sortBy' => $request->get('sortBy'),
            'data' => [
                'success' => true,
                'resultCount' => $resultCount,
                'resultData' => $service->getPropertyData(),
                'resultDataLimit' => HomeService::PROPERTY_LIMIT,
            ],
        ]);
    }

    public function asd($request) {
        //var_dump($request);
        var_dump($request->get('postcode'));
        $requestPostcode = strtoupper($request->get('postcode'));

        $ukPostcodeRegex = '/([Gg][Ii][Rr] 0[Aa]{2})|((([A-Za-z][0-9]{1,2})|(([A-Za-z][A-Ha-hJ-Yj-y][0-9]{1,2})|(([A-Za-z][0-9][A-Za-z])|([A-Za-z][A-Ha-hJ-Yj-y][0-9][A-Za-z]?))))\s?[0-9][A-Za-z]{2})/';
        $validUkPostcode = preg_match($ukPostcodeRegex, $requestPostcode);
        var_dump($validUkPostcode);

        $postcodeForScraper = str_replace(' ', '-', $requestPostcode);

        //$web = new \spekulatius\phpscraper();

        $guzzleClient = new GuzzleClient([
            'curl' => [
                CURLOPT_CAINFO => base_path('resources/certificate/cacert.pem')
            ]
        ]);

        // Goutte Client
        //$client = new Client();

        // We assume that we want to follow any redirects.
        /*
        $client->followRedirects(true);
        $client->followMetaRefresh(true);
        $client->setMaxRedirects(5);

        // Make ourselves known
        $client->setServerParameter(
            'HTTP_USER_AGENT',
            'Mozilla/5.0 (compatible; PHP Scraper/0.x; +https://phpscraper.de)'
        );
        $client->request('GET', $url);
        $asd = $client->getCrawler()->getNode(0)->textContent;
        */

        $url = "https://www.rightmove.co.uk/house-prices/$postcodeForScraper.html";
        $response0 = $guzzleClient->get($url);

        $asd = $response0->getBody()->getContents();
        //var_dump($data0);

        //$asd = "";
        $asdasd = null;

        $postcodeTag =
            preg_match('/' . self::POSTCODE . '[\^][0-9]+/', $asd, $asdasd);
        $locationIdentifier = ltrim($asdasd[0], self::POSTCODE . "^");

        $apiUrl = "https://www.rightmove.co.uk/house-prices/result?" .
            "locationType=POSTCODE&locationId=$locationIdentifier";

        //$response = file_get_contents($apiUrl);
        $guzzleClient = new GuzzleClient([
            'curl' => [
                CURLOPT_CAINFO => base_path('resources/certificate/cacert.pem')
            ]
        ]);
        $response = $guzzleClient->get($apiUrl);
        //$response = $client->request('GET', $apiUrl);
        //$asd2 = $client->getCrawler()->getNode(0);
        echo $response->getStatusCode();

        $data = json_decode($response->getBody()->getContents());

        var_dump($data->searchLocation->displayName);

        if ($data->searchLocation->displayName == $requestPostcode) {
            echo 'Valid postcode!';
        }


        //$web->go($url);

        //var_dump($web->filterFirst('locationIdentifier'));

        //array_keys($web);

        //echo '<pre>',print_r($web,1),'</pre>';

        echo 'Result count:' . $data->results->resultCount;

        /**
         * Default: ANY
         */
        $propertyCategoryValue = "ANY";
        $propertyCategory = "&propertyCategory=$propertyCategoryValue";

        /**
         * Default: ANY
         *
         * DETACHED
         * FLAT
         * SEMI_DETACHED
         * TERRACED
         * OTHER
         */
        $propertyTypeValue = "ANY";
        $propertyType = "&propertyType=$propertyTypeValue";

        $radiusValue = 0.0;
        $radius = "&radius=$radiusValue";

        /**
         * Default: 30
         */
        $soldInValue = 10;
        $soldIn = "&solidIn=$soldInValue";

        /**
         * Default: ANY
         *
         * FREEHOLD
         * LEASEHOLD
         */
        $tenureValue = "ANY";
        $tenure = "&tenure=$tenureValue";

        /**
         * Default: DATE_SOLD
         *
         * ADDRESS
         * DATE_SOLD
         * PRICE_ASC
         * PRICE_DESC
         */
        $sortByValue = "PRICE_DESC";
        $sortBy = "&sortBy=$sortByValue";

        $apiUrl2 = $apiUrl . $propertyCategory . $propertyType . $soldIn . $tenure . $sortBy;
        $response2 = $guzzleClient->get($apiUrl2);

        $data2 = json_decode($response2->getBody()->getContents());
        $properties = $data2->results->properties;

        /*
        foreach ($properties as $propertyKey => $propertyObject) {
            var_dump($propertyKey + 1);

            var_dump($propertyObject->address);
            var_dump($propertyObject->propertyType);

            $highestTransaction = 0;
            foreach ($propertyObject->transactions as $transaction) {
                $price = $transaction->displayPrice;
                $price = ltrim($price, "£");
                $price = str_replace(",", "", $price);
                if ($price > $highestTransaction) {
                    $highestTransaction = $price;
                }
            }

            echo "Highest price: " . $highestTransaction;

            if ($propertyKey == 4) {
                break;
            }
        }
        */

        echo '<pre>',print_r($properties,1),'</pre>';

        //die();

        //return redirect('/')->with('request', $request->get(''));
    }
}
