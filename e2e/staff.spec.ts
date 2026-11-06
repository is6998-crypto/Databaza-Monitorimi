import { expect, test } from '@playwright/test';
import { signIn } from './support/auth';

test('staff set up 2FA and see the analytics dashboard', async ({ page }) => {
    await signIn(page, 'admin@courtwatch.test');

    await page.goto('/admin');
    await expect(page.locator('.fi-wi-stats-overview-stat').first()).toBeVisible();
    await expect(page.locator('.fi-wi-chart canvas').first()).toBeVisible();
});

test('an editor opens the review queue', async ({ page }) => {
    await signIn(page, 'editor@courtwatch.test');

    await page.goto('/admin/review-queue');
    await expect(page.getByRole('heading', { name: /Radha e shqyrtimit|Review queue/ })).toBeVisible();
});
