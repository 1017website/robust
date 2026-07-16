<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\PraLead;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\User;
use App\Services\CodeGenerator;
use App\Services\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
        if ($createdDate = $request->get('created_date')) {
            $query->whereDate('created_at', $createdDate);
        }

        $praLeads = $query->paginate(8)->withQueryString();

        $counts = [
            'all' => PraLead::count(),
            'draft' => PraLead::where('status', 'draft')->count(),
            'assigned' => PraLead::where('status', 'assigned')->count(),
            'waiting' => PraLead::where('status', 'waiting_acceptance')->count(),
            'rejected' => PraLead::where('status', 'rejected')->count(),
        ];

        $salesList = User::assignableSales();
        $salesWorkloads = $salesList->map(function (User $sales) {
            $activeLeadCount = Lead::where('sales_id', $sales->id)->whereIn('status', ['aktif', 'active'])->count();
            $waitingPraLeadCount = PraLead::where('assigned_sales_id', $sales->id)->where('status', 'waiting_acceptance')->count();
            $quotationCount = Quotation::where('sales_id', $sales->id)->whereIn('status', ['draft', 'revision', 'waiting_approval', 'approved', 'sent_to_customer'])->count();
            $projectCount = Project::whereHas('quotation', fn ($q) => $q->where('sales_id', $sales->id))->whereIn('status', ['planning', 'ongoing', 'finishing'])->count();

            return [
                'sales' => $sales,
                'active_leads' => $activeLeadCount,
                'waiting_pra_leads' => $waitingPraLeadCount,
                'total_workload' => $activeLeadCount + $waitingPraLeadCount + $quotationCount + $projectCount,
            ];
        })->sortBy('total_workload')->values();

        return view('admin.pra_leads.index', compact('praLeads', 'counts', 'salesList', 'salesWorkloads'));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $action = $request->input('action', 'save');

        if ($action === 'send' && empty($data['assigned_sales_id'])) {
            return back()->withErrors(['assigned_sales_id' => 'Pilih sales terlebih dahulu sebelum mengirim pra lead ke sales.'])->withInput();
        }

        $data['code'] = CodeGenerator::next(PraLead::class, 'PRA', 4);
        $data['created_by'] = Auth::id();
        if ($action === 'send') {
            $data['status'] = 'waiting_acceptance';
            $data['sent_at'] = now();
            $data['responded_at'] = null;
        } elseif ($action === 'save' && ! empty($data['assigned_sales_id'])) {
            $data['status'] = 'assigned';
        } else {
            $data['status'] = 'draft';
            $data['sent_at'] = null;
            $data['responded_at'] = null;
        }

        $praLead = PraLead::create($data);
        Logger::record('created', "Pra Lead {$praLead->instansi} dibuat", $praLead);

        return redirect()->route('admin.pra-leads.index')->with('success', 'Pra Lead berhasil disimpan.');
    }

    public function update(Request $request, PraLead $praLead)
    {
        $data = $this->validateData($request);
        $action = $request->input('action', 'save');

        if ($action === 'send' && empty($data['assigned_sales_id'])) {
            return back()->withErrors(['assigned_sales_id' => 'Pilih sales terlebih dahulu sebelum mengirim pra lead ke sales.'])->withInput();
        }

        if ($action === 'send') {
            $data['status'] = 'waiting_acceptance';
            $data['sent_at'] = now();
            $data['responded_at'] = null;
        } elseif ($action === 'draft') {
            $data['status'] = 'draft';
            $data['sent_at'] = null;
            $data['responded_at'] = null;
        } elseif (! empty($data['assigned_sales_id'])) {
            if (in_array($praLead->status, ['draft', 'assigned', 'rejected'], true)) {
                $data['status'] = 'assigned';
            }
            // Jika data lama sudah berstatus menunggu konfirmasi, cukup isi sales-nya
            // dan biarkan tetap muncul di menu Request Masuk sales tersebut.
            if ($praLead->status === 'waiting_acceptance') {
                $data['status'] = 'waiting_acceptance';
                $data['sent_at'] = $praLead->sent_at ?: now();
            }
        } elseif (in_array($praLead->status, ['draft', 'assigned', 'waiting_acceptance', 'rejected'], true)) {
            $data['status'] = 'draft';
            $data['sent_at'] = null;
            $data['responded_at'] = null;
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
            'source' => ['required', Rule::in(array_keys(PraLead::sources()))],
            'lab_type' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'initial_need' => ['nullable', 'string'],
            'admin_note' => ['nullable', 'string'],
            'est_value_min' => ['nullable', 'numeric'],
            'est_value_max' => ['nullable', 'numeric'],
            'priority' => ['required', 'in:low,medium,high'],
            'assigned_sales_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', 'sales')
                    ->where('is_active', true)
                    ->whereNull('deleted_at')),
            ],
            'action' => ['nullable', 'in:save,draft,send'],
        ]);
    }
}
