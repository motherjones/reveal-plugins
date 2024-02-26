<?php
class MTM_Tag {
	public $reference;
	public $type;
	public $value;
	public $content;
	public $context = false;
	
	public static $types = array();
	public static $types_with_content = array();
	
	public function __construct($values){
		$this->reference = !empty($values['reference']) ? $values['reference']:'';
		$this->type = !empty($values['type']) ? $values['type']:'';
		$this->value = !empty($values['value']) ? $values['value']:'';
		$this->content = !empty($values['content']) ? $values['content']:'';
		if( !empty($values['context']) ){
			$this->context = explode(',', $values['context']);
		}
		do_action('mtm_tag', $values, $this);
	}
	
	public function output(){
		$output = apply_filters('mtm_tag_output_pre', null, $this);
		if( $output !== null ){
			return apply_filters('mtm_tag_output', $output, $this);
		}
		$tag_string = '<meta '.esc_attr($this->type).'="'.esc_attr($this->value).'"';
		if( $this->has_content() ){
			if( $this->type == 'http-equiv' && $this->value == 'Link' ){
				//escape the attribute but allow for the <url>; format to pass through 
				$tag_string .= ' content="'.preg_replace('/&lt;(.+)&gt;;/', '<$1>;', esc_attr($this->get_content())).'"';
			}else{
				$tag_string .= ' content="'.esc_attr($this->get_content()).'"';
			}
		}
		$tag_string .= ' />';
		return apply_filters('mtm_tag_output', $tag_string, $this);
	}
	
	public function to_array(){
		$array = array(
			'reference' => $this->reference,
			'type' => $this->type,
			'value' => $this->value
		);
		if( $this->has_content() ){
			$array['content'] = $this->content;
		}
		if( $this->context !== false && is_array($this->context) ){
			$array['context'] = implode(',', $this->context);
		}
		return apply_filters('mtm_tag_to_array', $array, $this);
	}
	
	public function is_valid(){
		$return = false;
		if( in_array($this->type, static::get_types()) ){ //pass
			if( !empty($this->value) ){ // pass ... so far
				if( !($this->has_content() && empty($this->content)) ){ // if this doesn't pass, it fails
					$return = true; //if we get here, we're good
				}
			}
		}
		return apply_filters('mtm_tag_is_valid', $return, $this);
	}
	
	public function get_content(){
		return apply_filters('mtm_tag_get_content', $this->content, $this);
	}
	
	public static function get_types(){
		if( empty(static::$types) ){
			static::$types = apply_filters('mtm_tag_get_types', array('name','http-equiv','charset','itemprop','property'));
		}
		return static::$types;
	}
	
	public function has_content(){
		if( empty(static::$types_with_content) ){
			static::$types_with_content = apply_filters('mtm_types_with_content', array('name','http-equiv','itemprop','property'));
		}
		return in_array($this->type, static::$types_with_content);
	}
	
	public function is_in_context(){
		$return = true;
		if( !empty($this->context) ){ //if empty, we assume it's meant to be output everywhere
			$return = static::check_context( $this->context );
		}
		return apply_filters('mtm_tag_is_in_context', $return, $this);
	}
	
	/**
	 * Checks if the currently displayed page meets the array of supplied contexts
	 * @param array $contexts
	 * @return bool
	 */
	public static function check_context( $contexts ){
		$return = empty($contexts);
		foreach( $contexts as $context ){
			if( $context == 'home' && is_front_page() ){
				$return = true;
			}else{
				//check post types and taxonomies
				if( preg_match('/^post-type_/', $context) ){
					$post_type = str_replace('post-type_', '', $context);
					if( Meta_Tag_Manager::is_cpt_page($post_type) ){
						$return = true;
					}
				}elseif( preg_match('/^taxonomy_/', $context) ){
					$taxonomy = str_replace('taxonomy_', '', $context);
					if( Meta_Tag_Manager::is_taxonomy_page( $taxonomy ) ){
						$return = true;
					}
				}
			}
		}
		return apply_filters('mtm_tag_check_context', $return, $contexts);
	}
}