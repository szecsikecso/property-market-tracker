<?php


namespace App\Http\Controllers;

use App\Rules\UkPostcode;
use App\Service\HomeService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{

    /**
     * @var string
     */
    private string $searchAttribute = 'postcode';

    /**
     * @var array|string[]
     */
    private array $filterAttributes = [
        'radius',
        'soldIn',
        'propertyType',
        'tenure',
        'sortBy',
    ];

    /**
     * @var array
     */
    private array $emptyAttributes = [
        'postcode' => '',
        'radius' => 0.0,
        'soldIn' => 10,
        'propertyType' => '',
        'tenure' => '',
        'sortBy' => '',
        'data' => [],
    ];

    /**
     * Displays the home page showing postcode tracker form
     *
     * @return Factory|View
     */
    public function index()
    {
        return view('front.index', $this->emptyAttributes);
    }

    /**
     * Processes postcode request to gather information about property market
     *
     * @param Request $request
     *
     * @return Application|Factory|View
     */
    public function process(Request $request)
    {
        $request->validate([
            $this->searchAttribute => ['required', 'string', new UkPostcode()],
            'radius' => ['numeric', 'between:0,15'],
            'soldIn' => ['integer', 'between:0,30'],
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
        if (!$service->hasValidBasicResponseData()) {
            $validator = Validator::make($request->all(), []);
            $validator->errors()->add('postcode',
                $service->getFirstInvalidCriteriaMessage()
            );
            return view('front.index',
                array_merge($this->emptyAttributes, ['errors' => $validator->errors()])
            );
        }

        $service->processPropertyDataByLocationIdentifierAndFilters($locationIdentifier);
        $propertyData = $service->getPropertyData();
        if (!$service->hasValidFilteredResponseData()) {
            $validator = Validator::make($request->all(), []);
            $validator->errors()->add('postcode',
                $service->getSecondInvalidCriteriaMessage()
            );
            return view('front.index', [
                array_merge($this->emptyAttributes, ['errors' => $validator->errors()])
            ]);
        }

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
                'resultData' => $propertyData,
                'resultDataLimit' => HomeService::PROPERTY_LIMIT,
            ],
        ]);
    }
}
