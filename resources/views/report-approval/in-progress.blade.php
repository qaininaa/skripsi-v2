@extends('layouts.app')

@section('title', 'Sedang Dikerjakan - ' . $roleLabel)
@section('page-title', 'Sedang Dikerjakan')

@section('content')
    @include('report-approval.partials.in-progress', [
        'showRoute' => $showRoute,
        'reports'   => $reports,
    ])
@endsection
