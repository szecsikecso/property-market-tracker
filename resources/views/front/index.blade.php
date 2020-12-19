@extends('front.template')

@section('main')
    <div class="container mt-5">

        <!-- Success message -->
        @if(Session::has('success'))
            <div class="alert alert-success">
                {{Session::get('success')}}
            </div>
        @endif

        <h1>
            Property Market Tracker
        </h1>

        @if(!isset($data['success']))
            <p>
                Use a proper UK postcode for tracking sold properties.<br>
            </p>
        @else
            <p>
                Good job!<br>
                Result count for the provided postcode without filtering: {{ $data['resultCount'] }}<br>
                @if($data['resultData'])
                    See the top filtered results below (limited to {{ $data['resultDataLimit'] }})
                @else
                    The result is empty with the actual filtering
                @endif
                <ul>
                @foreach($data['resultData'] as $resultItem)
                    <li>
                        <div>{{ $resultItem['orderNumber'] }}.</div>
                        <div>Address: {{ $resultItem['address'] }}</div>
                        <div>Property type: {{ $resultItem['propertyType'] }}</div>
                        <div>Price: {{ $resultItem['highestTransaction'] }}</div>
                        <br>
                    </li>
                @endforeach
                </ul>
            </p>
        @endif

        <form name="process" id="process" method="post" action="{{url('')}}">

            @csrf

            <div class="form-group">
                <label>Postcode</label>
                <input type="text" class="form-control @if($errors->has('postcode')) is-invalid @endif "
                       name="postcode" id="postcode" value="{{old('postcode', $postcode)}}"
                >
                @if($errors->has('postcode'))
                    <div class="invalid-feedback">{{$errors->first('postcode')}}</div>
                @endif
            </div>

            <div class="form-group">
                <label>Radius</label>
                <input type="number" class="form-control" min="0" max="15"
                       name="radius" id="radius" value="{{old('radius', $radius)}}"
                >
            </div>

            <div class="form-group">
                <label>Sold in (years)</label>
                <input type="number" class="form-control" min="0" max="30"
                       name="soldIn" id="soldIn" value="{{old('soldIn', $soldIn)}}"
                >
            </div>

            <div class="form-group">
                <label for="propertyType">Property type</label>
                <select class="form-control" name="propertyType" id="propertyType">
                    <option>Any</option>
                    <option @if($propertyType == 'DETACHED') selected @endif value="DETACHED">
                        Detached</option>
                    <option @if($propertyType == 'FLAT') selected @endif value="FLAT">
                        Flat</option>
                    <option @if($propertyType == 'SEMI_DETACHED') selected @endif value="SEMI_DETACHED">
                        Semi-detached</option>
                    <option @if($propertyType == 'TERRACED') selected @endif value="TERRACED">
                        Terraced</option>
                    <option @if($propertyType == 'OTHER') selected @endif value="OTHER">
                        Other</option>
                </select>
            </div>

            <div class="form-group">
                <label for="tenure">Tenure</label>
                <select class="form-control" name="tenure" id="tenure">
                    <option>Any</option>
                    <option @if($tenure == 'FREEHOLD') selected @endif value="FREEHOLD">
                        Freehold</option>
                    <option @if($tenure == 'LEASEHOLD') selected @endif value="LEASEHOLD">
                        Leasehold</option>
                </select>
            </div>

            <div class="form-group">
                <label for="sortBy">Sort by</label>
                <select class="form-control" name="sortBy" id="sortBy">
                    <option @if($sortBy == 'ADDRESS') selected @endif value="ADDRESS">Address</option>
                    <option @if($sortBy == 'DATE_SOLD') selected @endif value="DATE_SOLD">Date sold</option>
                    <option @if($sortBy == 'PRICE_ASC') selected @endif value="PRICE_ASC">Price asc</option>
                    <option @if($sortBy == '' || $sortBy == 'PRICE_DESC') selected @endif value="PRICE_DESC">
                        Price desc (default)
                    </option>
                </select>
            </div>

            <input type="submit" name="send" value="Submit" class="btn btn-dark btn-block">
        </form>
    </div>
@stop
