@extends('layouts.email')

@section('header')
    <table cellspacing="0" cellpadding="0" border="0" align="center" width="100%" style="max-width: 680px;">
        <tr>
            <td style="padding: 20px 0; text-align: center">
                <a href="http://www.sportforcharity.com/">
                    <img src="{{ url('images/emails/sport-for-charity.jpg') }}" width="115" height="35" alt="RunThrough" border="0">
                </a>
            </td>
        </tr>
    </table>
@stop

@section('body')
    <p>Hi RunThrough Admin,</p>
    <p>You have new registrations from Lets Do This.</p>
    <p>Click here to view and offer them places.</p>
    <p>Kind Regards!<br/>RunThrough</p>
@stop