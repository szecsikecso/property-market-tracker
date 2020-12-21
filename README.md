## About the project

The purpose of the application is to provide a proper UK postcode,
Returning properties sold within the area defined by the postcode.

I have chosen Laravel as PHP framework,
In the actual solution it is not using any database.
Started by "composer create-project laravel/laravel {name-of-the-project}"

The 3rd party library used for gathering information from online resource:
GuzzleHttp/Guzzle - https://packagist.org/packages/guzzlehttp/guzzle
It required a certification provided locally to allow using SSL connection.
Downloaded it from - https://curl.haxx.se/docs/caextract.html -> cacert.pem
Placed under: resources/certificate/cacert.pem

Used PHP 7.4 developing the custom solution in the framework

## How to run the application

Use composer install to start using the project.
When it finished execute:
php artisan to serve

## Application structure

- Single page application, its root page is fulfilled by HomeController and HomeService
- HomeService is relies on an online resource: https://www.rightmove.co.uk
- First the postcode is converted into a GET parameter calling the online resource
- Reading data item called LocationIdentifier from HTML output provided (can be in the <script> tag)
- With the LocationIdentifier REST API of https://www.rightmove.co.uk can be called

## Home form parameters

- Postcode - required data, validated by UK government provided UK postcode regular expression
- 2nd scenario uses a hardcoded parameter to show list of limited items actually defined by HomeService::PROPERTY_LIMIT
- Filters can be applied for the 2nd scenario: Radius, Sold in, Property type, Tenure

## Default scenario requirements

1. Number of sold properties - filters are not applied on this query
- Radius default value: 0.0 (miles)
- Sold in default value: 30 (years)
- Property type default value: any
- Tenure default value: any

2. 5 top properties sold will be listed - filters and sorting can be applied
- Radius default value: 0.0 (miles)
- Sold in default value: 10 (years)
- Property type default value: any
- Tenure default value: any

## Possible improvements

1. Extending functionality
- Actual solution handles exact UK postcodes so postcodes areas are not accepted
  Given postcode area code can be identified and handled separately if the business requires it

2. General improvements

- HomeService has grown too big for maintain, it needed to be spread into multiple classes 
- Adding REST API routes in Laravel to provide JSON output of gathered data to be consumed by other application
- Creating separate forms to handle 1st and 2nd scenarios
- Improving 2nd scenario by introducing pagination and allowing page size to be defined.
- Introducing code quality system to ensure following a common coding standard (PHP CodeSniffer)

3. Adding database

- Creating a mapping table for all Postcode to LocationIdentifier relation
- Looping through all the UK postcodes to fill the table   
- Checking the mapping table and if the LocationIdentifier is found then the html reading is not necessary
- Making sure that relation is valid by checking the received postcode of API call result
- The cached data could help provide results faster
