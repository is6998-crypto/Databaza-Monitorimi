import { expect, test } from '@playwright/test';
import { signIn } from './support/auth';

test('staff set up 2FA and see the analytics dashboard', async ({ page }) => {
    await signIn(page, 'admin@courtwatch.test');

    await page.goto('/admin');
    await expect(page.locator('.fi-wi-stats-overview-stat').first()).toBeVisible();

    // The charts sit below the dashboard's own panels and load when they scroll into view (lazy widgets).
    await expect(async () => {
        await page.mouse.wheel(0, 700);
        await expect(page.locator('.fi-wi-chart canvas').first()).toBeVisible({ timeout: 1_000 });
    }).toPass({ timeout: 30_000 });
});

test('an editor opens the review queue', async ({ page }) => {
    await signIn(page, 'editor@courtwatch.test');

    await page.goto('/admin/review-queue');
    await expect(page.getByRole('heading', { name: /Radha e shqyrtimit|Review queue/ })).toBeVisible();
});
