<?php

/**
 * Fase 9a — Configuración de mensajería (WhatsApp / SMS).
 *
 * Provider por defecto: 'log' → escribe a laravel.log sin enviar nada real.
 * Cuando estés listo para enviar de verdad, pon en .env:
 *
 *   MESSAGING_PROVIDER=whatsapp_cloud
 *   WHATSAPP_TOKEN=EAAxxxxxxx...
 *   WHATSAPP_PHONE_ID=1234567890
 *
 * (Ver "WhatsApp Cloud API" docs de Meta para obtener token y phone id)
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Provider activo
    |--------------------------------------------------------------------------
    | 'log' (default), 'whatsapp_cloud', 'twilio'
    */
    'provider' => env('MESSAGING_PROVIDER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Default channel (cuando el usuario no lo especifica)
    |--------------------------------------------------------------------------
    | 'whatsapp', 'sms', 'log'
    */
    'default_channel' => env('MESSAGING_DEFAULT_CHANNEL', 'whatsapp'),

    /*
    |--------------------------------------------------------------------------
    | Configuración de WhatsApp Cloud API (Meta)
    |--------------------------------------------------------------------------
    | Documentación: https://developers.facebook.com/docs/whatsapp/cloud-api
    | - phone_number_id: el ID del número (no el número en sí). Está en el dashboard.
    | - access_token: token permanente o de larga duración.
    | - api_version: por defecto v18.0.
    */
    'whatsapp_cloud' => [
        'phone_number_id' => env('WHATSAPP_PHONE_ID'),
        'access_token'    => env('WHATSAPP_TOKEN'),
        'api_version'     => env('WHATSAPP_API_VERSION', 'v18.0'),
        // Si quieres usar SOLO plantillas pre-aprobadas (recomendado para envío
        // fuera de la ventana de 24h), define aquí los identificadores.
        'templates' => [
            'reminder_appointment' => env('WHATSAPP_TPL_REMINDER', null),
            'evaluation_ready'     => env('WHATSAPP_TPL_EVAL', null),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configuración de Twilio (alternativo)
    |--------------------------------------------------------------------------
    */
    'twilio' => [
        'sid'          => env('TWILIO_SID'),
        'token'        => env('TWILIO_TOKEN'),
        'from_whatsapp'=> env('TWILIO_FROM_WHATSAPP'),   // ej. whatsapp:+14155238886
        'from_sms'     => env('TWILIO_FROM_SMS'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Datos para personalizar plantillas
    |--------------------------------------------------------------------------
    */
    'clinic_name'  => env('CLINIC_NAME', 'Healing Hands'),
    'clinic_phone' => env('CLINIC_PHONE', ''),
    'clinic_url'   => env('APP_URL', 'https://healinghands.eztranets.com'),

    /*
    |--------------------------------------------------------------------------
    | Catálogo de plantillas reutilizables
    |--------------------------------------------------------------------------
    | Plantillas TEXTO (para WhatsApp dentro de ventana de 24h o SMS).
    | Variables disponibles: {paciente}, {clinic_name}, {clinic_phone}, {fecha}, {hora}
    */
    'templates' => [
        'reminder' => [
            'label' => 'Recordatorio de cita',
            'icon'  => 'fa-calendar-check',
            'body'  =>
                "¡Hola {paciente}! 👋\n\nTe recordamos tu cita en *{clinic_name}* el {fecha} a las {hora}.\n\nSi necesitas reprogramar, responde a este mensaje o llámanos al {clinic_phone}.\n\n¡Te esperamos!",
        ],
        'exercises' => [
            'label' => 'Ejercicios en casa',
            'icon'  => 'fa-dumbbell',
            'body'  =>
                "¡Hola {paciente}! 💪\n\nRecuerda realizar tus ejercicios en casa según el plan que conversamos.\n\nSi tienes alguna duda, no dudes en contactarnos.\n\n*{clinic_name}*",
        ],
        'evaluation_ready' => [
            'label' => 'Resultado de evaluación',
            'icon'  => 'fa-clipboard-check',
            'body'  =>
                "¡Hola {paciente}! 📋\n\nTu evaluación clínica está lista. Te enviaremos el detalle por este medio o puedes pasar por la clínica.\n\nSaludos,\n*{clinic_name}*",
        ],
        'thank_you' => [
            'label' => 'Agradecimiento post-sesión',
            'icon'  => 'fa-heart',
            'body'  =>
                "¡Gracias por tu visita, {paciente}! 🌿\n\nFue un gusto atenderte hoy. Recuerda seguir las indicaciones que conversamos para potenciar tu recuperación.\n\n*{clinic_name}*",
        ],
        'follow_up' => [
            'label' => 'Seguimiento de evolución',
            'icon'  => 'fa-heart-pulse',
            'body'  =>
                "¡Hola {paciente}! 🌿\n\n¿Cómo te has sentido desde nuestra última sesión? Nos gustaría saber cómo va tu evolución.\n\nResponde a este mensaje cuando puedas.\n\n*{clinic_name}*",
        ],
        'free' => [
            'label' => 'Mensaje libre',
            'icon'  => 'fa-edit',
            'body'  => '',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Modo dry-run
    |--------------------------------------------------------------------------
    | Si está activo, los providers NO envían pero registran como si lo hubieran
    | hecho. Útil para QA. Independiente del provider 'log' (que ya es dry-run
    | por naturaleza).
    */
    'dry_run' => env('MESSAGING_DRY_RUN', false),
];
