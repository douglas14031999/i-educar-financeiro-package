# Módulo Financeiro para o i-Educar (Escolas Privadas e Comunitárias)

[![Latest Version](https://img.shields.io/badge/version-1.0.0-blue.svg)](https://github.com/douglas14031999/i-educar-financeiro-package)
[![License: GPL-2.0](https://img.shields.io/badge/License-GPL%202.0-green.svg)](LICENSE)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.4%20%7C%208.x-purple.svg)](composer.json)
[![Laravel Compatible](https://img.shields.io/badge/Laravel-8%20%7C%209%20%7C%2010%20%7C%2011-red.svg)](composer.json)

Pacote oficial e desacoplado desenvolvido para adicionar gestão financeira profissional ao **i-Educar 2.10**, atendendo escolas privadas, conveniadas e comunitárias.

O pacote se integra de forma **Plug-and-Play** ao i-Educar:
- **Zero Redundância Pedagógica**: Reutiliza 100% dos dados já cadastrados no i-Educar (Alunos, Matrículas, Turmas, Séries e Escolas).
- **Isolamento de Banco**: Opera em schema PostgreSQL dedicado (`financeiro.*`), mantendo a integridade do banco do i-Educar.
- **Identidade Visual Nativa**: 100% das telas utilizam os formulários, tabelas, cores (`#47728f`, `#ccdce6`) e botões oficiais do i-Educar (`.tablecadastro`, `.botaosalvar`).

---

## 🎯 Principais Funcionalidades

1. **Responsáveis Financeiros e Rateio**:
   - Vínculo de responsáveis com múltiplos alunos.
   - Suporte a rateio proporcional de custos entre pais separados (ex: 50%/50% com carnês independentes).

2. **Planos de Pagamento e Motor de Contratos**:
   - Tabela de preços por curso, série e ano letivo.
   - Geração automática de carnês de mensalidades atômicas com tratamento de centavos.
   - Conexão nativa com a matrícula do i-Educar (ao matricular gera contrato; ao cancelar matrícula cancela apenas parcelas futuras não vencidas).

3. **Gateway de Pagamento Asaas (Boleto & Pix Dinâmico)**:
   - Emissão de Boletos Híbridos com QR Code Pix embutido e Pix Copia-e-Cola.
   - Webhook com **tabela de idempotência** (risco zero de duplicidade de baixa).
   - Consulta e sincronização de status em tempo real com 1 clique.

4. **Baixa de Balcão e Recibos Digitais**:
   - Baixa presencial com cálculo automático de juros (1% a.m.) e multa (2%).
   - Impressão de recibo oficial de pagamento com autenticação criptográfica SHA-256.

5. **Régua de Cobrança e Gestão de Inadimplência**:
   - Gatilhos automáticos (D-7, D-3, D0, D+3, D+10) via WhatsApp e E-mail.
   - Botão de envio rápido via WhatsApp direto da secretaria.
   - Painel de Inadimplência por faixas de atraso (Aging: 1-30, 31-60, 61-90, >90 dias - PCLD).
   - Bloqueio de rematrícula de inadimplentes com respaldo no **Artigo 5º da Lei Federal nº 9.870/1999**.

6. **Renegociação de Dívidas e Acordos**:
   - Simulador de propostas com concessão de desconto e novação de dívida.
   - Emissão do Instrumento Particular de Confissão de Dívida pronto para assinatura.

7. **Contas a Pagar e Fluxo de Caixa**:
   - Controle de despesas da escola por centro de custo e plano de contas contábil.
   - Demonstrativo mensal de entradas realizadas vs despesas pagas.

8. **Relatórios Fiscais e Integração NFS-e**:
   - **DRE Escolar**: Demonstração do Resultado do Exercício.
   - **Informe de IRPF**: Declaração de pagamentos com autenticação digital para imposto de renda.
   - **Quitação Anual**: Declaração obrigatória conforme a **Lei Federal nº 12.007/2009**.
   - **Evento `TituloPago`**: Payload padronizado para integração com emissores de NFS-e (Focus NFe, PlugNotas).

---

## 🚀 Instalação Plug-and-Play

### Opção 1: Instalação Automática em 1 Comando (Recomendado)

Na pasta raiz da sua instalação do **i-Educar**, execute o comando abaixo:

```bash
curl -sSL https://raw.githubusercontent.com/douglas14031999/i-educar-financeiro-package/main/install.sh | bash
```

> **O que o script faz sozinho:**
> - Detecta o ambiente do i-Educar;
> - Configura o repositório VCS no `composer.json`;
> - Baixa e instala o pacote via Composer;
> - Publica as configurações (`config/financeiro.php`);
> - Executa as migrations do schema `financeiro.*` e insere os menus no i-Educar;
> - Roda o seeder do plano de contas padrão escolar;
> - Limpa e otimiza os caches da aplicação.

---

### Opção 2: Instalação Manual via Composer

1. Adicione o repositório no `composer.json` do seu i-Educar:
```json
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/douglas14031999/i-educar-financeiro-package.git"
    }
]
```

2. Execute o require:
```bash
composer require douglas14031999/i-educar-financeiro-package
```


### 3. Rodar as Migrações do Banco de Dados

```bash
php artisan migrate
```

O comando irá:
1. Criar o schema `financeiro.*` no PostgreSQL.
2. Criar todas as tabelas com relacionamentos com `pmieducar.*`.
3. Inserir o menu **"Financeiro"** e seus 10 submenus na tabela nativa de menus do i-Educar.

### 4. (Opcional) Publicar as Configurações

```bash
php artisan vendor:publish --tag=financeiro-config
```

Um arquivo `config/financeiro.php` será criado permitindo ajustar políticas de juros, multa, token do Asaas e canais de régua.

---

## ⚙️ Configuração do Gateway Asaas

1. Acesse o i-Educar com seu usuário administrador.
2. No menu lateral, clique em **Financeiro > Configurações do Gateway**.
3. Insira sua **API Key do Asaas** (Sandbox ou Produção) e clique no botão **Testar Conexão**.
4. Configure a URL do Webhook no painel do Asaas:
   - URL: `https://seu-ieducar.com.br/financeiro/webhooks/asaas`
   - Eventos: `PAYMENT_RECEIVED`, `PAYMENT_CONFIRMED`, `PAYMENT_DELETED`.

---

## ⏰ Rotina Automática da Régua de Cobrança

No agendador de tarefas do servidor (`crontab`), adicione a execução diária do comando da régua:

```bash
0 8 * * * cd /caminho/do/ieducar && php artisan financeiro:processar-regua >> /dev/null 2>&1
```

O comando avaliará os vencimentos e agendará as notificações por WhatsApp/E-mail conforme as regras ativas.

---

## 🧪 Executando os Testes

O pacote acompanha suíte completa de testes unitários:

```bash
vendor/bin/phpunit
```

---

## 📄 Licença

Este pacote é um software livre distribuído sob os termos da licença [GNU General Public License v2.0 (GPL-2.0)](LICENSE), em total conformidade com o projeto **i-Educar**.
