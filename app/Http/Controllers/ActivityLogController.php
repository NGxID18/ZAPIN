<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = ActivityLog::query();

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('action', 'ilike', "%{$s}%")
                  ->orWhere('description', 'ilike', "%{$s}%")
                  ->orWhere('user_role', 'ilike', "%{$s}%")
                  ->orWhere('ruangan_name', 'ilike', "%{$s}%");
            });
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $isAll = $request->per_page === 'all';
        $perPage = $isAll ? max(1, (clone $query)->count()) : min(max((int) $request->get('per_page', 50), 1), 500);
        $logs = $query->latest()->paginate($perPage)->withQueryString();
        $actionTypes = ActivityLog::select('action')->distinct()->pluck('action');

        return view('activity_logs.index', compact('logs', 'actionTypes'));
    }
}
