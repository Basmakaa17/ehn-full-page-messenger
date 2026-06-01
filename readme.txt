=== EHN Full Page Messenger ===
Contributors: ehn
Requires at least: 6.0
Tested up to: 6.8
Stable tag: 1.0.1
License: GPLv2 or later

Full-page Genesys web messaging UI with configurable branding.

== Description ==

Assign the "Full Page Messenger (EHN)" template to any page, or use the shortcode [ehn_full_page_chat]. Configure colours, avatar URL, and Genesys deployment under Settings → EHN Messenger.

Developer filters:
- ehn_full_page_messenger_options
- ehn_full_page_messenger_should_enqueue
- ehn_full_page_messenger_cookie_secure

== Changelog ==

= 1.0.1 =
* Prefixed all chat DOM IDs and CSS classes (`ehn-fpm-*`) to avoid theme conflicts.
* Admin color-picker script moved to a dedicated asset (settings page only).
* Shortcode and template limited to one chat instance per page.
* Removed legacy theme template; use plugin template or `[ehn_full_page_chat]`.

= 1.0.0 =
* Initial release (extracted from theme template).
