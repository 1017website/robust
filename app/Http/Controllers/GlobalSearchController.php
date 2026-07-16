<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\DesignRequest;
use App\Models\Document;
use App\Models\Lead;
use App\Models\Project;
use App\Models\Quotation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GlobalSearchController extends Controller
{
    public function index(Request $request)
    {
        $query = trim((string) $request->get('q', ''));
        $results = collect();

        if ($query !== '') {
            $user = Auth::user();
            $like = "%{$query}%";

            if ($user->isAdministrator() || $user->isSalesAdmin() || $user->isSales() || $user->isSalesSpv()) {
                $customers = Customer::with('primaryPic', 'sales')
                    ->when($user->isSales(), fn (Builder $q) => $q->where('sales_id', $user->id))
                    ->where(function (Builder $q) use ($like) {
                        $q->where('name', 'like', $like)
                            ->orWhere('email', 'like', $like)
                            ->orWhere('phone', 'like', $like)
                            ->orWhere('city', 'like', $like)
                            ->orWhereHas('primaryPic', fn (Builder $pic) => $pic->where('name', 'like', $like));
                    })
                    ->limit(6)
                    ->get();

                foreach ($customers as $customer) {
                    $results->push([
                        'group' => 'Customer',
                        'title' => $customer->name,
                        'subtitle' => trim(($customer->primaryPic?->name ?? 'PIC belum diisi').' · '.($customer->sales?->name ?? 'Sales belum diisi'), ' ·'),
                        'href' => route('sales.customers.show', $customer),
                        'icon' => 'bi-person-vcard',
                    ]);
                }
            }

            if ($user->isAdministrator() || $user->isSales()) {
                $leads = Lead::with('sales')
                    ->when($user->isSales(), fn (Builder $q) => $q->where('sales_id', $user->id))
                    ->where(function (Builder $q) use ($like) {
                        $q->where('instansi', 'like', $like)
                            ->orWhere('pic_name', 'like', $like)
                            ->orWhere('lab_name', 'like', $like)
                            ->orWhere('need_description', 'like', $like);
                    })
                    ->limit(5)
                    ->get();

                foreach ($leads as $lead) {
                    $results->push([
                        'group' => 'Lead',
                        'title' => $lead->instansi,
                        'subtitle' => trim(($lead->pic_name ?? 'PIC belum diisi').' · '.($lead->stage ?? 'lead'), ' ·'),
                        'href' => route('sales.leads.show', $lead),
                        'icon' => 'bi-person-lines-fill',
                    ]);
                }
            }

            if (! $user->isDrafter()) {
                $activities = Activity::with('customer', 'lead', 'sales')
                    ->when($user->isSales(), fn (Builder $q) => $q->where('sales_id', $user->id))
                    ->where(function (Builder $q) use ($like) {
                        $q->where('title', 'like', $like)
                            ->orWhere('description', 'like', $like)
                            ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', $like))
                            ->orWhereHas('lead', fn (Builder $lead) => $lead->where('instansi', 'like', $like));
                    })
                    ->latest('activity_date')
                    ->limit(6)
                    ->get();

                foreach ($activities as $activity) {
                    $results->push([
                        'group' => 'Activity',
                        'title' => $activity->title,
                        'subtitle' => trim(($activity->customer?->name ?? $activity->lead?->instansi ?? 'Customer belum diisi').' · '.($activity->activity_date?->translatedFormat('d M Y') ?? '-'), ' ·'),
                        'href' => route('activities.index', ['activity' => $activity->id]),
                        'icon' => 'bi-check2-square',
                    ]);
                }
            }

            if ($user->isAdministrator() || $user->isSales() || $user->isSalesSpv()) {
                $quotations = Quotation::with('sales')
                    ->when($user->isSales(), fn (Builder $q) => $q->where('sales_id', $user->id))
                    ->where(function (Builder $q) use ($like) {
                        $q->where('code', 'like', $like)
                            ->orWhere('customer_name', 'like', $like)
                            ->orWhere('project_name', 'like', $like)
                            ->orWhere('pic_name', 'like', $like);
                    })
                    ->latest()
                    ->limit(5)
                    ->get();

                foreach ($quotations as $quotation) {
                    $results->push([
                        'group' => 'Penawaran',
                        'title' => $quotation->code ?? 'Penawaran',
                        'subtitle' => "{$quotation->customer_name} · {$quotation->project_name}",
                        'href' => $user->isSalesSpv() && ! $user->isAdministrator()
                            ? route('spv.quotation-approvals.show', $quotation)
                            : route('sales.quotations.show', $quotation),
                        'icon' => 'bi-file-earmark-text',
                    ]);
                }
            }

            if ($user->isAdministrator() || $user->isSales() || $user->isDrafter()) {
                $designRequests = DesignRequest::with('sales', 'productionPic')
                    ->when($user->isSales(), fn (Builder $q) => $q->where('sales_id', $user->id))
                    ->when($user->isDrafter(), fn (Builder $q) => $q->where('production_pic_id', $user->id))
                    ->where(function (Builder $q) use ($like) {
                        $q->where('code', 'like', $like)
                            ->orWhere('customer_name', 'like', $like)
                            ->orWhere('project_name', 'like', $like)
                            ->orWhere('pic_name', 'like', $like);
                    })
                    ->latest()
                    ->limit(5)
                    ->get();

                foreach ($designRequests as $designRequest) {
                    $results->push([
                        'group' => 'Design Request',
                        'title' => $designRequest->code ?? 'Design Request',
                        'subtitle' => "{$designRequest->customer_name} · {$designRequest->project_name}",
                        'href' => $user->isDrafter()
                            ? route('drafter.design-requests.show', $designRequest)
                            : route('sales.design-requests.show', $designRequest),
                        'icon' => 'bi-pencil-square',
                    ]);
                }
            }

            if ($user->isAdministrator() || $user->isSales()) {
                $projects = Project::with('customer')
                    ->when($user->isSales(), fn (Builder $q) => $q->whereHas('quotation', fn (Builder $quote) => $quote->where('sales_id', $user->id)))
                    ->where(function (Builder $q) use ($like) {
                        $q->where('code', 'like', $like)
                            ->orWhere('name', 'like', $like)
                            ->orWhere('location', 'like', $like)
                            ->orWhereHas('customer', fn (Builder $customer) => $customer->where('name', 'like', $like));
                    })
                    ->latest()
                    ->limit(5)
                    ->get();

                foreach ($projects as $project) {
                    $results->push([
                        'group' => 'Project',
                        'title' => $project->name,
                        'subtitle' => trim(($project->customer?->name ?? 'Customer belum diisi').' · '.($project->code ?? '-'), ' ·'),
                        'href' => route('sales.projects.show', $project),
                        'icon' => 'bi-folder',
                    ]);
                }
            }

            $documents = Document::query()
                ->visibleTo($user)
                ->where(function (Builder $q) use ($like) {
                    $q->where('name', 'like', $like)
                        ->orWhere('category', 'like', $like)
                        ->orWhere('description', 'like', $like);
                })
                ->latest()
                ->limit(5)
                ->get();

            foreach ($documents as $document) {
                $results->push([
                    'group' => 'Document',
                    'title' => $document->name,
                    'subtitle' => $document->category ?: 'Dokumen',
                    'href' => route('documents.index', ['q' => $document->name]),
                    'icon' => 'bi-folder2-open',
                ]);
            }
        }

        return view('global-search.index', [
            'query' => $query,
            'results' => $results->groupBy('group'),
            'total' => $results->count(),
        ]);
    }
}
