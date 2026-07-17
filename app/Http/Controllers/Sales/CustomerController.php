<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Services\CodeGenerator;
use App\Services\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        if (! Auth::user()->isSales() && $salesId = $request->get('sales_id')) {
            $query->where('sales_id', $salesId);
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
        $selectedCustomer = null;
        if (! $request->boolean('hide_detail')) {
            $detailQuery = Customer::with([
                'pics',
                'sales',
                'projects' => fn ($query) => $query->latest(),
                'quotations' => fn ($query) => $query->latest(),
                'activities' => fn ($query) => $query->latest('activity_date'),
                'documents' => fn ($query) => $query->latest(),
            ])->when(Auth::user()->isSales(), fn ($query) => $query->where('sales_id', Auth::id()));

            $selectedCustomer = $request->filled('customer')
                ? $detailQuery->find($request->integer('customer'))
                : ($customers->first() ? $detailQuery->find($customers->first()->id) : null);
        }
        $salesList = User::assignableSales();

        return view('sales.customers.index', compact('customers', 'stats', 'selectedCustomer', 'salesList'));
    }

    public function create()
    {
        $salesList = User::assignableSales();

        return view('sales.customers.create', compact('salesList'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['code'] = CodeGenerator::next(Customer::class, 'CUST', 4);
        $data['sales_id'] = Auth::user()->isSales() ? Auth::id() : $data['sales_id'];
        $customer = DB::transaction(function () use ($data) {
            $customer = Customer::create($data);
            if (! empty($data['pic_name'])) {
                $customer->pics()->create([
                    'name' => $data['pic_name'],
                    'position' => $data['pic_position'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'is_primary' => true,
                ]);
            }

            return $customer;
        });

        Logger::record('created', "Customer {$customer->name} ditambahkan", $customer);

        return redirect()->route('sales.customers.show', $customer)->with('success', 'Customer berhasil disimpan.');
    }

    public function show(Customer $customer)
    {
        $this->ensureAccess($customer);
        $customer->load('pics', 'sales', 'projects', 'quotations', 'activities');

        return view('sales.customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        $this->ensureAccess($customer);
        $customer->load('primaryPic');
        $salesList = User::assignableSales();

        return view('sales.customers.edit', compact('customer', 'salesList'));
    }

    public function update(Request $request, Customer $customer)
    {
        $this->ensureAccess($customer);
        $data = $this->validateData($request);
        if (Auth::user()->isSales()) {
            unset($data['sales_id']);
        }
        DB::transaction(function () use ($customer, $data) {
            $customer->update($data);
            if (! empty($data['pic_name'])) {
                $customer->primaryPic()->updateOrCreate([], [
                    'name' => $data['pic_name'],
                    'position' => $data['pic_position'] ?? null,
                    'phone' => $data['phone'] ?? null,
                    'email' => $data['email'] ?? null,
                    'is_primary' => true,
                ]);
            }
        });
        Logger::record('updated', "Customer {$customer->name} diperbarui", $customer);

        return redirect()->route('sales.customers.show', $customer)->with('success', 'Customer diperbarui.');
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', Rule::in(Customer::categories())],
            'type' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'website' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'pipeline_stage' => ['required', Rule::in(array_keys(Customer::stages()))],
            'probability' => ['nullable', 'integer', 'min:0', 'max:100'],
            'pic_name' => ['nullable', 'string', 'max:255'],
            'pic_position' => ['nullable', 'string', 'max:255'],
            'partner_since' => ['nullable', 'date'],
            'sales_id' => [
                Rule::requiredIf(fn () => ! Auth::user()->isSales()),
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', 'sales')
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
            'notes' => ['nullable', 'string'],
        ]);
    }

    protected function ensureAccess(Customer $customer): void
    {
        abort_if(
            Auth::user()->isSales() && (int) $customer->sales_id !== (int) Auth::id(),
            403,
            'Customer ini bukan milik Anda.'
        );
    }
}
