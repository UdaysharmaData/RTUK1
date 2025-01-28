@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $name }},</h1>

    <p>Available places for the <a href="{{ $mailHelper->portalLink('event/' . $event['ref'] . '/edit') }}"
            target="_blank"><strong>{{ $event['name'] }}</strong></a>
        event have been exhausted.</p>

    <p>
        <x-button label="View Event" url="{{ $mailHelper->portalLink('event/' . $event['ref'] . '/edit') }}"></x-button>
    </p>

    <p>
        @if (isset($charity) && $charity)
            A participant with the details below attempted to register for the event under the
            <strong>{{ $charity['name'] }}</strong> charity.
        @else
            A participant with the details below attempted to register for the event.
        @endif
    </p>

    <p>
        <strong>First Name:</strong> {{ $user['first_name'] }} <br>
        <strong>Last Name:</strong> {{ $user['last_name'] }} <br>
        <strong>Email:</strong> {{ $user['email'] }}
    </p>
@endsection
