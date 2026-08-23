import { expect, test } from '@playwright/test';
import { loginAs, loginAsUser1, nationalPlayerRow, PLAYER_IDS } from './_helpers';

/**
 * E2E: the "National Team" page – listing nominated players grouped by
 * position, player details, the nominate-players link, and the remove-player
 * action.
 *
 * Seed data (before this spec runs):
 *   Team 41 "England" (user1) has 5 pre-nominated players:
 *     Goalkeeper:  Player1_T1  (ID 1)
 *     Defense:     Player1_LV1 (ID 3), Player1_IV1 (ID 5)
 *     Midfield:    Player1_LM1 (ID 9)
 *     Forward:     Player1_LS1 (ID 19)
 *
 * This spec runs before nominate-national-players.spec.ts (alphabetical order)
 * and removes player 19 (Player1_LS1).  After this spec, 4 players remain.
 */

test.describe.serial('National Team page (logged in as user1)', () => {
  test('renders the page title and team name', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nationalteam');

    await expect(page.locator('h1')).toHaveText('National Team');
    await expect(page.getByTestId('nt-team-name')).toHaveText('England');
  });

  test('shows the nominate players link', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nationalteam');

    const link = page.getByTestId('nt-nominate-link');
    await expect(link).toBeVisible();
    await expect(link).toContainText('Nominate Players');
  });

  test('lists players grouped by position with section headers', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nationalteam');

    // One table per position group (4 position groups → 4 tables).
    const tables = page.getByTestId('nt-players-table');
    await expect(tables).toHaveCount(4);

    // 4 position sections: Goalkeeper, Defense, Midfield, Forward.
    const sections = page.getByTestId('nt-position-section');
    await expect(sections).toHaveCount(4);
    await expect(sections.nth(0)).toContainText('Goalkeeper');
    await expect(sections.nth(1)).toContainText('Defense');
    await expect(sections.nth(2)).toContainText('Midfield');
    await expect(sections.nth(3)).toContainText('Forward');
  });

  test('shows 5 pre-nominated players', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nationalteam');

    const rows = page.getByTestId('nt-player-row');
    await expect(rows).toHaveCount(5);

    // Each player has a remove button.
    await expect(page.getByTestId('nt-player-remove')).toHaveCount(5);
  });

  test('player row displays name, club, and age', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nationalteam');

    const row = nationalPlayerRow(page, PLAYER_IDS.goalkeeper);
    await expect(row).toBeVisible();
    await expect(row).toContainText('Player1_T1');
    await expect(row).toContainText('Team 1');
    // age: geburtstag 1995-06-15, players_aging = birthday → 31 in 2026.
    await expect(row).toContainText('31');
  });

  test('player row displays strength attributes', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nationalteam');

    const row = nationalPlayerRow(page, PLAYER_IDS.goalkeeper);
    // Strength 75, technique 70, freshness 65, stamina 80, satisfaction 60.
    await expect(row).toContainText('75');
    await expect(row).toContainText('70');
    await expect(row).toContainText('65');
    await expect(row).toContainText('80');
    await expect(row).toContainText('60');
  });

  test('defense section shows 2 players', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nationalteam');

    // The Defense section header shows "(2)".
    const defenseHeader = page.getByTestId('nt-position-section').filter({ hasText: 'Defense' });
    await expect(defenseHeader).toContainText('(2)');

    // Both defense players are present.
    await expect(nationalPlayerRow(page, PLAYER_IDS.defenseLV)).toBeVisible();
    await expect(nationalPlayerRow(page, PLAYER_IDS.defenseIV)).toBeVisible();
  });

  test('forward section shows 1 player before removal', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nationalteam');

    const forwardHeader = page.getByTestId('nt-position-section').filter({ hasText: 'Forward' });
    await expect(forwardHeader).toContainText('(1)');
    await expect(nationalPlayerRow(page, PLAYER_IDS.forward)).toBeVisible();
  });

  test('removing a player shows success and removes the row', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=nationalteam');

    // Verify player 19 is present before removal.
    await expect(nationalPlayerRow(page, PLAYER_IDS.forward)).toBeVisible();

    // Click the remove button for player 19.
    const row = nationalPlayerRow(page, PLAYER_IDS.forward);
    await row.getByTestId('nt-player-remove').click();

    // The action redirects to the nationalteam page with a success message.
    await expect(page.locator('h1')).toHaveText('National Team');
    await expect(page.locator('#messages .alert-success')).toContainText(
      'The player has been successfully removed from your team.',
    );

    // The player is gone.
    await expect(nationalPlayerRow(page, PLAYER_IDS.forward)).toHaveCount(0);

    // 4 players remain.
    await expect(page.getByTestId('nt-player-row')).toHaveCount(4);

    // The Forward section is no longer rendered (no players in that position).
    const sections = page.getByTestId('nt-position-section');
    await expect(sections).toHaveCount(3);
    for (let i = 0; i < 3; i++) {
      await expect(sections.nth(i)).not.toContainText('Forward');
    }
  });

  test('empty national team shows an info message', async ({ page }) => {
    // user3 manages Team 43 "Italy" which has no nominated players.
    await loginAs(page, 'user3', 'user3');
    await page.goto('/?page=nationalteam');

    await expect(page.locator('h1')).toHaveText('National Team');
    await expect(page.getByTestId('nt-team-name')).toHaveText('Italy');
    await expect(page.getByTestId('nt-players-table')).toHaveCount(0);
    await expect(page.getByTestId('nt-no-players')).toBeVisible();
    await expect(page.getByTestId('nt-no-players')).toContainText(
      'You have not nominated any players yet.',
    );
  });
});
