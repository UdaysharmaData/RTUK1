@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $user['name'] }}</h1>

    @if ($isParticipantCurrentUser)
        <p>
            The participant <strong class="dark">{{ $participant['name'] }}</strong> has withdrawn their entry for the
            <strong class="dark">{{ $event['name'] . ' (' . $event['category'] . ')' }}</strong> event.
        </p>
    @else
        <p>
            The participant <strong class="dark">{{ $participant['name'] }}</strong> has been removed from the
            <strong class="dark">{{ $event['name'] . ' (' . $event['category'] . ')' }}</strong> event.
        </p>
    @endif

    @if ($refund)
        <p>
            A refund of <strong class="dark">{{ $refund['amount'] }}</strong> was made following this withdrawal.
        </p>
    @endif

    <p>
        <x-button label="View Participant"
            url="{{ $mailHelper->portalLink('participants/' . $participant['ref'] . '/edit') }}">
        </x-button>
    </p>

    <p>
        Please click the button above to view the participant.
    </p>
@endsection
