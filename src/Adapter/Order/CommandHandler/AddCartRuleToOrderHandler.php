<?php
/**
 * 2007-2020 PrestaShop SA and Contributors
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
 * @copyright 2007-2020 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

namespace PrestaShop\PrestaShop\Adapter\Order\CommandHandler;

use CartRule;
use Configuration;
use Currency;
use Order;
use OrderCartRule;
use OrderInvoice;
use PrestaShop\Decimal\Number;
use PrestaShop\PrestaShop\Adapter\Invoice\DTO\InvoiceTotalNumbers;
use PrestaShop\PrestaShop\Adapter\Order\AbstractOrderHandler;
use PrestaShop\PrestaShop\Adapter\Order\DTO\OrderTotalNumbers;
use PrestaShop\PrestaShop\Core\Domain\CartRule\ValueObject\PercentageDiscount;
use PrestaShop\PrestaShop\Core\Domain\Order\Command\AddCartRuleToOrderCommand;
use PrestaShop\PrestaShop\Core\Domain\Order\CommandHandler\AddCartRuleToOrderHandlerInterface;
use PrestaShop\PrestaShop\Core\Domain\Order\Exception\OrderException;
use PrestaShop\PrestaShop\Core\Domain\Order\OrderDiscountType;
use PrestaShop\PrestaShop\Core\Localization\CLDR\ComputingPrecision;
use Validate;

/**
 * @internal
 */
final class AddCartRuleToOrderHandler extends AbstractOrderHandler implements AddCartRuleToOrderHandlerInterface
{
    /**
     * Default rounding precision value (which was hardcoded in legacy code)
     * It is overridden by order currency precision.
     *
     * @var int
     */
    private $precision = 2;

    /**
     * {@inheritdoc}
     */
    public function handle(AddCartRuleToOrderCommand $command): void
    {
        $order = $this->getOrderObject($command->getOrderId());

        $computingPrecision = new ComputingPrecision();
        $currency = new Currency((int) $order->id_currency);
        // Override default precision with currency precision
        $this->precision = $computingPrecision->getPrecision($currency->precision);

        $discountValue = $command->getDiscountValue();
        $cartRuleType = $command->getCartRuleType();
        $reductionValues = $this->getReductionValues($cartRuleType, $order, $discountValue);
        $invoiceId = 0;
        $orderInvoice = $this->getInvoiceForUpdate($order, $command);

        if (null !== $orderInvoice) {
            $invoiceId = (int) $orderInvoice->id;
            $this->updateInvoiceDiscount($orderInvoice, $cartRuleType, $reductionValues);
        }

        $cartRule = $this->addCartRule($command, $order, $reductionValues, $discountValue);
        $this->addOrderCartRule($order->id, $command->getCartRuleName(), $cartRule->id, $invoiceId, $reductionValues);

        $this->applyReductionToOrder($order, $reductionValues);
    }

    /**
     * @param string $cartRuleType
     * @param Order $order
     * @param Number|null $discountValue
     *
     * @return array
     *
     * @throws OrderException
     */
    private function getReductionValues(string $cartRuleType, Order $order, ?Number $discountValue): array
    {
        $orderTotals = OrderTotalNumbers::buildFromOrder($order);

        switch ($cartRuleType) {
            case OrderDiscountType::DISCOUNT_PERCENT:
                if ($discountValue->isGreaterThan($this->number(PercentageDiscount::MAX_PERCENTAGE))) {
                    throw new OrderException('Percentage discount value cannot be higher than 100%.');
                }
                $reductionValues = $this->calculatePercentReduction(
                    $discountValue,
                    $orderTotals->getTotalPaidTaxIncl(),
                    $orderTotals->getTotalPaidTaxExcl()
                );

                break;
            case OrderDiscountType::DISCOUNT_AMOUNT:
                if ($discountValue->isGreaterThan($orderTotals->getTotalPaidTaxIncl())) {
                    throw new OrderException('The discount value is greater than the order total.');
                }
                $reductionValues = $this->calculateAmountReduction($discountValue, $this->number($order->getTaxesAverageUsed()));

                break;
            case OrderDiscountType::FREE_SHIPPING:
                $reductionValues = $this->calculateFreeShippingReduction(
                    $orderTotals->getTotalShippingTaxIncl(),
                    $orderTotals->getTotalShippingTaxExcl()
                );

                break;
            default:
                throw new OrderException('The discount type is invalid.');
        }

        return $reductionValues;
    }

