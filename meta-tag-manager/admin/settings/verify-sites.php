<?php
global $mtm_submit_button;
$verify = \Meta_Tag_Manager\Verify_Sites::get_options(false);
?>
<p><?php esc_html_e('Many websites or search engines offer ways to view private information about your domain or alternatively display your site as \'verified\' on profile pages etc.', 'meta-tag-manager'); ?></p>
<p><?php esc_html_e('Most platforms offer meta tag codes as a form of verification, below are some services where you can quickly post the ID value of a meta tag and let Meta Tag Manager do the rest!', 'meta-tag-manager'); ?></p>
<p><?php echo sprintf(esc_html__('Don\'t see the site you want to verify with here? You can always manually generate tags in the %s tab above.', 'meta-tag-manager'), esc_html__('Custom Meta Tags','meta-tag-manager')); ?></p>
<p><?php echo sprintf(esc_html__('Please paste the value in the %s part of the supplied meta tag, for example the bold part of %s.', 'meta-tag-manager'), '<code>contents="..."</code>', '<code>&lt;meta name="google-site-verification" contents="<strong>your_unique_id</strong>" /&gt;</code>'); ?></p>
<table class="form-table">
	<?php
	mtm_input_text( esc_html__('Google Webmaster Tools', 'meta-tag-manager'), 'mtm_verify_sites_google', $verify['google'] );
	mtm_input_text( esc_html__('Bing Webmaster Tools', 'meta-tag-manager'), 'mtm_verify_sites_bing', $verify['bing'] );
	mtm_input_text( esc_html__('Facebook Business', 'meta-tag-manager'), 'mtm_verify_sites_facebook', $verify['facebook'] );
	mtm_input_text( esc_html__('Pintrest', 'meta-tag-manager'), 'mtm_verify_sites_pintrest', $verify['pintrest'] );
	mtm_input_text( esc_html__('Sitelock', 'meta-tag-manager'), 'mtm_verify_sites_sitelock', $verify['sitelock'] );
	mtm_input_text( esc_html__('Yandex', 'meta-tag-manager'), 'mtm_verify_sites_yandex', $verify['yandex'] );
	?>
</table>
<?php do_action('mtm_settings_page_site_verification'); ?>
<?php echo $mtm_submit_button; ?>