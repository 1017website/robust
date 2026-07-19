<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\User;
use App\Services\CodeGenerator;
use App\Services\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'today');
        $selectedDate = $request->get('date');
        $calendarMonth = min(12, max(1, (int) $request->get('cal_month', now()->month)));
        $calendarYear = min(2100, max(2000, (int) $request->get('cal_year', now()->year)));
        $calendarFirst = Carbon::create($calendarYear, $calendarMonth, 1);
        $calendarPrev = $calendarFirst->copy()->subMonth();
        $calendarNext = $calendarFirst->copy()->addMonth();

        $query = Activity::with('customer.primaryPic', 'lead', 'sales')
            ->orderByDesc('activity_date')
            ->orderBy('activity_time');
        if (Auth::user()->isSales()) {
            $query->where('sales_id', Auth::id());
        }
        if (! Auth::user()->isSales() && $salesId = $request->get('sales_id')) {
            $query->where('sales_id', $salesId);
        }
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($stage = $request->get('pipeline_stage')) {
            $query->where('pipeline_stage', $stage);
        }
        if ($customerId = $request->get('customer_id')) {
            $query->where('customer_id', $customerId);
        }
        if ($selectedDate) {
            $query->whereDate('activity_date', $selectedDate);
        } elseif ($period === 'week') {
            $query->whereBetween('activity_date', [now()->startOfWeek(), now()->endOfWeek()]);
        } elseif ($period === 'month') {
            $query->whereYear('activity_date', now()->year)->whereMonth('activity_date', now()->month);
        } else {
            $query->whereDate('activity_date', today());
        }

        $activities = $query->paginate(10)->withQueryString();

        $selectedActivityQuery = Activity::with('customer.primaryPic', 'lead', 'sales');
        if (Auth::user()->isSales()) {
            $selectedActivityQuery->where('sales_id', Auth::id());
        }
        $selectedActivity = $request->get('activity')
            ? $selectedActivityQuery->find($request->get('activity'))
            : $activities->first();

        $activityScope = fn () => Activity::query()
            ->when(Auth::user()->isSales(), fn ($q) => $q->where('sales_id', Auth::id()))
            ->when(! Auth::user()->isSales() && $request->get('sales_id'), fn ($q) => $q->where('sales_id', $request->get('sales_id')));

        $stats = [
            'today' => $activityScope()->whereDate('activity_date', today())->count(),
            'pending' => $activityScope()->whereNotIn('status', ['completed', 'cancelled'])->count(),
            'completed_today' => $activityScope()->where('status', 'completed')->whereDate('updated_at', today())->count(),
            'overdue' => $activityScope()->whereNotIn('status', ['completed', 'cancelled'])->whereDate('activity_date', '<', today())->count(),
        ];

        $customerScope = fn () => Customer::with('primaryPic', 'sales')
            ->when(Auth::user()->isSales(), fn ($q) => $q->where('sales_id', Auth::id()))
            ->when(! Auth::user()->isSales() && $request->get('sales_id'), fn ($q) => $q->where('sales_id', $request->get('sales_id')));

        $pipeline = collect(Customer::stages())->mapWithKeys(function ($label, $stage) use ($customerScope) {
            $q = $customerScope()->where('pipeline_stage', $stage)->latest('updated_at');
            return [$stage => ['label' => $label, 'customers' => $q->get()]];
        });

        $stageWithCustomer = $pipeline->first(fn ($data) => $data['customers']->isNotEmpty());
        $previewCustomerId = $request->get('preview_customer', $request->get('customer_id'));
        $selectedCustomer = $request->boolean('hide_detail')
            ? null
            : ($previewCustomerId
                ? $customerScope()->find($previewCustomerId)
                : ($stageWithCustomer ? $stageWithCustomer['customers']->first() : $customerScope()->first()));
        $salesUsers = User::assignableSales();
        $customers = $customerScope()->orderBy('name')->get();

        return view('shared.activities.index', compact(
            'activities',
            'stats',
            'pipeline',
            'selectedCustomer',
            'salesUsers',
            'customers',
            'selectedActivity',
            'period',
            'selectedDate',
            'calendarFirst',
            'calendarPrev',
            'calendarNext'
        ));
    }

    public function create()
    {
        $customers = Customer::when(Auth::user()->isSales(), fn ($q) => $q->where('sales_id', Auth::id()))
            ->orderBy('name')
            ->get();
        $salesUsers = User::assignableSales();
        return view('shared.activities.create', compact('customers', 'salesUsers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['nullable', Rule::exists('customers', 'id')->whereNull('deleted_at')],
            'lead_id' => ['nullable', Rule::exists('leads', 'id')->whereNull('deleted_at')],
            'type' => ['required', Rule::in(array_keys(Activity::types()))],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'activity_date' => ['required', 'date'],
            'activity_time' => ['nullable'],
            'duration_minutes' => ['nullable', 'integer'],
            'location_link' => ['nullable', 'string', 'max:255'],
            'pipeline_stage' => ['nullable', 'in:'.implode(',', array_keys(Customer::stages()))],
            'status' => ['required', Rule::in(array_keys(Activity::statuses()))],
            'next_action' => ['nullable', 'string'],
            'next_followup_date' => ['nullable', 'date'],
            'sales_id' => [
                Rule::requiredIf(fn () => ! Auth::user()->isSales()),
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', 'sales')
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
        ]);
        if (! empty($data['customer_id'])) {
            $customer = Customer::findOrFail($data['customer_id']);
            abort_if(Auth::user()->isSales() && (int) $customer->sales_id !== (int) Auth::id(), 403);
            if (! Auth::user()->isSales() && (int) $customer->sales_id !== (int) $data['sales_id']) {
                throw ValidationException::withMessages(['customer_id' => 'Customer tidak dimiliki oleh sales yang dipilih.']);
            }
            $data['pipeline_stage'] = ($data['pipeline_stage'] ?? null) ?: $customer->pipeline_stage;
        }
        if (! empty($data['lead_id'])) {
            $lead = Lead::findOrFail($data['lead_id']);
            abort_if(Auth::user()->isSales() && (int) $lead->sales_id !== (int) Auth::id(), 403);
            if (! Auth::user()->isSales() && (int) $lead->sales_id !== (int) $data['sales_id']) {
                throw ValidationException::withMessages(['lead_id' => 'Lead tidak dimiliki oleh sales yang dipilih.']);
            }
        }
        $data['code'] = CodeGenerator::next(Activity::class, 'ACT-'.date('ymd'), 4);
        $data['sales_id'] = Auth::user()->isSales() ? Auth::id() : $data['sales_id'];
        $data['created_by'] = Auth::id();

        $activity = Activity::create($data);
        Logger::record('created', "Aktivitas {$activity->title} dibuat", $activity);

        return redirect()->route('activities.index')->with('success', 'Aktivitas berhasil disimpan.');
    }

    public function updateStatus(Request $request, Activity $activity)
    {
        abort_if(Auth::user()->isSales() && (int) $activity->sales_id !== (int) Auth::id(), 403);
        $request->validate([
            'status' => ['required', Rule::in(array_keys(Activity::statuses()))],
            'result' => ['nullable', 'string', 'max:2000'],
        ]);
        $activity->update($request->only('status', 'result'));
        return back()->with('success', 'Status aktivitas diperbarui.');
    }
}
