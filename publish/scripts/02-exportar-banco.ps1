# =============================================================
# AlcatechFinancas — Exportar Banco Local para Producao
# =============================================================
# O que faz:
#   Exporta o banco alcatech_financas do MySQL local para um
#   arquivo .sql pronto para importar no phpMyAdmin da Hostinger.
#
# Como usar:
#   .\scripts\02-exportar-banco.ps1
#   .\scripts\02-exportar-banco.ps1 -SomenteEstrutura   (sem dados)
#   .\scripts\02-exportar-banco.ps1 -SomenteDados       (sem estrutura)
# =============================================================

param(
    [string]$Host        = "localhost",
    [string]$Porta       = "3306",
    [string]$Banco       = "alcatech_financas",
    [string]$Usuario     = "root",
    [string]$Senha       = $env:DB_PASS,   # defina DB_PASS no ambiente ou passe -Senha
    [switch]$SomenteEstrutura = $false,
    [switch]$SomenteDados     = $false
)

$ErrorActionPreference = "Stop"

# ── Localizar mysqldump ───────────────────────────────────────
$mysqldumpPaths = @(
    "C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqldump.exe",
    "C:\Program Files\MySQL\MySQL Server 8.1\bin\mysqldump.exe",
    "C:\xampp\mysql\bin\mysqldump.exe",
    "mysqldump"
)

$mysqldump = $null
foreach ($path in $mysqldumpPaths) {
    if (Test-Path $path -ErrorAction SilentlyContinue) {
        $mysqldump = $path
        break
    }
}

if (-not $mysqldump) {
    # Tentar via PATH do sistema
    try {
        $mysqldump = (Get-Command mysqldump -ErrorAction Stop).Source
    } catch {
        Write-Host "ERRO: mysqldump nao encontrado." -ForegroundColor Red
        Write-Host "      Instale o MySQL 8.0+ ou adicione ao PATH." -ForegroundColor Red
        exit 1
    }
}

Write-Host "mysqldump encontrado: $mysqldump" -ForegroundColor Green

# ── Destino do arquivo ────────────────────────────────────────
$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$PUBLISH   = "$PSScriptRoot\.."
$SQL_DIR   = "$PUBLISH\sql"

if (-not (Test-Path $SQL_DIR)) { New-Item -ItemType Directory -Force -Path $SQL_DIR | Out-Null }

if ($SomenteEstrutura) {
    $arquivo = "$SQL_DIR\backup_estrutura_$timestamp.sql"
    $extra   = "--no-data"
    $tipo    = "ESTRUTURA APENAS"
} elseif ($SomenteDados) {
    $arquivo = "$SQL_DIR\backup_dados_$timestamp.sql"
    $extra   = "--no-create-info"
    $tipo    = "DADOS APENAS"
} else {
    $arquivo = "$SQL_DIR\backup_completo_$timestamp.sql"
    $extra   = ""
    $tipo    = "COMPLETO (estrutura + dados)"
}

Write-Host ""
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "  Exportando banco: $Banco" -ForegroundColor Cyan
Write-Host "  Tipo: $tipo" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""

# ── Executar mysqldump ────────────────────────────────────────
$args = @(
    "--host=$Host",
    "--port=$Porta",
    "--user=$Usuario",
    "--password=$Senha",
    "--databases", $Banco,
    "--add-drop-table",
    "--add-drop-database",
    "--create-options",
    "--disable-keys",
    "--extended-insert",
    "--set-charset",
    "--routines",           # inclui procedures e funcoes
    "--triggers",
    "--single-transaction", # snapshot consistente sem lock
    "--skip-lock-tables",
    "--result-file=$arquivo"
)

if ($extra) { $args += $extra }

Write-Host "Exportando..." -ForegroundColor Yellow

& $mysqldump @args 2>&1 | Where-Object { $_ -notmatch "Warning.*password" }

if ($LASTEXITCODE -eq 0 -or (Test-Path $arquivo)) {
    $tamanho = [math]::Round((Get-Item $arquivo).Length / 1KB, 1)
    Write-Host ""
    Write-Host "Exportacao concluida!" -ForegroundColor Green
    Write-Host "Arquivo: $arquivo ($tamanho KB)" -ForegroundColor Green
    Write-Host ""
    Write-Host "Como importar na Hostinger:"
    Write-Host "  1. hPanel > Bancos de Dados > phpMyAdmin"
    Write-Host "  2. Selecione o banco criado"
    Write-Host "  3. Aba 'Importar' > Escolher arquivo > $arquivo"
    Write-Host "  4. Clique em 'Executar'"
} else {
    Write-Host "ERRO na exportacao. Verifique as credenciais e tente novamente." -ForegroundColor Red
    exit 1
}
