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

namespace PrestaShop\PrestaShop\Tests\Unit\Core\Cart;

use PrestaShop\PrestaShop\Tests\TestCase\IntegrationTestCase;
use PrestaShop\PrestaShop\Tests\Unit\ContextMocker;

/**
 * these tests aim to check the correct calculation of cart total when applying cart rules
 *
 * products are inserted as fixtures
 * products are inserted in cart from data providers
 * cart rules are inserted from data providers
 */
class CartCalculationTest extends IntegrationTestCase
{

    /**
     * @var ContextMocker
     */
    protected $contextMocker;

    /**
     * @var \CartCore
     */
    protected $cart;

    /**
     * @var \CartRule[]
     */
    protected $cartRulesInCart = [];

    /**
     * @var \CartRule[]
     */
    protected $cartRules = [];

    /**
     * @var \Product[]
     */
    protected $products = [];

    protected $productFixtures = [
        1 => ['priceTaxIncl' => 19.812, 'taxRate' => 20],
        2 => ['priceTaxIncl' => 32.388, 'taxRate' => 20],
        3 => ['priceTaxIncl' => 31.188, 'taxRate' => 20],
        4 => ['priceTaxIncl' => 35.567, 'taxRate' => 20, 'outOfStock' => true],
    ];

    protected $cartRuleFixtures = [
        1  => ['priority' => 1, 'percent' => 50, 'amount' => 0],
        2  => ['priority' => 2, 'percent' => 50, 'amount' => 0],
        3  => ['priority' => 3, 'percent' => 10, 'amount' => 0],
        4  => ['priority' => 4, 'percent' => 0, 'amount' => 5],
        5  => ['priority' => 5, 'percent' => 0, 'amount' => 500],
        6  => ['priority' => 6, 'percent' => 0, 'amount' => 10],
        7  => ['priority' => 7, 'percent' => 50, 'amount' => 0],
        8  => ['priority' => 8, 'percent' => 0, 'amount' => 5, 'productRestrictionId' => 2],
        9  => ['priority' => 8, 'percent' => 0, 'amount' => 500, 'productRestrictionId' => 2],
        10 => ['priority' => 8, 'percent' => 50, 'amount' => 0, 'productRestrictionId' => 2],
        11 => ['priority' => 8, 'percent' => 10, 'amount' => 0, 'productRestrictionId' => 2],
        12 => ['priority' => 8, 'percent' => 10, 'amount' => 0, 'productGiftId' => 3],
        13 => ['priority' => 8, 'percent' => 10, 'amount' => 0, 'productGiftId' => 4],
    ];

    public function setUp()
    {
        parent::setUp();
        $this->contextMocker = new ContextMocker();
        $this->contextMocker->mockContext();
        $this->cart              = new \Cart();
        $this->cart->id_lang     = (int) \Context::getContext()->language->id;
        $this->cart->id_currency = (int) \Context::getContext()->currency->id;
        $this->cart->add(); // required, else we cannot get the content when calculation total
        \Context::getContext()->cart = $this->cart;
        $this->resetCart();
        $this->insertProducts();
        $this->insertCartRules();
    }

    public function tearDown()
    {
        parent::tearDown();

        $this->resetCart();

        // delete cart rules from cart
        foreach ($this->cartRulesInCart as $cartRule) {
            $cartRule->delete();
        }

        // delete products
        foreach ($this->products as $product) {
            $product->delete();
        }

        // delete cart
        $this->cart->delete();

        // delete products
        foreach ($this->products as $product) {
            $product->delete();
        }

        // delete cart rules
        foreach ($this->cartRules as $cartRule) {
            $cartRule->delete();
        }

        $this->contextMocker->resetContext();
    }

    protected function compareCartTotal($expectedTotal)
    {
        $totalV1 = $this->cart->getOrderTotal();
        $this->assertEquals(\Tools::convertPrice($expectedTotal), $totalV1, 'V1 fail');
        $totalV2 = $this->cart->getOrderTotalV2();
        $this->assertEquals(\Tools::convertPrice($expectedTotal), $totalV2, 'V2 fail');
    }

