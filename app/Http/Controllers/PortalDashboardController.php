<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use Illuminate\View\View;

class PortalDashboardController extends Controller
{
    public function index(): View
    {
        $patient = auth()->user()->patient;

        $pendingDocuments = DocumentTemplate::query()
            ->where('is_active', true)
            ->where(function ($query) use ($patient) {
                $query->whereNull('unit_id');

                if ($patient?->unit_id) {
                    $query->orWhere('unit_id', $patient->unit_id);
                }
            })
            ->whereDoesntHave('acceptances', fn ($query) => $query->where('patient_id', $patient?->id))
            ->get();

        $openInstallments = $patient?->accountsReceivable()
            ->with(['installments'])
            ->get()
            ->pluck('installments')
            ->flatten()
            ->whereIn('status', ['open', 'overdue']);

        $upcomingAppointments = $patient?->appointments()
            ->whereIn('status', ['requested', 'confirmed'])
            ->where('scheduled_start', '>=', now())
            ->orderBy('scheduled_start')
            ->limit(5)
            ->get();

        return view('portal.dashboard', [
            'patient' => $patient,
            'pendingDocuments' => $pendingDocuments,
            'openInstallments' => $openInstallments,
            'upcomingAppointments' => $upcomingAppointments,
        ]);
    }
}
