<?php
/**
 * Plugin Name:       DrTalks AI
 * Plugin URI:        https://drtalks.ai
 * Description:       Adds the DrTalks AI chat widget to your website. Configure your Widget ID and site domain to get started.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.0
 * Author:            DrTalks
 * Author URI:        https://drtalks.ai
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       drtalks-ai
 *
 * @package DrTalks_AI
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─── Constants ───────────────────────────────────────────────────────────────

define( 'DRTALKS_AI_OPTION', 'drtalks_ai_chat_settings' );
define( 'DRTALKS_AI_CDN',    'https://cdn.drtalks.ai/v1_0_4/drtalks-doctors-widget.min.js' );
define( 'DRTALKS_AI_HANDLE', 'drtalks-ai-widget' );

// ─── Page-type exclusion definitions ─────────────────────────────────────────

function drtalks_ai_exclusion_types() {
	$types = [
		'front_page'       => __( 'Homepage (front page)', 'drtalks-ai' ),
		'search'           => __( 'Search results pages', 'drtalks-ai' ),
		'404'              => __( '404 Not Found pages', 'drtalks-ai' ),
		'login'            => __( 'Login page', 'drtalks-ai' ),
		'blog'             => __( 'Blog / posts index page', 'drtalks-ai' ),
		'archive'          => __( 'Archive pages (categories, tags, dates, authors)', 'drtalks-ai' ),
		'feed'             => __( 'RSS / Atom feeds', 'drtalks-ai' ),
	];

	if ( class_exists( 'WooCommerce' ) ) {
		$types['wc_cart']     = __( 'WooCommerce — Cart', 'drtalks-ai' );
		$types['wc_checkout'] = __( 'WooCommerce — Checkout', 'drtalks-ai' );
		$types['wc_account']  = __( 'WooCommerce — My Account', 'drtalks-ai' );
		$types['wc_thankyou'] = __( 'WooCommerce — Order confirmation (thank you)', 'drtalks-ai' );
	}

	return $types;
}

// ─── Front end: enqueue the widget script ─────────────────────────────────────

add_action( 'wp_enqueue_scripts', 'drtalks_ai_inject_widget' );

function drtalks_ai_inject_widget() {
	$options   = get_option( DRTALKS_AI_OPTION, [] );
	$widget_id = isset( $options['widget_id'] )   ? trim( $options['widget_id'] )   : '';
	$domain    = isset( $options['site_domain'] )  ? trim( $options['site_domain'] )  : '';
	$use_defer = ! empty( $options['defer_script'] );

	if ( empty( $widget_id ) || empty( $domain ) ) {
		return;
	}

	if ( drtalks_ai_is_excluded( $options ) ) {
		return;
	}

	wp_enqueue_script(
		DRTALKS_AI_HANDLE,
		DRTALKS_AI_CDN,
		[],
		null, // phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion -- The CDN URL is already versioned in its path (v1_0_4), so a ?ver= query string is not needed.
		$use_defer ? [ 'strategy' => 'defer' ] : []
	);

	// Stored on the script so drtalks_ai_script_attributes() can add them to the tag.
	wp_script_add_data(
		DRTALKS_AI_HANDLE,
		'drtalks_ai_attributes',
		[
			'data-widget-id'   => $widget_id,
			'data-site-domain' => $domain,
		]
	);
}

add_filter( 'wp_script_attributes', 'drtalks_ai_script_attributes' );

/**
 * Adds the widget's data attributes to our script tag only.
 *
 * Values are escaped by wp_get_script_tag() when the tag is rendered.
 *
 * @param array $attributes Script tag attributes.
 * @return array
 */
function drtalks_ai_script_attributes( $attributes ) {
	if ( ! isset( $attributes['id'] ) || DRTALKS_AI_HANDLE . '-js' !== $attributes['id'] ) {
		return $attributes;
	}

	$data = wp_scripts()->get_data( DRTALKS_AI_HANDLE, 'drtalks_ai_attributes' );

	if ( is_array( $data ) ) {
		$attributes = array_merge( $attributes, $data );
	}

	return $attributes;
}

