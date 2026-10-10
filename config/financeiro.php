<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Schema do PostgreSQL
    |--------------------------------------------------------------------------
    | Nome do schema isolado no PostgreSQL onde residem as tabelas financeiras.
    | Não alterar a menos que haja necessidade estrita de ambiente multi-tenant.
    */
    'schema' => env('FINANCEIRO_DB_SCHEMA', 'financeiro'),

    /*
    |--------------------------------------------------------------------------
    | Configurações de Rotas e Middlewares
    |--------------------------------------------------------------------------
    | Prefixo base e middlewares aplicados às rotas administrativas e de API.
    */
    'route_prefix' => env('FINANCEIRO_ROUTE_PREFIX', 'financeiro'),
    'middleware' => ['web', 'auth'],
    'api_middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Políticas Financeiras Padrão para Escolas Privadas
    |--------------------------------------------------------------------------
    | Parâmetros de juros, multa e tolerância aplicados aos títulos em atraso.
    */
    'politicas' => [
        // Multa rescisória / atraso (máximo legal de 2% para mensalidades escolares)
        'multa_percentual' => (float) env('FINANCEIRO_MULTA_PERCENTUAL', 2.00),

        // Juros de mora mensal (1.0% ao mês)
        'juros_mensal_percentual' => (float) env('FINANCEIRO_JUROS_MENSAL', 1.00),

        // Dias de carência/tolerância antes de incidir juros e multas
        'dias_tolerancia' => (int) env('FINANCEIRO_DIAS_TOLERANCIA', 0),

        // Dia de vencimento padrão sugerido para novos contratos
        'dia_vencimento_padrao' => (int) env('FINANCEIRO_DIA_VENCIMENTO_PADRAO', 10),

        // Cancelamento de matrícula: 'apenas_futuras' (mantém vencidas) ou 'todas'
        'politica_cancelamento_matricula' => 'apenas_futuras',
    ],

    /*
    |--------------------------------------------------------------------------
    | Gateway de Pagamento Padrão (MVP: Asaas)
    |--------------------------------------------------------------------------
    | Gateway utilizado para emissão de Boletos Híbridos com Pix e Cartão.
    */
    'gateway_padrao' => env('FINANCEIRO_GATEWAY_PADRAO', 'asaas'),

    'gateways' => [
        'asaas' => [
            'api_key' => env('ASAAS_API_KEY', ''),
            'environment' => env('ASAAS_ENV', 'sandbox'), // 'sandbox' ou 'production'
            'webhook_token' => env('ASAAS_WEBHOOK_TOKEN', ''),
            'base_url' => env('ASAAS_ENV', 'sandbox') === 'production'
                ? 'https://api.asaas.com/v3'
                : 'https://sandbox.asaas.com/api/v3',
            'dias_vencimento_apos_emissao' => 3,
        ],

        'efi' => [
            'client_id' => env('EFI_CLIENT_ID', ''),
            'client_secret' => env('EFI_CLIENT_SECRET', ''),
            'certificate_path' => env('EFI_CERTIFICATE_PATH', ''),
            'sandbox' => env('EFI_SANDBOX', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Régua de Cobrança Automatizada (E-mail e WhatsApp)
    |--------------------------------------------------------------------------
    | Marcos temporais nos quais as notificações de cobrança são enfileiradas.
    */
    'regua_cobranca' => [
        'habilitada' => env('FINANCEIRO_REGUA_HABILITADA', true),
        'lembretes_antes_vencimento' => [7, 3], // D-7, D-3
        'notificar_dia_vencimento' => true,      // D0
        'alertas_apos_vencimento' => [3, 10],   // D+3, D+10
    ],

    /*
    |--------------------------------------------------------------------------
    | Formatação e Moeda
    |--------------------------------------------------------------------------
    */
    'moeda' => 'BRL',
    'simbolo_moeda' => 'R$',
];
