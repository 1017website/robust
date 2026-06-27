@extends('layouts.app')
@section('title', 'Reports')
@section('content')
<x-page-header title="Reports" subtitle="Ringkasan performa penjualan" />

<div class="stat-grid">
    <x-stat-card icon="bi-people" color="primary" label="Total Leads" :value="$summary['total_leads']" />
    <x-stat-card icon="bi-file-earmark-text" color="info" label="Total Penawaran" :value="$summary['total_quotations']" />
    <x-stat-card icon="bi-trophy" color="success" label="Won" :value="$summary['won']" :sub="$winRate.'% win rate'" />
    <x-stat-card icon="bi-cash-stack" color="warning" label="Nilai Won" :value="\App\Support\Format::rupiahShort($summary['total_value'])" />
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card-r">
            <div class="card-head"><h2>Penawaran per Bulan</h2></div>
            <div style="height:280px"><canvas id="monthlyChart"></canvas></div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card-r">
            <div class="card-head"><h2>Pipeline Customer</h2></div>
            <div class="table-wrap">
                <table class="table-r"><thead><tr><th>Stage</th><th>Jumlah</th></tr></thead><tbody>
                @foreach($pipelineValue as $stage)
                    <tr><td class="fw-semibold">{{ $stage['label'] }}</td><td>{{ $stage['count'] }}</td></tr>
                @endforeach
                </tbody></table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const months=['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
const mData=@json($monthly);
const labels=[],counts=[];
for(let m=1;m<=12;m++){ labels.push(months[m-1]); counts.push(mData[m]?mData[m].total:0); }
robustChart('monthlyChart','bar',labels,counts,'#1d6fe0');
</script>
@endpush
@endsection
