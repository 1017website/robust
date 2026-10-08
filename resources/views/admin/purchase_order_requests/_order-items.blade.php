<div class="card-r">
    <div class="card-head"><h2>Item untuk Produksi &amp; QC</h2><button type="button" id="addOrderItem" class="btn btn-soft btn-sm">Tambah Item</button></div>
    <p class="small text-muted-2">Nama barang, jumlah, unit, dan spesifikasi wajib lengkap sebelum project diajukan. Gambar dapat ditambahkan sebagai acuan.</p>
    <div class="table-wrap">
        <table class="table-r" style="min-width:1000px"><thead><tr><th>Nama Item</th><th>Qty</th><th>Unit</th><th>Harga Satuan</th><th>Spesifikasi</th><th>Gambar (opsional)</th><th></th></tr></thead><tbody id="orderItemRows"></tbody></table>
    </div>
    <div class="row g-3 mt-1">
        <div class="col-md-3"><label class="form-label">Tipe Diskon</label><select class="form-select order-charge" name="order_discount_type"><option value="percent" @selected(old('order_discount_type', $quotation?->discount_type) !== 'nominal')>Persen (%)</option><option value="nominal" @selected(old('order_discount_type', $quotation?->discount_type) === 'nominal')>Nominal (Rp)</option></select></div>
        <div class="col-md-3"><label class="form-label">Diskon</label><input class="form-control order-charge" name="order_discount_value" data-qty value="{{ old('order_discount_value', $quotation?->discount_value ?? 0) }}"></div>
        <div class="col-md-3"><label class="form-label">PPN (%)</label><input class="form-control order-charge" name="order_tax_percent" data-qty value="{{ old('order_tax_percent', $quotation?->tax_percent ?? 0) }}"></div>
        <div class="col-md-3"><label class="form-label">Biaya Tambahan</label><input class="form-control order-charge" name="order_additional_cost" data-rupiah value="{{ old('order_additional_cost', $quotation?->additional_total ?? 0) }}"></div>
        <div class="col-md-6"><label class="form-label" for="poTotal">Total Nilai PO *</label><input id="poTotal" name="po_total" class="form-control" data-rupiah required value="{{ old('po_total', $quotation?->grand_total) }}"><div class="form-text">Harus sesuai total rincian setelah diskon, pajak, dan biaya tambahan.</div></div>
        <div class="col-md-6 align-self-center"><span class="text-muted-2">Total Rincian</span><strong id="orderCalculatedTotal" class="d-block">Rp 0</strong><span class="small" id="orderValueMatch" role="status"></span></div>
    </div>
</div>
