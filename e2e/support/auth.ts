import { expect, type Page } from '@playwright/test';
import { createHmac } from 'node:crypto';
import { existsSync, mkdirSync, readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

export const DEMO_PASSWORD = process.env.E2E_PASSWORD ?? 'password';

const secretsFile = join(dirname(fileURLToPath(import.meta.url)), '..', '.auth', 'totp-secrets.json');

function readSecrets(): Record<string, string> {
    return existsSync(secretsFile) ? JSON.parse(readFileSync(secretsFile, 'utf8')) : {};
}

function saveSecret(email: string, secret: string): void {
    mkdirSync(dirname(secretsFile), { recursive: true });
    writeFileSync(secretsFile, JSON.stringify({ ...readSecrets(), [email]: secret }, null, 2));
}

function base32(secret: string): Buffer {
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    let bits = '';
    for (const char of secret.toUpperCase().replace(/[\s=]+/g, '')) {
        const value = alphabet.indexOf(char);
        if (value >= 0) bits += value.toString(2).padStart(5, '0');
    }
    const bytes: number[] = [];
    for (let i = 0; i + 8 <= bits.length; i += 8) bytes.push(parseInt(bits.slice(i, i + 8), 2));
    return Buffer.from(bytes);
}

/** RFC 6238 time-based one-time password (30 s, 6 digits, SHA-1), as authenticator apps compute it. */
export function totp(secret: string, at = Date.now()): string {
    const counter = Buffer.alloc(8);
    counter.writeBigUInt64BE(BigInt(Math.floor(at / 30_000)));
    const hmac = createHmac('sha1', base32(secret)).update(counter).digest();
    const offset = hmac[hmac.length - 1] & 0xf;
    return ((hmac.readUInt32BE(offset) & 0x7fffffff) % 1_000_000).toString().padStart(6, '0');
}

/** Waits for the next 30-second window, so a code is never reused (replay protection). */
async function freshCode(secret: string, used: Set<string>): Promise<string> {
    let code = totp(secret);
    while (used.has(code)) {
        await new Promise((resolve) => setTimeout(resolve, 1_000));
        code = totp(secret);
    }
    used.add(code);
    return code;
}

const usedCodes = new Map<string, Set<string>>();

/** Times of the sign-ins of this run: the app allows 5 attempts per minute per IP address. */
const signIns: number[] = [];

async function respectLoginLimit(page: Page): Promise<void> {
    // A sign-in with 2FA uses two attempts: allow two sign-ins per rolling minute.
    const recent = signIns.filter((time) => Date.now() - time < 61_000);
    if (recent.length >= 2) {
        await page.waitForTimeout(61_000 - (Date.now() - Math.min(...recent)));
    }
    signIns.push(Date.now());
}

/**
 * Signs in at /admin (staff) or /research (researchers): through the mandatory 2FA set-up on
 * the first run, then with a code computed from the stored secret.
 */
export async function signIn(page: Page, email: string, area: 'admin' | 'research' = 'admin'): Promise<void> {
    const used = usedCodes.get(email) ?? new Set<string>();
    usedCodes.set(email, used);
    await respectLoginLimit(page);

    await page.goto(`/${area}/login`);
    await page.locator('input[type=email]').fill(email);
    await page.locator('input[type=password]').fill(DEMO_PASSWORD);

    const challenge = page.getByText(/6-digit code|6 shifra|authenticator app|aplikacionin e autentifikimit/i).first();
    const throttled = page.locator('.fi-no-notification').filter({ hasText: /Too many|Shumë|Try again in|Provoni përsëri/i }).first();
    const leftLogin = () => page.waitForURL((url) => !url.pathname.endsWith('/login'), { timeout: 20_000 });

    // When an earlier run used up the per-IP limit, wait for it to pass and submit again.
    for (let attempt = 0; attempt < 3; attempt++) {
        await page.locator('button[type=submit]').click();
        await Promise.race([leftLogin(), challenge.waitFor({ timeout: 20_000 }), throttled.waitFor({ timeout: 20_000 })]).catch(() => undefined);

        if (!(await throttled.isVisible().catch(() => false))) {
            break;
        }

        await page.waitForTimeout(61_000);
    }

    if (page.url().includes('multi-factor-authentication/set-up')) {
        await page.locator('main button').filter({ hasText: /Set up|Konfiguro/ }).first().click();
        const modal = page.locator('.fi-modal-window:visible').first();
        await modal.waitFor();

        // Step 1: confirm the password.
        const password = modal.locator('input[type=password]:visible');
        if (await password.count()) {
            await password.fill(DEMO_PASSWORD);
            await modal.locator('button:visible').last().click();
        }

        // Step 2: the secret ("enter this code manually") and the 6-digit code.
        const manual = modal.getByText(/\b[A-Z2-7]{16,64}\b/).first();
        await expect(manual).toBeVisible();
        const secret = ((await manual.textContent()) ?? '').match(/\b([A-Z2-7]{16,64})\b/)?.[1];
        expect(secret, 'the set-up screen shows the secret for manual entry').toBeTruthy();
        saveSecret(email, secret!);

        await modal.locator('input:visible:not([type=password])').first().click();
        await page.keyboard.type(await freshCode(secret!, used), { delay: 30 });
        await modal.locator('button:visible').last().click();

        // Step 3: the recovery codes (shown once), then enable 2FA. The click is retried
        // because it can land while the previous step is still loading.
        await expect(modal.getByText(/recovery codes|kodet e rikuperimit/i).first()).toBeVisible();
        const enable = modal.getByRole('button', { name: /Enable authenticator app|Aktivizo/i });
        const proceed = page.locator('main').getByRole('button', { name: /^(Continue|Vazhdo)$/ });

        for (let attempt = 0; attempt < 3 && !(await proceed.isVisible()); attempt++) {
            await expect(enable).toBeEnabled();
            await enable.click();
            await proceed.waitFor({ timeout: 10_000 }).catch(() => undefined);
        }

        // The set-up page now says "Enabled": continue to the panel.
        await proceed.click();
        await page.waitForURL((url) => !url.pathname.includes('set-up'), { timeout: 20_000 });

        return;
    }

    if (page.url().endsWith('/login') && (await challenge.count())) {
        const secret = readSecrets()[email];
        expect(secret, `${email} already has 2FA but its secret is not in e2e/.auth: reset its 2FA in Administration → Users`).toBeTruthy();

        for (let attempt = 0; attempt < 3 && page.url().endsWith('/login'); attempt++) {
            const boxes = page.locator('main input:visible');
            for (let i = 0; i < (await boxes.count()); i++) {
                await boxes.nth(i).fill('');
            }
            await boxes.first().click();
            // The code is submitted automatically after the sixth digit.
            await page.keyboard.type(await freshCode(secret!, used), { delay: 30 });
            await Promise.race([leftLogin(), throttled.waitFor({ timeout: 20_000 })]).catch(() => undefined);

            if (await throttled.isVisible().catch(() => false)) {
                await page.waitForTimeout(61_000);
            }
        }
    }

    await leftLogin();
}
