<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\DesignRequest;
use App\Models\PraLead;
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

            if ($user) {
                if ($user->isSales()) {
                    $waitingPraLeads = PraLead::where('assigned_sales_id', $user->id)
                        ->where('status', 'waiting_acceptance')
                        ->count();
                    $this->addNotification($notifications, $waitingPraLeads, 'Request masuk perlu respon', 'Pra lead menunggu konfirmasi sales.', route('sales.request-masuk.index'), 'bi-inbox', 'text-primary');

                    $revisionQuotations = Quotation::where('sales_id', $user->id)
                        ->where('status', 'revision')
                        ->count();
                    $this->addNotification($notifications, $revisionQuotations, 'Penawaran perlu revisi', 'Cek catatan SPV dan update penawaran.', route('sales.quotations.index', ['status' => 'revision']), 'bi-file-earmark-text', 'text-warning');
                }

                if ($user->isSalesSpv() || $user->isAdministrator()) {
                    $waitingApprovals = Quotation::where('status', 'waiting_approval')->count();
                    $this->addNotification($notifications, $waitingApprovals, 'Approval penawaran', 'Penawaran menunggu review SPV.', route('spv.quotation-approvals.index', ['status' => 'waiting_approval']), 'bi-check2-square', 'text-primary');
                }

                if ($user->isAdminLevel() || $user->isAdministrator()) {
                    $submittedPo = PurchaseOrderRequest::where('status', 'submitted')->count();
                    $this->addNotification($notifications, $submittedPo, 'Request PO baru', 'Data PO perlu diproses ke Accurate.', route('admin.purchase-order-requests.index', ['status' => 'submitted']), 'bi-receipt', 'text-success');
                }

                if ($user->isDrafter()) {
                    $assignedDesigns = DesignRequest::where('production_pic_id', $user->id)
                        ->whereIn('status', ['assigned', 'drafting', 'costing', 'review'])
                        ->count();
                    $this->addNotification($notifications, $assignedDesigns, 'Task desain aktif', 'Design request masih perlu dikerjakan.', route('drafter.design-requests.index'), 'bi-pencil-square', 'text-primary');
                }

                if (! $user->isDrafter()) {
                    $overdueActivities = Activity::query()
                        ->when($user->isSales(), fn ($query) => $query->where('sales_id', $user->id))
                        ->whereDate('activity_date', '<', today())
                        ->whereNotIn('status', ['completed', 'cancelled'])
                        ->count();
                    $this->addNotification($notifications, $overdueActivities, 'Aktivitas terlambat', 'Follow up belum diselesaikan.', route('activities.index', ['status' => 'scheduled']), 'bi-exclamation-triangle', 'text-danger');
                }
            }

            $view->with('topbarNotifications', array_slice($notifications, 0, 5));
            $view->with('topbarNotificationCount', array_sum(array_column($notifications, 'count')));
        });
    }

    protected function addNotification(array &$items, int $count, string $title, string $detail, string $href, string $icon, string $tone): void
    {
        if ($count < 1) {
            return;
        }

        $items[] = compact('count', 'title', 'detail', 'href', 'icon', 'tone');
    }
}
