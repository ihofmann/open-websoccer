import { test } from '@playwright/test';
import { entityFactories } from './catalog';
import { runEntityCrud } from './helpers';

test('create, edit and delete a stadiumconstruction record', async ({ page }) => {
  await runEntityCrud(page, entityFactories.stadiumconstruction(Date.now()));
});
