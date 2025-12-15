<?php
/**
 * IdentityManager Settings (Component Settings)
 *
 * Reads canonical component keys:
 *   mode, ctx_feed, ctx_comments, ctx_profile, ctx_userlist,
 *   exclude_admins, exclude_moderators, exclude_usernames
 *
 * Falls back to old jb_idm_* keys if present.
 */
$action = ossn_site_url('action/jb_idm/settings/save');

$com = new OssnComponents();
$S   = $com->getSettings('IdentityManager');

function jb_idm_admin_get_any($S, array $keys, $default = '') {
	if (!is_object($S)) {
		return $default;
	}
	foreach ($keys as $k) {
		if (isset($S->$k)) {
			return $S->$k;
		}
	}
	return $default;
}

// Canonical keys first, then legacy fallbacks
$mode              = jb_idm_admin_get_any($S, array('mode', 'jb_idm_mode'), 'full_name');

$ctx_feed          = jb_idm_admin_get_any($S, array('ctx_feed', 'jb_idm_ctx_feed'), 'off');
$ctx_comments      = jb_idm_admin_get_any($S, array('ctx_comments', 'jb_idm_ctx_comments'), 'off');
$ctx_profile       = jb_idm_admin_get_any($S, array('ctx_profile', 'jb_idm_ctx_profile'), 'off');
$ctx_userlist      = jb_idm_admin_get_any($S, array('ctx_userlist', 'jb_idm_ctx_userlist'), 'off');

$exclude_admins    = jb_idm_admin_get_any($S, array('exclude_admins', 'jb_idm_exclude_admins'), 'off');
$exclude_mods      = jb_idm_admin_get_any($S, array('exclude_moderators', 'jb_idm_exclude_moderators'), 'off');
$exclude_usernames = jb_idm_admin_get_any($S, array('exclude_usernames', 'jb_idm_exclude_usernames'), '');

$enable_user_overrides = jb_idm_admin_get_any($S, array('enable_user_overrides', 'jb_idm_enable_user_overrides'), 'off');

?>
<form action="<?php echo $action; ?>" class="ossn-form" method="post">
	<?php echo ossn_plugin_view('input/security_token'); ?>

	<div class="ossn-components-settings">
		<div class="margin-top-10">
			<label>Display Mode</label>
			<select name="jb_idm_mode">
				<option value="full_name" <?php if($mode == 'full_name') echo 'selected'; ?>>Full Name</option>
				<option value="username" <?php if($mode == 'username') echo 'selected'; ?>>Username</option>
				<option value="at_username" <?php if($mode == 'at_username') echo 'selected'; ?>>@Username</option>
			</select>
		</div>

		<div class="margin-top-10">
			<label>User Overrides</label><br />
			<input type="checkbox" name="jb_idm_enable_user_overrides" <?php if($enable_user_overrides == 'on' || $enable_user_overrides == '1') echo 'checked'; ?> />
			Allow users to choose their own identity display mode (Profile → Edit)
		</div>


		<div class="margin-top-10">
			<label>Apply In</label><br />
			<input type="checkbox" name="jb_idm_ctx_feed" <?php if($ctx_feed == 'on' || $ctx_feed == '1') echo 'checked'; ?> /> Feed / Wall <br />
			<input type="checkbox" name="jb_idm_ctx_comments" <?php if($ctx_comments == 'on' || $ctx_comments == '1') echo 'checked'; ?> /> Comments <br />
			<input type="checkbox" name="jb_idm_ctx_profile" <?php if($ctx_profile == 'on' || $ctx_profile == '1') echo 'checked'; ?> /> Profile header / User pages <br />
			<input type="checkbox" name="jb_idm_ctx_userlist" <?php if($ctx_userlist == 'on' || $ctx_userlist == '1') echo 'checked'; ?> /> User lists
		</div>

		<div class="margin-top-10">
			<label>Exclusions</label><br />
			<input type="checkbox" name="jb_idm_exclude_admins" <?php if($exclude_admins == 'on' || $exclude_admins == '1') echo 'checked'; ?> /> Exclude admins <br />
			<input type="checkbox" name="jb_idm_exclude_moderators" <?php if($exclude_mods == 'on' || $exclude_mods == '1') echo 'checked'; ?> /> Exclude moderators
		</div>

		<div class="margin-top-10">
			<label>Exclude Usernames</label>
			<input type="text" name="jb_idm_exclude_usernames" value="<?php echo htmlspecialchars((string)$exclude_usernames, ENT_QUOTES, 'UTF-8'); ?>"/>
		</div>

		<br />
		<input type="submit" class="btn btn-success" value="<?php echo ossn_print('save'); ?>"/>
	</div>
</form>
