<?php
namespace Meta_Tag_Manager;
use Meta_Tag_Manager, MTM_Tag;

class Open_Graph {
	public static function init(){
		if( is_admin() ){
			include('open-graph-admin.php');
		}
		add_filter('mtm_head_meta_tags', 'Meta_Tag_Manager\Open_Graph::add_tags', 10);
	}
	
	public static function add_tags( $mtm_tags ){
		$og = static::get_options();
		if( empty($og['enabled']) ) return $mtm_tags;
		if( is_front_page() ){
			// add front page tags
			$this_page = array();
			if( !empty($og['home']['image']) ) $og['home']['image'] = wp_get_attachment_url($og['home']['image']);
			foreach( $og['home'] as $k => $v ){
				if( empty($v) ) continue;
				$this_page[] = array('type' => 'name', 'value' => 'og:'.$k, 'content' => $v, 'context' => 'home');
			}
			if( !empty($og['twitter']['enabled']) ){
				$this_page[] = array('type' => 'name', 'value' => 'twitter:card', 'content' => 'summary', 'context' => 'home');
				if( !empty($og['twitter']['site']) ) $this_page[] = array('type' => 'name', 'value' => 'twitter:site', 'content' => $og['twitter']['site'], 'context' => 'home');
				if( !empty($og['twitter']['creator']) ) $this_page[] = array('type' => 'name', 'value' => 'twitter:creator', 'content' => $og['twitter']['creator'], 'context' => 'home');
			}
			foreach( array_reverse($this_page) as $tag_array ){
				$meta_tag = new MTM_Tag($tag_array);
				array_unshift($mtm_tags, $meta_tag);
			}
		}elseif( !empty($og['generate_singular']) && is_singular() ){
			// generate og tags based on current page
			$this_page = array(
				'og:type' => 'webssite',
				'og:title' => get_the_title(),
				'og:description' => get_the_excerpt(),
				'og:image' => get_the_post_thumbnail_url(),
				'og:locale' => determine_locale(),
				'og:site_name' => $og['home']['site_name'],
			);
			if( !empty($og['twitter']['enabled']) ){
				$this_page['twitter:card'] = 'summary';
				if( !empty($og['twitter']['site']) ) $this_page['twitter:site'] = $og['twitter']['site'];
				if( !empty($og['twitter']['creator']) ) $this_page['twitter:site'] = $og['twitter']['creator'];
			}
			foreach( array_reverse( $this_page ) as $k => $v ){
				$tag_array = array('type' => 'name', 'value' => $k, 'content' => $v, 'context' => 'post-type_'.get_post_type() );
				$meta_tag = new MTM_Tag($tag_array);
				array_unshift($mtm_tags, $meta_tag);
			}
		}
		return $mtm_tags;
	}
	
	public static function get_options( $defaults = null ){
		$mtm_custom = get_option('mtm_custom');
		if( $defaults === null ) {
			return !empty($mtm_custom['og']) ? $mtm_custom['og'] : false;
		}else{
			$locale = (function_exists('determine_locale')) ? determine_locale() : get_locale();
			$og = array(
				'enabled' => false,
				'generate_singular' => false,
				'home' => array(
					'type' => 'website',
					'title' => get_bloginfo('name'),
					'description' => get_bloginfo('description'),
					'image' => get_theme_mod( 'custom_logo' ),
					'locale' => $locale,
					'site_name' => get_bloginfo('name'),
				),
				'twitter' => array(
					'enabled' => true, // enabled if og is enabled, no harm
					'site' => '',
					'creator' => '',
				)
			);
			if( !$defaults ){
				$og = empty($mtm_custom['og']) ? $og : Meta_Tag_Manager::array_merge($og, $mtm_custom['og']);
				$og['home']['site_name'] = $og['home']['title'];
			}
		}
		return $og;
	}
}
Open_Graph::init();