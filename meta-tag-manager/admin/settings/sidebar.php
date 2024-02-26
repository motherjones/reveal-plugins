
<div id="postbox-container-1" class="postbox-container">
	<div id="mtm-plugin-info" class="postbox ">
		<button type="button" class="handlediv button-link"
		        aria-expanded="true">
			<span class="screen-reader-text"><?php echo sprintf(esc_html__('Toggle panel: %s'), esc_html__('About This Plugin','meta-tag-manager')); ?></span>
			<span class="toggle-indicator" aria-hidden="true"></span>
		</button>
		<h2 class="hndle ui-sortable-handle">
			<span><?php esc_html_e('About This Plugin','meta-tag-manager'); ?></span>
		</h2>
		<div class="inside">
			<p>
				<?php echo sprintf(esc_html__('This plugin was developed by %s.', 'meta-tag-manager'), '<a href="http://msyk.es/?utm_source=meta-tag-manager&utm_medium=settings&utm_campaign=plugins" target="_blank">Marcus Sykes</a>'); ?>
			</p>
			<p style="color:green; font-weight:bold;">
				<?php
				echo sprintf(esc_html__('Please leave us a %s review on %s to show your support and help us keep making this plugin better!','meta-tag-manager'),
					'<a href="http://wordpress.org/support/view/plugin-reviews/meta-tag-manager?filter=5" target="_blank">★★★★★</a>',
					'<a href="https://wordpress.org/plugins/meta-tag-manager/" target="_blank">WordPress.org</a>'
				);
				?>
			</p>
		</div>
	</div>
	<div id="mtm-plugin-support" class="postbox ">
		<button type="button" class="handlediv button-link"
		        aria-expanded="true">
			<span class="screen-reader-text"><?php echo sprintf(esc_html__('Toggle panel: %s'), esc_html__('Need Help?','meta-tag-manager')); ?></span>
			<span class="toggle-indicator" aria-hidden="true"></span>
		</button>
		<h2 class="hndle ui-sortable-handle">
			<span><?php esc_html_e('Need Help?','meta-tag-manager'); ?></span>
		</h2>
		<div class="inside">
			<p>
				<?php echo sprintf(esc_html__('Please visit our %s if you have any questions.', 'meta-tag-manager'),
					'<a href="http://wordpress.org/support/plugin/meta-tag-manager/" target="_blank">'.esc_html__('Support Forum','meta-tag-manager').'</a>'); ?>
			</p>
		</div>
	</div>
</div>