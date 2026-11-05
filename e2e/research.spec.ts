import { expect, test } from '@playwright/test';
import { signIn } from './support/auth';

test('an approved researcher reaches the datasets and the API documentation', async ({ page }) => {
    await signIn(page, 'researcher@courtwatch.test', 'research');

    await expect(page.locator('[data-research-status="approved"]')).toBeVisible();

    await page.goto('/research/datasets');
    await expect(page.getByRole('button', { name: /Shkarko|Download/ })).toHaveCount(3);

    await page.goto('/docs/api');
    await expect(page).toHaveTitle(/CourtWatch Researcher API/);
});

test('a researcher waiting for approval cannot download data', async ({ page }) => {
    await signIn(page, 'researcher.pending@courtwatch.test', 'research');

    await expect(page.locator('[data-research-status="pending"]')).toBeVisible();
    expect((await page.goto('/research/datasets'))?.status()).toBe(403);
});
