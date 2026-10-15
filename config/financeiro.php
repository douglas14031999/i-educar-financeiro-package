<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Schema do PostgreSQL
    |--------------------------------------------------------------------------
    | Nome do schema isolado no PostgreSQL onde residem as tabelas financeiras.
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
        // Multa rescisória / atraso (máximo legal de 2% para mensalidades escolares - Lei 9.870/99)
        'multa_percentual' => (float) env('FINANCEIRO_MULTA_PERCENTUAL', 2.00),

        // Juros de mora mensal (1.0% ao mês pro-rata die)
        'juros_mensal_percentual' => (float) env('FINANCEIRO_JUROS_MENSAL', 1.00),

        // Dias de carência/tolerância antes de incidir encargos ou bloquear rematrícula
        'dias_tolerancia' => (int) env('FINANCEIRO_DIAS_TOLERANCIA', 0),

        // Dia de vencimento padrão sugerido para novos contratos
        'dia_vencimento_padrao' => (int) env('FINANCEIRO_DIA_VENCIMENTO_PADRAO', 10),

        // Cancelamento de matrícula: 'apenas_futuras' (preserva títulos vencidos) ou 'todas'
        'politica_cancelamento_matricula' => env('FINANCEIRO_CANCELAMENTO_MATRICULA', 'apenas_futuras'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Gateway de Pagamento Integrado (Asaas v3)
    |--------------------------------------------------------------------------
    | Gateway oficial para emissão de Boletos Híbridos com Pix QR Code e Carnês.
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
            'dias_vencimento_apos_emissao' => (int) env('ASAAS_DIAS_VENCIMENTO_EMISSAO', 3),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Régua de Cobrança Automatizada (E-mail e WhatsApp)
    |--------------------------------------------------------------------------
    */
    'regua_cobranca' => [
        'habilitada' => env('FINANCEIRO_REGUA_HABILITADA', true),
        'assunto_padrao' => 'Aviso Financeiro Escolar',
        'horario_comercial_inicio' => 7,
        'horario_comercial_fim' => 22,
    ],

    /*
    |--------------------------------------------------------------------------
    | Parâmetros Fiscais (NFS-e / Nota Fiscal de Serviços)
    |--------------------------------------------------------------------------
    */
    'fiscal' => [
        'nfse_habilitada' => (bool) env('FINANCEIRO_NFSE_HABILITADA', false),
        'codigo_municipio_ibge' => env('FINANCEIRO_MUNICIPIO_IBGE', ''),
        'aliquota_iss' => (float) env('FINANCEIRO_ALIQUOTA_ISS', 2.00),
    ],

    /*
    |--------------------------------------------------------------------------
    | Formatação e Moeda
    |--------------------------------------------------------------------------
    */
    'moeda' => 'BRL',
    'simbolo_moeda' => 'R$',
];
