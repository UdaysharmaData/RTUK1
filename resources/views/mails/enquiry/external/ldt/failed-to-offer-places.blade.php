@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $name }},</h1>

    <p>
        An attempt to offer places to <strong>{{ $numberOfParticipants }}</strong> enquiries fetched from <strong>
            Let’s Do This</strong> failed. You can view, manage, and manually offer places to these enquiries.
    </p>

    <p>
        <x-button label="View External Enquiries" url="{{ $mailHelper->portalLink('enquiries/external') }}"></x-button>
    </p>
@endsection
