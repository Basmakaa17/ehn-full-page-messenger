<?php
/**
 * Chat markup (template + shortcode). One instance per page.
 *
 * @package EHN_Full_Page_Messenger
 */

if (!defined('ABSPATH')) {
	exit;
}

$o           = EHN_FPM_Plugin::get_options();
$placeholder = esc_attr($o['input_placeholder']);
$send_label  = esc_html($o['send_button_label']);
?>
<div id="ehn-fpm-chat" class="ehn-fpm-chat">
	<div id="ehn-fpm-chat-body" class="ehn-fpm-chat__body"></div>
	<div id="ehn-fpm-system-message" class="ehn-fpm-chat__system-message" aria-live="polite"></div>
	<div class="ehn-fpm-chat__input-row">
		<textarea id="ehn-fpm-input" class="ehn-fpm-chat__input" placeholder="<?php echo $placeholder; ?>" rows="1"></textarea>
		<button type="button" id="ehn-fpm-send" class="ehn-fpm-chat__send"><?php echo $send_label; ?></button>
	</div>
</div>
