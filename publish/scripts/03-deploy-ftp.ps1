# =============================================================
# AlcatechFinancas — Deploy via FTP para Hostinger
# =============================================================
# Pre-requisito: WinSCP instalado
#   Download: https://winscp.net/eng/download.php
#
# Como usar:
#   1. Preencha as variaveis de configuracao abaixo
#   2. Execute: .\scripts\03-deploy-ftp.ps1
#   3. Para deploy completo (Opcao A): .\scripts\03-deploy-ftp.ps1 -Opcao A
#
# ATENCAO: Este script faz UPLOAD de todos os arquivos.
#          Primeiro deploy: use -ModoInicial
#          Atualizacoes:    use sem flag (sincroniza apenas mudancas)
# =============================================================

param(
    [string]$Opcao      = "A",   # "A" ou "B"
    [switch]$ModoInicial = $false  # $true = sobrescreve tudo
)

# ┌─────────────────────────────────────────────────────────────┐
# │  CONFIGURE AQUI — suas credenciais FTP da Hostinger         │
# └─────────────────────────────────────────────────────────────┘
$FTP_HOST    = "ftp.seudominio.com.br"   # ou IP do servidor FTP
$FTP_USUARIO = "u123456789"              # usuario FTP da Hostinger
$FTP_SENHA   = "SuaSenhaFTP"            # senha FTP
$FTP_PORTA   = 21

# Caminhos remotos na Hostinger
$REMOTE_PUBLIC_HTML  = "/public_html"
$REMOTE_APP_DIR      = "/alcatech_app"   # apenas Opcao A

# Caminho do WinSCP
$WinSCP = "C:\Program Files (x86)\WinSCP\WinSCP.com"
if (-not (Test-Path $WinSCP)) {
    $WinSCP = "C:\Program Files\WinSCP\WinSCP.com"
}

# ── Verificacoes ──────────────────────────────────────────────
if (-not (Test-Path $WinSCP)) {
    Write-Host "ERRO: WinSCP nao encontrado em '$WinSCP'" -ForegroundColor Red
    Write-Host "      Instale o WinSCP em https://winscp.net e tente novamente." -ForegroundColor Red
    Write-Host ""
    Write-Host "ALTERNATIVA: Use o Gerenciador de Arquivos da Hostinger (hPanel):" -ForegroundColor Yellow
    Write-Host "  1. hPanel > Gerenciador de Arquivos"
    Write-Host "  2. Navegue para public_html/"
    Write-Host "  3. Clique em 'Upload' e selecione os arquivos de publish\build\"
    exit 1
}

$PUBLISH = "$PSScriptRoot\.."
$BUILD   = "$PUBLISH\build"

if ($Opcao -eq "A") {
    $LOCAL_PUBLIC = "$BUILD\opcao-a\public_html"
    $LOCAL_APP    = "$BUILD\opcao-a\alcatech_app"
} else {
    $LOCAL_PUBLIC = "$BUILD\opcao-b\public_html"
}

if (-not (Test-Path $LOCAL_PUBLIC)) {
    Write-Host "ERRO: Build nao encontrado. Execute primeiro:" -ForegroundColor Red
    Write-Host "      .\scripts\01-build.ps1 -Opcao $Opcao" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "  AlcatechFinancas — Deploy FTP" -ForegroundColor Cyan
Write-Host "  Host: $FTP_HOST | Opcao: $Opcao" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""

# ── Script WinSCP ─────────────────────────────────────────────
$syncMode = if ($ModoInicial) { "SynchronizeMode.Remote" } else { "SynchronizeMode.Remote" }

$winscpScript = @"
option batch abort
option confirm off
open ftp://${FTP_USUARIO}:${FTP_SENHA}@${FTP_HOST}:${FTP_PORTA}/ -passive

# Upload public_html
synchronize remote "$LOCAL_PUBLIC" "$REMOTE_PUBLIC_HTML" -delete

"@

if ($Opcao -eq "A") {
    $winscpScript += @"
# Upload alcatech_app (fora do public_html)
synchronize remote "$LOCAL_APP" "$REMOTE_APP_DIR" -delete

"@
}

$winscpScript += @"
exit
"@

$scriptFile = [System.IO.Path]::GetTempFileName() + ".txt"
$winscpScript | Set-Content -Path $scriptFile -Encoding UTF8

Write-Host "Iniciando deploy FTP..." -ForegroundColor Yellow
Write-Host "De: $LOCAL_PUBLIC" -ForegroundColor Gray
Write-Host "Para: $FTP_HOST$REMOTE_PUBLIC_HTML" -ForegroundColor Gray
Write-Host ""

& $WinSCP /script="$scriptFile" /log="$PUBLISH\deploy.log"

Remove-Item $scriptFile -Force -ErrorAction SilentlyContinue

if ($LASTEXITCODE -eq 0) {
    Write-Host ""
    Write-Host "Deploy concluido com sucesso!" -ForegroundColor Green
    Write-Host "Log salvo em: $PUBLISH\deploy.log" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Acesse seu site: https://seudominio.com.br" -ForegroundColor Cyan
} else {
    Write-Host ""
    Write-Host "ERRO no deploy. Verifique o log: $PUBLISH\deploy.log" -ForegroundColor Red
    exit 1
}
