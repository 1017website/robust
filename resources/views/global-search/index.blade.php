@extends('layouts.app')
@section('title', 'Search')

@section('content')
<div class="page-header">
    <div>
        <h1 class="page-title">Search</h1>
        <div class="page-subtitle">
            @if($query)
                Menampilkan {{ $total }} hasil untuk "{{ $query }}".
            @else
                Ketik kata kunci di search bar untuk mencari data.
            @endif
        </div>
    </div>
</div>

<form class="card-r global-search-form" method="GET" action="{{ route('global-search.index') }}">
    <div class="search-box">
        <i class="bi bi-search"></i>
        <input type="text" name="q" value="{{ $query }}" class="form-control" placeholder="Cari customer, PIC, proyek, aktivitas, dokumen..." autofocus>
    </div>
    <button class="btn btn-primary"><i class="bi bi-search me-1"></i>Cari</button>
</form>

@if($query && $total < 1)
    <div class="card-r mt-3">
        <x-empty text="Tidak ada hasil yang cocok." />
    </div>
@endif

@foreach($results as $group => $items)
    <section class="card-r search-result-section">
        <div class="card-head">
            <h2>{{ $group }}</h2>
            <span class="pill">{{ $items->count() }} hasil</span>
        </div>
        <div class="search-result-list">
            @foreach($items as $item)
                <a href="{{ $item['href'] }}" class="search-result-item">
                    <i class="bi {{ $item['icon'] }}"></i>
                    <span>
                        <strong>{{ $item['title'] }}</strong>
                        <small>{{ $item['subtitle'] }}</small>
                    </span>
                    <i class="bi bi-arrow-right-short"></i>
                </a>
            @endforeach
        </div>
    </section>
@endforeach
@endsection
