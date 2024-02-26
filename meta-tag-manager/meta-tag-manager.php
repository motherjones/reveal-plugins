<?php
/*
Plugin Name: Meta Tag Manager
Plugin URI: https://metatagmanager.com
Description: A simple plugin to manage meta tags and other meta data that appear on aread of your site or individual posts. This can be used for verifiying google adding open graph tags, SEO meta and more.
Author: Pixelite
Version: 3.0.2
Author URI: https://pixelite.com/?utm_source=plugin-header&utm_medium=plugin&utm_campaign=plugin
Text Domain: meta-tag-manager
*/
/*
Copyright (C) 2022 Marcus Sykes

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */
if( !defined('ABSPATH') ) exit;

define('MTM_VERSION', '3.0.2');
define('MTM_DIR', dirname( __FILE__ )); //an absolute path to this directory

class Meta_Tag_Manager {
	/** loads the plugin */
	public static function init() {
		// Include admin backend if needed
		if ( is_admin() ) {
			require_once ( 'meta-tag-manager-admin.php' );
		}
		add_action ('wp_head', 'Meta_Tag_Manager::head', 1);
		do_action('metatagmanager_loaded');
	}
	
	/** puts the meta tags in the head */
	public static function head() {
		static::load('meta-tags');
		//If options form has been submitted, create a $_POST value that will be saved on databse
		$meta_tags = array();
		$mtm_custom = get_option('mtm_custom');
		// load open graph
		if( !empty($mtm_custom['og']['enabled']) ){
			Meta_Tag_Manager::load('open-graph');
		}
		// load and output schema - front page only (for now)
		if( !empty($mtm_custom['schema']['enabled']) && is_front_page() ){
			Meta_Tag_Manager::load('schema');
		}
		// load and output schema - front page only (for now)
		if( !empty($mtm_custom['verify']) && is_front_page() ){
			Meta_Tag_Manager::load('verify-sites');
		}
		//check global tags and filter out ones we'll show for this page
		$meta_tags_data = apply_filters('mtm_head_meta_tags_pre', self::get_data());
		foreach( $meta_tags_data as $tag ){
			if( $tag->is_in_context() ){
				$meta_tags[] = $tag;
			}
		}
		//check individual post in case we have specific post meta tags to show
		if( is_single() || is_page() ){
			if( !empty($mtm_custom['post-types']) && in_array(get_post_type(), $mtm_custom['post-types']) ){
				$post_meta_tags = self::get_post_data();
				//remove unique meta tags from being output within MTM (not other plugins), where specific tags take precendence
				$unique_types = apply_filters('mtm_unique_meta_name_values', array('description', 'keywords'));
				$meta_tags_unique_name_values = array();
				foreach( $unique_types as $name_value ){
					foreach( $meta_tags as $k => $tag ){ /* @var MTM_Tag $tag */
						if( $tag->type == 'name' && $tag->value == $name_value ){
							if( !empty($meta_tags_unique_name_values[$name_value]) ){
								//remove first one, last one takes precendence
								unset($meta_tags[$meta_tags_unique_name_values[$name_value]]);
							}
							$meta_tags_unique_name_values[$name_value] = $k;
						}
					}
					if( !empty($meta_tags_unique_name_values) ){
						foreach( $post_meta_tags as $k => $tag ){
							if( $tag->type == 'name' && $tag->value == $name_value && !empty($meta_tags_unique_name_values[$name_value]) ){
								unset($meta_tags[$meta_tags_unique_name_values[$name_value]]);	
							}
						}
					}
				}
				$meta_tags = array_merge($meta_tags, $post_meta_tags);
			}
		}
		$meta_tags = apply_filters('mtm_head_meta_tags', $meta_tags);
		//output the filtered out tags that pass validation
		if( !empty($meta_tags) ){
			//add as keys to prevent duplicates
			$meta_tag_strings = array();
			foreach( $meta_tags as $tag ){
				//only output valid keys
				if( $tag->is_valid() ){
					$meta_tag_strings[$tag->output()] = 1;
				}
			}
			//output tags if there are any
			if( !empty($meta_tag_strings) ){
				echo "\r\n\t\t".'<!-- Meta Tag Manager -->';
				foreach( $meta_tag_strings as $tag_string => $v ){
					echo "\r\n\t\t".$tag_string;
				}
				echo "\r\n\t\t".'<!-- / Meta Tag Manager -->';
				echo "\r\n";
			}
		}
		do_action('mtm_head', $meta_tags);
	}
	
	public static function get_data(){
		$mtm_data = get_option('mtm_data');
		$meta_tags = array();
		if( is_array($mtm_data) ){
			foreach( $mtm_data as $meta_tag_data ){
				$meta_tags[] = new MTM_Tag($meta_tag_data);
			}
		}
		return apply_filters('mtm_get_data', $meta_tags, $mtm_data);
	}
	
	public static function get_post_data( $post_id = false ){
		if( empty($post_id) ) $post_id = get_the_ID();
		$meta_tag_data = maybe_unserialize(get_post_meta($post_id, 'mtm_data', true));
		$meta_tags = array();
		if( is_array($meta_tag_data) ){
			foreach( $meta_tag_data as $tag_data ){
				$meta_tags[] = new MTM_Tag($tag_data);
			}
		}
		return $meta_tags;
	}
	
	// Page type conditionals
	
	public static function is_cpt_page( $post_type = null ){
		return apply_filters('mtm_is_cpt_page', is_singular($post_type), $post_type);
	}
	
	public static function is_taxonomy_page( $taxonomy = null ) {
		if( empty($taxonomy) ){
			$result = is_tax() || is_category() || is_tag();
		}else{
			$result = is_tax( $taxonomy ) || ($taxonomy == 'category' && is_category()) || ($taxonomy == 'post_tag' && is_tag());
		}
		return apply_filters('mtm_is_taxonomy_page', $result, $taxonomy);
	}
	
	public static function is_archive_page( $post_type = null ){
		$is_archive = is_post_type_archive( $post_type ) || get_option( 'page_for_posts' ) == get_queried_object_id();
		return apply_filters('mtm_is_archive_page', $is_archive, $post_type);
	}
	
	public static function is_archive(){
		return apply_filters('mtm_is_archive', is_archive());
	}
	
	// util functions
	
	public static function array_merge( array &$array1, array &$array2 ) {
		$merged = $array1;
		foreach ( $array2 as $key => &$value ) {
			if( is_array($value) && isset($merged[$key]) && is_array($merged[$key]) ){
				$merged[$key] = self::array_merge($merged[$key], $value);
			} else {
				$merged[$key] = $value;
			}
		}
		return $merged;
	}
	
	public static function load( $module = 'all' ){
		if( $module == 'meta-tags' || $module == 'all' ){
			include_once('mtm-tag.php');
		}
		if( $module == 'schema' || $module == 'all'){
			include_once('classes/schema.php');
		}
		if( $module == 'open-graph' || $module == 'all'){
			include_once('classes/open-graph.php');
		}
		if( $module == 'verify-sites' || $module == 'all'){
			include_once('classes/verify-sites.php');
		}
		if( $module == 'builder' || $module == 'all' ){
			include_once('mtm-tag.php');
			include_once('admin/mtm-builder.php');
		}
	}
}
// Start this plugin once all other plugins are fully loaded
add_action( 'plugins_loaded', array('Meta_Tag_Manager', 'init'), 100 );