@extends('.mails.layouts.' . $mailHelper->site->code)

@section('content')
<h1>Hello,</h1>
<p>Your exported data is ready. You can download it using the link below:</p>
<p><a href="{{ $s3PathLink }}">Download your file</a></p>
<p><strong>Note:</strong> This file will be automatically deleted after 1 hour.</p>
@endsection
