import { expect, test, type Locator, type Page } from '@playwright/test';

/**
 * E2E: every frontend page a logged-in user needs to manage their own team.
 *
 * Covered pages: office, myteam, formation, training, trainer-details, sponsor,
 * stadium, stadiumenvironment, finances, tickets, sell-player, extend-contract,
 * transfermarket, transferoffers.
 *
 * Seed data this suite relies on (e2e/seed/seed_data.sql):
 *   * user1 / user1 manages "Team 1"
 *   * Team 1 has 24 players, all with salary 50 000, contract 30 matches,
 *     strength 75, satisfaction 60
 *   * budget 5 000 000, stadium "Sample Arena" (capacity 18 200),
 *     sponsor "Sponsor Alpha", building "Youth Center", trainer "Coach Carl"
 *   * exactly one upcoming match: Team 1 - Team 3
 *   * Team 2 has a pending transfer offer for Player1_T1
 *
 * The suite is serial: later tests build on state created by earlier ones
 * (captain, unsellable flag, saved formation, player on the transfer market).
 */

const CURRENCY = 'EUR';

/**
 * Whole-label lookup for menu entries. Role based name matching is unusable
 * here because the Bootstrap icon pseudo element contributes a glyph to the
 * accessible name, and a substring match would let "Sell" also hit
 * "Mark as unsellable".
 */
function menuItem(menu: Locator, label: string): Locator {
  return menu.locator('a').filter({ hasText: new RegExp(`^\\s*${label}\\s*$`) });
}

