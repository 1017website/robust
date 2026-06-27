<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PraLead;
use App\Models\User;
use App\Services\CodeGenerator;
use App\Services\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PraLeadController extends Controller
{
    public function index(Request $request)
    {
        $query = PraLead::with('assignedSales')->latest();

        if ($s = $request->get('q')) {
            $query->where(function ($w) use ($s) {
                $w->where('instansi', 'like', "%$s%")
                  ->orWhere('pic_name', 'like', "%$s%")
                  ->orWhere('phone', 'like', "%$s%")
                  ->orWhere('initial_need', 'like', "%$s%");
            });
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($source = $request->get('source')) {
            $query->where('source', $source);
        }

        $praLeads = $query->paginate(10)->withQueryString();

        $counts = [
            'all' => PraLead::count(),
            'draft' => PraLead::where('status', 'draft')->count(),
            'assigned' => PraLead::where('status', 'assigned')->count(),
            'waiting' => PraLead::where('status', 'waiting_acceptance')->count(),
            'rejected' => PraLead::where('status', 'rejected')->count(),
        ];

        $salesList = User::where('role', 'sales')->where('is_active', true)->get();

        return view('admin.pra_leads.index', compact('praLeads', 'counts', 'salesList'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $data['code'] = CodeGenerator::next(PraLead::class, 'PRA', 4);
        $data['created_by'] = Auth::id();
        $data['status'] = $request->input('action') === 'send' ? 'waiting_acceptance' : ($data['assigned_sales_id'] ? 'assigned' : 'draft');
        if ($data['status'] === 'waiting_acceptance') {
            $data['sent_at'] = now();
        }

        $praLead = PraLead::create($data);
        Logger::record('created', "Pra Lead {$praLead->instansi} dibuat", $praLead);

        return redirect()->route('admin.pra-leads.index')->with('success', 'Pra Lead berhasil disimpan.');
    }

    public function update(Request $request, PraLead $praLead)
    {
        $data = $this->validateData($request);
        if ($request->input('action') === 'send') {
            $data['status'] = 'waiting_acceptance';
            $data['sent_at'] = now();
        }
        $praLead->update($data);
        Logger::record('updated', "Pra Lead {$praLead->instansi} diperbarui", $praLead);

        return redirect()->route('admin.pra-leads.index')->with('success', 'Pra Lead diperbarui.');
    }

    public function destroy(PraLead $praLead)
    {
        $praLead->delete();
        return back()->with('success', 'Pra Lead dihapus.');
    }

    protected function validateData(Request $request): array
    {
        return $request->validate([
            'instansi' => ['required', 'string', 'max:255'],
            'pic_name' => ['required', 'string', 'max:255'],
            'pic_position' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'source' => ['required', 'string'],
            'lab_type' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'initial_need' => ['nullable', 'string'],
            'admin_note' => ['nullable', 'string'],
            'est_value_min' => ['nullable', 'numeric'],
            'est_value_max' => ['nullable', 'numeric'],
            'priority' => ['required', 'in:low,medium,high'],
            'assigned_sales_id' => ['nullable', 'exists:users,id'],
        ]);
    }
}
