import { expect, test, type Locator, type Page } from '@playwright/test';

/**
 * E2E: frontend Free Clubs page (teams without a manager).
 *
 * Seed data:
 *   * League "Demo Bundesliga" (Deutschland) → Teams 21–40, all free → Germany (20)
 *   * League "Premier Sample League" (England) → Teams 1–20; Teams 1–5 managed
 *     by user1..user5 → England (15 free: Teams 6–20)
 *
 * Countries are ordered alphabetically (Deutschland, England). Accordion
 * panels start collapsed; Bootstrap keeps only one panel open at a time
 * (`data-bs-parent="#countries"`).
 *
 * Guests can browse the page (role guest/user), so no login is required.
 */

async function openFreeClubs(page: Page): Promise<Locator> {
  await page.goto('/?page=freeclubs');
  await expect(page.locator('h1')).toHaveText('Teams without Manager');
  return page.locator('#pagecontent');
}

function countryButton(content: Locator, label: string): Locator {
  return content.locator('#countries .accordion-button', { hasText: label });
}

function countryPanel(content: Locator, index: number): Locator {
  return content.locator(`#collapse${index}`);
}

/** Wait until Bootstrap finishes the expand/collapse CSS transition. */
async function waitForAccordionIdle(panel: Locator): Promise<void> {
  await expect(panel).not.toHaveClass(/collapsing/);
}

async function expandCountry(button: Locator, panel: Locator): Promise<void> {
  await button.click();
  await expect(panel).toBeVisible();
  await expect(panel).toHaveClass(/show/);
  await waitForAccordionIdle(panel);
  await expect(button).toHaveAttribute('aria-expanded', 'true');
  await expect(button).not.toHaveClass(/collapsed/);
}

async function collapseCountry(button: Locator, panel: Locator): Promise<void> {
  await button.click();
  await expect(panel).not.toBeVisible();
  await waitForAccordionIdle(panel);
  await expect(button).toHaveAttribute('aria-expanded', 'false');
  await expect(button).toHaveClass(/collapsed/);
}

test('lists free clubs grouped by country in collapsed accordions', async ({ page }) => {
  const content = await openFreeClubs(page);
  const accordion = content.locator('#countries');

  await expect(accordion.locator('.accordion-item')).toHaveCount(2);

  const germany = countryButton(content, 'Germany (20)');
  const england = countryButton(content, 'England (15)');

  await expect(germany).toBeVisible();
  await expect(england).toBeVisible();

  await expect(germany).toHaveClass(/collapsed/);
  await expect(england).toHaveClass(/collapsed/);
  await expect(germany).toHaveAttribute('aria-expanded', 'false');
  await expect(england).toHaveAttribute('aria-expanded', 'false');

  await expect(countryPanel(content, 1)).not.toBeVisible();
  await expect(countryPanel(content, 2)).not.toBeVisible();
});

test('expands and collapses a country accordion', async ({ page }) => {
  const content = await openFreeClubs(page);
  const germanyButton = countryButton(content, 'Germany (20)');
  const germanyPanel = countryPanel(content, 1);

  await expandCountry(germanyButton, germanyPanel);

  const germanyTable = germanyPanel.locator('table');
  await expect(germanyTable.locator('tbody tr')).toHaveCount(20);
  await expect(germanyTable.getByRole('link', { name: 'Team 21', exact: true })).toBeVisible();
  await expect(germanyTable.getByRole('link', { name: 'Team 40', exact: true })).toBeVisible();
  await expect(germanyTable).toContainText('Demo Bundesliga');
  await expect(germanyTable.getByRole('link', { name: 'Choose Team' }).first()).toBeVisible();

  await collapseCountry(germanyButton, germanyPanel);
});

test('opening one country collapses the other', async ({ page }) => {
  const content = await openFreeClubs(page);
  const germanyButton = countryButton(content, 'Germany (20)');
  const englandButton = countryButton(content, 'England (15)');
  const germanyPanel = countryPanel(content, 1);
  const englandPanel = countryPanel(content, 2);

  await expandCountry(germanyButton, germanyPanel);
  await expect(englandPanel).not.toBeVisible();

  await expandCountry(englandButton, englandPanel);

  await expect(germanyButton).toHaveAttribute('aria-expanded', 'false');
  await expect(germanyButton).toHaveClass(/collapsed/);
  await expect(germanyPanel).not.toBeVisible();

  const englandTable = englandPanel.locator('table');
  await expect(englandTable.locator('tbody tr')).toHaveCount(15);
  await expect(englandTable.getByRole('link', { name: 'Team 6', exact: true })).toBeVisible();
  await expect(englandTable.getByRole('link', { name: 'Team 20', exact: true })).toBeVisible();
  await expect(englandTable).toContainText('Premier Sample League');
  await expect(englandTable.getByRole('link', { name: 'Team 1', exact: true })).toHaveCount(0);
  await expect(englandTable.getByRole('link', { name: 'Team 5', exact: true })).toHaveCount(0);
});

test('team name links to the team page', async ({ page }) => {
  const content = await openFreeClubs(page);
  const englandButton = countryButton(content, 'England (15)');
  const englandPanel = countryPanel(content, 2);

  await expandCountry(englandButton, englandPanel);
  await englandPanel.getByRole('link', { name: 'Team 6', exact: true }).click();

  await expect(page.locator('h1')).toContainText('Team 6');
});
