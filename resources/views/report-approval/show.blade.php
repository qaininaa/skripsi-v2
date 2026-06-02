@extends('layouts.app')

@section('title', 'Detail Laporan - ' . $roleLabel)
@section('page-title', 'Detail Laporan')

@section('content')
    @include('report-approval.partials.show', [
        'report'           => $report,
        'approval'         => $approval,
        'previewOnly'      => $previewOnly ?? false,
        'sectionInstances' => $sectionInstances,
        'lockMap'          => $lockMap,
        'returnTargets'    => $returnTargets,
        'approveRoute'     => $approveRoute,
        'returnRoute'      => $returnRoute,
        'saveMonitoringRoute' => $saveMonitoringRoute,
        'backRoute'        => $backRoute,
        'roleLabel'        => $roleLabel,
    ])
@endsection
