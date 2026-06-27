<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Lead;
use App\Models\PraLead;
use App\Services\CodeGenerator;
use App\Services\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RequestMasukController extends Controller
{
    public function index(Request $request)
    {
        $uid = Auth::id();
        $query = PraLead::where('assigned_sales_id', $uid)
            ->whereIn('status', ['waiting_acceptance'])
            ->latest();

        if ($s = $request->get('q')) {
            $query->where(function ($w) use ($s) {
                $w->where('instansi', 'like', "%$s%")->orWhere('initial_need', 'like', "%$s%");
            });
        }
        if ($p = $request->get('priority')) {
            $query->where('priority', $p);
        }

        $requests = $query->paginate(8)->withQueryString();

        $stats = [
            'baru' => PraLead::where('assigned_sales_id', $uid)->where('status', 'waiting_acceptance')->whereDate('sent_at', '>=', today()->subDay())->count(),
            'hari_ini' => PraLead::where('assigned_sales_id', $uid)->whereDate('responded_at', today())->count(),
            'menunggu' => PraLead::where('assigned_sales_id', $uid)->where('status', 'waiting_acceptance')->count(),
            'ditolak' => PraLead::where('assigned_sales_id', $uid)->where('status', 'rejected')->whereDate('responded_at', '>=', today()->subDays(7))->count(),
        ];

        return view('sales.request_masuk.index', compact('requests', 'stats'));
    }

    public function accept(PraLead $praLead)
    {
        abort_if($praLead->assigned_sales_id !== Auth::id(), 403);

        DB::transaction(function () use ($praLead) {
            $praLead->update(['status' => 'accepted', 'responded_at' => now()]);

            $lead = Lead::create([
                'code' => CodeGenerator::next(Lead::class, 'LD', 5, true),
                'pra_lead_id' => $praLead->id,
                'instansi' => $praLead->instansi,
                'pic_name' => $praLead->pic_name,
                'pic_position' => $praLead->pic_position,
                'phone' => $praLead->phone,
                'email' => $praLead->email,
                'location' => $praLead->location,
                'source' => $praLead->source,
                'lab_name' => $praLead->lab_type,
                'need_description' => $praLead->initial_need,
                'est_value_min' => $praLead->est_value_min,
                'est_value_max' => $praLead->est_value_max,
                'priority' => $praLead->priority,
                'stage' => 'lead',
                'status' => 'aktif',
                'initial_note' => $praLead->admin_note,
                'sales_id' => $praLead->assigned_sales_id,
                'created_by' => Auth::id(),
            ]);

            Logger::record('accepted', "Request {$praLead->instansi} diterima dan menjadi Lead", $lead);
        });

        return redirect()->route('sales.leads.index')->with('success', 'Request diterima dan menjadi Lead.');
    }

    public function reject(Request $request, PraLead $praLead)
    {
        abort_if($praLead->assigned_sales_id !== Auth::id(), 403);
        $request->validate(['reject_reason' => ['required', 'string', 'max:500']]);

        $praLead->update([
            'status' => 'rejected',
            'reject_reason' => $request->reject_reason,
            'responded_at' => now(),
        ]);
        Logger::record('rejected', "Request {$praLead->instansi} ditolak", $praLead);

        return back()->with('success', 'Request ditolak.');
    }
}
