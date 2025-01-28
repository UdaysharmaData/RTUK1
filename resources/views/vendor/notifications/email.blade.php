@extends('mails.layouts.' . $mailHelper->site->code)

@section('email_css')
    <style type="text/css">
        a {
            color: #007BC3 !important
        }
    </style>
@endsection

@section('content')
    {{-- Greeting --}}
    @if (!empty($greeting))
        <h1>{{ $greeting }}</h1>
    @else
        @if ($level === 'error')
            <h1>@lang('Whoops!')</h1>
        @else
            <h1>@lang('Hello!')</h1>
        @endif
    @endif

    {{-- Intro Lines --}}
    @foreach ($introLines as $line)
        <p>{!! $line !!}</p>
    @endforeach

    {{-- Salutation --}}
    @if (!empty($salutation))
        <p><br />

            {{ $salutation }}
        </p>
    @endif

    {{-- Action Button --}}
    @if (isset($actionText) && isset($actionUrl) && !empty($actionText) && !empty($actionUrl))
        <p>
            <x-button label="{{ $actionText }}" url="{{ $actionUrl }}">
            </x-button>
        </p>
    @endif
@endsection
