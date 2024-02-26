<?php
function mtm_input_select_items($array, $saved_value) {
	$output = "";
	foreach($array as $key => $item) {
		$selected ='';
		if ($key == $saved_value)
			$selected = "selected='selected'";
		$output .= "<option value='".esc_attr($key)."' $selected >".esc_html($item)."</option>\n";
		
	}
	echo $output;
}

function mtm_input_checkbox_items($name, $array, $saved_values, $horizontal = true) {
	$output = "";
	foreach($array as $key => $item) {
		$checked = "";
		if (in_array($key, $saved_values)) $checked = "checked='checked'";
		$output .= "<label><input type='checkbox' name='".esc_attr($name)."[]' value='".esc_attr($key)."' $checked> ".esc_html($item)."</label>&nbsp; ";
		if(!$horizontal)
			$output .= "<br>\n";
	}
	echo $output;
	
}
function mtm_input_text($title, $name, $value='', $description ='', $ph = false, $classes = '') {
	?>
	<tr valign="top" id='<?php echo esc_attr($name);?>_row'>
		<th scope="row"><label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($title); ?></label></th>
		<td>
			<input name="<?php echo esc_attr($name) ?>" value="<?php echo esc_attr($value); ?>" type="text" id="<?php echo esc_attr($name) ?>" class="widefat" <?php if($ph) echo 'placeholder="'.esc_attr($ph).'"'; ?> >
			<?php if( !empty($description)): ?>
				<p><em><?php echo $description; ?></em></p>
			<?php endif; ?>
		</td>
	</tr>
	<?php
}

function mtm_input_password($title, $name, $value, $description ='') {
	?>
	<tr valign="top" id='<?php echo esc_attr($name);?>_row'>
		<th scope="row"><label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($title); ?></label></th>
		<td>
			<input name="<?php echo esc_attr($name) ?>" value="<?php echo esc_attr($value); ?>" type="password" id="<?php echo esc_attr($title) ?>" class="widefat" >
			<?php if(!empty($description)): ?><p><em><?php echo $description; ?></em></p><?php endif; ?>
		</td>
	</tr>
	<?php
}

function mtm_input_textarea($title, $name, $value, $description ='') {
	?>
	<tr valign="top" id='<?php echo esc_attr($name);?>_row'>
		<th scope="row"><label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($title); ?></label></th>
		<td>
			<textarea name="<?php echo esc_attr($name) ?>" id="<?php echo esc_attr($name) ?>"  class="widefat" cols="60"><?php echo esc_attr($value);?></textarea>
			<?php if(!empty($description)): ?><p><em><?php echo $description; ?></em></p><?php endif; ?>
		</td>
	</tr>
	<?php
}

function mtm_input_radio($name, $options, $option = '', $title='') {
	?>
	<tr valign="top" id='<?php echo esc_attr($name);?>_row'>
		<?php if( !empty($title) ): ?>
		<th scope="row"><?php  echo esc_html($title); ?></th>
		<td>
			<?php else: ?>
		<td colspan="2">
			<?php endif; ?>
			<table>
				<?php foreach($options as $value => $text): ?>
					<tr>
						<td><input id="<?php echo esc_attr($name) ?>_<?php echo esc_attr($value); ?>" name="<?php echo esc_attr($name) ?>" type="radio" value="<?php echo esc_attr($value); ?>" <?php if($option == $value) echo "checked='checked'"; ?>></td>
						<td><label for="<?php echo esc_attr($name); ?>"><?php echo $text ?></label></td>
					</tr>
				<?php endforeach; ?>
			</table>
		</td>
	</tr>
	<?php
}

function mtm_input_radio_binary($title, $name, $value='', $description='', $option_names = '', $trigger='') {
	if( empty($option_names) ) $option_names = array(0 => __('No','events-manager'), 1 => __('Yes','events-manager'));
	$trigger_att = ($trigger) ? 'data-trigger="'.esc_attr($trigger).'" class="em-trigger"':'';
	?>
	<tr valign="top" id='<?php echo $name;?>_row'>
		<th scope="row"><?php echo esc_html($title); ?></th>
		<td>
			<label><?php echo $option_names[1]; ?> <input id="<?php echo esc_attr($name) ?>_yes" name="<?php echo esc_attr($name) ?>" type="radio" value="1" <?php if($value) echo "checked='checked'"; echo $trigger_att; ?> ></label>&nbsp;&nbsp;&nbsp;
			<label><?php echo $option_names[0]; ?> <input  id="<?php echo esc_attr($name) ?>_no" name="<?php echo esc_attr($name) ?>" type="radio" value="0" <?php if(!$value) echo "checked='checked'"; echo $trigger_att; ?> ></label>
			<?php if(!empty($description)): ?><p><em><?php echo $description; ?></em></p><?php endif; ?>
		</td>
	</tr>
	<?php
}

function mtm_input_select($title, $name, $list, $option_value = null, $description='', $classes = '') {
	$required = !empty($list['']) ? 'required':'';
	?>
	<tr valign="top" id='<?php echo esc_attr($name);?>_row' class="<?php echo esc_attr($classes); ?>">
		<th scope="row"><label for="<?php echo esc_attr($name); ?>"><?php echo esc_html($title); ?></label></th>
		<td>
			<select name="<?php echo esc_attr($name); ?>" id="<?php echo esc_attr($name); ?>"  class="widefat" <?php echo $required; ?>>
				<?php
				foreach($list as $key => $value) {
					if( is_array($value) ){
						?><optgroup label="<?php echo $key; ?>"><?php
						foreach( $value as $key_group => $value_group ){
							?>
							<option value='<?php echo esc_attr($key_group) ?>' <?php echo ("$key_group" == $option_value) ? "selected='selected' " : ''; ?>>
								<?php echo esc_html($value_group); ?>
							</option>
							<?php
						}
						?></optgroup><?php
					}else{
						$ph = '';
						if( $key === '' ) {
							$ph = $option_value === null ? ' disabled selected hidden' : ' disabled hidden';
						}
						?>
						<option value='<?php echo esc_attr($key) ?>' <?php echo ("$key" == $option_value) ? "selected" : ''; echo $ph ?>>
							<?php echo esc_html($value); ?>
						</option>
						<?php
					}
				}
				?>
			</select>
			<?php if(!empty($description)): ?><p><em><?php echo $description; ?></em></p><?php endif; ?>
		</td>
	</tr>
	<?php
}