import { expect, test, type Locator, type Page } from '@playwright/test';

/**
 * E2E: country and language flags.
 *
 * Flags are SVG files copied from the flag-icons npm package into
 * websoccer/img/flags by `npm run build:flags` (scripts/copy-flags.js) and
 * rendered through the macros in templates/default/macros/flags.twig:
 *   * languages/<language code>.svg - language switcher in the navigation bar
 *   * countries/<nationality>.svg   - nationality of a player or league
 *
 * Seed data (e2e/seed/seed_data.sql):
 *   * player 1   "Player1_T1"    nation "England"
 *   * player 961 "German Keeper" nation "Deutschland"
 *   * player 973 "Free Agent"    nation "Spain", which deliberately has no flag
 *     file, so the macro falls back to the plain country name
 *   * leagues in "England" and "Deutschland", both with clubs without a
 *     manager, so the free clubs page renders one flag per country
 */

/**
 * Asserts that an <img> really shows the expected flag: the browser decoded the
 * SVG (a broken image has no intrinsic size), the element is laid out with a
 * visible box, and the file itself is served as SVG.
 */
async function expectFlagRendered(page: Page, flag: Locator, expectedFile: string) {
  await expect(flag).toBeVisible();

  const source = await flag.getAttribute('src');
  expect(source).toBe(`/img/flags/${expectedFile}`);

  expect(await flag.evaluate((img: HTMLImageElement) => img.naturalWidth)).toBeGreaterThan(0);

  const box = await flag.boundingBox();
  expect(box!.width).toBeGreaterThan(10);
  expect(box!.height).toBeGreaterThan(7);

  const response = await page.request.get(source!);
  expect(response.status()).toBe(200);
  expect(response.headers()['content-type']).toContain('image/svg+xml');
}

test.describe('country and language flags', () => {
  test('language switcher shows the flag of the current language and of every option', async ({
    page,
  }) => {
    await page.goto('/?page=home');

    // English is the default language of the E2E installation.
    await expectFlagRendered(
      page,
      page.getByTestId('lang-switch').locator('img.ws-flag'),
      'languages/en.svg',
    );

    await page.getByTestId('lang-switch').click();

    for (const language of ['de', 'en', 'es', 'it']) {
      await expectFlagRendered(
        page,
        page.getByTestId(`lang-option-${language}`).locator('img.ws-flag'),
        `languages/${language}.svg`,
      );
    }
  });

  test('player details show the flag of the player nationality', async ({ page }) => {
    await page.goto('/?page=player&id=1');
    const englishPlayer = page.getByTestId('player-nationality');
    await expectFlagRendered(page, englishPlayer.locator('img.ws-flag'), 'countries/England.svg');
    await expect(englishPlayer.locator('img.ws-flag')).toHaveAttribute('title', 'England');

    await page.goto('/?page=player&id=961');
    const germanPlayer = page.getByTestId('player-nationality');
    await expectFlagRendered(page, germanPlayer.locator('img.ws-flag'), 'countries/Deutschland.svg');
    // The flag label is translated, the file name is not.
    await expect(germanPlayer.locator('img.ws-flag')).toHaveAttribute('title', 'Germany');
  });

  test('player details fall back to the country name when no flag exists', async ({ page }) => {
    await page.goto('/?page=player&id=973');

    const nationality = page.getByTestId('player-nationality');
    await expect(nationality).toHaveText('Spain');
    await expect(nationality.locator('img')).toHaveCount(0);
  });

  test('free clubs page shows the flag of every country', async ({ page }) => {
    await page.goto('/?page=freeclubs');

    const countries = page.locator('#countries .accordion-button');
    await expect(countries).toHaveCount(2);

    await expectFlagRendered(
      page,
      countries.filter({ hasText: 'Germany' }).locator('img.ws-flag'),
      'countries/Deutschland.svg',
    );
    await expectFlagRendered(
      page,
      countries.filter({ hasText: 'England' }).locator('img.ws-flag'),
      'countries/England.svg',
    );
  });
});
