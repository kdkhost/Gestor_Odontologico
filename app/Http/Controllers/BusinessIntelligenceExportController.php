<?php

namespace App\Http\Controllers;

use App\Services\BusinessIntelligenceService;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BusinessIntelligenceExportController extends Controller
{
    public function __invoke(Request $request, string $section, BusinessIntelligenceService $bi): StreamedResponse
    {
        abort_unless($request->user()?->can('financeiro.export') === true, 403);

        try {
            $payload = $bi->exportPayload(
                section: $section,
                unitId: $request->integer('unit_id') ?: null,
                fromDate: $request->query('from'),
                toDate: $request->query('to'),
            );
        } catch (InvalidArgumentException) {
            abort(404);
        }

        return response()->streamDownload(function () use ($payload): void {
            $handle = fopen('php://output', 'wb');

            fputcsv($handle, $payload['headers'], ';');

            foreach ($payload['rows'] as $row) {
                fputcsv($handle, $row, ';');
            }

            fclose($handle);
        }, $payload['filename'], [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
