import { defineConfig, devices } from '@playwright/test';

/**
 * End-to-end smoke tests (proposal B2). They run against a running app with the demo data:
 *
 *   docker compose up            # or: php artisan serve, with SEED_DEMO_DATA=true
 *   npm run e2e                  # E2E_BASE_URL=http://localhost:8000 by default
 *
 * Staff accounts must set up 2FA at their first sign-in: the tests do it through the real
 * set-up screen and keep the secrets in e2e/.auth/ (git-ignored) for the next runs.
 */
export default defineConfig({
    testDir: './e2e',
    // Generous: sign-ins may wait for the per-IP login limit (5 per minute).
    timeout: 240_000,
    expect: { timeout: 15_000 },
    fullyParallel: false,
    workers: 1,
    retries: 0,
    reporter: [['list']],
    use: {
        baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:8000',
        locale: 'sq-AL',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