async function loginAsUser1(page: Page): Promise<void> {
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
async function expandAccordion(page: Page, positionLabel: string): Promise<Locator> {
  const button = page.locator('#playersSelection .accordion-button', { hasText: positionLabel });
  const target = await button.getAttribute('data-bs-target');
  expect(target).toBeTruthy();

  if (await button.evaluate((el) => el.classList.contains('collapsed'))) {
    await button.click();
  }

  const panel = page.locator(target!);
  await expect(panel).toHaveClass(/show/);
  await expect(panel).not.toHaveClass(/collapsing/);
  await expect(panel.locator('.playerDraggable').first()).toBeVisible();
  return panel;
}

/** Adds a player of the given accordion section to the pitch position. */
async function addPlayerToPitch(player: Locator, position: string): Promise<void> {
  await player.locator('.playerAddToPitchLink').click();
  await player.locator(`.playerAddToPitchLinkItem[data-target="${position}"]`).click();
  await expect(player).toHaveClass(/playerIsOnPitch/);
}

const benchRows = (page: Page) => page.locator('tr.benchposition');

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
async function submitContractOffer(page: Page): Promise<void> {
  await page.evaluate(() =>
    (document.querySelector('#pagecontent button.ajaxSubmit') as HTMLElement).click(),
  );
}

test.describe.serial('Team management (logged in as user1)', () => {
  // -------------------------------------------------------------------------
  // My Office
  // -------------------------------------------------------------------------

  test('office page shows next and last match', async ({ page }) => {
    await loginAsUser1(page);

    const content = page.locator('#pagecontent');
    await expect(content).toContainText('Next Match');
    await expect(content).toContainText('Team 1');
    await expect(content).toContainText('Team 3');
    await expect(content).toContainText('My Last Match');
    await expect(content.getByRole('link', { name: 'Formation' })).toBeVisible();
  });

  test('office navigation menu links to the team management pages', async ({ page }) => {
    await loginAsUser1(page);

    const menu = page.locator('ul[aria-labelledby="labeloffice"]');
    await page.locator('#labeloffice').click();
    await expect(menu).toBeVisible();
    for (const label of ['My Team', 'Finances', 'Tickets', 'Sponsor']) {
      await expect(menuItem(menu, label)).toBeVisible();
    }

    await menuItem(menu, 'My Team').click();
    await expect(page.locator('h1')).toHaveText('My Players');
  });

  // -------------------------------------------------------------------------
  // My Players
  // -------------------------------------------------------------------------

  test('my players page lists the whole squad with salary total', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=myteam');
    await expect(page.locator('h1')).toHaveText('My Players');

    const table = page.locator('#pagecontent table');
    await expect(table.locator('tbody tr')).toHaveCount(24);

    const headers = table.locator('thead th');
    await expect(headers.nth(1)).toHaveText('Name');
    await expect(headers.nth(4)).toContainText('Salary per Match');

    await expect(table).toContainText('Player1_T1 Lastname11');
    await expect(table).toContainText('Player1_RS2 Lastname122');

    // 24 players x 50 000
    await expect(table.locator('tfoot')).toContainText(`1 200 000 ${CURRENCY}`);
  });

  test('player action dropdown offers all enabled squad actions', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=myteam');

    const firstRow = page.locator('#pagecontent table tbody tr').first();
    await firstRow.locator('.dropdown-toggle', { hasText: 'Action' }).click();

    const menu = firstRow.locator('.dropdown-menu');
    await expect(menu).toBeVisible();
    for (const label of [
      'Sell',
      'Mark as unsellable',
      'Offer to borrow',
      'Nominate as captain',
      'Dismiss',
    ]) {
      await expect(menuItem(menu, label)).toBeVisible();
    }

    // Contracts run for 30 matches, which is above
    // contract_max_number_of_remaining_matches (15), so no extension offer yet.
    await expect(menu).not.toContainText('Contract Extension');
  });

  test('dismiss action opens a confirmation modal instead of firing directly', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=myteam');

    const firstRow = page.locator('#pagecontent table tbody tr').first();
    await firstRow.locator('.dropdown-toggle', { hasText: 'Action' }).click();
    await menuItem(firstRow.locator('.dropdown-menu'), 'Dismiss').click();

    const modal = page.locator('div.modal.show');
    await expect(modal).toBeVisible();
    await expect(modal).toContainText('Player1_T1 Lastname11');
    await modal.getByRole('button', { name: 'Cancel' }).click();
    await expect(modal).toBeHidden();

    // Squad is untouched.
    await expect(page.locator('#pagecontent table tbody tr')).toHaveCount(24);
  });

  test('nominating a captain marks the player with the captain icon', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=myteam');

    const firstRow = page.locator('#pagecontent table tbody tr').first();
    await firstRow.locator('.dropdown-toggle', { hasText: 'Action' }).click();
    await menuItem(firstRow.locator('.dropdown-menu'), 'Nominate as captain').click();

    await expect(page.locator('#messages .alert-success')).toContainText(
      'Your new captain has been successfully selected!',
    );

    const captainRow = page.locator('#pagecontent table tbody tr', {
      hasText: 'Player1_T1 Lastname11',
    });
    await expect(captainRow.locator('i.bi-people')).toBeVisible();

    // The captain can no longer be nominated again.
    await captainRow.locator('.dropdown-toggle', { hasText: 'Action' }).click();
    await expect(captainRow.locator('.dropdown-menu')).not.toContainText('Nominate as captain');
  });

  test('marking a player unsellable is applied and can be reverted', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=myteam');

    const row = page.locator('#pagecontent table tbody tr', { hasText: 'Player1_T2 Lastname12' });
    await row.locator('.dropdown-toggle', { hasText: 'Action' }).click();
    await menuItem(row.locator('.dropdown-menu'), 'Mark as unsellable').click();

    await expect(page.locator('#messages .alert-success')).toContainText(
      'Player has been marked as unsellable.',
    );

    // The AJAX response re-rendered #pagecontent, so re-resolve the row.
    const markedRow = page.locator('#pagecontent table tbody tr', {
      hasText: 'Player1_T2 Lastname12',
    });
    // Only the marker next to the player name carries "darkIcon"; the same icon
    // is reused inside the action dropdown.
    await expect(markedRow.locator('i.bi-eye-slash.darkIcon')).toBeVisible();
    // Selling is no longer offered for an unsellable player.
    await markedRow.locator('.dropdown-toggle', { hasText: 'Action' }).click();
    await expect(menuItem(markedRow.locator('.dropdown-menu'), 'Sell')).toHaveCount(0);

    // Revert via the marker next to the player name.
    await markedRow.locator('a.ajaxLink', { has: page.locator('i.bi-eye-slash.darkIcon') }).click();
    await expect(page.locator('#messages .alert-success')).toContainText(
      'Unsellable flag has been removed.',
    );
    await expect(
      page
        .locator('#pagecontent table tbody tr', { hasText: 'Player1_T2 Lastname12' })
        .locator('i.bi-eye-slash.darkIcon'),
    ).toHaveCount(0);
  });

  // -------------------------------------------------------------------------
  // Formation and tactics
  // -------------------------------------------------------------------------

  test('formation page renders pitch, empty bench and player accordion', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');
    await expect(page.locator('h1')).toHaveText('Formation and Tactics');

    // Default setup 4-1-3-1-1-0 => 10 outfield positions + goalkeeper.
    await expect(page.locator('input[name="setup"]')).toHaveValue('4-1-3-1-1-0');
    await expect(page.locator('#pitch .position')).toHaveCount(11);
    await expect(page.locator('#pitch .position.freePosition')).toHaveCount(11);

    const rows = benchRows(page);
    await expect(rows).toHaveCount(5);
    for (let i = 0; i < 5; i++) {
      await expect(rows.nth(i)).toHaveClass(/freePosition/);
      await expect(rows.nth(i).locator('.benchPlaceholder')).toBeVisible();
      await expect(rows.nth(i).locator('.benchPlayerRemove')).toBeHidden();
      await expect(rows.nth(i).locator('.benchPlayerSubAdd')).toBeHidden();
      await expect(rows.nth(i).locator('.benchPlayerSubInfo')).toBeHidden();
    }

    // One accordion section per position, only the last one expanded.
    const sections = page.locator('#playersSelection .accordion-collapse');
    await expect(sections).toHaveCount(4);
    await expect(page.locator('#playersSelection .accordion-collapse.show')).toHaveCount(1);
    await expect(page.locator('#collapsegoaly .playerDraggable')).toHaveCount(2);
    await expect(page.locator('#collapsedefense .playerDraggable')).toHaveCount(6);
  });

  test('adding a player to the bench shows his name and the bench actions', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');

    const panel = await expandAccordion(page, 'Goalkeeper');
    const player = panel.locator('.playerDraggable').first();
    const playerId = await player.evaluate((el) => (el as HTMLElement).dataset.playerid);
    const playerName = await player.evaluate((el) => (el as HTMLElement).dataset.pname);
    expect(playerName).toBeTruthy();

    await player.locator('.playerAddToBenchLink').click();

    // Regression guard: addPlayerToBench() used to throw a SyntaxError on the
    // "> .benchPlayerInfo" selector, so the name was never rendered, and the
    // bench buttons were hidden by a stylesheet rule that show() could not
    // override. Both the label and the buttons must now appear.
    const row = benchRows(page).first();
    await expect(row).not.toHaveClass(/freePosition/);
    await expect(row.locator('.benchPlaceholder')).toBeHidden();
    await expect(row.locator('.benchPlayer')).toContainText(playerName!);
    await expect(row.locator('.benchPlayer')).toContainText('GK');
    await expect(row.locator('.benchPlayerRemove')).toBeVisible();
    await expect(row.locator('.benchPlayerSubAdd')).toBeVisible();

    // Hidden field that gets submitted must carry the player.
    await expect(page.locator('#bench1')).toHaveValue(playerId!);

    // The player himself switches from "add" to "remove" actions.
    await expect(player).toHaveClass(/playerIsOnBench/);
    await expect(player.locator('.playerAddToBenchLink')).toBeHidden();
    await expect(player.locator('.playerAddToPitchLink')).toBeHidden();
    await expect(player.locator('.playerRemoveLink')).toBeVisible();
  });

  test('removing a player from the bench restores the placeholder', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');

    const panel = await expandAccordion(page, 'Goalkeeper');
    const player = panel.locator('.playerDraggable').first();
    await player.locator('.playerAddToBenchLink').click();

    const row = benchRows(page).first();
    await expect(row.locator('.benchPlayer')).toBeVisible();

    await row.locator('.benchPlayerRemove').click();

    await expect(row).toHaveClass(/freePosition/);
    await expect(row.locator('.benchPlaceholder')).toBeVisible();
    await expect(row.locator('.benchPlayer')).toHaveCount(0);
    await expect(row.locator('.benchPlayerRemove')).toBeHidden();
    await expect(page.locator('#bench1')).toHaveValue('');

    await expect(player).not.toHaveClass(/playerIsOnBench/);
    await expect(player.locator('.playerAddToBenchLink')).toBeVisible();
  });

  test('bench is filled from top to bottom and capped at five players', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');

    const panel = await expandAccordion(page, 'Defense');
    const players = panel.locator('.playerDraggable');
    await expect(players).toHaveCount(6);

    const rows = benchRows(page);
    for (let i = 0; i < 5; i++) {
      const player = players.nth(i);
      const name = await player.evaluate((el) => (el as HTMLElement).dataset.pname);
      await player.locator('.playerAddToBenchLink').click();
      await expect(rows.nth(i)).not.toHaveClass(/freePosition/);
      await expect(rows.nth(i).locator('.benchPlayer')).toContainText(name!);
    }

    // Bench is full - the sixth player cannot be added anymore.
    const sixth = players.nth(5);
    await sixth.locator('.playerAddToBenchLink').click();
    await expect(sixth).not.toHaveClass(/playerIsOnBench/);
    await expect(rows.locator('.benchPlayer')).toHaveCount(5);
  });

  test('a player on the pitch cannot be put on the bench', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');

    const panel = await expandAccordion(page, 'Goalkeeper');
    const player = panel.locator('.playerDraggable').first();
    const playerId = await player.evaluate((el) => (el as HTMLElement).dataset.playerid);
    const playerName = await player.evaluate((el) => (el as HTMLElement).dataset.pname);

    await addPlayerToPitch(player, 'T');

    const goalPosition = page.locator('#pitch .position.T');
    await expect(goalPosition).not.toHaveClass(/freePosition/);
    await expect(goalPosition.locator('.positionPlayer')).toHaveText(playerName!);
    await expect(page.locator('#player1')).toHaveValue(playerId!);
    await expect(page.locator('#player1_pos')).toHaveValue('T');

    await expect(player.locator('.playerAddToBenchLink')).toBeHidden();
    await expect(benchRows(page).locator('.benchPlayer')).toHaveCount(0);
    await expect(page.locator('tr.benchposition.freePosition')).toHaveCount(5);

    // Removing him from the pitch re-enables the bench action.
    await goalPosition.locator('.positionPlayerRemove').click();
    await expect(goalPosition).toHaveClass(/freePosition/);
    await expect(page.locator('#player1')).toHaveValue('');
    await expect(player.locator('.playerAddToBenchLink')).toBeVisible();
  });

  test('clear all empties pitch and bench', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');

    const goalies = await expandAccordion(page, 'Goalkeeper');
    await addPlayerToPitch(goalies.locator('.playerDraggable').first(), 'T');

    const defenders = await expandAccordion(page, 'Defense');
    await defenders.locator('.playerDraggable').first().locator('.playerAddToBenchLink').click();
    await expect(benchRows(page).first()).not.toHaveClass(/freePosition/);

    await page.locator('.clearAllBtn').click();

    await expect(page.locator('#pitch .position.freePosition')).toHaveCount(11);
    await expect(page.locator('tr.benchposition.freePosition')).toHaveCount(5);
    await expect(page.locator('#player1')).toHaveValue('');
    await expect(page.locator('#bench1')).toHaveValue('');
  });

  test('a substitution can be planned and removed again', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');

    const goalies = await expandAccordion(page, 'Goalkeeper');
    const onPitch = goalies.locator('.playerDraggable').first();
    const onPitchId = await onPitch.evaluate((el) => (el as HTMLElement).dataset.playerid);
    const onPitchName = await onPitch.evaluate((el) => (el as HTMLElement).dataset.pname);
    await addPlayerToPitch(onPitch, 'T');

    const defenders = await expandAccordion(page, 'Defense');
    const onBench = defenders.locator('.playerDraggable').first();
    const onBenchId = await onBench.evaluate((el) => (el as HTMLElement).dataset.playerid);
    await onBench.locator('.playerAddToBenchLink').click();

    const row = benchRows(page).first();
    await expect(row.locator('.benchPlayer')).toBeVisible();

    await row.locator('.benchPlayerSubAdd').click();
    const modal = page.locator('#subModal1');
    await expect(modal).toBeVisible();

    // Only players on the pitch can be substituted out.
    await expect(modal.locator('#sub_out1 option')).toHaveCount(2);
    await expect(modal.locator(`#sub_out1 option[value="${onPitchId}"]`)).toHaveCount(1);

    await modal.locator('#sub_minute1').fill('60');
    await modal.locator('#sub_out1').selectOption(onPitchId!);
    await modal.locator('#sub_condition1').selectOption('Leading');
    await modal.locator('#sub_position1').selectOption('IV');
    await modal.getByRole('button', { name: 'Save' }).click();
    await expect(modal).toBeHidden();

    await expect(row).toHaveClass(/benchActiveSubstitution/);
    const subInfo = row.locator('.benchPlayerSubInfo');
    await expect(subInfo).toBeVisible();
    await expect(subInfo.locator('.benchPlayerSubInfoMinute')).toHaveText('60');
    await expect(subInfo.locator('.benchPlayerSubInfoPlayer')).toHaveText(onPitchName!);
    await expect(subInfo.locator('.benchPlayerSubInfoConditionLeading')).toBeVisible();
    await expect(subInfo.locator('.benchPlayerSubInfoConditionTie')).toBeHidden();
    await expect(row.locator('.benchPlayerSubAdd')).toBeHidden();

    await expect(page.locator('#sub1_in')).toHaveValue(onBenchId!);
    await expect(page.locator('#sub1_out')).toHaveValue(onPitchId!);
    await expect(page.locator('#sub1_minute')).toHaveValue('60');
    await expect(page.locator('#sub1_condition')).toHaveValue('Leading');
    await expect(page.locator('#sub1_position')).toHaveValue('IV');

    await subInfo.locator('.removeSubstitutionBtn').click();
    await expect(row).not.toHaveClass(/benchActiveSubstitution/);
    await expect(subInfo).toBeHidden();
    await expect(row.locator('.benchPlayerSubAdd')).toBeVisible();
    await expect(page.locator('#sub1_minute')).toHaveValue('');
  });

  test('a substitution is rejected when the minute is out of range', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');

    const goalies = await expandAccordion(page, 'Goalkeeper');
    const onPitch = goalies.locator('.playerDraggable').first();
    const onPitchId = await onPitch.evaluate((el) => (el as HTMLElement).dataset.playerid);
    await addPlayerToPitch(onPitch, 'T');

    const defenders = await expandAccordion(page, 'Defense');
    await defenders.locator('.playerDraggable').first().locator('.playerAddToBenchLink').click();

    const row = benchRows(page).first();
    await row.locator('.benchPlayerSubAdd').click();
    const modal = page.locator('#subModal1');
    await expect(modal).toBeVisible();

    await modal.locator('#sub_minute1').fill('120');
    await modal.locator('#sub_out1').selectOption(onPitchId!);
    await modal.getByRole('button', { name: 'Save' }).click();
    await expect(modal).toBeHidden();

    await expect(row).not.toHaveClass(/benchActiveSubstitution/);
    await expect(row.locator('.benchPlayerSubInfo')).toBeHidden();
    await expect(page.locator('#sub1_minute')).toHaveValue('');
  });

  test('formation with bench, tactics and free kick taker is saved and reloaded', async ({
    page,
  }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');

    // All eleven pitch positions are mandatory for a save, so let the
    // application preselect the strongest eleven first.
    const setupForm = page.locator('form', { has: page.locator('#preselect') });
    await setupForm.locator('button.dropdown-toggle').click();
    await setupForm.locator('.formationSetupSubmit[data-preselect="strongest"]').click();

    await expect(page.locator('h1')).toHaveText('Formation and Tactics');
    await expect(page.locator('#pitch .position.freePosition')).toHaveCount(0);
    await expect(page.locator('#playersSelection .playerIsOnPitch')).toHaveCount(11);

    const keeperId = await page.locator('#pitch .position.T').getAttribute('data-playerid');
    expect(keeperId).toBeTruthy();

    // Two defenders are left over after the preselect - put both on the bench.
    const defenders = await expandAccordion(page, 'Defense');
    const available = defenders.locator(
      '.playerDraggable:not(.playerIsOnPitch):not(.playerIsOnBench)',
    );
    await expect(available).toHaveCount(2);

    const bench1Name = await available.nth(0).evaluate((el) => (el as HTMLElement).dataset.pname);
    await available.nth(0).locator('.playerAddToBenchLink').click();
    const bench2Name = await available.nth(0).evaluate((el) => (el as HTMLElement).dataset.pname);
    await available.nth(0).locator('.playerAddToBenchLink').click();
    await expect(available).toHaveCount(0);

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

    await expect(page.locator('#pitch .position.freePosition')).toHaveCount(0);
    await expect(page.locator('#pitch .position.T')).toHaveAttribute('data-playerid', keeperId!);

    const rows = benchRows(page);
    await expect(rows.nth(0).locator('.benchPlayer')).toContainText(bench1Name!);
    await expect(rows.nth(1).locator('.benchPlayer')).toContainText(bench2Name!);
    await expect(rows.nth(2)).toHaveClass(/freePosition/);

    await expect(page.locator('#longpasses')).toBeChecked();
    await expect(page.locator('#counterattacks')).toBeChecked();
    await expect(page.locator('#offensive')).toHaveValue('70');
    await expect(page.locator('#freekickplayer')).toHaveValue(keeperId!);
  });

  test('an incomplete formation is rejected on save', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=formation');

    await page.locator('.clearAllBtn').click();
    await expect(page.locator('#pitch .position.freePosition')).toHaveCount(11);

    const goalies = await expandAccordion(page, 'Goalkeeper');
    await addPlayerToPitch(goalies.locator('.playerDraggable').first(), 'T');

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
    await expect(page.locator('#pitch .position')).toHaveCount(11);
    await expect(page.locator('#pitch .position.IV')).toHaveCount(3);
  });

  test('formation page runs without JavaScript errors', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', (error) => errors.push(`pageerror: ${error.message}`));
    page.on('console', (message) => {
      if (message.type() === 'error') errors.push(`console: ${message.text()}`);
    });

    await loginAsUser1(page);
    await page.goto('/?page=formation');

    // Exercise the code paths that were broken by the jQuery migration.
    // Start from an empty pitch so the goalkeeper is definitely available.
    await page.locator('.clearAllBtn').click();
    const panel = await expandAccordion(page, 'Goalkeeper');
    const player = panel.locator('.playerDraggable').first();
    await player.locator('.playerAddToBenchLink').click();
    await expect(benchRows(page).first().locator('.benchPlayer')).toBeVisible();
    await benchRows(page).first().locator('.benchPlayerRemove').click();
    await expect(benchRows(page).first()).toHaveClass(/freePosition/);

    expect(errors).toEqual([]);
  });

  // -------------------------------------------------------------------------
  // Training
  // -------------------------------------------------------------------------

  test('training page lists hireable trainers', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=training');
    await expect(page.locator('h1')).toHaveText('Training');

    const content = page.locator('#pagecontent');
    await expect(content).toContainText('Open Training Units');
    await expect(content).toContainText('You can execute another training unit every 24 hours.');
    await expect(content).toContainText('Choose a Trainer');

    const headers = content.locator('table thead th');
    await expect(headers.nth(1)).toHaveText('Salary per Unit');
    await expect(headers.nth(2)).toHaveText('Effectiveness: Technique Training');

    const trainerRow = content.locator('table tbody tr', { hasText: 'Coach Carl' });
    await expect(trainerRow).toContainText(`50 000 ${CURRENCY}`);
    await expect(trainerRow.getByRole('link', { name: 'Choose' })).toBeVisible();
  });

  test('choosing a trainer opens the hire form', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=training');

    await page
      .locator('#pagecontent table tbody tr', { hasText: 'Coach Carl' })
      .getByRole('link', { name: 'Choose' })
      .click();

    await expect(page).toHaveURL(/page=trainer-details/);
    await expect(page.locator('h1')).toHaveText('Coach Carl');

    const content = page.locator('#pagecontent');
    await expect(content).toContainText('Hire This Trainer');
    await expect(content).toContainText('Effectiveness: Stamina Training');
    await expect(page.locator('#units')).toBeVisible();
    // Number of units is mandatory.
    await expect(page.locator('#units')).toHaveAttribute('required', '');
    expect(await page.locator('#units').evaluate((el) => (el as HTMLInputElement).checkValidity()))
      .toBe(false);
  });

  // -------------------------------------------------------------------------
  // Sponsor
  // -------------------------------------------------------------------------

  test('sponsor page blocks a sponsor change early in the season', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=sponsor');
    await expect(page.locator('h1')).toHaveText('Sponsor');
    await expect(page.locator('#pagecontent')).toContainText(
      'You may choose a new sponsor only after the 4th match day.',
    );
  });

  // -------------------------------------------------------------------------
  // Stadium
  // -------------------------------------------------------------------------

  test('stadium page shows the stadium, upgrades and running construction', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=stadium');
    await expect(page.locator('h1')).toHaveText('Stadium');

    const content = page.locator('#pagecontent');
    await expect(content).toContainText('Sample Arena');
    await expect(content).toContainText('18 200');
    await expect(content).toContainText('Maintenance and Upgrading');
    await expect(content).toContainText('Grass quality');
    await expect(content).toContainText(`13 000 ${CURRENCY}`);

    // The stadium is drawn on a canvas.
    await expect(page.locator('canvas#stadium')).toBeVisible();

    // An extension is already ordered in the seed data, so no new order form.
    await expect(content).toContainText('Construction is currently in progress');
    await expect(content).toContainText('Builder Inc.');
  });

  test('stadium environment page shows existing and available buildings', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=stadiumenvironment');
    await expect(page.locator('h1')).toHaveText('Stadium Environment');

    const content = page.locator('#pagecontent');
    await expect(content).toContainText('Existing buildings');
    await expect(content).toContainText('Youth Center');
    await expect(content).toContainText('Available buildings');
    await expect(content).toContainText('Medical Center');
    await expect(content).toContainText(`80 000 ${CURRENCY}`);
    await expect(content.getByRole('link', { name: 'Build' }).first()).toBeVisible();
  });

  // -------------------------------------------------------------------------
  // Finances
  // -------------------------------------------------------------------------

  test('finances page shows budget and account statement', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=finances');
    await expect(page.locator('h1')).toHaveText('Finances');

    const content = page.locator('#pagecontent');
    // The exact amount is deliberately not asserted: the admin center CRUD
    // suite books a test transaction for this team, so the budget depends on
    // which specs ran before.
    await expect(content.locator('h3').first()).toHaveText(
      new RegExp(`^Budget: [\\d ]+ ${CURRENCY}$`),
    );

    const statement = content.locator('table');
    await expect(statement.locator('thead th').nth(1)).toHaveText('Sender / Recipient');
    const row = statement.locator('tbody tr', { hasText: 'Sponsor Alpha' });
    await expect(row).toContainText('Sponsor payment');
    await expect(row).toContainText(`100 000 ${CURRENCY}`);
  });

  // -------------------------------------------------------------------------
  // Tickets
  // -------------------------------------------------------------------------

  test('tickets page shows all price fields with the sold ratio', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=tickets');
    await expect(page.locator('h1')).toHaveText('Tickets');

    for (const id of ['#p_stands', '#p_seats', '#p_stands_grand', '#p_seats_grand', '#p_vip']) {
      await expect(page.locator(id)).toBeVisible();
      await expect(page.locator(id)).toHaveAttribute('required', '');
      await expect(page.locator(id)).not.toHaveValue('');
    }

    const content = page.locator('#pagecontent');
    await expect(content).toContainText('V.I.P. Lounges');
    await expect(content).toContainText('Last match sold: 0/10 000');
    await expect(content).toContainText('Last match sold: 0/200');
  });

  test('tickets form reports missing prices', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=tickets');

    for (const id of ['#p_stands', '#p_seats', '#p_stands_grand', '#p_seats_grand', '#p_vip']) {
      await page.locator(id).fill('');
    }
    await page.locator('#pagecontent button[type=submit]').click();

    await expect(page.locator('#messages .alert-danger')).toContainText('Invalid Input');
    await expect(page.locator('#pagecontent .invalid-feedback').first()).toContainText(
      'must be provided.',
    );
    await expect(page.locator('#messages .alert-success')).toHaveCount(0);
  });

  test('tickets form reports prices above the allowed maximum', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=tickets');

    await page.locator('#p_vip').fill('5000');
    await page.locator('#pagecontent button[type=submit]').click();

    await expect(page.locator('#messages .alert-danger')).toContainText('Invalid Input');
    await expect(page.locator('#pagecontent .invalid-feedback')).toContainText(
      'must not be higher than 1000.',
    );
  });

  test('tickets form saves new prices', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=tickets');

    await page.locator('#p_stands').fill('11');
    await page.locator('#p_seats').fill('16');
    await page.locator('#p_stands_grand').fill('21');
    await page.locator('#p_seats_grand').fill('26');
    await page.locator('#p_vip').fill('61');
    await page.locator('#pagecontent button[type=submit]').click();

    await expect(page.locator('#messages .alert-success')).toContainText('Successfully saved.');

    await page.goto('/?page=tickets');
    await expect(page.locator('#p_stands')).toHaveValue('11');
    await expect(page.locator('#p_vip')).toHaveValue('61');
  });

  // -------------------------------------------------------------------------
  // Sell player
  // -------------------------------------------------------------------------

  test('sell player page shows contract details and the minimum bid field', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=sell-player&id=23');
    await expect(page.locator('h1')).toHaveText('Sell Player1_RS1 Lastname121');

    const content = page.locator('#pagecontent');
    await expect(content).toContainText('Put on Transfer List');
    await expect(content).toContainText('Market value');
    await expect(content).toContainText(`725 000 ${CURRENCY}`);
    await expect(content).toContainText(`50 000 ${CURRENCY}`);
    await expect(content).toContainText('30 matches');
    await expect(content.getByRole('link', { name: 'To Player Profile' })).toBeVisible();

    await expect(page.locator('#min_bid')).toBeVisible();
    await expect(page.locator('#min_bid')).toHaveAttribute('required', '');
  });

  test('sell player form requires a minimum bid', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=sell-player&id=23');

    await page.locator('#min_bid').fill('');
    expect(
      await page.locator('#min_bid').evaluate((el) => (el as HTMLInputElement).checkValidity()),
    ).toBe(false);

    await page.locator('#pagecontent button[type=submit]').click();

    // Native validation blocked the submit, so we stay on the same page.
    await expect(page.locator('h1')).toHaveText('Sell Player1_RS1 Lastname121');
    await expect(page.locator('#messages .alert-success')).toHaveCount(0);
  });

  test('sell player form rejects a bid below half the market value', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=sell-player&id=23');

    // Market value is 725 000, so anything below 362 500 is rejected.
    await page.locator('#min_bid').fill('1000');
    await page.locator('#pagecontent button[type=submit]').click();

    await expect(page.locator('#messages .alert-danger')).toContainText(
      "The minimum bid must be at least half of the player's market value.",
    );
    await expect(page.locator('h1')).toHaveText('Sell Player1_RS1 Lastname121');
  });

  test('sell player form puts the player on the transfer market', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=sell-player&id=23');

    await page.locator('#min_bid').fill('500000');
    await page.locator('#pagecontent button[type=submit]').click();

    // The controller forwards to the transfer market.
    await expect(page.locator('h1')).toHaveText('Transfer Market');
    await expect(page.locator('#messages .alert-success')).toContainText(
      'The player is now available at the transfer market.',
    );
    await expect(page.locator('#overview table')).toContainText('Player1_RS1 Lastname121');

    // My Players marks him with the transfer market icon and hides "Sell".
    await page.goto('/?page=myteam');
    const row = page.locator('#pagecontent table tbody tr', { hasText: 'Player1_RS1 Lastname121' });
    await expect(row.locator('i.bi-hand-index-thumb')).toBeVisible();
    await row.locator('.dropdown-toggle', { hasText: 'Action' }).click();
    await expect(
      menuItem(row.locator('.dropdown-menu'), 'Sell'),
    ).toHaveCount(0);
  });

  // -------------------------------------------------------------------------
  // Transfer market
  // -------------------------------------------------------------------------

  test('transfer market position filter narrows the list', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=transfermarket');
    await expect(page.locator('h1')).toHaveText('Transfer Market');

    await expect(page.locator('#overview table')).toContainText('Player1_RS1 Lastname121');

    // The listed player is a forward, so filtering for goalkeepers finds nothing.
    await page.selectOption('#position', 'goaly');
    await page.getByRole('button', { name: 'Display' }).click();
    await expect(page.locator('#pagecontent')).toContainText(
      'Could not find any players on the transfer market.',
    );

    await page.selectOption('#position', 'striker');
    await page.getByRole('button', { name: 'Display' }).click();
    await expect(page.locator('#overview table')).toContainText('Player1_RS1 Lastname121');

    await page.getByRole('link', { name: 'Reset' }).click();
    await expect(page.locator('#position')).toHaveValue('');
  });

  test('transfer market tabs load their content via AJAX', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=transfermarket');

    await expect(page.locator('#mybidslist')).toBeEmpty();

    const [response] = await Promise.all([
      page.waitForResponse((r) => r.url().includes('ajax.php') && r.url().includes('mybids')),
      page.locator('#transferTab a', { hasText: 'My Bids' }).click(),
    ]);
    expect(response.ok()).toBe(true);

    await expect(page.locator('#mybids')).toBeVisible();
    await expect(page.locator('#mybidslist')).not.toBeEmpty();
  });

  // -------------------------------------------------------------------------
  // Contract extension
  // -------------------------------------------------------------------------

  test('extend contract page shows the existing contract and the offer form', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=extend-contract&id=22');
    await expect(page.locator('h1')).toHaveText('Negotiation with Player1_MS2 Lastname112');

    const content = page.locator('#pagecontent');
    await expect(content).toContainText('Existing Contract');
    await expect(content).toContainText(`50 000 ${CURRENCY}`);
    await expect(content).toContainText('30 matches');
    await expect(content).toContainText(
      "Note that the player's satisfaction will decrease for each offer that is too low.",
    );
    await expect(content).toContainText('Your Offer');

    await expect(page.locator('#salary')).toHaveAttribute('required', '');
    await expect(page.locator('#matches')).toHaveAttribute('required', '');
    await expect(page.locator('#goal_bonus')).not.toHaveAttribute('required', '');
  });

  test('extend contract form reports a contract length below the minimum', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=extend-contract&id=22');

    await page.locator('#salary').fill('200000');
    await page.locator('#goal_bonus').fill('2000');
    await page.locator('#matches').fill('10');
    await submitContractOffer(page);

    await expect(page.locator('#messages .alert-danger')).toContainText('Invalid Input');
    // Not scoped to #pagecontent on purpose, see submitContractOffer().
    await expect(page.locator('.invalid-feedback')).toContainText('must be at least 20.');
  });

  test('extend contract form rejects a salary the player will not accept', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=extend-contract&id=22');

    // Current salary is 50 000 and satisfaction 60, so the player asks for
    // at least 70 000 per match.
    await page.locator('#salary').fill('55000');
    await page.locator('#goal_bonus').fill('2000');
    await page.locator('#matches').fill('30');
    await submitContractOffer(page);

    await expect(page.locator('#messages .alert-danger')).toContainText(
      'The offered salary is too low for the player.',
    );
  });

  test('extend contract form rejects a goal bonus below the current one', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=extend-contract&id=22');

    // Goal bonus must exceed 1 000 * 1.3.
    await page.locator('#salary').fill('200000');
    await page.locator('#goal_bonus').fill('1000');
    await page.locator('#matches').fill('30');
    await submitContractOffer(page);

    await expect(page.locator('#messages .alert-danger')).toContainText(
      'The player wants a higher goal bonus.',
    );
  });

  test('extend contract form saves the new conditions', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=extend-contract&id=22');

    await page.locator('#salary').fill('200000');
    await page.locator('#goal_bonus').fill('5000');
    await page.locator('#matches').fill('40');
    await submitContractOffer(page);

    await expect(page.locator('#messages .alert-success')).toContainText(
      'The contract has been successfully extended under the new conditions!',
    );
    // The offer form is replaced by a link back to the squad, and the
    // "existing contract" summary shows the new conditions.
    const content = page.locator('#pagecontent');
    await expect(content.getByRole('link', { name: 'My Team' })).toBeVisible();
    await expect(content).toContainText(`200 000 ${CURRENCY}`);
    await expect(content).toContainText(`5 000 ${CURRENCY}`);
    await expect(content).toContainText('40 matches');

    await page.goto('/?page=myteam');
    const row = page.locator('#pagecontent table tbody tr', { hasText: 'Player1_MS2 Lastname112' });
    await expect(row).toContainText(`200 000 ${CURRENCY}`);
    await expect(row.locator('td').last()).toContainText('40');
  });

  // -------------------------------------------------------------------------
  // Transfer offers
  // -------------------------------------------------------------------------

  test('transfer offers page lists the received offer', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=transferoffers');
    await expect(page.locator('h1')).toHaveText('Transfer Offers');

    const content = page.locator('#pagecontent');
    await expect(content).toContainText('Received Offers');
    const row = content.locator('table tbody tr', { hasText: 'Player1_T1 Lastname11' });
    await expect(row).toContainText('Team 2');
    await expect(row).toContainText(`1 000 000 ${CURRENCY}`);
    await expect(row.getByRole('link', { name: 'Accept' })).toBeVisible();
    // The reject action opens a modal and is marked up as role="button".
    await expect(row.getByRole('button', { name: 'Reject' })).toBeVisible();
  });

  test('a received transfer offer can be rejected with a comment', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=transferoffers');

    const row = page.locator('#pagecontent table tbody tr', { hasText: 'Player1_T1 Lastname11' });
    await row.getByRole('button', { name: 'Reject' }).click();

    const modal = page.locator('.modal.show');
    await expect(modal).toContainText('Reject offer');
    await expect(modal.locator('#allow_alternative')).toBeChecked();
    await modal.locator('#comment').fill('Not enough for my top scorer.');
    await modal.getByRole('button', { name: 'Submit' }).click();

    await expect(page.locator('#messages .alert-success')).toContainText(
      'The offer has been successfully rejected.',
    );
    await expect(page.locator('#pagecontent')).toContainText(
      'You have not received any offers from other managers yet.',
    );
  });

  // -------------------------------------------------------------------------
  // Authentication
  // -------------------------------------------------------------------------

  for (const teamPage of [
    'office',
    'myteam',
    'formation',
    'training',
    'tickets',
    'finances',
    'stadium',
    'sponsor',
  ]) {
    test(`${teamPage} page requires a login`, async ({ page }) => {
      await page.goto(`/?page=${teamPage}`);
      await expect(page.locator('h1')).toHaveText('Log In');
    });
  }
});

