import { test, expect } from "@playwright/test";
import { loginAsAdmin } from "./helpers";

/**
 * E2E: the registration date of a user created in the admin area is set.
 *
 * The users edit form marks `datum_anmeldung` as read-only. On "add" the
 * value must be generated (now), otherwise the column stays at its database
 * default 0, which renders as 01.01.1970.
 *
 * Regression test for #179.
 */
test("registration date is set when an admin creates a user", async ({
  page,
}) => {
  await loginAsAdmin(page);

  const nick = `regtest${Date.now()}`;

  await page.goto("/admin/index.php?site=manage&entity=users&show=add");
  await expect(page.locator("legend")).toHaveText("Add New");
  await page.fill("#nick", nick);
  await page.fill("#email", `${nick}@e2e.test`);
  await page.fill("#passwort", "E2ePass123");
  await page.check("#status");

  await page
    .locator("form")
    .filter({ has: page.locator('input[name="action"][value="save"]') })
    .locator("input[type=submit]")
    .click();

  const errorBox = page.locator(".alert-danger");
  if (await errorBox.count()) {
    throw new Error(`Creating the user failed: ${(await errorBox.innerText()).trim()}`);
  }
  await expect(page.locator(".alert-success")).toBeVisible();

  // Open the edit form of the created user and inspect the registration date.
  const row = page.locator("table tbody tr", { hasText: nick });
  await expect(row).toBeVisible();
  await row.locator('a[title="Edit"]').click();
  await expect(page.locator("legend")).toHaveText("Edit");

  const regDate = (await page.locator("#datum_anmeldung").textContent()) ?? "";
  // The unix epoch (dated 01.01.1970) must not be displayed.
  expect(regDate.trim()).not.toContain("1970");
  // It must be a real date, matching the current year.
  expect(regDate.trim()).toContain(String(new Date().getFullYear()));
});
