@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $user['name'] }},</h1>
    <p>An account manager has created a account for you on <strong>{{ $mailHelper->site->name }}</strong>.
        You can access your account using the details below.
    </p>
    <p>
        <strong>Email Address: </strong> {{ $user['email'] }}<br />
        <strong>Password: </strong> {{ $user['password'] }}
    </p>
    <p>We recommend users to change their their password to the one they can easily remember.</p>
    <p>Kind Regards!</p>
    <p>
        <x-button label="Access portal" url="{{ $mailHelper->portalLink('auth/login') }}"></x-button>
    </p>
@endsection
