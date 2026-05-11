<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query()->with(['user', 'beneficiary'])->latest();

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($q2) use ($q) {
                $q2->where('action', 'like', "%$q%")
                    ->orWhere('model_type', 'like', "%$q%")
                    ->orWhere('ip_address', 'like', "%$q%")
                    ->orWhereHas('user', fn($u) => $u->where('name', 'like', "%$q%"));
            });
        }

        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        if ($request->filled('model_type')) {
            $query->where('model_type', $request->model_type);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate($request->get('per_page', 25))->withQueryString();

        // Get distinct model types for the filter dropdown
        $modelTypes = AuditLog::select('model_type')->distinct()->pluck('model_type');

        return inertia('audit-logs.index', [
            'logs'       => $logs,
            'modelTypes' => $modelTypes,
            'filters'    => $request->only(['search', 'action', 'model_type', 'date_from', 'date_to']),
        ]);
    }

    public function show(string $id)
    {
        $log = AuditLog::with(['user', 'beneficiary'])->findOrFail($id);
        return response()->json($log);
    }
}

