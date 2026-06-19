<?php
/**
 * IdentityManager
 * Runtime display override only (no DB user field changes)
 * White theme compatibility: topbar uses ->first_name
 */

define('JB_IDENTITYMANAGER', ossn_route()->com . 'IdentityManager/');

function jb_idm_clean_mode($mode, $default = 'full_name', $allow_empty = false) {
	$mode = trim((string)$mode);
	$allowed = array('full_name', 'username', 'at_username');
	if ($allow_empty && $mode === '') {
		return '';
	}
	return in_array($mode, $allowed, true) ? $mode : $default;
}

function jb_idm_get_mode() {
	$S = (new OssnComponents())->getSettings('IdentityManager');
	if (is_object($S) && !empty($S->mode)) {
		return jb_idm_clean_mode($S->mode);           // canonical
	}
	if (is_object($S) && !empty($S->jb_idm_mode)) {
		return jb_idm_clean_mode($S->jb_idm_mode);    // legacy mirror
	}
	return 'full_name';
}

/**
 * Read a component setting (canonical keys).
 */
function jb_idm_setting($key, $default = null) {
	$S = (new OssnComponents())->getSettings('IdentityManager');
	if (is_object($S) && isset($S->{$key}) && $S->{$key} !== '' && $S->{$key} !== null) {
		return $S->{$key};
	}
	return $default;
}

function jb_idm_setting_on($key, $default = 'off') {
	$value = (string) jb_idm_setting($key, $default);
	return $value === 'on' || $value === '1';
}

/**
 * Per-user preferences via OSSN annotations (persistent, OSSN-native).
 * One annotation per user:
 *   type:         identitymanager_pref
 *   owner_guid:   <user_guid>
 *   subject_guid: <user_guid>
 * Stored fields as annotation entities:
 *   idm_mode, idm_ctx_feed, ...
 */
function jb_idm_user_pref_get($user_guid) {
	$user_guid = (int)$user_guid;
	if ($user_guid <= 0) {
		return false;
	}
	if (!function_exists('ossn_get_annotations')) {
		return false;
	}
	$rows = ossn_get_annotations(array(
		'type'         => 'identitymanager_pref',
		'owner_guid'   => $user_guid,
		'subject_guid' => $user_guid,
		'limit'        => 1,
		'page_limit'   => false,
		'offset'       => false,
		'order_by'     => 'a.id DESC',
	));
	if (is_array($rows) && !empty($rows)) {
		return $rows[0];
	}
	return false;
}

/**
 * Effective mode:
 * - Global default is jb_idm_get_mode()
 * - If admin enables enable_user_overrides=on AND user has idm_mode set, prefer that.
 */

function jb_idm_current_place() {
        $ctx = function_exists('ossn_get_context') ? ossn_get_context() : '';
        $url = function_exists('current_url') ? current_url() : '';

        if ($ctx === 'home' || $ctx === 'index') {
                return 'feed';
        }
        if ($ctx === 'u' || $ctx === 'avatar' || $ctx === 'cover') {
                return 'profile';
        }
        if ($ctx === 'post' || (is_string($url) && preg_match('~/comment|/post~i', $url))) {
                return 'comments';
        }
        if ($ctx === 'site_members' || (is_string($url) && preg_match('~site_members|friends~i', $url))) {
                return 'userlist';
        }
        return 'global';
}

function jb_idm_context_enabled($place) {
	$map = array(
		'feed'     => 'ctx_feed',
		'comments' => 'ctx_comments',
		'profile'  => 'ctx_profile',
		'userlist' => 'ctx_userlist',
	);
	if (!isset($map[$place])) {
		return true;
	}
	return jb_idm_setting_on($map[$place], 'on');
}

function jb_idm_user_excluded(OssnUser $u) {
	$username = isset($u->username) ? trim((string)$u->username) : '';
	$excluded = (string) jb_idm_setting('exclude_usernames', '');
	if ($username !== '' && trim($excluded) !== '') {
		$names = preg_split('/[\s,]+/', $excluded, -1, PREG_SPLIT_NO_EMPTY);
		foreach ($names as $name) {
			if (strcasecmp($username, $name) === 0) {
				return true;
			}
		}
	}

	$is_admin = false;
	if (method_exists($u, 'isAdmin')) {
		try {
			$is_admin = (bool) $u->isAdmin();
		} catch (Throwable $e) {
			$is_admin = false;
		}
	}
	if ($is_admin && jb_idm_setting_on('exclude_admins')) {
		return true;
	}

	$is_moderator = false;
	if (method_exists($u, 'canModerate')) {
		try {
			$is_moderator = (bool) $u->canModerate();
		} catch (Throwable $e) {
			$is_moderator = false;
		}
	}
	if (!$is_admin && $is_moderator && jb_idm_setting_on('exclude_moderators')) {
		return true;
	}

	return false;
}

function jb_idm_get_effective_mode(OssnUser $u) {
        $mode = jb_idm_get_mode();
        $place = jb_idm_current_place();
        if (!jb_idm_context_enabled($place)) {
                return 'full_name';
        }

        $enabled = (string) jb_idm_setting('enable_user_overrides', 'off');
        if ($enabled !== 'on') {
                return $mode;
        }

        $pref = jb_idm_user_pref_get($u->guid);
        if (!is_object($pref)) {
                return $mode;
        }

        $place_key = 'idm_mode_' . $place;

        if (!empty($pref->{$place_key})) {
                return jb_idm_clean_mode($pref->{$place_key});
        }
        if (!empty($pref->idm_mode_global)) {
                return jb_idm_clean_mode($pref->idm_mode_global);
        }
        return $mode;
}


