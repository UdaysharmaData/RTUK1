@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $user['name'] }},</h1>

    <p>
        We couldn't transfer your registration from the <strong class="dark">{{ $oldEec['event']['name'] . ' (' . $oldEec['category']['name'] . ')' }}</strong> event
        to the <strong class="dark">{{ $newEec['event']['name'] . ' (' . $newEec['category']['name'] . ')' }}</strong>

        @if (isset($refundedAmount))
            event and have been refunded <strong class="dark">{{$refundedAmount}}</strong> for it.
        @else
            event.
        @endif
    </p>

    <p>
        Contact the administrator regarding this issue.
    </p>

    <p>
        <x-button label="View Your Registration"
            url="{{ $mailHelper->portalLink('entries/' . $participant['ref'] . '/edit') }}">
        </x-button>
    </p>

    <p>
        Please click the button above to view your registration.
    </p>
@endsection
