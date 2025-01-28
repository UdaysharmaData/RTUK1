@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $charity['name'] }},</h1>

    <p>
        Your charity membership just expired. Please <a href="{{ $mailHelper->portalLink('') }}">renew</a>
        your membership as soon as possible.
    </p>

    <p>
        <x-button label="Access portal" url="{{ $mailHelper->portalLink('') }}"></x-button>
    </p>
@endsection
