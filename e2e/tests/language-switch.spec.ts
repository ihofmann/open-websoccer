import { expect, test } from '@playwright/test';

/**
 * E2E: Language switching on the frontend start page.
 *
 * The test visits the home page, switches the UI language to German, English,
 * Italian and Spanish (in that order) and verifies that the news box title
 * is translated correctly for each language.
 *
 * The news block is rendered on the home page via
 * `viewHandler.renderBlock('news')`. Its title comes from the i18n key
 * `news_block_title`. The block carries a `data-testid="news-box"` attribute
 * (set in topnews.twig) so the locator is robust against layout changes.
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

    // The news block has a data-testid="news-box" attribute (set in topnews.twig
    // via the box_testid block in box.twig) so the locator is independent of
    // column layout classes.
    const newsBoxTitle = page.getByTestId('news-box').locator('h4');
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
    // The application rejects the same action id twice within
    // DOUBLE_SUBMIT_CHECK_SECONDS (3 s, compared on whole-second timestamps)
    // with a "double submit" error, so consecutive switches must be spaced out.
    const doubleSubmitWaitMs = 3_100;

    // Switch through all languages in order and verify each one.
    const languages = ['de', 'en', 'it', 'es'];
    for (const [index, lang] of languages.entries()) {
      if (index > 0) {
        await page.waitForTimeout(doubleSubmitWaitMs);
      }
      await switchLanguageAndAssert(page, lang);
    }
  });
});
