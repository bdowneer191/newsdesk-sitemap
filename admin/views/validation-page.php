<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
	<h1><?php esc_html_e( 'Sitemap Validation', 'newsdesk-sitemap' ); ?></h1>
	<div class="card">
		<h2><?php esc_html_e( 'Live Validator', 'newsdesk-sitemap' ); ?></h2>
		<p><?php esc_html_e( 'Check your sitemap against Google News schemas.', 'newsdesk-sitemap' ); ?></p>
		<button class="button button-primary" id="nds-run-validation"><?php esc_html_e( 'Run Validation', 'newsdesk-sitemap' ); ?></button>
		<div id="nds-validation-results" style="margin-top:20px;"></div>
	</div>
</div>