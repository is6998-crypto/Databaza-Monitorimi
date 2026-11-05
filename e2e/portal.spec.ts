import { expect, test } from '@playwright/test';

test('the public portal works in Albanian and English', async ({ page }) => {
    const errors: string[] = [];
    page.on('pageerror', (error) => errors.push(error.message));

    await page.goto('/sq');
    await expect(page.locator('html')).toHaveAttribute('lang', 'sq');
    await expect(page.locator('[x-data^="echart("] svg, [x-data^="echart("] canvas').first()).toBeVisible();

    await page.locator('header a[hreflang="en"]').first().click();
    await expect(page).toHaveURL(/\/en\/?$/);
    await expect(page.locator('html')).toHaveAttribute('lang', 'en');

    expect(errors).toEqual([]);
});

test('anonymized case search, a case page and an open-data download', async ({ page, request }) => {
    await page.goto('/en/cases');
    await page.locator('a[href*="/en/cases/"]').first().click();

    await expect(page).toHaveURL(/\/en\/cases\/[0-9a-z]{26}$/i);
    await expect(page.getByText(/CW-[0-9A-Z]{10}/).first()).toBeVisible();

    const csv = await request.get('/en/open-data/cases-by-type.csv');
    expect(csv.status()).toBe(200);
    expect(csv.headers()['content-type']).toContain('text/csv');
});
