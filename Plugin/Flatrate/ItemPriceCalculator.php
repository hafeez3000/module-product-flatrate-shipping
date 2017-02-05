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

namespace Pmclain\ProductFlatrateShipping\Plugin\Flatrate;

use Magento\Checkout\Model\Session;

class ItemPriceCalculator
{
  protected $session;
  protected $quote;
  protected $customShippingPrices = [];
  protected $shippingPrices = [];
  protected $basePrice;
  protected $freeBoxes;
  protected $packageCount;
  protected $basePricePackageCount = 0;

  public function __construct(
    Session $session
  ) {
    $this->session = $session;
    $this->quote = $this->session->getQuote();
    $this->getCustomShippingPrices();
  }

  public function aroundGetShippingPricePerItem(
    $subject,
    $proceed,
    $request,
    $basePrice,
    $freeBoxes
  ) {
    $result = $proceed($request, $basePrice, $freeBoxes);
    if (!empty($this->customShippingPrices)) {
      $this->basePrice = $basePrice;
      $this->freeBoxes = $freeBoxes;
      $this->packageCount = $request->getPackageQty();

      if ($this->packageCount == $this->freeBoxes) {
        return 0;
      }

      $this->calculateBasePricePackageCount();
      $this->createShippingPricesArray();
      $this->applyFreeBoxes();

      return array_sum($this->shippingPrices);
    }
    return $result;
  }

  private function getCustomShippingPrices() {
    $items = $this->quote->getAllItems();
    foreach ($items as $item) {
      if ($customRate = $item->getProduct()->getCustomFlatRate()) {
        $this->customShippingPrices[] = $customRate;
      }
    }
  }

  private function calculateBasePricePackageCount() {
    if ($this->packageCount > $this->freeBoxes) {
      $adjustedCount = $this->packageCount - count($this->customShippingPrices);
    }
    $this->basePricePackageCount = ($adjustedCount > 0 ? $adjustedCount : 0);
  }

  private function createShippingPricesArray() {
    if ($this->basePricePackageCount) {
      $this->shippingPrices = array_fill(
        0, $this->basePricePackageCount, $this->basePrice
      );
    }

    foreach ($this->customShippingPrices as $key => $value) {
      $this->shippingPrices[] = $value;
    }
  }

  private function applyFreeBoxes() {
    arsort($this->shippingPrices);

    if ($this->freeBoxes) {
      $this->shippingPrices = array_slice(
        $this->shippingPrices, $this->freeBoxes
      );
    }
  }
}