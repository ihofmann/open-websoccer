import { test } from '@playwright/test';
import { entityFactories } from './catalog';
import { runEntityCrud } from './helpers';

test('create, edit and delete a admin record', async ({ page }) => {
  await runEntityCrud(page, entityFactories.admin(Date.now()));
});
