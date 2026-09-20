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
 *
 * The seeded manager is restored afterwards so the specs that run after this
 * one and log in as `user1` keep working (same convention as profile.spec.ts).
 */
test("removing the manager from a club is persisted", async ({ page }) => {
  await loginAsAdmin(page);

  // "Team 1" is managed by user1 in the seed data. Match the name cell by its
  // exact link text so "Team 10".."Team 19" are not picked up accidentally.
  const team1Row = () =>
    page.locator("table tbody tr", {
      has: page.getByRole("link", { name: "Team 1", exact: true }),
    });

  const managerSelect = () => page.locator("select#user_id");

  const openTeam1EditForm = async () => {
    await page.goto("/admin/index.php?site=manage&entity=club&show=edit&id=1");
    await expect(page.locator("legend")).toHaveText("Edit");
  };

  const saveClubForm = async () => {
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
  };

  try {
    await page.goto("/admin/index.php?site=manage&entity=club");
    await expect(team1Row()).toBeVisible();
    await expect(team1Row()).toContainText("user1");

    // Open the edit form and clear the manager selection.
    await openTeam1EditForm();
    await expect(managerSelect()).toHaveValue("1");
    await managerSelect().selectOption("");
    await saveClubForm();

    // The overview must no longer show user1 as the manager of Team 1.
    await expect(team1Row()).toBeVisible();
    await expect(team1Row()).not.toContainText("user1");
  } finally {
    // Restore the seed state (Team 1 managed by user1) even if the test
    // fails mid-way: about 30 specs that run after this one log in as user1
    // and expect to manage Team 1.
    await openTeam1EditForm();
    if ((await managerSelect().inputValue()) !== "1") {
      await managerSelect().selectOption("1");
      await saveClubForm();
      await expect(team1Row()).toContainText("user1");
    }
  }
});
