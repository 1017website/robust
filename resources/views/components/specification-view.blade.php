@props(['specification', 'compact' => false])
@php($sections = \App\Support\StructuredSpecification::parse($specification))

@if($sections)
    <div {{ $attributes->class(['structured-spec-view', 'is-compact' => $compact]) }}>
        @foreach($sections as $section)
            <details class="structured-spec-section" @if($loop->first) open @endif>
                <summary class="structured-spec-title">
                    <i class="bi bi-layers"></i>
                    <span>{{ $section['title'] }}</span>
                    <i class="bi bi-chevron-down structured-spec-chevron"></i>
                </summary>
                <div class="structured-spec-rows">
                    @foreach($section['rows'] as $row)
                        @if($row['type'] === 'breakdown')
                            <div class="structured-spec-breakdown">
                                <span class="spec-breakdown-label">{{ $row['label'] ?: 'Rincian' }}</span>
                                <span class="spec-breakdown-qty">{{ rtrim(rtrim(number_format($row['qty'], 2), '0'), '.') }}</span>
                                <span class="spec-breakdown-unit">{{ $row['unit'] }}</span>
                                @if($row['unit_price'] !== null)
                                    <span class="spec-breakdown-price">{{ \App\Support\Format::rupiah($row['unit_price']) }}</span>
                                    <span class="spec-breakdown-total">{{ \App\Support\Format::rupiah($row['qty'] * $row['unit_price']) }}</span>
                                @endif
                            </div>
                        @else
                            <div class="structured-spec-detail">
                                @if(filled($row['label']))
                                    <span class="structured-spec-label">{{ $row['label'] }}</span>
                                @endif
                                <span class="structured-spec-value">{{ $row['value'] ?: '—' }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </details>
        @endforeach
    </div>
@else
    <span class="text-muted-2">—</span>
@endif
