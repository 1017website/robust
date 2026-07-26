@props([
    'item',
    'showCost' => true,
    'showMasterCode' => true,
    'priceLabel' => 'Harga Jual',
])

<article {{ $attributes->class(['quotation-item-card', 'without-cost' => ! $showCost]) }}>
    <div class="quotation-item-card-head">
        <div class="quotation-item-identity">
            @if($item->quotation_image_path)
                <img src="{{ asset('storage/'.$item->quotation_image_path) }}" alt="{{ $item->name }}" class="quotation-item-thumb">
            @else
                <div class="quotation-item-thumb d-grid align-items-center justify-content-center text-muted-2"><i class="bi bi-image"></i></div>
            @endif
            <div>
                <strong>{{ $item->name }}</strong>
                @if($item->is_optional)<span class="badge text-bg-secondary mt-1">Opsional</span>@endif
                @if($item->variant)<small class="text-primary">{{ $item->variant }}</small>@endif
                @if($showMasterCode && $item->itemMaster)<small class="text-muted-2">{{ $item->itemMaster->code }}</small>@endif
            </div>
        </div>
        <div class="quotation-item-metric">
            <span>Qty</span>
            <b>{{ rtrim(rtrim(number_format($item->qty, 2), '0'), '.') }} {{ $item->unit }}</b>
        </div>
        @if($showCost)
            <div class="quotation-item-metric">
                <span>HPP</span>
                <b>{{ \App\Support\Format::rupiah($item->cost_price) }}</b>
            </div>
        @endif
        <div class="quotation-item-metric">
            <span>{{ $priceLabel }}</span>
            <b>{{ \App\Support\Format::rupiah($item->unit_price) }}</b>
            @if($showCost)<small class="text-success">Margin dari harga jual {{ rtrim(rtrim(number_format($item->margin, 2), '0'), '.') }}%</small>@endif
        </div>
        <div class="quotation-item-metric">
            <span>Total</span>
            <b>{{ \App\Support\Format::rupiah($item->total) }}</b>
        </div>
    </div>
    <div class="quotation-item-spec">
        <x-specification-view :specification="$item->specification" />
    </div>
</article>