    /**
     * @param OrderInvoice $orderInvoice
     * @param string $cartRuleType
     * @param Number|null $discountValue
     * @param Number[] $reductionValues
     */
    private function updateInvoiceDiscount(OrderInvoice $orderInvoice, string $cartRuleType, array $reductionValues): void
    {
        $valueTaxIncl = $reductionValues['value_tax_incl'];
        $valueTaxExcl = $reductionValues['value_tax_excl'];
        $invoiceTotals = InvoiceTotalNumbers::buildFromInvoice($orderInvoice);

        $isAlreadyFreeShipping = OrderDiscountType::FREE_SHIPPING === $cartRuleType && $invoiceTotals->getTotalShippingTaxIncl() <= 0;
        $discountAmountIsTooBig = OrderDiscountType::DISCOUNT_AMOUNT === $cartRuleType &&
            $reductionValues['value_tax_incl']->isGreaterThan($invoiceTotals->getTotalPaidTaxExcl())
        ;

        if ($isAlreadyFreeShipping) {
            return;
        } elseif ($discountAmountIsTooBig) {
            throw new OrderException('The discount value is greater than the order invoice total.');
        }

        $orderInvoice->total_discount_tax_incl = (float) (string) $invoiceTotals->getTotalDiscountTaxIncl()
            ->plus($valueTaxIncl)
        ;
        $orderInvoice->total_discount_tax_excl = (float) (string) $invoiceTotals->getTotalDiscountTaxExcl()
            ->plus($valueTaxExcl)
        ;
        $orderInvoice->total_paid_tax_incl = (float) (string) $invoiceTotals->getTotalPaidTaxIncl()
            ->minus($valueTaxIncl)
        ;
        $orderInvoice->total_paid_tax_excl = (float) (string) $invoiceTotals->getTotalPaidTaxExcl()
            ->minus($valueTaxExcl)
        ;

        $orderInvoice->update();
    }

    /**
     * @param AddCartRuleToOrderCommand $command
     * @param Order $order
     * @param Number[] $reducedValues
     * @param float $discountValue
     *
     * @return CartRule
     */
    private function addCartRule(
        AddCartRuleToOrderCommand $command,
        Order $order,
        array $reducedValues,
        ?Number $discountValue
    ): CartRule {
        $cartRuleObj = new CartRule();
        $cartRuleObj->date_from = date('Y-m-d H:i:s', strtotime('-1 hour', strtotime($order->date_add)));
        $cartRuleObj->date_to = date('Y-m-d H:i:s', strtotime('+1 hour'));
        $cartRuleObj->name[Configuration::get('PS_LANG_DEFAULT')] = $command->getCartRuleName();
        $cartRuleObj->quantity = 0;
        $cartRuleObj->quantity_per_user = 1;

        if ($command->getCartRuleType() === OrderDiscountType::DISCOUNT_PERCENT) {
            $cartRuleObj->reduction_percent = (float) (string) $discountValue;
        } elseif ($command->getCartRuleType() === OrderDiscountType::DISCOUNT_AMOUNT) {
            $cartRuleObj->reduction_amount = (float) (string) $reducedValues['value_tax_excl'];
        } elseif ($command->getCartRuleType() === OrderDiscountType::FREE_SHIPPING) {
            $cartRuleObj->free_shipping = 1;
        }

        $cartRuleObj->active = 0;

        if (false === $cartRuleObj->add()) {
            throw new OrderException('An error occurred during the CartRule creation');
        }

        return $cartRuleObj;
    }

    /**
     * @param int $orderId
     * @param string $cartRuleName
     * @param int $cartRuleId
     * @param int $invoiceId
     * @param Number[] $reductionValues
     */
    private function addOrderCartRule(
        int $orderId,
        string $cartRuleName,
        int $cartRuleId,
        int $invoiceId,
        array $reductionValues
    ): void {
        $orderCartRule = new OrderCartRule();
        $orderCartRule->id_order = $orderId;
        $orderCartRule->id_cart_rule = $cartRuleId;
        $orderCartRule->id_order_invoice = $invoiceId;
        $orderCartRule->name = $cartRuleName;
        $orderCartRule->value = (float) (string) $reductionValues['value_tax_incl'];
        $orderCartRule->value_tax_excl = (float) (string) $reductionValues['value_tax_excl'];

        if (false === $orderCartRule->add()) {
            throw new OrderException('An error occurred during the OrderCartRule creation');
        }
    }

