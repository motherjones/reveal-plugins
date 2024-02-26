<?php
use Meta_Tag_Manager\Schema;

	global $mtm_submit_button;
	$schema = Schema::get_options();
	$test_url_google = "https://search.google.com/test/rich-results?url=" . urlencode(get_site_url());
	$test_url_schemaorg = "https://validator.schema.org/#url=" . urlencode(get_site_url());
	
	$schema_types = array(
		'Person' => array( array( 'description' => esc_html__('Person', 'meta-tag-manager') ), ),
		'Organization' => array(
			'description' => esc_html__('Organization'),
			'types' => array(
				'Airline' => array( 'description' => esc_html__('Airline', 'meta-tag-manager') ),
				'Consortium' => array( 'description' => esc_html__('Consortium', 'meta-tag-manager') ),
				'Corporation' => array( 'description' => esc_html__('Corporation', 'meta-tag-manager') ),
				'EducationalOrganization' => array(
					'description' => esc_html__('Educational Organization', 'meta-tag-manager'),
					'types' => array(
						'CollegeOrUniversity' => array( 'description' => esc_html__('College Or University', 'meta-tag-manager') ),
						'ElementarySchool' => array( 'description' => esc_html__('Elementary School', 'meta-tag-manager') ),
						'HighSchool' => array( 'description' => esc_html__('High School', 'meta-tag-manager') ),
						'MiddleSchool' => array( 'description' => esc_html__('Middle School', 'meta-tag-manager') ),
						'Preschool' => array( 'description' => esc_html__('Preschool', 'meta-tag-manager') ),
						'School' => array( 'description' => esc_html__('School', 'meta-tag-manager') ),
					),
				),
				'FundingScheme' => array( 'description' => esc_html__('Funding Scheme', 'meta-tag-manager') ),
				'GovernmentOrganization' => array( 'description' => esc_html__('Government Organization', 'meta-tag-manager') ),
				'LibrarySystem' => array( 'description' => esc_html__('Library System', 'meta-tag-manager') ),
				/*
				'LocalBusiness' => array(
					'description' => esc_html__('Local Business', 'meta-tag-manager'),
					'types' => array(
						'AnimalShelter' => array('description' => esc_html__('Animal Shelter', 'meta-tag-manager')),
						'ArchiveOrganization' => array('description' => esc_html__('Archive Organization', 'meta-tag-manager')),
						'AutomotiveBusiness' => array('description' => esc_html__('Automotive Business', 'meta-tag-manager')),
						'ChildCare' => array('description' => esc_html__('Child Care', 'meta-tag-manager')),
						'Dentist' => array('description' => esc_html__('Dentist', 'meta-tag-manager')),
						'DryCleaningOrLaundry' => array('description' => esc_html__('Dry Cleaning Or Laundry', 'meta-tag-manager')),
						'EmergencyService' => array('description' => esc_html__('Emergency Service', 'meta-tag-manager')),
						'EmploymentAgency' => array('description' => esc_html__('Employment Agency', 'meta-tag-manager')),
						'EntertainmentBusiness' => array('description' => esc_html__('Entertainment Business', 'meta-tag-manager')),
						'FinancialService' => array('description' => esc_html__('Financial Service', 'meta-tag-manager')),
						'FoodEstablishment' => array('description' => esc_html__('Food Establishment', 'meta-tag-manager')),
						'GovernmentOffice' => array('description' => esc_html__('Government Office', 'meta-tag-manager')),
						'HealthAndBeautyBusiness' => array('description' => esc_html__('Health And Beauty Business', 'meta-tag-manager')),
						'HomeAndConstructionBusiness' => array('description' => esc_html__('Home And Construction Business', 'meta-tag-manager')),
						'InternetCafe' => array('description' => esc_html__('Internet Cafe', 'meta-tag-manager')),
						'LegalService' => array('description' => esc_html__('Legal Service', 'meta-tag-manager')),
						'Library' => array('description' => esc_html__('Library', 'meta-tag-manager')),
						'LodgingBusiness' => array('description' => esc_html__('Lodging Business', 'meta-tag-manager')),
						'MedicalBusiness' => array('description' => esc_html__('Medical Business', 'meta-tag-manager')),
						'ProfessionalService' => array('description' => esc_html__('Professional Service', 'meta-tag-manager')),
						'RadioStation' => array('description' => esc_html__('Radio Station', 'meta-tag-manager')),
						'RealEstateAgent' => array('description' => esc_html__('Real Estate Agent', 'meta-tag-manager')),
						'RecyclingCenter' => array('description' => esc_html__('Recycling Center', 'meta-tag-manager')),
						'SelfStorage' => array('description' => esc_html__('Self Storage', 'meta-tag-manager')),
						'ShoppingCenter' => array('description' => esc_html__('Shopping Center', 'meta-tag-manager')),
						'SportsActivityLocation' => array('description' => esc_html__('Sports Activity Location', 'meta-tag-manager')),
						'Store' => array('description' => esc_html__('Store', 'meta-tag-manager')),
						'TelevisionStation' => array('description' => esc_html__('Television Station', 'meta-tag-manager')),
						'TouristInformationCenter' => array('description' => esc_html__('Tourist Information Center', 'meta-tag-manager')),
						'TravelAgency' => array('description' => esc_html__('Travel Agency', 'meta-tag-manager')),
					)
				),
				*/
				'MedicalOrganization' => array(
					'description' => esc_html__('Medical Organization', 'meta-tag-manager'),
					'types' => array(
						'Dentist' => array( 'description' => esc_html__('Dentist', 'meta-tag-manager') ),
						'DiagnosticLab' => array( 'description' => esc_html__('Diagnostic Lab', 'meta-tag-manager') ),
						'Hospital' => array( 'description' => esc_html__('Hospital', 'meta-tag-manager') ),
						'MedicalClinic' => array( 'description' => esc_html__('Medical Clinic', 'meta-tag-manager') ),
						'Pharmacy' => array( 'description' => esc_html__('Pharmacy', 'meta-tag-manager') ),
						'Physician' => array( 'description' => esc_html__('Physician', 'meta-tag-manager') ),
						'VeterinaryCare' => array( 'description' => esc_html__('Veterinary Care', 'meta-tag-manager') ),
					)
				),
				'NGO' => array( 'description' => esc_html__('NGO', 'meta-tag-manager') ),
				'NewsMediaOrganization' => array( 'description' => esc_html__('News Media Organization', 'meta-tag-manager') ),
				'PerformingGroup' => array(
					'description' => esc_html__('Performing Group', 'meta-tag-manager'),
					'types' => array(
						'DanceGroup' => array( 'description' => esc_html__('Dance Group', 'meta-tag-manager') ),
						'MusicGroup' => array( 'description' => esc_html__('Music Group', 'meta-tag-manager') ),
						'TheaterGroup' => array( 'description' => esc_html__('Theater Group', 'meta-tag-manager') ),
					)
				),
				'Project' => array(
					'description' => esc_html__('Project', 'meta-tag-manager'),
					'types' => array(
						'FundingAgency' => array( 'description' => esc_html__('Funding Agency', 'meta-tag-manager') ),
						'ResearchProject' => array( 'description' => esc_html__('Research Project', 'meta-tag-manager') ),
					),
				),
				'ResearchOrganization' => array( 'description' => esc_html__('Research Organization', 'meta-tag-manager') ),
				'SportsOrganization' => array(
					'description' => esc_html__('Sports Organization', 'meta-tag-manager'),
					'types' => array(
						'SportsTeam' => array( 'description' => esc_html__('Sports Team', 'meta-tag-manager') ),
					),
				),
				'WorkersUnion' => array( 'description' => esc_html__('Workers Union', 'meta-tag-manager') ),
			),
		)
	);
	$site_type_options = array(
		'0' => esc_html__('Chooase a site type', 'meta-tag-manager'),
		'Person' => esc_html__('Personal Website or Blog', 'meta-tag-manager'),
		'Organization' => esc_html__('Business or Organization', 'meta-tag-manager'),
	);
