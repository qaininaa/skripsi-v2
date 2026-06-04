@extends('layouts.app')

@section('title', 'Dashboard Analis')

@section('content')
    <x-cards.welcome-card
        title="Dashboard Analis"
        description="Selamat datang di dashboard Analis."
        :name="$userName"
    />

    <x-cards.review-notification
        :note="$revisionNote ?? null"
        :href="route('report.index', ['tab' => 'dikembalikan'])"
        action-label="Lihat Revisi"
    />
@endsection
