@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
<h1>Hello {{ $user['name'] }},</h1>

<p>
    The <strong><a href="{{ $mailHelper->portalLink('event/' . $event['ref'] . '/edit') }}" class="text__primary">{{ $event['name'] }}</a></strong>
    event has expired and is now archived.
</p>

<p>
    The archived event has been cloned and a new one published for next year:
    <strong><a href="{{ $mailHelper->portalLink('event/' . $clone['ref'] . '/edit') }}" class="text__primary">{{ $clone['name'] }}</a></strong>.
</p>

<p>
    <x-button label="View New Event" url="{{ $mailHelper->portalLink('event/' . $clone['ref'] . '/edit') }}"></x-button>
</p>
@endsection