?>
<p>
	<?php esc_html_e('These settings provide search engines and other services that read schema.org meta data information about your website in general. The more information you provide, the more additional information search engines like Google can provide when displaying your site in search results.', 'meta-tag-manager'); ?>
</p>
<p>
<label><input type="checkbox" name="mtm_schema_enabled" class="mtm-settings-binary-trigger" data-trigger-content=".mtm-schema-settings" id="mtm_schema_enabled" value="1" <?php if( !empty($schema['enabled']) ) echo 'checked'; ?>> <?php esc_html_e('Enable Structured Data (Schema) Features', 'meta-tag-manager'); ?></label>
<div class="mtm-schema-settings">
	</p>
	<p>
		<?php
			esc_html_e('Click the buttons below once you have saved your settings, or the \'Save & Test\' buttons at the bottom to save and test. Make sure your information is valid and readable by Google and others.', 'meta-tag-manager');
		?>
	</p>
	<p>
		<a href="<?php echo esc_url($test_url_google) ?>" target="_blank" class="button-secondary"><?php echo sprintf(esc_html__('Test Schema Settings (%s)', 'meta-tag-manager'), 'Google'); ?></a>
		<a href="<?php echo esc_url($test_url_schemaorg) ?>" target="_blank" class="button-secondary"><?php echo sprintf(esc_html__('Test Schema Settings (%s)', 'meta-tag-manager'), 'Schema.org'); ?></a>
	</p>
	<h3><?php esc_html_e('General Information', 'meta-tag-manager'); ?></h3>
	<table class="form-table">
		<?php
		mtm_input_select( esc_html__('This website represents a', 'meta-tag-manager'), 'mtm_schema_site_type', $site_type_options, $schema['type'] );
		$org_types = array( 'Organization' =>  esc_html__('General/Other', 'meta-tag-manager') );
		$org_subtypes = array();
		foreach( $schema_types['Organization']['types'] as $type => $type_data ){
			$org_types[$type] = $type_data['description'];
			if( !empty($type_data['types']) ){
				$org_subtypes[$type] = array( $type =>  esc_html__('General/Other', 'meta-tag-manager') );
				foreach( $type_data['types'] as $subtype => $subtype_data ){
					$org_subtypes[$type][$subtype] = $subtype_data['description'];
				}
			}
		}
		$image_id = empty($schema['logo']) ? get_theme_mod( 'custom_logo' ) : $schema['logo'];
		$image = intval( $image_id ) > 0 ? wp_get_attachment_image( $image_id ) : '';
		?>
		<tr class="mtm-image-upload" data-action="mtm_get_logo_url">
			<th><?php esc_html_e( 'Logo' ); ?></th>
			<td>
				<div class="mtm-image-upload-preview"><?php echo $image; ?></div>
				<input type="hidden" name="mtm_schema_site_logo" class="mtm-image-upload-input" value="<?php echo absint($image_id); ?>" class="regular-text">
				<input type='button' class="button-primary mtm-image-upload-submit" value="<?php esc_attr_e('Select Image'); ?>" >
				<input type='button' class="button-secondary mtm-image-upload-reset" value="<?php esc_attr_e('Remove'); ?>" >
				<p><em><?php echo sprintf( esc_html__('The image size must be at least %s and one of the following formats : %s', 'meta-tag-manager'), '112px x 112px', 'BMP, GIF, JPEG, PNG, WebP or SVG'); ?></em></p>
			</td>
		</tr>
	</table>
	<table class="form-table mtm-schema-types mtm-schema-type-Person">
		<?php
		mtm_input_text( esc_html__('Name of Person', 'meta-tag-manager'), 'mtm_schema_person_name', $schema['name'] );
		?>
	</table>
	<table class="form-table mtm-schema-types mtm-schema-type-Organization">
		<tbody class="mtm-schema-orgnization-types">
		<?php
		mtm_input_text( esc_html__('Organization Name', 'meta-tag-manager'), 'mtm_schema_organization_name', $schema['name']);
		$description = sprintf(esc_html__('Choose a more specific type of %s your site represents. If none of these options fit, then leave it as General/Other.', 'meta-tag-manager'), esc_html__('Organization', 'meta-tag-manager'));
		$description .= '<br>'. esc_html__('Local Business types are currently unavailable, coming soon!', 'meta-tag-manager');
		mtm_input_select( esc_html__('Organization Type', 'meta-tag-manager'), 'mtm_schema_site_type_organization', $org_types, $schema['Organization']['type'], $description);
		?>
		</tbody>
		<tbody class="mtm-schema-orgnization-subtypes">
			<?php
			foreach( $org_subtypes as $type => $specific_org_subtypes ){
				$description = sprintf(esc_html__('Choose a more specific type of %s your site represents. If none of these options fit, then leave it as General/Other.', 'meta-tag-manager'), $org_types[$type]);
				mtm_input_select( esc_html__('Organization Subtype', 'meta-tag-manager'), 'mtm_schema_site_type_organization_specific_'.$type, $specific_org_subtypes, $schema['Organization']['subtype'], $description );
			}
			?>
		</tbody>
	</table>
	<h3><?php esc_html_e('Contact Information', 'meta-tag-manager'); ?></h3>
	<table class="form-table">
		<?php
			mtm_input_radio_binary( esc_html__('Provide Contact Info?', 'meta-tag-manager'), 'mtm_schema_contact', $schema['Contact']['enabled']);
		?>
		<tbody class="mtm-schema-contact-fields">
			<?php
				mtm_input_text( esc_html__('Contact Name or Department', 'meta-tag-manager'), 'mtm_schema_contact_name', $schema['Contact']['name'], esc_html__("This could be a name of a person or department such as 'Customer Service' or 'Technical Support'", 'meta-tag-manager'));
				$contact_page_id = is_numeric($schema['Contact']['url']) ?  $schema['Contact']['url'] : 0;
				$contact_page_url = !is_numeric($schema['Contact']['url']) ?  $schema['Contact']['url'] : '';
			?>
			<tr>
				<th><?php echo sprintf(esc_html__( '%s Page', 'events-manager'),esc_html__('Contact','events-manager')); ?></th>
				<td>
					<?php wp_dropdown_pages(array('name'=>'mtm_schema_contact_page', 'selected'=> $contact_page_id, 'show_option_none'=> esc_html__('Custom URL', 'meta-tag-manager') )); ?>
				</td>
			</tr>
			<?php
				mtm_input_text( esc_html__('Contact URL', 'meta-tag-manager'), 'mtm_schema_contact_url', $contact_page_url);
				$description = sprintf(esc_html__('Enter a phone number in the international format, containing your %s. For example, in the US and Canada : %s'), '<a target="_blank" href="https://en.wikipedia.org/wiki/List_of_country_calling_codes">'.esc_html__('country code', 'meta-tag-manager').'</a>', '+1-800-123-1234');
				mtm_input_text( esc_html__('Contact Phone', 'meta-tag-manager'), 'mtm_schema_contact_telephone', $schema['Contact']['telephone'], $description, '+1-800-123-1234');
				mtm_input_text( esc_html__('Contact Email', 'meta-tag-manager'), 'mtm_schema_contact_email', $schema['Contact']['email'], '', 'contact@yoursite.com');
			?>
		</tbody>
	</table>
	

	<h3><?php esc_html_e('Social/External Profiles', 'meta-tag-manager'); ?></h3>
	<p><?php esc_html_e('If you have any external social media accounts or websites with your profile or a specific page about you, add the full URL here. If recognized by Google they will show up on your search results and provide added legitimacy to your company or brand. If you don\'t have a specific profile below, leave it blank.', 'meta-tag-manager'); ?></p>
	<p><?php esc_html_e('Any other site such as pintrest, wikipedia, tumblr etc. could be added below, click the \'Add URL\' below to add additional profiles.', 'meta-tag-manager'); ?></p>
	<table class="form-table mtm-schema-profiles">
		<?php
		$default_profiles = array('facebook' => '', 'instagram' => '', 'linkedin' => '', 'twitter' => '');
		$schema['profiles'] = empty($schema['profiles']) ? $default_profiles : array_merge($default_profiles, $schema['profiles']);
		mtm_input_text( 'Facebook', 'mtm_schema_profiles[facebook]', $schema['profiles']['facebook'], '', 'https://facebook.com/username');
		mtm_input_text( 'Twitter', 'mtm_schema_profiles[twitter]', $schema['profiles']['twitter'], '', 'https://twitter.com/username');
		mtm_input_text( 'Instagram', 'mtm_schema_profiles[instagram]', $schema['profiles']['instagram'], '', 'https://instagram.com/username');
		mtm_input_text( 'LinkedIn', 'mtm_schema_profiles[linkedin]', $schema['profiles']['linkedin'], '', 'https://www.linkedin.com/in/username');
		foreach( $schema['profiles'] as $k => $v ){
			if( is_numeric($k) ){
				?>
				<tr valign="top" class="mtm-schema-profiles-other">
					<th scope="row">
						<a href="#" class="mtm-schema-profile-remove"><span class="dashicons dashicons-no"></span></a>
						<label for="mtm_schema_profiles">Other</label>
					</th>
					<td>
						<input name="mtm_schema_profiles[]" value="<?php echo esc_url($v); ?>" type="text" id="mtm_schema_profiles" class="widefat" placeholder="https://site.com/about-your-site">
					</td>
				</tr>
				<?php
			}
		}
		?>
		<tr valign="top" id="mtm_schema_profiles_other_row" class="mtm-schema-profiles-other">
			<th scope="row">
				<a href="#" class="mtm-schema-profile-remove"><span class="dashicons dashicons-no"></span></a>
				<label for="mtm_schema_profiles">Other</label>
			</th>
			<td>
				<input name="mtm_schema_profiles[]" value="" type="text" id="mtm_schema_profiles" class="widefat" placeholder="https://site.com/about-your-site">
			</td>
		</tr>
	</table>
	<a href="#" class="button-secondary mtm-schema-profile-add"><?php esc_html_e('Add URL', 'meta-tag-manager'); ?></a>
	
	
	<h3><?php esc_html_e('Google Sitelinks', 'meta-tag-manager'); ?></h3>
	<p><?php esc_html_e('Google allows you to provide extra information about your site which they may use in their search results to produce expanded links and search forms within the search result, increasing the visibility of your search result and the changes of people clicking through to your site.', 'meta-tag-manager'); ?></p>
	<table class="form-table mtm-schema-sitelinks">
		<?php
		$menus =  wp_get_nav_menus();
		$nav_menus = array( 0 => esc_html__('No Sitelinks Map', 'meta-tag-manager') );
		foreach( $menus as $menu ){
			$nav_menus[$menu->term_id] = $menu->name;
		}
		$description = esc_html__("Choose a menu containing the links you'd like Google to include in Sitelinks. This can be a menu you already have, or preferably a dedicated menu for sitelinks. It is not guaranteed they'll use them, but it will certainly increase the chances of them doing so. %s", 'meta-tag-manager');
		$description = sprintf( $description, '<a href="https://developers.google.com/search/docs/advanced/appearance/sitelinks">'. esc_html__('Learn more', 'meta-tag-manager') .'</a>');
		mtm_input_select( esc_html__('Sitelinks Map', 'meta-tag-manager'), 'mtm_schema_sitelinks_menu', $nav_menus, $schema['sitelinks']['menu'], $description);
		
		$description = esc_html__("Google can choose to display a search box below your search result, directly allowing users to search your site. You can choose to enable this or specifically ask Google not to include it. %s", 'meta-tag-manager');
		$description = sprintf( $description, '<a href="https://developers.google.com/search/docs/advanced/structured-data/sitelinks-searchbox">'. esc_html__('Learn more', 'meta-tag-manager') .'</a>');
		$search_options = array( 0 => esc_html__('Enable or Disable', 'meta-tag-manager'), 1 => esc_html__('Enable Sitelinks Search', 'meta-tag-manager'), 2 => esc_html__('Disable Sitelinks Search', 'meta-tag-manager') );
		mtm_input_select( esc_html__('Enable Sitelinks Searchbox?', 'meta-tag-manager'), 'mtm_schema_sitelinks_search', $search_options, $schema['sitelinks']['search'], $description)
		?>
	</table>
	
	<script type="text/javascript">
		jQuery(document).ready( function($) {
			$('input[name="mtm_schema_contact"]').on('change', function(){
				if( $('input[name="mtm_schema_contact"]:checked').val() == 1 ){
					$('tbody.mtm-schema-contact-fields').show();
				}else{
					$('tbody.mtm-schema-contact-fields').hide();
				}
			}).trigger('change');
			$('#mtm_schema_site_type_organization').on('change', function(){
				$('tbody.mtm-schema-orgnization-subtypes tr').hide();
				var value = $(this).find(":selected").val();
				var selected_value = $('#mtm_schema_site_type_organization_specific_'+ value + '_row');
				if( selected_value.length > 0 ) selected_value.show();
			}).trigger('change');
			$('#mtm_schema_site_type').on('change', function(){
				$('table.mtm-schema-types').hide();
				var value = $(this).find(":selected").val();
				var selected_value = $('.mtm-schema-type-'+ value );
				if( selected_value.length > 0 ) selected_value.show();
			}).trigger('change');
			$('#mtm_schema_contact_page').on('change', function(){
				var value = $(this).find(":selected").val();
				if( value > 0 ) {
					$('#mtm_schema_contact_url_row').hide();
				}else{
					$('#mtm_schema_contact_url_row').show();
				}
			}).trigger('change');
			$('.mtm-schema-profile-add').on('click', function(e){
				e.preventDefault();
				var row = $('#mtm_schema_profiles_other_row').clone().removeAttr('id').show();
				$('.mtm-schema-profiles').append( row );
			});
			$('.mtm-schema-profiles').on('click', '.mtm-schema-profile-remove', function(e){
				e.preventDefault();
				$(this).closest('.mtm-schema-profiles-other').remove();
			});
			$('#mtm_schema_enabled').on('change', function(){
				if( $(this).val() > 0 ){
					selected_value.show();
				}else{
				
				}
			}).trigger('change');

			$('#mtm_schema_contact_page').on('change', function(){
				var value = $(this).find(":selected").val();
				if( value == 0 ){
					$('#mtm_schema_contact_url_row').show();
				}else{
					$('#mtm_schema_contact_url_row').hide();
				}
			}).trigger('change');
			
			$('.mtm-schema-save-and-test').on('click', function(){
				var el = $(this);
				el.append('<input type="hidden" name="schema_test_afterwards" value="'+ el.data('validator') +'">');
			});
		});
	</script>
	<?php
	if( !empty($_GET['schema_test']) && $_GET['schema_test'] == 'google' ) echo '<script type="text/javascript">window.open("'. $test_url_google .'");</script>';
	if( !empty($_GET['schema_test']) && $_GET['schema_test'] == 'schema.org' ) echo '<script type="text/javascript">window.open("'. $test_url_schemaorg .'");</script>';
	?>
</div>
<p class="mtm-actions">
	<button type="submit" class="button-primary"><?php esc_html_e('Save Changes','meta-tag-manager'); ?></button>
	<span class="mtm-schema-settings">
	<button type="submit" class="button-primary mtm-schema-save-and-test" data-validator="google"><?php echo sprintf(esc_html__('Save & Test (%s)', 'meta-tag-manager'), 'Google'); ?></button>
	<button type="submit" class="button-primary mtm-schema-save-and-test" data-validator="schema.org"><?php echo sprintf(esc_html__('Save & Test (%s)', 'meta-tag-manager'), 'Schema.org'); ?></button>
	</span>
</p>