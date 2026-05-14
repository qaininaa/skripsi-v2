@extends('layouts.auth')

@section('title', 'Sign In')
@section('auth-heading', 'Masuk ke Akun Anda')

@section('auth-content')
    <x-forms.auth-form :action="route('login.post')" />
@endsection
