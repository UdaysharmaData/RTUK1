@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $user['name'] }},</h1>

    @if ($isParticipantCurrentUser)
        <p>
            Your entry for the <strong class="dark">{{ $event['name'] . ' (' . $event['category'] . ')' }}</strong> event
            has been withdrawn.
        </p>
    @else
        <p>
            You have been removed from the <strong class="dark">{{ $event['name'] . ' (' . $event['category'] . ')' }}</strong> event.
        </p>
    @endif

    @if ($refund)
        @if ($refund['via_wallet'])
            <p>
                A refund of <strong class="dark">{{ $refund['amount'] }}</strong> was made to your
                <a href="{{ $mailHelper->portalLink('wallet') }}">wallet</a>.
            </p>
        @else
            <p>
                A refund of <strong class="dark">{{ $refund['amount'] }}</strong> was made following this withdrawal.
            </p>
        @endif
    @endif

    <p>
        <x-button label="Manage Your Entries" url="{{ $mailHelper->portalLink('entries') }}">
        </x-button>
    </p>

    <p>
        Please click the button above to manage your entries.
    </p>
@endsection
