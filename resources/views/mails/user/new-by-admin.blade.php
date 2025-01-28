@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $user['name'] }},</h1>

    <p>
        An account has been created for you on
        <strong><a href="{{ $mailHelper->site->url }}">{{ $mailHelper->site->name }}</a></strong>.
        To get started, you'll need to set up a secure password for your account.
    </p>

    <p>
        Please enter the 6-digit code below when prompted on the password set up page to proceed. {!! \App\Modules\User\Models\VerificationCode::getValidityMessage() !!}
    </p>

    <p>
        <strong>{{ $user['code'] }}</strong>
    </p>

    <p>
        <x-button label="Set Up Your Password" url="{{ $mailHelper->portalLink('auth/password/setup') }}?code={{ $user['code'] }}&email={{ $user['email'] }}"></x-button>
    </p>

    <p>Your account will also be automatically verified once you set up your password, and you will be able to log in.</p>
@endsection
