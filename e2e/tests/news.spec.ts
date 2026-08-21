import { expect, test } from '@playwright/test';

/**
 * E2E: frontend news overview and article details.
 *
 * Seed data includes one published article:
 *   title: "Welcome to OpenWebSoccer"
 *   body:  "This is a sample news entry."
 *   author: admin (name = "admin")
 *
 * Guests can browse news, so no login is required.
 */
test('lists latest news and shows article details', async ({ page }) => {
  const articleTitle = 'Welcome to OpenWebSoccer';
  const articleBody = 'This is a sample news entry.';

  await page.goto('/?page=news');
  await expect(page.locator('h1')).toHaveText('News');

  const content = page.locator('#pagecontent');
  await expect(content.getByRole('link', { name: articleTitle })).toBeVisible();
  await expect(content).toContainText(articleBody);

  await content.getByRole('link', { name: articleTitle }).click();

  await expect(page.locator('h1')).toHaveText(articleTitle);
  await expect(page.locator('#pagecontent')).toContainText(articleBody);
  await expect(page.locator('#pagecontent')).toContainText(/written by admin/i);
});
