import { expect, test, type Page } from '@playwright/test';

/**
 * Shared AdminCenter helpers for E2E tests.
 *
 * Seed data: AdminCenter login is `admin` / `admin`.
 */

export type FieldFill =
  | { type: 'text' | 'number' | 'email' | 'password' | 'textarea' | 'date' | 'color'; value: string }
  | { type: 'select'; value: string }
  | { type: 'fk'; value: string }
  | { type: 'timestamp'; date: string; time: string }
  | { type: 'checkbox'; checked: boolean };

export type EntityCrudConfig = {
  /** Admin page / entity id (`?entity=`). */
  id: string;
  /** English heading rendered in `<h1>`. */
  heading: string;
  /** Form field whose value is shown in the overview and used to find the row. */
  uniqueField: string;
  /** Text that identifies the created row in the overview table. */
  createdLabel: string;
  /** Text that identifies the row after the edit. */
  editedLabel: string;
  /** GET filter column id when the unique value is searchable. */
  filterColumn?: string;
  fields: Record<string, FieldFill>;
  /** Extra fields written on edit (defaults to updating uniqueField). */
  editFields?: Record<string, FieldFill>;
};

export async function loginAsAdmin(page: Page, password = 'admin'): Promise<void> {
  await page.goto('/admin/');
  await expect(page).toHaveURL(/login\.php/);
  await page.fill('#inputUser', 'admin');
  await page.fill('#inputPassword', password);
  await page.click('button[type=submit]');
  await expect(page).toHaveURL(/\/admin\/index\.php$/);
  await expect(page.locator('.navbar-text')).toContainText('admin');
}

export async function logoutAdmin(page: Page): Promise<void> {
  await page.goto('/admin/logout.php');
  await expect(page).toHaveURL(/login\.php\?loggedout=1/);
}

/**
 * Submits an AdminCenter page form that posts `show=save` (imprint, terms, profile, …).
 */
export async function submitPageSaveForm(page: Page): Promise<void> {
  await page
    .locator('form')
    .filter({ has: page.locator('input[name="show"][value="save"]') })
    .locator('input[type=submit]')
    .click();

  const errorBox = page.locator('.alert-danger');
  if (await errorBox.count()) {
    throw new Error(`Saving failed: ${((await errorBox.innerText()).trim())}`);
  }

  await expect(page.locator('.alert-success')).toBeVisible();
}

async function fillForeignKey(page: Page, fieldId: string, value: string): Promise<void> {
  const select = page.locator(`select#${fieldId}`);
  if (await select.count()) {
    await select.selectOption(value);
    return;
  }

  const hidden = page.locator(`input[name="${fieldId}"]`).first();
  await hidden.evaluate((el, v) => {
    (el as HTMLInputElement).value = v;
  }, value);
}

export async function fillFields(page: Page, fields: Record<string, FieldFill>): Promise<void> {
  for (const [fieldId, fill] of Object.entries(fields)) {
    switch (fill.type) {
      case 'fk':
        await fillForeignKey(page, fieldId, fill.value);
        break;
      case 'timestamp':
        await page.fill(`input[name="${fieldId}_date"]`, fill.date);
        await page.fill(`input[name="${fieldId}_time"]`, fill.time);
        break;
      case 'checkbox':
        if (fill.checked) {
          await page.check(`#${fieldId}`);
        } else if (await page.locator(`#${fieldId}`).count()) {
          await page.uncheck(`#${fieldId}`);
        }
        break;
      case 'select':
        await page.selectOption(`#${fieldId}`, fill.value);
        break;
      case 'textarea':
        await page.fill(`#${fieldId}`, fill.value);
        break;
      default:
        await page.fill(`#${fieldId}`, fill.value);
        break;
    }
  }
}

async function submitAndAssertSaved(page: Page): Promise<void> {
  await page.locator('form').filter({ has: page.locator('input[name="action"][value="save"]') }).locator('input[type=submit]').click();

  const errorBox = page.locator('.alert-danger');
  if (await errorBox.count()) {
    throw new Error(`Saving the record failed: ${((await errorBox.innerText()).trim())}`);
  }

  await expect(page.locator('.alert-success')).toBeVisible();
}

async function findRow(page: Page, config: EntityCrudConfig, uniqueText: string) {
  if (config.filterColumn) {
    const params = new URLSearchParams({
      site: 'manage',
      entity: config.id,
      [config.filterColumn]: uniqueText,
    });
    await page.goto(`/admin/index.php?${params.toString()}`);
  }

  const row = page.locator('table tbody tr', { hasText: uniqueText });
  if (await row.count()) {
    return row.first();
  }

  const lastPage = page.locator('.pagination .page-item:not(.disabled) a.page-link').last();
  if (await lastPage.count()) {
    await lastPage.click();
  }

  return page.locator('table tbody tr', { hasText: uniqueText }).first();
}

async function openEditForm(page: Page, config: EntityCrudConfig, uniqueText: string): Promise<void> {
  const row = await findRow(page, config, uniqueText);
  await expect(row).toBeVisible();

  const editLink = row.locator('a[title="Edit"]');
  if (await editLink.count()) {
    await editLink.click();
    return;
  }

  const id = await row.locator('input[name="del_id[]"]').getAttribute('value');
  if (!id) {
    throw new Error(`Could not determine the id of the ${config.id} record '${uniqueText}'.`);
  }
  await page.goto(`/admin/index.php?site=manage&entity=${config.id}&show=edit&id=${id}`);
}

async function deleteRecord(page: Page, config: EntityCrudConfig, uniqueText: string): Promise<void> {
  const row = await findRow(page, config, uniqueText);
  await expect(row).toBeVisible();
  await row.locator('a.deleteLink').click();

  const confirmDialog = page.locator('#wsConfirmModal');
  await expect(confirmDialog).toBeVisible();
  await confirmDialog.locator('#wsConfirmYes').click();

  await expect(page.locator('.alert-success')).toBeVisible();
  await expect(page.locator('table tbody tr', { hasText: uniqueText })).toHaveCount(0);
}

/**
 * Signs in, creates a record, edits it and deletes it for the given entity.
 */
export async function runEntityCrud(page: Page, config: EntityCrudConfig): Promise<void> {
  test.setTimeout(90_000);

  await loginAsAdmin(page);

  await page.goto(`/admin/index.php?site=manage&entity=${config.id}&show=add`);
  await expect(page.locator('h1')).toHaveText(config.heading);
  await expect(page.locator('legend')).toHaveText('Add New');

  await fillFields(page, config.fields);
  await submitAndAssertSaved(page);

  await openEditForm(page, config, config.createdLabel);
  await expect(page.locator('legend')).toHaveText('Edit');

  const editFields = config.editFields ?? {
    [config.uniqueField]: { type: 'text', value: config.editedLabel } satisfies FieldFill,
  };
  await fillFields(page, editFields);
  await submitAndAssertSaved(page);

  await deleteRecord(page, config, config.editedLabel);
}
