import { expect, type Locator, type Page } from '@playwright/test';

/**
 * Shared helpers for the team-management E2E specs.
 *
 * Seed data this suite relies on (e2e/seed/seed_data.sql):
 *   * user1 / user1 manages "Team 1"
 *   * Team 1 has 24 players, all with salary 50 000, contract 30 matches,
 *     strength 75, satisfaction 60
 *   * budget 5 000 000, stadium "Sample Arena" (capacity 18 200),
 *     sponsor "Sponsor Alpha", building "Youth Center", trainer "Coach Carl"
 *   * exactly one upcoming match: Team 1 - Team 3
 *   * Team 2 has a pending transfer offer for Player1_T1
 */

export const CURRENCY = 'EUR';

/**
 * Whole-label lookup for menu entries. Role based name matching is unusable
 * here because the Bootstrap icon pseudo element contributes a glyph to the
 * accessible name, and a substring match would let "Sell" also hit
 * "Mark as unsellable".
 */
export function menuItem(menu: Locator, label: string): Locator {
  return menu.locator('a').filter({ hasText: new RegExp(`^\\s*${label}\\s*$`) });
}

export async function loginAsUser1(page: Page): Promise<void> {
  await page.goto('/?page=login');
  await page.fill('#loginstr', 'user1');
  await page.fill('#loginpassword', 'user1');
  await page.click('button[type=submit]');
  await expect(page.locator('h1')).toHaveText('My Office');
}

/**
 * Expands one section of the formation player accordion and returns its panel.
 *
 * Only the last section is expanded on page load and `data-bs-parent` allows a
 * single open panel, so players must always be looked up inside the panel that
 * is actually open - otherwise they have a zero-size box and cannot be clicked.
 */
export async function expandAccordion(page: Page, positionLabel: string): Promise<Locator> {
  const button = page
    .locator('#playersSelection')
    .getByRole('button', { name: new RegExp(positionLabel) });
  const target = await button.getAttribute('data-bs-target');
  expect(target).toBeTruthy();

  if (await button.evaluate((el) => el.classList.contains('collapsed'))) {
    await button.click();
  }

  const panel = page.locator(target!);
  await expect(panel).toHaveClass(/show/);
  await expect(panel).not.toHaveClass(/collapsing/);
  await expect(panel.getByTestId('player-selectable').first()).toBeVisible();
  return panel;
}

/** Adds a player of the given accordion section to the pitch position. */
export async function addPlayerToPitch(player: Locator, position: string): Promise<void> {
  await player.getByTestId('player-add-pitch').click();
  await player
    .locator(`[data-testid="player-add-pitch-item"][data-target="${position}"]`)
    .click();
  // Instead of checking a CSS state class, verify the observable behavior:
  // the remove link is now visible and the add links are hidden.
  await expect(player.getByTestId('player-remove')).toBeVisible();
  await expect(player.getByTestId('player-add-pitch')).toBeHidden();
}

export const benchRows = (page: Page): Locator => page.getByTestId('bench-row');

/**
 * Submits the contract extension form.
 *
 * The form contains `<input name="matches">`, and HTMLFormElement exposes its
 * named controls with priority over its own members, so `form.matches()` is no
 * longer a function on this form. Playwright resolves a descendant selector by
 * matching the ancestors of the candidates, which therefore throws for every
 * element below this form. Looking the button up with a plain
 * `document.querySelector` avoids that; the click is processed by a delegated
 * listener on `document`, so the AJAX submit runs exactly as for a real click.
 */
export async function submitContractOffer(page: Page): Promise<void> {
  await page.evaluate(() =>
    (document.querySelector('[data-testid="submit-contract-offer"]') as HTMLElement).click(),
  );
}
