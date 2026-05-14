@extends('layouts.app')

@section('title', 'Dashboard Analis')

@section('content')
    <x-cards.welcome-card
        title="Dashboard Analis"
        description="Selamat datang di dashboard Analis."
        :name="$userName"
    />
@endsection
