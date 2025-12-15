<?php
/**
 * IdentityManager - Save User Preference (per-user override)
 *
 * Storage: OSSN annotations
 *   type: identitymanager_pref
 *   owner_guid: user_guid
 *   subject_guid: user_guid
 * Annotation entities:
 *   idm_mode_global, idm_mode_feed, idm_mode_comments, idm_mode_profile, idm_mode_userlist
 *
 * Empty value => "inherit" (remove that override).
 */

if (!ossn_isLoggedin()) {
        redirect('home');
}
$user = ossn_loggedin_user();
if (!$user || empty($user->guid)) {
        redirect('home');
}

// Gate: admin must enable user overrides globally
$S = (new OssnComponents())->getSettings('IdentityManager');
$enabled = (is_object($S) && isset($S->enable_user_overrides)) ? (string)$S->enable_user_overrides : 'off';
if ($enabled !== 'on') {
        ossn_trigger_message(ossn_print('ossn:admin:error'), 'error');
        redirect(REF);
}

function idm_clean_mode($val) {
        $val = trim((string)$val);
        $allowed = array('', 'full_name', 'username', 'at_username'); // '' means inherit
        return in_array($val, $allowed, true) ? $val : '';
}

$save = array(
        'idm_mode_global'   => idm_clean_mode(input('idm_mode_global')),
        'idm_mode_feed'     => idm_clean_mode(input('idm_mode_feed')),
        'idm_mode_comments' => idm_clean_mode(input('idm_mode_comments')),
        'idm_mode_profile'  => idm_clean_mode(input('idm_mode_profile')),
        'idm_mode_userlist' => idm_clean_mode(input('idm_mode_userlist')),
);

// Remove any existing pref annotations (enforce 1 row max)
$existing = ossn_get_annotations(array(
        'type'         => 'identitymanager_pref',
        'owner_guid'   => (int)$user->guid,
        'subject_guid' => (int)$user->guid,
        'limit'        => false,
        'page_limit'   => false,
        'offset'       => false,
));
if (is_array($existing) && !empty($existing)) {
        $ann = new OssnAnnotation();
        foreach ($existing as $row) {
                if (isset($row->id) && $row->id) {
                        $ann->deleteAnnotation((int)$row->id);
                }
        }
}

// If everything is inherit/empty -> nothing to store
$any = false;
foreach ($save as $v) {
        if ($v !== '') { $any = true; break; }
}
if (!$any) {
        ossn_trigger_message(ossn_print('user:updated'), 'success');
        redirect(REF);
}

// Create one preference annotation with entity fields
$a = new OssnAnnotation();
$a->owner_guid   = (int)$user->guid;
$a->subject_guid = (int)$user->guid;
$a->type         = 'identitymanager_pref';
$a->value        = '1';
$a->data         = new stdClass();

foreach ($save as $k => $v) {
        if ($v !== '') {
                $a->data->{$k} = $v;
        }
}

$ok = $a->addAnnotation();
if ($ok) {
        ossn_trigger_message(ossn_print('user:updated'), 'success');
} else {
        ossn_trigger_message(ossn_print('ossn:admin:error'), 'error');
}
redirect(REF);
