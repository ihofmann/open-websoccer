import { expect, test } from '@playwright/test';
import {
  loginAsUser1,
  MATCH_IDS,
  expandAccordion,
  addPlayerToPitch,
  benchRows,
  fillAllPitchPositions,
  freePositions,
} from './_helpers';

/**
 * E2E: the youth "Formation and Tactics" page – setting up the line-up for a
 * scheduled youth match.
 *
 * Seed data: Match #2 (Team 1 vs Team 2, ~8 days ahead, not simulated).
 * Team 1 has 12 youth players at the time this spec runs (after youth-buy
 * added 1 and youth-fire removed 1).
 *
 * This spec is serial: the save test persists a formation.
 */

test.describe.serial('Youth formation page (logged in as user1)', () => {
  test('renders the pitch, bench and player accordion', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youth-formation&matchid=${MATCH_IDS.scheduled}`);
    await expect(page.locator('h1')).toHaveText('Formation and Tactics');

    // Match info is shown.
    await expect(page.locator('#pagecontent')).toContainText('Team 1');
    await expect(page.locator('#pagecontent')).toContainText('Team 2');

    // Default setup 4-1-3-1-1 → 11 pitch positions.
    await expect(page.locator('input[name="setup"]')).toHaveValue('4-1-3-1-1');
    await expect(page.locator('#pitch').getByTestId('pitch-position')).toHaveCount(11);
    await expect(freePositions(page)).toHaveCount(11);

    // 5 bench rows, all empty.
    const rows = benchRows(page);
    await expect(rows).toHaveCount(5);
    await expect(rows.first().getByTestId('bench-placeholder')).toBeVisible();

    // Player accordion has sections.
    const sections = page.locator('#playersSelection').getByTestId('position-section-panel');
    await expect(sections).toHaveCount(4);

    // Save and cancel buttons.
    await expect(page.getByTestId('youth-formation-save')).toBeVisible();
    await expect(page.getByTestId('youth-formation-cancel')).toBeVisible();
  });

  test('changing the formation setup updates pitch positions', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youth-formation&matchid=${MATCH_IDS.scheduled}`);

    // Default: 4-1-3-1-1 = 11 positions.
    await expect(page.locator('#pitch').getByTestId('pitch-position')).toHaveCount(11);

    // Change to 3-1-3-1-2 (defense 3, striker 2).
    await page.getByTestId('formation-defense').selectOption('3');
    await page.getByTestId('formation-forward').selectOption('2');
    await page.getByTestId('formation-setup-change').click();

    // Still 11 positions (3+1+3+1+2 = 10 + goalkeeper).
    await expect(page.locator('#pitch').getByTestId('pitch-position')).toHaveCount(11);
    await expect(page.locator('input[name="setup"]')).toHaveValue('3-1-3-1-2');
  });

  test('adding a player to the pitch marks them as placed', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youth-formation&matchid=${MATCH_IDS.scheduled}`);

    const panel = await expandAccordion(page, 'Goalkeeper');
    const player = panel.getByTestId('player-selectable').first();
    await addPlayerToPitch(player, 'T');

    // One position is now occupied.
    await expect(freePositions(page)).toHaveCount(10);
  });

  test('adding a player to the bench shows their name', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youth-formation&matchid=${MATCH_IDS.scheduled}`);

    const panel = await expandAccordion(page, 'Defense');
    const player = panel.getByTestId('player-selectable').first();
    const playerName = await player.getAttribute('data-pname');
    expect(playerName).toBeTruthy();

    await player.getByTestId('player-add-bench').click();

    const row = benchRows(page).first();
    await expect(row.getByTestId('bench-placeholder')).toBeHidden();
    await expect(row.getByTestId('bench-player-label')).toContainText(playerName!);
    await expect(row.getByTestId('bench-remove')).toBeVisible();
  });

  test('clear all empties all pitch and bench positions', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youth-formation&matchid=${MATCH_IDS.scheduled}`);

    // Add a player to the pitch first.
    const panel = await expandAccordion(page, 'Goalkeeper');
    await addPlayerToPitch(panel.getByTestId('player-selectable').first(), 'T');
    await expect(freePositions(page)).toHaveCount(10);

    // Clear all.
    await page.getByTestId('formation-clear-all').click();
    await expect(freePositions(page)).toHaveCount(11);

    // Bench is also cleared.
    await expect(benchRows(page).first().getByTestId('bench-placeholder')).toBeVisible();
  });

  test('a complete formation is saved successfully', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youth-formation&matchid=${MATCH_IDS.scheduled}`);

    // Fill all 11 pitch positions + 1 bench.
    await fillAllPitchPositions(page);
    await expect(freePositions(page)).toHaveCount(0);

    // Save.
    await page.getByTestId('youth-formation-save').click();

    // The controller returns null → stays on the same page with a success msg.
    await expect(page.locator('h1')).toHaveText('Formation and Tactics');
    await expect(page.locator('#messages .alert-success')).toBeVisible();
  });

  test('cancel link returns to the youth matches page', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youth-formation&matchid=${MATCH_IDS.scheduled}`);

    await page.getByTestId('youth-formation-cancel').click();
    await expect(page.locator('h1')).toHaveText('Matches of my team');
  });
});
