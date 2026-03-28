<?php

namespace App\Http\Controllers;

use App\Models\PrivacyRequest;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminPrivacyExportController extends Controller
{
    public function __invoke(PrivacyRequest $request): BinaryFileResponse
    {
        abort_unless(auth()->user()?->can('configuracoes.manage') === true, 403);
        abort_unless($request->request_type === 'export' && $request->status === 'completed' && filled($request->export_path), 404);
        abort_unless(Storage::disk('local')->exists($request->export_path), 404);

        return response()->download(
            Storage::disk('local')->path($request->export_path),
            basename($request->export_path),
            ['Content-Type' => 'application/json; charset=UTF-8'],
        );
    }
}
