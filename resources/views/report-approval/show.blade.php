@extends('layouts.app')

@section('title', 'Detail Laporan - ' . $roleLabel)
@section('page-title', 'Detail Laporan')

@section('content')
    @include('report-approval.partials.show', [
        'report'           => $report,
        'approval'         => $approval,
        'sectionInstances' => $sectionInstances,
        'lockMap'          => $lockMap,
        'returnTargets'    => $returnTargets,
        'approveRoute'     => $approveRoute,
        'returnRoute'      => $returnRoute,
        'backRoute'        => $backRoute,
        'roleLabel'        => $roleLabel,
    ])
@endsection
