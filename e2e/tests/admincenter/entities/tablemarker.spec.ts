import { test } from '@playwright/test';
import { entityFactories } from './catalog';
import { runEntityCrud } from './helpers';

test('create, edit and delete a tablemarker record', async ({ page }) => {
  await runEntityCrud(page, entityFactories.tablemarker(Date.now()));
});
