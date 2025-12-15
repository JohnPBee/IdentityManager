<?php
/**
 * IdentityManager - User Settings Tab
 * Shows user override UI only when admin enables it globally.
 */

$user = ossn_loggedin_user();
if (!$user) {
	redirect('home');
}

// Gate: admin must enable this feature globally
$S = (new OssnComponents())->getSettings('IdentityManager');
$enabled = (is_object($S) && isset($S->enable_user_overrides)) ? (string)$S->enable_user_overrides : 'off';
if ($enabled !== 'on') {
	echo '<div class="ossn-message-box ossn-message-box-warning">' . ossn_print('ossn:admin:error') . '</div>';
	return;
}

// Pull current user preference (if any) via helper in ossn_com.php
$pref = function_exists('jb_idm_user_pref_get') ? jb_idm_user_pref_get($user->guid) : false;

$params = array(
	'username' => $user->username,
	'idm_mode_global'   => (is_object($pref) && isset($pref->idm_mode_global))   ? (string)$pref->idm_mode_global   : '',
	'idm_mode_feed'     => (is_object($pref) && isset($pref->idm_mode_feed))     ? (string)$pref->idm_mode_feed     : '',
	'idm_mode_comments' => (is_object($pref) && isset($pref->idm_mode_comments)) ? (string)$pref->idm_mode_comments : '',
	'idm_mode_profile'  => (is_object($pref) && isset($pref->idm_mode_profile))  ? (string)$pref->idm_mode_profile  : '',
	'idm_mode_userlist' => (is_object($pref) && isset($pref->idm_mode_userlist)) ? (string)$pref->idm_mode_userlist : '',
);
echo ossn_view_form('account_settings/identitymanager', array(
	'action'    => ossn_site_url() . 'action/identitymanager/user_settings_save',
	'component' => 'IdentityManager',
	'params'    => $params,
), false);
