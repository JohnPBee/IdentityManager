<?php
function idm_sel($current, $value) {
	return ((string)$current === (string)$value) ? 'selected="selected"' : '';
}
$g   = isset($params['idm_mode_global']) ? (string)$params['idm_mode_global'] : '';
$fd  = isset($params['idm_mode_feed']) ? (string)$params['idm_mode_feed'] : '';
$cm  = isset($params['idm_mode_comments']) ? (string)$params['idm_mode_comments'] : '';
$pr  = isset($params['idm_mode_profile']) ? (string)$params['idm_mode_profile'] : '';
$ul  = isset($params['idm_mode_userlist']) ? (string)$params['idm_mode_userlist'] : '';

/* Make selects shorter/nicer (override theme "full width" look) */
$sel_style = 'style="max-width: 340px; width: 100%; display: inline-block;"';
?>
<div>
	<label>Default identity display preference <span style="font-size:12px; color:#6b7280; font-weight:400;">(can be overridden below)</span></label><br>
	<select name="idm_mode_global" <?php echo $sel_style; ?>>
		<option value="" <?php echo idm_sel($g,''); ?>>Use admin default</option>
		<option value="full_name" <?php echo idm_sel($g,'full_name'); ?>>Full Name</option>
		<option value="username" <?php echo idm_sel($g,'username'); ?>>Username</option>
		<option value="at_username" <?php echo idm_sel($g,'at_username'); ?>>@Username</option>
	</select>
	
</div>

<hr style="margin:12px 0;">

<div>
	<label>Feed</label><br>
	<select name="idm_mode_feed" <?php echo $sel_style; ?>>
		<option value="" <?php echo idm_sel($fd,''); ?>>Use default (above)</option>
		<option value="full_name" <?php echo idm_sel($fd,'full_name'); ?>>Full Name</option>
		<option value="username" <?php echo idm_sel($fd,'username'); ?>>Username</option>
		<option value="at_username" <?php echo idm_sel($fd,'at_username'); ?>>@Username</option>
	</select>
</div>

<div style="margin-top:10px;">
	<label>Comments</label><br>
	<select name="idm_mode_comments" <?php echo $sel_style; ?>>
		<option value="" <?php echo idm_sel($cm,''); ?>>Use default (above)</option>
		<option value="full_name" <?php echo idm_sel($cm,'full_name'); ?>>Full Name</option>
		<option value="username" <?php echo idm_sel($cm,'username'); ?>>Username</option>
		<option value="at_username" <?php echo idm_sel($cm,'at_username'); ?>>@Username</option>
	</select>
</div>

<div style="margin-top:10px;">
	<label>Profile</label><br>
	<select name="idm_mode_profile" <?php echo $sel_style; ?>>
		<option value="" <?php echo idm_sel($pr,''); ?>>Use default (above)</option>
		<option value="full_name" <?php echo idm_sel($pr,'full_name'); ?>>Full Name</option>
		<option value="username" <?php echo idm_sel($pr,'username'); ?>>Username</option>
		<option value="at_username" <?php echo idm_sel($pr,'at_username'); ?>>@Username</option>
	</select>
</div>

<div style="margin-top:10px;">
	<label>User lists</label><br>
	<select name="idm_mode_userlist" <?php echo $sel_style; ?>>
		<option value="" <?php echo idm_sel($ul,''); ?>>Use default (above)</option>
		<option value="full_name" <?php echo idm_sel($ul,'full_name'); ?>>Full Name</option>
		<option value="username" <?php echo idm_sel($ul,'username'); ?>>Username</option>
		<option value="at_username" <?php echo idm_sel($ul,'at_username'); ?>>@Username</option>
	</select>
</div>

<div style="margin-top:12px;">
	<input type="submit" class="btn btn-primary" value="<?php echo ossn_print('save'); ?>" />
</div>
