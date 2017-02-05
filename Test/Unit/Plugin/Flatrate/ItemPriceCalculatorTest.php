<?php
/**
 * Pmclain_ProductFlatrateShipping extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GPL v3 License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/gpl.txt
 *
 * @category       Pmclain
 * @package        ProductFlatrateShipping
 * @copyright      Copyright (c) 2017
 * @license        https://www.gnu.org/licenses/gpl.txt GPL v3 License
 */

namespace Pmclain\ProductFlatrateShipping\Test\Unit\Plugin\Flatrate;

use Pmclain\ProductFlatrateShipping\Plugin\Flatrate\ItemPriceCalculator;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Checkout\Model\Session;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Quote\Model\ResourceModel\Quote\Item\Collection as QuoteItemCollection;
use Magento\Catalog\Model\Product;

class ItemPriceCalculatorTest extends \PHPUnit_Framework_TestCase
{
  /** @var ObjectManager */
  protected $objectManager;

  /** @var ItemPriceCalculator */
  protected $itemPriceCalculatorPlugin;

  /** @var Session|MockObject */
  protected $sessionMock;

  /** @var \Magento\OfflineShipping\Model\Carrier\Flatrate\ItemPriceCalculator|MockObject */
  protected $itemPriceCalculatorMock;

  /** @var MockObject */
  protected $proceedMock;

  /** @var RateRequest|MockObject */
  protected $rateRequestMock;

  /** @var Quote|MockObject */
  protected $quoteMock;

  /** @var Item|MockObject */
  protected $quoteItemMock;

  /** @var Product|MockObject */
  protected $productMock;

  /** @var QuoteItemCollection|MockObject */
  protected $quoteItemCollectionMock;

  protected function setUp() {
    $this->objectManager = new ObjectManager($this);

    $this->sessionMock = $this->getMockBuilder(Session::class)
      ->disableOriginalConstructor()
      ->setMethods(['getQuote'])
      ->getMock();

    $this->quoteMock = $this->getMockBuilder(Quote::class)
      ->disableOriginalConstructor()
      ->setMethods(['getAllItems'])
      ->getMock();

    $this->quoteItemMock = $this->getMockBuilder(Item::class)
      ->disableOriginalConstructor()
      ->setMethods(['getProduct'])
      ->getMock();

    $this->quoteItemCollectionMock = $this->objectManager->getCollectionMock(
      QuoteItemCollection::class, [$this->quoteItemMock]
    );

    $this->productMock = $this->getMockBuilder(Product::class)
      ->disableOriginalConstructor()
      ->setMethods(['getCustomFlatRate'])
      ->getMock();

    $this->sessionMock->expects($this->once())
      ->method('getQuote')
      ->willReturn($this->quoteMock);

    $this->quoteMock->expects($this->once())
      ->method('getAllItems')
      ->willReturn($this->quoteItemCollectionMock);

    $this->quoteItemMock->expects($this->any())
      ->method('getProduct')
      ->willReturn($this->productMock);

    $this->itemPriceCalculatorMock = $this->getMockBuilder(
      \Magento\OfflineShipping\Model\Carrier\Flatrate\ItemPriceCalculator::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->rateRequestMock = $this->getMockBuilder(RateRequest::class)
      ->setMethods(['getPackageQty'])
      ->getMock();
  }

  /**
   * @param string $basePrice
   * @param int $freeBoxes
   * @param int $packageCount
   * @param array $customPrices
   * @param float $expectedResult
   * @dataProvider aroundGetShippingPricePerItemDataProvider
   */
  public function testAroundGetShippingPricePerItem(
    $basePrice,
    $freeBoxes,
    $packageCount,
    $customPrices,
    $expectedResult
  ) {
    $this->itemPriceCalculatorPlugin = $this->objectManager->getObject(
      ItemPriceCalculator::class,
      [
        'session' => $this->sessionMock,
        'customShippingPrices' => $customPrices
      ]
    );

    $this->proceedMock = $this->getMock(\stdClass::class, ['__invoke']);
    $this->proceedMock->expects($this->any())
      ->method('__invoke')
      ->with($this->rateRequestMock, $basePrice, $freeBoxes)
      ->willReturn($packageCount * $basePrice - $freeBoxes * $basePrice);

    $this->rateRequestMock->expects($this->any())
      ->method('getPackageQty')
      ->willReturn($packageCount);

    $result = $this->itemPriceCalculatorPlugin->aroundGetShippingPricePerItem(
      $this->itemPriceCalculatorMock,
      $this->proceedMock,
      $this->rateRequestMock,
      $basePrice,
      $freeBoxes
    );

    $this->assertEquals($expectedResult, $result);
  }

  public function aroundGetShippingPricePerItemDataProvider() {
    return [
      // $basePrice, $freeBoxes, $packageCount, $customPrices, $expected
      ['5.00', 0, 1, [3.99], 3.99],
      ['5.00', 0, 2, [3.99], 8.99],
      ['5.00', 1, 2, [3.99], 3.99],
      ['5.00', 2, 2, [3.99], 0],
      ['5.00', 2, 3, [8.99], 5],
      ['5.00', 0, 3, [2,2], 9],
      ['5.00', 1, 3, [2,2], 4]
    ];
  }
}