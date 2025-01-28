@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $name }},</h1>

    <p>
        A participant with the details below has attempted to register for the
        <a href="{{ $mailHelper->portalLink('event/' . $event['ref'] . '/edit') }}"
            class="text__primary"><strong>{{ $event['name'] }}</strong></a>
        event failed because it is an estimated event.
    </p>

    <p>
        <strong>First Name:</strong> {{ $user['first_name'] }} <br>
        <strong>Last Name:</strong> {{ $user['last_name'] }} <br>
        <strong>Email:</strong> {{ $user['email'] }}
    </p>

    <p>
        <x-button label="Event detail" url="{{ $mailHelper->portalLink('event/' . $event['ref'] . '/edit') }}"></x-button>
    </p>
@endsection
