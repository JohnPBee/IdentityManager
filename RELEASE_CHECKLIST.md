# IdentityManager Release Checklist

Use this checklist for every public release. The safe pattern is:

1. Change code on a branch.
2. Test in the OSSN sandbox.
3. Build a release ZIP.
4. Review the result.
5. Publish only after explicit approval.

## Current Release Target

- Component: `IdentityManager`
- Branch: `ossn-9.9-compat`
- Release version: read from `ossn_com.xml`
- Sandbox site: `https://daily.postbits.ca/`
- Sandbox component path: `/var/www/ossn/components/IdentityManager/`

## Local Checks

- Review changed files:

```powershell
git status --short
git diff --stat
```

- Build the OSSN-ready ZIP:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\build-release.ps1
```

- Confirm the generated ZIP is under `dist/` and opens with this root:

```text
IdentityManager/ossn_com.php
IdentityManager/ossn_com.xml
```

Do not upload GitHub's automatic source-code ZIP to OSSN. Use the custom ZIP from `dist/`.

## Sandbox Checks

- Deploy the built component folder or ZIP to the sandbox.
- Enable the component in OSSN admin.
- Save admin settings.
- Confirm the user Identity Manager tab appears when user overrides are enabled.
- Test display modes:
  - `full_name`
  - `username`
  - `at_username`
- Test contexts:
  - feed
  - comments
  - profile
  - user list
- Check Apache/PHP logs for new IdentityManager fatal or parse errors.

## Publish Gates

These actions require explicit approval:

- Commit local changes.
- Push a branch to GitHub.
- Merge to `main`.
- Create a tag.
- Create a GitHub release.
- Upload the ZIP to the OSSN website.

Suggested commit message:

```text
Adapt IdentityManager for OSSN 9.9
```

Suggested tag:

```text
v1.0.3
```

Suggested release title:

```text
IdentityManager 1.0.3 - OSSN 9.9 compatibility
```

## Release Notes Template

```text
IdentityManager 1.0.3

Changes:
- Adds OSSN 9.9 compatibility metadata.
- Validates identity display modes before saving/applying settings.
- Honors admin context settings and exclusions.
- Improves runtime display handling for OSSN white theme name fields.
- Keeps user overrides stored through OSSN annotations.

Tested:
- OSSN Premium 9.9 sandbox
- PHP 8.2 syntax checks
- Admin settings page
- User profile Identity Manager tab
- full_name, username, and at_username display modes
- feed, comments, profile, and user-list display contexts

Known limitation:
- Comment-specific context detection depends on the current OSSN page context. Embedded comments may share feed/profile context.
```
