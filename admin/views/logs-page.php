<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
	<h1><?php esc_html_e( 'Ping Logs', 'newsdesk-sitemap' ); ?></h1>
	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Time', 'newsdesk-sitemap' ); ?></th>
				<th><?php esc_html_e( 'Service', 'newsdesk-sitemap' ); ?></th>
				<th><?php esc_html_e( 'Status', 'newsdesk-sitemap' ); ?></th>
				<th><?php esc_html_e( 'Message', 'newsdesk-sitemap' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php
			if ( ! empty( $logs ) ) {
				foreach ( $logs as $log ) {
					echo '<tr>';
					echo '<td>' . esc_html( $log['timestamp'] ) . '</td>';
					echo '<td>' . esc_html( $log['search_engine'] ) . '</td>';
					echo '<td>' . ( $log['response_code'] == 200 ? '<span style="color:green">OK</span>' : '<span style="color:red">Fail</span>' ) . '</td>';
					echo '<td>' . esc_html( $log['response_message'] ) . '</td>';
					echo '</tr>';
				}
			} else {
				echo '<tr><td colspan="4">' . esc_html__( 'No logs found.', 'newsdesk-sitemap' ) . '</td></tr>';
			}
			?>
		</tbody>
	</table>
</div>