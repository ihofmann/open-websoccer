import { expect, type Locator, type Page } from '@playwright/test';

/**
 * Shared helpers for the national-team-management E2E specs.
 *
 * Seed data this suite relies on (e2e/seed/seed_data.sql):
 *   * National teams (IDs 41-43, beyond the 40 club teams):
 *       41 "England"      managed by user1  – 5 pre-nominated English players.
 *       42 "Deutschland"  managed by user2  – 12 German players available.
 *       43 "Italy"        managed by user3  – no players (empty-team test).
 *   * user4 / user5 do NOT manage a national team (requires-team error test).
 *
 *   * Pre-nominated England players (ws3_nationalplayer):
 *       ID  1  Player1_T1   Torwart     (Goalkeeper)
 *       ID  3  Player1_LV1  Abwehr/LV   (Defense)
 *       ID  5  Player1_IV1  Abwehr/IV   (Defense)
 *       ID  9  Player1_LM1  Mittelfeld  (Midfield)
 *       ID 19  Player1_LS1  Sturm/LS    (Forward)
 *
 *   * National team matches (ws3_spiel):
 *       #25  England vs Deutschland  completed 3-1  (yesterday)
 *       #26  England vs Deutschland  scheduled      (~10 years ahead)
 *
 * Spec files run in alphabetical order (workers = 1, fullyParallel = false):
 *   1. auth.spec.ts                     (read-only)
 *   2. national-matches.spec.ts         (read-only)
 *   3. national-team.spec.ts            (removes player 19 – mutation)
 *   4. nominate-national-players.spec.ts (nominates player 2 – mutation)
 */

/** Player IDs referenced by the specs. */
export const PLAYER_IDS = {
  /** Pre-nominated England players. */
  goalkeeper: 1, // Player1_T1
  defenseLV: 3, // Player1_LV1
  defenseIV: 5, // Player1_IV1
  midfield: 9, // Player1_LM1
  forward: 19, // Player1_LS1  (removed by national-team.spec.ts)
  /** Eligible English players (not pre-nominated). */
  nominateGoalkeeper: 2, // Player1_T2  (nominated by nominate-national-players.spec.ts)
} as const;

/** National team match IDs from seed data. */
export const MATCH_IDS = {
  completed: 25,
  scheduled: 26,
} as const;

/** National team IDs from seed data. */
export const TEAM_IDS = {
  england: 41,
  deutschland: 42,
  italy: 43,
} as const;

export async function loginAsUser1(page: Page): Promise<void> {
  await page.goto('/?page=login');
  await page.fill('#loginstr', 'user1');
  await page.fill('#loginpassword', 'user1');
  await page.click('button[type=submit]');
  await expect(page.locator('h1')).toHaveText('My Office');
}

export async function loginAs(page: Page, nick: string, password: string): Promise<void> {
  await page.goto('/?page=login');
  await page.fill('#loginstr', nick);
  await page.fill('#loginpassword', password);
  await page.click('button[type=submit]');
  await expect(page.locator('h1')).toHaveText('My Office');
}

/**
 * Finds a national-team player row on the "National Team" page by the player's
 * data-player-id attribute and returns the row locator.
 */
export function nationalPlayerRow(page: Page, playerId: number): Locator {
  return page
    .getByTestId('nt-players-table')
    .locator('tbody tr')
    .filter({ has: page.locator(`[data-player-id="${playerId}"]`) });
}

/**
 * Finds a search-result row on the "Nominate Players" page by the player's
 * data-player-id attribute and returns the row locator.
 */
export function searchResultRow(page: Page, playerId: number): Locator {
  return page
    .getByTestId('nt-search-results-table')
    .locator('tbody tr')
    .filter({ has: page.locator(`[data-player-id="${playerId}"]`) });
}
