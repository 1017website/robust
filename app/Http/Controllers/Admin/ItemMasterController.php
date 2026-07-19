<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItemMaster;
use App\Services\CodeGenerator;
use Illuminate\Http\Request;

class ItemMasterController extends Controller
{
    public function index(Request $request)
    {
        $items = ItemMaster::query()
            ->when($request->filled('q'), fn ($query) => $query->where(fn ($scope) => $scope
                ->where('code', 'like', '%'.$request->q.'%')->orWhere('name', 'like', '%'.$request->q.'%')
                ->orWhere('variant', 'like', '%'.$request->q.'%')->orWhere('category', 'like', '%'.$request->q.'%')))
            ->orderBy('category')->orderBy('name')->paginate(15)->withQueryString();

        return view('admin.item_masters.index', compact('items'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['code'] = $data['code'] ?: CodeGenerator::next(ItemMaster::class, 'ITM', 4);
        ItemMaster::create($data);
        return back()->with('success', 'Master item berhasil ditambahkan.');
    }

    public function update(Request $request, ItemMaster $itemMaster)
    {
        $itemMaster->update($this->validated($request, $itemMaster));
        return back()->with('success', 'Master item berhasil diperbarui.');
    }

    protected function validated(Request $request, ?ItemMaster $itemMaster = null): array
    {
        return $request->validate([
            'code' => ['nullable', 'string', 'max:50', 'unique:item_masters,code,'.($itemMaster?->id ?: 'NULL')],
            'category' => ['required', 'string', 'max:100'], 'name' => ['required', 'string', 'max:255'],
            'variant' => ['nullable', 'string', 'max:255'], 'specification' => ['nullable', 'string', 'max:2000'],
            'unit' => ['required', 'string', 'max:50'], 'default_cost_price' => ['nullable', 'numeric', 'min:0'],
            'default_margin' => ['nullable', 'numeric', 'min:0', 'max:99.99'], 'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active')];
    }
}