/**
 * IMPORTANT:
 * If we previously overwrote $user->fullname to "@username",
 * we must not rely on $user->fullname to restore real full name.
 * Fetch from DB to force the true full name.
 */
function jb_idm_db_name_parts($guid, $fallback_first = '', $fallback_last = '', $fallback_fullname = '') {
	$guid = (int)$guid;
	if ($guid <= 0) {
		$fullname = trim((string)$fallback_fullname);
		if ($fullname === '') {
			$fullname = trim((string)$fallback_first . ' ' . (string)$fallback_last);
		}
		return array(
			'first_name' => (string)$fallback_first,
			'last_name'  => (string)$fallback_last,
			'fullname'   => $fullname,
		);
	}
	$db = new OssnDatabase();
	$db->statement("SELECT first_name,last_name FROM ossn_users WHERE guid='{$guid}' LIMIT 1");
	$db->execute();
	$row = $db->fetch();
	if ($row && (isset($row->first_name) || isset($row->last_name))) {
		$fn = isset($row->first_name) ? (string)$row->first_name : '';
		$ln = isset($row->last_name)  ? (string)$row->last_name  : '';
		$name = trim($fn . ' ' . $ln);
		if ($name !== '') {
			return array(
				'first_name' => $fn,
				'last_name'  => $ln,
				'fullname'   => $name,
			);
		}
	}
	$fullname = trim((string)$fallback_fullname);
	if ($fullname === '') {
		$fullname = trim((string)$fallback_first . ' ' . (string)$fallback_last);
	}
	return array(
		'first_name' => (string)$fallback_first,
		'last_name'  => (string)$fallback_last,
		'fullname'   => $fullname,
	);
}

function jb_idm_db_fullname($guid, $fallback_fullname = '') {
	$parts = jb_idm_db_name_parts($guid, '', '', $fallback_fullname);
	return $parts['fullname'];
}

function jb_idm_should_skip_mutation() {
	if (isset($_GET['section']) && $_GET['section'] === 'basic') {
		return true;
	}
	if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'user/edit') {
		return true;
	}
	return false;
}

function jb_idm_apply_display(OssnUser $u) {
	$parts = jb_idm_db_name_parts(
		$u->guid,
		isset($u->first_name) ? $u->first_name : '',
		isset($u->last_name) ? $u->last_name : '',
		isset($u->fullname) ? $u->fullname : ''
	);
	$mode = jb_idm_user_excluded($u) ? 'full_name' : jb_idm_get_effective_mode($u);

	if ($mode === 'username') {
		$label = (string)$u->username;
	} elseif ($mode === 'at_username') {
		$label = '@' . (string)$u->username;
	} else {
		$u->first_name = $parts['first_name'];
		$u->last_name  = $parts['last_name'];
		$u->fullname   = $parts['fullname'];
		return;
	}

	// Apply runtime display consistently. White theme topbar reads first_name.
	$u->fullname   = $label;
	$u->first_name = $label;
	$u->last_name  = '';
}

function jb_idm_user_fetched_object_hook($hook, $type, $return, $params) {
	// Guard: do not rewrite user display while editing/saving profile basics.
	if (jb_idm_should_skip_mutation()) {
		return $return;
	}

	if ($return instanceof OssnUser) {
		jb_idm_apply_display($return);
	}
	return $return;
}


function jb_idm_profile_edit_section($hook, $type, $return, $params) {
	// OSSN profile edit uses ?section=<name>
	if (isset($params['section']) && $params['section'] === 'identitymanager') {
		return ossn_plugin_view('account_settings/IdentityManager/tab');
	}
	return $return;
}

function jb_identitymanager_init() {
	ossn_register_com_panel('IdentityManager', 'settings');
	ossn_register_action('jb_idm/settings/save', JB_IDENTITYMANAGER . 'actions/settings_save.php');
	ossn_register_action('identitymanager/user_settings_save', JB_IDENTITYMANAGER . 'actions/user_settings_save.php');
	// Add Profile -> Edit tab
	if (function_exists('ossn_isLoggedin') && ossn_isLoggedin()) {
		ossn_add_hook('profile', 'edit:section', 'jb_idm_profile_edit_section');
		ossn_register_menu_item('profile/edit/tabs', array(
				'name' => 'identitymanager',
				'href' => '?section=identitymanager',
				'text' => 'Identity Manager',
		));
	}


	// Mutate fetched user objects
	ossn_add_hook('user', 'get', 'jb_idm_user_fetched_object_hook');

	// Mutate logged-in user object for topbar
	if (function_exists('ossn_isLoggedin') && ossn_isLoggedin() && !jb_idm_should_skip_mutation()) {
		$u = ossn_loggedin_user();
		if ($u instanceof OssnUser) {
			jb_idm_apply_display($u);
		}
	}
}
ossn_register_callback('ossn', 'init', 'jb_identitymanager_init');
