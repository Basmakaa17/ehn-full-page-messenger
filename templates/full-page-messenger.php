<?php
/**
 * Plugin page template: full-page messenger (uses active theme header/footer).
 *
 * @package EHN_Full_Page_Messenger
 */

if (!defined('ABSPATH')) {
	exit;
}

get_header();

echo EHN_FPM_Frontend::render_chat_shell(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

get_footer();
