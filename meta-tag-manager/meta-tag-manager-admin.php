<?php
/*
Copyright (C) 2021 Marcus Sykes

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

class Meta_Tag_Manager_Admin {
	
	/** loads the plugin */
	public static function init() {
	    global $pagenow, $MTM_Notices;
		include_once('admin/notices/admin-notices.php');
		include_once('admin/notices/notices.php');
		$MTM_Notices = new \Meta_Tag_Manager\Notices();
		// add plugin page to admin menu
		add_action ( 'admin_menu', array ( __CLASS__, 'menus' ) );
		if( version_compare(MTM_VERSION, get_option('mtm_version', 0)) ){
			include_once('admin/mtm-update.php');
		}
		if($pagenow == 'post.php' || $pagenow == 'post-new.php' ){ //only needed if editing post 
			//meta boxes
			add_action('add_meta_boxes', 'Meta_Tag_Manager_Admin::meta_boxes');
    		//Save/Edit actions
    		add_filter('wp_insert_post_data', 'Meta_Tag_Manager_Admin::wp_insert_post_data', 100, 2); //validate post meta before saving is done
    		//special for attachments (if supported)
    		add_action('attachment_updated', 'Meta_Tag_Manager_Admin::wp_insert_attachment_data'); 
    		add_action('add_attachment', 'Meta_Tag_Manager_Admin::wp_insert_attachment_data');
		}
		add_action('admin_init', 'Meta_Tag_Manager_Admin::admin_init');
		
		// Ajax action to refresh the user image
		add_action( 'wp_ajax_mtm_get_logo_url', 'Meta_Tag_Manager_Admin::ajax_logo_preview' );
	}
	
	public static function admin_init(){
		if ( !empty($_REQUEST['mtm_nonce']) && wp_verify_nonce($_REQUEST['mtm_nonce'], 'mtm_options_submitted') ) {
			Meta_Tag_Manager::load();
			$mtm_data = MTM_Builder::get_post();
			update_option ( 'mtm_data', $mtm_data );
			//quickly sanitize the post type custom data and save
			$mtm_custom = array('post-types'=>array());
			if( !empty($_REQUEST['mtm-post-types']) ){
				$post_types = get_post_types(array('public'=>true), 'names');
				foreach( $_REQUEST['mtm-post-types'] as $post_type ){
					if( in_array($post_type, $post_types) ){
						$mtm_custom['post-types'][] = sanitize_text_field($post_type);
					}
				}
			}
			// save schema, og, etc.
			$mtm_custom['schema'] = \Meta_Tag_Manager\Schema_Admin::get_post();
			$mtm_custom['og'] = \Meta_Tag_Manager\Open_Graph_Admin::get_post();
			$mtm_custom['verify'] = \Meta_Tag_Manager\Verify_Sites_Admin::get_post();
			// filters/actions and save
			$mtm_custom = apply_filters('mtm_custom_settings_save', $mtm_custom);
			update_option('mtm_custom', $mtm_custom);
			do_action('meta_tag_manager_settings_saved');
			$url = add_query_arg('saved', 1, wp_get_referer());
			// add test link
			if( !empty($_REQUEST['schema_test_afterwards']) && in_array($_REQUEST['schema_test_afterwards'],array('google', 'schema.org')) ) $url = add_query_arg('schema_test', $_REQUEST['schema_test_afterwards'], $url);
			if( !empty($_REQUEST['og_test_afterwards']) && in_array($_REQUEST['og_test_afterwards'],array('google', 'twitter', 'fb')) ) $url = add_query_arg('og_test', $_REQUEST['og_test_afterwards'], $url);
			wp_redirect( $url );
			exit;
		}
	}
	
	public static function load_plugin_textdomain(){
		load_plugin_textdomain('meta-tag-manager', false, dirname( plugin_basename( __FILE__ ) ).'/languages');
	}
	
	/** adds plugin page to admin menu; put it under 'Settings' */
	public static function menus() {
		$page = add_options_page ( __ ( 'Meta Tag Manager', 'meta-tag-manager' ), __ ( 'Meta Tag Manager', 'meta-tag-manager' ), 'list_users', 'meta-tag-manager', 'Meta_Tag_Manager_Admin::options' );
		// add javascript
		add_action ( "admin_enqueue_scripts", 'Meta_Tag_Manager_Admin::scripts', 10, 1 );
	}
	
	/** loads javascript on plugin admin page */
	public static function scripts( $hook ) {
	    if( in_array($hook, array('post.php', 'post-new.php', 'settings_page_meta-tag-manager', 'force_load')) ){
	        if($hook == 'post.php' || $hook == 'post-new.php' ){
	            global $post;
	            $mtm_custom = get_option('mtm_custom');
	            if( !empty($mtm_custom['post-types']) && !in_array($post->post_type, $mtm_custom['post-types']) ) return;
	        }
		    if ( ! did_action( 'wp_enqueue_media' ) )  wp_enqueue_media(); // include upload image field for logo
	        $jquery_deps = array('jquery','jquery-ui-core','jquery-ui-widget','jquery-ui-mouse','jquery-ui-sortable');
	        if( (defined('WP_DEBUG') && WP_DEBUG) || defined('SCRIPT_DEBUG') && SCRIPT_DEBUG || defined('MTM_DEBUG') && MTM_DEBUG ){
		        wp_enqueue_script('mtm-selectize', plugins_url('js/selectize.js',__FILE__), $jquery_deps, MTM_VERSION);
    			wp_enqueue_script('meta-tag-manager', plugins_url('js/meta-tag-manager.js',__FILE__), $jquery_deps, MTM_VERSION);
    			wp_enqueue_style('mtm-selectize', plugins_url('css/selectize/selectize.css',__FILE__), array(), MTM_VERSION);
    			wp_enqueue_style('meta-tag-manager', plugins_url('css/meta-tag-manager.css',__FILE__), array(), MTM_VERSION);
		        if( $hook == 'settings_page_meta-tag-manager' ) {
			        wp_enqueue_script('meta-tag-manager-settings', plugins_url('js/meta-tag-manager-settings.js', __FILE__), $jquery_deps, MTM_VERSION);
		        }
	        }else{
		        wp_enqueue_script('mtm-selectize', plugins_url('js/selectize.min.js',__FILE__), $jquery_deps, MTM_VERSION);
	        	wp_enqueue_script('meta-tag-manager', plugins_url('js/meta-tag-manager.min.js',__FILE__), $jquery_deps, MTM_VERSION);
	        	wp_enqueue_style('meta-tag-manager', plugins_url('css/meta-tag-manager.min.css',__FILE__), array(), MTM_VERSION);
		        if( $hook == 'settings_page_meta-tag-manager' ){
			        wp_enqueue_script('meta-tag-manager-settings', plugins_url('js/meta-tag-manager-settings.min.js',__FILE__), $jquery_deps, MTM_VERSION);
		        }
	        }
	        do_action('mtm_admin_scripts', $hook);
		
		    if($hook == 'post.php' || $hook == 'post-new.php' ){
			    do_action('mtm_admin_scripts_posts', $hook);
		    }elseif( $hook == 'settings_page_meta-tag-manager' ){
			    do_action('mtm_admin_scripts_settings');
		    }
	    }
	}
	
	public static function meta_boxes(){
	    global $post;
	    //no need to proceed if we're not dealing with posts we're set to add meta
	    $mtm_custom = get_option('mtm_custom');
	    add_meta_box('meta-tag-manager', __('Meta Tag Manager','meta-tag-manager'), 'Meta_Tag_Manager_Admin::post_meta_box', $mtm_custom['post-types'], 'normal','low');
	}
	
	public static function post_meta_box(){
		global $post;
		//output builder
		static::load_builder();
	    MTM_Builder::output(Meta_Tag_Manager::get_post_data($post->ID), array('context'=>false));
	}
	
	public static function load_builder(){
		Meta_Tag_Manager::load('builder'); //legacy function
	}
	
	public static function wp_insert_post_data( $data, $postarr ){
		$post_type = $data['post_type'];
		$post_ID = !empty($postarr['ID']) ? $postarr['ID'] : false;
		$mtm_custom = get_option('mtm_custom');
		//get posted meta tag data and save it to CPT meta
		if( $post_ID && !empty($mtm_custom['post-types']) && in_array($post_type, $mtm_custom['post-types']) ){
			Meta_Tag_Manager::load('builder');
			$mtm_data = MTM_Builder::get_post(array('context'=>false));
			if( !empty($mtm_data) ){
				update_post_meta($post_ID, 'mtm_data', $mtm_data);
			}else{
				//check if we already had tags for this post, if so, delete them since it must have been deleted
				$previous_tags = Meta_Tag_Manager::get_post_data($post_ID);
				if( !empty($previous_tags) ) delete_post_meta($post_ID, 'mtm_data');
			}
		}
		return $data;
	}
	
	/**
	 * Wrapper function for wp_insert_post_data processing of attachments, since they have specific actions and no filter.
	 * @param int $post_ID
	 */
	public static function wp_insert_attachment_data( $post_ID ){
		self::wp_insert_post_data(array('post_type' => 'attachment'), array('ID'=>$post_ID));
	}
		
	/** the plugin options page */
	public static function options() {
		static::load_builder();
		include_once('admin/functions.php');
		include('admin/mtm-admin-settings.php');
	}
	
	public static function ajax_logo_preview() {
		if( isset($_GET['id']) ){
			$image = wp_get_attachment_image( filter_input( INPUT_GET, 'id', FILTER_VALIDATE_INT ) );
			$data = array( 'image' => $image );
			wp_send_json_success( $data );
		} else {
			wp_send_json_error();
		}
	}
	
	/**
	 * Outputs JS for dismissing notices.
	 */
	public static function admin_footer(){
		?>
		<script type="text/javascript">
			jQuery(document).ready( function($){
				$('.mtm-admin-notice').on('click', 'button.notice-dismiss', function(e){
					var the_notice = $(this).closest('.mtm-admin-notice');
					$.get('<?php echo admin_url('admin-ajax.php'); ?>', {'action' : the_notice.data('dismiss-action'), 'notice' : the_notice.data('dismiss-key') });
				});
			});
		</script>
		<?php
	}
}

// Start this plugin once all other plugins are fully loaded
add_action( 'init', array('Meta_Tag_Manager_Admin', 'init') );
add_action('plugins_loaded', 'Meta_Tag_Manager_Admin::load_plugin_textdomain');
