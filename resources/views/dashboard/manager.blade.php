@extends('layouts.app')

@section('title', 'Dashboard Manajer')

@section('content')
    <x-cards.welcome-card
        title="Dashboard Manajer"
        description="Selamat datang di dashboard Manajer."
        :name="$userName"
    />

    <x-cards.review-notification :note="$reviewNote ?? null" />
@endsection
