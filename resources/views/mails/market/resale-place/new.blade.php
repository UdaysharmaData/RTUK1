@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $charity['name'] }}</h1>
    <p>
        <b>{{ $places }}</b> {{ $places > 1 ? 'places' : 'place' }} of the <b>{{ $resalePlace['event']['name'] }}</b>
        event {{ $places > 1 ? 'have' : 'has' }} been listed on the market place.
        Please <a href="{{ $mailHelper->portalLink("market?id={$resalePlace['id']}") }}"> take a look.</a>
    </p>
    <p>Kind Regards!</p>
@endsection
