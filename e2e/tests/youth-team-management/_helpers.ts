import { expect, type Locator, type Page } from '@playwright/test';

/**
 * Shared helpers for the youth-team-management E2E specs.
 *
 * Seed data this suite relies on (e2e/seed/seed_data.sql):
 *   * user1 / user1 manages "Team 1" with 12 youth players (IDs 1-12).
 *     - Player 1  "Young Talent"       age 16  Mittelfeld  (sell test)
 *     - Player 2  "Young Goalie"       age 17  Torwart     (make-pro test)
 *     - Player 3  "Teen Defender"      age 15  Abwehr      (too young)
 *     - Player 4  "Market Midfielder"  age 16  Mittelfeld  (on market, 200 000)
 *     - Player 5  "Youth Striker"      age 17  Sturm       (fire test)
 *   * user2 / user2 manages "Team 2" with 12 youth players (IDs 13-24).
 *   * user3 / user3 manages "Team 3" with 1 youth player on market (ID 25).
 *   * user4 / user4 manages "Team 4" – no youth players.
 *   * Scouts: ID 1 "Scout Sam" (fee 5 000), ID 2 "Scout Alex" (fee 3 000).
 *   * Match requests: #1 Team 1 (reward 5 000), #2 Team 2 (no reward).
 *   * Youth matches: #1 completed (2-1), #2 scheduled (~8 days ahead).
 *   * Scouting: Team 1 never scouted (possible), Team 2 just scouted (blocked).
 */

export const CURRENCY = 'EUR';

/** Team 1 youth-player IDs that action specs mutate. */
export const PLAYER_IDS = {
  sell: 1, // Young Talent
  makeProfessional: 2, // Young Goalie
  tooYoung: 3, // Teen Defender
  onMarket: 4, // Market Midfielder
  fire: 5, // Youth Striker
  buyable: 25, // Buyable Youth (Team 3)
} as const;

/** Youth match IDs from seed data. */
export const MATCH_IDS = {
  completed: 1,
  scheduled: 2,
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
 * Finds a youth-player row on the "My Youth Team" page by the player's full
 * name and returns the row locator.
 */
export function youthPlayerRow(page: Page, fullName: string): Locator {
  return page
    .getByTestId('youth-players-table')
    .locator('tbody tr')
    .filter({ hasText: fullName });
}

// ---------------------------------------------------------------------------
// Formation helpers (shared with the professional formation page via
// formation_base.twig, which already defines data-testid attributes).
// ---------------------------------------------------------------------------

/**
 * Expands one section of the formation player accordion and returns its panel.
 *
 * Only the last section is expanded on page load and `data-bs-parent` allows a
 * single open panel, so players must always be looked up inside the panel that
 * is actually open.
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

/** Adds a player to a pitch position via the add-to-pitch dropdown. */
export async function addPlayerToPitch(player: Locator, position: string): Promise<void> {
  await player.getByTestId('player-add-pitch').click();
  await player
    .locator(`[data-testid="player-add-pitch-item"][data-target="${position}"]`)
    .click();
  await expect(player.getByTestId('player-remove')).toBeVisible();
  await expect(player.getByTestId('player-add-pitch')).toBeHidden();
}

export const benchRows = (page: Page): Locator => page.getByTestId('bench-row');

/** Free pitch positions (no player assigned yet). */
export const freePositions = (page: Page): Locator =>
  page.locator('#pitch [data-testid="pitch-position"]:not([data-playerid])');

/**
 * Fills all 11 pitch positions for the default 4-1-3-1-1 setup by iterating
 * through the accordion sections and adding the first available player to each
 * target position. Also puts one remaining player on the bench.
 *
 * Requires 12 youth players in the squad (2 goaly, 3 defense, 4 midfield,
 * 3 striker).
 */
export async function fillAllPitchPositions(page: Page): Promise<void> {
  // Position targets for 4-1-3-1-1: T, LV, IV, IV, RV, DM, LM, ZM, RM, OM, MS.
  const assignments: Array<{ section: string; positions: string[] }> = [
    { section: 'Goalkeeper', positions: ['T'] },
    { section: 'Defense', positions: ['LV', 'IV', 'RV'] },
    { section: 'Midfield', positions: ['IV', 'DM', 'LM', 'ZM'] },
    { section: 'Forward', positions: ['RM', 'OM', 'MS'] },
  ];

  let benchFilled = false;
  for (const { section, positions } of assignments) {
    const panel = await expandAccordion(page, section);
    const selectable = panel.getByTestId('player-selectable');

    for (const pos of positions) {
      // Find the first player whose add-to-pitch link is still visible.
      const count = await selectable.count();
      let added = false;
      for (let i = 0; i < count && !added; i++) {
        const player = selectable.nth(i);
        const addLink = player.getByTestId('player-add-pitch');
        if (await addLink.isVisible()) {
          await addPlayerToPitch(player, pos);
          added = true;
        }
      }
      expect(added).toBe(true);
    }

    // Put the second goalkeeper on the bench.
    if (!benchFilled && section === 'Goalkeeper') {
      const count = await selectable.count();
      for (let i = 0; i < count && !benchFilled; i++) {
        const player = selectable.nth(i);
        const benchLink = player.getByTestId('player-add-bench');
        if (await benchLink.isVisible()) {
          await benchLink.click();
          benchFilled = true;
        }
      }
    }
  }

  await expect(freePositions(page)).toHaveCount(0);
}
