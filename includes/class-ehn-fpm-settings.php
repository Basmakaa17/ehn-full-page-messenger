<?php
/**
 * Settings page under Settings → EHN Messenger.
 */

if (!defined('ABSPATH')) {
	exit;
}

final class EHN_FPM_Settings {

	const PAGE_SLUG = 'ehn-full-page-messenger';
	const GROUP     = 'ehn_fpm_settings_group';

	public static function init(): void {
		add_action('admin_menu', array(__CLASS__, 'admin_menu'));
		add_action('admin_init', array(__CLASS__, 'register'));
		add_action('admin_init', array(__CLASS__, 'maybe_reset'));
		add_action('admin_init', array(__CLASS__, 'maybe_dismiss_orphan_notice'));
		add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));

		add_action('admin_notices', array(__CLASS__, 'orphaned_theme_template_notice'));
	}

	public static function enqueue_admin_assets(string $hook_suffix): void {
		if ($hook_suffix !== 'settings_page_' . self::PAGE_SLUG) {
			return;
		}

		wp_enqueue_style('wp-color-picker');
		wp_enqueue_script(
			'ehn-fpm-admin-settings',
			EHN_FPM_URL . 'assets/js/admin-settings.js',
			array('jquery', 'wp-color-picker'),
			EHN_FPM_VERSION,
			true
		);
	}

	public static function maybe_dismiss_orphan_notice(): void {
		if (!isset($_GET['ehn_fpm_dismiss_orphan'], $_GET['_wpnonce'])) {
			return;
		}
		if (!current_user_can('edit_pages')) {
			return;
		}
		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'ehn_fpm_dismiss_orphan')) {
			return;
		}
		update_user_meta(get_current_user_id(), 'ehn_fpm_dismiss_orphan_notice', '1');
		wp_safe_redirect(remove_query_arg(array('ehn_fpm_dismiss_orphan', '_wpnonce')));
		exit;
	}

	public static function admin_menu(): void {
		add_options_page(
			__('EHN Messenger', 'ehn-full-page-messenger'),
			__('EHN Messenger', 'ehn-full-page-messenger'),
			'manage_options',
			self::PAGE_SLUG,
			array(__CLASS__, 'render_page')
		);
	}

	public static function register(): void {
		register_setting(
			self::GROUP,
			EHN_FPM_Plugin::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array(__CLASS__, 'sanitize_options'),
				'default'           => EHN_FPM_Plugin::defaults(),
			)
		);
	}

	public static function maybe_reset(): void {
		if (!isset($_POST['ehn_fpm_reset_defaults']) || !isset($_POST['_wpnonce'])) {
			return;
		}
		if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'ehn_fpm_reset_defaults')) {
			return;
		}
		if (!current_user_can('manage_options')) {
			return;
		}
		update_option(EHN_FPM_Plugin::OPTION_KEY, EHN_FPM_Plugin::defaults());
		wp_safe_redirect(add_query_arg('ehn_fpm_reset', '1', menu_page_url(self::PAGE_SLUG, false)));
		exit;
	}

	public static function sanitize_options($input): array {
		if (!is_array($input)) {
			return EHN_FPM_Plugin::defaults();
		}
		$d = EHN_FPM_Plugin::defaults();

		$out = array(
			'primary_color'             => EHN_FPM_Plugin::sanitize_hex_or_default(isset($input['primary_color']) ? (string) $input['primary_color'] : '', $d['primary_color']),
			'primary_text_color'        => EHN_FPM_Plugin::sanitize_hex_or_default(isset($input['primary_text_color']) ? (string) $input['primary_text_color'] : '', $d['primary_text_color']),
			'send_hover_color'          => EHN_FPM_Plugin::sanitize_hex_or_default(isset($input['send_hover_color']) ? (string) $input['send_hover_color'] : '', $d['send_hover_color']),
			'incoming_bubble_bg'        => EHN_FPM_Plugin::sanitize_hex_or_default(isset($input['incoming_bubble_bg']) ? (string) $input['incoming_bubble_bg'] : '', $d['incoming_bubble_bg']),
			'incoming_bubble_text'      => EHN_FPM_Plugin::sanitize_hex_or_default(isset($input['incoming_bubble_text']) ? (string) $input['incoming_bubble_text'] : '', $d['incoming_bubble_text']),
			'border_color'              => EHN_FPM_Plugin::sanitize_hex_or_default(isset($input['border_color']) ? (string) $input['border_color'] : '', $d['border_color']),
			'quick_reply_bg'            => EHN_FPM_Plugin::sanitize_hex_or_default(isset($input['quick_reply_bg']) ? (string) $input['quick_reply_bg'] : '', $d['quick_reply_bg']),
			'quick_reply_text'          => EHN_FPM_Plugin::sanitize_hex_or_default(isset($input['quick_reply_text']) ? (string) $input['quick_reply_text'] : '', $d['quick_reply_text']),
			'quick_reply_border'        => EHN_FPM_Plugin::sanitize_hex_or_default(isset($input['quick_reply_border']) ? (string) $input['quick_reply_border'] : '', $d['quick_reply_border']),
			'quick_reply_hover_bg'      => EHN_FPM_Plugin::sanitize_hex_or_default(isset($input['quick_reply_hover_bg']) ? (string) $input['quick_reply_hover_bg'] : '', $d['quick_reply_hover_bg']),
			'system_message_color'      => EHN_FPM_Plugin::sanitize_hex_or_default(isset($input['system_message_color']) ? (string) $input['system_message_color'] : '', $d['system_message_color']),
			'readout_color'             => EHN_FPM_Plugin::sanitize_hex_or_default(isset($input['readout_color']) ? (string) $input['readout_color'] : '', $d['readout_color']),
			'notification_badge_color'  => EHN_FPM_Plugin::sanitize_hex_or_default(isset($input['notification_badge_color']) ? (string) $input['notification_badge_color'] : '', $d['notification_badge_color']),
			'notification_badge_text'   => EHN_FPM_Plugin::sanitize_hex_or_default(isset($input['notification_badge_text']) ? (string) $input['notification_badge_text'] : '', $d['notification_badge_text']),
			'avatar_url'                => ! empty($input['avatar_url']) ? esc_url_raw((string) $input['avatar_url']) : $d['avatar_url'],
			'notification_icon_url'     => isset($input['notification_icon_url']) ? esc_url_raw((string) $input['notification_icon_url']) : '',
			'input_placeholder'         => isset($input['input_placeholder']) ? sanitize_text_field((string) $input['input_placeholder']) : $d['input_placeholder'],
			'send_button_label'         => isset($input['send_button_label']) ? sanitize_text_field((string) $input['send_button_label']) : $d['send_button_label'],
			'genesys_environment'       => isset($input['genesys_environment']) ? sanitize_text_field((string) $input['genesys_environment']) : $d['genesys_environment'],
			'genesys_deployment_id'     => isset($input['genesys_deployment_id']) ? sanitize_text_field((string) $input['genesys_deployment_id']) : $d['genesys_deployment_id'],
			'genesys_bootstrap_url'     => isset($input['genesys_bootstrap_url']) ? esc_url_raw((string) $input['genesys_bootstrap_url']) : $d['genesys_bootstrap_url'],
		);

		if ($out['genesys_bootstrap_url'] === '') {
			$out['genesys_bootstrap_url'] = $d['genesys_bootstrap_url'];
		}

		return $out;
	}

	public static function orphaned_theme_template_notice(): void {
		if (!current_user_can('edit_pages')) {
			return;
		}
		if (get_user_meta(get_current_user_id(), 'ehn_fpm_dismiss_orphan_notice', true) === '1') {
			return;
		}

		$ids = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'any',
				'posts_per_page' => 20,
				'fields'         => 'ids',
				'meta_key'       => '_wp_page_template',
				'meta_value'     => 'page-templates/full-page-messenger.php',
				'suppress_filters' => true,
			)
		);

		if (empty($ids)) {
			return;
		}

		$dismiss_url = wp_nonce_url(add_query_arg('ehn_fpm_dismiss_orphan', '1'), 'ehn_fpm_dismiss_orphan');
		?>
		<div class="notice notice-warning is-dismissible">
			<p>
				<strong><?php esc_html_e('EHN Full Page Messenger:', 'ehn-full-page-messenger'); ?></strong>
				<?php esc_html_e('One or more pages still use the old theme template path (page-templates/full-page-messenger.php). Edit each page and choose “Full Page Messenger (EHN)” from the Template dropdown after activating this plugin.', 'ehn-full-page-messenger'); ?>
			</p>
			<p>
				<?php
				foreach ($ids as $pid) {
					$link = get_edit_post_link($pid);
					if ($link) {
						echo '<a href="' . esc_url($link) . '">' . esc_html(get_the_title($pid)) . '</a> ';
					}
				}
				?>
			</p>
			<p><a href="<?php echo esc_url($dismiss_url); ?>"><?php esc_html_e('Dismiss this notice', 'ehn-full-page-messenger'); ?></a></p>
		</div>
		<?php
	}

	public static function render_page(): void {
		if (!current_user_can('manage_options')) {
			return;
		}

		if (isset($_GET['ehn_fpm_reset']) && $_GET['ehn_fpm_reset'] === '1') {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings reset to defaults.', 'ehn-full-page-messenger') . '</p></div>';
		}

		$o = EHN_FPM_Plugin::get_options();
		?>
		<div class="wrap">
			<h1><?php esc_html_e('EHN Messenger', 'ehn-full-page-messenger'); ?></h1>
			<p><?php esc_html_e('Assign the “Full Page Messenger (EHN)” template to a page, or use the shortcode [ehn_full_page_chat] on a page that loads scripts in the footer.', 'ehn-full-page-messenger'); ?></p>

			<form method="post" action="options.php">
				<?php settings_fields(self::GROUP); ?>

				<h2><?php esc_html_e('Branding', 'ehn-full-page-messenger'); ?></h2>
				<table class="form-table" role="presentation">
					<?php self::color_row('primary_color', __('Primary / accents', 'ehn-full-page-messenger'), $o); ?>
					<?php self::color_row('primary_text_color', __('Primary text', 'ehn-full-page-messenger'), $o); ?>
					<?php self::color_row('send_hover_color', __('Send button hover', 'ehn-full-page-messenger'), $o); ?>
					<?php self::color_row('incoming_bubble_bg', __('Incoming bubble background', 'ehn-full-page-messenger'), $o); ?>
					<?php self::color_row('incoming_bubble_text', __('Incoming bubble text', 'ehn-full-page-messenger'), $o); ?>
					<?php self::color_row('border_color', __('Borders', 'ehn-full-page-messenger'), $o); ?>
					<?php self::color_row('quick_reply_bg', __('Quick reply background', 'ehn-full-page-messenger'), $o); ?>
					<?php self::color_row('quick_reply_text', __('Quick reply text', 'ehn-full-page-messenger'), $o); ?>
					<?php self::color_row('quick_reply_border', __('Quick reply border', 'ehn-full-page-messenger'), $o); ?>
					<?php self::color_row('quick_reply_hover_bg', __('Quick reply hover', 'ehn-full-page-messenger'), $o); ?>
					<?php self::color_row('system_message_color', __('System message text', 'ehn-full-page-messenger'), $o); ?>
					<?php self::color_row('readout_color', __('Timestamp / readout', 'ehn-full-page-messenger'), $o); ?>
					<?php self::color_row('notification_badge_color', __('Notification badge background', 'ehn-full-page-messenger'), $o); ?>
					<?php self::color_row('notification_badge_text', __('Notification badge text', 'ehn-full-page-messenger'), $o); ?>
					<tr>
						<th scope="row"><label for="avatar_url"><?php esc_html_e('Incoming avatar image URL', 'ehn-full-page-messenger'); ?></label></th>
						<td><input type="url" class="large-text" id="avatar_url" name="<?php echo esc_attr(EHN_FPM_Plugin::OPTION_KEY); ?>[avatar_url]" value="<?php echo esc_attr($o['avatar_url']); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="notification_icon_url"><?php esc_html_e('Browser notification icon URL', 'ehn-full-page-messenger'); ?></label></th>
						<td><input type="url" class="large-text" id="notification_icon_url" name="<?php echo esc_attr(EHN_FPM_Plugin::OPTION_KEY); ?>[notification_icon_url]" value="<?php echo esc_attr($o['notification_icon_url']); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="input_placeholder"><?php esc_html_e('Input placeholder', 'ehn-full-page-messenger'); ?></label></th>
						<td><input type="text" class="regular-text" id="input_placeholder" name="<?php echo esc_attr(EHN_FPM_Plugin::OPTION_KEY); ?>[input_placeholder]" value="<?php echo esc_attr($o['input_placeholder']); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="send_button_label"><?php esc_html_e('Send button label', 'ehn-full-page-messenger'); ?></label></th>
						<td><input type="text" class="regular-text" id="send_button_label" name="<?php echo esc_attr(EHN_FPM_Plugin::OPTION_KEY); ?>[send_button_label]" value="<?php echo esc_attr($o['send_button_label']); ?>"></td>
					</tr>
				</table>

				<h2><?php esc_html_e('Genesys', 'ehn-full-page-messenger'); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="genesys_environment"><?php esc_html_e('Environment', 'ehn-full-page-messenger'); ?></label></th>
						<td><input type="text" class="regular-text" id="genesys_environment" name="<?php echo esc_attr(EHN_FPM_Plugin::OPTION_KEY); ?>[genesys_environment]" value="<?php echo esc_attr($o['genesys_environment']); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="genesys_deployment_id"><?php esc_html_e('Deployment ID', 'ehn-full-page-messenger'); ?></label></th>
						<td><input type="text" class="large-text" id="genesys_deployment_id" name="<?php echo esc_attr(EHN_FPM_Plugin::OPTION_KEY); ?>[genesys_deployment_id]" value="<?php echo esc_attr($o['genesys_deployment_id']); ?>"></td>
					</tr>
					<tr>
						<th scope="row"><label for="genesys_bootstrap_url"><?php esc_html_e('Bootstrap script URL', 'ehn-full-page-messenger'); ?></label></th>
						<td><input type="url" class="large-text" id="genesys_bootstrap_url" name="<?php echo esc_attr(EHN_FPM_Plugin::OPTION_KEY); ?>[genesys_bootstrap_url]" value="<?php echo esc_attr($o['genesys_bootstrap_url']); ?>"></td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>

			<form method="post" style="margin-top:1em;">
				<?php wp_nonce_field('ehn_fpm_reset_defaults'); ?>
				<input type="hidden" name="ehn_fpm_reset_defaults" value="1">
				<?php submit_button(__('Reset all settings to defaults', 'ehn-full-page-messenger'), 'secondary'); ?>
			</form>
		</div>
		<?php
	}

	private static function color_row(string $key, string $label, array $o): void {
		$name = EHN_FPM_Plugin::OPTION_KEY . '[' . $key . ']';
		$val  = isset($o[ $key ]) ? $o[ $key ] : '';
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
			<td>
				<input type="text" class="ehn-fpm-color-field" id="<?php echo esc_attr($key); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($val); ?>" data-default-color="<?php echo esc_attr(EHN_FPM_Plugin::defaults()[ $key ] ?? ''); ?>">
			</td>
		</tr>
		<?php
	}
}
