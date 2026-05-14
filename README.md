# AlcatechFinanças — Sistema de Gestão Financeira Pessoal

Sistema web completo para controle de receitas, despesas, cartões de crédito e parcelamentos. Desenvolvido em PHP 8.2+ com MySQL, sem frameworks pesados.

---

## Requisitos

| Componente | Versão mínima |
|---|---|
| PHP | 8.1+ |
| MySQL | 8.0+ (ou MariaDB 10.6+) |
| Extensões PHP | `pdo`, `pdo_mysql`, `mbstring`, `json`, `session` |
| Servidor web | Apache (com `mod_rewrite`) ou Nginx |

---

## Instalação Local com XAMPP

### 1. Instalar o XAMPP

Baixe e instale o XAMPP em [apachefriends.org](https://www.apachefriends.org/). Versão recomendada: **8.2+**.

Inicie os módulos **Apache** e **MySQL** no painel de controle do XAMPP.

### 2. Copiar os Arquivos

Copie a pasta do projeto para dentro do diretório `htdocs` do XAMPP:

```
C:\xampp\htdocs\AlcatechFinancas\
```

A estrutura final deve ser:

```
C:\xampp\htdocs\AlcatechFinancas\
├── public\
│   ├── index.php
│   ├── .htaccess
│   └── assets\
├── app\
├── config\
├── core\
└── migrations\
```

### 3. Configurar o Virtual Host (recomendado)

Edite `C:\xampp\apache\conf\extra\httpd-vhosts.conf` e adicione:

```apache
<VirtualHost *:80>
    DocumentRoot "C:/xampp/htdocs/AlcatechFinancas/public"
    ServerName financas.local
    <Directory "C:/xampp/htdocs/AlcatechFinancas/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Adicione ao arquivo `C:\Windows\System32\drivers\etc\hosts`:

```
127.0.0.1   financas.local
```

Reinicie o Apache no painel do XAMPP.

> **Alternativa sem Virtual Host:** Acesse via `http://localhost/AlcatechFinancas/public/`. Neste caso, ajuste o `APP_URL` no arquivo `config/config.php`.

### 4. Criar o Banco de Dados

Acesse o phpMyAdmin em `http://localhost/phpmyadmin` e:

1. Clique em **Novo** na barra lateral
2. Nome do banco: `alcatech_financas`
3. Collation: `utf8mb4_unicode_ci`
4. Clique em **Criar**

Em seguida, selecione o banco recém-criado, clique na aba **SQL** e cole o conteúdo do arquivo:

```
migrations/001_schema_completo.sql
```

Clique em **Executar**. Todas as tabelas serão criadas automaticamente.

### 5. Configurar a Conexão com o Banco

Edite o arquivo `config/database.php`:

```php
// Configurações padrão para XAMPP local:
// DB_HOST = localhost
// DB_PORT = 3306
// DB_NAME = alcatech_financas
// DB_USER = root
// DB_PASS = (vazio por padrão no XAMPP)
```

As configurações são lidas via variáveis de ambiente (`getenv()`). Para o XAMPP, você pode definir as variáveis no `.env` ou editá-las diretamente no arquivo:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'alcatech_financas');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 6. Verificar o `.htaccess`

Certifique-se que o arquivo `public/.htaccess` existe com o conteúdo correto. O `mod_rewrite` deve estar habilitado no Apache.

Para verificar/habilitar o `mod_rewrite` no XAMPP:
- Abra `C:\xampp\apache\conf\httpd.conf`
- Localize a linha `#LoadModule rewrite_module modules/mod_rewrite.so`
- Remova o `#` do início
- Reinicie o Apache

### 7. Acessar o Sistema

Abra o navegador em `http://financas.local` (ou `http://localhost/AlcatechFinancas/public/`).

Você verá a tela de **Login**. Clique em **Criar conta** para registrar o primeiro usuário.

---

## Instalação Local com Laragon

O Laragon é mais simples para desenvolvimento local e já vem com `mod_rewrite` configurado.

### 1. Instalar o Laragon

