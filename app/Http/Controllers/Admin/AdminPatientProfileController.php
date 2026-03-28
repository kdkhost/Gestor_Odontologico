<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Services\PatientInsightService;
use App\Support\AdminModuleRegistry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AdminPatientProfileController extends Controller
{
    public function __invoke(Request $request, Patient $patient, PatientInsightService $insights): View
    {
        $module = AdminModuleRegistry::find('pacientes', $request->user());

        abort_if(blank($module), 403);

        $user = $request->user();

        if (! (method_exists($user, 'hasRole') && $user->hasRole('superadmin')) && $user->unit_id && $patient->unit_id !== $user->unit_id) {
            abort(403);
        }

        return view('admin.patients.show', [
            'module' => $module,
            'patient' => $patient,
            'snapshot' => $insights->snapshot($patient),
            'coreProfileUrl' => "/admin/core/patients/{$patient->getKey()}/perfil-360",
        ]);
    }
}
