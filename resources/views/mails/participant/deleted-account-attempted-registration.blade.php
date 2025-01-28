@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $name }},</h1>

    @if (isset($charity) && $charity)
        <p>
            A participant with the details below has attempted to register for the
            <strong>{{ $event['name'] }}</strong> event under the {{ $charity['name'] }} charity but failed because
            their account has been deleted.
        </p>
    @else
        <p>
            A participant with the details below has attempted to register for the
            <strong>{{ $event['name'] }}</strong> event but failed because their account has been deleted.
        </p>
    @endif

    <p>
        <strong>First Name:</strong> {{ $user['first_name'] }} <br>
        <strong>Last Name:</strong> {{ $user['last_name'] }} <br>
        <strong>Email:</strong> {{ $user['email'] }}
    </p>

    <p>
        <x-button label="View User" url="{{ $mailHelper->portalLink('user/' . $user['ref'] . '/edit') }}"></x-button>
    </p>

    <p>
        Please take action to permanently delete the user account or offer them a place after restoring their account.
    </p>
@endsection
