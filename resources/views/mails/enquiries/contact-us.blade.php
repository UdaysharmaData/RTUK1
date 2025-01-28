@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
    <h1>Hello {{ $name }},</h1>
    <p>{{ $intro }}</p>
    Message:
    <p>{{ $text }}</p>
@endsection
