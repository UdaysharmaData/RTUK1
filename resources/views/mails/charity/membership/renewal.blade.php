@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $charity['name'] }},</h1>

    @if (isset($numOfReminders))
        <p>This is the {{ $numOfReminders }} reminder that your invoice is now overdue by {{ $numOfReminders * 2 }}
            weeks from {{ Carbon::parse($invoice->date)->toFormattedDateString() }}.</p>
        <hr>
    @endif

    <p>Your account manager has renewed your <strong>{{ preg_replace('_', ' ', $charity['membership']['type']) }}</strong>
        membership which is now active for another {{ $charity['membership']['monthly_subscription'] }} months.
    </p>

    @if ($Invoice['status'] == 'unpaid')
        <p>The payment terms of the membership is 2 weeks from {{ now()->toFormattedDateString() }}. Please
            <a href="/profile/invoices">complete the payment</a> as soon as possible.
        </p>
    @endif
    <p>Attached to this email is a copy of your invoice.</p>
    <p>If you have any questions please email <a
            href="mailto:{{ $charity['manager']['email'] }}">{{ $charity['manager']['name'] }}</a>.
    </p>
    <p>Kind Regards!<br />
    @endsection
