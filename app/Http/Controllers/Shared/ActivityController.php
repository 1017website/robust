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

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', 'today');
        $selectedDate = $request->get('date');
        $calendarMonth = (int) $request->get('cal_month', now()->month);
        $calendarYear = (int) $request->get('cal_year', now()->year);
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
        $selectedCustomer = $request->get('customer_id')
            ? $customerScope()->find($request->get('customer_id'))
            : ($stageWithCustomer ? $stageWithCustomer['customers']->first() : $customerScope()->first());
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
        return view('shared.activities.create', compact('customers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'lead_id' => ['nullable', 'exists:leads,id'],
            'type' => ['required', 'string'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'activity_date' => ['required', 'date'],
            'activity_time' => ['nullable'],
            'duration_minutes' => ['nullable', 'integer'],
            'location_link' => ['nullable', 'string', 'max:255'],
            'pipeline_stage' => ['nullable', 'in:'.implode(',', array_keys(Customer::stages()))],
            'status' => ['required', 'string'],
            'next_action' => ['nullable', 'string'],
            'next_followup_date' => ['nullable', 'date'],
        ]);
        if (! empty($data['customer_id'])) {
            $customer = Customer::findOrFail($data['customer_id']);
            abort_if(Auth::user()->isSales() && (int) $customer->sales_id !== (int) Auth::id(), 403);
            $data['pipeline_stage'] = $data['pipeline_stage'] ?: $customer->pipeline_stage;
        }
        $data['code'] = CodeGenerator::next(Activity::class, 'ACT-'.date('ymd'), 4);
        $data['sales_id'] = Auth::id();
        $data['created_by'] = Auth::id();

        $activity = Activity::create($data);
        Logger::record('created', "Aktivitas {$activity->title} dibuat", $activity);

        return redirect()->route('activities.index')->with('success', 'Aktivitas berhasil disimpan.');
    }

    public function updateStatus(Request $request, Activity $activity)
    {
        $request->validate(['status' => ['required', 'string'], 'result' => ['nullable', 'string']]);
        $activity->update($request->only('status', 'result'));
        return back()->with('success', 'Status aktivitas diperbarui.');
    }
}
