@extends('layouts.app')
@section('title', 'Preview '.$document->name)
@section('content')
<x-page-header :title="'Preview '.$document->name" :subtitle="strtoupper($document->file_type ?: 'file').' · '.$document->humanSize()">
    <a href="{{ route('documents.download', $document) }}" class="btn btn-primary btn-sm"><i class="bi bi-download me-1"></i>Download</a>
</x-page-header>

<div class="card-r">
    @if($previewError)
        <div class="text-center py-5">
            <i class="bi bi-file-earmark fs-1 text-primary"></i>
            <h2 class="h5 mt-3">Preview isi belum tersedia</h2>
            <p class="text-muted-2 mb-0">{{ $previewError }}</p>
        </div>
    @elseif(empty($preview['rows']))
        <x-empty text="File tidak memiliki data yang dapat ditampilkan." />
    @else
        <div class="d-flex justify-content-between align-items-center mb-3">
            <strong>{{ $preview['sheet'] }}</strong>
            @if($preview['truncated'])<span class="badge text-bg-light">Menampilkan 60 baris pertama</span>@endif
        </div>
        <div class="table-wrap">
            <table class="table-r">
                <tbody>
                @foreach($preview['rows'] as $row)
                    <tr>@foreach($row as $cell)<td style="white-space:pre-wrap;min-width:120px">{{ $cell }}</td>@endforeach</tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
