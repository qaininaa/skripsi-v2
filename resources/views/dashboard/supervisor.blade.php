@extends('layouts.app')

@section('title', 'Dashboard Supervisor')

@section('content')
    <x-cards.welcome-card
        title="Dashboard Supervisor"
        description="Selamat datang di dashboard Supervisor."
        :name="$userName"
    />

    <x-cards.review-notification :note="$reviewNote ?? null" />
@endsection
