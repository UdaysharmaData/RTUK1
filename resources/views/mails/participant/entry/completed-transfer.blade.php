@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $user['name'] }},</h1>

    <p>
        We have successfully transferred your registration from the <strong class="dark">{{ $oldParticipant['event']['name'] . ' (' . $oldParticipant['event']['category'] . ')' }}</strong> event
        to the <strong class="dark">{{ $newParticipant['event']['name'] . ' (' . $newParticipant['event']['category'] . ')' }}</strong> event.
    </p>

    @if ($total)
        <p>
            During the transfer, we noticed an outstanding balance of <strong class="dark">{{ $total }}</strong>.
            The total has been <a href="{{ $mailHelper->portalLink('wallet') }}">credited to your wallet</a>.
        </p>
    @endif

    <p>
        <x-button label="View Your Registration"
            url="{{ $mailHelper->portalLink('entries/' . $newParticipant['ref'] . '/edit') }}">
        </x-button>
    </p>

    @if ($newParticipant['status'] != \App\Enums\ParticipantStatusEnum::Complete)
        <p>
            Please click the button above to complete your registration.
        </p>
    @else
        <p>
            You are not fully registered yet. Please click the button above to view and complete your registration.
        </p>
    @endif
@endsection
