<?php
/**
 * Removes DrTalks AI settings when the plugin is deleted.
 *
 * @package DrTalks_AI
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'drtalks_ai_chat_settings' );
