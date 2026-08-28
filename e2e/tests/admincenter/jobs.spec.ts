import { test, expect } from "@playwright/test";
import { loginAsAdmin } from "./entities/helpers";

/**
 * E2E: AdminCenter Jobs page.
 *
 * Verifies that:
 *  - The jobs table shows ID, Name, Last Execution and Execute columns
 *    (no Interval, Status, or Start/Stop columns).
 *  - Every configured job can be executed via the "Execute now" button.
 *  - The cronjob explanation section is rendered below the table.
 *  - The executeJob.php web service accepts a single job ID and a
 *    comma-separated list of job IDs.
 *
 * Seed data: AdminCenter login is `admin` / `admin`.
 * The six seeded jobs are: addplyr, extransf, sim, usractv, stats, stadium.
 */

const jobs = [
  { id: "addplyr", name: "Add players without team to transfer market" },
  { id: "extransf", name: "Execute open transfers" },
  { id: "sim", name: "Simulate open matches" },
  { id: "usractv", name: "Compute and update user inactivity" },
  { id: "stats", name: "Compute and update league statistics" },
  { id: "stadium", name: "Accept stadium construction works and training camp bookings" },
];

test.describe("AdminCenter Jobs", () => {
  test("table shows ID, Name, Last Execution and Execute columns (no interval/status/start)", async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto("/admin/index.php?site=jobs");
    await expect(page.locator("h1")).toHaveText("Jobs");

    // Verify table headers: ID, Name, Last Execution, Execute
    const headers = page.locator("table thead th");
    await expect(headers).toHaveText(["ID", "Name", "Last Execution", "Execute"]);

    // No Start or Stop buttons
    await expect(page.getByRole("link", { name: "Start" })).toHaveCount(0);
    await expect(page.getByRole("link", { name: "Stop" })).toHaveCount(0);

    // Each job row shows its ID and an "Execute now" button
    for (const job of jobs) {
      const row = page.locator("table tbody tr", { hasText: job.name });
      await expect(row).toBeVisible();
      await expect(row).toContainText(job.id);
      await expect(row.getByRole("link", { name: "Execute now" })).toBeVisible();
    }
  });

  for (const job of jobs) {
    test(`admin can execute job: ${job.id}`, async ({ page }) => {
      await loginAsAdmin(page);
      await page.goto("/admin/index.php?site=jobs");

      const row = page.locator("table tbody tr", { hasText: job.name });
      await row.getByRole("link", { name: "Execute now" }).click();

      // The success alert must appear (execution happened in the same request,
      // before the table is re-rendered). Some server-side jobs can take
      // significantly longer than the default 15s expect timeout, so allow up
      // to 90s just for these job-execution assertions.
      await expect(page.locator(".alert-success")).toContainText(
        "Successfully executed.",
        { timeout: 90_000 },
      );
    });
  }

  test("cronjob explanation is shown below the jobs table", async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto("/admin/index.php?site=jobs");

    await expect(page.locator("h2")).toHaveText("Setting up CronJobs");
    await expect(page.locator("h3").first()).toHaveText("Recommended setup (two CronJobs)");
    await expect(page.locator("h3").last()).toHaveText("Separate CronJob per job");

    // The recommended section must contain the sim cron line and the all-others line
    const recommendedCode = page.locator("pre code").first();
    await expect(recommendedCode).toContainText("executeJob.php");
    await expect(recommendedCode).toContainText("sectoken=");
    await expect(recommendedCode).toContainText("jobid=sim");
    await expect(recommendedCode).toContainText("*/15");

    // The per-job section must contain a line for every job
    const perJobCode = page.locator("pre code").last();
    for (const job of jobs) {
      await expect(perJobCode).toContainText(`jobid=${job.id}`);
    }
  });

  test("executeJob.php web service executes a single job", async ({ request }) => {
    // The default security key is "-" (from module.xml default).
    const resp = await request.get(
      "/webservices/executeJob.php?sectoken=-&jobid=stats",
    );
    expect(resp.status()).toBe(200);
    const body = await resp.text();
    // No error messages should be in the response body.
    expect(body).not.toContain("not found");
    expect(body).not.toContain("disabled");
    expect(body).not.toContain("invalid");
  });

  test("executeJob.php web service executes a comma-separated list of job IDs", async ({ request }) => {
    const resp = await request.get(
      "/webservices/executeJob.php?sectoken=-&jobid=addplyr,extransf,usractv",
    );
    expect(resp.status()).toBe(200);
    const body = await resp.text();
    expect(body).not.toContain("not found");
    expect(body).not.toContain("disabled");
    expect(body).not.toContain("invalid");
  });
});
