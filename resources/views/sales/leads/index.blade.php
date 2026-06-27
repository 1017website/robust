@extends('layouts.app')
@section('title', 'Leads')
@section('content')
<x-page-header title="Leads" subtitle="Daftar lead aktif yang Anda kelola">
    <a href="{{ route('sales.leads.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Lead Baru</a>
</x-page-header>

<div class="card-r">
    <form class="filter-bar" method="GET">
        <input type="text" name="q" value="{{ request('q') }}" class="form-control" placeholder="Cari instansi / PIC...">
        <select name="stage" class="form-select">
            <option value="">Semua Stage</option>
            @foreach(['lead'=>'Lead','design_request'=>'Design Request','penawaran'=>'Penawaran','negosiasi'=>'Negosiasi','won'=>'Won','lost'=>'Lost'] as $k=>$v)
                <option value="{{ $k }}" @selected(request('stage')==$k)>{{ $v }}</option>
            @endforeach
        </select>
        <button class="btn btn-soft btn-sm"><i class="bi bi-funnel me-1"></i>Filter</button>
    </form>

    <div class="table-wrap">
        <table class="table-r">
            <thead><tr><th>Kode</th><th>Instansi</th><th>PIC</th><th>Lab/Proyek</th><th>Estimasi</th><th>Stage</th><th>Prioritas</th><th></th></tr></thead>
            <tbody>
            @forelse($leads as $lead)
                <tr>
                    <td class="fw-semibold">{{ $lead->code }}</td>
                    <td>{{ $lead->instansi }}<div class="small text-muted-2">{{ $lead->city }}</div></td>
                    <td>{{ $lead->pic_name }}</td>
                    <td>{{ $lead->lab_name }}</td>
                    <td class="fw-num">{{ $lead->est_value_min ? \App\Support\Format::rupiahShort($lead->est_value_min) : '—' }}</td>
                    <td><x-status-badge :status="$lead->stage" /></td>
                    <td><x-status-badge :status="$lead->priority" /></td>
                    <td><a href="{{ route('sales.leads.show',$lead) }}" class="btn btn-sm btn-soft">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="8"><x-empty text="Belum ada lead." /></td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $leads->links() }}</div>
</div>
@endsection
