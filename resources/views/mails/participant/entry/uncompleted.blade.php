@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $user['name'] }},</h1>

    <p>
        We hope you are getting ready to take on the <strong> {{ $event['name'] }}</strong> event. We can see that your
        registration is currently not quite complete.
    </p>

    <p>
        Please, <a href="{{ $mailHelper->portalLink('entries/' . $event['ref'] . '/edit') }}">complete your registration</a>.
    </p>

    <p>
        <x-button label="Complete Your Registration"
            url="{{ $mailHelper->portalLink('entries/' . $event['ref'] . '/edit') }}">
        </x-button>
    </p>

    <p><strong>If you miss the registration deadline, we will be unable to refund you for your place.</strong></p>

    @if ($charity)
        <p>
            Also, if you have not done so, please
            <a href="{{ $charity['fundraising_platform_url'] ?: 'https://www.justgiving.com/start-fundraising' }}">
                set up your fundraising page
            </a> and then
            <a href="{{ $mailHelper->portalLink('entries/' . $event['ref'] . '/edit?fundraising=true') }}">
                add your fundraising link
            </a> to the charity portal. Here are some
            <a href="{{ $mailHelper->portalLink($charity['fundraising_ideas_url']) }}">helpful fundraising tips</a>
            to help you get going.
        </p>

        <p>
            We would love to see how your fundraising is going and adding your link will allow us to help and support
            you in the best way we can.
        </p>
    @endif
@endsection
