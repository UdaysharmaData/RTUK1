@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $user['name'] }},</h1>

    <p>You requested a new password set up code for your account.</p>

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
