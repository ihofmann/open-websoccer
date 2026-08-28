import { test, expect } from "@playwright/test";
import { loginAsAdmin } from "./entities/helpers";

/**
 * E2E: AdminCenter login is written to the admin log and old records can be
 * removed without deleting recent records.
 *
 * Seed data: AdminCenter login is `admin` / `admin`.
 */
test("admin login is listed and logs older than six months can be cleared", async ({
  page,
}) => {
  await loginAsAdmin(page);

  await page.goto("/admin/index.php?site=all_logging");
  await expect(page.locator("h1")).toHaveText("Admin Log");
  await expect(page.locator("table")).toContainText("admin");
  await expect(page.locator("table")).toContainText("oldadmin");

  await page
    .getByRole("button", { name: "Clear logs older than 6 months" })
    .click();
  await expect(page.locator(".alert-success")).toContainText(
    "Logs older than 6 months have been deleted.",
  );
  await expect(page.locator("table")).toContainText("admin");
  await expect(page.locator("table")).not.toContainText("oldadmin");
});
