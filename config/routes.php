<?php
/**
 * Mapa de rotas da aplicação.
 * Formato: $router->add(MÉTODO, 'uri', 'Controller@action');
 */

// ──────────────────────────────────────────────
// Autenticação (rotas públicas)
// ──────────────────────────────────────────────
$router->add('GET',  '/login',    'AuthController@loginForm');
$router->add('POST', '/login',    'AuthController@login');
$router->add('GET',  '/cadastro', 'AuthController@cadastroForm');
$router->add('POST', '/cadastro', 'AuthController@cadastro');
$router->add('GET',  '/sair',     'AuthController@logout');
$router->add('GET',  '/ping',     'AuthController@ping');

// ──────────────────────────────────────────────
// Dashboard (requer autenticação)
// ──────────────────────────────────────────────
$router->add('GET', '/',          'DashboardController@index');
$router->add('GET', '/dashboard', 'DashboardController@index');

// ──────────────────────────────────────────────
// Receitas
// ──────────────────────────────────────────────
$router->add('GET',  '/receitas',          'ReceitaController@index');
$router->add('GET',  '/receitas/criar',    'ReceitaController@criar');
$router->add('POST', '/receitas/criar',    'ReceitaController@store');
$router->add('GET',  '/receitas/editar',   'ReceitaController@editar');
$router->add('POST', '/receitas/editar',   'ReceitaController@update');
$router->add('POST', '/receitas/excluir',  'ReceitaController@destroy');

// ──────────────────────────────────────────────
// Despesas
// ──────────────────────────────────────────────
$router->add('GET',  '/despesas',          'DespesaController@index');
$router->add('GET',  '/despesas/criar',    'DespesaController@criar');
$router->add('POST', '/despesas/criar',    'DespesaController@store');
$router->add('GET',  '/despesas/editar',   'DespesaController@editar');
$router->add('POST', '/despesas/editar',   'DespesaController@update');
$router->add('POST', '/despesas/excluir',  'DespesaController@destroy');
$router->add('POST', '/despesas/pagar',    'DespesaController@pagar');

// Despesas Fixas
$router->add('GET',  '/despesas-fixas',         'DespesaFixaController@index');
$router->add('GET',  '/despesas-fixas/criar',   'DespesaFixaController@criar');
$router->add('POST', '/despesas-fixas/criar',   'DespesaFixaController@store');
$router->add('GET',  '/despesas-fixas/editar',  'DespesaFixaController@editar');
$router->add('POST', '/despesas-fixas/editar',  'DespesaFixaController@update');
$router->add('POST', '/despesas-fixas/excluir', 'DespesaFixaController@destroy');
$router->add('POST', '/despesas-fixas/deletar', 'DespesaFixaController@deletar');
$router->add('POST', '/despesas-fixas/ativar',  'DespesaFixaController@ativar');

// ──────────────────────────────────────────────
// Cartões
// ──────────────────────────────────────────────
$router->add('GET',  '/cartoes',          'CartaoController@index');
$router->add('GET',  '/cartoes/criar',    'CartaoController@criar');
$router->add('POST', '/cartoes/criar',    'CartaoController@store');
$router->add('GET',  '/cartoes/editar',   'CartaoController@editar');
$router->add('POST', '/cartoes/editar',   'CartaoController@update');
$router->add('POST', '/cartoes/excluir',  'CartaoController@destroy');
$router->add('GET',  '/cartoes/fatura',   'CartaoController@fatura');

// ──────────────────────────────────────────────
// Parcelamentos
// ──────────────────────────────────────────────
$router->add('GET',  '/parcelamentos',         'ParcelamentoController@index');
$router->add('GET',  '/parcelamentos/criar',   'ParcelamentoController@criar');
$router->add('POST', '/parcelamentos/criar',   'ParcelamentoController@store');
$router->add('GET',  '/parcelamentos/detalhe', 'ParcelamentoController@detalhe');
$router->add('POST', '/parcelamentos/pagar',   'ParcelamentoController@pagarParcela');
$router->add('POST', '/parcelamentos/bloquear',    'ParcelamentoController@bloquear');
$router->add('POST', '/parcelamentos/desbloquear', 'ParcelamentoController@desbloquear');
$router->add('POST', '/parcelamentos/excluir',     'ParcelamentoController@destroy');

// ──────────────────────────────────────────────
// Categorias
// ──────────────────────────────────────────────
$router->add('GET',  '/categorias',        'CategoriaController@index');
$router->add('POST', '/categorias/criar',  'CategoriaController@store');
$router->add('POST', '/categorias/editar', 'CategoriaController@update');
$router->add('POST', '/categorias/excluir','CategoriaController@destroy');

// ──────────────────────────────────────────────
// Chat IA
// ──────────────────────────────────────────────
$router->add('POST', '/api/chat',           'ChatController@send');
$router->add('POST', '/api/chat/confirmar', 'ChatController@confirmar');

// ──────────────────────────────────────────────
// Relatórios
// ──────────────────────────────────────────────
$router->add('GET', '/relatorios', 'RelatorioController@index');

// ──────────────────────────────────────────────
// Perfil do usuário
// ──────────────────────────────────────────────
$router->add('GET',  '/perfil',          'PerfilController@index');
$router->add('POST', '/perfil/atualizar','PerfilController@atualizar');
$router->add('POST', '/perfil/senha',    'PerfilController@atualizarSenha');
