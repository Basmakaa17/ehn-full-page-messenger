<?php
/**
 * Bootstrap: options helper, loads settings and front-end.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class EHN_FPM_Plugin {

	const OPTION_KEY = 'ehn_full_page_messenger';

	public static function init(): void {
		require_once EHN_FPM_PATH . 'includes/class-ehn-fpm-settings.php';
		require_once EHN_FPM_PATH . 'includes/class-ehn-fpm-frontend.php';

		EHN_FPM_Settings::init();
		EHN_FPM_Frontend::init();
	}

	public static function defaults(): array {
		return array(
			'primary_color'             => '#c8d873',
			'primary_text_color'        => '#000000',
			'send_hover_color'          => '#0056b3',
			'incoming_bubble_bg'        => '#f1f0f0',
			'incoming_bubble_text'      => '#000000',
			'border_color'              => '#cccccc',
			'quick_reply_bg'            => '#fafafa',
			'quick_reply_text'          => '#000000',
			'quick_reply_border'        => '#000000',
			'quick_reply_hover_bg'      => '#eeeeee',
			'system_message_color'      => '#555555',
			'readout_color'             => '#666666',
			'notification_badge_color'    => '#ff0000',
			'notification_badge_text'     => '#ffffff',
			'avatar_url'                => 'https://placehold.co/40/eee/000000?text=EHN',
			'notification_icon_url'     => '',
			'input_placeholder'         => 'Type a message...',
			'send_button_label'         => 'Send',
			'genesys_environment'       => 'prod-cac1',
			'genesys_deployment_id'     => '06c61b03-8bae-4b1b-ba1d-77b81108bbfd',
			'genesys_bootstrap_url'     => 'https://apps.cac1.pure.cloud/genesys-bootstrap/genesys.min.js',
		);
	}

	public static function get_options(): array {
		$stored = get_option(self::OPTION_KEY, array());
		if (!is_array($stored)) {
			$stored = array();
		}
		$merged = wp_parse_args($stored, self::defaults());
		return apply_filters('ehn_full_page_messenger_options', $merged);
	}

	public static function sanitize_hex_or_default(?string $value, string $default): string {
		if ($value === null || $value === '') {
			return $default;
		}
		$hex = sanitize_hex_color($value);
		return $hex !== '' ? $hex : $default;
	}
}
