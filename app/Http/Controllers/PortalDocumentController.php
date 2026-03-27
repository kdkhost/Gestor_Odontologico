<?php

namespace App\Http\Controllers;

use App\Models\DocumentAcceptance;
use App\Models\DocumentTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PortalDocumentController extends Controller
{
    public function accept(Request $request, DocumentTemplate $template): RedirectResponse
    {
        $patient = $request->user()->patient;

        abort_unless($patient, 403);

        $replacements = [
            '{{paciente_nome}}' => $patient->name,
            '{{data_atendimento}}' => now()->format('d/m/Y H:i'),
            '{{profissional_nome}}' => $patient->latestAppointment?->professional?->user?->name ?? 'Equipe da clínica',
        ];

        $content = str_replace(array_keys($replacements), array_values($replacements), $template->body);

        DocumentAcceptance::query()->firstOrCreate(
            [
                'document_template_id' => $template->id,
                'patient_id' => $patient->id,
            ],
            [
                'user_id' => $request->user()->id,
                'accepted_at' => now(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'content_hash' => hash('sha256', $content),
                'rendered_content' => $content,
                'context' => ['accepted_from' => 'portal'],
            ],
        );

        return back()->with('status', 'Documento aceito com sucesso.');
    }
}