    /**
     * @dataProvider cartWithoutCartRulesProvider
     */
    public function testCartWithoutCartRules($productDatas, $expectedTotal, $cartRuleDatas)
    {
        $this->addProductsToCart($productDatas);
        $this->addCartRulesToCart($cartRuleDatas);
        $this->compareCartTotal($expectedTotal);
    }

    /**
     * @dataProvider cartWithOneCartRulePercentProvider
     */
    public function testCartWithOneCartRulePercent($productDatas, $expectedTotal, $cartRuleDatas)
    {
        $this->addProductsToCart($productDatas);
        $this->addCartRulesToCart($cartRuleDatas);
        $this->compareCartTotal($expectedTotal);
    }

    /**
     * @dataProvider cartWithMultipleCartRulesPercentProvider
     */
    public function testCartWithMultipleCartRulesPercent($productDatas, $expectedTotal, $cartRuleDatas)
    {
        $this->addProductsToCart($productDatas);
        $this->addCartRulesToCart($cartRuleDatas);
        $this->compareCartTotal($expectedTotal);
    }

    /**
     * @dataProvider cartWithOneCartRuleAmountProvider
     */
    public function testCartWithOneCartRuleAmount($productDatas, $expectedTotal, $cartRuleDatas)
    {
        $this->addProductsToCart($productDatas);
        $this->addCartRulesToCart($cartRuleDatas);
        $this->compareCartTotal($expectedTotal);
    }

    /**
     * @dataProvider cartWithMultipleCartRulesAmountProvider
     */
    public function testCartWithMultipleCartRulesAmount($productDatas, $expectedTotal, $cartRuleDatas)
    {
        $this->addProductsToCart($productDatas);
        $this->addCartRulesToCart($cartRuleDatas);
        $this->compareCartTotal($expectedTotal);
    }

    /**
     * @dataProvider cartWithMultipleCartRulesMixedProvider
     */
    public function testCartWithMultipleCartRulesMixed($productDatas, $expectedTotal, $cartRuleDatas)
    {
        $this->addProductsToCart($productDatas);
        $this->addCartRulesToCart($cartRuleDatas);
        $this->compareCartTotal($expectedTotal);
    }

    /**
     * @dataProvider cartWithOneProductSpecificCartRulesAmountProvider
     */
    public function testCartWithOneProductSpecificCartRulesAmount($productDatas, $expectedTotal, $cartRuleDatas)
    {
        $this->addProductsToCart($productDatas);
        $this->addCartRulesToCart($cartRuleDatas);
        $this->compareCartTotal($expectedTotal);
    }

    /**
     * @dataProvider cartWithOneProductSpecificCartRulesPercentProvider
     */
    public function testCartWithOneProductSpecificCartRulesPercent($productDatas, $expectedTotal, $cartRuleDatas)
    {
        $this->addProductsToCart($productDatas);
        $this->addCartRulesToCart($cartRuleDatas);
        $this->compareCartTotal($expectedTotal);
    }

    /**
     * @dataProvider cartWithMultipleProductSpecificCartRulesPercentProvider
     */
    public function testCartWithMultipleProductSpecificCartRulesPercent($productDatas, $expectedTotal, $cartRuleDatas)
    {
        $this->addProductsToCart($productDatas);
        $this->addCartRulesToCart($cartRuleDatas);
        $this->compareCartTotal($expectedTotal);
    }

    /**
     * @dataProvider cartWithMultipleProductSpecificCartRulesMixedProvider
     */
    public function testCartWithMultipleProductSpecificCartRulesMixed($productDatas, $expectedTotal, $cartRuleDatas)
    {
        $this->addProductsToCart($productDatas);
        $this->addCartRulesToCart($cartRuleDatas);
        $this->compareCartTotal($expectedTotal);
    }

    /**
     * @dataProvider cartWithMultipleProductOutOfStockSpecificCartRulesMixedProvider
     */
    public function testCartWithMultipleProductOutOfStockSpecificCartRulesMixed(
        $productDatas,
        $expectedTotal,
        $cartRuleDatas
    ) {
        $this->addProductsToCart($productDatas);
        $this->addCartRulesToCart($cartRuleDatas);
        $this->compareCartTotal($expectedTotal);
    }

