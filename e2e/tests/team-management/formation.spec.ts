import { expect, test, type Locator, type Page } from '@playwright/test';
import {
  loginAsUser1,
  expandAccordion,
  addPlayerToPitch,
  benchRows,
} from './_helpers';

/**
 * E2E: the "Formation and Tactics" page – assembling the starting line-up,
 * bench, substitutions, and tactics.
 *
 * Seed data: Team 1 has 24 players. Default setup is 4-1-3-1-1-0 (10 outfield
 * + goalkeeper). One upcoming match: Team 1 - Team 3.
 *
 * This spec is serial: later tests build on state created by earlier ones
 * (saved formation, player on the transfer market from the sell-player spec).
 */

/** Pitch positions that currently have a player assigned. */
const occupiedPositions = (page: Page) =>
  page.locator('#pitch [data-testid="pitch-position"][data-playerid]');

/** Pitch positions that are still free. */
const freePositions = (page: Page) =>
  page.locator('#pitch [data-testid="pitch-position"]:not([data-playerid])');

test.describe.serial('Formation page (logged in as user1)', () => {
  // -------------------------------------------------------------------------
  // Pitch, bench and player accordion
  // -------------------------------------------------------------------------

  test('renders pitch, empty bench and player accordion', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');
    await expect(page.locator('h1')).toHaveText('Formation and Tactics');

    // Default setup 4-1-3-1-1-0 => 10 outfield positions + goalkeeper.
    await expect(page.locator('input[name="setup"]')).toHaveValue('4-1-3-1-1-0');
    await expect(page.locator('#pitch').getByTestId('pitch-position')).toHaveCount(11);
    await expect(freePositions(page)).toHaveCount(11);

    const rows = benchRows(page);
    await expect(rows).toHaveCount(5);
    for (let i = 0; i < 5; i++) {
      await expect(rows.nth(i).getByTestId('bench-placeholder')).toBeVisible();
      await expect(rows.nth(i).getByTestId('bench-remove')).toBeHidden();
      await expect(rows.nth(i).getByTestId('bench-sub-add')).toBeHidden();
      await expect(rows.nth(i).getByTestId('bench-sub-info')).toBeHidden();
    }

    // One accordion section per position, only the last one expanded.
    const sections = page.locator('#playersSelection').getByTestId('position-section-panel');
    await expect(sections).toHaveCount(4);
    await expect(page.locator('#playersSelection .show')).toHaveCount(1);
    await expect(page.locator('#collapsegoaly').getByTestId('player-selectable')).toHaveCount(2);
    await expect(page.locator('#collapsedefense').getByTestId('player-selectable')).toHaveCount(6);
  });

  test('adding a player to the bench shows his name and the bench actions', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');

    const panel = await expandAccordion(page, 'Goalkeeper');
    const player = panel.getByTestId('player-selectable').first();
    const playerId = await player.getAttribute('data-playerid');
    const playerName = await player.getAttribute('data-pname');
    expect(playerName).toBeTruthy();

    await player.getByTestId('player-add-bench').click();

    // Regression guard: addPlayerToBench() used to throw a SyntaxError on the
    // "> .benchPlayerInfo" selector, so the name was never rendered, and the
    // bench buttons were hidden by a stylesheet rule that show() could not
    // override. Both the label and the buttons must now appear.
    const row = benchRows(page).first();
    await expect(row.getByTestId('bench-placeholder')).toBeHidden();
    await expect(row.getByTestId('bench-player-label')).toContainText(playerName!);
    await expect(row.getByTestId('bench-player-label')).toContainText('GK');
    await expect(row.getByTestId('bench-remove')).toBeVisible();
    await expect(row.getByTestId('bench-sub-add')).toBeVisible();

    // Hidden field that gets submitted must carry the player.
    await expect(page.locator('#bench1')).toHaveValue(playerId!);

    // The player himself switches from "add" to "remove" actions.
    await expect(player.getByTestId('player-remove')).toBeVisible();
    await expect(player.getByTestId('player-add-bench')).toBeHidden();
    await expect(player.getByTestId('player-add-pitch')).toBeHidden();
  });

  test('removing a player from the bench restores the placeholder', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');

    const panel = await expandAccordion(page, 'Goalkeeper');
    const player = panel.getByTestId('player-selectable').first();
    await player.getByTestId('player-add-bench').click();

    const row = benchRows(page).first();
    await expect(row.getByTestId('bench-player-label')).toBeVisible();

    await row.getByTestId('bench-remove').click();

    await expect(row.getByTestId('bench-placeholder')).toBeVisible();
    await expect(row.getByTestId('bench-player-label')).toHaveCount(0);
    await expect(row.getByTestId('bench-remove')).toBeHidden();
    await expect(page.locator('#bench1')).toHaveValue('');

    await expect(player.getByTestId('player-add-bench')).toBeVisible();
  });

  test('bench is filled from top to bottom and capped at five players', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');

    const panel = await expandAccordion(page, 'Defense');
    const players = panel.getByTestId('player-selectable');
    await expect(players).toHaveCount(6);

    const rows = benchRows(page);
    for (let i = 0; i < 5; i++) {
      const player = players.nth(i);
      const name = await player.getAttribute('data-pname');
      await player.getByTestId('player-add-bench').click();
      await expect(rows.nth(i).getByTestId('bench-player-label')).toContainText(name!);
    }

    // Bench is full - the sixth player cannot be added anymore.
    const sixth = players.nth(5);
    await sixth.getByTestId('player-add-bench').click();
    await expect(sixth.getByTestId('player-add-bench')).toBeVisible();
    await expect(rows.getByTestId('bench-player-label')).toHaveCount(5);
  });

  test('a player on the pitch cannot be put on the bench', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');

    const panel = await expandAccordion(page, 'Goalkeeper');
    const player = panel.getByTestId('player-selectable').first();
    const playerId = await player.getAttribute('data-playerid');
    const playerName = await player.getAttribute('data-pname');

    await addPlayerToPitch(player, 'T');

    const goalPosition = page.locator('#pitch [data-testid="pitch-position"][data-mainposition="T"]');
    await expect(goalPosition).toHaveAttribute('data-playerid', playerId!);
    await expect(goalPosition.getByTestId('pitch-player-name')).toHaveText(playerName!);
    await expect(page.locator('#player1')).toHaveValue(playerId!);
    await expect(page.locator('#player1_pos')).toHaveValue('T');

    await expect(player.getByTestId('player-add-bench')).toBeHidden();
    await expect(benchRows(page).getByTestId('bench-player-label')).toHaveCount(0);
    for (let i = 0; i < 5; i++) {
      await expect(benchRows(page).nth(i).getByTestId('bench-placeholder')).toBeVisible();
    }

    // Removing him from the pitch re-enables the bench action.
    await goalPosition.getByTestId('pitch-player-remove').click();
    expect(await goalPosition.getAttribute('data-playerid')).toBeNull();
    await expect(page.locator('#player1')).toHaveValue('');
    await expect(player.getByTestId('player-add-bench')).toBeVisible();
  });

  test('clear all empties pitch and bench', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');

    const goalies = await expandAccordion(page, 'Goalkeeper');
    await addPlayerToPitch(goalies.getByTestId('player-selectable').first(), 'T');

    const defenders = await expandAccordion(page, 'Defense');
    await defenders
      .getByTestId('player-selectable')
      .first()
      .getByTestId('player-add-bench')
      .click();
    await expect(benchRows(page).first().getByTestId('bench-player-label')).toBeVisible();

    await page.getByTestId('clear-all').click();

    await expect(freePositions(page)).toHaveCount(11);
    for (let i = 0; i < 5; i++) {
      await expect(benchRows(page).nth(i).getByTestId('bench-placeholder')).toBeVisible();
    }
    await expect(page.locator('#player1')).toHaveValue('');
    await expect(page.locator('#bench1')).toHaveValue('');
  });

  // -------------------------------------------------------------------------
  // Substitutions
  // -------------------------------------------------------------------------

  test('a substitution can be planned and removed again', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');

    const goalies = await expandAccordion(page, 'Goalkeeper');
    const onPitch = goalies.getByTestId('player-selectable').first();
    const onPitchId = await onPitch.getAttribute('data-playerid');
    const onPitchName = await onPitch.getAttribute('data-pname');
    await addPlayerToPitch(onPitch, 'T');

    const defenders = await expandAccordion(page, 'Defense');
    const onBench = defenders.getByTestId('player-selectable').first();
    const onBenchId = await onBench.getAttribute('data-playerid');
    await onBench.getByTestId('player-add-bench').click();

    const row = benchRows(page).first();
    await expect(row.getByTestId('bench-player-label')).toBeVisible();

    await row.getByTestId('bench-sub-add').click();
    const modal = page.locator('#subModal1');
    await expect(modal).toBeVisible();

    // Only players on the pitch can be substituted out.
    await expect(modal.locator('#sub_out1 option')).toHaveCount(2);
    await expect(modal.locator(`#sub_out1 option[value="${onPitchId}"]`)).toHaveCount(1);

    await modal.locator('#sub_minute1').fill('60');
    await modal.locator('#sub_out1').selectOption(onPitchId!);
    await modal.locator('#sub_condition1').selectOption('Leading');
    await modal.locator('#sub_position1').selectOption('IV');
    await modal.getByTestId('save-substitution').click();
    await expect(modal).toBeHidden();

    // Substitution is active: sub info is visible, add button is hidden.
    await expect(row.getByTestId('bench-sub-info')).toBeVisible();
    await expect(row.getByTestId('bench-sub-add')).toBeHidden();

    const subInfo = row.getByTestId('bench-sub-info');
    await expect(subInfo.getByTestId('bench-sub-minute')).toHaveText('60');
    await expect(subInfo.getByTestId('bench-sub-player')).toHaveText(onPitchName!);
    await expect(subInfo.getByTestId('bench-sub-condition-leading')).toBeVisible();
    await expect(subInfo.getByTestId('bench-sub-condition-tie')).toBeHidden();

    await expect(page.locator('#sub1_in')).toHaveValue(onBenchId!);
    await expect(page.locator('#sub1_out')).toHaveValue(onPitchId!);
    await expect(page.locator('#sub1_minute')).toHaveValue('60');
    await expect(page.locator('#sub1_condition')).toHaveValue('Leading');
    await expect(page.locator('#sub1_position')).toHaveValue('IV');

    await subInfo.getByTestId('remove-substitution').click();
    await expect(row.getByTestId('bench-sub-info')).toBeHidden();
    await expect(row.getByTestId('bench-sub-add')).toBeVisible();
    await expect(page.locator('#sub1_minute')).toHaveValue('');
  });

  test('a substitution is rejected when the minute is out of range', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');

    const goalies = await expandAccordion(page, 'Goalkeeper');
    const onPitch = goalies.getByTestId('player-selectable').first();
    const onPitchId = await onPitch.getAttribute('data-playerid');
    await addPlayerToPitch(onPitch, 'T');

    const defenders = await expandAccordion(page, 'Defense');
    await defenders
      .getByTestId('player-selectable')
      .first()
      .getByTestId('player-add-bench')
      .click();

    const row = benchRows(page).first();
    await row.getByTestId('bench-sub-add').click();
    const modal = page.locator('#subModal1');
    await expect(modal).toBeVisible();

    await modal.locator('#sub_minute1').fill('120');
    await modal.locator('#sub_out1').selectOption(onPitchId!);
    await modal.getByTestId('save-substitution').click();
    await expect(modal).toBeHidden();

    // Substitution was rejected: sub info stays hidden.
    await expect(row.getByTestId('bench-sub-info')).toBeHidden();
    await expect(page.locator('#sub1_minute')).toHaveValue('');
  });

  // -------------------------------------------------------------------------
  // Save / reload
  // -------------------------------------------------------------------------

  test('formation with bench, tactics and free kick taker is saved and reloaded', async ({
    page,
  }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');

    // All eleven pitch positions are mandatory for a save, so let the
    // application preselect the strongest eleven first.
    const setupForm = page.locator('form', { has: page.locator('#preselect') });
    await setupForm.getByTestId('preselect-dropdown-toggle').click();
    await setupForm
      .locator('[data-testid="formation-setup-submit"][data-preselect="strongest"]')
      .click();

    await expect(page.locator('h1')).toHaveText('Formation and Tactics');
    await expect(freePositions(page)).toHaveCount(0);
    await expect(occupiedPositions(page)).toHaveCount(11);

    const keeperId = await page
      .locator('#pitch [data-testid="pitch-position"][data-mainposition="T"]')
      .getAttribute('data-playerid');
    expect(keeperId).toBeTruthy();

    // Two defenders are left over after the preselect - put both on the bench.
    // Available players are those whose add-to-bench link is still visible
    // (players already on the pitch have it hidden by the JS via ws-hidden).
    const defenders = await expandAccordion(page, 'Defense');
    const availableIds = await defenders
      .getByTestId('player-selectable')
      .evaluateAll((els) =>
        els
          .filter((el) => {
            const link = el.querySelector('[data-testid="player-add-bench"]');
            return link instanceof HTMLElement &&
              !link.classList.contains('ws-hidden');
          })
          .map((el) => (el as HTMLElement).dataset.playerid),
      );
    expect(availableIds).toHaveLength(2);

    const bench1Name = await defenders
      .locator(`[data-testid="player-selectable"][data-playerid="${availableIds[0]}"]`)
      .getAttribute('data-pname');
    await defenders
      .locator(`[data-testid="player-selectable"][data-playerid="${availableIds[0]}"]`)
      .getByTestId('player-add-bench')
      .click();
    const bench2Name = await defenders
      .locator(`[data-testid="player-selectable"][data-playerid="${availableIds[1]}"]`)
      .getAttribute('data-pname');
    await defenders
      .locator(`[data-testid="player-selectable"][data-playerid="${availableIds[1]}"]`)
      .getByTestId('player-add-bench')
      .click();

    // Only players on the pitch can be chosen as free kick taker.
    await expect(page.locator('#freekickplayer option')).toHaveCount(12);
    await page.locator('#freekickplayer').selectOption(keeperId!);

    await page.check('#longpasses');
    await page.check('#counterattacks');
    // Playwright cannot fill a range input, so the value is set directly.
    await page.locator('#offensive').evaluate((el) => {
      (el as HTMLInputElement).value = '70';
    });

    await page.locator('#formationForm button[type=submit]').click();

    await expect(page.locator('#messages .alert-success')).toContainText('Successfully saved.');

    // Reload from scratch: everything must be restored from the database.
    await page.goto('/?page=formation');
    await expect(page.locator('h1')).toHaveText('Formation and Tactics');

    await expect(freePositions(page)).toHaveCount(0);
    await expect(
      page.locator('#pitch [data-testid="pitch-position"][data-mainposition="T"]'),
    ).toHaveAttribute('data-playerid', keeperId!);

    const rows = benchRows(page);
    await expect(rows.nth(0).getByTestId('bench-player-label')).toContainText(bench1Name!);
    await expect(rows.nth(1).getByTestId('bench-player-label')).toContainText(bench2Name!);
    await expect(rows.nth(2).getByTestId('bench-placeholder')).toBeVisible();

    await expect(page.locator('#longpasses')).toBeChecked();
    await expect(page.locator('#counterattacks')).toBeChecked();
    await expect(page.locator('#offensive')).toHaveValue('70');
    await expect(page.locator('#freekickplayer')).toHaveValue(keeperId!);
  });

  test('an incomplete formation is rejected on save', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');

    await page.getByTestId('clear-all').click();
    await expect(freePositions(page)).toHaveCount(11);

    const goalies = await expandAccordion(page, 'Goalkeeper');
    await addPlayerToPitch(goalies.getByTestId('player-selectable').first(), 'T');

    await page.locator('#formationForm button[type=submit]').click();

    await expect(page.locator('#messages .alert-danger')).toContainText('Invalid Input');
    await expect(page.locator('#messages .alert-success')).toHaveCount(0);
  });

  test('changing the formation setup re-renders the pitch', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');

    await page.selectOption('select[name="formation_defense"]', '3');
    await page.selectOption('select[name="formation_defensemidfield"]', '1');
    await page.selectOption('select[name="formation_midfield"]', '4');
    await page.selectOption('select[name="formation_offensivemidfield"]', '1');
    await page.selectOption('select[name="formation_forward"]', '1');
    await page.selectOption('select[name="formation_outsideforward"]', '0');
    await page.locator('button[name="buttonSetupChange"]').click();

    await expect(page.locator('h1')).toHaveText('Formation and Tactics');
    await expect(page.locator('input[name="setup"]')).toHaveValue('3-1-4-1-1-0');
    await expect(page.locator('select[name="formation_defense"]')).toHaveValue('3');
    await expect(page.locator('select[name="formation_midfield"]')).toHaveValue('4');
    await expect(page.locator('#pitch').getByTestId('pitch-position')).toHaveCount(11);
    await expect(page.locator('#pitch .IV')).toHaveCount(3);
  });

  test('runs without JavaScript errors', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', (error) => errors.push(`pageerror: ${error.message}`));
    page.on('console', (message) => {
      if (message.type() === 'error') errors.push(`console: ${message.text()}`);
    });

    await loginAsUser1(page);
    await page.goto('/?page=formation');

    // Exercise the code paths that were broken by the jQuery migration.
    // Start from an empty pitch so the goalkeeper is definitely available.
    await page.getByTestId('clear-all').click();
    const panel = await expandAccordion(page, 'Goalkeeper');
    const player = panel.getByTestId('player-selectable').first();
    await player.getByTestId('player-add-bench').click();
    await expect(benchRows(page).first().getByTestId('bench-player-label')).toBeVisible();
    await benchRows(page).first().getByTestId('bench-remove').click();
    await expect(benchRows(page).first().getByTestId('bench-placeholder')).toBeVisible();

    expect(errors).toEqual([]);
  });
});
