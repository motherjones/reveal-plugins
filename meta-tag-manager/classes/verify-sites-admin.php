<?php
namespace Meta_Tag_Manager;

class Verify_Sites_Admin {
	
	public static function get_post(){
		$verify_sites = array();
		$sites = Verify_Sites::get_sites();
		foreach( $sites as $site => $value ){
			if( !empty($_REQUEST['mtm_verify_sites_'.$site]) ){
				if( preg_match('/content=(["][^"]+|[\'][^\']+)/', wp_unslash($_REQUEST['mtm_verify_sites_'.$site]), $match) ){
					$verify_sites[$site] = substr($match[1], 1);
				}else{
					$verify_sites[$site] = sanitize_text_field($_REQUEST['mtm_verify_sites_'.$site]);
				}
			}
		}
		return $verify_sites;
	}
}