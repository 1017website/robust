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
use Illuminate\Support\Facades\Auth;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $query = Activity::with('customer', 'sales')->latest('activity_date');
        if (Auth::user()->isSales()) {
            $query->where('sales_id', Auth::id());
        }
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $activities = $query->paginate(10)->withQueryString();

        $stats = [
            'today' => Activity::whereDate('activity_date', today())->when(Auth::user()->isSales(), fn ($q) => $q->where('sales_id', Auth::id()))->count(),
            'pending' => Activity::where('status', 'pending')->when(Auth::user()->isSales(), fn ($q) => $q->where('sales_id', Auth::id()))->count(),
            'completed_today' => Activity::where('status', 'completed')->whereDate('updated_at', today())->when(Auth::user()->isSales(), fn ($q) => $q->where('sales_id', Auth::id()))->count(),
            'overdue' => Activity::where('status', '!=', 'completed')->whereDate('activity_date', '<', today())->when(Auth::user()->isSales(), fn ($q) => $q->where('sales_id', Auth::id()))->count(),
        ];

        $pipeline = collect(Customer::stages())->mapWithKeys(function ($label, $stage) {
            $q = Customer::where('pipeline_stage', $stage);
            if (Auth::user()->isSales()) $q->where('sales_id', Auth::id());
            return [$stage => ['label' => $label, 'customers' => $q->get()]];
        });

        return view('shared.activities.index', compact('activities', 'stats', 'pipeline'));
    }

    public function create()
    {
        $customers = Customer::orderBy('name')->get();
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
            'status' => ['required', 'string'],
            'next_action' => ['nullable', 'string'],
            'next_followup_date' => ['nullable', 'date'],
        ]);
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
