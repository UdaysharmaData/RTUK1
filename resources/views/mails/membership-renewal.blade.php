<?php use Carbon\Carbon; ?>

@extends('layouts.email')

@section('header')
    <table cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="max-width: 680px;">
        <tr>
            <td style="padding: 20px 0; text-align: center">
                <a href="http://www.sportforcharity.com/">
                    <img src="{{ url('images/emails/sport-for-charity.jpg') }}" width="115" height="35" alt="Sport for Charity" border="0">
                </a>
            </td>
        </tr>
    </table>
@stop

@section('content')
    @if(isset($numOfReminders))
        <p>This is the {{ \App\Http\Helpers\Ordinal::express($numOfReminders) }} reminder that your invoice is now overdue by {{ ($numOfReminders * 2) }} weeks from {{ \Carbon\Carbon::parse($invoice->date)->toFormattedDateString() }}.</p>
        <hr>
    @endif

    <p>Hi {{ $charity->finance_contact_name ?? $charity->title }},</p>
    <p>Your account manager has renewed your <strong style="text-transform: capitalize;">{{ trim(preg_replace('/([A-Z])/', ' $1', $charity->latestCharityMembership->name)) }}</strong> membership which is now active for another {{ $charity->latestCharityMembership == \App\Enums\CharityMembershipTypeEnum::TwoYear ? 24 : 12 }} months.</p>
    <p>The payment terms on the membership is 2 weeks from {{ Carbon::now()->toFormattedDateString() }}. Please <a href="{{ url('/profile/invoices') }}">complete the payment</a> as soon as possible.</p>
    <p>Attached to this email is a copy of your invoice.</p>
    <p>If you have any questions please email <a href="mailto:{{ $charity->charityManager->user->email }}">{{ $charity->charityManager->user->full_name }}</a>.</p>
    <p>Kind Regards!<br/>Sport For Charity</p>
@stop