<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\Reports\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(ReportService $reportService): Response
    {
        return Inertia::render('admin/reports/index', [
            'reports' => $reportService->catalog(),
        ]);
    }

    public function show(string $report, Request $request, ReportService $reportService): Response
    {
        $catalog = $reportService->catalog();
        if (! isset($catalog[$report])) {
            abort(404, "Report [{$report}] not found.");
        }

        $filters = $request->query();
        $generated = $reportService->generate($report, $filters);

        return Inertia::render('admin/reports/show', [
            'reportKey' => $report,
            'reportInfo' => $catalog[$report],
            'headers' => $generated['headers'],
            'rows' => $generated['rows'],
            'filters' => $filters,
        ]);
    }

    public function export(string $report, Request $request, ReportService $reportService): StreamedResponse
    {
        $catalog = $reportService->catalog();
        if (! isset($catalog[$report])) {
            abort(404, "Report [{$report}] not found.");
        }

        $filters = $request->all();
        $generated = $reportService->generate($report, $filters);
        $headers = $generated['headers'];
        $rows = $generated['rows'];

        // MANDATORY AUDIT: Every export writes an audit log recording who exported what dataset
        AuditLog::create([
            'user_id' => Auth::id(),
            'action' => 'report.exported',
            'auditable_type' => null,
            'auditable_id' => null,
            'before' => null,
            'after' => [
                'report' => $report,
                'filters' => $filters,
                'rows_count' => count($rows),
            ],
            'description' => "Exported {$catalog[$report]['title']} (".count($rows).' rows)',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent() ? substr($request->userAgent(), 0, 500) : null,
        ]);

        $filename = "sshs-report-{$report}-".now()->toDateString().'.csv';

        return response()->stream(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Output headers
            fputcsv($handle, $headers);

            // Output data rows
            foreach ($rows as $row) {
                fputcsv($handle, array_values($row));
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}
