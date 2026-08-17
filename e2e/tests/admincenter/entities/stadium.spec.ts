import { test } from '@playwright/test';
import { entityFactories } from './catalog';
import { runEntityCrud } from './helpers';

test('create, edit and delete a stadium record', async ({ page }) => {
  await runEntityCrud(page, entityFactories.stadium(Date.now()));
});
