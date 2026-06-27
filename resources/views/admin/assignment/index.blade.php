@extends('layouts.app')
@section('title', 'Assignment')
@section('content')
<x-page-header title="Assignment & Workload" subtitle="Pantau beban kerja dan acceptance rate tim sales" />

<div class="stat-grid">
    <x-stat-card icon="bi-people" color="primary" label="Total Sales" :value="$stats['total_sales']" />
    <x-stat-card icon="bi-person-lines-fill" color="info" label="Total Leads" :value="$stats['total_leads']" />
    <x-stat-card icon="bi-folder" color="warning" label="Project Aktif" :value="$stats['active_projects']" />
    <x-stat-card icon="bi-check2-circle" color="success" label="Acceptance Rate" :value="$stats['acceptance_rate'].'%'" />
</div>

<div class="card-r">
    <div class="card-head"><h2>Beban Kerja per Sales</h2></div>
    <div class="table-wrap">
        <table class="table-r">
            <thead><tr><th>Sales</th><th>Request Masuk</th><th>Leads Aktif</th><th>Design Req</th><th>Penawaran</th><th>Project</th></tr></thead>
            <tbody>
            @foreach($workload as $row)
                <tr>
                    <td class="fw-semibold d-flex align-items-center gap-2">
                        <div class="user-chip"><div class="avatar">{{ strtoupper(substr($row['sales']->name,0,1)) }}</div></div>
                        {{ $row['sales']->name }}
                    </td>
                    <td>{{ $row['request_masuk'] }}</td>
                    <td>{{ $row['leads_aktif'] }}</td>
                    <td>{{ $row['design_request'] }}</td>
                    <td>{{ $row['penawaran_aktif'] }}</td>
                    <td>{{ $row['project_aktif'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card-r">
    <div class="card-head"><h2>Acceptance Rate</h2></div>
    <div class="table-wrap">
        <table class="table-r">
            <thead><tr><th>Sales</th><th>Assigned</th><th>Diterima</th><th>Ditolak</th><th>Rate</th></tr></thead>
            <tbody>
            @foreach($acceptance as $row)
                <tr>
                    <td class="fw-semibold">{{ $row['sales']->name }}</td>
                    <td>{{ $row['assigned'] }}</td>
                    <td class="text-success fw-semibold">{{ $row['accepted'] }}</td>
                    <td class="text-danger fw-semibold">{{ $row['rejected'] }}</td>
                    <td style="min-width:140px">
                        <div class="d-flex align-items-center gap-2">
                            <div class="prog flex-grow-1"><span style="width:{{ $row['rate'] }}%"></span></div>
                            <span class="small fw-semibold">{{ $row['rate'] }}%</span>
                        </div>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
