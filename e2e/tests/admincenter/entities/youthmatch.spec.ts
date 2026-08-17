import { test } from '@playwright/test';
import { entityFactories } from './catalog';
import { runEntityCrud } from './helpers';

test('create, edit and delete a youthmatch record', async ({ page }) => {
  await runEntityCrud(page, entityFactories.youthmatch(Date.now()));
});
