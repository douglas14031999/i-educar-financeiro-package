@extends('financeiro::layouts.app')

@section('title', 'Termo de Confissão de Dívida - Acordo ' . $acordo->numero_acordo)
@section('breadcrumb', 'Renegociação > Termo de Acordo')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            #documento-termo, #documento-termo * {
                visibility: visible;
            }
            #documento-termo {
                position: absolute;
                left: 0;
                top: 0;
                width: 100% !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="renegociacao-termo-container">
        
        <!-- Cabeçalho (Oculto na Impressão) -->
        <header class="no-print flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2" data-purpose="header-section">
            <div>
                <div class="flex items-center gap-2.5 text-brand-petroleum">
                    <div class="p-2 bg-brand-petroleum/10 text-brand-petroleum">
                        <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                    </div>
                    <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900" style="margin: 0;">
                        Termo de Confissão e Acordo Extrajudicial
                    </h1>
                </div>
                <p class="text-xs md:text-sm text-slate-500 mt-1 pl-0.5" style="margin: 4px 0 0 0;">
                    Acordo nº <strong class="font-mono text-slate-700">{{ $acordo->numero_acordo }}</strong> formalizado em {{ $acordo->created_at->format('d/m/Y \à\s H:i') }}
                </p>
            </div>
            <div class="flex items-center gap-2.5">
                <button type="button" onclick="window.print();" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white text-sm font-semibold shadow-sm hover:shadow transition duration-150">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Imprimir Termo de Acordo
                </button>
                <a href="{{ route('financeiro.renegociacao.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 text-sm font-medium border border-slate-300 shadow-2xs transition" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar
                </a>
            </div>
        </header>

        <!-- Termo Jurídico Formatado (Folha A4) -->
        <div id="documento-termo" class="bg-white shadow-xs border border-slate-200 p-8 md:p-12 text-slate-800 text-xs md:text-sm leading-relaxed space-y-6">
            <div class="text-center border-b-2 border-brand-petroleum pb-4 space-y-1">
                <h2 class="text-base md:text-lg font-bold text-brand-petroleum tracking-wide uppercase" style="margin: 0;">
                    INSTRUMENTO PARTICULAR DE CONFISSÃO E RENEGOCIAÇÃO DE DÍVIDA ESCOLAR
                </h2>
                <div class="text-xs text-slate-500 font-mono font-semibold">
                    ACORDO EXTRAJUDICIAL Nº {{ $acordo->numero_acordo }}
                </div>
            </div>

            <p class="text-justify leading-relaxed">
                Pelo presente instrumento particular, de um lado a <strong>INSTITUIÇÃO DE ENSINO CREDORA</strong>, devidamente qualificada no sistema de gestão escolar <strong>i-Educar</strong>, e de outro lado:
            </p>

            <div class="bg-slate-50 border border-slate-200 p-4 space-y-1 text-xs">
                <div class="font-bold text-slate-700 uppercase tracking-wider text-[11px]">DEVEDOR(A) / RESPONSÁVEL FINANCEIRO(A):</div>
                <div>Nome Completo: <strong>{{ $acordo->responsavel->nome }}</strong></div>
                <div>CPF / CNPJ: <strong class="font-mono">{{ $acordo->responsavel->cpf_cnpj_formatado }}</strong></div>
                <div>Telefone / WhatsApp: {{ $acordo->responsavel->telefone_celular ?? 'Não informado' }}</div>
                <div>Endereço: {{ $acordo->responsavel->logradouro ?? 'Conforme cadastro geral' }}, nº {{ $acordo->responsavel->numero ?? '-' }} - {{ $acordo->responsavel->bairro ?? '-' }}, {{ $acordo->responsavel->cidade ?? '-' }}/{{ $acordo->responsavel->estado ?? '-' }} (CEP: {{ $acordo->responsavel->cep ?? '-' }})</div>
            </div>

            <p class="text-justify leading-relaxed">
                Têm entre si, justo e contratado, a novação e o parcelamento da dívida acumulada, mediante as seguintes cláusulas e condições:
            </p>

            <p class="text-justify leading-relaxed">
                <strong>CLÁUSULA PRIMEIRA - DA CONFISSÃO DA DÍVIDA:</strong> O(A) DEVEDOR(A) reconhece expressamente e confessa ser legítimo(a) devedor(a) da quantia original de <strong>R$ {{ number_format($acordo->valor_original, 2, ',', '.') }}</strong>, oriunda de mensalidades e encargos educacionais inadimplidos referentes aos seguintes títulos:
                @foreach($acordo->titulosOriginais as $to)
                    <span class="font-mono">[Título #{{ $to->id }} - {{ $to->competencia }} - R$ {{ number_format($to->valor_nominal, 2, ',', '.') }}]</span>{{ !$loop->last ? ',' : '.' }}
                @endforeach
            </p>

            <p class="text-justify leading-relaxed">
                <strong>CLÁUSULA SEGUNDA - DA CONCESSÃO DE DESCONTO E VALOR DO ACORDO:</strong> Por mera liberalidade da CREDORA, concede-se o desconto de <strong>R$ {{ number_format($acordo->valor_desconto_concedido, 2, ',', '.') }}</strong>, restando o saldo líquido, certo e exigível a ser adimplido no valor de <strong>R$ {{ number_format($acordo->valor_acordo, 2, ',', '.') }}</strong>.
            </p>

            <p class="text-justify leading-relaxed">
                <strong>CLÁUSULA TERCEIRA - DA FORMA DE PAGAMENTO E PARCELAMENTO:</strong> A dívida renegociada será adimplida em <strong>{{ $acordo->qtd_parcelas }} parcela(s)</strong>, emitidas com opção de pagamento via Boleto Bancário e Pix dinâmico, conforme o seguinte cronograma de vencimentos:
            </p>

            <div class="overflow-x-auto border border-slate-200">
                <table class="w-full text-left border-collapse text-xs">
                    <thead>
                        <tr class="bg-slate-100 text-slate-700 font-semibold border-b border-slate-200 uppercase text-[11px]">
                            <th class="py-2.5 px-3 text-center" width="90">Parcela</th>
                            <th class="py-2.5 px-3" width="130">Vencimento</th>
                            <th class="py-2.5 px-3 text-right" width="140">Valor Previsto</th>
                            <th class="py-2.5 px-3 text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($acordo->novasParcelas as $np)
                            <tr>
                                <td class="py-2 px-3 text-center font-mono font-bold">{{ $np->numero_parcela }} / {{ $np->total_parcelas }}</td>
                                <td class="py-2 px-3 font-mono font-semibold text-brand-petroleum">{{ \Carbon\Carbon::parse($np->data_vencimento)->format('d/m/Y') }}</td>
                                <td class="py-2 px-3 text-right font-mono font-bold text-emerald-700">R$ {{ number_format($np->valor_nominal, 2, ',', '.') }}</td>
                                <td class="py-2 px-3 text-center">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                        {{ ucfirst($np->status) }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="text-justify leading-relaxed">
                <strong>CLÁUSULA QUARTA - DO DESCUMPRIMENTO E PERDA DO BENEFÍCIO:</strong> O não pagamento de qualquer parcela em até 30 (trinta) dias após o respectivo vencimento implicará no vencimento antecipado das parcelas vincendas, perda integral do desconto concedido na Cláusula Segunda, incidência de multa de 2% (dois por cento) e juros de 1% (um por cento) ao mês, além do encaminhamento para cobrança jurídica e inclusão nos órgãos de proteção ao crédito (SPC/Serasa).
            </p>

            <p class="text-justify leading-relaxed">
                E, por estarem de pleno e comum acordo, firmam o presente termo em 2 (duas) vias de igual teor e forma.
            </p>

            <div class="pt-12 grid grid-cols-2 gap-12 text-center text-xs">
                <div>
                    <div class="border-t border-slate-800 pt-2 font-semibold">
                        INSTITUIÇÃO CREDORA<br>
                        <span class="font-normal text-slate-500 text-[11px]">Setor Financeiro Escolar / i-Educar</span>
                    </div>
                </div>
                <div>
                    <div class="border-t border-slate-800 pt-2 font-semibold">
                        {{ $acordo->responsavel->nome }}<br>
                        <span class="font-normal text-slate-500 font-mono text-[11px]">CPF: {{ $acordo->responsavel->cpf_cnpj_formatado }}</span>
                    </div>
                </div>
            </div>

            <div class="pt-8 grid grid-cols-2 gap-12 text-center text-[11px] text-slate-500">
                <div>
                    <div class="border-t border-dashed border-slate-400 pt-1">
                        Testemunha 1 (Nome e CPF)
                    </div>
                </div>
                <div>
                    <div class="border-t border-dashed border-slate-400 pt-1">
                        Testemunha 2 (Nome e CPF)
                    </div>
                </div>
            </div>
        </div>

    </main>
</div>
@endsection
