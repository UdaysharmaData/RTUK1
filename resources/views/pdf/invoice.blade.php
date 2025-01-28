@extends('layouts.pdf')

@section('title', $title)

@section('content')
    <div class="content__header">
        <img src="{{ $logo }}" alt="Company logo">
        <h2>Invoice</h2>
    </div>

    <div class="content__info">
        <h4>Hello {{ $name }},</h4>
        <p>{{ $description }}</p>
    </div>

    {{-- Invoice information section --}}
    <x-table :headers="$headerInfo" :body="$bodyInfo"></x-table>

    {{-- Invoice Summary section --}}
    <div class="mt-1">
        <h3>Order Summary</h3>
        <x-table :headers="$headerSummary" :body="$bodySummary"></x-table>
    </div>

    {{-- Invoice Payment Details section --}}
    <div class="mt-1">
        <h3>Order Details</h3>
        <x-table :headers="$headerDetails" :body="$bodyDetails"></x-table>
    </div>

    {{-- Bottom content --}}
    <div class="{{$explanation ? "bottom__content_2" : "bottom__content" }}">
        <strong data-site="runthrough.co.uk">Note:</strong>
        <p>{!! $note !!}</p>
    </div>
@endsection
