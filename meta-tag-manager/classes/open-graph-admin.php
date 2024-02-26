<?php
namespace Meta_Tag_Manager;

class Open_Graph_Admin {
	
	public static function get_post(){
		$og = Open_Graph::get_options(true);
		if( empty($_REQUEST['mtm_og_enabled']) ){
			$og = array('enabled' => false);
		} else {
			$og['enabled'] = true;
			$og['generate_singular'] = !empty($_REQUEST['mtm_og_generate_singular']);
			if( !empty($_REQUEST['mtm_og_site_title']) ) $og['home']['title'] =  sanitize_text_field($_REQUEST['mtm_og_site_title']);
			if( !empty($_REQUEST['mtm_og_site_description']) ) $og['home']['description'] =  sanitize_text_field($_REQUEST['mtm_og_site_description']);
			if( !empty($_REQUEST['mtm_og_site_logo']) ) $og['home']['image'] = absint($_REQUEST['mtm_og_site_logo']);
			$og['twitter'] = array('enabled' => !empty($_REQUEST['mtm_og_twitter_enabled']));
			if( $og['twitter']['enabled'] ){
				if( !empty($_REQUEST['mtm_og_twitter_site']) && preg_match('/^@[a-zA-Z0-9_]{4,15}$/', $_REQUEST['mtm_og_twitter_site']) ) $og['twitter']['site'] = sanitize_text_field($_REQUEST['mtm_og_twitter_site']);
				if( !empty($_REQUEST['mtm_og_twitter_creator']) && preg_match('/^@[a-zA-Z0-9_]{4,15}$/', $_REQUEST['mtm_og_twitter_creator']) ) $og['twitter']['creator'] = sanitize_text_field($_REQUEST['mtm_og_twitter_creator']);
			}
		}
		return $og;
	}
}