<?php
/**
 * Apollo Seed Runner
 *
 * Plugin Name: Apollo Seed Runner
 * Description: Loader for _inventory/seed/apollo-seed.php. That file only defines
 *              apollo_seed_run()/apollo_seed_format() and registers the
 *              ?apollo_seed=1[&write=1] admin_init handler — it does nothing
 *              until something requires it. This plugin is that something.
 *              Sits outside apollo-core on purpose: it is a one-off content
 *              operation, not ecosystem infrastructure, and shouldn't live
 *              inside a plugin that 24+ others depend on.
 * Version: 1.0.0
 * Author: Apollo::Rio
 * License: Proprietary
 *
 * Entry points (admin, logged in as an administrator):
 *   /wp-admin/?apollo_seed=1          dry run — prints the plan, writes nothing
 *   /wp-admin/?apollo_seed=1&write=1  writes — idempotent, safe to repeat
 *
 * @package Apollo\SeedRunner
 * @since   2026-08-27
 */

/*
 * ARCH: apollo-seed-runner
 *
 * Gerado de código real (scan-plugins.js). Não edite à mão: rode
 * `node D:/dev/_cos/verify/gen-arch-blocks.js` para regenerar.
 * Contrato completo: D:/dev/_cos/verify/MODULE-CONTRACT.md
 *
 * OWNER     apollo-seed-runner   1 arquivos PHP, 33 LOC
 * RUNTIME   CPT/taxonomia/meta registrados por apollo-core (init:5)
 * UI        não emite HTML
 * META      0 chaves tocadas
 * REST      nenhuma rota
 * REQUIRES  nenhuma dependência confirmada
 *
 * NÃO FAÇA
 *   - registrar CPT direto: apollo-core é o dono do init:5.
 *     Fallback do owner só com post_type_exists().
 *   - gravar meta de outro domínio (hoje 227 chaves não têm dono).
 *   - registrar um segundo namespace REST. Só apollo/v1.
 *     Já existe um namespace fora do padrão no apollo-telegram.
 *   - add_shortcode() sem shortcode_exists(): o último a registrar
 *     vence em silêncio e quem roda vira acidente de ordem de carga.
 *
 * VERIFICAR   node D:/dev/_cos/verify/plugin-audit.js
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$apollo_seed_file = __DIR__ . '/../_inventory/seed/apollo-seed.php';
if ( is_readable( $apollo_seed_file ) ) {
	require_once $apollo_seed_file;
}
