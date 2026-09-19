<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\Paginated;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $action = $request->query('action');
        $userId = $request->query('user_id');
        $auditableType = $request->query('auditable_type');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        $query = AuditLog::query()
            ->with(['user:id,name,email'])
            ->when($action, fn ($q) => $q->where('action', 'like', "%{$action}%"))
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($auditableType, fn ($q) => $q->where('auditable_type', 'like', "%{$auditableType}%"))
            ->when($dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->whereDate('created_at', '<=', $dateTo))
            ->latest('id');

        $logs = $query->paginate(25)->withQueryString();

        // Common actions for filter dropdown
        $actions = AuditLog::query()
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->take(50);

        $users = User::query()
            ->whereHas('roles')
            ->orderBy('name')
            ->select(['id', 'name', 'email'])
            ->get();

        return Inertia::render('admin/audit/index', [
            'logs' => Paginated::from($logs, fn (AuditLog $log): array => [
                'id' => $log->id,
                'action' => $log->action,
                'auditable_type' => $log->auditable_type ? class_basename($log->auditable_type) : null,
                'auditable_id' => $log->auditable_id,
                'before' => $log->before,
                'after' => $log->after,
                'description' => $log->description,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at?->toIso8601String(),
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                ] : null,
            ]),
            'filters' => [
                'action' => $action,
                'user_id' => $userId,
                'auditable_type' => $auditableType,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'availableActions' => $actions,
            'staffUsers' => $users,
        ]);
    }
}
