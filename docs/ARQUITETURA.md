# AlcatechFinanças — Documentação Técnica Completa
**Sistema de Gestão Financeira Pessoal**
**Versão:** 1.0 | **Data:** Maio 2026 | **Arquiteto:** Pedro Wenner

---

## Índice

1. [Visão Geral do Projeto](#1-visão-geral-do-projeto)
2. [Arquitetura do Sistema](#2-arquitetura-do-sistema)
3. [Estrutura de Pastas](#3-estrutura-de-pastas)
4. [Modelagem do Banco de Dados](#4-modelagem-do-banco-de-dados)
5. [Scripts SQL Completos](#5-scripts-sql-completos)
6. [Regras de Negócio](#6-regras-de-negócio)
7. [Estratégia de Parcelamentos](#7-estratégia-de-parcelamentos)
8. [Estratégia de Recorrência Mensal](#8-estratégia-de-recorrência-mensal)
9. [Estratégia de Segurança](#9-estratégia-de-segurança)
10. [Arquitetura do Frontend](#10-arquitetura-do-frontend)
11. [Fluxo das Telas](#11-fluxo-das-telas)
12. [Roadmap de Desenvolvimento](#12-roadmap-de-desenvolvimento)
13. [Extras e Sugestões Futuras](#13-extras-e-sugestões-futuras)

---

## 1. Visão Geral do Projeto

### 1.1 Objetivo

O **AlcatechFinanças** é um sistema web de gestão financeira pessoal projetado para substituir planilhas Excel no controle de receitas, despesas, cartões de crédito e parcelamentos. O sistema é acessível via desktop e dispositivos móveis, com autenticação segura e dashboard analítico interativo.

### 1.2 Escopo Funcional

| Módulo | Descrição |
|---|---|
| Autenticação | Login, cadastro e proteção de rotas por sessão |
| Receitas | Controle de salários, rendas extras e outras entradas |
| Despesas Fixas | Contas recorrentes com geração automática de lançamentos |
| Despesas Variáveis | Gastos avulsos com ou sem cartão de crédito |
| Parcelamentos | Compras parceladas com distribuição automática por mês |
| Cartões de Crédito | Múltiplos cartões com limites, faturas e vencimentos |
| Categorias | Classificação personalizável de receitas e despesas |
| Dashboard | Painel visual com gráficos, resumos e indicadores |

### 1.3 Stack Tecnológico

| Camada | Tecnologia | Justificativa |
|---|---|---|
| Backend | PHP 8.2+ | Requisito do projeto; amplamente suportado em hospedagens |
| Banco de Dados | MySQL 8.0+ | Robusto, gratuito, excelente para dados relacionais |
| Frontend | HTML5 + CSS3 + JavaScript vanilla | Leveza, sem dependência de frameworks complexos |
| Gráficos | Chart.js | Biblioteca leve, interativa, sem backend necessário |
| Estilo | CSS Custom Properties + Flexbox/Grid | Responsividade nativa sem frameworks CSS pesados |
| Ícones | Lucide Icons (via CDN) | Ícones modernos e consistentes |
| Sessão | PHP Sessions + CSRF tokens | Segurança robusta sem overhead |

---

## 2. Arquitetura do Sistema

### 2.1 Padrão Arquitetural: MVC Simplificado

O sistema segue o padrão **MVC (Model-View-Controller)** adaptado para PHP puro, sem framework, com roteamento manual via `index.php` (Front Controller Pattern).

```
Requisição HTTP
       │
       ▼
  index.php (Front Controller)
       │
       ▼
  Router.php (Identifica rota e controller)
       │
       ▼
  Controller (Lógica de negócio + chamada ao Model)
       │
   ┌───┴───┐
   ▼       ▼
Model    View
(DB)   (HTML/Template)
```

### 2.2 Camadas da Aplicação

**Camada de Apresentação (View)**
- Arquivos `.php` na pasta `views/`
- Templates HTML com dados injetados via variáveis PHP
- Assets estáticos (CSS, JS, imagens) em `public/`

**Camada de Controle (Controller)**
- Recebe a requisição, valida dados, orquestra Models
- Retorna dados para a View ou redireciona
- Nunca acessa o banco diretamente — usa Models

**Camada de Modelo (Model)**
- Encapsula toda a lógica de acesso ao banco
- Usa PDO com prepared statements
- Retorna arrays ou objetos para o Controller

**Camada de Infraestrutura**
- `config/` → configurações de ambiente
- `core/` → classes base (Database, Router, Auth, etc.)
- `helpers/` → funções utilitárias reutilizáveis

### 2.3 Fluxo de uma Requisição

```
1. Usuário acessa /despesas/criar
2. index.php carrega o Router
3. Router identifica: Controller=DespesaController, Action=criar
4. Router verifica autenticação (Auth::check())
5. DespesaController::criar() é executado
6. Controller chama CartaoModel::listar() e CategoriaModel::listar()
7. Controller envia dados para views/despesas/criar.php
8. View renderiza o HTML com os dados
9. Resposta é enviada ao navegador
```

---

## 3. Estrutura de Pastas

```
alcatech-financas/
│
├── public/                          # Único diretório público (Document Root)
│   ├── index.php                    # Front Controller — ponto de entrada
│   ├── .htaccess                    # Rewrite rules para URLs amigáveis
│   ├── assets/
│   │   ├── css/
│   │   │   ├── app.css              # Estilos globais e variáveis CSS
│   │   │   ├── dashboard.css        # Estilos do dashboard
│   │   │   ├── components.css       # Cards, botões, formulários
│   │   │   └── dark-mode.css        # Tema escuro
│   │   ├── js/
│   │   │   ├── app.js               # JavaScript global
│   │   │   ├── dashboard.js         # Lógica dos gráficos do dashboard
│   │   │   ├── masks.js             # Máscaras de input (dinheiro, data)
│   │   │   └── components.js        # Componentes interativos (modals, etc.)
│   │   └── img/
│   │       └── logo.svg
│
├── app/                             # Código da aplicação (fora do Document Root)
│   ├── Controllers/
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── ReceitaController.php
│   │   ├── DespesaController.php
│   │   ├── CartaoController.php
│   │   ├── ParcelamentoController.php
│   │   └── CategoriaController.php
│   │
│   ├── Models/
│   │   ├── BaseModel.php            # Classe base com métodos CRUD comuns
│   │   ├── UsuarioModel.php
│   │   ├── ReceitaModel.php
│   │   ├── DespesaModel.php
│   │   ├── DespesaFixaModel.php
│   │   ├── CartaoModel.php
│   │   ├── ParcelamentoModel.php
│   │   ├── ParcelaModel.php
│   │   └── CategoriaModel.php
│   │
│   ├── Views/
│   │   ├── layouts/
│   │   │   ├── main.php             # Layout principal (sidebar + header)
│   │   │   └── auth.php             # Layout de autenticação
│   │   ├── auth/
│   │   │   ├── login.php
│   │   │   └── cadastro.php
│   │   ├── dashboard/
│   │   │   └── index.php
│   │   ├── receitas/
│   │   │   ├── index.php            # Listagem
│   │   │   ├── criar.php            # Formulário de criação
│   │   │   └── editar.php           # Formulário de edição
│   │   ├── despesas/
│   │   │   ├── index.php
│   │   │   ├── criar.php
│   │   │   └── editar.php
│   │   ├── cartoes/
│   │   │   ├── index.php
│   │   │   ├── criar.php
│   │   │   ├── editar.php
│   │   │   └── fatura.php           # Visualização da fatura
│   │   ├── parcelamentos/
│   │   │   ├── index.php
│   │   │   └── detalhe.php
│   │   └── categorias/
│   │       └── index.php
│   │
│   └── Helpers/
│       ├── FormatHelper.php         # Formatação de moeda, data, etc.
│       ├── ValidationHelper.php     # Validação de formulários
│       └── CsrfHelper.php          # Proteção CSRF
│
├── core/                            # Classes do núcleo do sistema
│   ├── Database.php                 # Singleton PDO connection
│   ├── Router.php                   # Roteador de URLs
│   ├── Auth.php                     # Gerenciamento de sessão/autenticação
│   ├── Request.php                  # Encapsula dados da requisição HTTP
│   └── Response.php                 # Helpers para resposta (redirect, json)
│
├── config/
│   ├── config.php                   # Constantes globais do sistema
│   ├── database.php                 # Credenciais do banco (não versionar)
│   └── routes.php                   # Mapa de rotas da aplicação
│
├── migrations/                      # Scripts SQL versionados
│   ├── 001_create_usuarios.sql
│   ├── 002_create_categorias.sql
│   ├── 003_create_cartoes.sql
│   ├── 004_create_receitas.sql
│   ├── 005_create_despesas.sql
│   ├── 006_create_despesas_fixas.sql
│   ├── 007_create_parcelamentos.sql
│   └── 008_create_parcelas.sql
│
├── .env.example                     # Exemplo de variáveis de ambiente
├── .gitignore                       # Ignora .env, vendor/, etc.
└── README.md
```

### 3.1 Configuração do .htaccess

```apache
# public/.htaccess
Options -Indexes
RewriteEngine On

# Redireciona tudo para index.php, exceto arquivos e pastas existentes
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Cabeçalhos de segurança
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
```

---

## 4. Modelagem do Banco de Dados

### 4.1 Diagrama Entidade-Relacionamento (DER)

```
┌─────────────┐     ┌──────────────────┐     ┌──────────────┐
│  usuarios   │─────│    receitas      │     │  categorias  │
│─────────────│ 1:N │──────────────────│     │──────────────│
│ id          │     │ id               │     │ id           │
│ nome        │     │ usuario_id (FK)  │─────│ usuario_id   │
│ email       │     │ categoria_id (FK)│ N:1 │ nome         │
│ senha_hash  │     │ descricao        │     │ tipo         │
│ created_at  │     │ valor            │     │ icone        │
└─────────────┘     │ data             │     │ cor          │
       │            │ tipo_receita     │     └──────────────┘
       │            │ observacoes      │            │
       │            └──────────────────┘            │
       │                                            │
       │ 1:N        ┌──────────────────┐            │
       │────────────│    despesas      │────────────┘
       │            │──────────────────│ N:1
       │            │ id               │
       │            │ usuario_id (FK)  │
       │            │ categoria_id (FK)│
       │            │ cartao_id (FK)   │──────┐
       │            │ parcelamento_id  │      │ N:1
       │            │ descricao        │      │
       │            │ valor            │      ▼
       │            │ data             │ ┌────────────────┐
       │            │ tipo             │ │    cartoes     │
       │            │ forma_pagamento  │ │────────────────│
       │            │ numero_parcela   │ │ id             │
       │            │ total_parcelas   │ │ usuario_id(FK) │
       │            │ pago             │ │ nome           │
       │            └──────────────────┘ │ limite         │
       │                                 │ dia_fechamento │
       │ 1:N        ┌──────────────────┐ │ dia_vencimento │
       └────────────│ despesas_fixas   │ │ bandeira       │
                    │──────────────────│ │ cor            │
                    │ id               │ └────────────────┘
                    │ usuario_id (FK)  │
                    │ categoria_id (FK)│
                    │ descricao        │
                    │ valor            │
                    │ dia_vencimento   │
                    │ data_inicio      │
                    │ data_fim         │
                    │ ativo            │
                    └──────────────────┘

┌─────────────────────┐     ┌────────────────┐
│   parcelamentos     │─────│    parcelas    │
│─────────────────────│ 1:N │────────────────│
│ id                  │     │ id             │
│ usuario_id (FK)     │     │ parcelamento_id│
│ cartao_id (FK)      │     │ numero_parcela │
│ categoria_id (FK)   │     │ valor          │
│ descricao           │     │ data_vencimento│
│ valor_total         │     │ pago           │
│ numero_parcelas     │     │ data_pagamento │
│ valor_parcela       │     └────────────────┘
│ data_compra         │
│ data_primeira_parc  │
└─────────────────────┘
```

### 4.2 Descrição das Entidades

**usuarios** — Armazena os dados de autenticação de cada usuário do sistema.

**categorias** — Classificações personalizáveis por usuário. O campo `tipo` indica se a categoria se aplica a receitas, despesas ou ambos.

**cartoes** — Cartões de crédito cadastrados. `dia_fechamento` e `dia_vencimento` são usados para calcular a qual fatura pertence cada despesa.

**receitas** — Entradas financeiras de qualquer tipo (salário, renda extra, etc.).

**despesas** — Saídas financeiras avulsas. Pode estar vinculada a um cartão ou ser pagamento direto. Se pertencer a um parcelamento, guarda referência ao parcelamento pai.

**despesas_fixas** — Template de despesa recorrente. Define o padrão mensal; os lançamentos reais são gerados automaticamente na tabela `despesas`.

**parcelamentos** — Cabeçalho de uma compra parcelada. Armazena os dados gerais da compra.

**parcelas** — As parcelas individuais de um parcelamento, com data de vencimento e status de pagamento independente.

---

## 5. Scripts SQL Completos

### 5.1 Criação do Banco e Tabelas

```sql
-- ============================================================
-- AlcatechFinanças — Script de Criação do Banco de Dados
-- Versão: 1.0 | MySQL 8.0+
-- ============================================================

CREATE DATABASE IF NOT EXISTS alcatech_financas
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE alcatech_financas;

-- ============================================================
-- TABELA: usuarios
-- ============================================================
CREATE TABLE usuarios (
    id            INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    nome          VARCHAR(100)    NOT NULL,
    email         VARCHAR(150)    NOT NULL,
    senha_hash    VARCHAR(255)    NOT NULL,
    ativo         TINYINT(1)      NOT NULL DEFAULT 1,
    ultimo_login  DATETIME            NULL,
    created_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                   ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    UNIQUE KEY uq_email (email),
    INDEX idx_email_ativo (email, ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: categorias
-- ============================================================
CREATE TABLE categorias (
    id          INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    usuario_id  INT UNSIGNED    NOT NULL,
    nome        VARCHAR(80)     NOT NULL,
    tipo        ENUM('receita','despesa','ambos')
                                NOT NULL DEFAULT 'ambos',
    icone       VARCHAR(50)         NULL COMMENT 'Nome do ícone Lucide',
    cor         VARCHAR(7)          NULL COMMENT 'Hex color, ex: #3498db',
    ativo       TINYINT(1)      NOT NULL DEFAULT 1,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_cat_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_cat_usuario_tipo (usuario_id, tipo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: cartoes
-- ============================================================
CREATE TABLE cartoes (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    usuario_id      INT UNSIGNED    NOT NULL,
    nome            VARCHAR(80)     NOT NULL,
    bandeira        ENUM('visa','mastercard','elo','amex',
                         'hipercard','outro')
                                    NOT NULL DEFAULT 'outro',
    limite          DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    dia_fechamento  TINYINT         NOT NULL COMMENT '1-31',
    dia_vencimento  TINYINT         NOT NULL COMMENT '1-31',
    cor             VARCHAR(7)          NULL COMMENT 'Hex color',
    ativo           TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                     ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_cartao_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_cartao_usuario (usuario_id, ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: receitas
-- ============================================================
CREATE TABLE receitas (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    usuario_id      INT UNSIGNED    NOT NULL,
    categoria_id    INT UNSIGNED        NULL,
    descricao       VARCHAR(200)    NOT NULL,
    valor           DECIMAL(12,2)   NOT NULL,
    data            DATE            NOT NULL,
    tipo_receita    ENUM('salario','salario_conjuge',
                         'renda_extra','investimento','outro')
                                    NOT NULL DEFAULT 'outro',
    observacoes     TEXT                NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                     ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_rec_usuario  FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_rec_categoria FOREIGN KEY (categoria_id)
        REFERENCES categorias(id) ON SET NULL,
    INDEX idx_rec_usuario_data (usuario_id, data),
    INDEX idx_rec_mes_ano (usuario_id, YEAR(data), MONTH(data))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: despesas_fixas  (templates de recorrência)
-- ============================================================
CREATE TABLE despesas_fixas (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    usuario_id      INT UNSIGNED    NOT NULL,
    categoria_id    INT UNSIGNED        NULL,
    descricao       VARCHAR(200)    NOT NULL,
    valor           DECIMAL(12,2)   NOT NULL,
    dia_vencimento  TINYINT         NOT NULL COMMENT 'Dia do mês de vencimento',
    data_inicio     DATE            NOT NULL,
    data_fim        DATE                NULL COMMENT 'NULL = sem data de fim',
    ativo           TINYINT(1)      NOT NULL DEFAULT 1,
    observacoes     TEXT                NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                     ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_df_usuario   FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_df_categoria FOREIGN KEY (categoria_id)
        REFERENCES categorias(id) ON SET NULL,
    INDEX idx_df_usuario_ativo (usuario_id, ativo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: parcelamentos  (cabeçalho da compra parcelada)
-- ============================================================
CREATE TABLE parcelamentos (
    id                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    usuario_id            INT UNSIGNED    NOT NULL,
    cartao_id             INT UNSIGNED        NULL,
    categoria_id          INT UNSIGNED        NULL,
    descricao             VARCHAR(200)    NOT NULL,
    valor_total           DECIMAL(12,2)   NOT NULL,
    numero_parcelas       TINYINT         NOT NULL,
    valor_parcela         DECIMAL(12,2)   NOT NULL,
    data_compra           DATE            NOT NULL,
    data_primeira_parcela DATE            NOT NULL,
    observacoes           TEXT                NULL,
    created_at            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at            DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                           ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_parc_usuario   FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_parc_cartao    FOREIGN KEY (cartao_id)
        REFERENCES cartoes(id) ON SET NULL,
    CONSTRAINT fk_parc_categoria FOREIGN KEY (categoria_id)
        REFERENCES categorias(id) ON SET NULL,
    INDEX idx_parc_usuario (usuario_id),
    INDEX idx_parc_cartao  (cartao_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: parcelas  (lançamentos individuais do parcelamento)
-- ============================================================
CREATE TABLE parcelas (
    id                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    parcelamento_id   INT UNSIGNED    NOT NULL,
    numero_parcela    TINYINT         NOT NULL COMMENT 'Ex: 1, 2, 3',
    valor             DECIMAL(12,2)   NOT NULL,
    data_vencimento   DATE            NOT NULL,
    pago              TINYINT(1)      NOT NULL DEFAULT 0,
    data_pagamento    DATE                NULL,
    created_at        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_parcela_parcelamento FOREIGN KEY (parcelamento_id)
        REFERENCES parcelamentos(id) ON DELETE CASCADE,
    INDEX idx_parcela_vencimento (parcelamento_id, data_vencimento),
    INDEX idx_parcela_pago       (parcelamento_id, pago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABELA: despesas  (todos os lançamentos de saída)
-- ============================================================
CREATE TABLE despesas (
    id                INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    usuario_id        INT UNSIGNED    NOT NULL,
    categoria_id      INT UNSIGNED        NULL,
    cartao_id         INT UNSIGNED        NULL,
    parcela_id        INT UNSIGNED        NULL COMMENT 'FK para parcela, se parcelado',
    despesa_fixa_id   INT UNSIGNED        NULL COMMENT 'FK para template, se recorrente',
    descricao         VARCHAR(200)    NOT NULL,
    valor             DECIMAL(12,2)   NOT NULL,
    data              DATE            NOT NULL,
    tipo              ENUM('fixa','variavel','parcelada')
                                      NOT NULL DEFAULT 'variavel',
    forma_pagamento   ENUM('dinheiro','debito','credito',
                           'pix','ted','boleto','outro')
                                      NOT NULL DEFAULT 'outro',
    pago              TINYINT(1)      NOT NULL DEFAULT 0,
    data_pagamento    DATE                NULL,
    observacoes       TEXT                NULL,
    created_at        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                       ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (id),
    CONSTRAINT fk_desp_usuario     FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_desp_categoria   FOREIGN KEY (categoria_id)
        REFERENCES categorias(id) ON SET NULL,
    CONSTRAINT fk_desp_cartao      FOREIGN KEY (cartao_id)
        REFERENCES cartoes(id) ON SET NULL,
    CONSTRAINT fk_desp_parcela     FOREIGN KEY (parcela_id)
        REFERENCES parcelas(id) ON SET NULL,
    CONSTRAINT fk_desp_fixa        FOREIGN KEY (despesa_fixa_id)
        REFERENCES despesas_fixas(id) ON SET NULL,

    INDEX idx_desp_usuario_data   (usuario_id, data),
    INDEX idx_desp_mes_ano        (usuario_id, YEAR(data), MONTH(data)),
    INDEX idx_desp_cartao_data    (cartao_id, data),
    INDEX idx_desp_tipo           (usuario_id, tipo),
    INDEX idx_desp_pago           (usuario_id, pago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DADOS INICIAIS: categorias padrão (inseridas no cadastro)
-- ============================================================
-- (Executar após criação do primeiro usuário, adaptando o usuario_id)

-- Exemplo de procedure para inserir categorias padrão:
DELIMITER $$
CREATE PROCEDURE inserir_categorias_padrao(IN p_usuario_id INT UNSIGNED)
BEGIN
    INSERT INTO categorias (usuario_id, nome, tipo, icone, cor) VALUES
    (p_usuario_id, 'Moradia',       'despesa', 'home',          '#e74c3c'),
    (p_usuario_id, 'Alimentação',   'despesa', 'utensils',      '#e67e22'),
    (p_usuario_id, 'Transporte',    'despesa', 'car',           '#f39c12'),
    (p_usuario_id, 'Saúde',         'despesa', 'heart-pulse',   '#e91e63'),
    (p_usuario_id, 'Lazer',         'despesa', 'gamepad-2',     '#9b59b6'),
    (p_usuario_id, 'Compras',       'despesa', 'shopping-bag',  '#3498db'),
    (p_usuario_id, 'Educação',      'despesa', 'graduation-cap','#1abc9c'),
    (p_usuario_id, 'Assinaturas',   'despesa', 'tv',            '#34495e'),
    (p_usuario_id, 'Salário',       'receita', 'briefcase',     '#27ae60'),
    (p_usuario_id, 'Investimentos', 'receita', 'trending-up',   '#2ecc71'),
    (p_usuario_id, 'Renda Extra',   'receita', 'plus-circle',   '#16a085');
END$$
DELIMITER ;
```

### 5.2 Views SQL Úteis

```sql
-- View: Resumo mensal por usuário
CREATE VIEW vw_resumo_mensal AS
SELECT
    u.id          AS usuario_id,
    YEAR(r.data)  AS ano,
    MONTH(r.data) AS mes,
    SUM(r.valor)  AS total_receitas,
    (
        SELECT COALESCE(SUM(d.valor), 0)
        FROM despesas d
        WHERE d.usuario_id = u.id
          AND YEAR(d.data) = YEAR(r.data)
          AND MONTH(d.data) = MONTH(r.data)
    )             AS total_despesas
FROM receitas r
JOIN usuarios u ON u.id = r.usuario_id
GROUP BY u.id, YEAR(r.data), MONTH(r.data);

-- View: Gastos por cartão no mês atual
CREATE VIEW vw_gastos_cartao_mes_atual AS
SELECT
    c.id          AS cartao_id,
    c.nome        AS cartao_nome,
    c.limite,
    c.usuario_id,
    COALESCE(SUM(d.valor), 0) AS total_gasto,
    c.limite - COALESCE(SUM(d.valor), 0) AS limite_disponivel
FROM cartoes c
LEFT JOIN despesas d
    ON d.cartao_id = c.id
    AND YEAR(d.data) = YEAR(CURDATE())
    AND MONTH(d.data) = MONTH(CURDATE())
WHERE c.ativo = 1
GROUP BY c.id, c.nome, c.limite, c.usuario_id;

-- View: Parcelas por mês (para projeção futura)
CREATE VIEW vw_parcelas_futuras AS
SELECT
    p.id,
    par.descricao,
    par.usuario_id,
    par.cartao_id,
    p.numero_parcela,
    par.numero_parcelas,
    p.valor,
    p.data_vencimento,
    p.pago
FROM parcelas p
JOIN parcelamentos par ON par.id = p.parcelamento_id
WHERE p.pago = 0
ORDER BY p.data_vencimento;
```

---

## 6. Regras de Negócio

### 6.1 Receitas

- Todo usuário só pode ver/editar suas próprias receitas (isolamento por `usuario_id`)
- O campo `valor` deve ser positivo
- O campo `data` não pode ser mais de 5 anos no passado (validação de sanidade)
- O tipo de receita é obrigatório

### 6.2 Despesas

- Despesas de cartão de crédito devem ter `cartao_id` preenchido
- Se `tipo = 'parcelada'`, deve existir um `parcelamento_id` associado
- O campo `forma_pagamento = 'credito'` obrigatoriamente requer `cartao_id`
- Despesas geradas automaticamente por recorrência têm `despesa_fixa_id` preenchido

### 6.3 Cartões

- Um cartão só pode ser excluído se não houver despesas associadas (ou excluir em cascata com confirmação)
- O `limite` não pode ser negativo
- `dia_fechamento` e `dia_vencimento` devem estar entre 1 e 28 (para evitar problema de meses com menos dias)

### 6.4 Saldo

```
Saldo do Mês = Total Receitas - Total Despesas (do mesmo mês/ano)
Saldo Acumulado = Soma de todos os saldos mensais até hoje
```

### 6.5 Fatura do Cartão

A data de corte da fatura é calculada assim:

- Se a data da despesa for **antes** do `dia_fechamento` do cartão → pertence à fatura **atual**
- Se a data da despesa for **igual ou depois** do `dia_fechamento` → pertence à fatura do **próximo mês**

```php
// Exemplo de cálculo em PHP
function calcularMesFatura(int $diaFechamento, DateTimeImmutable $dataCompra): string {
    $diaCompra = (int) $dataCompra->format('d');
    if ($diaCompra < $diaFechamento) {
        return $dataCompra->format('Y-m');
    }
    return $dataCompra->modify('+1 month')->format('Y-m');
}
```

---

## 7. Estratégia de Parcelamentos

### 7.1 Fluxo de Criação

Quando o usuário cadastra uma compra parcelada, o sistema executa três operações em uma única transação de banco:

```
[Usuário preenche o formulário]
        │
        ▼
[Controller recebe: descricao, valor_total, parcelas, data_compra, cartao_id]
        │
        ▼
[Cria registro em `parcelamentos`]
        │
        ▼
[Loop: para cada parcela de 1 até N]
        │
        ├── Calcula data_vencimento (data_primeira_parcela + N meses)
        ├── Calcula valor (valor_total / N, com ajuste na última)
        └── Insere em `parcelas`
        │
        ▼
[Commit da transação]
```

### 7.2 Implementação PHP do Gerador de Parcelas

```php
// app/Models/ParcelamentoModel.php

public function criarComParcelas(array $dados): int
{
    $db = Database::getInstance();

    try {
        $db->beginTransaction();

        // 1. Calcula valor por parcela (distribui centavos na última)
        $valorTotal   = round($dados['valor_total'], 2);
        $nParcelas    = (int) $dados['numero_parcelas'];
        $valorParcela = floor(($valorTotal / $nParcelas) * 100) / 100;
        $valorUltima  = round($valorTotal - ($valorParcela * ($nParcelas - 1)), 2);

        // 2. Insere o cabeçalho do parcelamento
        $stmtP = $db->prepare("
            INSERT INTO parcelamentos
                (usuario_id, cartao_id, categoria_id, descricao,
                 valor_total, numero_parcelas, valor_parcela,
                 data_compra, data_primeira_parcela, observacoes)
            VALUES
                (:usuario_id, :cartao_id, :categoria_id, :descricao,
                 :valor_total, :numero_parcelas, :valor_parcela,
                 :data_compra, :data_primeira_parcela, :observacoes)
        ");
        $stmtP->execute([
            ':usuario_id'           => $dados['usuario_id'],
            ':cartao_id'            => $dados['cartao_id'] ?? null,
            ':categoria_id'         => $dados['categoria_id'] ?? null,
            ':descricao'            => $dados['descricao'],
            ':valor_total'          => $valorTotal,
            ':numero_parcelas'      => $nParcelas,
            ':valor_parcela'        => $valorParcela,
            ':data_compra'          => $dados['data_compra'],
            ':data_primeira_parcela'=> $dados['data_primeira_parcela'],
            ':observacoes'          => $dados['observacoes'] ?? null,
        ]);
        $parcelamentoId = (int) $db->lastInsertId();

        // 3. Gera cada parcela individualmente
        $stmtParcela = $db->prepare("
            INSERT INTO parcelas
                (parcelamento_id, numero_parcela, valor, data_vencimento)
            VALUES
                (:parcelamento_id, :numero_parcela, :valor, :data_vencimento)
        ");

        $dataPrimeira = new DateTimeImmutable($dados['data_primeira_parcela']);

        for ($i = 1; $i <= $nParcelas; $i++) {
            $isUltima = ($i === $nParcelas);
            $valor    = $isUltima ? $valorUltima : $valorParcela;

            // Avança N-1 meses a partir da data da primeira parcela
            $dataVenc = $dataPrimeira->modify('+' . ($i - 1) . ' months');

            $stmtParcela->execute([
                ':parcelamento_id' => $parcelamentoId,
                ':numero_parcela'  => $i,
                ':valor'           => $valor,
                ':data_vencimento' => $dataVenc->format('Y-m-d'),
            ]);
        }

        $db->commit();
        return $parcelamentoId;

    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
}
```

### 7.3 Cálculo da Data da Primeira Parcela

A data da primeira parcela depende do cartão e do dia de fechamento:

```php
public function calcularDataPrimeiraParcela(
    int $cartaoId,
    string $dataCompra
): string {
    $cartao = CartaoModel::findById($cartaoId);
    $compra = new DateTimeImmutable($dataCompra);
    $diaCompra     = (int) $compra->format('d');
    $diaFechamento = $cartao['dia_fechamento'];
    $diaVencimento = $cartao['dia_vencimento'];

    // Se comprou antes do fechamento, vence no mês seguinte
    // Se comprou depois do fechamento, vence em dois meses
    if ($diaCompra < $diaFechamento) {
        $mesVenc = $compra->modify('first day of next month');
    } else {
        $mesVenc = $compra->modify('first day of +2 months');
    }

    // Usa o dia de vencimento do cartão
    return $mesVenc->format('Y-m-') . str_pad($diaVencimento, 2, '0', STR_PAD_LEFT);
}
```

### 7.4 Exibição das Parcelas

Para o dashboard e a listagem de parcelamentos, a query principal:

```sql
SELECT
    par.id,
    par.descricao,
    par.valor_total,
    par.numero_parcelas,
    COUNT(p.id)                          AS total_parcelas,
    SUM(CASE WHEN p.pago = 1 THEN 1 END) AS parcelas_pagas,
    SUM(CASE WHEN p.pago = 0 THEN 1 END) AS parcelas_pendentes,
    SUM(CASE WHEN p.pago = 1 THEN p.valor ELSE 0 END) AS valor_pago,
    SUM(CASE WHEN p.pago = 0 THEN p.valor ELSE 0 END) AS valor_pendente,
    c.nome   AS cartao_nome,
    c.cor    AS cartao_cor
FROM parcelamentos par
LEFT JOIN parcelas  p ON p.parcelamento_id = par.id
LEFT JOIN cartoes   c ON c.id = par.cartao_id
WHERE par.usuario_id = :usuario_id
GROUP BY par.id
ORDER BY par.created_at DESC;
```

---

## 8. Estratégia de Recorrência Mensal

### 8.1 Abordagem: Geração Sob Demanda (On-Demand)

A estratégia recomendada para despesas fixas recorrentes é **gerar lançamentos sob demanda** quando o usuário acessa um determinado mês, em vez de gerar todos os lançamentos futuros de uma vez. Isso evita poluição do banco e facilita edições no valor mensal.

```
Usuário acessa Fevereiro/2026
        │
        ▼
Sistema verifica: "Existem lançamentos de despesas fixas para Fev/2026?"
        │
        ├── SIM → Exibe normalmente
        │
        └── NÃO → Executa DespesaFixaService::gerarLancamentosDoMes(2026, 2)
                       Insere na tabela `despesas` todos os templates ativos
                       Retorna os lançamentos recém-criados
```

### 8.2 Implementação do Gerador de Recorrência

```php
// app/Services/DespesaFixaService.php

class DespesaFixaService
{
    public function gerarLancamentosDoMes(
        int $usuarioId,
        int $ano,
        int $mes
    ): int {
        $db = Database::getInstance();

        // Verifica se já foram gerados para este mês
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM despesas
            WHERE usuario_id = :uid
              AND despesa_fixa_id IS NOT NULL
              AND YEAR(data) = :ano
              AND MONTH(data) = :mes
        ");
        $stmt->execute([':uid' => $usuarioId, ':ano' => $ano, ':mes' => $mes]);
        if ($stmt->fetchColumn() > 0) {
            return 0; // Já gerados
        }

        // Busca todos os templates ativos para este período
        $primeiroDiaMes = sprintf('%04d-%02d-01', $ano, $mes);
        $ultimoDiaMes   = date('Y-m-t', strtotime($primeiroDiaMes));

        $stmt = $db->prepare("
            SELECT * FROM despesas_fixas
            WHERE usuario_id = :uid
              AND ativo = 1
              AND data_inicio <= :ultimo_dia
              AND (data_fim IS NULL OR data_fim >= :primeiro_dia)
        ");
        $stmt->execute([
            ':uid'          => $usuarioId,
            ':primeiro_dia' => $primeiroDiaMes,
            ':ultimo_dia'   => $ultimoDiaMes,
        ]);
        $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($templates)) {
            return 0;
        }

        // Insere um lançamento para cada template
        $stmtIns = $db->prepare("
            INSERT INTO despesas
                (usuario_id, categoria_id, despesa_fixa_id,
                 descricao, valor, data, tipo, forma_pagamento)
            VALUES
                (:uid, :categoria_id, :despesa_fixa_id,
                 :descricao, :valor, :data, 'fixa', 'boleto')
        ");

        $count = 0;
        foreach ($templates as $t) {
            // Usa o dia de vencimento do template, limitado ao último dia do mês
            $dia  = min($t['dia_vencimento'], (int) date('t', strtotime($primeiroDiaMes)));
            $data = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);

            $stmtIns->execute([
                ':uid'            => $usuarioId,
                ':categoria_id'   => $t['categoria_id'],
                ':despesa_fixa_id'=> $t['id'],
                ':descricao'      => $t['descricao'],
                ':valor'          => $t['valor'],
                ':data'           => $data,
            ]);
            $count++;
        }

        return $count;
    }
}
```

### 8.3 Quando Executar a Geração

O método `gerarLancamentosDoMes` deve ser chamado em dois momentos:

1. **Ao carregar o dashboard** — para o mês atual
2. **Ao navegar para um mês** — nas telas de listagem de despesas

Isso garante que os lançamentos estejam sempre prontos sem necessidade de cron jobs na hospedagem.

> **Nota:** Para projetos com acesso a cron job no servidor, é possível criar um job diário que gera os lançamentos do mês seguinte na virada do mês. Isso garante que os lançamentos apareçam antecipadamente no dashboard.

---

## 9. Estratégia de Segurança

### 9.1 Autenticação e Sessão

```php
// core/Auth.php

class Auth
{
    public static function login(array $usuario): void
    {
        session_regenerate_id(true); // Previne Session Fixation
        $_SESSION['user_id']    = $usuario['id'];
        $_SESSION['user_name']  = $usuario['nome'];
        $_SESSION['user_email'] = $usuario['email'];
        $_SESSION['logged_in']  = true;
        $_SESSION['ip']         = $_SERVER['REMOTE_ADDR'];
        $_SESSION['ua']         = $_SERVER['HTTP_USER_AGENT'];
    }

    public static function check(): bool
    {
        if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
            return false;
        }
        // Valida consistência da sessão (previne session hijacking básico)
        if ($_SESSION['ip'] !== $_SERVER['REMOTE_ADDR']) {
            self::logout();
            return false;
        }
        return true;
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            header('Location: /login');
            exit;
        }
    }

    public static function userId(): int
    {
        return (int) ($_SESSION['user_id'] ?? 0);
    }

    public static function logout(): void
    {
        session_destroy();
        session_start();
        session_regenerate_id(true);
    }
}
```

### 9.2 Hash de Senha

```php
// Ao cadastrar
$senhaHash = password_hash($senhaDigitada, PASSWORD_ARGON2ID, [
    'memory_cost' => 65536,
    'time_cost'   => 4,
    'threads'     => 1,
]);

// Ao fazer login
if (password_verify($senhaDigitada, $senhaHashDoBanco)) {
    // Verificar se precisa de rehash
    if (password_needs_rehash($senhaHashDoBanco, PASSWORD_ARGON2ID)) {
        $novoHash = password_hash($senhaDigitada, PASSWORD_ARGON2ID);
        UsuarioModel::atualizarSenha($usuarioId, $novoHash);
    }
    Auth::login($usuario);
}
```

### 9.3 Proteção CSRF

```php
// core/CsrfHelper.php

class CsrfHelper
{
    public static function generateToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function validateToken(string $token): bool
    {
        return isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function inputHidden(): string
    {
        $token = self::generateToken();
        return '<input type="hidden" name="csrf_token" value="' . $token . '">';
    }
}

// Em todo formulário POST:
// <?= CsrfHelper::inputHidden() ?>

// No Controller, antes de processar:
// CsrfHelper::validateToken($_POST['csrf_token']) || die('CSRF inválido');
```

### 9.4 Prepared Statements — Padrão Obrigatório

```php
// NUNCA fazer isso:
// $sql = "SELECT * FROM receitas WHERE usuario_id = " . $_GET['id'];

// SEMPRE usar prepared statements:
$stmt = $db->prepare("SELECT * FROM receitas WHERE usuario_id = :id AND id = :rid");
$stmt->execute([':id' => Auth::userId(), ':rid' => (int)$_GET['id']]);
$receita = $stmt->fetch(PDO::FETCH_ASSOC);
```

### 9.5 Proteção XSS

```php
// helpers/FormatHelper.php

// Toda saída de dados para HTML deve passar por esta função:
function e(string $valor): string
{
    return htmlspecialchars($valor, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// No template:
// <h1><?= e($receita['descricao']) ?></h1>
```

### 9.6 Isolamento de Dados por Usuário

Todo Model **sempre** filtra por `usuario_id = Auth::userId()`. Nunca confiar em IDs vindos de `$_GET` ou `$_POST` sem validar o dono:

```php
public function findById(int $id): ?array
{
    $stmt = $this->db->prepare("
        SELECT * FROM receitas
        WHERE id = :id AND usuario_id = :uid
    ");
    $stmt->execute([':id' => $id, ':uid' => Auth::userId()]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}
```

### 9.7 Configurações de Sessão Segura

```php
// config/config.php — executado no início de toda requisição

ini_set('session.cookie_httponly', 1);    // JS não acessa o cookie
ini_set('session.cookie_secure', 1);      // Apenas HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 3600);  // 1 hora de inatividade
ini_set('session.use_strict_mode', 1);
session_start();
```

---

## 10. Arquitetura do Frontend

### 10.1 Paleta de Cores e Variáveis CSS

```css
/* public/assets/css/app.css */

:root {
    /* Cores Primárias */
    --color-primary:       #2563eb;
    --color-primary-dark:  #1d4ed8;
    --color-primary-light: #dbeafe;

    /* Semânticas */
    --color-success:  #16a34a;
    --color-danger:   #dc2626;
    --color-warning:  #d97706;
    --color-info:     #0891b2;

    /* Neutros */
    --color-bg:           #f8fafc;
    --color-surface:      #ffffff;
    --color-border:       #e2e8f0;
    --color-text:         #1e293b;
    --color-text-muted:   #64748b;

    /* Sidebar */
    --sidebar-width:      260px;
    --sidebar-bg:         #1e293b;
    --sidebar-text:       #94a3b8;
    --sidebar-active:     #2563eb;

    /* Espaçamentos */
    --space-xs:  4px;
    --space-sm:  8px;
    --space-md:  16px;
    --space-lg:  24px;
    --space-xl:  32px;

    /* Border radius */
    --radius-sm:  6px;
    --radius-md:  10px;
    --radius-lg:  16px;

    /* Sombras */
    --shadow-sm: 0 1px 3px rgba(0,0,0,.08);
    --shadow-md: 0 4px 12px rgba(0,0,0,.10);
    --shadow-lg: 0 8px 24px rgba(0,0,0,.12);
}

/* Dark Mode */
[data-theme="dark"] {
    --color-bg:         #0f172a;
    --color-surface:    #1e293b;
    --color-border:     #334155;
    --color-text:       #f1f5f9;
    --color-text-muted: #94a3b8;
}
```

### 10.2 Layout Responsivo

```css
/* Layout principal: sidebar fixa + conteúdo */
.app-layout {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: var(--sidebar-width);
    background: var(--sidebar-bg);
    position: fixed;
    height: 100vh;
    overflow-y: auto;
    transition: transform .3s ease;
}

.main-content {
    margin-left: var(--sidebar-width);
    flex: 1;
    padding: var(--space-lg);
    background: var(--color-bg);
}

/* Mobile: sidebar vira drawer */
@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%);
        z-index: 1000;
    }
    .sidebar.open {
        transform: translateX(0);
    }
    .main-content {
        margin-left: 0;
    }
}
```

### 10.3 Cards do Dashboard

```css
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: var(--space-lg);
    margin-bottom: var(--space-xl);
}

.stat-card {
    background: var(--color-surface);
    border-radius: var(--radius-md);
    padding: var(--space-lg);
    box-shadow: var(--shadow-sm);
    border-left: 4px solid var(--color-primary);
    display: flex;
    flex-direction: column;
    gap: var(--space-sm);
}

.stat-card.receitas { border-color: var(--color-success); }
.stat-card.despesas  { border-color: var(--color-danger); }
.stat-card.saldo     { border-color: var(--color-primary); }
.stat-card.cartao    { border-color: var(--color-warning); }
```

### 10.4 Gráficos com Chart.js

```javascript
// public/assets/js/dashboard.js

// Gráfico de Pizza — Despesas por Categoria
function renderizarGraficoCategorias(dados) {
    const ctx = document.getElementById('chart-categorias').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: dados.map(d => d.categoria),
            datasets: [{
                data: dados.map(d => d.total),
                backgroundColor: dados.map(d => d.cor),
                borderWidth: 2,
                borderColor: '#fff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right' },
                tooltip: {
                    callbacks: {
                        label: ctx => ` R$ ${ctx.parsed.toLocaleString('pt-BR', {
                            minimumFractionDigits: 2
                        })}`
                    }
                }
            }
        }
    });
}

// Gráfico de Barras — Evolução Mensal (6 meses)
function renderizarEvolucaoMensal(dados) {
    const ctx = document.getElementById('chart-evolucao').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: dados.map(d => d.mes),
            datasets: [
                {
                    label: 'Receitas',
                    data: dados.map(d => d.receitas),
                    backgroundColor: 'rgba(22, 163, 74, 0.8)',
                    borderRadius: 6
                },
                {
                    label: 'Despesas',
                    data: dados.map(d => d.despesas),
                    backgroundColor: 'rgba(220, 38, 38, 0.8)',
                    borderRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: val => 'R$ ' + val.toLocaleString('pt-BR')
                    }
                }
            }
        }
    });
}
```

### 10.5 Máscara de Moeda

```javascript
// public/assets/js/masks.js

function mascaraMoeda(input) {
    input.addEventListener('input', function () {
        let valor = this.value.replace(/\D/g, '');
        valor = (parseInt(valor) / 100).toFixed(2);
        this.value = valor.replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    });
}

// Aplica em todos os inputs de valor monetário
document.querySelectorAll('[data-mask="money"]').forEach(mascaraMoeda);
```

---

## 11. Fluxo das Telas

### 11.1 Mapa de Navegação

```
[Login] → [Dashboard]
              │
    ┌─────────┼──────────┬──────────┬──────────┐
    ▼         ▼          ▼          ▼          ▼
[Receitas] [Despesas] [Cartões] [Parcelam.] [Config]
    │         │          │          │
[Criar]   [Criar]   [Fatura]   [Detalhe]
[Editar]  [Editar]  [Editar]
[Excluir] [Excluir] [Excluir]
```

### 11.2 Dashboard — Seções

```
┌─────────────────────────────────────────────────────┐
│  AlcatechFinanças       Maio 2026    [ < ] [ > ]    │ ← Header com navegação de mês
├─────────┬───────────────────────────────────────────┤
│         │  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐    │
│         │  │Receit│ │Despes│ │Saldo │ │Faturas│   │ ← Cards de resumo
│ SIDEBAR │  │ R$X  │ │ R$Y  │ │ R$Z  │ │ próx  │   │
│         │  └──────┘ └──────┘ └──────┘ └──────┘    │
│ Dashboard│  ┌────────────────┐ ┌────────────────┐  │
│ Receitas│  │ Despesas por   │ │ Evolução Mensal │  │ ← Gráficos
│ Despesas│  │ Categoria 🍕  │ │ (Barras 6M)    │  │
│ Cartões │  └────────────────┘ └────────────────┘  │
│ Parcelas│  ┌────────────────┐ ┌────────────────┐  │
│ Categ.  │  │ Gastos por     │ │ Parcelas       │  │
│ Sair    │  │ Cartão         │ │ Futuras        │  │
│         │  └────────────────┘ └────────────────┘  │
│         │  ┌──────────────────────────────────┐   │
│         │  │ Últimos Lançamentos              │   │
│         │  └──────────────────────────────────┘   │
└─────────┴───────────────────────────────────────────┘
```

### 11.3 Tela de Despesas — Filtros

```
[Mês Atual ▼] [Tipo: Todos ▼] [Categoria ▼] [Cartão ▼]  [+ Nova Despesa]
────────────────────────────────────────────────────────────────────────
Data       Descrição              Categoria    Forma Pag.  Valor   Ações
────────────────────────────────────────────────────────────────────────
15/05/26   Supermercado          Alimentação  PIX         R$320    ✏ 🗑
12/05/26   Netflix               Assinaturas  Cartão Nubank R$55   ✏ 🗑
10/05/26   Financiamento casa    Moradia      Boleto      R$1.200  ✏ 🗑
────────────────────────────────────────────────────────────────────────
                                                    Total: R$1.575
```

---

## 12. Roadmap de Desenvolvimento

### Fase 1 — Fundação (2-3 semanas)

**Objetivo:** Sistema funcional com autenticação e CRUD básico.

| Tarefa | Prioridade | Estimativa |
|---|---|---|
| Estrutura de pastas e Front Controller | Alta | 1 dia |
| Roteador (Router.php) | Alta | 1 dia |
| Conexão com banco (Database.php singleton) | Alta | 0,5 dia |
| Sistema de autenticação (login/cadastro/sessão) | Alta | 2 dias |
| Layout principal (sidebar, header, responsivo) | Alta | 2 dias |
| CRUD de Categorias | Alta | 1 dia |
| Script SQL completo de criação do banco | Alta | 1 dia |

**Entrega:** Usuário consegue criar conta, logar e gerenciar categorias.

---

### Fase 2 — Módulos Principais (3-4 semanas)

**Objetivo:** CRUD completo de receitas, despesas e cartões.

| Tarefa | Prioridade | Estimativa |
|---|---|---|
| CRUD de Receitas | Alta | 2 dias |
| CRUD de Cartões | Alta | 2 dias |
| CRUD de Despesas Variáveis | Alta | 2 dias |
| CRUD de Despesas Fixas (templates) | Alta | 2 dias |
| Gerador automático de lançamentos recorrentes | Alta | 2 dias |
| Listagem de despesas com filtros (mês, tipo, categoria) | Alta | 2 dias |
| Tela de Fatura do Cartão | Média | 2 dias |

**Entrega:** Controle completo de receitas e despesas, com recorrência funcionando.

---

### Fase 3 — Parcelamentos (1-2 semanas)

**Objetivo:** Sistema de parcelamentos completo.

| Tarefa | Prioridade | Estimativa |
|---|---|---|
| Formulário de nova compra parcelada | Alta | 1 dia |
| Gerador automático de parcelas (transação SQL) | Alta | 2 dias |
| Listagem de parcelamentos com progresso | Alta | 1 dia |
| Tela de detalhe do parcelamento | Alta | 1 dia |
| Marcar parcela como paga | Alta | 0,5 dia |
| Integração parcelas com fatura do cartão | Alta | 1 dia |

**Entrega:** Compras parceladas com distribuição automática por mês.

---

### Fase 4 — Dashboard (1-2 semanas)

**Objetivo:** Dashboard visual e analítico.

| Tarefa | Prioridade | Estimativa |
|---|---|---|
| Cards de resumo (receitas/despesas/saldo) | Alta | 1 dia |
| Gráfico de pizza (categorias) | Alta | 1 dia |
| Gráfico de barras (evolução mensal) | Alta | 1 dia |
| Gastos por cartão | Alta | 1 dia |
| Parcelas futuras | Média | 1 dia |
| Últimos lançamentos | Média | 0,5 dia |
| Navegação por mês no dashboard | Média | 1 dia |

**Entrega:** Dashboard completo com visão financeira em tempo real.

---

### Fase 5 — Polimento e UX (1-2 semanas)

**Objetivo:** Refinamento da experiência do usuário.

| Tarefa | Prioridade | Estimativa |
|---|---|---|
| Dark mode (toggle + localStorage) | Média | 1 dia |
| Máscaras de input (dinheiro, data) | Alta | 0,5 dia |
| Validação de formulários client-side | Alta | 1 dia |
| Mensagens de feedback (sucesso/erro) | Alta | 0,5 dia |
| Ordenação e paginação nas listagens | Média | 1 dia |
| Otimização para mobile (bottom nav) | Alta | 1 dia |
| Auditoria de segurança (XSS, CSRF, SQLi) | Alta | 1 dia |

**Entrega:** Sistema polido, seguro e com boa usabilidade mobile.

---

### Fase 6 — Extras e Funcionalidades Avançadas (Futuro)

| Funcionalidade | Complexidade |
|---|---|
| Exportação para Excel (via PhpSpreadsheet) | Média |
| Exportação para PDF (via FPDF ou DomPDF) | Média |
| Metas financeiras | Média |
| Gráfico de linha (evolução do saldo) | Baixa |
| Notificações de vencimento (via e-mail) | Alta |
| Controle de investimentos | Alta |
| Backup automático do banco | Média |
| PWA (Progressive Web App) | Alta |
| Relatório mensal por e-mail | Alta |

---

## 13. Extras e Sugestões Futuras

### 13.1 Dark Mode

Implemente via CSS custom properties + toggle JavaScript:

```javascript
// Alternância de tema
const toggle = document.getElementById('theme-toggle');
toggle.addEventListener('click', () => {
    const isDark = document.documentElement.dataset.theme === 'dark';
    document.documentElement.dataset.theme = isDark ? 'light' : 'dark';
    localStorage.setItem('theme', isDark ? 'light' : 'dark');
});

// Aplicar tema salvo
const savedTheme = localStorage.getItem('theme') || 'light';
document.documentElement.dataset.theme = savedTheme;
```

### 13.2 Exportação para Excel

Use a biblioteca **PhpSpreadsheet** via Composer:

```php
// composer require phpoffice/phpspreadsheet

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

public function exportarDespesasMes(int $ano, int $mes): void
{
    $despesas = DespesaModel::listarPorMes($ano, $mes);
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->fromArray(['Data','Descrição','Categoria','Valor','Pago'], null, 'A1');
    $linha = 2;
    foreach ($despesas as $d) {
        $sheet->fromArray([
            $d['data'], $d['descricao'], $d['categoria'],
            $d['valor'], $d['pago'] ? 'Sim' : 'Não'
        ], null, "A{$linha}");
        $linha++;
    }

    $writer = new Xlsx($spreadsheet);
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment; filename=despesas_{$ano}_{$mes}.xlsx");
    $writer->save('php://output');
}
```

### 13.3 PWA (Progressive Web App)

Adicione um `manifest.json` e um Service Worker básico para permitir instalação no celular:

```json
// public/manifest.json
{
  "name": "AlcatechFinanças",
  "short_name": "Alcatech",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#1e293b",
  "theme_color": "#2563eb",
  "icons": [
    { "src": "/assets/img/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/assets/img/icon-512.png", "sizes": "512x512", "type": "image/png" }
  ]
}
```

### 13.4 Metas Financeiras

Sugestão de tabela adicional:

```sql
CREATE TABLE metas (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    usuario_id    INT UNSIGNED  NOT NULL,
    nome          VARCHAR(100)  NOT NULL,
    valor_alvo    DECIMAL(12,2) NOT NULL,
    valor_atual   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    data_limite   DATE              NULL,
    concluida     TINYINT(1)    NOT NULL DEFAULT 0,
    created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    CONSTRAINT fk_meta_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;
```

### 13.5 Índices de Performance Adicionais

Quando o volume de dados crescer, adicione:

```sql
-- Para relatórios por período
ALTER TABLE despesas ADD INDEX idx_desp_periodo (usuario_id, data, tipo);
ALTER TABLE receitas ADD INDEX idx_rec_periodo  (usuario_id, data, tipo_receita);

-- Para dashboard de cartões
ALTER TABLE despesas ADD INDEX idx_desp_cartao_mes (cartao_id, YEAR(data), MONTH(data));
```

---

## Considerações Finais

O sistema **AlcatechFinanças** foi projetado com foco em:

**Segurança** — Isolamento total de dados por usuário, prepared statements em 100% das queries, proteção CSRF e XSS em todos os formulários, hash Argon2id para senhas.

**Escalabilidade** — A arquitetura MVC com separação clara de responsabilidades permite adicionar novos módulos sem reescrever código existente. A migração futura para um micro-framework (como Slim PHP) ou até Laravel é simples, pois a lógica já está organizada em Models e Controllers.

**Manutenibilidade** — Cada módulo é independente. O código segue Clean Code e princípios SOLID adaptados para PHP sem framework. Toda lógica de banco está nos Models, toda lógica de negócio nos Controllers e toda apresentação nas Views.

**Performance** — Índices estratégicos no banco, queries otimizadas com filtros por `usuario_id` e data, e geração de lançamentos sob demanda (sem poluição de registros futuros).

**Experiência do Usuário** — Interface responsiva que funciona bem no celular, navegação por mês no dashboard, feedback visual em todas as ações e dark mode para uso noturno.

---

*Documento gerado em Maio de 2026 — AlcatechFinanças v1.0*
