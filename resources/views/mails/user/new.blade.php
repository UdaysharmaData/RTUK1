@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $user['name'] }},</h1>
    <p>
        An account has been created for you on
        <strong><a href="{{ $mailHelper->site->url }}">{{ $mailHelper->site->name }}</a></strong>.
        Use the details below to <a href="{{ $mailHelper->portalLink('auth/login') }}" target="_blank">Log In</a>
        to your account.
    </p>

    <p>
        <strong>Email Address: </strong> {{ $user['email'] }}<br />
        <strong>Password: </strong> {{ $user['password'] }}
    </p>

    <p>
        <x-button label="Log In" url="{{ $mailHelper->portalLink('auth/login') }}">
        </x-button>
    </p>

    <p>Once logged in, please endeavor to reset your password (so that we can keep your information safe).</p>
@endsection
