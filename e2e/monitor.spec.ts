import { devices, expect, test } from '@playwright/test';
import { signIn } from './support/auth';

// Monitors record hearings on their phones.
test.use({ ...devices['Pixel 7'] });

test('a monitor opens the monitor app on a phone-sized screen', async ({ page }) => {
    await signIn(page, 'monitor@courtwatch.test');

    await page.goto('/m');
    await expect(page.getByRole('heading', { name: /Aplikacioni i monitoruesit|Monitor app/ })).toBeVisible();

    const width = page.viewportSize()?.width ?? 0;
    expect(width).toBeLessThan(500);
    // Nothing wider than the screen.
    expect(await page.evaluate(() => document.documentElement.scrollWidth)).toBeLessThanOrEqual(width + 1);
});
