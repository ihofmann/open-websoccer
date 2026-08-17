import { test } from '@playwright/test';
import { entityFactories } from './catalog';
import { runEntityCrud } from './helpers';

test('create, edit and delete a youthscout record', async ({ page }) => {
  await runEntityCrud(page, entityFactories.youthscout(Date.now()));
});
