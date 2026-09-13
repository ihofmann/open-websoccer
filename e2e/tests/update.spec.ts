import { expect, test } from '@playwright/test';

/**
 * E2E: the update wizard must not run again once the update has been
 * performed.
 *
 * The E2E application config (e2e/docker/config.template.inc.php, synced by
 * the run scripts via e2e/scripts/set-installed-version.js) marks the
 * version shipped in websoccer/admin/config/version.txt as installed, so
 * opening /update only shows the "Update already performed" notice instead
 * of the wizard steps.
 */
test('update page shows "Update already performed"', async ({ page }) => {
  await page.goto('/update');

  await expect(page.locator('.alert-info')).toContainText('Update already performed');
});
