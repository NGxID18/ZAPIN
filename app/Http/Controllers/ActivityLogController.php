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
            $escaped = addcslashes(trim($request->search), '%_');
            $query->where(function ($q) use ($escaped) {
                $q->where('action', 'like', "%{$escaped}%")
                  ->orWhere('description', 'like', "%{$escaped}%")
                  ->orWhere('user_role', 'like', "%{$escaped}%")
                  ->orWhere('ruangan_name', 'like', "%{$escaped}%");
            });
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $perPage = $request->per_page === 'all' ? 250 : min(max((int) $request->get('per_page', 50), 1), 250);
        $logs = $query->latest()->paginate($perPage)->withQueryString();
        $actionTypes = ActivityLog::select('action')->distinct()->pluck('action');

        return view('activity_logs.index', compact('logs', 'actionTypes'));
    }
}
