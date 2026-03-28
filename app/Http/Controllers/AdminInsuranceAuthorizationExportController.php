<?php

namespace App\Http\Controllers;

use App\Models\InsuranceAuthorization;
use App\Services\InsuranceAuthorizationService;
use Illuminate\Http\Response;

class AdminInsuranceAuthorizationExportController extends Controller
{
    public function __invoke(InsuranceAuthorization $authorization, InsuranceAuthorizationService $service): Response
    {
        abort_unless(auth()->user()?->can('planos.view') === true, 403);

        $payload = $service->exportPayload($authorization);

        return response(
            json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            200,
            [
                'Content-Type' => 'application/json; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="'.$authorization->reference.'.json"',
            ],
        );
    }
}
