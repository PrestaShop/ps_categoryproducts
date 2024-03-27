import {
  // Import utils
  testContext,
  // Import BO pages
  boDashboardPage,
  boLoginPage,
  boModuleManagerPage,
  boModuleManagerSelectionPage,
  boModuleManagerUninstalledModulesPage,
  boRouterPage,
  // Import FO pages
  foClassicHomePage,
  foClassicProductPage,
  // Import data
  dataModules,
  dataProducts,
} from '@prestashop-core/ui-testing';

import { test, expect, Page, BrowserContext } from '@playwright/test';
import semver from 'semver';

const baseContext: string = 'modules_ps_categoryproducts_installation_disableEnableModule';
const psVersion = testContext.getPSVersion();

test.describe('Category products module - Disable/Enable module', async () => {
  let browserContext: BrowserContext;
  let page: Page;

  test.beforeAll(async ({ browser }) => {
    browserContext = await browser.newContext();
    page = await browserContext.newPage();
  });
  test.afterAll(async () => {
    await page.close();
  });

  test('should login in BO', async () => {
    await testContext.addContextItem(test.info(), 'testIdentifier', 'loginBO', baseContext);

    await boLoginPage.goTo(page, global.BO.URL);
    await boLoginPage.successLogin(page, global.BO.EMAIL, global.BO.PASSWD);

    const pageTitle = await boDashboardPage.getPageTitle(page);
    expect(pageTitle).toContain(boDashboardPage.pageTitle);
  });

  if (semver.lt(psVersion, '8.0.0')) {
    // We need to install the module
    test('should go to \'Modules > Module Manager\' page for installing module', async () => {
      await testContext.addContextItem(test.info(), 'testIdentifier', 'goToModuleManagerPageToInstall', baseContext);
  
      await boDashboardPage.goToSubMenu(
        page,
        boDashboardPage.modulesParentLink,
        boDashboardPage.moduleManagerLink,
      );
      await boModuleManagerPage.closeSfToolBar(page);

      const pageTitle = await boModuleManagerPage.getPageTitle(page);
      if (semver.gte(psVersion, '7.4.0')) {
        expect(pageTitle).toContain(boModuleManagerPage.pageTitle);
      } else {
        expect(pageTitle).toContain(boModuleManagerSelectionPage.pageTitle);
      }
    });

    // >= 1.7.5
    if (semver.gte(psVersion, '7.5.0')) {
      test('should install module (in tab Uninstalled Modules)', async () => {
        await testContext.addContextItem(test.info(), 'testIdentifier', 'searchModuleToInstallTabUninstalledModules', baseContext);
    
        await boModuleManagerUninstalledModulesPage.goToTabUninstalledModules(page);
    
        const isInstalled = await boModuleManagerUninstalledModulesPage.installModule(page, dataModules.psCategoryProducts.tag);
        expect(isInstalled).toBeTruthy();
      });
    }

    // < 1.7.5
    if (semver.lt(psVersion, '7.5.0')) {
      test('should install module (in tab Selection)', async () => {
        await testContext.addContextItem(test.info(), 'testIdentifier', 'searchModuleToInstallTabSelection', baseContext);
    
        await boModuleManagerSelectionPage.goToTabSelection(page);
    
        const isInstalled = await boModuleManagerSelectionPage.installModule(page, dataModules.psCategoryProducts.tag);
        expect(isInstalled).toBeTruthy();
      });
    }
  }

  test('should go to \'Modules > Module Manager\' page', async () => {
    await testContext.addContextItem(test.info(), 'testIdentifier', 'goToModuleManagerPageForEnable', baseContext);

    await boRouterPage.goToModuleManagerPage(page);

    const pageTitle = await boModuleManagerPage.getPageTitle(page);
    expect(pageTitle).toContain(boModuleManagerPage.pageTitle);
  });

  test(`should search the module ${dataModules.psCategoryProducts.name} (for disable)`, async () => {
    await testContext.addContextItem(test.info(), 'testIdentifier', 'searchModuleForDisable', baseContext);

    const isModuleVisible = await boModuleManagerPage.searchModule(page, dataModules.psCategoryProducts);
    expect(isModuleVisible).toEqual(true);
  });

  test('should disable and cancel the module', async () => {
    await testContext.addContextItem(test.info(), 'testIdentifier', 'disableCancelModule', baseContext);

    await boModuleManagerPage.setActionInModule(page, dataModules.psCategoryProducts, 'disable', true);

    const isModuleVisible = await boModuleManagerPage.isModuleVisible(page, dataModules.psCategoryProducts);
    expect(isModuleVisible).toEqual(true);
  });

  test('should disable the module', async () => {
    await testContext.addContextItem(test.info(), 'testIdentifier', 'disableModule', baseContext);

    const successMessage = await boModuleManagerPage.setActionInModule(page, dataModules.psCategoryProducts, 'disable');
    expect(successMessage).toEqual(boModuleManagerPage.disableModuleSuccessMessage(dataModules.psCategoryProducts.tag));
  });

  test('should go to the front office (after disabling the module)', async () => {
    await testContext.addContextItem(test.info(), 'testIdentifier', 'goToFOAfterDisable', baseContext);

    page = await boModuleManagerPage.viewMyShop(page);
    await foClassicHomePage.changeLanguage(page, 'en');

    const isHomePage = await foClassicHomePage.isHomePage(page);
    expect(isHomePage).toEqual(true);
  });

  test('should go to the product page (after disabling the module)', async () => {
    await testContext.addContextItem(test.info(), 'testIdentifier', 'goToProductPageAfterDisable', baseContext);

    await foClassicHomePage.goToProductPage(page, dataProducts.demo_6.id);

    const pageTitle = await foClassicProductPage.getPageTitle(page);
    expect(pageTitle.toUpperCase()).toContain(dataProducts.demo_6.name.toUpperCase());
  });

  test('should check if the "Category Products" block is not visible', async () => {
    await testContext.addContextItem(test.info(), 'testIdentifier', 'checkNotVisible', baseContext);

    const hasProductsBlock = await foClassicProductPage.hasProductsBlock(page, 'categoryproducts');
    expect(hasProductsBlock).toEqual(false);
  });

  test('should return to the back office', async () => {
    await testContext.addContextItem(test.info(), 'testIdentifier', 'returnBO', baseContext);

    page = await foClassicHomePage.closePage(browserContext, page, 0);

    const pageTitle = await boModuleManagerPage.getPageTitle(page);
    expect(pageTitle).toContain(boModuleManagerPage.pageTitle);
  });

  test(`should search the module ${dataModules.psCategoryProducts.name} (for enable)`, async () => {
    await testContext.addContextItem(test.info(), 'testIdentifier', 'searchModuleForEnable', baseContext);

    const isModuleVisible = await boModuleManagerPage.searchModule(page, dataModules.psCategoryProducts);
    expect(isModuleVisible).toEqual(true);
  });

  test('should enable the module', async () => {
    await testContext.addContextItem(test.info(), 'testIdentifier', 'enableModule', baseContext);

    const successMessage = await boModuleManagerPage.setActionInModule(page, dataModules.psCategoryProducts, 'enable');
    expect(successMessage).toEqual(boModuleManagerPage.enableModuleSuccessMessage(dataModules.psCategoryProducts.tag));
  });

  test('should go to the front office (after enabling the module)', async () => {
    await testContext.addContextItem(test.info(), 'testIdentifier', 'goToFOAfterEnable', baseContext);

    page = await boModuleManagerPage.viewMyShop(page);
    await foClassicHomePage.changeLanguage(page, 'en');

    const isHomePage = await foClassicHomePage.isHomePage(page);
    expect(isHomePage).toEqual(true);
  });

  test('should go to the product page (after enabling the module)', async () => {
    await testContext.addContextItem(test.info(), 'testIdentifier', 'goToProductPageAfterEnable', baseContext);

    await foClassicHomePage.goToProductPage(page, dataProducts.demo_6.id);

    const pageTitle = await foClassicHomePage.getPageTitle(page);
    expect(pageTitle.toUpperCase()).toContain(dataProducts.demo_6.name.toUpperCase());
  });

  test('should check if the "Category Products" block is visible', async () => {
    await testContext.addContextItem(test.info(), 'testIdentifier', 'checkVisible', baseContext);

    const hasProductsBlock = await foClassicProductPage.hasProductsBlock(page, 'categoryproducts');
    expect(hasProductsBlock).toEqual(true);
  });
});
