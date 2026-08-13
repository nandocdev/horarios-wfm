<?php

declare(strict_types=1);

return [
    'cisco' => [
        'base_url' => env('UCCX_URL_BASE'),
        'username' => env('UCCX_USERNAME'),
        'password' => env('UCCX_PASSWORD'),
        'timeout' => (int) env('UCCX_TIMEOUT', 15),
        'verify_ssl' => env('UCCX_VERIFY_SSL', false),
    ],

    'cisco_webhook_ips' => [
        '192.168.1.100',
        '10.0.0.50',
        '203.0.113.42',
    ],

    'citizen_id_pattern' => '/^\d{8,12}$/',

    /*
    |--------------------------------------------------------------------------
    | Analytics & BI Thresholds
    |--------------------------------------------------------------------------
    */
    'sla_threshold_seconds' => (int) env('CONTACT_CENTER_SLA_THRESHOLD', 20),

    /*
    |--------------------------------------------------------------------------
    | Finesse - Cisco Finesse API
    |--------------------------------------------------------------------------
    | Configuración para consumir la API REST de Finesse para obtener
    | información de colas (CSQ). Usa las mismas credenciales que CUIC.
    */
    'finesse' => [
        'base_url' => env('FINESSE_BASE_URL'),
        'username' => env('CUIC_USERNAME'),
        'password' => env('CUIC_PASSWORD'),
        'verify_ssl' => (bool) env('CUIC_VERIFY_SSL', false),
        'timeout' => (int) env('CUIC_TIMEOUT', 30),
        'max_queue_id' => (int) env('FINESSE_MAX_QUEUE_ID', 100),
    ],

    /*
    |--------------------------------------------------------------------------
    | CUIC - Cisco Unified Intelligence Center
    |--------------------------------------------------------------------------
    | Configuración para consumir la API REST de CUIC.
    | Los report_ids son identificadores INTERNOS de CUIC (asignados por el
    | sistema al crear el reporte). Se obtienen inspeccionando la URL del
    | reporte en el navegador:
    | https://host:8444/cuic/rest/{locale}/reports/execute/{REPORT_ID}
    */
    'cuic' => [
        'base_url' => env('CUIC_BASE_URL', 'https://pmie04cm06.css.gob.pa:8444'),
        'domain' => env('CUIC_DOMAIN', 'CCX'),
        'username' => env('CUIC_USERNAME'),
        'password' => env('CUIC_PASSWORD'),
        'verify_ssl' => (bool) env('CUIC_VERIFY_SSL', false),
        'timeout' => (int) env('CUIC_TIMEOUT', 30),
        'locale' => env('CUIC_LOCALE', 'es_PA'),

        /*
        | Mapa de reportes: clave semántica → configuración completa del reporte.
        |
        | Campos por reporte:
        |   id      → UUID interno de CUIC (copiado de la URL del reporte en la UI)
        |   locale  → locale del reporte (puede diferir del locale global)
        |   params  → mapa de paramIds fijos asignados por CUIC al crear el reporte
        |             Claves semánticas → paramId interno de CUIC
        |
        | Flujo de ejecución con filtros:
        |   1. POST /cuic/rest/{locale}/reports/{id}/filter   ← setFilter()
        |   2. GET  /cuic/rest/{locale}/reports/execute/{id}  ← executeReport()
        */
        'reports' => [

            // Transiciones de estado de agentes (sin filtros requeridos)
            'agent_state_transitions' => [
                'id' => env('CUIC_REPORT_AGENT_STATE_TRANSITIONS', 'DAB089391000019D000043E40A0B1855'),
                'locale' => 'es_PA',
                'params' => [],
            ],

            // Reporte de detalle de agentes — "Informe detallado de estado de agente"
            // defId: 8821D19410000133768D8F9C3F57F543 (mismo para custom y stock)
            //
            // ⚠ DIAGNÓSTICO: el endpoint GET /execute da HTTP 500 cuando se usa con
            // POST /filter previo en esta versión/configuración del servidor CUIC.
            // Causa: CUIC no persiste el filtro entre peticiones REST independientes.
            // Workaround activo: usar el reporte DAB089 (state_transitions) + filtro en memoria.
            //
            // Dos variantes disponibles (ambas con mismo defId y paramIds):
            //   - CUSTOM  (propiedad del usuario): 53C2272810000192000001050A0B1455 (NotReadyReport)
            //   - STOCK   (catálogo global):       8827A5EA100001336498D5B73F57F543
            'agent_detail' => [
                'id' => env('CUIC_REPORT_AGENT_DETAIL', '8827A5EA100001336498D5B73F57F543'),
                'locale' => 'es_ES',
                'params' => [
                    'start_datetime' => '8826E279100001332E83071D3F57F543',
                    'end_datetime' => '8826E2791000013322B912763F57F543',
                    'agent_names' => '8826E279100001334F8426E93F57F543',
                    'current_user' => '5FAA41181000015A0000021F0A4E5B53',
                ],
            ],

            // Agent Detail Report (AHT / Performance)
            'agent_performance_detail' => [
                'id' => env('CUIC_REPORT_AGENT_PERFORMANCE', 'E33ED4BF100001990000219C0A0B1855'),
                'locale' => 'es_ES',
                'params' => [
                    'start_datetime' => 'D9054E6E1000013246EBEA2F3F57F543',
                    'end_datetime' => 'D9054E6F100001326C2D2AA63F57F543',
                    'current_user' => '5B280A721000015A0000001D0A4E5B53',
                ],
            ],

            // Detail Call CSQ Agent Report
            'agent_csq_detail' => [
                'id' => env('CUIC_REPORT_AGENT_CSQ_DETAIL', 'E32941841000019D0000216E0A0B1855'),
                'locale' => 'es_ES',
                'params' => [
                    'start_datetime' => 'D0C5F75C10000133687E542C3F57F543',
                    'end_datetime' => 'D0C5F75B100001337DED6CAC3F57F543',
                    'current_user' => '6138C7E01000015A000003C40A4E5B53',
                ],
            ],

            // ContactCallDetail
            /**
             * {"reportId":"FCF5DE621000019F000011E80A0B1455",
             * "hardRefresh":false,
             * "filter":{
             * "repType":"STPROC",
             * "filterParams":[
             *  {"paramId":"5A18B127100001345BE2E30E3F57F543",
             * "paramType":"DATETIME",
             * "relativeDate":false,
             * "date":"08/13/2026 00:00:00",
             * "value":"08/13/2026 00:00:00"},
             * {"paramId":"5A18B1281000013434902F833F57F543",
             * "paramType":"DATETIME",
             * "relativeDate":false,
             * "date":"08/13/2026 23:59:59",
             * "value":"08/13/2026 23:59:59"},
             * {"paramId":"75ACE9481000016C000000850A4E5E1D",
             * "paramType":"STRING",
             * "value":"CCX\\ferncastillo"}]}}
             */
            'detailed_call_by_call_ccdr' => [
                'id' => env('CUIC_REPORT_CONTACT_CALL_DETAIL', 'FCF5DE621000019F000011E80A0B1455'),
                'locale' => 'es_ES',
                'params' => [
                    'start_datetime' => '5A18B127100001345BE2E30E3F57F543',
                    'end_datetime' => '5A18B1281000013434902F833F57F543',
                    'current_user' => '75ACE9481000016C000000850A4E5E1D',
                ],
            ],

            // Voice CSQ Summary Report (Realtime Snapshot)
            'voice_csq_summary' => [
                'id' => env('CUIC_REPORT_VOICE_CSQ_SUMMARY', 'F42547F51000019D00020F7D0A0B1855'),
                'locale' => 'es_ES',
                'params' => [
                    'csq_names' => '720298FB10000140000000A10A4E5E6F',
                ],
            ],

            // Detail Chat Agent Report
            'agent_chat_detail' => [
                'id' => env('CUIC_REPORT_AGENT_CHAT_DETAIL', 'E35B32C710000199000021C80A0B1855'),
                'locale' => 'es_ES',
                'params' => [
                    'start_datetime' => '76284BFE100001357AD6962A3F57F543',
                    'end_datetime' => '76284BFE100001354E56E44E3F57F543',
                    'current_user' => '5E2BA9CA1000015A000000400A4E5B53',
                ],
            ],

            // Agent Realtime Detail (filtered by Agent Login ID)
            'agent_realtime_detail' => [
                'id' => env('CUIC_REPORT_AGENT_REALTIME_DETAIL', '5D411E7010000140000000210A4E5E6B'),
                'locale' => 'es_ES',
                'params' => [
                    'agent_login_id' => '5D40A42D10000140000000190A4E5E6B',
                ],
            ],
        ],
    ],
];
