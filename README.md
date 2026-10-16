# AlcatechFinanças

Sistema web de **gestão financeira pessoal**: receitas, despesas, despesas fixas recorrentes, cartões de crédito com fatura, compras parceladas e relatórios. Feito em **PHP 8 puro (sem framework)** com MySQL, arquitetura MVC própria, e instalável no celular como **PWA**.

O projeto está em uso real em produção, com hospedagem compartilhada (Hostinger).

> 📸 _Adicione aqui capturas de tela do dashboard, da fatura do cartão e do chat no celular (ex.: `docs/img/dashboard.png`)._

---

## Funcionalidades

| Módulo | O que faz |
|---|---|
| **Dashboard** | Resumo do mês com saldo, gráficos por categoria e evolução (Chart.js) |
| **Receitas / Despesas** | CRUD com categoria, status de pagamento e vínculo opcional com cartão |
| **Despesas fixas** | Contas recorrentes geradas automaticamente a cada mês, com controle de pagamento |
| **Cartões de crédito** | Vários cartões, limite, dia de fechamento/vencimento e fatura mensal |
| **Parcelamentos** | Compra parcelada distribuída nos meses certos da fatura; bloqueio e quitação de parcelas |
| **Relatórios** | Visões anual, mensal e por período personalizado |
| **Lançamento por texto** | Widget de chat: digitar _"uber 25 ontem no nubank"_ ou _"tv 2400 em 10x"_ cria o lançamento já categorizado, após confirmação |
| **Console SQL (admin)** | Tela restrita a e-mails autorizados para rodar migrations e consultas salvas |
| **PWA** | Manifest + service worker; instalável no Android (inclusive empacotado como TWA) |

## Stack

- **Backend:** PHP 8.1+ (`strict_types`), PDO, sem dependências do Composer
- **Banco:** MySQL 8 / MariaDB 10.6, com migrations versionadas em [`migrations/`](migrations/)
- **Frontend:** HTML, CSS (custom properties, tema claro/escuro, responsivo) e JavaScript vanilla
- **Bibliotecas via CDN:** Chart.js, SweetAlert2
- **Deploy:** scripts PowerShell de build, export do banco, deploy por FTP e backup ([`publish/scripts/`](publish/scripts/))

## Arquitetura

Front Controller + MVC escrito do zero, para entender o que um framework faz por baixo:

```
public/index.php      → ponto de entrada único (carrega .env, config, autoload)
core/Router.php       → mapeia "MÉTODO /uri" → Controller@action (config/routes.php)
core/Request.php      → leitura tipada e sanitizada de GET/POST
core/Response.php     → views com layout, JSON, redirect, flash messages
core/Database.php     → PDO singleton
core/Auth.php         → sessão, login, "lembrar de mim"
app/Controllers/      → um controller por módulo
app/Models/           → acesso a dados (BaseModel + um model por tabela)
app/Services/         → NlpParser (chat) e SqlConsoleService
app/Views/            → templates PHP
```

Alguns pontos que deram mais trabalho:

- **Parcelamentos e fatura:** cada parcela cai na fatura certa conforme o dia de fechamento do cartão. Compras feitas a partir do dia de fechamento vão para o mês seguinte.
- **Recorrência mensal:** as despesas fixas viram lançamentos do mês de forma idempotente, sem duplicar se a página for recarregada.
- **Parser de linguagem natural** ([`NlpParser.php`](app/Services/NlpParser.php)): extrai tipo, valor, nº de parcelas, data relativa ("ontem", "dia 5"), cartão e categoria a partir de palavras-chave. Roda localmente, sem API externa. A primeira versão usava LLM (Groq) e foi substituída para cortar custo e latência.

A documentação técnica completa (modelagem do banco, regras de negócio, estratégias) está em [`docs/ARQUITETURA.md`](docs/ARQUITETURA.md).

## Segurança

- Senhas com **Argon2id** (`password_hash`) e rehash automático
- **CSRF** em todo POST, validado com `hash_equals`
- **100% prepared statements** (PDO com `EMULATE_PREPARES = false`)
- Escape de saída com `htmlspecialchars` (helper `e()`)
- `session_regenerate_id` no login, cookies `HttpOnly`/`SameSite`, `Secure` em produção
- "Lembrar de mim" com token aleatório guardado **só como hash** no banco e rotacionado a cada uso
- Isolamento multiusuário: toda consulta filtra por `usuario_id`
- Credenciais só via variáveis de ambiente (`.env` fora do controle de versão)

## Rodando localmente

Pré-requisitos: PHP 8.1+ (extensões `pdo_mysql`, `mbstring`) e MySQL 8.

```bash
git clone https://github.com/PedroWenner/financas.git
cd financas

cp .env.example .env          # ajuste DB_USER / DB_PASS

mysql -u root -p -e "CREATE DATABASE alcatech_financas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
for f in migrations/*.sql; do mysql -u root -p alcatech_financas < "$f"; done

php -S localhost:8000 -t public
```

Depois é só abrir `http://localhost:8000`, criar uma conta e as categorias padrão são geradas automaticamente.

Instalação com XAMPP/Laragon, hospedagem compartilhada e solução de problemas: [`docs/INSTALACAO.md`](docs/INSTALACAO.md).
Deploy na Hostinger: [`docs/DEPLOY_HOSTINGER.md`](docs/DEPLOY_HOSTINGER.md).

## Estrutura

```
app/          Controllers, Models, Services, Helpers e Views
config/       config.php, database.php, routes.php
core/         Router, Request, Response, Auth, Database, autoload
migrations/   scripts SQL versionados (001 → 006)
public/       document root: index.php, assets, manifest, service worker
publish/      templates de produção e scripts de build/deploy
docs/         arquitetura, instalação e deploy
```

---

Desenvolvido por **Pedro Wenner**.
