@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $name }},</h1>

    <p>A new campaign has been created for <strong>{{ $chrity['name'] }}</strong> charity</p>.
    Please <a href="{{ $mailHelper->portalLink("campaign/{$campaign['ref']}/edit") }}">review</a> it and set the campaign
    events.
@endsection
