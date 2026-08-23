import { expect, test } from '@playwright/test';
import { loginAsUser1 } from './_helpers';

/**
 * E2E: the "Create Youth Match Request" page – posting a new open request for
 * a youth friendly match.
 *
 * Seed data: Team 1 (user1) has 12 youth players (>= 11 required) and 1
 * existing open match request (ID 1).  The config defaults are:
 *   youth_matchrequest_max_open_requests = 2
 *   youth_matchrequest_allowedtimes     = "14:00,15:00"
 *   youth_matchrequest_max_futuredays   = 14
 *
 * The date dropdown is populated by the model with the next 14 days at the
 * allowed times.  We pick the first option for valid submissions.
 */

test.describe.serial('Create youth match request page (logged in as user1)', () => {
  test('renders the form with date and reward fields', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-matchrequests-create');
    await expect(page.locator('h1')).toHaveText('Post a new youth match request');

    await expect(page.getByTestId('matchrequest-create-form')).toBeVisible();

    const dateSelect = page.getByTestId('matchrequest-create-matchdate');
    await expect(dateSelect).toBeVisible();
    // Blank option + 14 days × 2 times = 28 options.
    const dateOptions = dateSelect.locator('option');
    await expect(dateOptions).toHaveCount(29);

    await expect(page.getByTestId('matchrequest-create-reward')).toBeVisible();
    await expect(page.getByTestId('matchrequest-create-submit')).toBeVisible();
    await expect(page.getByTestId('matchrequest-create-cancel')).toBeVisible();
  });

  test('submitting without a date shows a validation error', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-matchrequests-create');

    // Leave the date unselected (blank option) and submit.
    await page.getByTestId('matchrequest-create-submit').click();

    // The framework validates the required matchdate parameter before the
    // controller runs, showing a generic validation error.
    await expect(page.locator('#messages .alert-danger')).toContainText(
      'Invalid Input',
    );
    // Still on the create page.
    await expect(page.locator('h1')).toHaveText('Post a new youth match request');
  });

  test('submitting with an excessive reward shows a budget error', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-matchrequests-create');

    // Select the first available date.
    const dateSelect = page.getByTestId('matchrequest-create-matchdate');
    await dateSelect.selectOption({ index: 1 });

    // Enter a reward higher than the team budget (5 000 000).
    await page.getByTestId('matchrequest-create-reward').fill('9999999');
    await page.getByTestId('matchrequest-create-submit').click();

    await expect(page.locator('#messages .alert-danger')).toContainText(
      'You cannot afford the entered reward at the moment.',
    );
  });

  test('submitting with a valid date and no reward creates the request', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-matchrequests-create');

    // Select the first available date.
    await page.getByTestId('matchrequest-create-matchdate').selectOption({ index: 1 });

    // Leave reward empty (optional field).
    await page.getByTestId('matchrequest-create-submit').click();

    // The controller forwards to the match requests list.
    await expect(page.locator('h1')).toHaveText('Match Requests');
    await expect(page.locator('#messages .alert-success')).toContainText(
      'Your match request has been successfully published.',
    );
  });

  test('cancel link returns to the match requests list', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto('/?page=youth-matchrequests-create');

    await page.getByTestId('matchrequest-create-cancel').click();
    await expect(page.locator('h1')).toHaveText('Match Requests');
  });
});
