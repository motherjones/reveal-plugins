<?php
if( !defined('ABSPATH') ) exit;
Meta_Tag_Manager::load(); // load everything
global $MTM_Notices;

// if previously saved
if( !empty($_GET['saved']) ) {
	echo '<div id="message" class="updated fade"><p><strong>' . __('Settings saved.') . '</strong></p></div>'; // No textdomain: phrase used in core, too
}
// get tabs settings, in case we want to split them page by page rather than one page
if( defined('MTM_SETTINGS_TABS') && MTM_SETTINGS_TABS ){
	$tabs_enabled = true;
	$builder_tab_link = esc_url(add_query_arg( array('mtm_tab'=>'builder')));
	$general_tab_link = esc_url(add_query_arg( array('mtm_tab'=>'general')));
}else{
	$general_tab_link = $builder_tab_link = '';
}
// button for submission (reused)
global $mtm_submit_button;
$mtm_submit_button = '<p class="mtm-actions"><button type="submit" class="button-primary">'. esc_html__('Save Changes','meta-tag-manager') .'</button></p>';

?>
<div class="wrap tabs-active">
	<?php if( !empty($MTM_Notices) ) echo $MTM_Notices; ?>
	<h1><?php esc_html_e( 'Meta Tag Manager', 'meta-tag-manager' ); ?></h1>
	<h2 class="nav-tab-wrapper">
		<?php
		$tabs = $fixed_tabs = array(
			'custom-meta-tags' => esc_html__('Custom Meta Tags','meta-tag-manager'),
			'general' => esc_html__('General Options','meta-tag-manager'),
			'open-graph' => esc_html__('Open Graph','meta-tag-manager'),
			'schema' => esc_html__('Structured Data (Schema)','meta-tag-manager'),
			'verify-sites' => esc_html__('Site Verification','meta-tag-manager'),
		);
		if( !defined('MTM_PRO_VERSION') ){
			$tabs['go-pro'] = $fixed_tabs['go-pro'] = '<span style="color:green;">'. esc_html__('Pro Features!','meta-tag-manager') . '</span>';
		}
		$tabs = apply_filters('mtm_settings_page_tabs', $tabs);
		foreach( $tabs as $tab_key => $tab_name ){
			$tab_link = !empty($tabs_enabled) ? esc_url(add_query_arg( array('mtm_tab'=>$tab_key))) : '';
			$active_class = (!empty($tabs_enabled) && !empty($_GET['mtm_tab']) && $_GET['mtm_tab'] == $tab_key) || $tab_key == 'custom-meta-tags' ? 'nav-tab-active':'';
			echo "<a href='$tab_link#$tab_key' id='mtm-menu-$tab_key' class='nav-tab $active_class'>$tab_name</a>";
		}
		?>
	</h2>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-2 mtm-settings">
			<div id="postbox-container-2" class="postbox-container">
				<form action="" method="post">
					<?php
					wp_nonce_field('mtm_options_submitted', 'mtm_nonce');
					if( empty($tabs_enabled) || (!empty($_REQUEST['mtm_tab']) || $_REQUEST['mtm_tab'] == 'custom-meta-tags') ) {
						?>
						<div class="mtm-menu-custom-meta-tags mtm-menu-group">
							<?php include('settings/custom-meta-tags.php'); ?>
						</div>
						<?php
					}
					if( empty($tabs_enabled) || (!empty($_REQUEST['mtm_tab']) || $_REQUEST['mtm_tab'] == 'general') ){
						?>
						<div class="mtm-menu-general mtm-menu-group">
							<?php include('settings/general.php'); ?>
						</div>
						<?php
					}
					if( empty($tabs_enabled) || (!empty($_REQUEST['mtm_tab']) || $_REQUEST['mtm_tab'] == 'open-graph') ){
						?>
						<div class="mtm-menu-open-graph mtm-menu-group">
							<?php include('settings/open-graph.php'); ?>
						</div>
						<?php
					}
					if( empty($tabs_enabled) || (!empty($_REQUEST['mtm_tab']) || $_REQUEST['mtm_tab'] == 'schema') ){
						?>
						<div class="mtm-menu-schema mtm-menu-group">
							<?php include('settings/schema.php'); ?>
						</div>
						<?php
					}
					if( empty($tabs_enabled) || (!empty($_REQUEST['mtm_tab']) || $_REQUEST['mtm_tab'] == 'verify-sites') ){
						?>
						<div class="mtm-menu-verify-sites mtm-menu-group">
							<?php include('settings/verify-sites.php'); ?>
						</div>
						<?php
					}
					if( !defined('MTM_PRO_VERSION') && (empty($tabs_enabled) || (!empty($_REQUEST['mtm_tab']) || $_REQUEST['mtm_tab'] == 'go-pro')) ){
						include('settings/go-pro.php');
					}
					// output custom tabs content here
					if( !empty($tabs_enabled) && array_key_exists($_REQUEST['mtm_tab'], $tabs) && !array_key_exists($_REQUEST['mtm_tab'], $fixed_tabs) ){
						?>
						<div class="mtm-menu-<?php echo esc_attr($_REQUEST['mtm_tab']) ?> mtm-menu-group">
							<?php do_action('mtm_settings_page_tab_'. $_REQUEST['mtm_tab']); ?>
							<?php echo $mtm_submit_button; ?>
						</div>
						<?php
					}else{
						foreach( $tabs as $tab_key => $tab_name ){
							if( !empty($fixed_tabs[$tab_key]) ) continue;
							?>
							<div class="mtm-menu-<?php echo esc_attr($tab_key) ?> mtm-menu-group" style="display:none;">
								<?php do_action('mtm_settings_page_tab_'. $tab_key); ?>
								<?php echo $mtm_submit_button; ?>
							</div>
							<?php
						}
					}
					?>
				</form>
			</div>
			<?php include('settings/sidebar.php'); ?>
		</div><!-- #post-body -->
	</div><!-- #poststuff -->
</div>