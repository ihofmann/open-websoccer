import { test } from '@playwright/test';
import { entityFactories } from './catalog';
import { runEntityCrud } from './helpers';

test('create, edit and delete a randomevent record', async ({ page }) => {
  await runEntityCrud(page, entityFactories.randomevent(Date.now()));
});
