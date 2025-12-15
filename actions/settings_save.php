<?php
/**
 * IdentityManager
 * Save Settings Action (Component Settings API)
 *
 * What this changes:
 * - Saves settings into IdentityManager component entities using keys:
 *   mode, ctx_feed, ctx_comments, ctx_profile, ctx_userlist, exclude_admins,
 *   exclude_moderators, exclude_usernames
 *
 * Rollback:
 * - Restore from your tar backup in /var/www/ossn_data/jb_idm/ (created earlier)
 */

if (!ossn_isAdminLoggedin()) {
	ossn_trigger_message(ossn_print('ossn:admin:error'), 'error');
	redirect(REF);
}

// Accept current form field names (jb_idm_*) but store canonical component keys.
$mode = input('jb_idm_mode');
if (empty($mode)) {
	$mode = 'full_name';
}

$vars = array(
// Canonical keys (new)
    'mode'               => $mode,
    'enable_user_overrides' => input('jb_idm_enable_user_overrides') ? 'on' : 'off',
    'exclude_usernames'  => (string) input('jb_idm_exclude_usernames'),
    'exclude_admins'     => input('jb_idm_exclude_admins') ? 'on' : 'off',
    'exclude_moderators' => input('jb_idm_exclude_moderators') ? 'on' : 'off',
    'ctx_feed'           => input('jb_idm_ctx_feed') ? 'on' : 'off',
    'ctx_comments'       => input('jb_idm_ctx_comments') ? 'on' : 'off',
    'ctx_profile'        => input('jb_idm_ctx_profile') ? 'on' : 'off',
    'ctx_userlist'       => input('jb_idm_ctx_userlist') ? 'on' : 'off',

    // Legacy mirror keys (old)
    'jb_idm_mode'               => $mode,
    'jb_idm_exclude_usernames'  => (string) input('jb_idm_exclude_usernames'),
    'jb_idm_exclude_admins'     => input('jb_idm_exclude_admins') ? 'on' : 'off',
    'jb_idm_exclude_moderators' => input('jb_idm_exclude_moderators') ? 'on' : 'off',
    'jb_idm_ctx_feed'           => input('jb_idm_ctx_feed') ? 'on' : 'off',
    'jb_idm_ctx_comments'       => input('jb_idm_ctx_comments') ? 'on' : 'off',
    'jb_idm_ctx_profile'        => input('jb_idm_ctx_profile') ? 'on' : 'off',
    'jb_idm_ctx_userlist'       => input('jb_idm_ctx_userlist') ? 'on' : 'off',

);
$components = new OssnComponents();
$ok = $components->setSettings('IdentityManager', $vars);

if ($ok) {
	ossn_trigger_message(ossn_print('settings:saved'), 'success');
} else {
	ossn_trigger_message(ossn_print('ossn:admin:error'), 'error');
}

redirect(REF);
