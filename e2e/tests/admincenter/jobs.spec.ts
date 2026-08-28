import { test, expect } from "@playwright/test";
import { loginAsAdmin } from "./entities/helpers";

/**
 * E2E: job definitions and runtime state are read from the database and a
 * configured job can be executed once from the AdminCenter.
 */
test("admin can inspect and execute a job", async ({ page }) => {
  await loginAsAdmin(page);

  await page.goto("/admin/index.php?site=jobs");
  await expect(page.locator("h1")).toHaveText("Jobs");
  const statisticsJob = page.locator("table tbody tr", {
    hasText: "Compute and update league statistics",
  });
  await expect(statisticsJob).toBeVisible();
  await expect(statisticsJob).toContainText("Not Running");

  await statisticsJob.getByRole("link", { name: "Execute once" }).click();
  await expect(page.locator(".alert-success")).toContainText(
    "Successfully executed.",
  );
});
