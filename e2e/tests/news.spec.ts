import { expect, test } from '@playwright/test';

/**
 * E2E: frontend news overview and article details.
 *
 * Seed data includes one published article:
 *   title: "Welcome to OpenWebSoccer"
 *   body:  "This is a sample news entry."
 *   author: admin (name = "admin")
 *   related links: project, documentation, and license
 *
 * Guests can browse news, so no login is required.
 */
test('lists latest news and shows article details', async ({ page }) => {
  const articleTitle = 'Welcome to OpenWebSoccer';
  const articleBody = 'This is a sample news entry.';
  const relatedLinks = [
    { label: 'OpenWebSoccer project', url: 'https://github.com/OpenWebSoccer' },
    { label: 'OpenWebSoccer documentation', url: 'https://docs.openwebsoccer.org' },
    {
      label: 'OpenWebSoccer license',
      url: 'https://github.com/OpenWebSoccer/OpenWebSoccer-Sim/blob/master/LICENSE',
    },
  ];

  await page.goto('/?page=news');
  await expect(page.locator('h1')).toHaveText('News');

  const content = page.locator('#pagecontent');
  await expect(content.getByRole('link', { name: articleTitle })).toBeVisible();
  await expect(content).toContainText(articleBody);

  await content.getByRole('link', { name: articleTitle }).click();

  await expect(page.locator('h1')).toHaveText(articleTitle);
  const articleContent = page.locator('#pagecontent');
  await expect(articleContent).toContainText(articleBody);
  await expect(articleContent).toContainText(/written by admin/i);
  await expect(articleContent.locator('ul li')).toHaveCount(relatedLinks.length);

  for (const relatedLink of relatedLinks) {
    const link = articleContent.getByRole('link', { name: relatedLink.label });
    await expect(link).toBeVisible();
    await expect(link).toHaveAttribute('href', relatedLink.url);
  }
});
