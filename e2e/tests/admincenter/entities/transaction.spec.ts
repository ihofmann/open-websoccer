import { test } from '@playwright/test';
import { entityFactories } from './catalog';
import { runEntityCrud } from './helpers';

test('create, edit and delete a transaction record', async ({ page }) => {
  await runEntityCrud(page, entityFactories.transaction(Date.now()));
});
