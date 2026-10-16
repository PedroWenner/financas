# AlcatechFinancas — Guia Completo de Deploy na Hostinger

> **Engenheiro DevOps:** Guia gerado automaticamente após análise completa da estrutura do projeto.  
> **Projeto:** PHP 8.1+ | MySQL 8.0+ | Apache | MVC sem framework | Sem Composer/npm  
> **Destino:** Hostinger (hospedagem compartilhada — hPanel)

---

## Sumário

1. [Análise do Projeto](#1-análise-do-projeto)
2. [Pré-requisitos na Hostinger](#2-pré-requisitos-na-hostinger)
3. [Escolha sua Estratégia de Deploy](#3-escolha-sua-estratégia-de-deploy)
4. [Passo a Passo — Opção A (Recomendada)](#4-passo-a-passo--opção-a-recomendada)
5. [Passo a Passo — Opção B (Sem SSH)](#5-passo-a-passo--opção-b-sem-ssh)
6. [Configurar Banco de Dados](#6-configurar-banco-de-dados)
7. [Configurar Domínio e SSL](#7-configurar-domínio-e-ssl)
8. [Executar o Build Automatizado](#8-executar-o-build-automatizado)
9. [Scripts Disponíveis](#9-scripts-disponíveis)
10. [Checklist de Deploy](#10-checklist-de-deploy)
11. [Troubleshooting](#11-troubleshooting)
12. [Rollback](#12-rollback)
13. [Manutenção](#13-manutenção)

---

## 1. Análise do Projeto

### Identificação

| Campo | Valor |
|---|---|
| **Linguagem** | PHP 8.1+ |
| **Framework** | Nenhum (PHP puro — MVC artesanal) |
| **Tipo** | Monolito Web (Server-Side Rendering) |
| **Padrão** | Front Controller + MVC |
| **Banco** | MySQL 8.0+ (InnoDB, utf8mb4) |
| **Dependências externas** | Nenhuma (sem Composer, sem npm) |
| **Assets** | CSS e JS estáticos (sem compilação) |
| **Docker** | Não utilizado |
| **Build necessário** | Não (PHP interpretado) — apenas preparação de arquivos |

### Estrutura do Projeto

```
AlcatechFinancas/
├── public/          ← Document Root (único diretório exposto ao navegador)
│   ├── index.php    ← Front Controller
│   ├── .htaccess    ← Rewrite rules
│   └── assets/      ← CSS, JS estáticos
├── app/             ← Controllers, Models, Views, Helpers
├── config/          ← Configurações (database, rotas, app)
├── core/            ← Router, Auth, Database, Request, Response
└── migrations/      ← Schema SQL
```

### Compatibilidade com Hostinger

| Recurso | Hostinger | Compatível? |
|---|---|---|
| PHP 8.1+ | ✓ Disponível (hPanel > PHP) | ✅ |
| MySQL 8.0 | ✓ Disponível | ✅ |
| Apache + mod_rewrite | ✓ Ativo por padrão | ✅ |
| SSL grátis | ✓ Let's Encrypt | ✅ |
| SSH | ✓ Business+ | ✅ |
| Composer | Não necessário | ✅ |
| Node.js | Não necessário | ✅ |

**Conclusão:** O projeto é totalmente compatível com hospedagem compartilhada da Hostinger, **sem adaptações de linguagem**.

---

## 2. Pré-requisitos na Hostinger

### No Painel (hPanel)

1. **Conta ativa** com domínio apontando para a Hostinger
2. **Plano mínimo:** Premium (com SSH) ou Business
3. **PHP configurado para 8.1+:**
   - hPanel → Avançado → PHP → Versão PHP → Selecionar **8.1** ou **8.2**
4. **Extensões PHP ativas** (todas padrão na Hostinger):
   - `pdo`, `pdo_mysql`, `mbstring`, `json`, `session`

### No Seu Computador

- PowerShell 5.1+ (já incluso no Windows 10/11)
- MySQL 8.0+ instalado (para os scripts de backup)
- WinSCP instalado *(opcional, apenas para deploy automático via FTP)*
  - Download: https://winscp.net

---

## 3. Escolha sua Estratégia de Deploy

### Opção A — Recomendada (SSH disponível)

```
Servidor Hostinger:
├── /public_html/          ← Document Root
│   ├── index.php          ← Front Controller adaptado
│   ├── .htaccess
│   └── assets/
└── /alcatech_app/         ← FORA do public_html (mais seguro)
    ├── app/
    ├── config/
    ├── core/
    └── migrations/
```

**Vantagem:** Arquivos PHP do app ficam inacessíveis via navegador.  
**Requer:** Criar a pasta `alcatech_app/` via SSH ou Gerenciador de Arquivos.

### Opção B — Sem SSH (planos básicos)

```
Servidor Hostinger:
└── /public_html/
    ├── index.php
    ├── .htaccess          ← bloqueia acesso às pastas do app
    ├── assets/
    ├── app/               ← protegido por .htaccess interno
    ├── config/            ← protegido
    ├── core/              ← protegido
    └── migrations/        ← protegido
```

**Vantagem:** Mais simples, não precisa de SSH.  
**Risco:** Pastas protegidas apenas por `.htaccess` — menor isolamento.

---

## 4. Passo a Passo — Opção A (Recomendada)

### Etapa 1: Preparar os Arquivos

**1.1** Edite `publish\config\database.producao.php` com as credenciais do banco:
```php
define('DB_NAME', 'u123456789_financas');  // seu banco na Hostinger
define('DB_USER', 'u123456789_user');       // seu usuário MySQL
define('DB_PASS', 'SuaSenhaSegura123!');    // sua senha MySQL
```

**1.2** Edite `publish\config\config.producao.php`:
```php
define('APP_URL', 'https://seudominio.com.br');  // seu domínio real
```

**1.3** Execute o script de build:
```powershell
cd C:\Programas\AlcatechFinancas\publish
.\scripts\01-build.ps1 -Opcao A
```

O build gera:
```
publish\build\
├── opcao-a\
│   ├── public_html\   ← enviar para /public_html/ na Hostinger
│   └── alcatech_app\  ← enviar para /alcatech_app/ na Hostinger
└── alcatech-deploy-opcaoA.zip
```

### Etapa 2: Criar a Pasta alcatech_app/ na Hostinger

**Via Gerenciador de Arquivos (hPanel):**
1. hPanel → Gerenciador de Arquivos
2. Navegue até `/` (raiz, um nível acima de `public_html`)
3. Clique em **+ Nova Pasta** → nome: `alcatech_app`

**Via SSH:**
```bash
ssh u123456789@servidor.hostinger.com
mkdir ~/alcatech_app
```

### Etapa 3: Enviar os Arquivos

**Método A1 — Via Gerenciador de Arquivos (sem FTP):**

1. hPanel → Gerenciador de Arquivos
2. Navegue até `/public_html/`
3. Clique em **Upload** → arraste o conteúdo de `build\opcao-a\public_html\`
4. Navegue até `/alcatech_app/`
5. Clique em **Upload** → arraste o conteúdo de `build\opcao-a\alcatech_app\`

**Método A2 — Via FTP (FileZilla):**

| Campo | Valor |
|---|---|
| Host | ftp.seudominio.com.br |
| Usuário | u123456789 (usuário FTP da Hostinger) |
| Senha | Sua senha FTP |
| Porta | 21 |

Painel esquerdo (local) → `build\opcao-a\public_html\`  
Painel direito (servidor) → `/public_html/`  
Arrastar e soltar.

Repetir para `alcatech_app\` → `/alcatech_app/`.

**Método A3 — Script automático (WinSCP):**
```powershell
# Configure as credenciais FTP em 03-deploy-ftp.ps1 primeiro
.\scripts\03-deploy-ftp.ps1 -Opcao A -ModoInicial
```

---

## 5. Passo a Passo — Opção B (Sem SSH)

### Etapa 1: Preparar os Arquivos

Mesmos passos 1.1 e 1.2 da Opção A, depois:

```powershell
.\scripts\01-build.ps1 -Opcao B
```

### Etapa 2: Enviar para public_html/

Envie o conteúdo completo de `build\opcao-b\public_html\` para `/public_html/` na Hostinger.

**Estrutura resultante em public_html/:**
```
public_html/
├── index.php
├── .htaccess          ← regras de bloqueio das pastas
├── assets/
├── app/
│   └── .htaccess      ← "Require all denied"
├── config/
│   └── .htaccess      ← "Require all denied"
├── core/
│   └── .htaccess      ← "Require all denied"
└── migrations/
    └── .htaccess      ← "Require all denied"
```

---

## 6. Configurar Banco de Dados

### 6.1 Criar o Banco na Hostinger

1. hPanel → **Bancos de Dados** → **MySQL**
2. Clique em **Criar banco de dados**
3. Preencha:
   - **Nome do banco:** `financas` (ficará `u123456789_financas`)
   - **Usuário:** `root` (ficará `u123456789_root`)
   - **Senha:** escolha uma senha forte
4. Marque **Conceder todos os privilégios**
5. Clique em **Criar**
6. **Anote** as credenciais geradas — você vai precisar delas

### 6.2 Importar o Schema

**Via phpMyAdmin:**
1. hPanel → **Bancos de Dados** → **phpMyAdmin**
2. Selecione o banco `u123456789_financas` na barra lateral
3. Clique na aba **SQL**
4. Abra o arquivo `publish\sql\schema_producao.sql` em um editor de texto
5. Copie todo o conteúdo e cole na caixa SQL
6. Clique em **Executar**

**Resultado esperado:** 8 tabelas + 2 views criadas sem erros.

**Via SSH (alternativa):**
```bash
mysql -h localhost -u u123456789_root -p u123456789_financas < schema_producao.sql
```

### 6.3 Verificar as Credenciais nos Arquivos

Confirme que `config/database.php` no servidor contém:
```php
define('DB_HOST', 'localhost');       // sempre localhost na Hostinger
define('DB_NAME', 'u123456789_financas');
define('DB_USER', 'u123456789_root');
define('DB_PASS', 'SuaSenhaSegura');
```

---

## 7. Configurar Domínio e SSL

### 7.1 Apontar o Domínio

Se o domínio foi comprado na Hostinger: **já está configurado**.

Se o domínio é externo (Registro.br, GoDaddy, etc.):
1. Anote o IP do servidor: hPanel → **Conta** → **Detalhes**
2. No painel do seu registrador, crie um registro **A**:
   - Nome: `@` (ou `seudominio.com.br`)
   - IP: IP da Hostinger
   - TTL: 3600
3. Para `www`: crie outro registro A com o mesmo IP

**Propagação:** 1 a 48 horas.

### 7.2 Ativar SSL (HTTPS)

1. hPanel → **SSL** → **SSL Gratuito (Let's Encrypt)**
2. Selecione seu domínio
3. Clique em **Instalar**
4. Aguarde 5-10 minutos

### 7.3 Forçar HTTPS

Já está configurado no `htaccess.producao` que você enviou:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## 8. Executar o Build Automatizado

### Sequência Completa de Deploy

```powershell
# 1. Edite as configs de produção (faça isso UMA VEZ)
notepad publish\config\database.producao.php
notepad publish\config\config.producao.php

# 2. Gere o build
cd C:\Programas\AlcatechFinancas\publish
.\scripts\01-build.ps1 -Opcao A

# 3. Exporte o banco local (para migração inicial)
.\scripts\02-exportar-banco.ps1

# 4. Envie os arquivos (FTP automático com WinSCP)
#    OU faça o upload manual pelo hPanel
.\scripts\03-deploy-ftp.ps1 -Opcao A -ModoInicial

# 5. Para atualizacoes futuras (só envia mudancas):
.\scripts\01-build.ps1 -Opcao A
.\scripts\03-deploy-ftp.ps1 -Opcao A
```

---

## 9. Scripts Disponíveis

| Script | Uso | Descrição |
|---|---|---|
| `01-build.ps1` | `.\scripts\01-build.ps1 -Opcao A` | Prepara arquivos para deploy |
| `01-build.ps1` | `.\scripts\01-build.ps1 -Opcao B` | Prepara para deploy sem SSH |
| `02-exportar-banco.ps1` | `.\scripts\02-exportar-banco.ps1` | Exporta banco local completo |
| `02-exportar-banco.ps1` | `.\scripts\02-exportar-banco.ps1 -SomenteEstrutura` | Só estrutura (sem dados) |
| `03-deploy-ftp.ps1` | `.\scripts\03-deploy-ftp.ps1 -Opcao A -ModoInicial` | Primeiro deploy via FTP |
| `03-deploy-ftp.ps1` | `.\scripts\03-deploy-ftp.ps1 -Opcao A` | Atualização (só mudanças) |
| `04-backup.ps1` | `.\scripts\04-backup.ps1` | Backup do banco de produção |
| `04-backup.ps1` | `.\scripts\04-backup.ps1 -Restaurar "arquivo.sql"` | Restaura backup |

### Arquivos de Configuração

| Arquivo | Edite antes do build | Descrição |
|---|---|---|
| `config\database.producao.php` | ✅ Obrigatório | Credenciais do banco na Hostinger |
| `config\config.producao.php` | ✅ Obrigatório | APP_URL com seu domínio |
| `config\htaccess.producao` | Opcional | Ajuste o redirect www se necessário |
| `scripts\03-deploy-ftp.ps1` | Se usar FTP automático | Credenciais FTP |
| `scripts\04-backup.ps1` | Se usar SSH | Credenciais SSH e banco de produção |

---

## 10. Checklist de Deploy

### Antes do Build

- [ ] Editado `config\database.producao.php` com credenciais da Hostinger
- [ ] Editado `config\config.producao.php` com URL real (`https://seudominio.com.br`)
- [ ] PHP 8.1+ selecionado no hPanel
- [ ] Banco de dados criado no hPanel
- [ ] Schema importado via phpMyAdmin

### Upload de Arquivos

- [ ] Pasta `alcatech_app/` criada na raiz do servidor (Opção A)
- [ ] Conteúdo de `build\opcao-a\public_html\` enviado para `/public_html/`
- [ ] Conteúdo de `build\opcao-a\alcatech_app\` enviado para `/alcatech_app/`
- [ ] Arquivo `.htaccess` enviado (verificar que não foi ignorado pelo FTP)

### Configurações

- [ ] SSL ativado no hPanel
- [ ] HTTPS funcionando (`https://seudominio.com.br`)
- [ ] Redirecionamento HTTP → HTTPS ativo

### Testes

- [ ] Página de login carrega: `https://seudominio.com.br/login`
- [ ] Cadastro de usuário funciona
- [ ] Login funciona
- [ ] Dashboard carrega
- [ ] Criar receita funciona
- [ ] Criar despesa funciona
- [ ] Criar despesa fixa funciona
- [ ] Nenhum erro 500 ou PHP warning visível

---

## 11. Troubleshooting

### Erro 500 — Internal Server Error

**Causa mais comum:** mod_rewrite não ativo ou `.htaccess` com erro.

**Verificação:**
1. hPanel → Avançado → PHP → Error Log
2. Procure a linha com o erro

**Soluções:**
- Confirme que o `.htaccess` foi enviado (FTP às vezes ignora arquivos ocultos)
- Verifique se o `mod_rewrite` está ativo: hPanel → Avançado → .htaccess

---

### Página em branco (sem erro visível)

**Causa:** `APP_ENV` ainda como `development` ou erro PHP sem exibição.

**Solução temporária:** Mude `config/config.php` no servidor:
```php
define('APP_ENV', 'development');  // só para debug, reverter depois!
```

---

### "Erro de conexão com o banco"

**Causas possíveis:**
1. Credenciais erradas em `config/database.php`
2. Nome do banco incorreto (deve ter prefixo `u123456789_`)
3. MySQL não é `localhost` (verifique no hPanel)

**Verificação:** hPanel → Bancos de Dados → veja o host exato listado.

---

### ".htaccess não funciona" / Rotas não funcionam

**Causa:** `AllowOverride All` não está configurado (raro na Hostinger).

**Solução:**
- A Hostinger tem `AllowOverride All` por padrão no `public_html`
- Se não funcionar: hPanel → Suporte → abra um ticket

---

### Arquivos de assets não carregam (CSS/JS)

**Causa:** Caminho errado nos assets.

**Verificação:** O layout usa `/assets/css/app.css` (caminho absoluto).  
Com Virtual Host configurado, `/assets/` resolve para `public_html/assets/`.

**Solução:** Confirme que a pasta `public_html/assets/` foi enviada completa.

---

### FTP não enviou o .htaccess

**Causa:** FileZilla e outros FTP clientes às vezes ignoram arquivos `.` (ocultos).

**Solução no FileZilla:**
Editar → Configurações → Transferências → FTP → Marcar "Transferir arquivos ocultos"

---

### Erro de sessão / "IP binding"

**Causa:** O `Auth.php` valida o IP da sessão. Proxies ou redes com IP dinâmico podem causar logout automático.

**Solução:** Desabilite temporariamente o IP binding em `core/Auth.php`:
```php
// Comente estas linhas para desabilitar:
// $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
// if (!empty($_SESSION['login_ip']) && $_SESSION['login_ip'] !== $currentIp) {
//     self::logout();
//     return false;
// }
```

---

## 12. Rollback

### Rollback de Código

1. Mantenha sempre um ZIP do deploy anterior em `publish\build\`
2. Para reverter: envie o ZIP anterior via FTP

```powershell
# Via script (requer WinSCP)
.\scripts\03-deploy-ftp.ps1 -Opcao A -ModoInicial
# (apontando para o build anterior)
```

### Rollback do Banco de Dados

```powershell
# Restaurar backup via SSH
.\scripts\04-backup.ps1 -Restaurar "prod_backup_20260514_120000.sql"
```

**Via phpMyAdmin:**
1. hPanel → Bancos de Dados → phpMyAdmin
2. Selecione o banco → aba **Importar**
3. Selecione o arquivo `.sql` de backup

**⚠ ATENÇÃO:** O restore apaga todos os dados atuais. Faça um backup antes.

---

## 13. Manutenção

### Fazer Backup Regular (Semanal)

```powershell
.\scripts\04-backup.ps1
```
Backups ficam em `publish\sql\backups-producao\`.

### Atualizar o Sistema (Nova Versão)

```powershell
# 1. Fazer backup antes
.\scripts\04-backup.ps1

# 2. Gerar novo build
.\scripts\01-build.ps1 -Opcao A

# 3. Deploy (sincroniza só o que mudou)
.\scripts\03-deploy-ftp.ps1 -Opcao A
```

### Monitorar Erros PHP

hPanel → **Avançado** → **PHP** → **Error Logs**

Ou via SSH:
```bash
tail -f ~/logs/error.log
```

### Renovação de SSL

A Hostinger renova o certificado Let's Encrypt automaticamente a cada 90 dias.

---

## Estrutura de Arquivos Gerados pelo Build

```
publish\
├── build\
│   ├── opcao-a\                   ← Para deploy com SSH
│   │   ├── public_html\           → /public_html/ na Hostinger
│   │   │   ├── index.php          (front controller adaptado)
│   │   │   ├── .htaccess          (produção com HTTPS)
│   │   │   └── assets\
│   │   └── alcatech_app\          → /alcatech_app/ na Hostinger
│   │       ├── app\
│   │       ├── config\
│   │       │   ├── config.php     (APP_ENV=production)
│   │       │   ├── database.php   (credenciais produção)
│   │       │   └── routes.php
│   │       ├── core\
│   │       └── migrations\
│   ├── opcao-b\                   ← Para deploy sem SSH
│   │   └── public_html\           → /public_html/ na Hostinger
│   │       ├── index.php
│   │       ├── .htaccess
│   │       ├── assets\
│   │       ├── app\  + .htaccess
│   │       ├── config\ + .htaccess
│   │       ├── core\ + .htaccess
│   │       └── migrations\ + .htaccess
│   ├── alcatech-deploy-opcaoA.zip
│   └── alcatech-deploy-opcaoB.zip
│
├── scripts\
│   ├── 01-build.ps1
│   ├── 02-exportar-banco.ps1
│   ├── 03-deploy-ftp.ps1
│   └── 04-backup.ps1
│
├── sql\
│   ├── schema_producao.sql        ← Importar no phpMyAdmin
│   └── backups-producao\          ← Backups gerados pelo script 04
│
├── config\
│   ├── config.producao.php        ← Template config de produção
│   ├── database.producao.php      ← Template DB de produção ← EDITE AQUI
│   ├── htaccess.producao          ← .htaccess otimizado
│   ├── index.opcao-a.php          ← index.php para Opção A
│   └── index.opcao-b.php          ← index.php para Opção B
│
(este guia fica em docs/DEPLOY_HOSTINGER.md)
```

---

## Pendências para Publicação Definitiva

Antes de considerar o deploy concluído, verifique:

| Pendência | Ação necessária |
|---|---|
| **Credenciais de banco** | Editar `config\database.producao.php` com dados reais da Hostinger |
| **URL do domínio** | Editar `config\config.producao.php` com seu domínio |
| **Credenciais FTP** | Editar `scripts\03-deploy-ftp.ps1` se usar deploy automático |
| **Credenciais SSH** | Editar `scripts\04-backup.ps1` com dados SSH da Hostinger |
| **Domínio apontado** | Registros DNS configurados no registrador |
| **SSL ativado** | Certificado Let's Encrypt instalado no hPanel |
| **Banco importado** | `schema_producao.sql` executado no phpMyAdmin |
| **Teste completo** | Todos os módulos testados em produção |
| **APP_ENV=production** | Confirmado no `config.php` enviado ao servidor |
| **Modo debug desativado** | `display_errors = Off` em produção |

---

*Gerado automaticamente em 14/05/2026 — AlcatechFinancas DevOps Guide*
