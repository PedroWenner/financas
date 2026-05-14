# =============================================================
# AlcatechFinancas — Script de Build para Producao
# =============================================================
# O que faz:
#   1. Copia todos os arquivos do projeto para publish\build\
#   2. Separa em duas estruturas (Opcao A e Opcao B)
#   3. Substitui configuracoes locais pelas de producao
#   4. Gera arquivos .zip prontos para upload na Hostinger
#
# Como usar:
#   Abra o PowerShell na pasta publish\ e execute:
#   .\scripts\01-build.ps1
#
# Pre-requisitos:
#   - Edite publish\config\database.producao.php com as
#     credenciais reais do banco na Hostinger ANTES de rodar.
# =============================================================

param(
    [string]$Opcao = "A",   # "A" = app fora do public_html | "B" = tudo dentro
    [switch]$GerarZip = $true
)

$ErrorActionPreference = "Stop"

# ── Caminhos ──────────────────────────────────────────────────
$ROOT     = Resolve-Path "$PSScriptRoot\..\..\"   # C:\Programas\AlcatechFinancas
$PUBLISH  = "$PSScriptRoot\.."                    # publish\
$BUILD    = "$PUBLISH\build"
$CONFIG   = "$PUBLISH\config"

Write-Host ""
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "  AlcatechFinancas — Build de Producao" -ForegroundColor Cyan
Write-Host "  Opcao de deploy: $Opcao" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""

# ── Verificacoes ──────────────────────────────────────────────
Write-Host "[1/7] Verificando arquivos de configuracao..." -ForegroundColor Yellow

if (-not (Test-Path "$CONFIG\database.producao.php")) {
    Write-Host "ERRO: Arquivo '$CONFIG\database.producao.php' nao encontrado." -ForegroundColor Red
    Write-Host "      Copie e edite esse arquivo com as credenciais da Hostinger." -ForegroundColor Red
    exit 1
}

if (-not (Test-Path "$CONFIG\config.producao.php")) {
    Write-Host "ERRO: Arquivo '$CONFIG\config.producao.php' nao encontrado." -ForegroundColor Red
    exit 1
}

Write-Host "  OK - Configuracoes de producao encontradas." -ForegroundColor Green

# ── Limpar build anterior ─────────────────────────────────────
Write-Host "[2/7] Limpando build anterior..." -ForegroundColor Yellow
if (Test-Path $BUILD) {
    Remove-Item $BUILD -Recurse -Force
}
New-Item -ItemType Directory -Force -Path $BUILD | Out-Null
Write-Host "  OK." -ForegroundColor Green

# ── Criar estrutura ───────────────────────────────────────────
Write-Host "[3/7] Criando estrutura de pastas para Opcao $Opcao..." -ForegroundColor Yellow

if ($Opcao -eq "A") {
    # Opcao A: public_html/ (so a pasta public/) + alcatech_app/ (resto)
    $PUB  = "$BUILD\opcao-a\public_html"
    $APP  = "$BUILD\opcao-a\alcatech_app"
    New-Item -ItemType Directory -Force -Path $PUB | Out-Null
    New-Item -ItemType Directory -Force -Path $APP | Out-Null
} else {
    # Opcao B: tudo dentro de public_html/
    $PUB  = "$BUILD\opcao-b\public_html"
    $APP  = "$BUILD\opcao-b\public_html"
    New-Item -ItemType Directory -Force -Path $PUB | Out-Null
}
Write-Host "  OK." -ForegroundColor Green

# ── Copiar arquivos publicos ──────────────────────────────────
Write-Host "[4/7] Copiando arquivos publicos (public/)..." -ForegroundColor Yellow

# index.php adaptado
if ($Opcao -eq "A") {
    Copy-Item "$CONFIG\index.opcao-a.php" "$PUB\index.php" -Force
} else {
    Copy-Item "$CONFIG\index.opcao-b.php" "$PUB\index.php" -Force
}

# .htaccess de producao
Copy-Item "$CONFIG\htaccess.producao" "$PUB\.htaccess" -Force

# assets/
Copy-Item "$ROOT\public\assets" "$PUB\assets" -Recurse -Force

Write-Host "  OK - index.php, .htaccess, assets copiados." -ForegroundColor Green

# ── Copiar app, config, core, migrations ─────────────────────
Write-Host "[5/7] Copiando app/, config/, core/, migrations/..." -ForegroundColor Yellow