function drtalks_ai_is_excluded( $options ) {
	$excluded_types = isset( $options['exclude_types'] ) && is_array( $options['exclude_types'] )
		? $options['exclude_types']
		: [];

	// Page-type checks
	if ( in_array( 'front_page',       $excluded_types, true ) && is_front_page() )  { return true; }
	if ( in_array( 'search',           $excluded_types, true ) && is_search() )      { return true; }
	if ( in_array( '404',              $excluded_types, true ) && is_404() )          { return true; }
	if ( in_array( 'blog',             $excluded_types, true ) && is_home() )         { return true; }
	if ( in_array( 'archive',          $excluded_types, true ) && is_archive() )      { return true; }
	if ( in_array( 'feed',             $excluded_types, true ) && is_feed() )         { return true; }
	if ( in_array( 'login',            $excluded_types, true ) && ( isset( $GLOBALS['pagenow'] ) && $GLOBALS['pagenow'] === 'wp-login.php' ) ) { return true; }

	if ( class_exists( 'WooCommerce' ) ) {
		if ( in_array( 'wc_cart',     $excluded_types, true ) && function_exists( 'is_cart' )     && is_cart() )           { return true; }
		if ( in_array( 'wc_checkout', $excluded_types, true ) && function_exists( 'is_checkout' ) && is_checkout() )       { return true; }
		if ( in_array( 'wc_account',  $excluded_types, true ) && function_exists( 'is_account_page' ) && is_account_page() ) { return true; }
		if ( in_array( 'wc_thankyou', $excluded_types, true ) && function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'order-received' ) ) { return true; }
	}

	// Custom URL checks (skipped when the server does not provide a request URI)
	$exclude_urls_raw = isset( $options['exclude_urls'] ) ? $options['exclude_urls'] : '';
	if ( ! empty( $exclude_urls_raw ) && isset( $_SERVER['REQUEST_URI'] ) ) {
		$request_uri  = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		$current_url  = home_url( $request_uri );
		$current_path = wp_parse_url( $current_url, PHP_URL_PATH );

		foreach ( explode( "\n", $exclude_urls_raw ) as $line ) {
			$line = trim( $line );
			if ( empty( $line ) ) {
				continue;
			}
			// Match against full URL or just the path, with optional wildcard support
			$line_path = wp_parse_url( $line, PHP_URL_PATH );
			$pattern   = $line_path ? $line_path : $line;

			if ( strpos( $pattern, '*' ) !== false ) {
				// Wildcard: convert to regex (e.g. /my-account/* → matches /my-account/anything)
				$regex = '#^' . str_replace( '\*', '.*', preg_quote( rtrim( $pattern, '/' ), '#' ) ) . '(/.*)?$#';
				if ( preg_match( $regex, $current_path ) ) {
					return true;
				}
			} else {
				// Exact path or full URL match
				if ( rtrim( $current_path, '/' ) === rtrim( $pattern, '/' ) ) {
					return true;
				}
				if ( rtrim( $current_url, '/' ) === rtrim( $line, '/' ) ) {
					return true;
				}
			}
		}
	}

	return false;
}

// ─── Admin: register settings ─────────────────────────────────────────────────

add_action( 'admin_init', 'drtalks_ai_register_settings' );

function drtalks_ai_register_settings() {
	register_setting(
		DRTALKS_AI_OPTION,
		DRTALKS_AI_OPTION,
		[
			'sanitize_callback' => 'drtalks_ai_sanitize',
		]
	);
}

function drtalks_ai_sanitize( $input ) {
	$clean = [];

	// Widget ID
	$clean['widget_id'] = isset( $input['widget_id'] )
		? sanitize_text_field( $input['widget_id'] )
		: '';

	// Domain – strip protocol, www, trailing slashes
	$raw_domain = isset( $input['site_domain'] ) ? $input['site_domain'] : '';
	$raw_domain = preg_replace( '#^https?://#i', '', $raw_domain );
	$raw_domain = preg_replace( '#^www\.#i', '',  $raw_domain );
	$raw_domain = rtrim( $raw_domain, '/' );
	$clean['site_domain'] = sanitize_text_field( $raw_domain );

	$clean['defer_script'] = ! empty( $input['defer_script'] ) ? '1' : '';

	// Excluded page types – whitelist against known keys
	$allowed_types         = array_keys( drtalks_ai_exclusion_types() );
	$submitted_types       = isset( $input['exclude_types'] ) && is_array( $input['exclude_types'] )
		? $input['exclude_types']
		: [];
	$clean['exclude_types'] = array_values( array_intersect( $submitted_types, $allowed_types ) );

	// Excluded URLs – sanitize line by line
	$raw_urls = isset( $input['exclude_urls'] ) ? $input['exclude_urls'] : '';
	$lines    = explode( "\n", $raw_urls );
	$safe     = [];
	foreach ( $lines as $line ) {
		$line = trim( sanitize_text_field( $line ) );
		if ( ! empty( $line ) ) {
			$safe[] = $line;
		}
	}
	$clean['exclude_urls'] = implode( "\n", $safe );

	return $clean;
}

