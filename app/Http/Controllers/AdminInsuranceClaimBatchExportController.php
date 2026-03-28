<?php

namespace App\Http\Controllers;

use App\Models\InsuranceClaimBatch;
use App\Services\InsuranceClaimBillingService;
use Illuminate\Http\Response;

class AdminInsuranceClaimBatchExportController extends Controller
{
    public function __invoke(InsuranceClaimBatch $batch, InsuranceClaimBillingService $service): Response
    {
        abort_unless(auth()->user()?->can('financeiro.export') === true, 403);

        $payload = $service->exportPayload($batch);

        return response(
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            200,
            [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$batch->reference.'.json"',
            ],
        );
    }
}
