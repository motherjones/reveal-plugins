<?php
if( defined('MTM_VERSION') ){
	//coming in from MTM 1.x we change the values to something else
	$mtm_version = get_option('mtm_version');
	if( !$mtm_version ){
		$mtm_data = get_option('mtm_data', array());
		$new_mtm_data = array();
		foreach($mtm_data as $mtm_tag){
			$new_tag = array(
				'value' => $mtm_tag[0],
				'content' => $mtm_tag[1],
				'reference' => $mtm_tag[2],
				'type' => 'name'
			);
			if( !empty($mtm_tag[3]) ){
				$new_tag['location'] = 'home';
			}else{
				$new_tag['location'] = 'all';
			}
			$new_mtm_data[] = $new_tag;
		}
		update_option('mtm_data', $new_mtm_data);
		update_option('mtm_custom', array('post-types'=>get_post_types()));
	}elseif( version_compare( MTM_VERSION, $mtm_version ) ){
		delete_option('mtm_shiny_update_notice');
		$mtm_custom = get_option('mtm_custom');
		if( version_compare( '3.0.1', $mtm_version )  ){
			if( empty($mtm_custom['admin_notices']) ) $mtm_custom['admin_notices'] = array();
			$admin_notice = sprintf(esc_html__('Meta Tag Manager 3.0 introduces many new features including Open Graph and Schema support. Check out the newly updated %s!', 'meta-tag-manager'), '<a href="'.admin_url('options-general.php?page=meta-tag-manager').'">'. esc_html__('Settings Page', 'meta-tag-manager') .'</a>');
			$admin_notice .= '</p><p>'. esc_html__('We are also proud to announce a new Pro add-on to help fund further development of this plugin, which has been freely available since 2009!', 'meta-tag-manager');
			$admin_notice .= '</p><p><a class="button-primary" href="'.admin_url('options-general.php?page=meta-tag-manager#go-pro').'">'. esc_html__('Learn More', 'meta-tag-manager') .'</a> <a class="mtm-notice-dismiss button-secondary">'.__('Dismiss').'</a>';
			$Admin_Notice = new \Meta_Tag_Manager\Admin_Notice('new-features-3-0', 'info', $admin_notice, 'all');
			\Meta_Tag_Manager\Admin_Notices::add( $Admin_Notice );
		}
	}
	
	update_option('mtm_version', MTM_VERSION);
}