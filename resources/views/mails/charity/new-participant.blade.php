@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $charity['name'] }},</h1>

    <p>You have just received a new registration for the <strong>{{ $event['name'] }}</strong> event through the
        <strong>{{ $participant['latest_action'] }}</strong> channel.
    </p>

    <p>
        <x-button label="View Participant" url="{{ $mailHelper->portalLink('participants/' . $participant['ref'] . '/edit') }}">
        </x-button>
    </p>
@endsection
