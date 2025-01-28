@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
<h1>Hello {{ $user['name'] }},</h1>

<p>
    The events below have expired and are now archived. The archived events have been cloned and new ones published for next year.
</p>

{{-- Passed Order Summary section --}}
<div class="my-1">
    <h3>Archived Events</h3>
    <x-table :headers="$header" :body="$events"></x-table>
</div>

<p>
    <x-button label="View New Events" url="{{ $mailHelper->portalLink('event?ids=' . $new_events_ids) }}"></x-button>
</p>
@endsection