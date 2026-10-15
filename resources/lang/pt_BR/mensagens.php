<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mensagens de Domínio do Módulo Financeiro i-Educar (BUG-040)
    |--------------------------------------------------------------------------
    | Textos padronizados e humanizados para respostas ao usuário e auditoria.
    */
    'operacao_sucesso' => 'Operação realizada com sucesso.',
    'erro_generico' => 'Ocorreu uma falha ao processar a operação financeira. Por favor, tente novamente ou consulte o suporte.',
    
    // Títulos e Cobranças
    'titulo_nao_encontrado' => 'Título financeiro não localizado no sistema.',
    'titulo_liquidado' => 'O título já se encontra liquidado ou quitado.',
    'titulo_cancelado' => 'O título encontra-se cancelado.',
    'titulo_renegociado' => 'O título já foi renegociado em outro acordo.',
    
    // Caixa e Baixas
    'baixa_realizada' => 'Recebimento e baixa registrados com sucesso.',
    'estorno_realizado' => 'Estorno de recebimento concluído com recomposição de saldo.',
    'divergencia_valores' => 'O valor informado difere do cálculo financeiro esperado.',
    
    // Renegociação
    'renegociacao_sucesso' => 'Acordo de renegociação homologado com sucesso.',
    'renegociacao_cancelada' => 'Acordo de renegociação cancelado e parcelas originais reativadas.',
    
    // Contratos
    'contrato_criado' => 'Contrato financeiro emitido com sucesso.',
    'contrato_cancelado' => 'Contrato financeiro cancelado com sucesso.',
    'contrato_duplicado' => 'Já existe um contrato financeiro ativo para este aluno no ano letivo selecionado.',
    
    // Contas a Pagar
    'despesa_liquidada' => 'Despesa liquidada com sucesso com registro no extrato bancário.',
    'despesa_estornada' => 'Liquidação estornada com sucesso e saldo bancário recomposto.',
    
    // Gateways e Webhooks
    'gateway_sucesso' => 'Cobrança enviada com sucesso para o gateway financeiro.',
    'gateway_sincronizado' => 'Status da cobrança sincronizado com o gateway.',
    'webhook_reprocessado' => 'Evento colocado na fila para reprocessamento.',
];
