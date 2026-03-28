<?php

return [
    'system_version' => env('SYSTEM_VERSION', '1.15.6'),

    'developer' => [
        'name' => env('DEVELOPER_NAME', ''),
        'email' => env('DEVELOPER_EMAIL', ''),
        'whatsapp' => env('DEVELOPER_WHATSAPP', ''),
        'site' => env('DEVELOPER_SITE', ''),
        'footer_note' => env('DEVELOPER_FOOTER_NOTE', ''),
    ],

    'roles' => [
        'superadmin',
        'admin-unidade',
        'recepcao',
        'dentista',
        'financeiro',
        'estoque',
        'paciente',
    ],

    'modules' => [
        'dashboard',
        'usuarios',
        'unidades',
        'pacientes',
        'profissionais',
        'agenda',
        'procedimentos',
        'planos',
        'financeiro',
        'estoque',
        'documentos',
        'notificacoes',
        'manutencao',
        'configuracoes',
    ],

    'appointment_statuses' => [
        'requested',
        'confirmed',
        'checked_in',
        'in_progress',
        'completed',
        'no_show',
        'cancelled',
    ],

    'financial_statuses' => [
        'open',
        'partial',
        'paid',
        'cancelled',
        'overdue',
    ],

    'maintenance_exceptions' => [
        '127.0.0.1',
        '::1',
    ],

    'onboarding' => [
        'auto_start' => env('ONBOARDING_AUTO_START', true),
    ],

    'whatsapp' => [
        'dispatch_enabled' => env('WHATSAPP_DISPATCH_ENABLED', false),
        'respect_business_hours' => env('WHATSAPP_RESPECT_BUSINESS_HOURS', true),
        'business_hours_start' => env('WHATSAPP_BUSINESS_HOURS_START', '08:00'),
        'business_hours_end' => env('WHATSAPP_BUSINESS_HOURS_END', '18:00'),
        'min_interval_seconds' => env('WHATSAPP_MIN_INTERVAL_SECONDS', 120),
        'max_per_minute' => env('WHATSAPP_MAX_PER_MINUTE', 10),
        'max_per_hour_per_recipient' => env('WHATSAPP_MAX_PER_HOUR_PER_RECIPIENT', 4),
        'require_opt_in' => env('WHATSAPP_REQUIRE_OPT_IN', true),
        'signature' => env('WHATSAPP_SIGNATURE', 'Equipe {{app_name}}'),
        'default_delay_ms' => env('WHATSAPP_DEFAULT_DELAY_MS', 1500),
        'link_preview' => env('WHATSAPP_LINK_PREVIEW', false),
    ],

    'automation' => [
        'appointment_reminder_enabled' => env('AUTOMATION_APPOINTMENT_REMINDER_ENABLED', true),
        'appointment_reminder_hours_before' => env('AUTOMATION_APPOINTMENT_REMINDER_HOURS_BEFORE', 24),
        'financial_due_enabled' => env('AUTOMATION_FINANCIAL_DUE_ENABLED', true),
        'financial_due_days_before' => env('AUTOMATION_FINANCIAL_DUE_DAYS_BEFORE', 2),
        'patient_reactivation_enabled' => env('AUTOMATION_PATIENT_REACTIVATION_ENABLED', true),
        'patient_reactivation_after_days' => env('AUTOMATION_PATIENT_REACTIVATION_AFTER_DAYS', 90),
        'reactivation_cooldown_days' => env('AUTOMATION_REACTIVATION_COOLDOWN_DAYS', 30),
    ],
];
