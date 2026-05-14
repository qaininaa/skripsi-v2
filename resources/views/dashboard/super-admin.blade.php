@extends('layouts.app')

@section('title', 'Dashboard Super Admin')

@section('content')
    <x-cards.welcome-card
        title="Dashboard Super Admin"
        description="Selamat datang di dashboard Super Admin."
        :name="$userName"
    />
@endsection
