<?php
namespace Meta_Tag_Manager;

class Schema_Admin {
	public static function get_post(){
		$schema = Schema::get_options(true);
		$schema['enabled'] = !empty($_REQUEST['mtm_schema_enabled']);
		if( !$schema['enabled'] ) return $schema;
		if( !empty($_REQUEST['mtm_schema_site_type']) ) $schema['type'] =  sanitize_text_field($_REQUEST['mtm_schema_site_type']);
		if( empty($schema['type']) ){
			global $MTM_Notices; /* @var \Meta_Tag_Manager\Notices $MTM_Notices */
			$MTM_Notices->add( sprintf(esc_html__('You must select a value in the %s field in the %s tab in order for schema markup to show up on your homepage.', 'meta-tag-manager'), '<code>'.esc_html__('This website represents a', 'meta-tag-manager').'</code>', '<code>'.esc_html__('Structured Data (Schema)', 'meta-tag-manager').'</code>'), 'errors', true );
		}
		if( !empty($_REQUEST['mtm_schema_site_logo']) ) $schema['logo'] = absint($_REQUEST['mtm_schema_site_logo']);
		if( $schema['type'] == 'Person' ){
			if( !empty($_REQUEST['mtm_schema_person_name']) ) $schema['name'] = sanitize_text_field($_REQUEST['mtm_schema_person_name']);
		}elseif( $schema['type'] == 'Organization' ) {
			if( !empty($_REQUEST['mtm_schema_organization_name']) ) $schema['name'] = sanitize_text_field($_REQUEST['mtm_schema_organization_name']);
			if( !empty($_REQUEST['mtm_schema_site_type_organization']) ) $schema['Organization']['type'] = sanitize_text_field($_REQUEST['mtm_schema_site_type_organization']);
			if( !empty($_REQUEST['mtm_schema_site_type_organization_specific_'. $schema['Organization']['type']]) ) {
				$schema['Organization']['subtype'] = sanitize_text_field($_REQUEST['mtm_schema_site_type_organization_specific_' . $schema['Organization']['type']]);
			}else {
				$schema['Organization']['subtype'] = $schema['Organization']['type'];
			}
		}
		$schema['Contact']['enabled'] = !empty($_REQUEST['mtm_schema_contact']);
		if( $schema['Contact']['enabled'] ) {
			if( !empty($_REQUEST['mtm_schema_contact_name']) ) $schema['Contact']['name'] =  sanitize_text_field($_REQUEST['mtm_schema_contact_name']);
			if( !empty($_REQUEST['mtm_schema_contact_page']) ) {
				$schema['Contact']['url'] = absint($_REQUEST['mtm_schema_contact_page']);
			}elseif( !empty($_REQUEST['mtm_schema_contact_url']) ) {
				$schema['Contact']['url'] = esc_url_raw($_REQUEST['mtm_schema_contact_url']);
			}
			if( !empty($_REQUEST['mtm_schema_contact_telephone']) ) $schema['Contact']['telephone'] =  sanitize_text_field($_REQUEST['mtm_schema_contact_telephone']);
			if( !empty($_REQUEST['mtm_schema_contact_email']) ) $schema['Contact']['email'] = sanitize_email($_REQUEST['mtm_schema_contact_email']);
		}
		if( isset($_REQUEST['mtm_schema_sitelinks_menu']) ) $schema['sitelinks']['menu'] =  absint($_REQUEST['mtm_schema_sitelinks_menu']);
		if( !empty($_REQUEST['mtm_schema_sitelinks_search']) ) $schema['sitelinks']['search'] =  absint($_REQUEST['mtm_schema_sitelinks_search']);
		if( !empty($_REQUEST['mtm_schema_profiles']) && is_array($_REQUEST['mtm_schema_profiles']) ){
			$schema['profiles'] = array();
			foreach( $_REQUEST['mtm_schema_profiles'] as $k => $profile ){
				if( !empty($profile) && !in_array($profile, $schema['profiles']) ){
					if( is_numeric($k) ){
						$schema['profiles'][] = esc_url_raw($profile);
					}else{
						$k = sanitize_key($k);
						$schema['profiles'][$k] = esc_url_raw($profile);
					}
				}
			}
		}
		return $schema;
	}
}