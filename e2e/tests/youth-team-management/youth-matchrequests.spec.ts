import { expect, test } from '@playwright/test';
import { CURRENCY, loginAsUser1 } from './_helpers';

/**
 * E2E: the "Youth Match Requests" page – browsing open requests, cancelling
 * own requests, and accepting requests from other teams.
 *
 * Seed data:
 *   * Request #1: Team 1 (user1), 7 days ahead, reward 5 000 → Cancel button.
 *   * Request #2: Team 2 (user2), 5 days ahead, no reward      → Accept button.
 *
 * youth-matchrequests-create.spec.ts ran before this one and created an
 * additional request for Team 1, so Team 1 now has 2 open requests.
 *
 * This spec is serial: tests 2 and 3 mutate state (cancel + accept).
 */

test.describe.serial('Youth match requests page (logged in as user1)', () => {
  test('lists open requests with correct action buttons', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-matchrequests');
    await expect(page.locator('h1')).toHaveText('Match Requests');

    await expect(page.getByTestId('matchrequest-create-button')).toBeVisible();

    const rows = page.getByTestId('matchrequest-row');
    // At least Team 1's (2) and Team 2's (1) requests.
    await expect(rows).not.toHaveCount(0);

    // Team 1's requests → Cancel button (owned by user1).
    const team1Rows = rows.filter({ hasText: 'Team 1' });
    await expect(team1Rows.first().getByTestId('matchrequest-cancel')).toBeVisible();

    // Team 2's request → Accept button.
    const team2Row = rows.filter({ hasText: 'Team 2' });
    await expect(team2Row.getByTestId('matchrequest-accept')).toBeVisible();

    // The seeded Team 1 request has a 5 000 reward.
    const seededRow = team1Rows.filter({ hasText: `5 000 ${CURRENCY}` });
    await expect(seededRow).toHaveCount(1);
  });

  test('cancelling own request succeeds', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-matchrequests');

    // Cancel Team 1's original request (the first Team 1 row).
    const team1Row = page.getByTestId('matchrequest-row').filter({ hasText: 'Team 1' }).first();
    await team1Row.getByTestId('matchrequest-cancel').click();

    await expect(page.locator('h1')).toHaveText('Match Requests');
    await expect(page.locator('#messages .alert-success')).toContainText(
      'The match request has been successfully canceled.',
    );
  });

  test('accepting another team\'s request creates a match', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-matchrequests');

    const team2Row = page.getByTestId('matchrequest-row').filter({ hasText: 'Team 2' });
    await team2Row.getByTestId('matchrequest-accept').click();

    // The controller forwards to the youth matches page.
    await expect(page.locator('h1')).toHaveText('Matches of my team');
    await expect(page.locator('#messages .alert-success')).toContainText(
      'The match has been successfully created!',
    );
    await expect(page.locator('#messages .alert-success')).toContainText(
      'Set your tactics for this match now.',
    );

    // The accepted request is gone from the requests list.
    await page.goto('/?page=youth-matchrequests');
    await expect(page.getByTestId('matchrequest-row').filter({ hasText: 'Team 2' })).toHaveCount(0);
  });
});
