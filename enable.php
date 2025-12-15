<?php
/**
 * IdentityManager enable script (clean)
 *
 * This component is runtime-only identity display override.
 * For safety and minimal intrusion, we do NOT create custom tables
 * or seed ossn_site_settings from here.
 *
 * If you later add optional features (e.g., per-user titles),
 * prefer OSSN-native storage (entities/metadata) or implement an explicit
 * admin migration action with clear rollback.
 */
