<?php

namespace App\Http\Controllers\Drafter;

use App\Http\Controllers\Controller;
use App\Models\DesignRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = DesignRequest::with('sales', 'documents')
            ->where('production_pic_id', $user->id)
            ->latest();

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($keyword = $request->get('q')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('code', 'like', "%{$keyword}%")
                    ->orWhere('customer_name', 'like', "%{$keyword}%")
                    ->orWhere('project_name', 'like', "%{$keyword}%");
            });
        }

        $tasks = $query->paginate(10)->withQueryString();

        $base = DesignRequest::where('production_pic_id', $user->id);
        $stats = [
            'todo' => (clone $base)->whereIn('status', ['assigned', 'drafting', 'costing'])->count(),
            'review' => (clone $base)->where('status', 'review')->count(),
            'completed' => (clone $base)->where('status', 'completed')->count(),
            'overdue' => (clone $base)->whereNotIn('status', ['completed', 'rejected'])->whereDate('deadline', '<', today())->count(),
        ];

        return view('drafter.tasks.index', compact('tasks', 'stats'));
    }
}
