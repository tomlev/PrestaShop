<?php
/**
 * 2007-2017 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2017 PrestaShop SA
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShopBundle\Controller\Api\Localization;

use \PrestaShopBundle\Controller\Api\ApiController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class CurrencyController extends ApiController
{

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function listInstalledCurrenciesAction(Request $request)
    {
        $currencies = array();
        /*
                foreach ($this->getCurrentLocale()->getCurrencyManager()->getInstalledCurrencies() as $currency) {
                    $currencies[] = $this->exposeCurrency($currency);
                }
        */

        foreach (array('EUR', 'USD') as $code) {
            $currencies[$code] = $this->exposeCurrency(
                $this->getCurrentLocale()->getCurrencyManager()->getCurrencyByIsoCode($code)
            );
        }

        return $this->jsonResponse($currencies, $request);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function listAvailableCurrenciesAction(Request $request)
    {

        $currencies = array();

        /*
        foreach ($this->getCurrentLocale()->getCurrencyManager()->getAvailableCurrencies() as $currency) {
            $currencies[] = $this->exposeCurrency($currency);
        }
        */

        foreach (array('EUR', 'USD', 'CHF') as $code) {
            $currencies[$code] = $this->exposeCurrency(
                $this->getCurrentLocale()->getCurrencyManager()->getCurrencyByIsoCode($code)
            );
        }

        return $this->jsonResponse($currencies, $request);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getCurrencyAction(Request $request)
    {
        $id = $request->attributes->get('id');

        /*
        $currency =  $this->exposeCurrency(
                $this->getCurrentLocale()->getCurrencyManager()->getCurrencyById($id)
        );
        */
        $currency = $this->exposeCurrency(
            $this->getCurrentLocale()->getCurrencyManager()->getCurrencyByIsoCode('EUR')
        );

        return $this->jsonResponse($currency, $request);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function getCurrencyAvailableAction(Request $request)
    {
        $code = $request->attributes->get('code');
        $code = strtoupper($code);

        $currency = $this->exposeCurrency(
            $this->getCurrentLocale()->getCurrencyManager()->getCurrencyByIsoCode($code)
        );

        return $this->jsonResponse($currency, $request);
    }

    /**
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function editCurrencyAction(Request $request)
    {
        $symbol   = $request->request->getAlnum('symbol');
        $decimals = $request->request->getInt('code');

        return $this->jsonResponse($currency, $request);
    }

    protected function getCurrentLocale()
    {
        return $this->container->get('prestashop.cldr.locale.manager')->getLocaleByIsoCode(
            \Context::getContext()->language->locale
        );
    }

    protected function exposeCurrency(\PrestaShopBundle\Currency\Currency $currency)
    {
        $currencyData = array(
            //'id'            => $currency->getId(),
            'id'            => 2,
            'code'          => $currency->getIsoCode(),
            'numericCode'   => $currency->getNumericIsoCode(),
            'decimals'      => $currency->getDecimalDigits(),
            'exchangeRate'  => 1.0,//$currency->getExchangeRate(),
            'localizations' => array(),
        );

        /** @var \PrestaShopBundle\Localization\Locale $locale */
        /*
        foreach ($this->container->get('prestashop.cldr.locale.manager')->getInstalledLocales() as $locale) {
            $currencyData['localizations'][$locale->getLocaleCode()] = array(
                'name'            => $currency->getName(\Context::getContext()->language->iso_code),
                'currencyPattern' => $locale->getCurrencyPattern(),
            );
        }
        */

        foreach (array('en_US', 'fr_FR') as $code) {
            $locale                                                  = $this->container->get(
                'prestashop.cldr.locale.manager'
            )->getLocaleByIsoCode($code);
            $contextualCurrency                                      = $locale->getCurrencyManager()
                                                                              ->getCurrencyByIsoCode(
                                                                                  $currency->getIsoCode()
                                                                              );
            $currencyData['localizations'][$locale->getLocaleCode()] = array(
                'name'            => $contextualCurrency->getName('one'),
                'currencyPattern' => $locale->getCurrencyPattern(),
                'symbol'          => $contextualCurrency->getSymbol('default'),
            );
        }

        return $currencyData;
    }
}