    /**
     * @dataProvider cartWithGiftProvider
     */
    public function testCartWithGift(
        $productDatas,
        $expectedTotal,
        $cartRuleDatas,
        $expectedProductCount
    ) {
        $this->addProductsToCart($productDatas);
        $this->addCartRulesToCart($cartRuleDatas);
        $this->compareCartTotal($expectedTotal);
        $this->assertEquals($expectedProductCount, \Cart::getNbProducts($this->cart->id));
    }

    /**
     * @dataProvider cartRuleValidityProvider
     */
    public function testCartRuleValidity(
        $productDatas,
        $cartRuleDatas,
        $shouldRulesBeApplied,
        $expectedProductCount,
        $expectedProductCountAfterRules
    ) {
        $this->addProductsToCart($productDatas);
        $this->assertEquals($expectedProductCount, \Cart::getNbProducts($this->cart->id));
        $result = true;
        foreach ($cartRuleDatas as $cartRuleId) {
            $cartRule                = $this->getCartRuleFromFixtureId($cartRuleId);
            $result                  = $result && $cartRule->checkValidity(\Context::getContext(), false, false);
            $this->cartRulesInCart[] = $cartRule;
            $this->cart->addCartRule($cartRule->id);
        }
        $this->assertEquals($shouldRulesBeApplied, $result);
        $this->assertEquals($expectedProductCountAfterRules, \Cart::getNbProducts($this->cart->id));
    }

    public function cartWithoutCartRulesProvider()
    {
        return [
            // WITHOUT CART RULES

            'empty cart'                             => [
                'products'      => [],
                'expectedTotal' => 0,
                'cartRules'     => [],
            ],
            'one product in cart, quantity 1'        => [
                'products'      => [
                    1 => 1,
                ],
                'expectedTotal' => 19.81,
                'cartRules'     => [],
            ],
            'one product in cart, quantity 3'        => [
                'products'      => [
                    1 => 3,
                ],
                'expectedTotal' => 59.44,
                'cartRules'     => [],
            ],
            '3 products in cart, several quantities' => [
                'products'      => [
                    2 => 2, // 64.776
                    1 => 3, // 59.43
                    3 => 1, // 31.188
                    // total without rule : 155.41
                ],
                'expectedTotal' => 155.41,
                'cartRules'     => [],
            ],
        ];
    }

    public function cartWithOneCartRulePercentProvider()
    {
        return [
            'empty cart'                                                     => [
                'products'      => [],
                'expectedTotal' => 0,
                'cartRules'     => [2],
            ],
            'one product in cart, quantity 1, one 50% global voucher'        => [
                'products'      => [
                    1 => 1,
                ],
                'expectedTotal' => 9.9,
                'cartRules'     => [2],
            ],
            'one product in cart, quantity 3, one 50% global voucher'        => [
                'products'      => [
                    1 => 3,
                ],
                'expectedTotal' => 29.72,
                'cartRules'     => [2],
            ],
            '3 products in cart, several quantities, one 50% global voucher' => [
                'products'      => [
                    2 => 2, // 64.776
                    1 => 3, // 59.43
                    3 => 1, // 31.188
                    // total without rule : 155.41
                ],
                'expectedTotal' => 77.7,
                'cartRules'     => [2],
            ],
        ];
    }

    public function cartWithMultipleCartRulesPercentProvider()
    {
        return [
            'empty cart'                                                  => [
                'products'      => [],
                'expectedTotal' => 0,
                'cartRules'     => [2, 3],
            ],
            'one product in cart, quantity 1, 2 % global vouchers'        => [
                'products'      => [
                    1 => 1,
                ],
                'expectedTotal' => 8.91,
                'cartRules'     => [2, 3],
            ],
            'one product in cart, quantity 3, 2 % global vouchers'        => [
                'products'      => [
                    1 => 3,
                ],
                'expectedTotal' => 26.75,
                'cartRules'     => [2, 3],
            ],
            '3 products in cart, several quantities, 2 % global vouchers' => [
                'products'      => [
                    2 => 2, // 64.776
                    1 => 3, // 59.43
                    3 => 1, // 31.188
                    // total without rule : 155.41
                ],
                'expectedTotal' => 69.93,
                'cartRules'     => [2, 3],
            ],
        ];
    }

