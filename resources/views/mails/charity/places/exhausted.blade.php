@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $charity['name'] }},</h1>

    <p>Available places for the <a href="{{ $mailHelper->portalLink('event/' . $event['ref'] . '/edit') }}"
            target="_blank"><strong>{{ $event['name'] }}</strong></a>
        event under your charity have been exhausted.
    </p>

    <p>
        <x-button label="View Event" url="{{ $mailHelper->portalLink('event/' . $event['ref'] . '/edit') }}"></x-button>
    </p>

    <p>A participant with the details below attempted to register for the event.</p>

    <p>
        <strong>First Name:</strong> {{ $user['first_name'] }} <br>
        <strong>Last Name:</strong> {{ $user['last_name'] }} <br>
        <strong>Email:</strong> {{ $user['email'] }}
    </p>
@endsection
