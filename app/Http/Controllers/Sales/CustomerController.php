<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Services\CodeGenerator;
use App\Services\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::with('primaryPic', 'sales')->latest();

        if (Auth::user()->isSales()) {
            $query->where('sales_id', Auth::id());
        }
        if ($s = $request->get('q')) {
            $query->where(fn ($w) => $w->where('name', 'like', "%$s%")->orWhere('email', 'like', "%$s%"));
        }
        if ($status = $request->get('status')) {
            $query->where('pipeline_stage', $status);
        }
        if ($cat = $request->get('category')) {
            $query->where('category', $cat);
        }

        $customers = $query->paginate(10)->withQueryString();

        $scope = fn () => Customer::query()->when(Auth::user()->isSales(), fn ($q) => $q->where('sales_id', Auth::id()));
        $stats = [
            'total' => $scope()->count(),
            'identify' => $scope()->where('pipeline_stage', 'identify')->count(),
            'approaching' => $scope()->where('pipeline_stage', 'approaching')->count(),
            'follow_up' => $scope()->where('pipeline_stage', 'follow_up')->count(),
            'won' => $scope()->where('pipeline_stage', 'won_closing')->count(),
            'lost' => $scope()->where('pipeline_stage', 'lost')->count(),
            'maintaining' => $scope()->where('pipeline_stage', 'maintaining')->count(),
        ];
        $selectedCustomer = $customers->first();

        return view('sales.customers.index', compact('customers', 'stats', 'selectedCustomer'));
    }

    public function create()
    {
        return view('sales.customers.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['code'] = CodeGenerator::next(Customer::class, 'CUST', 4);
        $data['sales_id'] = Auth::id();
        $customer = Customer::create($data);

        $customer->pics()->create([
            'name' => $request->pic_name,
            'position' => $request->pic_position,
            'phone' => $request->phone,
            'email' => $request->email,
            'is_primary' => true,
        ]);

        Logger::record('created', "Customer {$customer->name} ditambahkan", $customer);
        return redirect()->route('sales.customers.show', $customer)->with('success', 'Customer berhasil disimpan.');
    }

    public function show(Customer $customer)
    {
        $customer->load('pics', 'sales', 'projects', 'quotations', 'activities');
        return view('sales.customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        return view('sales.customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($this->validateData($request));
        Logger::record('updated', "Customer {$customer->name} diperbarui", $customer);
        return redirect()->route('sales.customers.show', $customer)->with('success', 'Customer diperbarui.');
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'pipeline_stage' => ['required', 'string'],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'pic_position' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
