<?php
namespace Meta_Tag_Manager;
use Meta_Tag_Manager, MTM_Tag;

class Verify_Sites {
	public static function init(){
		if( is_admin() ){
			include_once('verify-sites-admin.php');
		}
		add_filter('mtm_head_meta_tags', 'Meta_Tag_Manager\Verify_Sites::add_tags', 10);
	}
	
	public static function add_tags( $mtm_tags ){
		$verify = static::get_options();
		if( empty($verify) ) return $mtm_tags;
		if( is_front_page() ){
			$sites = static::get_sites();
			foreach( $verify as $site => $site_key ){
				if( !empty($sites[$site]) ) {
					$mtm_tags[] = new MTM_Tag(array('type' => 'name', 'value' => $sites[$site], 'content' => $site_key, 'context' => 'home'));
				}
			}
		}
		return $mtm_tags;
	}
	
	public static function get_sites(){
		return array(
			'google' => 'google-site-verification',
			'bing' => 'msvalidate.01', // https://www.bing.com/webmasters/help/add-and-verify-site-12184f8b
			'facebook' => 'facebook-domain-verification',
			'pintrest' => 'p:domain_verify', // https://help.pinterest.com/en/business/article/claim-your-website#section-12101
			'sitelock' => 'sitelock-site-verification',
			'yandex' => 'yandex-verification', // https://yandex.com/support/webmaster/service/rights.html
		);
	}
	
	public static function get_options( $defaults = null ){
		$mtm_custom = get_option('mtm_custom');
		if( $defaults === null ) {
			return !empty($mtm_custom['verify']) ? $mtm_custom['verify'] : false;
		}else{
			$verify = static::get_sites();
			foreach( $verify as $k => $v ) $verify[$k] = ''; // empty string it
			if( !$defaults ){
				$verify = empty($mtm_custom['verify']) ? $verify : Meta_Tag_Manager::array_merge($verify, $mtm_custom['verify']);
			}
		}
		return $verify;
	}
}
Verify_Sites::init();