<?php
/**
 * Plugin Name: EHN Full Page Messenger
 * Description: Full-page Genesys web messaging UI with per-site branding and template support.
 * Version: 1.0.1
 * Author: EHN
 * Text Domain: ehn-full-page-messenger
 *
 * Hooks for developers:
 * - `ehn_full_page_messenger_options` (array): Filter merged plugin options after defaults.
 * - `ehn_full_page_messenger_should_enqueue` (bool): Return false to skip loading assets on a request.
 * - `ehn_full_page_messenger_cookie_secure` (bool): Cookie Secure flag; defaults to `is_ssl()`.
 */

if (!defined('ABSPATH')) {
	exit;
}

define('EHN_FPM_VERSION', '1.0.1');
define('EHN_FPM_PATH', plugin_dir_path(__FILE__));
define('EHN_FPM_URL', plugin_dir_url(__FILE__));
define('EHN_FPM_TEMPLATE_REL', 'ehn-full-page-messenger/templates/full-page-messenger.php');
/** @var string Theme template path before messenger moved to this plugin (still stored on existing pages). */
define('EHN_FPM_LEGACY_TEMPLATE_REL', 'page-templates/full-page-messenger.php');

require_once EHN_FPM_PATH . 'includes/class-ehn-fpm-plugin.php';

EHN_FPM_Plugin::init();