$pastas = @("app", "core", "migrations")
foreach ($p in $pastas) {
    Copy-Item "$ROOT\$p" "$APP\$p" -Recurse -Force
}

# config/ — usa versao de producao
New-Item -ItemType Directory -Force -Path "$APP\config" | Out-Null
Copy-Item "$CONFIG\config.producao.php"   "$APP\config\config.php"   -Force
Copy-Item "$CONFIG\database.producao.php" "$APP\config\database.php" -Force
Copy-Item "$ROOT\config\routes.php"       "$APP\config\routes.php"   -Force

# Opcao B: adicionar .htaccess de protecao nas pastas sensiveis
if ($Opcao -eq "B") {
    $pastas_proteger = @("app", "config", "core", "migrations")
    $htaccessBlock = "Options -Indexes`nRequire all denied"
    foreach ($p in $pastas_proteger) {
        $htPath = "$APP\$p\.htaccess"
        Set-Content -Path $htPath -Value $htaccessBlock -Encoding UTF8
    }
    Write-Host "  OK - .htaccess de protecao adicionado nas pastas sensiveis." -ForegroundColor Green
}

Write-Host "  OK - Arquivos da aplicacao copiados." -ForegroundColor Green

# ── Remover arquivos desnecessarios em producao ───────────────
Write-Host "[6/7] Removendo arquivos de desenvolvimento..." -ForegroundColor Yellow

$remover = @(
    "$APP\migrations",         # nao expor SQL em producao
    "$APP\config\routes.php"   # sera recriado abaixo (routes nao muda)
)

# Recolocar routes.php (removi por engano no exemplo)
Copy-Item "$ROOT\config\routes.php" "$APP\config\routes.php" -Force

# Remover migrations da pasta app (ja foi importado no banco)
# Comentado — mantenha se precisar re-rodar migrations
# Remove-Item "$APP\migrations" -Recurse -Force -ErrorAction SilentlyContinue

Write-Host "  OK." -ForegroundColor Green

# ── Gerar ZIP ─────────────────────────────────────────────────
if ($GerarZip) {
    Write-Host "[7/7] Gerando arquivo ZIP..." -ForegroundColor Yellow

    $zipPath = "$BUILD\alcatech-deploy-opcao${Opcao}.zip"
    $srcPath  = if ($Opcao -eq "A") { "$BUILD\opcao-a" } else { "$BUILD\opcao-b" }

    if (Test-Path $zipPath) { Remove-Item $zipPath -Force }

    Add-Type -Assembly System.IO.Compression.FileSystem
    [System.IO.Compression.ZipFile]::CreateFromDirectory($srcPath, $zipPath)

    $tamanho = [math]::Round((Get-Item $zipPath).Length / 1KB, 1)
    Write-Host "  OK - ZIP gerado: $zipPath ($tamanho KB)" -ForegroundColor Green
} else {
    Write-Host "[7/7] ZIP ignorado (use -GerarZip para gerar)." -ForegroundColor Gray
}

# ── Resumo ────────────────────────────────────────────────────
Write-Host ""
Write-Host "=============================================" -ForegroundColor Green
Write-Host "  BUILD CONCLUIDO COM SUCESSO!" -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green
Write-Host ""

if ($Opcao -eq "A") {
    Write-Host "  Estrutura gerada (Opcao A - recomendada com SSH):" -ForegroundColor Cyan
    Write-Host "    $BUILD\opcao-a\"
    Write-Host "    ├── public_html\   → enviar para /public_html/ na Hostinger"
    Write-Host "    └── alcatech_app\  → enviar para /alcatech_app/ na Hostinger"
} else {
    Write-Host "  Estrutura gerada (Opcao B - sem SSH):" -ForegroundColor Cyan
    Write-Host "    $BUILD\opcao-b\"
    Write-Host "    └── public_html\   → enviar para /public_html/ na Hostinger"
}

Write-Host ""
Write-Host "  Proximos passos:"
Write-Host "    1. Configure o banco de dados na Hostinger (hPanel > MySQL)"
Write-Host "    2. Importe publish\sql\schema_producao.sql no phpMyAdmin"
Write-Host "    3. Envie os arquivos via FTP ou Gerenciador de Arquivos"
Write-Host "    4. Ative SSL no hPanel > SSL"
Write-Host ""
