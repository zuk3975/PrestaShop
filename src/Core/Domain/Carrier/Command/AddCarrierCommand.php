<?php
/**
 * 2007-2019 PrestaShop and Contributors
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
 * needs please refer to https://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2019 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\PrestaShop\Core\Domain\Carrier\Command;

use PrestaShop\PrestaShop\Core\Domain\Carrier\Exception\CarrierConstraintException;
use PrestaShop\PrestaShop\Core\Domain\Carrier\ValueObject\OutOfRangeBehavior;
use PrestaShop\PrestaShop\Core\Domain\Carrier\ValueObject\Billing;
use PrestaShop\PrestaShop\Core\Domain\Carrier\ValueObject\SpeedGrade;
use PrestaShop\PrestaShop\Core\Domain\Carrier\ValueObject\TrackingUrl;

/**
 * Adds new carrier
 */
class AddCarrierCommand extends AbstractAddCarrierCommand
{
    /**
     * This class should be initialized using static factories
     */
    private function __construct()
    {
    }

    /**
     * Adds carrier with shipping costs
     *
     * @param string $name
     * @param string[] $localizedShippingDelays
     * @param int $speedGrade
     * @param string $trackingUrl
     * @param bool $shippingCostIncluded
     * @param int $billing
     * @param int $taxRulesGroupId
     * @param int $outOfRangeBehavior
     * @param array $shippingRanges
     * @param int $maxPackageWidth
     * @param int $maxPackageHeight
     * @param int $maxPackageDepth
     * @param float $maxPackageWeight
     * @param int[] $associatedGroupIds
     * @param int[] $associatedShopIds
     * @param bool $enabled
     *
     * @return AddCarrierCommand
     *
     * @throws CarrierConstraintException
     */
    public static function withPricedShipping(
        string $name,
        array $localizedShippingDelays,
        int $speedGrade,
        string $trackingUrl,
        bool $shippingCostIncluded,
        int $billing,
        int $taxRulesGroupId,
        int $outOfRangeBehavior,
        array $shippingRanges,
        int $maxPackageWidth,
        int $maxPackageHeight,
        int $maxPackageDepth,
        float $maxPackageWeight,
        array $associatedGroupIds,
        array $associatedShopIds,
        bool $enabled
    ) {
        $command = new self();
        $command->setName($name);
        $command->setLocalizedShippingDelays($localizedShippingDelays);
        $command->setMeasures($maxPackageWidth, $maxPackageHeight, $maxPackageDepth, $maxPackageWeight);
        $command->setShippingRanges($shippingRanges);
        $command->speedGrade = new SpeedGrade($speedGrade);
        $command->billing = new Billing($billing);
        $command->trackingUrl = new TrackingUrl($trackingUrl);
        $command->outOfRangeBehavior = new OutOfRangeBehavior($outOfRangeBehavior);
        $command->shippingCostIncluded = $shippingCostIncluded;
        $command->taxRulesGroupId = $taxRulesGroupId;
        $command->associatedGroupIds = $associatedGroupIds;
        $command->associatedShopIds = $associatedShopIds;
        $command->enabled = $enabled;
        $command->freeShipping = false;

        return $command;
    }

    /**
     * Adds carrier with free of charge shipping
     *
     * @param string $name
     * @param string[] $localizedShippingDelays
     * @param int $speedGrade
     * @param string $trackingUrl
     * @param int $taxRulesGroupId
     * @param int $maxPackageWidth
     * @param int $maxPackageHeight
     * @param int $maxPackageDepth
     * @param float $maxPackageWeight
     * @param int[] $associatedGroupIds
     * @param int[] $associatedShopIds
     * @param bool $enabled
     *
     * @return AddCarrierCommand
     *
     * @throws CarrierConstraintException
     */
    public static function withFreeShipping(
        string $name,
        array $localizedShippingDelays,
        int $speedGrade,
        string $trackingUrl,
        int $taxRulesGroupId,
        int $maxPackageWidth,
        int $maxPackageHeight,
        int $maxPackageDepth,
        float $maxPackageWeight,
        array $associatedGroupIds,
        array $associatedShopIds,
        bool $enabled
    ) {
        $command = new self();
        $command->setName($name);
        $command->setLocalizedShippingDelays($localizedShippingDelays);
        $command->setMeasures($maxPackageWidth, $maxPackageHeight, $maxPackageDepth, $maxPackageWeight);
        $command->speedGrade = new SpeedGrade($speedGrade);
        $command->trackingUrl = new TrackingUrl($trackingUrl);
        $command->outOfRangeBehavior = new OutOfRangeBehavior(OutOfRangeBehavior::APPLY_HIGHEST_RANGE);
        $command->billing = new Billing(Billing::ACCORDING_TO_WEIGHT);
        $command->taxRulesGroupId = $taxRulesGroupId;
        $command->associatedGroupIds = $associatedGroupIds;
        $command->associatedShopIds = $associatedShopIds;
        $command->enabled = $enabled;

        $command->shippingRanges = [];
        $command->freeShipping = true;
        $command->shippingCostIncluded = false;

        return $command;
    }
}
