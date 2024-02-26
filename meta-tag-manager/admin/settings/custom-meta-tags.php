<?php
global $mtm_submit_button;
?>
<div class="mtm-menu-custom-meta-tags mtm-menu-group">
	<p><?php esc_html_e('Use the meta tag builder to create meta tags which will be used on your site. You can choose what kind of meta tag to display, as well as where to display them such as on all your pages, just the home page or specific post types and taxonomies.', 'meta-tag-manager'); ?></p>
	<p><?php esc_html_e('You can also enter a reference name at the top of each field card (something to help you remember the meta tag) and then enter the values below it that you want the meta tag to hold.', 'meta-tag-manager'); ?></p>
	<p><?php echo sprintf(esc_html__('Certain tag values for the "name" type such as %s cannot be repeated, and the last duplicate tag will take precendence unless there is a tag defined for the specific post type being viewed.', 'meta-tag-manager'), '<code>keywords, description</code>'); ?></p>
	<p><?php esc_html_e('For adding meta tags to specific post types, please click on the \'General Options\' tab above.', 'meta-tag-manager'); ?></p>
	<?php MTM_Builder::output(Meta_Tag_Manager::get_data(), array('context'=>true, 'reference'=>true)); ?>
	<?php do_action('mtm_ettings_page_builder'); ?>
	<?php echo $mtm_submit_button; ?>
</div>