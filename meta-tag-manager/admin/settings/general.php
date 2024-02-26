<?php
global $mtm_submit_button;
$mtm_custom = get_option('mtm_custom');
?>
<div class="mtm-menu-general mtm-menu-group"  style="display:none;">
	<div id="mtm-post-types" class="mtm-post-types">
		<h3><?php esc_html_e('Post Type Support', 'meta-tag-manager'); ?></h3>
		<p><?php echo sprintf(esc_html__('Enable the meta tag builder on the edit pages of your selected %1$s below. This will allow you to create specific %1$s for specific %2$s on your site. Leave blank for all %1$s.', 'meta-tag-manager'), esc_html__('post types', 'meta-tag-manager'),  esc_html__('post types', 'meta-tag-manager')); ?></p>
		<?php
		//Post Types
		$post_type_options = array();
		foreach( get_post_types(array('public'=>true), 'objects') as $post_type){
			$post_type_options[$post_type->labels->name] = $post_type->name;
		}
		?>
		<select name="mtm-post-types[]" class="mtm-post-types-select" multiple>
			<option value=""><?php echo sprintf(esc_html__('choose one or more %s', 'mtm-pro'), esc_html__('post types', 'meta-tag-manager')); ?></option>
			<?php
			echo MTM_Builder::output_select_options($post_type_options, $mtm_custom['post-types']);
			?>
		</select>
	</div>
	<?php do_action('mtm_settings_page_general'); ?>
	<?php echo $mtm_submit_button; ?>
</div>