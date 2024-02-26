<?php
namespace Meta_Tag_Manager;
use Meta_Tag_Manager;

class Schema {
	
	public static function init(){
		if( is_admin() ){
			include('schema-admin.php');
		}
		add_action('mtm_head', 'Meta_Tag_Manager\Schema::output');
	}
	
	public static function output(){
		if( !is_front_page() ) return; // for now, only front page stuff
		$schema_options = static::get_options();
		?>
		<!-- Meta Tag Manager - Schema -->
		<script type="application/ld+json">
			<?php echo static::output_general() ."\n" ?>
		</script>
		<?php
		if( !empty($schema_options['sitelinks']['search']) ){
			if( $schema_options['sitelinks']['search'] == 1 ){
				echo '<script type="application/ld+json">'."\n\t\t\t";
				echo static::output_sitelinks_search();
				echo "\n\t\t".'</script>';
			}else{
				echo '<meta name="google" content="nositelinkssearchbox" />';
			}
		}
		if( !empty($schema_options['sitelinks']['menu']) ){
			$sitelinks = static::output_sitelinks();
			if( !empty($sitelinks) ){
				echo "\n\t\t".'<script type="application/ld+json">'."\n\t\t\t";
				echo static::output_sitelinks();
				echo "\n\t\t".'</script>';
			}
		}
		echo "\n";
		?>
		<!-- / Meta Tag Manager - Schema -->
		<?php
	}
	
	public static function output_general(){
		$schema_options = static::get_options();
		$schema = array('@context' => 'https://schema.org');
		// get the type of
		$schema['@type'] = esc_js($schema_options['type']);
		$schema['name'] = esc_js($schema_options['name']);
		$schema['url'] = get_site_url();
		if( $schema_options['type'] == 'Organization' && !empty($schema_options['Organization']['subtype']) ){
			$schema['@type'] = esc_js($schema_options['Organization']['subtype']);
		}
		if( !empty($schema_options['logo']) ){
			$image = wp_get_attachment_image_src( $schema_options['logo'], 'full' );
			if( $image ){
				$schema['logo'] = array(
					'@type' => 'ImageObject',
					'url' => $image[0],
					'width' => $image[1],
					'height' => $image[2],
				);
			}
		}
		if( !empty($schema_options['Contact']['enabled']) ){
			$schema['ContactPoint'] = array(
				'@type' => 'ContactPoint',
				'contactType' => esc_js($schema_options['Contact']['name']),
			);
			if( !empty($schema_options['Contact']['telephone']) ) $schema['ContactPoint']['telephone'] = esc_js($schema_options['Contact']['telephone']);
			if( !empty($schema_options['Contact']['email']) ) $schema['ContactPoint']['email'] = esc_js($schema_options['Contact']['email']);
			if( !empty($schema_options['Contact']['url']) ){
				if( is_numeric($schema_options['Contact']['url']) ){
					$schema['ContactPoint']['url'] = get_permalink($schema_options['Contact']['url']);
				}else{
					$schema['ContactPoint']['url'] = esc_url($schema_options['Contact']['url']);
				}
			}
		}
		if( !empty($schema_options['profiles']) ){
			$schema['sameAs'] = array();
			foreach( $schema_options['profiles'] as $profile ){
				$schema['sameAs'][] = esc_url($profile);
			}
		}
		return json_encode($schema);
	}
	
	public static function output_sitelinks(){
		$schema_options = static::get_options();
		if( !empty($schema_options['sitelinks']['menu']) ){
			$menu_items = wp_get_nav_menu_items($schema_options['sitelinks']['menu']);
			if( !empty($menu_items) ){
				$schema = array (
					'@context' => 'https://schema.org',
					'@graph' => array (),
				);
				foreach( $menu_items as $menu_item ){
					$schema['@graph'][] = array (
						'@context' => 'https://schema.org',
						'@type' => 'SiteNavigationElement',
						'id' => 'site-navigation',
						'name' => $menu_item->title,
						'url' => esc_js(esc_url($menu_item->url)),
					);
				}
				return json_encode($schema);
			}
		}
		return '';
	}
	
	public static function output_sitelinks_search(){
		$schema = array (
			'@context' => 'https://schema.org',
			'@type' => 'WebSite',
			'name' => get_bloginfo('name'),
			'url' => get_site_url(),
			'potentialAction' =>
				array (
					0 =>
						array (
							'@type' => 'SearchAction',
							'target' => home_url( '?s={search_term_string}' ),
							'query-input' => 'required name=search_term_string',
						),
				),
		);
		return json_encode($schema);
	}

	public static function get_options( $defaults = false ){
		$mtm_custom = get_option('mtm_custom');
		$schema_default = array(
			'enabled' => false,
			'type' => '',
			'logo' => 0,
			'name' => '',
			'Organization' => array(
				'type' => 'Organization',
				'subtype' => 'Organization',
			),
			'Contact' => array(
				'enabled' => 0,
				'name' => '',
				'telephone' => '',
				'url' => '',
				'email' => '',
			),
			'sitelinks' => array(
				'menu' => null,
				'search' => null,
			),
			'profiles' => array(),
		);
		return empty($mtm_custom['schema']) || $defaults ? $schema_default : Meta_Tag_Manager::array_merge($schema_default, $mtm_custom['schema']);
	}

}
Schema::init();