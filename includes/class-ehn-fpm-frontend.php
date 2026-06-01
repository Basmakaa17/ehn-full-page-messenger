<?php
/**
 * Templates, assets, shortcode.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class EHN_FPM_Frontend {

	private static $shortcode_maybe = false;
	private static $shortcode_rendered = false;

	public static function init(): void {
		add_filter('theme_page_templates', array(__CLASS__, 'register_template'));
		add_filter('template_include', array(__CLASS__, 'template_include'), 99);
		add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'), 20);
		add_filter('the_posts', array(__CLASS__, 'detect_shortcode_in_posts'), 10, 2);
		add_shortcode('ehn_full_page_chat', array(__CLASS__, 'shortcode'));
	}

	public static function register_template(array $templates): array {
		$templates[ EHN_FPM_TEMPLATE_REL ] = __('Full Page Messenger (EHN)', 'ehn-full-page-messenger');
		return $templates;
	}

	public static function template_include(string $template): string {
		if (! is_page()) {
			return $template;
		}
		$post_id = get_queried_object_id();
		if (! self::page_uses_messenger_template($post_id)) {
			return $template;
		}
		$plugin_template = EHN_FPM_PATH . 'templates/full-page-messenger.php';
		return file_exists($plugin_template) ? $plugin_template : $template;
	}

	public static function detect_shortcode_in_posts(array $posts, WP_Query $query): array {
		if (! $query->is_main_query() || is_admin()) {
			return $posts;
		}
		foreach ($posts as $post) {
			if (! $post instanceof WP_Post) {
				continue;
			}
			$content = (string) $post->post_content;
			if (has_shortcode($content, 'ehn_full_page_chat') || strpos($content, '[ehn_full_page_chat') !== false) {
				self::$shortcode_maybe = true;
				break;
			}
		}
		return $posts;
	}

	/**
	 * True when the page is assigned the plugin template or the old theme template path.
	 */
	private static function page_uses_messenger_template(int $post_id): bool {
		if ($post_id <= 0) {
			return false;
		}
		$assigned = (string) get_post_meta($post_id, '_wp_page_template', true);
		return $assigned === EHN_FPM_TEMPLATE_REL || $assigned === EHN_FPM_LEGACY_TEMPLATE_REL;
	}

	private static function wants_assets(): bool {
		if (! is_singular('page')) {
			return false;
		}
		$post_id = get_queried_object_id();
		if (self::page_uses_messenger_template($post_id)) {
			return true;
		}
		return self::$shortcode_maybe;
	}

	public static function enqueue_assets(): void {
		$allow = self::wants_assets();
		$run   = apply_filters('ehn_full_page_messenger_should_enqueue', $allow);
		if (! $run) {
			return;
		}

		$o = EHN_FPM_Plugin::get_options();

		wp_register_style(
			'ehn-fpm-chat',
			EHN_FPM_URL . 'assets/css/chat.css',
			array(),
			EHN_FPM_VERSION
		);

		$vars = self::build_css_variables($o);
		wp_add_inline_style('ehn-fpm-chat', $vars);

		wp_enqueue_style('ehn-fpm-chat');

		wp_register_script(
			'ehn-fpm-chat',
			EHN_FPM_URL . 'assets/js/chat.js',
			array(),
			EHN_FPM_VERSION,
			true
		);

		$cookie_secure = apply_filters('ehn_full_page_messenger_cookie_secure', is_ssl());

		wp_localize_script(
			'ehn-fpm-chat',
			'ehnFullPageMessenger',
			array(
				'genesysBootstrapUrl' => $o['genesys_bootstrap_url'],
				'genesysEnvironment'  => $o['genesys_environment'],
				'genesysDeploymentId' => $o['genesys_deployment_id'],
				'notificationIconUrl' => $o['notification_icon_url'],
				'cookieSecure'        => (bool) $cookie_secure,
			)
		);

		wp_enqueue_script('ehn-fpm-chat');
	}

	private static function build_css_variables(array $o): string {
		$d        = EHN_FPM_Plugin::defaults();
		$hex      = static function (string $key) use ($o, $d ): string {
			return EHN_FPM_Plugin::sanitize_hex_or_default($o[ $key ] ?? '', $d[ $key ]);
		};
		$avatar   = esc_url_raw($o['avatar_url'] ?? '');
		$avatar_css = esc_attr($avatar !== '' ? $avatar : $d['avatar_url']);

		$lines = array(
			'#ehn-fpm-chat {',
			'--ehn-chat-primary: ' . $hex('primary_color') . ';',
			'--ehn-chat-primary-text: ' . $hex('primary_text_color') . ';',
			'--ehn-chat-send-hover: ' . $hex('send_hover_color') . ';',
			'--ehn-chat-incoming-bg: ' . $hex('incoming_bubble_bg') . ';',
			'--ehn-chat-incoming-text: ' . $hex('incoming_bubble_text') . ';',
			'--ehn-chat-border: ' . $hex('border_color') . ';',
			'--ehn-chat-quick-bg: ' . $hex('quick_reply_bg') . ';',
			'--ehn-chat-quick-text: ' . $hex('quick_reply_text') . ';',
			'--ehn-chat-quick-border: ' . $hex('quick_reply_border') . ';',
			'--ehn-chat-quick-hover: ' . $hex('quick_reply_hover_bg') . ';',
			'--ehn-chat-system: ' . $hex('system_message_color') . ';',
			'--ehn-chat-readout: ' . $hex('readout_color') . ';',
			'--ehn-chat-badge-bg: ' . $hex('notification_badge_color') . ';',
			'--ehn-chat-badge-text: ' . $hex('notification_badge_text') . ';',
			'--ehn-chat-avatar-url: url(\'' . $avatar_css . '\');',
			'}',
		);

		return implode("\n", $lines);
	}

	public static function shortcode(): string {
		return self::render_chat_shell(true);
	}

	/**
	 * Output chat markup once per request (template or shortcode).
	 *
	 * @param bool $is_shortcode When true, show an editor notice on duplicate shortcode use.
	 */
	public static function render_chat_shell(bool $is_shortcode = false): string {
		if (self::$shortcode_rendered) {
			if ($is_shortcode && current_user_can('edit_posts')) {
				return '<p class="ehn-fpm-chat__admin-notice">' . esc_html__(
					'EHN Full Page Messenger: only one chat instance is allowed per page.',
					'ehn-full-page-messenger'
				) . '</p>';
			}
			return '';
		}

		self::$shortcode_rendered = true;
		self::$shortcode_maybe    = true;

		ob_start();
		require EHN_FPM_PATH . 'templates/partials/chat-shell.php';
		return ob_get_clean();
	}
}
