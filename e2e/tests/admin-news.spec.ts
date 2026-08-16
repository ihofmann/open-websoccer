import { test, expect } from '@playwright/test';

/**
 * E2E: Admin user signs in at the AdminCenter, publishes a news article,
 * edits it and finally deletes it.
 *
 * Seed data provides the admin account `admin` / `admin` (id = 1).
 */
test('admin publishes, edits and deletes a news article', async ({ page }) => {
  // --- Sign in at the AdminCenter -------------------------------------------
  await page.goto('/admin/');
  // Not yet logged in -> forwarded to the login page.
  await expect(page).toHaveURL(/login\.php/);

  await page.fill('#inputUser', 'admin');
  await page.fill('#inputPassword', 'admin');
  await page.click('button[type=submit]');

  // Successful login redirects to the admin home.
  await expect(page).toHaveURL(/\/admin\/index\.php$/);
  await expect(page.locator('.navbar-text')).toContainText('admin');

  // --- Navigate to the News management --------------------------------------
  await page.goto('/admin/index.php?site=manage&entity=news');
  await expect(page.locator('h1')).toHaveText('News');

  // The two titles must not be substrings of each other, so that the
  // assertions below can tell them apart reliably.
  const stamp = Date.now();
  const title = `E2E Created ${stamp}`;
  const editedTitle = `E2E Edited ${stamp}`;
  const body = `Created by the Playwright E2E suite at ${new Date().toISOString()}`;

  // --- Publish (create) a news article --------------------------------------
  await page.click('a[href*="show=add"]');
  // The entity heading stays "News"; the form itself is titled "Add New".
  await expect(page.locator('legend')).toHaveText('Add New');

  // Author is a foreign-key <select> with the single seeded admin (id = 1).
  await page.selectOption('#autor_id', '1');
  await page.fill('#titel', title);
  // The date/time pair of a "timestamp" field is rendered with name only.
  await page.fill('input[name="datum_date"]', '2026-08-16');
  await page.fill('input[name="datum_time"]', '12:00');
  await page.fill('#nachricht', body);
  // "Active" checkbox -> makes the article visible (published) on the site.
  await page.check('#status');
  await page.click('input[type=submit]');

  // Back at the overview; the new article must be listed.
  await expect(page.locator('table')).toContainText(title);

  // --- Edit the news article ------------------------------------------------
  const row = page.locator('tr', { hasText: title });
  await row.locator('a[title="Edit"]').click();

  // The edit form is pre-filled with the title just created.
  await expect(page.locator('legend')).toHaveText('Edit');
  await expect(page.locator('#titel')).toHaveValue(title);
  await page.fill('#titel', editedTitle);
  await page.click('input[type=submit]');

  // The updated title is now shown in the overview.
  await expect(page.locator('table')).toContainText(editedTitle);
  await expect(page.locator('table')).not.toContainText(title);

  // --- Delete the news article ----------------------------------------------
  const editedRow = page.locator('tr', { hasText: editedTitle });
  await editedRow.locator('a.deleteLink').click();

  // Deleting asks for confirmation in a Bootstrap modal.
  const confirmDialog = page.locator('#wsConfirmModal');
  await expect(confirmDialog).toBeVisible();
  await confirmDialog.locator('#wsConfirmYes').click();

  await expect(page.locator('.alert-success')).toBeVisible();
  await expect(page.locator('table')).not.toContainText(editedTitle);
});