    public function cartWithOneCartRuleAmountProvider()
    {
        return [
            'empty cart'                                                                                      => [
                'products'      => [],
                'expectedTotal' => 0,
                'cartRules'     => [4],
            ],
            'one product in cart, quantity 1, one 5€ global voucher'                                          => [
                'products'      => [
                    1 => 1,
                ],
                'expectedTotal' => 13.81,
                'cartRules'     => [4],
            ],
            'one product in cart, quantity 1, one 500€ global voucher'                                        => [
                'products'      => [
                    1 => 1,
                ],
                'expectedTotal' => 0, // voucher exceeds total
                'cartRules'     => [5],
            ],
            'one product in cart, quantity 3, one 5€ global voucher'                                          => [
                'products'      => [
                    1 => 3,
                ],
                'expectedTotal' => 53.44,
                'cartRules'     => [4],
            ],
            '3 products in cart, several quantities, one 5€ global voucher (reduced product at first place)'  => [
                'products'      => [
                    2 => 2, // 64.776
                    1 => 3, // 59.43
                    3 => 1, // 31.188
                    // total without rule : 155.41
                ],
                'expectedTotal' => 149.41,
                'cartRules'     => [4],
            ],
            '3 products in cart, several quantities, one 5€ global voucher (reduced product at second place)' => [
                'products'      => [
                    1 => 3, // 59.43
                    2 => 2, // 64.776
                    3 => 1, // 31.188
                    // total without rule : 155.41
                ],
                'expectedTotal' => 149.41,
                'cartRules'     => [4],
            ],
            '3 products in cart, several quantities, one 500€ global voucher'                                 => [
                'products'      => [
                    2 => 2, // 64.776
                    1 => 3, // 59.43
                    3 => 1, // 31.188
                    // total without rule : 155.41
                ],
                'expectedTotal' => 0, // voucher exceeds total
                'cartRules'     => [5],
            ],
        ];
    }

    public function cartWithMultipleCartRulesAmountProvider()
    {
        return [
            'empty cart'                                                                            => [
                'products'      => [],
                'expectedTotal' => 0,
                'cartRules'     => [4, 6],
            ],
            'one product in cart, quantity 1, one 5€ global voucher, one 10€ global voucher'        => [
                'products'      => [
                    1 => 1,
                ],
                'expectedTotal' => 1.81,
                'cartRules'     => [4, 6],
            ],
            'one product in cart, quantity 3, one 5€ global voucher, one 10€ global voucher'        => [
                'products'      => [
                    1 => 3,
                ],
                'expectedTotal' => 41.44,
                'cartRules'     => [4, 6],
            ],
            '3 products in cart, several quantities, one 5€ global voucher, one 10€ global voucher' => [
                'products'      => [
                    2 => 2, // 64.776
                    1 => 3, // 59.43
                    3 => 1, // 31.188
                    // total without rule : 155.41
                ],
                'expectedTotal' => 137.41,
                'cartRules'     => [4, 6],
            ],
        ];
    }

    public function cartWithMultipleCartRulesMixedProvider()
    {
        return [
            'one product in cart, quantity 1, one 50% global voucher, one 5€ global voucher'   => [
                'products'      => [
                    1 => 1,
                ],
                'expectedTotal' => 3.9,
                'cartRules'     => [2, 4],
            ],
            'one product in cart, quantity 1, one 50% global voucher, one 500€ global voucher' => [
                'products'      => [
                    1 => 1,
                ],
                'expectedTotal' => 0,
                'cartRules'     => [2, 5],
            ],
            'one product in cart, quantity 3, one 5€ global voucher, one 50% global voucher'   => [
                'products'      => [
                    1 => 3,
                ],
                'expectedTotal' => 6.9,
                'cartRules'     => [4, 7],
            ],
            'one product in cart, quantity 3, one 500€ global voucher, one 50% global voucher' => [
                'products'      => [
                    1 => 3,
                ],
                'expectedTotal' => 0,
                'cartRules'     => [5, 7],
            ],
        ];
    }

