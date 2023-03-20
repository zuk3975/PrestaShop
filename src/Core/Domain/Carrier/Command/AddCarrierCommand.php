<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
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
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
declare(strict_types=1);

namespace PrestaShop\PrestaShop\Core\Domain\Carrier\Command;

use PrestaShop\PrestaShop\Core\Domain\Carrier\Exception\CarrierConstraintException;
use PrestaShop\PrestaShop\Core\Domain\Carrier\ValueObject\OutOfRangeBehavior;
use PrestaShop\PrestaShop\Core\Domain\Carrier\ValueObject\ShippingMethod;
use PrestaShop\PrestaShop\Core\Domain\Customer\Group\ValueObject\GroupId;
use PrestaShop\PrestaShop\Core\Domain\Exception\DomainConstraintException;
use PrestaShop\PrestaShop\Core\Domain\Product\ValueObject\Dimension;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopId;
use PrestaShop\PrestaShop\Core\Domain\TaxRulesGroup\ValueObject\TaxRulesGroupId;

class AddCarrierCommand
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array<int, string>
     */
    private $localizedTransitTime;

    /**
     * @var bool
     */
    private $active;

    /**
     * @var int|null
     */
    private $speedGrade;

    /**
     * @var string|null
     */
    private $trackingUrl;

    /**
     * @var bool|null
     */
    private $addHandlingCosts;

    /**
     * @var bool|null
     */
    private $freeShipping;

    /**
     * @var ShippingMethod|null
     */
    private $shippingMethod;

    /**
     * @var TaxRulesGroupId|null
     */
    private $taxRulesGroupId;

    /**
     * @var OutOfRangeBehavior|null
     */
    private $outOfRangeBehavior;

    /**
     * @var Dimension|null
     */
    private $width;

    /**
     * @var Dimension|null
     */
    private $height;

    /**
     * @var Dimension|null
     */
    private $depth;

    /**
     * @var Dimension|null
     */
    private $weight;

    /**
     * @var GroupId[]
     */
    private $customerGroupIds = [];

    /**
     * @var ShopId[]
     */
    private $shopIds = [];

    /**
     * @param string $name
     * @param array<int, string> $localizedTransitTime
     */
    public function __construct(
        string $name,
        array $localizedTransitTime,
        bool $active = true
    ) {
        $this->name = $name;
        $this->localizedTransitTime = $localizedTransitTime;
        $this->active = $active;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<int, string>
     */
    public function getLocalizedTransitTime(): array
    {
        return $this->localizedTransitTime;
    }

    /**
     * @param int $speedGrade
     *
     * @return $this
     */
    public function setSpeedGrade(int $speedGrade): AddCarrierCommand
    {
        $this->speedGrade = $speedGrade;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSpeedGrade(): ?int
    {
        return $this->speedGrade;
    }

    /**
     * @return string|null
     */
    public function getTrackingUrl(): ?string
    {
        return $this->trackingUrl;
    }

    /**
     * @param string $trackingUrl
     *
     * @return $this
     */
    public function setTrackingUrl(string $trackingUrl): AddCarrierCommand
    {
        $this->trackingUrl = $trackingUrl;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function isHandlingCostsAdded(): ?bool
    {
        return $this->addHandlingCosts;
    }

    /**
     * @param bool $addHandlingCosts
     *
     * @return $this
     */
    public function setAddHandlingCosts(bool $addHandlingCosts): AddCarrierCommand
    {
        $this->addHandlingCosts = $addHandlingCosts;

        return $this;
    }

    /**
     * @return bool
     */
    public function isFreeShipping(): bool
    {
        return $this->freeShipping;
    }

    /**
     * @param bool $freeShipping
     *
     * @return $this
     */
    public function setFreeShipping(bool $freeShipping): AddCarrierCommand
    {
        $this->freeShipping = $freeShipping;

        return $this;
    }

    /**
     * @return ShippingMethod
     */
    public function getShippingMethod(): ShippingMethod
    {
        return $this->shippingMethod;
    }

    /**
     * @param int $shippingMethod
     *
     * @return $this
     */
    public function setShippingMethod(int $shippingMethod): AddCarrierCommand
    {
        $this->shippingMethod = new ShippingMethod($shippingMethod);

        return $this;
    }

    /**
     * @return TaxRulesGroupId|null
     */
    public function getTaxRulesGroupId(): ?TaxRulesGroupId
    {
        return $this->taxRulesGroupId;
    }

    /**
     * @param int $taxRulesGroupId
     *
     * @return $this
     */
    public function setTaxRulesGroupId(int $taxRulesGroupId): AddCarrierCommand
    {
        $this->taxRulesGroupId = new TaxRulesGroupId($taxRulesGroupId);

        return $this;
    }

    /**
     * @return OutOfRangeBehavior|null
     */
    public function getOutOfRangeBehavior(): ?OutOfRangeBehavior
    {
        return $this->outOfRangeBehavior;
    }

    /**
     * @param int $outOfRangeBehavior
     *
     * @return $this
     */
    public function setOutOfRangeBehavior(int $outOfRangeBehavior): AddCarrierCommand
    {
        $this->outOfRangeBehavior = new OutOfRangeBehavior($outOfRangeBehavior);

        return $this;
    }

    /**
     * @return Dimension|null
     */
    public function getWidth(): ?Dimension
    {
        return $this->width;
    }

    /**
     * @param string $width
     *
     * @return self
     */
    public function setWidth(string $width): AddCarrierCommand
    {
        $this->setDimension($width, 'width');

        return $this;
    }

    /**
     * @return Dimension|null
     */
    public function getHeight(): ?Dimension
    {
        return $this->height;
    }

    /**
     * @param string $height
     *
     * @return $this
     */
    public function setHeight(string $height): AddCarrierCommand
    {
        $this->setDimension($height, 'height');

        return $this;
    }

    /**
     * @return Dimension|null
     */
    public function getDepth(): ?Dimension
    {
        return $this->depth;
    }

    /**
     * @param string $depth
     *
     * @return $this
     */
    public function setDepth(string $depth): AddCarrierCommand
    {
        $this->setDimension($depth, 'depth');

        return $this;
    }

    /**
     * @return Dimension|null
     */
    public function getWeight(): ?Dimension
    {
        return $this->weight;
    }

    /**
     * @param string $weight
     *
     * @return $this
     */
    public function setWeight(string $weight): AddCarrierCommand
    {
        $this->setDimension($weight, 'weight');

        return $this;
    }

    /**
     * @param int[] $customerGroupIds
     *
     * @return AddCarrierCommand
     */
    public function setCustomerGroupIds(array $customerGroupIds): AddCarrierCommand
    {
        foreach ($customerGroupIds as $groupId) {
            $this->customerGroupIds[] = new GroupId($groupId);
        }

        return $this;
    }

    /**
     * @return GroupId[]
     */
    public function getCustomerGroupIds(): array
    {
        return $this->customerGroupIds;
    }

    /**
     * @return ShopId[]
     */
    public function getShopIds(): array
    {
        return $this->shopIds;
    }

    /**
     * @param int[] $shopIds
     *
     * @return AddCarrierCommand
     */
    public function setShopIds(array $shopIds): AddCarrierCommand
    {
        foreach ($shopIds as $shopId) {
            $this->shopIds[] = $shopId;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param string $value
     * @param string $propertyName
     */
    private function setDimension(string $value, string $propertyName): void
    {
        $codeByDimension = [
            'width' => CarrierConstraintException::INVALID_WIDTH,
            'height' => CarrierConstraintException::INVALID_HEIGHT,
            'depth' => CarrierConstraintException::INVALID_DEPTH,
            'weight' => CarrierConstraintException::INVALID_WEIGHT,
        ];

        try {
            $this->{$propertyName} = new Dimension($value);
        } catch (DomainConstraintException $e) {
            throw new CarrierConstraintException(
                sprintf('Invalid %s.', $propertyName),
                $codeByDimension[$propertyName],
                $e
            );
        }
    }
}