// ─── Plugins page: add Settings link ─────────────────────────────────────────

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'drtalks_ai_action_links' );

function drtalks_ai_action_links( $links ) {
	$settings_link = '<a href="' . esc_url( admin_url( 'options-general.php?page=drtalks-ai' ) ) . '">' . esc_html__( 'Settings', 'drtalks-ai' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}

// ─── Admin: settings page ─────────────────────────────────────────────────────

add_action( 'admin_menu', 'drtalks_ai_add_menu' );

function drtalks_ai_add_menu() {
	add_options_page(
		__( 'DrTalks AI Settings', 'drtalks-ai' ),
		__( 'DrTalks AI', 'drtalks-ai' ),
		'manage_options',
		'drtalks-ai',
		'drtalks_ai_settings_page'
	);
}

function drtalks_ai_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$options   = get_option( DRTALKS_AI_OPTION, [] );
	$widget_id = isset( $options['widget_id'] ) ? $options['widget_id'] : '';

	// Default to the current site's hostname when the domain has never been saved
	if ( ! empty( $options['site_domain'] ) ) {
		$domain = $options['site_domain'];
	} else {
		$host   = wp_parse_url( get_site_url(), PHP_URL_HOST );
		$host   = preg_replace( '#^www\.#i', '', $host );
		$domain = $host ? $host : '';
	}

	// Default defer to true when the option has never been saved
	$defer          = isset( $options['defer_script'] ) ? ! empty( $options['defer_script'] ) : true;
	$ready          = ! empty( $widget_id ) && ! empty( $domain );
	$excluded_types = isset( $options['exclude_types'] ) && is_array( $options['exclude_types'] ) ? $options['exclude_types'] : [];
	$exclude_urls   = isset( $options['exclude_urls'] ) ? $options['exclude_urls'] : '';

	$home_link     = '<a href="https://drtalks.ai" target="_blank" rel="noopener">drtalks.ai</a>';
	$api_keys_link = '<a href="https://drtalks.ai/api-keys" target="_blank" rel="noopener">drtalks.ai/api-keys</a>';

	?>
	<div class="wrap" id="drtalks-ai-wrap">

		<h1><?php esc_html_e( 'DrTalks AI – Chat Widget Settings', 'drtalks-ai' ); ?></h1>

		<p style="max-width:640px;color:#555;">
			<?php echo wp_kses_post( __( 'This plugin adds the <strong>DrTalks AI chat widget</strong> to every page of your website. To set it up you need two pieces of information from your DrTalks account — a <strong>Widget ID</strong> and your <strong>site domain</strong>.', 'drtalks-ai' ) ); ?>
		</p>

		<?php settings_errors( DRTALKS_AI_OPTION ); ?>

		<?php if ( $ready ) : ?>
		<div class="notice notice-info is-dismissible">
			<p><?php echo wp_kses_post( __( 'Your widget is <strong>active</strong>. The script below is now being added to every page.', 'drtalks-ai' ) ); ?></p>
		</div>
		<?php else : ?>
		<div class="notice notice-warning">
			<p><?php echo wp_kses_post( __( 'The widget is <strong>not yet active</strong> — please enter your <strong>Widget ID</strong> and <strong>Website Domain</strong> below and save.', 'drtalks-ai' ) ); ?></p>
		</div>
		<?php endif; ?>

		<!-- ─ Step 1: get credentials ─────────────────────────────────────── -->
		<div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:20px 24px;max-width:680px;margin:24px 0 0;">
			<h2 style="margin-top:0;font-size:16px;"><?php esc_html_e( 'Step 1 — Find your Widget ID and domain', 'drtalks-ai' ); ?></h2>
			<ol style="margin:0 0 0 18px;padding:0;line-height:1.9;">
				<li>
					<?php
					/* translators: %s: link to the DrTalks website (drtalks.ai). */
					echo wp_kses_post( sprintf( __( 'Log in to your DrTalks account at %s.', 'drtalks-ai' ), $home_link ) );
					?>
				</li>
				<li>
					<?php
					/* translators: %s: link to the DrTalks API keys page (drtalks.ai/api-keys). */
					echo wp_kses_post( sprintf( __( 'Go to <strong>%s</strong>.', 'drtalks-ai' ), $api_keys_link ) );
					?>
				</li>
				<li>
					<?php
					/* translators: %s: example Widget ID, e.g. widget_yoursite-com_xxxxxxxx. */
					echo wp_kses_post( sprintf( __( 'Copy the <strong>Widget ID</strong> listed for your website — it looks like %s.', 'drtalks-ai' ), '<code>widget_yoursite-com_xxxxxxxx</code>' ) );
					?>
				</li>
				<li>
					<?php
					/* translators: %s: example website URL, e.g. https://yoursite.com. */
					echo wp_kses_post( sprintf( __( 'Copy the <strong>Website URL</strong> listed next to your widget — it looks like %s.', 'drtalks-ai' ), '<code>https://yoursite.com</code>' ) );
					?>
				</li>
			</ol>
		</div>

		<!-- ─ Step 2: settings form ───────────────────────────────────────── -->
		<div style="background:#fff;border:1px solid #ddd;border-radius:6px;padding:20px 24px;max-width:680px;margin:16px 0 0;">
			<h2 style="margin-top:0;font-size:16px;"><?php esc_html_e( 'Step 2 — Enter your details below', 'drtalks-ai' ); ?></h2>

			<form method="post" action="options.php">
				<?php settings_fields( DRTALKS_AI_OPTION ); ?>

				<table class="form-table" role="presentation" style="margin-top:0;">

					<tr>
						<th scope="row">
							<label for="drtalks_widget_id"><?php esc_html_e( 'Widget ID', 'drtalks-ai' ); ?></label>
						</th>
						<td>
							<input
								type="text"
								id="drtalks_widget_id"
								name="<?php echo esc_attr( DRTALKS_AI_OPTION ); ?>[widget_id]"
								value="<?php echo esc_attr( $widget_id ); ?>"
								class="regular-text"
								placeholder="widget_yoursite-com_xxxxxxxx"
								autocomplete="off"
							/>
							<p class="description">
								<?php
								/* translators: %s: link to the DrTalks API keys page (drtalks.ai/api-keys). */
								echo wp_kses_post( sprintf( __( 'Copy this exactly from %s.', 'drtalks-ai' ), $api_keys_link ) );
								?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="drtalks_site_domain"><?php esc_html_e( 'Website Domain', 'drtalks-ai' ); ?></label>
						</th>
						<td>
							<input
								type="text"
								id="drtalks_site_domain"
								name="<?php echo esc_attr( DRTALKS_AI_OPTION ); ?>[site_domain]"
								value="<?php echo esc_attr( $domain ); ?>"
								class="regular-text"
								placeholder="yoursite.com"
								autocomplete="off"
							/>
							<p class="description">
								<?php
								echo wp_kses_post(
									sprintf(
										/* translators: 1: link to the DrTalks API keys page, 2: "http://", 3: "https://", 4: "www.". */
										__( 'Copy the <strong>Website URL</strong> shown next to your widget on %1$s. You can paste the full URL — the plugin will automatically strip %2$s, %3$s, and %4$s for you.', 'drtalks-ai' ),
										$api_keys_link,
										'<code>http://</code>',
										'<code>https://</code>',
										'<code>www.</code>'
									)
								);
								?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row"><?php esc_html_e( 'Script loading', 'drtalks-ai' ); ?></th>
						<td>
							<label>
								<input
									type="checkbox"
									name="<?php echo esc_attr( DRTALKS_AI_OPTION ); ?>[defer_script]"
									value="1"
									<?php checked( $defer, true ); ?>
								/>
								<?php
								/* translators: %s: the word "defer" formatted as code. */
								echo wp_kses_post( sprintf( __( 'Load script with %s <em>(recommended)</em>', 'drtalks-ai' ), '<code>defer</code>' ) );
								?>
							</label>
							<p class="description">
								<?php
								/* translators: %s: the word "defer" formatted as code. */
								echo wp_kses_post( sprintf( __( '%s lets the page load first and then fetches the widget in the background, which is better for page speed. Only uncheck this if you have a specific reason to.', 'drtalks-ai' ), '<code>defer</code>' ) );
								?>
							</p>
						</td>
					</tr>

				</table>

				<!-- ─ Exclusions ───────────────────────────────────────────── -->
				<hr style="margin:24px 0;">
				<h3 style="margin-top:0;"><?php esc_html_e( 'Hide widget on specific pages', 'drtalks-ai' ); ?></h3>
				<p style="color:#555;margin-bottom:16px;">
					<?php esc_html_e( "The widget loads on every page by default. Use the options below to prevent it from appearing on pages where it isn't appropriate.", 'drtalks-ai' ); ?>
				</p>

				<table class="form-table" role="presentation" style="margin-top:0;">

					<tr>
						<th scope="row"><?php esc_html_e( 'Page types', 'drtalks-ai' ); ?></th>
						<td>
							<?php foreach ( drtalks_ai_exclusion_types() as $key => $label ) : ?>
							<label style="display:block;margin-bottom:6px;">
								<input
									type="checkbox"
									name="<?php echo esc_attr( DRTALKS_AI_OPTION ); ?>[exclude_types][]"
									value="<?php echo esc_attr( $key ); ?>"
									<?php checked( in_array( $key, $excluded_types, true ), true ); ?>
								/>
								<?php echo esc_html( $label ); ?>
							</label>
							<?php endforeach; ?>
							<p class="description" style="margin-top:8px;">
								<?php echo wp_kses_post( __( 'Tick any page types where the chat widget should <strong>not</strong> appear.', 'drtalks-ai' ) ); ?>
							</p>
						</td>
					</tr>

					<tr>
						<th scope="row">
							<label for="drtalks_exclude_urls"><?php esc_html_e( 'Specific URLs', 'drtalks-ai' ); ?></label>
						</th>
						<td>
							<textarea
								id="drtalks_exclude_urls"
								name="<?php echo esc_attr( DRTALKS_AI_OPTION ); ?>[exclude_urls]"
								rows="6"
								class="large-text code"
								placeholder="/members/&#10;/contact/&#10;https://yoursite.com/some-page/"
							><?php echo esc_textarea( $exclude_urls ); ?></textarea>
							<p class="description">
								<?php
								echo wp_kses_post(
									sprintf(
										/* translators: 1: example full URL, 2: example path, 3: wildcard character, 4: example wildcard rule, 5: first example matched path, 6: second example matched path. */
										__( 'Enter one URL or path per line. You can use a full URL (%1$s) or just the path (%2$s). Trailing slashes are ignored.<br>Use %3$s as a wildcard to match any sub-path — for example %4$s will hide the widget on %5$s, %6$s, and so on.', 'drtalks-ai' ),
										'<code>https://yoursite.com/contact/</code>',
										'<code>/contact/</code>',
										'<code>*</code>',
										'<code>/my-account/*</code>',
										'<code>/my-account/orders/</code>',
										'<code>/my-account/edit-address/</code>'
									)
								);
								?>
							</p>
						</td>
					</tr>

				</table>

				<?php submit_button( __( 'Save Settings', 'drtalks-ai' ) ); ?>

			</form>
		</div>

		<?php if ( $ready ) : ?>
		<!-- ─ Preview ─────────────────────────────────────────────────────── -->
		<div style="background:#f6f7f7;border:1px solid #ddd;border-radius:6px;padding:20px 24px;max-width:680px;margin:16px 0 24px;">
			<h2 style="margin-top:0;font-size:16px;"><?php esc_html_e( "Preview — code added to your site's <head>", 'drtalks-ai' ); ?></h2>
			<pre style="background:#1e1e1e;color:#d4d4d4;padding:16px;border-radius:4px;overflow-x:auto;font-size:13px;line-height:1.6;margin:0;"><?php
$defer_preview = $defer ? "\n        defer" : '';
echo esc_html(
	"<!-- DrTalks Chat Widget -->\n"
	// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript -- Display-only text shown inside <pre> and escaped with esc_html(); it is never output as a script tag. The real script is enqueued in drtalks_ai_inject_widget().
	. "<script src=\"" . DRTALKS_AI_CDN . "\"{$defer_preview}\n"
	. "        data-widget-id=\"{$widget_id}\"\n"
	. "        data-site-domain=\"{$domain}\">\n"
	. "</script>\n"
	. "<!-- End DrTalks Chat Widget -->"
);
			?></pre>
			<p style="margin:10px 0 0;color:#666;font-size:13px;">
				<?php echo wp_kses_post( __( 'This widget is currently <strong>active</strong> on your site.', 'drtalks-ai' ) ); ?>
			</p>
		</div>
		<?php endif; ?>

	</div>
	<?php
}