    public function cartWithOneProductSpecificCartRulesAmountProvider()
    {
        return [
            'empty cart'                                                                      => [
                'products'      => [],
                'expectedTotal' => 0,
                'cartRules'     => [8],
            ],
            'one product in cart, quantity 1, one specific 5€ voucher on product #2'          => [
                'products'      => [
                    1 => 1,
                ],
                'expectedTotal' => 19.81, // specific discount not applied on product #1
                'cartRules'     => [8],
            ],
            'one product in cart, quantity 3, one specific 5€ voucher on product #2'          => [
                'products'      => [
                    1 => 3,
                ],
                'expectedTotal' => 59.44, // specific discount not applied on product #1
                'cartRules'     => [8],
            ],
            '3 products in cart, several quantities, one specific 5€ voucher on product #2'   => [
                'products'      => [
                    2 => 2, // 64.776
                    1 => 3, // 59.43
                    3 => 1, // 31.188
                    // total without rule : 155.41
                ],
                'expectedTotal' => 149.41,
                'cartRules'     => [8],
            ],
            '3 products in cart, several quantities, one specific 500€ voucher on product #2' => [
                'products'      => [
                    2 => 2, // 64.776
                    1 => 3, // 59.43
                    3 => 1, // 31.188
                    // total without rule : 155.41
                ],
                'expectedTotal' => 85.96, // voucher exceeds #2 total
                'cartRules'     => [9],
            ],
        ];
    }

    public function cartWithOneProductSpecificCartRulesPercentProvider()
    {
        return [
            'empty cart'                                                                     => [
                'products'      => [],
                'expectedTotal' => 0,
                'cartRules'     => [10],
            ],
            'one product in cart, quantity 1, one specific 50% voucher on product #2'        => [
                'products'      => [
                    1 => 1,
                ],
                'expectedTotal' => 19.81, // specific discount not applied on product #1
                'cartRules'     => [10],
            ],
            'one product in cart, quantity 3, one specific 50% voucher on product #2'        => [
                'products'      => [
                    1 => 3,
                ],
                'expectedTotal' => 59.44, // specific discount not applied on product #1
                'cartRules'     => [10],
            ],
            'one product #2 in cart, quantity 3, one specific 50% voucher on product #2'     => [
                'products'      => [
                    2 => 3,
                ],
                'expectedTotal' => 48.58,
                'cartRules'     => [10],
            ],
            '3 products in cart, several quantities, one specific 50% voucher on product #2' => [
                'products'      => [
                    2 => 2, // 64.776
                    1 => 3, // 59.43
                    3 => 1, // 31.188
                    // total without rule : 155.41
                ],
                'expectedTotal' => 123.02,
                'cartRules'     => [10],
            ],
        ];
    }

    public function cartWithMultipleProductSpecificCartRulesPercentProvider()
    {
        return [
            'empty cart'                                                                     => [
                'products'      => [],
                'expectedTotal' => 0,
                'cartRules'     => [10, 11],
            ],
            'one product in cart, quantity 1, one specific 50% voucher on product #2'        => [
                'products'      => [
                    1 => 1,
                ],
                'expectedTotal' => 19.81, // specific discount not applied on product #1
                'cartRules'     => [10, 11],
            ],
            'one product in cart, quantity 3, one specific 50% voucher on product #2'        => [
                'products'      => [
                    1 => 3,
                ],
                'expectedTotal' => 59.44, // specific discount not applied on product #1
                'cartRules'     => [10, 11],
            ],
            'one product #2 in cart, quantity 3, one specific 50% voucher on product #2'     => [
                'products'      => [
                    2 => 3,
                ],
                'expectedTotal' => 43.72,
                'cartRules'     => [10, 11],
            ],
            '3 products in cart, several quantities, one specific 50% voucher on product #2' => [
                'products'      => [
                    2 => 2, // 64.776
                    1 => 3, // 59.43
                    3 => 1, // 31.188
                    // total without rule : 155.41
                ],
                'expectedTotal' => 119.78,
                'cartRules'     => [10, 11],
            ],
        ];
    }

