@extends('financeiro::layouts.app')

@section('title', 'Detalhes do Responsável - ' . $responsavel->nome)
@section('breadcrumb', 'Detalhes do Responsável')
@section('module-style', 'padding: 0;')

@push('styles')
    @include('financeiro::dashboard.styles')
@endpush

@section('financeiro-content')
<div class="financeiro-dashboard-container bg-[#f4f7f9] text-slate-800 font-sans min-h-screen antialiased flex flex-col justify-between" style="font-family: 'Inter', sans-serif;">
    <main class="w-full p-4 md:p-6 space-y-6" data-purpose="responsaveis-show-container">
        
        <!-- Cabeçalho -->
        <header class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pb-2" data-purpose="header-section">
            <div>
                <div class="flex items-center gap-2.5 text-brand-petroleum">
                    <div class="p-2 bg-brand-petroleum/10 text-brand-petroleum">
                        <svg class="w-6 h-6" width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        </svg>
                    </div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-xl md:text-2xl font-bold tracking-tight text-slate-900" style="margin: 0;">
                            {{ $responsavel->nome }}
                        </h1>
                        @if($responsavel->ativo ?? true)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Ativo
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                Inativo
                            </span>
                        @endif
                    </div>
                </div>
                <p class="text-xs md:text-sm text-slate-500 mt-1 pl-0.5" style="margin: 4px 0 0 0;">
                    CPF: {{ $responsavel->cpf_cnpj_formatado }} &bull; E-mail: {{ $responsavel->email }}
                </p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('financeiro.responsaveis.edit', $responsavel->id) }}" class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-amber-50 hover:bg-amber-100 text-amber-800 text-sm font-medium border border-amber-200 transition" style="text-decoration: none;">
                    <svg class="w-4 h-4 text-amber-700" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Editar Cadastro
                </a>
                <a href="{{ route('financeiro.responsaveis.index') }}" class="inline-flex items-center gap-2 px-3.5 py-2 btn-ieducar-action btn-ieducar-outline" style="text-decoration: none;">
                    <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 19l-7-7m0 0l7-7m-7 7h18" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Voltar
                </a>
            </div>
        </header>

        <!-- Ficha Cadastral em Cards Modernos -->
        <div class="bg-white shadow-xs border border-slate-200 overflow-hidden">
            <div class="bg-[#47728f] px-5 py-3 text-white flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Ficha Cadastral e Contatos
                    </h2>
                </div>
                <span class="text-xs text-slate-300 font-mono">
                    ID #{{ $responsavel->id }}
                </span>
            </div>

            <div class="p-5 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="p-3 bg-slate-50 border border-slate-200">
                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Documento (CPF/CNPJ)</div>
                    <div class="font-mono font-semibold text-slate-900 mt-1 text-sm">
                        {{ $responsavel->cpf_cnpj_formatado }}
                    </div>
                    @if($responsavel->rg_ie)
                        <div class="text-xs text-slate-400 mt-0.5">
                            RG/IE: {{ $responsavel->rg_ie }}
                        </div>
                    @endif
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200">
                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">E-mail Principal</div>
                    <div class="font-medium text-slate-900 mt-1 text-sm truncate" title="{{ $responsavel->email }}">
                        {{ $responsavel->email }}
                    </div>
                    <div class="text-xs text-slate-400 mt-0.5">Notificações e faturas</div>
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200">
                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Contatos Telefônicos</div>
                    <div class="font-mono font-medium text-slate-900 mt-1 text-sm">
                        {{ $responsavel->telefone_celular }}
                    </div>
                    @if($responsavel->telefone_fixo)
                        <div class="text-xs text-slate-500 font-mono mt-0.5">
                            Fixo: {{ $responsavel->telefone_fixo }}
                        </div>
                    @endif
                </div>

                <div class="p-3 bg-slate-50 border border-slate-200">
                    <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Endereço de Cobrança</div>
                    <div class="text-xs text-slate-700 mt-1 leading-relaxed">
                        {{ $responsavel->logradouro }}, {{ $responsavel->numero }}
                        @if($responsavel->complemento) ({{ $responsavel->complemento }}) @endif
                        <br>{{ $responsavel->bairro }}, {{ $responsavel->cidade }}/{{ $responsavel->estado }}
                        <br><span class="font-mono text-slate-500">CEP: {{ $responsavel->cep }}</span>
                    </div>
                </div>
            </div>

            @if($responsavel->observacoes)
                <div class="px-5 pb-5">
                    <div class="p-3 bg-blue-50/50 border border-blue-200 text-slate-700 text-xs">
                        <span class="font-semibold text-brand-petroleum">Observações:</span> {{ $responsavel->observacoes }}
                    </div>
                </div>
            @endif
        </div>

        <!-- Seção de Alunos Vinculados e Rateio Financeiro -->
        <section class="bg-white shadow-xs border border-slate-200 overflow-hidden" data-purpose="alunos-vinculados-section">
            <div class="bg-[#47728f] px-5 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-white">
                <div class="flex items-center gap-2">
                    <svg class="w-5 h-5 text-cyan-200" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 14l9-5-9-5-9 5 9 5z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                        <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    <h2 class="text-sm font-semibold tracking-wide" style="margin: 0; color: white;">
                        Alunos Vinculados e Rateio Financeiro ({{ $responsavel->alunoVinculos->count() }})
                    </h2>
                </div>
                <div class="text-xs text-slate-300">
                    Configuração de rateio percentual para divisão automática de cobranças
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-xs md:text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 uppercase text-[11px] font-semibold tracking-wider">
                            <th class="py-3 px-4">Código / Aluno</th>
                            <th class="py-3 px-4">Parentesco</th>
                            <th class="py-3 px-4 text-right">Rateio Financeiro (%)</th>
                            <th class="py-3 px-4 text-center">Responsável Principal</th>
                            <th class="py-3 px-4 text-center">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse($responsavel->alunoVinculos as $vinculo)
                            <tr class="hover:bg-slate-50/80 transition duration-150">
                                <td class="py-3.5 px-4 font-semibold text-slate-900">
                                    Aluno #{{ $vinculo->aluno_id }}
                                </td>
                                <td class="py-3.5 px-4 text-slate-600 capitalize">
                                    {{ $vinculo->parentesco }}
                                </td>
                                <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-900">
                                    {{ number_format($vinculo->percentual_rateio, 2, ',', '.') }}%
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    @if($vinculo->responsavel_principal)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            Principal
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                                            Secundário
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3.5 px-4 text-center">
                                    <form method="post" action="{{ route('financeiro.responsaveis.desvincular-aluno', [$responsavel->id, $vinculo->aluno_id]) }}" onsubmit="return confirm('Deseja realmente remover o vínculo deste aluno?');" style="display: inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center p-1.5 bg-red-50 hover:bg-red-100 text-red-700 text-xs font-medium border border-red-200 transition" title="Desvincular Aluno">
                                            <svg class="w-3.5 h-3.5" width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-8 text-center text-slate-400">
                                    Nenhum aluno vinculado a este responsável financeiro até o momento.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Formulário para Vincular Aluno -->
            <div class="p-5 bg-slate-50 border-t border-slate-200">
                <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-600" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path d="M12 4v16m8-8H4" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                    </svg>
                    Vincular Novo Aluno ao Responsável
                </h4>

                <form method="post" action="{{ route('financeiro.responsaveis.vincular-aluno', $responsavel->id) }}" class="flex flex-wrap items-end gap-3">
                    @csrf
                    <div class="w-full sm:w-44">
                        <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider mb-1">
                            Código Aluno (i-Educar) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="aluno_id" required placeholder="Ex: 1042" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                    </div>

                    <div class="w-full sm:w-40">
                        <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider mb-1">
                            Parentesco <span class="text-red-500">*</span>
                        </label>
                        <select name="parentesco" class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                            <option value="pai">Pai</option>
                            <option value="mae">Mãe</option>
                            <option value="avo">Avô / Avó</option>
                            <option value="tutor">Tutor Legal</option>
                            <option value="empresa">Empresa</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>

                    <div class="w-full sm:w-32">
                        <label class="block text-[11px] font-semibold text-slate-600 uppercase tracking-wider mb-1">
                            % Rateio <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="percentual_rateio" step="0.01" min="1" max="100" value="100.00" required class="w-full px-3 py-2 bg-white border border-slate-300 text-slate-800 text-sm font-mono focus:outline-none focus:border-brand-petroleum focus:ring-1 focus:ring-brand-petroleum transition">
                    </div>

                    <div class="flex items-center gap-2 pt-1 sm:pt-0 pb-2">
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                            <input type="checkbox" name="responsavel_principal" value="1" checked class="w-4 h-4 text-brand-petroleum rounded border-slate-300 focus:ring-brand-petroleum">
                            Principal
                        </label>
                    </div>

                    <div class="pt-1 sm:pt-0">
                        <button type="submit" class="inline-flex items-center gap-1.5 px-4 py-2 btn-ieducar-action btn-ieducar-green">
                            <svg class="w-4 h-4" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"></path>
                            </svg>
                            Vincular Aluno
                        </button>
                    </div>
                </form>
            </div>
        </section>

    </main>
</div>
@endsection
