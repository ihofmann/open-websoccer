import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright configuration for the OpenWebSoccer-Sim E2E suite.
 *
 * The Docker stack (e2e/docker-compose.e2e.yml) is started separately by the
 * run scripts (run-e2e.ps1 / run-e2e.sh), which is why no `webServer` block is
 * defined here. The tests simply talk to the running container on port 8081.
 */
export default defineConfig({
  testDir: './tests',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  timeout: 60_000,
  expect: { timeout: 15_000 },

  reporter: [['list'], ['html', { open: 'never', outputFolder: 'playwright-report' }]],

  use: {
    // Port 8081 is the E2E web container (the dev stack uses 8080).
    baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:8081',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    actionTimeout: 20_000,
    navigationTimeout: 30_000,
  },

  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