    public function cartWithMultipleProductSpecificCartRulesMixedProvider()
    {
        return [
            'empty cart'                                                                     => [
                'products'      => [],
                'expectedTotal' => 0,
                'cartRules'     => [8, 10],
            ],
            'one product in cart, quantity 1, one specific 50% voucher on product #2'        => [
                'products'      => [
                    1 => 1,
                ],
                'expectedTotal' => 19.81, // specific discount not applied on product #1
                'cartRules'     => [8, 10],
            ],
            'one product in cart, quantity 3, one specific 50% voucher on product #2'        => [
                'products'      => [
                    1 => 3,
                ],
                'expectedTotal' => 59.44, // specific discount not applied on product #1
                'cartRules'     => [8, 10],
            ],
            'one product #2 in cart, quantity 3, one specific 50% voucher on product #2'     => [
                'products'      => [
                    2 => 3,
                ],
                'expectedTotal' => 46.08,
                'cartRules'     => [8, 10],
            ],
            '3 products in cart, several quantities, one specific 50% voucher on product #2' => [
                'products'      => [
                    2 => 2, // 64.776
                    1 => 3, // 59.43
                    3 => 1, // 31.188
                    // total without rule : 155.41
                ],
                'expectedTotal' => 120.52,
                'cartRules'     => [8, 10],
            ],
        ];
    }

    public function cartWithMultipleProductOutOfStockSpecificCartRulesMixedProvider()
    {
        return [

            'one product in cart, quantity 1, out of stock' => [
                'products'      => [
                    4 => 1,
                ],
                'expectedTotal' => 35.57,
                'cartRules'     => [],
            ],
            '2 products in cart, one is out of stock'       => [
                'products'      => [
                    1 => 3,
                    4 => 1,
                ],
                'expectedTotal' => 95.01,
                'cartRules'     => [],
            ],
        ];
    }

    public function cartWithGiftProvider()
    {
        return [
            '1 product in cart (out of stock), 1 cart rule give it as a gift, offering a gift (out of stock) and a global 10% discount' => [
                'products'             => [
                    4 => 1,
                ],
                'expectedTotal'        => 0,
                'cartRules'            => [13],
                'expectedProductCount' => 2,
            ],
            '2 products in cart, one cart rule offering a gift (out of stock) and a global 10% discount'                                => [
                'products'             => [
                    1 => 3,
                    4 => 1,
                ],
                'expectedTotal'        => 53.496,
                'cartRules'            => [13],
                'expectedProductCount' => 5,
            ],
            '2 products in cart, one cart rule offering a gift (in stock) and a global 10% discount'                                    => [
                'products'             => [
                    3 => 3,
                    4 => 1,
                ],
                'expectedTotal'        => 56.138,
                'cartRules'            => [12],
                'expectedProductCount' => 5,
            ],
        ];
    }

    public function cartRuleValidityProvider()
    {
        return [
            'No product in cart should give a not valid cart rule insertion'                                               => [
                'products'                       => [],
                'cartRules'                      => [1],
                'shouldRulesBeApplied'           => false,
                'expectedProductCount'           => 0,
                'expectedProductCountAfterRules' => 1,
            ],
            '1 product in cart, cart rules are inserted correctly'                                                         => [
                'products'                       => [1 => 1],
                'cartRules'                      => [1],
                'shouldRulesBeApplied'           => true,
                'expectedProductCount'           => 1,
                'expectedProductCountAfterRules' => 1,
            ],
            '1 product in cart, cart rule giving gift, and global cart rule should be inserted without error'              => [
                'products'                       => [4 => 1],
                'cartRules'                      => [12, 1],
                'shouldRulesBeApplied'           => true,
                'expectedProductCount'           => 1,
                'expectedProductCountAfterRules' => 2,
            ],
            // test PR #8361
            '1 product in cart, cart rule giving gift out of stock, and global cart rule should be inserted without error' => [
                'products'                       => [1 => 1],
                'cartRules'                      => [13, 1],
                'shouldRulesBeApplied'           => true,
                'expectedProductCount'           => 1,
                'expectedProductCountAfterRules' => 1,
            ],
        ];
    }

