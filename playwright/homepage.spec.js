const { test, expect } = require('@playwright/test');

test.describe('homepage', () => {
  test('desktop screenshot', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto('/');
    await expect(page).toHaveScreenshot('homepage-desktop.png', { fullPage: true });
  });

  test('mobile screenshot', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/');
    await expect(page).toHaveScreenshot('homepage-mobile.png', { fullPage: true });
  });
});
