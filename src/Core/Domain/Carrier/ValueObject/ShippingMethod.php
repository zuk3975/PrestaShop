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

namespace PrestaShop\PrestaShop\Core\Domain\Carrier\ValueObject;

use PrestaShop\PrestaShop\Core\Domain\Carrier\Exception\CarrierConstraintException;

class ShippingMethod
{
    public const SHIPPING_METHOD_DEFAULT = 0;
    public const SHIPPING_METHOD_WEIGHT = 1;
    public const SHIPPING_METHOD_PRICE = 2;
    public const SHIPPING_METHOD_FREE = 3;

    public const VALID_SHIPPING_METHODS = [
        self::SHIPPING_METHOD_DEFAULT,
        self::SHIPPING_METHOD_WEIGHT,
        self::SHIPPING_METHOD_PRICE,
        self::SHIPPING_METHOD_FREE,
    ];

    /**
     * @var int
     */
    private $value;

    public function __construct(
        int $value
    ) {
        $this->assertValueIsValid($value);
        $this->value = $value;
    }

    /**
     * @return int
     */
    public function getValue(): int
    {
        return $this->value;
    }

    public function assertValueIsValid(int $value): void
    {
        if (in_array($value, self::VALID_SHIPPING_METHODS, true)) {
            return;
        }

        throw new CarrierConstraintException(
            'Invalid shipping method value',
            CarrierConstraintException::INVALID_SHIPPING_METHOD
        );
    }
}
