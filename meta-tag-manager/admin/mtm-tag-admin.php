<?php
class MTM_Tag_Admin extends MTM_Tag {
	
	public static $type_options = array();
	
	/**
	 * Constructs a meta tag object but assumes $values is dirty and sanitizes it before passing onto parent constructor
	 * @param array $values
	 */
	public function __construct($values){
		//context may be passed as an array, if so we implode it first
		if( !empty($values['context']) && is_array($values['context']) ){
			if( in_array('all',$values['context']) ){
				//all pages equates to no context, since when we're outputting global meta tags no context implies outputting it everywhere
				$values['context'] = false;
			}else{
				// sanitize context values so they are 'valid'
				$values['context'] = MTM_Tag_Admin::sanitize_contexts( wp_unslash($values['context']) );
				// implode the context values
				$values['context'] = implode(',', $values['context']);
			}
		}
		$values = apply_filters('mtm_tag_admin_values', $values, $this);
		$values_cleaned = array();
		//go through all non-empty values and wp_kses it
		foreach( $values as $k => $v ){
			if( $k == 'content'){
				$values_cleaned[$k] = wp_unslash($v);
			}else{
				$values_cleaned[$k] = wp_kses(wp_unslash($v), array());
			}
		}
		$values_cleaned = apply_filters('mtm_tag_admin_values_cleaned', $values_cleaned, $values, $this);
		//now pass cleaned values to parent constructor
		parent::__construct($values_cleaned);
	}
	
	public static function get_type_values($type = false){
		if( empty(self::$type_options) ){
			$options = array(
				'name' => array(
					__('Common values','meta-tag-manager') => array( 'application-name', 'google-site-verification', 'facebook-domain-verification', 'author', 'description', 'generator', 'keywords', 'referrer' ),
					__('Other possible values','meta-tag-manager') => array( 'creator', 'googlebot', 'publisher', 'robots', 'slurp', 'viewport')
				),
				'http-equiv' => array( 'Content-Security-Policy', 'default-style', 'refresh' ),
				'property' => array(
					'OpenGraph Properties' => array( 'og:url', 'og:type', 'og:title', 'og:locale', 'og:image', 'og:image:secure_url', 'og:image:type', 'og:image:width', 'og:image:height', 'og:video', 'og:video:secure_url', 'og:video:type', 'og:video:width', 'og:video:height', 'og:audio', 'og:audio:secure_url', 'og:audio:type', 'og:description', 'og:site_name', 'og:determiner' ),
					'Twitter Card Properties' => array( 'twitter:card', 'twitter:site', 'twitter:site:id', 'twitter:creator', 'twitter:creator:id', 'twitter:description', 'twitter:title', 'twitter:image', 'twitter:image:alt', 'twitter:player', 'twitter:player:width', 'twitter:player:height', 'twitter:player:stream', 'twitter:app:name:iphone', 'twitter:app:id:iphone', 'twitter:app:url:iphone', 'twitter:app:name:ipad', 'twitter:app:id:ipad', 'twitter:app:url:ipad', 'twitter:app:name:googleplay', 'twitter:app:id:googleplay', 'twitter:app:url:googleplay' )
				)
			);
			self::$type_options = apply_filters('mtm_tag_type_options', $options);
		}
		if( !empty($type) ){
			return array_key_exists($type, self::$type_options) ? self::$type_options[$type] : array();
		}
		return apply_filters('mtm_tag_admin_get_type_values', self::$type_options);
	}
	
	
	public static function sanitize_contexts( $contexts ){
		Meta_Tag_Manager_Admin::load_builder(); // just in case
		$context_options = MTM_Builder::get_context_options();
		$valid_contexts = array();
		// flatten the valid contexts array
		foreach( $context_options as $context ){
			if( is_array($context) ){
				foreach( $context as $subcontext ){
					if( !is_array($subcontext) ) $valid_contexts[] = $subcontext;
				}
			}else{
				$valid_contexts[] = $context;
			}
		}
		$clean_contexts = array();
		foreach( $contexts as $k => $context ){
			if( in_array($context, $valid_contexts) ){
				$clean_contexts[] = $context;
			}
		}
		return apply_filters('mtm_tag_admin_sanitize_contexts', $clean_contexts, $contexts);
	}
}