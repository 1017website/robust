<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\DesignRequest;
use App\Models\PraLead;
use App\Models\Project;
use App\Models\PurchaseOrderRequest;
use App\Models\Quotation;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        Schema::defaultStringLength(191);

        View::composer('layouts.app', function ($view) {
            $user = Auth::user();
            $notifications = [];
            $sidebarNotificationCounts = [];
            // Layout harus tetap bisa dirender sebelum migration terbaru dijalankan,
            // terutama karena command migrate tersedia dari halaman System Settings.
            $hasExpandedOperationalWorkflow = Schema::hasTable('project_workflows')
                && Schema::hasColumn('project_workflows', 'delivery_status');

            if ($user) {
                if ($user->isSales()) {
                    $waitingPraLeads = PraLead::where('assigned_sales_id', $user->id)
                        ->where('status', 'waiting_acceptance')
                        ->count();
                    $this->addNotification($notifications, $sidebarNotificationCounts, 'sales.request-masuk.*', $waitingPraLeads, 'Request masuk perlu respon', 'Pra lead menunggu konfirmasi sales.', route('sales.request-masuk.index'), 'bi-inbox', 'text-primary');

                    $revisionQuotations = Quotation::where('sales_id', $user->id)
                        ->where('status', 'revision')
                        ->count();
                    $this->addNotification($notifications, $sidebarNotificationCounts, 'sales.quotations.*', $revisionQuotations, 'Penawaran perlu revisi', 'Cek catatan SPV dan update penawaran.', route('sales.quotations.index', ['status' => 'revision']), 'bi-file-earmark-text', 'text-warning');

                    $completedDesignRequests = DesignRequest::where('sales_id', $user->id)
                        ->where('status', 'completed')
                        ->whereNotNull('submitted_at')
                        ->whereDoesntHave('quotations', fn ($query) => $query
                            ->whereColumn('quotations.updated_at', '>=', 'design_requests.submitted_at'))
                        ->count();
                    $this->addNotification($notifications, $sidebarNotificationCounts, 'sales.quotations.*', $completedDesignRequests, 'Design Request selesai diproses', 'Produksi sudah melengkapi spesifikasi dan HPP. Buat atau perbarui penawaran.', route('sales.quotations.create'), 'bi-file-earmark-check', 'text-success');

                    $approvedQuotations = Quotation::where('sales_id', $user->id)
                        ->where('status', 'approved')
                        ->count();
                    $this->addNotification($notifications, $sidebarNotificationCounts, 'sales.quotations.*', $approvedQuotations, 'Penawaran disetujui SPV', 'Excel dan PDF sudah dapat diunduh dan penawaran siap dikirim ke customer.', route('sales.quotations.index', ['status' => 'approved']), 'bi-patch-check', 'text-success');
                }

                if ($user->isSalesSpv() || $user->isAdministrator()) {
                    $waitingApprovals = Quotation::where('status', 'waiting_approval')->count();
                    $this->addNotification($notifications, $sidebarNotificationCounts, 'spv.quotation-approvals.*', $waitingApprovals, 'Approval penawaran', 'Penawaran menunggu review SPV.', route('spv.quotation-approvals.index', ['status' => 'waiting_approval']), 'bi-check2-square', 'text-primary');
                }

                if ($user->canManageBackOffice()) {
                    $submittedPo = PurchaseOrderRequest::where('status', 'submitted')->count();
                    $this->addNotification($notifications, $sidebarNotificationCounts, 'admin.purchase-order-requests.*', $submittedPo, 'Request PO baru', 'Data PO perlu diproses ke Accurate.', route('admin.purchase-order-requests.index', ['status' => 'submitted']), 'bi-receipt', 'text-success');

                    $readyInvoices = $hasExpandedOperationalWorkflow
                        ? Project::whereHas('workflow', fn ($workflow) => $workflow->where('delivery_status', 'completed'))
                            ->whereHas('quotation.purchaseOrderRequest', fn ($po) => $po->whereDoesntHave('invoice'))
                            ->count()
                        : 0;
                    $this->addNotification($notifications, $sidebarNotificationCounts, 'admin.invoices.*', $readyInvoices, 'Project siap ditagihkan', 'Delivery dan penerimaan customer selesai. Invoice dapat diterbitkan.', route('admin.invoices.index'), 'bi-file-earmark-richtext', 'text-success');
                }

                if ($user->isDrafter()) {
                    $assignedDesigns = DesignRequest::where('production_pic_id', $user->id)
                        ->whereIn('status', ['assigned', 'drafting', 'costing', 'review'])
                        ->count();
                    $this->addNotification($notifications, $sidebarNotificationCounts, 'drafter.design-requests.*', $assignedDesigns, 'Task desain aktif', 'Design request masih perlu dikerjakan.', route('drafter.design-requests.index'), 'bi-pencil-square', 'text-primary');

                    $fabricationProjects = Project::query()
                        ->where(function ($query) use ($user) {
                            $query->where('project_manager_id', $user->id)
                                ->orWhereJsonContains('internal_team', (string) $user->id)
                                ->orWhereJsonContains('internal_team', $user->id);
                        })
                        ->whereHas('quotation.purchaseOrderRequest', fn ($po) => $po->where('status', 'po_created'))
                        ->whereDoesntHave('documents', fn ($documents) => $documents
                            ->where('category', 'fabrication_drawing')
                            ->where('is_current', true))
                        ->count();
                    $this->addNotification($notifications, $sidebarNotificationCounts, 'drafter.projects.*', $fabricationProjects, 'Gambar fabrikasi diperlukan', 'PO Accurate sudah terbit. Lengkapi gambar fabrikasi untuk Produksi.', route('drafter.projects.index'), 'bi-rulers', 'text-warning');
                }

                if ($user->isProduction()) {
                    $readyProduction = Project::query()
                        ->whereHas('documents', fn ($documents) => $documents->where('category', 'fabrication_drawing')->where('is_current', true))
                        ->where(fn ($projects) => $projects->whereDoesntHave('workflow')
                            ->orWhereHas('workflow', fn ($workflow) => $workflow->where('production_status', 'stock')))
                        ->count();
                    $this->addNotification($notifications, $sidebarNotificationCounts, 'drafter.projects.*', $readyProduction, 'Gambar fabrikasi siap', 'Drafter sudah mengunggah gambar. Produksi dapat mengunduh dan mulai bekerja.', route('drafter.projects.index'), 'bi-building-gear', 'text-primary');
                }

                if ($user->isQc()) {
                    $pendingQc = Project::whereHas('workflow', fn ($workflow) => $workflow
                        ->where('production_status', 'production_finished')
                        ->where('qc_completed', false))
                        ->count();
                    $this->addNotification($notifications, $sidebarNotificationCounts, 'drafter.projects.*', $pendingQc, 'Project menunggu QC', 'Produksi selesai. Periksa checklist spesifikasi penawaran.', route('drafter.projects.index'), 'bi-patch-check', 'text-warning');
                }

                if ($user->isDelivery()) {
                    $pendingDelivery = $hasExpandedOperationalWorkflow
                        ? Project::whereHas('workflow', fn ($workflow) => $workflow
                            ->where('qc_completed', true)
                            ->where('delivery_status', '!=', 'completed'))
                            ->count()
                        : 0;
                    $this->addNotification($notifications, $sidebarNotificationCounts, 'drafter.projects.*', $pendingDelivery, 'Project siap dikirim', 'QC selesai. Atur jadwal, unggah POD, dan konfirmasi penerimaan customer.', route('drafter.projects.index'), 'bi-truck', 'text-primary');
                }

                if (in_array($user->role, ['administrator', 'sales_spv', 'sales', 'administration'], true)) {
                    $overdueActivities = Activity::query()
                        ->when($user->isSales(), fn ($query) => $query->where('sales_id', $user->id))
                        ->whereDate('activity_date', '<', today())
                        ->whereNotIn('status', ['completed', 'cancelled'])
                        ->count();
                    $this->addNotification($notifications, $sidebarNotificationCounts, 'activities.*', $overdueActivities, 'Aktivitas terlambat', 'Follow up belum diselesaikan.', route('activities.index', ['status' => 'scheduled']), 'bi-exclamation-triangle', 'text-danger');
                }
            }

            $view->with('topbarNotifications', array_slice($notifications, 0, 5));
            $view->with('topbarNotificationCount', array_sum(array_column($notifications, 'count')));
            $view->with('sidebarNotificationCounts', $sidebarNotificationCounts);
        });
    }

    protected function addNotification(array &$items, array &$sidebarCounts, string $menuPattern, int $count, string $title, string $detail, string $href, string $icon, string $tone): void
    {
        if ($count < 1) {
            return;
        }

        $items[] = compact('count', 'title', 'detail', 'href', 'icon', 'tone');
        $sidebarCounts[$menuPattern] = ($sidebarCounts[$menuPattern] ?? 0) + $count;
    }
}
