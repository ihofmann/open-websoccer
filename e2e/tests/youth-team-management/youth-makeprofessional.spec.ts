import { expect, test } from '@playwright/test';
import { loginAsUser1, PLAYER_IDS, youthPlayerRow } from './_helpers';

/**
 * E2E: the "Make Professional" page – converting a youth player into a
 * professional player by selecting a main position.
 *
 * Seed data: Player ID 2 "Young Goalie" belongs to Team 1, age 17, Torwart,
 * strength 60.  The youth_min_age_professional config defaults to 16.
 *
 * This spec is serial: the submission test promotes the player.
 */

test.describe.serial('Make professional page (logged in as user1)', () => {
  test('shows player details and position selector for a goalkeeper', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youthplayer-makeprofessional&id=${PLAYER_IDS.makeProfessional}`);

    await expect(page.locator('h1')).toHaveText('Hire as professional');

    const details = page.getByTestId('youth-player-details');
    await expect(details).toBeVisible();
    await expect(details).toContainText('Young Goalie');

    await expect(page.getByTestId('youth-makeprofessional-form')).toBeVisible();

    // A goalkeeper can only be assigned position "T", which is pre-selected.
    const select = page.getByTestId('youth-makeprofessional-mainposition');
    await expect(select).toBeVisible();
    const options = select.locator('option');
    await expect(options).toHaveCount(2); // blank option + "T"
    await expect(select).toHaveValue('T');

    await expect(page.getByTestId('youth-makeprofessional-submit')).toBeVisible();
    await expect(page.getByTestId('youth-makeprofessional-cancel')).toBeVisible();
  });

  test('submitting promotes the player to the professional team', async ({ page }) => {
    await loginAsUser1(page);
    await page.goto(`/?page=youthplayer-makeprofessional&id=${PLAYER_IDS.makeProfessional}`);

    // "T" is already selected (only valid option for a goalkeeper).
    await page.getByTestId('youth-makeprofessional-submit').click();

    // The controller forwards to "myteam" (the professional squad page).
    await expect(page.locator('h1')).toHaveText('My Players');
    await expect(page.locator('#messages .alert-success')).toContainText(
      'The player now belongs to the professional team!',
    );

    // The youth player is gone from the youth team.
    await page.goto('/?page=youth-team');
    await expect(youthPlayerRow(page, 'Young Goalie')).toHaveCount(0);
  });

  test('cancel link returns to the youth team page', async ({ page }) => {
    // Use a different eligible player (ID 6, age 16, Abwehr).
    await loginAsUser1(page);
    await page.goto('/?page=youthplayer-makeprofessional&id=6');

    await page.getByTestId('youth-makeprofessional-cancel').click();
    await expect(page.locator('h1')).toHaveText('My Youth Team');
  });

  test('a defender shows LV, IV, RV position options', async ({ page }) => {
    // Player ID 6 "Young Defender", age 16, Abwehr.
    await loginAsUser1(page);
    await page.goto('/?page=youthplayer-makeprofessional&id=6');

    const select = page.getByTestId('youth-makeprofessional-mainposition');
    const options = select.locator('option');
    // blank + LV + IV + RV = 4 options.
    await expect(options).toHaveCount(4);
  });
});
