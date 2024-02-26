<?php
use Meta_Tag_Manager\Open_Graph;

global $mtm_submit_button;
$og = Open_Graph::get_options( false ); // return with defaults merged in
$test_url_google = "https://search.google.com/test/rich-results?url=" . urlencode(get_site_url());
$test_url_fb = "https://developers.facebook.com/tools/debug/?q=" . urlencode(get_site_url());
$test_url_twitter = "https://cards-dev.twitter.com/validator?url=" . urlencode(get_site_url());

?>
<p>
	<?php esc_html_e('Open Graph is a widely supported protocol which allows services like Facebook, Twitter, LinkedIn and any other site that parses links to your site to obtain more structured information about your website pages.', 'meta-tag-manager'); ?>
</p>
<p>
	<?php esc_html_e('The settings below are specific to the home page of your website.', 'meta-tag-manager'); ?>
	<?php echo sprintf(esc_html__('You can also choose to auto-generate open graph tags below, or generate your own using our meta tag builder in the %s tab above or on individual pages.', 'meta-tag-manager'), esc_html__('Custom Meta Tags','meta-tag-manager')); ?>
</p>
<label><input type="checkbox" name="mtm_og_enabled" class="mtm-settings-binary-trigger" data-trigger-content=".mtm-og-settings" value="1" <?php if( !empty($og['enabled']) ) echo 'checked'; ?>> <?php esc_html_e('Enable Open Graph Features', 'meta-tag-manager'); ?></label>
<div class="mtm-og-settings">
	<h3><?php esc_html_e('Testing and Validation', 'meta-tag-manager'); ?></h3>
	<p>
		<?php
		esc_html_e('Click the buttons below once you have saved your settings, or the \'Save & Test\' buttons at the bottom to save and test. Make sure your information is valid and readable by Google, Facebook, Twitter and others.', 'meta-tag-manager');
		?>
	</p>
	<p>
		<a href="<?php echo esc_url($test_url_google) ?>" target="_blank" class="button-secondary"><?php echo sprintf(esc_html__('Test Open Graph Settings (%s)', 'meta-tag-manager'), 'Google'); ?></a>
		<a href="<?php echo esc_url($test_url_fb) ?>" target="_blank" class="button-secondary"><?php echo sprintf(esc_html__('Test Open Graph Settings (%s)', 'meta-tag-manager'), 'Facebook'); ?></a>
		<a href="<?php echo esc_url($test_url_twitter) ?>" target="_blank" class="button-secondary"><?php echo sprintf(esc_html__('Test Open Graph Settings (%s)', 'meta-tag-manager'), 'Twitter'); ?></a>
	</p>
	<h3><?php esc_html_e('General Information', 'meta-tag-manager'); ?></h3>
	<table class="form-table">
		<?php
		mtm_input_text( esc_html__('Website Name', 'meta-tag-manager'), 'mtm_og_site_title', $og['home']['title'] );
		mtm_input_text( esc_html__('Website Description', 'meta-tag-manager'), 'mtm_og_site_description', $og['home']['description'] );
		$image_id = empty($og['home']['image']) ? get_theme_mod( 'custom_logo' ) : $og['home']['image'];
		$image = intval( $image_id ) > 0 ? wp_get_attachment_image( $image_id ) : '';
		?>
		<tr class="mtm-image-upload" data-action="mtm_get_logo_url">
			<th><?php esc_html_e( 'Logo' ); ?></th>
			<td>
				<div class="mtm-image-upload-preview"><?php echo $image; ?></div>
				<input type="hidden" name="mtm_og_site_logo" class="mtm-image-upload-input" value="<?php echo absint($image_id); ?>" class="regular-text">
				<input type='button' class="button-primary mtm-image-upload-submit" value="<?php esc_attr_e('Select Image'); ?>" >
				<input type='button' class="button-secondary mtm-image-upload-reset" value="<?php esc_attr_e('Remove'); ?>" >
				<p><em><?php echo sprintf( esc_html__('The image size must be at least %s and one of the following formats : %s', 'meta-tag-manager'), '112px x 112px', 'BMP, GIF, JPEG, PNG, WebP or SVG'); ?></em></p>
			</td>
		</tr>
		<?php
		$description = esc_html__('If enabled, all posts, pages and custom post types will have default open graph meta tags generated.', 'meta-tag-manager');
		mtm_input_radio_binary( esc_html__('Generate Open Graph for Posts/Pages?', 'meta-tag-manager'), 'mtm_og_generate_singular', $og['generate_singular'], $description );
		?>
	</table>
	
	<h3><?php esc_html_e('Twitter Cards', 'meta-tag-manager'); ?></h3>
	<p>
		<?php esc_html_e('Twitter has an addition open graph format called \'Twitter Cards\', which are similar and complimentary to Open Graph meta tags. You can enable the twitter:card meta tags and add additional information which will give more meaning to your shared links on Twitter.', 'meta-tag-manager'); ?>
		<a href="https://developer.twitter.com/en/docs/twitter-for-websites/cards/guides/getting-started" target="_blank"><?php esc_html_e('Learn More', 'meta-tag-manager'); ?></a>
	</p>
	<p><?php echo sprintf( esc_html__('If enabled, the following meta tag will be added to your Open Graph meta tags : %s', 'meta-tag-manager'), '<code>&lt;meta name="twitter:card" content="summary" /&gt;</code>'); ?></p>
	<p>
		<label><input type="checkbox" name="mtm_og_twitter_enabled" class="mtm-settings-binary-trigger" data-trigger-content=".mtm-og-twitter-card" value="1" <?php if( !empty($og['twitter']['enabled']) ) echo 'checked'; ?>> <?php esc_html_e('Add Twitter Card Meta', 'meta-tag-manager'); ?></label>
	</p>
	<div class="mtm-og-twitter-card">
		<p><?php echo sprintf(esc_html__('You can also generate twitter tags (such as the creator username) via our meta tag builder in the %s settings tab above or on individual pages.', 'meta-tag-manager'), esc_html__('Custom Meta Tags','meta-tag-manager')); ?></p>
		<table class="form-table">
			<?php
			$description = esc_html__('@username on twitter.com for this website or comapny.', 'meta-tag-manager') .' '. esc_html__('This is optional and will be ignored if left blank.', 'meta-tag-manager');
			mtm_input_text( esc_html__('Website Username', 'meta-tag-manager'), 'mtm_og_twitter_site', $og['twitter']['site'], $description, '@username' );
			$description = esc_html__('@username on twitter.com of the content creator / author.', 'meta-tag-manager') .' '. esc_html__('This card will only be shown on the front-page.', 'meta-tag-manager') .' '. esc_html__('This is optional and will be ignored if left blank.', 'meta-tag-manager');
			mtm_input_text( esc_html__('Creator Username', 'meta-tag-manager'), 'mtm_og_twitter_creator', $og['twitter']['creator'], $description, '@username' );
			?>
		</table>
	</div>
	<?php
	if( !empty($_GET['og_test']) && $_GET['og_test'] == 'google' ) echo '<script type="text/javascript">window.open("'. $test_url_google .'");</script>';
	if( !empty($_GET['og_test']) && $_GET['og_test'] == 'fb' ) echo '<script type="text/javascript">window.open("'. $test_url_fb .'");</script>';
	if( !empty($_GET['og_test']) && $_GET['og_test'] == 'twitter' ) echo '<script type="text/javascript">window.open("'. $test_url_twitter .'");</script>';
	?>
</div>

<p class="mtm-actions">
	<button type="submit" class="button-primary"><?php esc_html_e('Save Changes','meta-tag-manager'); ?></button>
</p>
<p class="mtm-actions mtm-og-settings">
	<button type="submit" class="button-primary mtm-og-save-and-test" data-validator="google"><?php echo sprintf(esc_html__('Save & Test (%s)', 'meta-tag-manager'), 'Google'); ?></button>
	<button type="submit" class="button-primary mtm-og-save-and-test" data-validator="fb"><?php echo sprintf(esc_html__('Save & Test (%s)', 'meta-tag-manager'), 'Facebook'); ?></button>
	<button type="submit" class="button-primary mtm-og-save-and-test" data-validator="twitter"><?php echo sprintf(esc_html__('Save & Test (%s)', 'meta-tag-manager'), 'Twitter'); ?></button>
</p>
<script type="text/javascript">
	jQuery(document).ready( function($) {
		$('.mtm-og-save-and-test').on('click', function(){
			var el = $(this);
			el.append('<input type="hidden" name="og_test_afterwards" value="'+ el.data('validator') +'">');
		});
	});
</script>