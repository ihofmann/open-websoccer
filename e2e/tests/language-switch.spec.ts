import { expect, test } from '@playwright/test';

/**
 * E2E: Language switching on the frontend start page.
 *
 * The test visits the home page, switches the UI language to German, English,
 * Italian and Spanish (in that order) and verifies that the news box title
 * is translated correctly for each language.
 *
 * The news block is rendered on the home page in the first content column
 * via `viewHandler.renderBlock('news')`. Its title comes from the i18n key
 * `news_block_title`.
 *
 * No login is required – guests can view the home page and switch languages.
 *
 * Seed data includes at least one published news article so the block renders.
 */
test.describe('language switching on start page', () => {
  // Expected news_block_title per language.
  const expectedTitles: Record<string, string> = {
    de: 'Neuigkeiten',
    en: 'News',
    it: 'Notizie',
    es: 'Noticias',
  };

  /**
   * Navigate to the home page with a switch-language action so that the
   * session language is set before the page renders.
   */
  async function switchLanguageAndAssert(page: import('@playwright/test').Page, lang: string) {
    const expected = expectedTitles[lang];
    expect(expected).toBeDefined();

    // The switch-language action sets the session language, then the page
    // is rendered in that language.
    await page.goto(`/?page=home&action=switch-language&lang=${lang}`);

    // The news block is the first .box in the first content column.
    const newsBoxTitle = page.locator('.col-md-4 .box h4').first();
    await expect(newsBoxTitle).toHaveText(expected);
  }

  test('news box title is translated when switching to German', async ({ page }) => {
    await switchLanguageAndAssert(page, 'de');
  });

  test('news box title is translated when switching to English', async ({ page }) => {
    await switchLanguageAndAssert(page, 'en');
  });

  test('news box title is translated when switching to Italian', async ({ page }) => {
    await switchLanguageAndAssert(page, 'it');
  });

  test('news box title is translated when switching to Spanish', async ({ page }) => {
    await switchLanguageAndAssert(page, 'es');
  });

  test('all four languages in sequence in a single session', async ({ page }) => {
    // Switch through all languages in order and verify each one.
    for (const lang of ['de', 'en', 'it', 'es']) {
      await switchLanguageAndAssert(page, lang);
    }
  });
});
