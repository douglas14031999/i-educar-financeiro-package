<?php

namespace iEducar\Financeiro\Http\Middleware;

use Closure;
use iEducar\Financeiro\Constants\ProcessosFinanceiro;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AutorizarProcessoFinanceiro
{
    /**
     * Cache estático em memória por request para evitar queries repetidas de autorização
     */
    protected static array $permissionCache = [];

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|int  $processo Constante ou código do processo (ex: MENU_DASHBOARD ou 999951)
     * @param  string  $nivel visualiza | cadastra | exclui
     * @return mixed
     */
    public function handle(Request $request, Closure $next, $processo, string $nivel = 'visualiza')
    {
        $user = $request->user();

        if (!$user) {
            // Se o middleware 'auth' não estiver configurado (ex.: ambiente de teste simplificado)
            $configured = (array) config('financeiro.middleware', ['web', 'auth']);
            if (!in_array('auth', $configured)) {
                return $next($request);
            }

            if ($request->expectsJson()) {
                return response()->json(['error' => 'Não autenticado.'], 401);
            }
            return redirect()->guest('/login');
        }

        if (!static::verificarPermissao($user, $processo, $nivel)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Acesso negado. Você não possui autorização para este processo financeiro.',
                    'processo' => $processo,
                    'nivel' => $nivel,
                ], 403);
            }

            abort(403, 'Acesso negado. Seu perfil de usuário não possui permissão para executar esta operação financeira.');
        }

        return $next($request);
    }

    /**
     * Verifica se o usuário tem autorização para o processo e nível solicitado
     */
    public static function verificarPermissao($user, $processo, string $nivel = 'visualiza'): bool
    {
        if (!$user) {
            return false;
        }

        $userId = $user->getAuthIdentifier();
        $cacheKey = "{$userId}:{$processo}:{$nivel}";
        if (isset(static::$permissionCache[$cacheKey])) {
            return static::$permissionCache[$cacheKey];
        }

        // 1. Super-administradores e Administradores institucionais têm permissão irrestrita
        $isAdmin = false;
        if (isset($user->is_admin) && $user->is_admin) {
            $isAdmin = true;
        } elseif (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            $isAdmin = true;
        } elseif (isset($user->tipo_usuario_id) && in_array((int) $user->tipo_usuario_id, config('financeiro.perfis_padrao', [1]))) {
            $isAdmin = true;
        } elseif (isset($user->ref_cod_tipo_usuario) && in_array((int) $user->ref_cod_tipo_usuario, config('financeiro.perfis_padrao', [1]))) {
            $isAdmin = true;
        }

        if ($isAdmin) {
            return static::$permissionCache[$cacheKey] = true;
        }

        // 2. Se a tabela menu_tipo_usuario não existir no host, permite se usuário autenticado
        if (!Schema::hasTable('menu_tipo_usuario') || !Schema::hasTable('menus')) {
            return static::$permissionCache[$cacheKey] = true;
        }

        // 3. Resolve o código inteiro do processo
        $processoInt = static::resolverProcessoInt($processo);

        // 4. Resolve o tipo de usuário
        $tipoUsuarioId = $user->tipo_usuario_id ?? ($user->ref_cod_tipo_usuario ?? ($user->cod_tipo_usuario ?? null));
        if (!$tipoUsuarioId) {
            return static::$permissionCache[$cacheKey] = false;
        }

        // 5. Consulta menu_id correspondente
        $menu = DB::table('menus')
            ->where('process', $processoInt)
            ->orWhere('old', $processoInt)
            ->first();

        if (!$menu) {
            return static::$permissionCache[$cacheKey] = false;
        }

        $permissao = DB::table('menu_tipo_usuario')
            ->where('menu_id', $menu->id)
            ->where('tipo_usuario_id', $tipoUsuarioId)
            ->first();

        if (!$permissao) {
            return static::$permissionCache[$cacheKey] = false;
        }

        $coluna = match ($nivel) {
            'cadastra' => 'cadastra',
            'exclui'   => 'exclui',
            default    => 'visualiza',
        };

        $permitido = !empty($permissao->$coluna) && (int) $permissao->$coluna === 1;

        return static::$permissionCache[$cacheKey] = $permitido;
    }

    /**
     * Limpa o cache estático de permissões (útil para testes unitários)
     */
    public static function limparCache(): void
    {
        static::$permissionCache = [];
    }

    /**
     * Mapeia nome de constante ou valor direto para o integer do processo
     */
    protected static function resolverProcessoInt($processo): int
    {
        if (is_numeric($processo)) {
            return (int) $processo;
        }

        return match ($processo) {
            'MENU_FINANCEIRO_RAIZ' => ProcessosFinanceiro::MENU_FINANCEIRO_RAIZ,
            'MENU_DASHBOARD'       => ProcessosFinanceiro::MENU_DASHBOARD,
            'MENU_RESPONSAVEIS'    => ProcessosFinanceiro::MENU_RESPONSAVEIS,
            'MENU_PLANOS'          => ProcessosFinanceiro::MENU_PLANOS,
            'MENU_CONTRATOS'       => ProcessosFinanceiro::MENU_CONTRATOS,
            'MENU_TITULOS'         => ProcessosFinanceiro::MENU_TITULOS,
            'MENU_BAIXA_MANUAL'    => ProcessosFinanceiro::MENU_BAIXA_MANUAL,
            'MENU_RENEGOCIACAO'    => ProcessosFinanceiro::MENU_RENEGOCIACAO,
            'MENU_CONTAS_PAGAR'    => ProcessosFinanceiro::MENU_CONTAS_PAGAR,
            'MENU_RELATORIOS'      => ProcessosFinanceiro::MENU_RELATORIOS,
            'MENU_GATEWAY_CONFIG'  => ProcessosFinanceiro::MENU_GATEWAY_CONFIG,
            default                => (int) $processo,
        };
    }
}
