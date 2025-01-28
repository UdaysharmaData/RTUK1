@extends('layouts.email')

@php
    
    $socialMedia = [
        ['label' => 'Facebook', 'link' => 'https://www.facebook.com/RunThrough/', 'icon' => 'facebook.png', 'class' => 'facebook'],
        ['label' => 'Instagram', 'link' => 'https://www.instagram.com/runthroughuk/', 'icon' => 'instagram.png', 'class' => 'instagram'], 
        ['label' => 'Twitter', 'link' => 'https://twitter.com/RunThroughUK', 'icon' => 'twitter.png', 'class' => 'twitter'],
        ['label' => 'Youtube', 'link' => 'https://www.youtube.com/runthroughtv?sub_confirmation=1', 'icon' => 'youtube.png', 'class' => 'youtube'],
        ['label' => 'TikTok', 'link' => 'https://www.tiktok.com/@runthroughuk', 'icon' => 'tiktok.png', 'class' => 'tiktok'],  
    ];
@endphp

@section('email_css')
    <style type="text/css">
        a {
            text-decoration: none;
            color: #007BC3
        }

        .text__primary {
            color: #007BC3 !important
        }

        .bg__primary {
            background-color: #007BC3 !important
        }
    </style>
@endsection

@section('title', $title ?? ($subject ?? null))

@section('body')

    @yield('content')

   

    @if (isset($opt))
        <x-divider></x-divider>
        <p class="text__center">
            {{ $opt->message }} If not, you can
            <a href="{{ $opt->unsubscribe }}" target="_blank">
                <strong class="text__primary">
                    unsubscribe
                </strong>
            </a> from it.
        </p>
    @endif

@endsection
