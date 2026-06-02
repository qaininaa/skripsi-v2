@extends('layouts.app')

@section('title', 'Laporan Masuk - ' . $roleLabel)
@section('page-title', 'Laporan Masuk')

@section('content')
    @include('report-approval.partials.inbox', [
        'showRoute'   => $showRoute,
        'tabRoute'    => $tabRoute,
        'reports'     => $reports,
        'counts'      => $counts,
        'activeTab'   => $activeTab,
    ])
@endsection
