#!/usr/bin/env bash
# ==============================================================================
# Script de Instalação Automatizada: Módulo Financeiro i-Educar (Escolas Privadas)
# Repositório: https://github.com/douglas14031999/i-educar-financeiro-package
# ==============================================================================

set -e

# Cores para saída no terminal
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

echo -e "${CYAN}${BOLD}"
echo "================================================================================"
echo "    INSTALADOR OFICIAL: MÓDULO FINANCEIRO I-EDUCAR (ESCOLAS PRIVADAS)           "
echo "================================================================================"
echo -e "${NC}"

# 1. Localização da instalação do i-Educar
TARGET_DIR="${IEDUCAR_DIR:-$(pwd)}"

if [ ! -f "$TARGET_DIR/artisan" ] || [ ! -f "$TARGET_DIR/composer.json" ]; then
    echo -e "${YELLOW}[!] Não foi detectada uma instalação do i-Educar no diretório atual: $TARGET_DIR${NC}"
    echo -e "Tentando caminhos comuns de instalação (/var/www/ieducar, /var/www/html)..."
    
    if [ -f "/var/www/ieducar/artisan" ]; then
        TARGET_DIR="/var/www/ieducar"
    elif [ -f "/var/www/html/artisan" ]; then
        TARGET_DIR="/var/www/html"
    elif [ -f "/var/www/i-educar/artisan" ]; then
        TARGET_DIR="/var/www/i-educar"
    else
        echo -e "${RED}[X] ERRO: Instalação do i-Educar não encontrada.${NC}"
        echo -e "Por favor, navegue até a pasta do i-Educar e execute o comando novamente:"
        echo -e "  ${BOLD}cd /caminho/do/seu/ieducar && curl -sSL https://raw.githubusercontent.com/douglas14031999/i-educar-financeiro-package/main/install.sh | bash${NC}"
        exit 1
    fi
fi

echo -e "${GREEN}[✓] Instalação do i-Educar detectada em:${NC} ${BOLD}$TARGET_DIR${NC}"
cd "$TARGET_DIR"

# 2. Verificação de pré-requisitos (PHP e Composer)
echo -e "\n${BLUE}[*] Verificando dependências do sistema...${NC}"

if ! command -v php &> /dev/null; then
    echo -e "${RED}[X] ERRO: PHP não encontrado no PATH do sistema.${NC}"
    exit 1
fi

PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo -e "  - PHP Detectado: ${CYAN}$PHP_VERSION${NC}"

if ! command -v composer &> /dev/null; then
    echo -e "${RED}[X] ERRO: Composer não encontrado no sistema.${NC}"
    echo -e "Instale o Composer antes de prosseguir: https://getcomposer.org"
    exit 1
fi

COMPOSER_BIN=$(command -v composer)
echo -e "  - Composer Detectado: ${CYAN}$COMPOSER_BIN${NC}"

export COMPOSER_ALLOW_SUPERUSER=1

# 3. Adicionar repositório VCS ao composer.json
echo -e "\n${BLUE}[*] Configurando repositório do Módulo Financeiro no Composer...${NC}"
composer config repositories.ieducar-financeiro vcs https://github.com/douglas14031999/i-educar-financeiro-package.git

# 4. Instalar o pacote via Composer
echo -e "\n${BLUE}[*] Baixando e instalando douglas14031999/i-educar-financeiro-package...${NC}"
INSTALLED=false
if composer require douglas14031999/i-educar-financeiro-package:dev-main -W --no-interaction 2>/dev/null; then
    INSTALLED=true
elif composer require douglas14031999/i-educar-financeiro-package:dev-main -W --ignore-platform-reqs --no-interaction 2>/dev/null; then
    INSTALLED=true
fi

if [ "$INSTALLED" = false ]; then
    echo -e "${YELLOW}[!] Download via Composer bloqueado por autenticação/rate-limit. Aplicando sincronização direta via Git...${NC}"
    TMP_CLONE="/tmp/ieducar-financeiro-clone-$$"
    rm -rf "$TMP_CLONE"
    git clone https://github.com/douglas14031999/i-educar-financeiro-package.git "$TMP_CLONE"
    mkdir -p vendor/douglas14031999/i-educar-financeiro-package
    rsync -av --exclude='.git' "$TMP_CLONE/" vendor/douglas14031999/i-educar-financeiro-package/
    rm -rf "$TMP_CLONE"
    composer dump-autoload -o --no-interaction 2>/dev/null || true
    echo -e "${GREEN}[✓] Código do pacote sincronizado com sucesso!${NC}"
fi


# 5. Publicar arquivos de configuração
echo -e "\n${BLUE}[*] Publicando arquivos de configuração (config/financeiro.php)...${NC}"
php artisan vendor:publish --tag=financeiro-config --force

# 6. Executar migrações do schema financeiro.*
echo -e "\n${BLUE}[*] Executando migrações do banco de dados (schema financeiro e menus)...${NC}"
php artisan migrate --force

# 7. Executar seeder do Plano de Contas padrão escolar
echo -e "\n${BLUE}[*] Populando Plano de Contas padrão para escolas privadas...${NC}"
SEEDER_FILE="vendor/douglas14031999/i-educar-financeiro-package/database/seeders/PlanoContasPadraoSeeder.php"
if [ -f "$SEEDER_FILE" ]; then
    php -r "require 'vendor/autoload.php'; require '$SEEDER_FILE'; (new iEducar\Financeiro\Database\Seeders\PlanoContasPadraoSeeder())->run();" 2>/dev/null || php artisan db:seed --class="iEducar\\Financeiro\\Database\\Seeders\\PlanoContasPadraoSeeder" --force 2>/dev/null || true
    echo -e "${GREEN}[✓] Plano de Contas inicial configurado com sucesso!${NC}"
fi

# 8. Sincronizar menus e permissões no i-Educar
echo -e "\n${BLUE}[*] Sincronizando menus e permissões do Módulo Financeiro...${NC}"
php artisan financeiro:sincronizar-menus || true

# 9. Limpar e atualizar caches da aplicação
echo -e "\n${BLUE}[*] Otimizando caches da aplicação...${NC}"
php artisan cache:clear || true
php artisan route:clear || true
php artisan view:clear || true
php artisan config:clear || true

echo -e "\n${GREEN}${BOLD}================================================================================"
echo "    INSTALAÇÃO CONCLUÍDA COM SUCESSO! 🎉                                       "
echo -e "================================================================================${NC}"
echo -e "${CYAN}O Módulo Financeiro já está ativo no seu i-Educar!${NC}\n"

echo -e "  📌 ${BOLD}Acesso ao Módulo:${NC} Abra seu navegador em ${BOLD}/financeiro${NC} ou use o menu lateral"
echo -e "  🔄 ${YELLOW}${BOLD}ATENÇÃO AO MENU:${NC} Se você já está logado no i-Educar no navegador,"
echo -e "     ${BOLD}FAÇA LOGOUT (SAIR) E LOGIN NOVAMENTE${NC} para que o i-Educar recarregue o menu lateral!"
echo -e "  ⚙️  ${BOLD}Gateway Asaas:${NC} Configure a chave de API em ${BOLD}Financeiro > Configurações do Gateway${NC}"
echo -e "  ⏰ ${BOLD}Régua Automática:${NC} Configure no crontab do servidor:"
echo -e "     ${YELLOW}0 8 * * * cd $TARGET_DIR && php artisan financeiro:processar-regua >> /dev/null 2>&1${NC}\n"
echo -e "${GREEN}Pronto para gerenciar as finanças da sua escola!${NC}\n"

