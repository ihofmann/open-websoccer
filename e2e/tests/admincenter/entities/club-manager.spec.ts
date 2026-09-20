import { test, expect } from "@playwright/test";
import { loginAsAdmin } from "./helpers";

/**
 * E2E: an admin removes the manager from a club; the change is persisted.
 *
 * Seed data: "Team 1" is managed by `user1`. The club edit form renders the
 * manager as a foreign-key select whose first option means "no manager".
 *
 * Regression test for #178: previously the empty value was silently omitted
 * from the UPDATE, so the previous manager remained assigned after saving.
 */
test("removing the manager from a club is persisted", async ({ page }) => {
  await loginAsAdmin(page);

  // "Team 1" is managed by user1 in the seed data. Match the name cell by its
  // exact link text so "Team 10".."Team 19" are not picked up accidentally.
  const team1Row = () =>
    page.locator("table tbody tr", {
      has: page.getByRole("link", { name: "Team 1", exact: true }),
    });

  await page.goto("/admin/index.php?site=manage&entity=club");
  await expect(team1Row()).toBeVisible();
  await expect(team1Row()).toContainText("user1");

  // Open the edit form and clear the manager selection.
  await team1Row().getByRole("link", { name: "Team 1", exact: true }).click();
  await expect(page.locator("legend")).toHaveText("Edit");

  const managerSelect = page.locator("select#user_id");
  await expect(managerSelect).toHaveValue("1");
  await managerSelect.selectOption("");

  await page
    .locator("form")
    .filter({ has: page.locator('input[name="action"][value="save"]') })
    .locator("input[type=submit]")
    .click();

  const errorBox = page.locator(".alert-danger");
  if (await errorBox.count()) {
    throw new Error(`Saving the club failed: ${(await errorBox.innerText()).trim()}`);
  }
  await expect(page.locator(".alert-success")).toBeVisible();

  // The overview must no longer show user1 as the manager of Team 1.
  await expect(team1Row()).toBeVisible();
  await expect(team1Row()).not.toContainText("user1");
});
