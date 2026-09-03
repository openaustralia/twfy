const { test, expect } = require('@playwright/test');
const percySnapshot = require('@percy/playwright');

test.describe('homepage', () => {
  test('desktop screenshot', async ({ page }, testInfo) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto('/');
    await expect(page.locator('#hero-search')).toBeVisible();
    await page.screenshot({ path: testInfo.outputPath('homepage-desktop.png'), fullPage: true });
    // No-ops when not run under `percy exec` (eg a plain `npx playwright test`),
    // so this is safe to leave in for every run, not just CI - see
    // package.json's test:percy script.
    await percySnapshot(page, 'Homepage - Desktop');
    if (!process.env.CI) {
      await expect(page).toHaveScreenshot('homepage-desktop.png', { fullPage: true });
    }
  });

  test('mobile screenshot', async ({ page }, testInfo) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/');
    await expect(page.locator('#hero-search')).toBeVisible();
    await page.screenshot({ path: testInfo.outputPath('homepage-mobile.png'), fullPage: true });
    await percySnapshot(page, 'Homepage - Mobile');
    if (!process.env.CI) {
      await expect(page).toHaveScreenshot('homepage-mobile.png', { fullPage: true });
    }
  });
});