    protected function resetCart()
    {
        $productDatas = $this->cart->getProducts(true);
        foreach ($productDatas as $productData) {
            $this->cart->updateQty(0, $productData['id_product']);
        }
        $carRuleDatas = $this->cart->getCartRules();
        foreach ($carRuleDatas as $carRuleData) {
            $this->cart->removeCartRule($carRuleData['id_cart_rule']);
        }
    }

    protected function insertProducts()
    {
        foreach ($this->productFixtures as $k => $productFixture) {
            $product           = new \Product;
            $product->price    = round($productFixture['priceTaxIncl'] / (1 + $productFixture['taxRate'] / 100), 3);
            $product->tax_rate = $productFixture['taxRate'];
            $product->name     = 'product name';
            if (!empty($productFixture['outOfStock'])) {
                $product->out_of_stock = 0;
            }
            $product->add();
            if (!empty($productFixture['outOfStock'])) {
                \StockAvailable::setProductOutOfStock((int) $product->id, 0);
            }
            $this->products[$k] = $product;
        }
    }

    protected function addProductsToCart($productDatas)
    {
        foreach ($productDatas as $id => $quantity) {
            $product = $this->getProductFromFixtureId($id);
            if ($product !== null) {
                $this->cart->updateQty($quantity, $product->id);
            }
        }
    }

    /**
     * @param int $id fixture product id
     *
     * @return \Product|null
     */
    protected function getProductFromFixtureId($id)
    {
        if (isset($this->products[$id])) {
            return $this->products[$id];
        }

        return null;
    }

    /**
     * @param int $id fixture cart rule id
     *
     * @return \CartRule|null
     */
    protected function getCartRuleFromFixtureId($id)
    {
        if (isset($this->cartRules[$id])) {
            return $this->cartRules[$id];
        }

        return null;
    }

    protected function insertCartRules()
    {
        foreach ($this->cartRuleFixtures as $k => $cartRuleData) {
            $cartRule                    = new \CartRule;
            $cartRule->reduction_percent = $cartRuleData['percent'];
            $cartRule->reduction_amount  = $cartRuleData['amount'];
            $cartRule->name              = [\Configuration::get('PS_LANG_DEFAULT') => 'foo'];
            $cartRule->code              = 'bar';
            $cartRule->priority          = $cartRuleData['priority'];
            $cartRule->quantity          = 1000;
            $cartRule->quantity_per_user = 1000;
            if (!empty($cartRuleData['productRestrictionId'])) {
                $product = $this->getProductFromFixtureId($cartRuleData['productRestrictionId']);
                if ($product === null) {
                    // if product does not exist, skip this rule
                    continue;
                }
                $cartRule->product_restriction = true;
                $cartRule->reduction_product   = $product->id;
            }
            if (!empty($cartRuleData['productGiftId'])) {
                $product = $this->getProductFromFixtureId($cartRuleData['productGiftId']);
                if ($product === null) {
                    // if product does not exist, skip this rule
                    continue;
                }
                $cartRule->gift_product = $product->id;
            }
            $now = new \DateTime();
            // sub 1s to avoid bad comparisons with strictly greater than
            $now->sub(new \DateInterval('PT1S'));
            $cartRule->date_from = $now->format('Y-m-d H:i:s');
            $now->add(new \DateInterval('P1Y'));
            $cartRule->date_to = $now->format('Y-m-d H:i:s');
            $cartRule->active  = 1;
            $cartRule->add();
            $this->cartRules[$k] = $cartRule;
        }
    }

    protected function addCartRulesToCart(array $cartRuleIds)
    {
        foreach ($cartRuleIds as $cartRuleId) {
            $cartRule = $this->getCartRuleFromFixtureId($cartRuleId);
            if ($cartRule !== null) {
                $this->cartRulesInCart[] = $cartRule;
                $this->cart->addCartRule($cartRule->id);
            }
        }
    }

}
