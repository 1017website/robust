const orderQuotations = @json($orderQuotationData);
let initialOrderItems = @json($orderItemValues);
let orderItemIndex = 0;
const orderRows = document.getElementById('orderItemRows');
const orderEsc = value => String(value ?? '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
const orderDirect = () => document.querySelector('[name="purchase_source"]:checked')?.value === 'external';
function addOrderItem(item = {}, index = null) {
    const i = index === null ? orderItemIndex : Number(index);
    orderItemIndex = Math.max(orderItemIndex, i + 1);
    const readonly = orderDirect() ? '' : 'readonly';
    const tr = document.createElement('tr');
    tr.dataset.optional = item.is_optional ? '1' : '0';
    tr.innerHTML = `<td><input type="hidden" name="order_items[${i}][id]" value="${orderEsc(item.id)}"><input aria-label="Nama item" class="form-control form-control-sm" name="order_items[${i}][name]" value="${orderEsc(item.name)}" required ${readonly}>${item.is_optional ? '<small>Item alternatif</small>' : ''}</td>
        <td><input aria-label="Jumlah" class="form-control form-control-sm" style="min-width:70px" name="order_items[${i}][qty]" value="${orderEsc(item.qty ?? 1)}" data-qty required ${readonly}></td>
        <td><input aria-label="Unit" class="form-control form-control-sm" style="min-width:70px" name="order_items[${i}][unit]" value="${orderEsc(item.unit ?? 'Unit')}" required ${readonly}></td>
        <td><input aria-label="Harga satuan" class="form-control form-control-sm" style="min-width:130px" name="order_items[${i}][unit_price]" value="${orderEsc(item.unit_price ?? '')}" data-rupiah required ${readonly}></td>
        <td><textarea aria-label="Spesifikasi item (opsional)" class="form-control form-control-sm" style="min-width:220px" name="order_items[${i}][specification]" rows="3">${orderEsc(item.specification)}</textarea></td>
        <td>${item.quotation_image_path ? `<a href="${@json(asset('storage'))}/${orderEsc(item.quotation_image_path)}" target="_blank" rel="noopener">Lihat gambar</a>` : ''}<input aria-label="Gambar item" class="form-control form-control-sm" name="order_items[${i}][image]" type="file" accept=".jpg,.jpeg,.png,.webp"></td>
        <td>${orderDirect() ? '<button type="button" class="btn btn-soft btn-sm remove-order-item" aria-label="Hapus item">Hapus</button>' : ''}</td>`;
    orderRows.appendChild(tr);
    if (window.bindNumberInputs) bindNumberInputs(tr);
    tr.querySelector('.remove-order-item')?.addEventListener('click', () => { tr.remove(); calculateOrderTotal(); });
    calculateOrderTotal();
}
function calculateOrderTotal() {
    const number = input => window.numberValue ? window.numberValue(input) : Number(input?.value || 0);
    const quote = orderQuotations[quotationSelect.value];
    let subtotal = 0;
    orderRows.querySelectorAll('tr').forEach(tr => {
        if (tr.dataset.optional === '1') return;
        subtotal += number(tr.querySelector('[name$="[qty]"]')) * number(tr.querySelector('[name$="[unit_price]"]'));
    });
    const discount = number(document.querySelector('[name="order_discount_value"]'));
    const deduction = document.querySelector('[name="order_discount_type"]').value === 'percent' ? subtotal * discount / 100 : discount;
    const net = subtotal - Math.min(deduction, subtotal);
    const total = !orderDirect() && quote ? Number(quote.total) : Math.round((net * (1 + number(document.querySelector('[name="order_tax_percent"]')) / 100) + number(document.querySelector('[name="order_additional_cost"]'))) * 100) / 100;
    document.getElementById('orderCalculatedTotal').textContent = 'Rp ' + new Intl.NumberFormat('id-ID', { maximumFractionDigits: 0 }).format(total);
    const entered = number(document.getElementById('poTotal'));
    document.getElementById('orderValueMatch').textContent = Math.abs(entered - total) < 0.01 ? 'Nilai PO sesuai rincian.' : 'Nilai PO belum sesuai rincian.';
}
function formatOrderCharges() {
    const discount = document.querySelector('[name="order_discount_value"]');
    const nominal = document.querySelector('[name="order_discount_type"]').value === 'nominal';
    discount.toggleAttribute('data-rupiah', nominal);
    discount.toggleAttribute('data-qty', !nominal);
    discount.dataset.numberKind = nominal ? 'currency' : 'decimal';
    formatNumberEl(discount, discount.dataset.numberKind);
}
function renderOrderItems() {
    const direct = orderDirect();
    document.getElementById('addOrderItem').classList.toggle('d-none', !direct);
    document.querySelectorAll('.order-charge').forEach(input => input.disabled = !direct);
    const quote = orderQuotations[quotationSelect.value];
    const rows = initialOrderItems ?? (direct ? [] : quote?.items ?? []);
    initialOrderItems = null;
    orderRows.replaceChildren();
    orderItemIndex = 0;
    Object.entries(rows).forEach(([index, row]) => addOrderItem(row, index));
    if (direct && !Object.keys(rows).length) addOrderItem();
    if (!direct && quote) {
        document.querySelector('[name="order_discount_type"]').value = quote.discount_type || 'percent';
        for (const field of ['discount_value','tax_percent','additional_cost']) setNumberInputValue(document.querySelector(`[name="order_${field}"]`), quote[field] || 0);
        if (!document.getElementById('poTotal').value) setNumberInputValue(document.getElementById('poTotal'), quote.total);
    }
    formatOrderCharges();
    if (window.bindNumberInputs) bindNumberInputs(document.querySelector('.order-charge').closest('.row'));
    calculateOrderTotal();
}
document.getElementById('addOrderItem').addEventListener('click', () => addOrderItem());
document.querySelectorAll('.order-charge,#poTotal').forEach(input => input.addEventListener('input', calculateOrderTotal));
document.querySelector('[name="order_discount_type"]').addEventListener('change', () => { formatOrderCharges(); calculateOrderTotal(); });
orderRows.addEventListener('input', calculateOrderTotal);
quotationSelect.addEventListener('change', () => {
    initialOrderItems = null;
    setNumberInputValue(document.getElementById('poTotal'), orderQuotations[quotationSelect.value]?.total ?? '');
    renderOrderItems();
});
syncPurchaseSource();