    /**
     * @param Order $order
     * @param AddCartRuleToOrderCommand $command
     *
     * @return OrderInvoice|null
     */
    private function getInvoiceForUpdate(Order $order, AddCartRuleToOrderCommand $command): ?OrderInvoice
    {
        // If the discount is for only one invoice
        if ($order->hasInvoice() && null !== $command->getOrderInvoiceId()) {
            $orderInvoice = new OrderInvoice($command->getOrderInvoiceId()->getValue());
            if (!Validate::isLoadedObject($orderInvoice)) {
                throw new OrderException('Can\'t load Order Invoice object');
            }

            return $orderInvoice;
        }

        return null;
    }

    /**
     * @param Number $discountValue
     * @param Number $totalPaidTaxIncl
     * @param Number $totalPaidTaxExcl
     *
     * @return array
     */
    private function calculatePercentReduction(
        Number $discountValue,
        Number $totalPaidTaxIncl,
        Number $totalPaidTaxExcl
    ): array {
        $hundredPercent = $this->number(100);

        $valueTaxIncl = $discountValue
            ->times($totalPaidTaxIncl)
            ->dividedBy($hundredPercent)
        ;

        $valueTaxExcl = $discountValue
            ->times($totalPaidTaxExcl)
            ->dividedBy($hundredPercent)
        ;

        return $this->buildReducedValues(
            $this->number($valueTaxIncl->round($this->precision)),
            $this->number($valueTaxExcl->round($this->precision))
        );
    }

    /**
     * @param Number $discountValue
     * @param Number $taxesAverageUsed
     *
     * @return array
     */
    private function calculateAmountReduction(
        Number $discountValue,
        Number $taxesAverageUsed
    ) {
        $hundredPercent = $this->number(100);
        $avgTax = $this->number(1)->plus($taxesAverageUsed->dividedBy($hundredPercent));

        $totalTaxExcl = $discountValue
            ->dividedBy($avgTax)
        ;

        return $this->buildReducedValues(
            $this->number($discountValue->round($this->precision)),
            $this->number($totalTaxExcl->round($this->precision))
        );
    }

    /**
     * @param Number $totalShippingTaxIncl
     * @param Number $totalShippingTaxExcl
     *
     * @return array
     */
    private function calculateFreeShippingReduction(Number $totalShippingTaxIncl, Number $totalShippingTaxExcl)
    {
        return $this->buildReducedValues(
            $totalShippingTaxIncl,
            $totalShippingTaxExcl
        );
    }

    /**
     * @param Number $valueTaxIncl
     * @param Number $valueTaxExcl
     *
     * @return array
     */
    private function buildReducedValues(Number $valueTaxIncl, Number $valueTaxExcl): array
    {
        return [
            'value_tax_incl' => $valueTaxIncl,
            'value_tax_excl' => $valueTaxExcl,
        ];
    }

    /**
     * @param Order $order
     * @param Number[] $reductionValues
     */
    private function applyReductionToOrder(Order $order, array $reductionValues): void
    {
        $orderTotals = OrderTotalNumbers::buildFromOrder($order);

        $order->total_discounts = (float) $orderTotals->getTotalDiscounts()
            ->plus($reductionValues['value_tax_incl'])
            ->round($this->precision)
        ;
        $order->total_discounts_tax_incl = (float) $orderTotals->getTotalDiscountTaxIncl()
            ->plus($reductionValues['value_tax_incl'])
            ->round($this->precision)
        ;
        $order->total_discounts_tax_excl = (float) $orderTotals->getTotalDiscountTaxExcl()
            ->plus($reductionValues['value_tax_excl'])
            ->round($this->precision)
        ;
        $order->total_paid = (float) $orderTotals->getTotalPaid()
            ->minus($reductionValues['value_tax_incl'])
            ->round($this->precision)
        ;
        $order->total_paid_tax_incl = (float) $orderTotals->getTotalPaidTaxIncl()
            ->minus($reductionValues['value_tax_incl'])
            ->round($this->precision)
        ;
        $order->total_paid_tax_excl = (float) $orderTotals->getTotalPaidTaxExcl()
            ->minus($reductionValues['value_tax_excl'])
            ->round($this->precision)
        ;

        if (false === $order->update()) {
            throw new OrderException('An error occurred trying to apply cart rule to order');
        }
    }
}
