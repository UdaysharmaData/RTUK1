@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $user['name'] }},</h1>

    <p>
        We have initiated a transfer of your registration from the <strong>{{ $event['name'] . ' (' . $event['category'] . ')' }}</strong> event to <strong>{{ $newEvent['name'] . ' (' . $newEvent['category'] . ')' }}</strong> event.
        Please note that there is an <strong>additional cost ({{ $total }})</strong> you need to pay to <a href='{{ $mailHelper->portalLink('entries/' . $participant['ref'] . '/transfer?eec=' . $newEvent['eec_ref']) }}'>complete the transfer</a>.
    </p>

    <p>
        <x-button label="Complete The Transfer"
            url="{{ $mailHelper->portalLink('entries/' . $participant['ref'] . '/transfer?eec=' . $newEvent['eec_ref']) }}">
        </x-button>
    </p>

    <p>
        Please click the button above to accept the transfer and complete the transaction.
    </p>
@endsection