Baixe em [laragon.org](https://laragon.org/download/). Recomendado: versão **Full** (inclui PHP 8.2, MySQL 8, Apache).

### 2. Copiar os Arquivos

Coloque a pasta do projeto em:

```
C:\laragon\www\AlcatechFinancas\
```

### 3. Criar Virtual Host Automático

O Laragon cria virtual hosts automaticamente. Após colocar a pasta em `www\`, reinicie o Laragon. O sistema ficará disponível em:

```
http://alcatechfinancas.test
```

### 4. Criar o Banco de Dados

Abra o **HeidiSQL** (incluído no Laragon) e:

1. Conecte ao servidor local (usuário: `root`, sem senha)
2. Clique com o botão direito em **Unnamed** → **Criar novo** → **Banco de dados**
3. Nome: `alcatech_financas`, Collation: `utf8mb4_unicode_ci`
4. Abra o arquivo `migrations/001_schema_completo.sql` e execute-o

### 5. Configurar o Banco

As configurações padrão do Laragon já funcionam com os valores padrão do `config/database.php` (host: `localhost`, user: `root`, pass: vazio).

### 6. Acessar

Abra `http://alcatechfinancas.test` no navegador.

---

## Instalação em Hospedagem Compartilhada (cPanel)

### 1. Fazer Upload dos Arquivos

Via **Gerenciador de Arquivos** do cPanel ou FTP (FileZilla), faça upload de **todos os arquivos** para a pasta do seu domínio ou subdomínio.

**Estrutura recomendada:**

Se o seu domínio aponta para `public_html/`, a estrutura deve ser:

```
public_html/          ← conteúdo da pasta public\ do projeto
    index.php
    .htaccess
    assets\
app\                  ← FORA do public_html (mais seguro)
config\
core\
migrations\
```

Para isso, faça upload de `app\`, `config\`, `core\` e `migrations\` **um nível acima** de `public_html`.

Depois ajuste os caminhos no `public/index.php`:

```php
// Altere o define de BASE_PATH para apontar para o diretório correto:
define('BASE_PATH', dirname(__DIR__, 1)); // ou o caminho absoluto
```

> Se não tiver controle sobre a estrutura de pastas, coloque tudo dentro de `public_html/` e certifique-se que a pasta `config/` tem `chmod 750` para impedir acesso direto.

### 2. Criar o Banco de Dados

No cPanel:
1. Vá em **Bancos de Dados MySQL**
2. Crie um novo banco (ex: `meuusuario_financas`)
3. Crie um usuário MySQL com senha forte
4. Adicione o usuário ao banco com **todos os privilégios**
5. Anote: nome do banco, usuário e senha

### 3. Importar o Schema

No cPanel, vá em **phpMyAdmin**:
1. Selecione o banco recém-criado
2. Clique na aba **Importar**
3. Selecione o arquivo `migrations/001_schema_completo.sql`
4. Clique em **Executar**

### 4. Configurar as Credenciais

Edite o arquivo `config/database.php` com os dados do seu banco:

```php
define('DB_HOST', 'localhost');        // geralmente localhost
define('DB_NAME', 'meuusuario_financas');
define('DB_USER', 'meuusuario_dbuser');
define('DB_PASS', 'SuaSenhaForte123!');
```

Ou utilize variáveis de ambiente via `SetEnv` no `.htaccess`:

```apache
SetEnv DB_HOST localhost
SetEnv DB_NAME meuusuario_financas
SetEnv DB_USER meuusuario_dbuser
SetEnv DB_PASS SuaSenhaForte123!
```

### 5. Verificar Permissões

```bash
chmod 755 public_html/
chmod 644 public_html/.htaccess
chmod 750 config/
chmod 640 config/database.php
```

### 6. Forçar HTTPS (recomendado)

Adicione ao `public/.htaccess`, antes das regras de rewrite:

```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## Configurações Adicionais

### Fuso Horário

Em `config/config.php`, ajuste o fuso conforme sua localização:

```php
date_default_timezone_set('America/Sao_Paulo');
```

### Modo de Ambiente

```php
define('APP_ENV', 'production'); // ou 'development'
```

Em `development`, erros PHP são exibidos na tela. Em `production`, são suprimidos (configure um log de erros no servidor).

### Session Secret

Para maior segurança das sessões, defina uma chave secreta única em `config/config.php`:

```php
define('SESSION_NAME', 'alcatech_sess_' . md5('seu_segredo_aqui'));
```

---

## Estrutura de Pastas

```
AlcatechFinancas/
│
├── public/                     # Document Root (único diretório público)
│   ├── index.php               # Front Controller
│   ├── .htaccess               # Rewrite rules + headers de segurança
│   └── assets/
│       ├── css/app.css         # Estilos (tema claro/escuro, responsivo)
│       └── js/app.js           # JavaScript (masks, modais, tema)
│
├── app/
│   ├── Controllers/            # Lógica de cada módulo
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── ReceitaController.php
│   │   ├── DespesaController.php
│   │   ├── DespesaFixaController.php
│   │   ├── CartaoController.php
│   │   ├── ParcelamentoController.php
│   │   └── CategoriaController.php
│   │
│   ├── Models/                 # Acesso ao banco de dados
│   │   ├── BaseModel.php
│   │   ├── UsuarioModel.php
│   │   ├── CategoriaModel.php
│   │   ├── ReceitaModel.php
│   │   ├── DespesaModel.php
│   │   ├── DespesaFixaModel.php
│   │   ├── CartaoModel.php
│   │   ├── ParcelamentoModel.php
│   │   └── PerfilModel.php
│   │
│   ├── Views/                  # Templates PHP (HTML)
│   │   ├── layouts/
│   │   │   ├── main.php        # Layout autenticado
│   │   │   └── auth.php        # Layout de login/cadastro
│   │   ├── auth/
│   │   ├── dashboard/
│   │   ├── receitas/
│   │   ├── despesas/
│   │   ├── cartoes/
│   │   ├── parcelamentos/
│   │   ├── categorias/
│   │   └── errors/
│   │
│   └── Helpers/
│       ├── FormatHelper.php    # e(), moeda(), dataFmt(), badgePago()...
│       ├── CsrfHelper.php      # Proteção CSRF
│       └── ValidationHelper.php
│
├── config/
│   ├── config.php              # Configurações gerais da aplicação
│   ├── database.php            # Credenciais do banco de dados
│   └── routes.php              # Mapa de rotas GET/POST
│
├── core/
│   ├── autoload.php            # Autoloader de classes
│   ├── Auth.php                # Gerenciamento de sessão e autenticação
│   ├── Database.php            # PDO Singleton
│   ├── Request.php             # Leitura segura de $_GET/$_POST
│   ├── Response.php            # Redirect, view(), JSON, flash messages
│   └── Router.php              # Roteador Front Controller
│
├── migrations/
│   └── 001_schema_completo.sql # Script completo do banco de dados
│
├── ARQUITETURA_SISTEMA.md      # Documentação técnica detalhada
└── README.md                   # Este arquivo
```

---

## Módulos do Sistema

| Módulo | URL | Descrição |
|---|---|---|
| Dashboard | `/` | Visão geral mensal com gráficos |
| Receitas | `/receitas` | Entradas financeiras |
| Despesas | `/despesas` | Saídas variáveis |
| Despesas Fixas | `/despesas-fixas` | Recorrências mensais |
| Cartões | `/cartoes` | Gestão de cartões de crédito |
| Parcelamentos | `/parcelamentos` | Compras parceladas |
| Categorias | `/categorias` | Gerenciar categorias |
| Perfil | `/perfil` | Dados do usuário e senha |

---

## Segurança Implementada

- **Senhas**: Argon2ID via `password_hash()` com rehash automático
- **CSRF**: Token por sessão validado com `hash_equals()` em todo POST
- **SQL Injection**: 100% prepared statements com PDO
- **XSS**: Função `e()` com `htmlspecialchars(ENT_QUOTES|ENT_HTML5)`
- **Sessão**: `session_regenerate_id(true)` no login, IP binding, cookie httponly
- **Isolamento**: Toda query filtra por `usuario_id` (multi-usuário seguro)

---

## Solução de Problemas

### "Página não encontrada" em todas as rotas

O `mod_rewrite` não está ativo. Verifique:
1. O arquivo `public/.htaccess` existe
2. `AllowOverride All` está configurado no Virtual Host
3. O módulo `mod_rewrite` está habilitado no Apache

### Erro de conexão com o banco

Verifique as credenciais em `config/database.php` e se o serviço MySQL está rodando.

### Erro 500 (Internal Server Error)

Habilite a exibição de erros temporariamente em `config/config.php`:

```php
define('APP_ENV', 'development');
```

Ou verifique o log de erros do Apache: `C:\xampp\apache\logs\error.log`.

### As despesas fixas não aparecem automaticamente

As despesas fixas são geradas automaticamente **na primeira visita ao dashboard do mês**. Se não aparecerem, acesse o dashboard e atualize a página.

### Parcelamentos não aparecem nas faturas do cartão

Verifique se o parcelamento foi criado com um cartão vinculado. As parcelas aparecem na fatura do mês correspondente à data de vencimento de cada parcela.

---

## Primeiro Acesso

1. Acesse o sistema no navegador
2. Clique em **Criar conta**
3. Preencha nome, e-mail e senha (mínimo 6 caracteres)
4. O sistema criará automaticamente as **12 categorias padrão**
5. Comece cadastrando seus **cartões de crédito** e **despesas fixas**
6. Explore o **Dashboard** para ver a visão geral financeira

---

*AlcatechFinanças — Controle financeiro pessoal simples e eficiente.*
