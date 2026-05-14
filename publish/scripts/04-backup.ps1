# =============================================================
# AlcatechFinancas — Backup do Banco de Producao (via SSH)
# =============================================================
# Pre-requisito: Plano Hostinger com SSH habilitado
#   Ou use o phpMyAdmin para exportar manualmente.
#
# Como usar:
#   .\scripts\04-backup.ps1
#   .\scripts\04-backup.ps1 -Restaurar "backup_completo_20260514.sql"
# =============================================================

param(
    [string]$SSH_HOST    = "srv123.hostinger.com",  # servidor SSH da Hostinger
    [string]$SSH_USER    = "u123456789",
    [string]$SSH_KEY     = "$HOME\.ssh\id_rsa",     # chave SSH (opcional)
    [string]$DB_HOST     = "localhost",
    [string]$DB_NAME     = "u123456789_financas",   # nome do banco na Hostinger
    [string]$DB_USER     = "u123456789_root",
    [string]$DB_PASS     = "",                       # senha do banco na Hostinger
    [string]$Restaurar   = ""                        # nome do arquivo para restaurar
)

$PUBLISH  = "$PSScriptRoot\.."
$SQL_DIR  = "$PUBLISH\sql\backups-producao"

if (-not (Test-Path $SQL_DIR)) { New-Item -ItemType Directory -Force -Path $SQL_DIR | Out-Null }

$timestamp = Get-Date -Format "yyyyMMdd_HHmmss"
$arquivo   = "$SQL_DIR\prod_backup_$timestamp.sql"

if ($Restaurar) {
    # ── RESTAURAR ─────────────────────────────────────────────
    $sqlFile = "$SQL_DIR\$Restaurar"
    if (-not (Test-Path $sqlFile)) {
        Write-Host "ERRO: Arquivo '$sqlFile' nao encontrado." -ForegroundColor Red
        exit 1
    }

    Write-Host "ATENCAO: Restaurar apagara todos os dados atuais do banco!" -ForegroundColor Red
    $confirmar = Read-Host "Digite 'CONFIRMAR' para continuar"
    if ($confirmar -ne "CONFIRMAR") { Write-Host "Cancelado."; exit 0 }

    Write-Host "Restaurando $Restaurar..." -ForegroundColor Yellow

    $remoteCmd = "mysql -h $DB_HOST -u $DB_USER -p'$DB_PASS' $DB_NAME < /tmp/restore.sql"
    ssh "${SSH_USER}@${SSH_HOST}" "cat > /tmp/restore.sql" < $sqlFile
    ssh "${SSH_USER}@${SSH_HOST}" $remoteCmd

    Write-Host "Restauracao concluida!" -ForegroundColor Green

} else {
    # ── BACKUP ────────────────────────────────────────────────
    Write-Host ""
    Write-Host "=============================================" -ForegroundColor Cyan
    Write-Host "  Backup do banco de producao" -ForegroundColor Cyan
    Write-Host "  $SSH_USER@$SSH_HOST" -ForegroundColor Cyan
    Write-Host "=============================================" -ForegroundColor Cyan
    Write-Host ""

    $remoteCmd = "mysqldump -h $DB_HOST -u $DB_USER -p'$DB_PASS' --single-transaction --routines $DB_NAME"

    Write-Host "Executando backup remoto via SSH..." -ForegroundColor Yellow

    ssh "${SSH_USER}@${SSH_HOST}" $remoteCmd | Set-Content -Path $arquivo -Encoding UTF8

    if (Test-Path $arquivo) {
        $tamanho = [math]::Round((Get-Item $arquivo).Length / 1KB, 1)
        Write-Host "Backup salvo: $arquivo ($tamanho KB)" -ForegroundColor Green
    } else {
        Write-Host "ERRO: Backup falhou." -ForegroundColor Red
        Write-Host ""
        Write-Host "ALTERNATIVA MANUAL (phpMyAdmin):" -ForegroundColor Yellow
        Write-Host "  1. hPanel > Bancos de Dados > phpMyAdmin"
        Write-Host "  2. Selecione o banco"
        Write-Host "  3. Aba 'Exportar' > Formato SQL > Executar"
        exit 1
    }
}
