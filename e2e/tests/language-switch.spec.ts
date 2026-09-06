import { expect, test } from '@playwright/test';

/**
 * E2E: Language switching on the frontend start page.
 *
 * The test visits the home page, opens the language dropdown in the top
 * navigation bar, switches the UI language to German, English, Italian and
 * Spanish (in that order) and verifies that the news box title is translated
 * correctly for each language.
 *
 * The language dropdown lives in navigationbar.twig. Its toggle button carries
 * `data-testid="lang-switch"` and each language option carries
 * `data-testid="lang-option-<code>"` (e.g. `lang-option-de`), so the locators
 * are independent of CSS classes or layout.
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
   * Open the language dropdown in the top bar and click the option for the
   * given language code. The option link navigates to the switch-language
   * action URL, which sets the session language and re-renders the home page.
   */
  async function switchLanguageAndAssert(page: import('@playwright/test').Page, lang: string) {
    const expected = expectedTitles[lang];
    expect(expected).toBeDefined();

    // Open the language dropdown in the top navigation bar.
    await page.getByTestId('lang-switch').click();

    // Click the language option — this navigates to the switch-language
    // action URL, which sets the session language and renders the page.
    await page.getByTestId(`lang-option-${lang}`).click();

    // The news block has a data-testid="news-box" attribute (set in topnews.twig
    // via the box_testid block in box.twig) so the locator is independent of
    // column layout classes.
    const newsBoxTitle = page.getByTestId('news-box').locator('h4');
    await expect(newsBoxTitle).toHaveText(expected);
  }

  test('news box title is translated when switching to German', async ({ page }) => {
    await page.goto('/?page=home');
    await switchLanguageAndAssert(page, 'de');
  });

  test('news box title is translated when switching to English', async ({ page }) => {
    await page.goto('/?page=home');
    await switchLanguageAndAssert(page, 'en');
  });

  test('news box title is translated when switching to Italian', async ({ page }) => {
    await page.goto('/?page=home');
    await switchLanguageAndAssert(page, 'it');
  });

  test('news box title is translated when switching to Spanish', async ({ page }) => {
    await page.goto('/?page=home');
    await switchLanguageAndAssert(page, 'es');
  });

  test('all four languages in sequence in a single session', async ({ page }) => {
    // The application rejects the same action id twice within
    // DOUBLE_SUBMIT_CHECK_SECONDS (3 s, compared on whole-second timestamps)
    // with a "double submit" error, so consecutive switches must be spaced out.
    const doubleSubmitWaitMs = 3_100;

    await page.goto('/?page=home');

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
