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

import { EventEmitter } from '../../components/event-emitter';

const $ = window.$;

/**
 * Responsible for carrier summary content which depends from previous steps inputs
 */
export default class SummaryContentHandler {
  constructor(
    formWrapper,
    freeShippingInput,
    transitTimeInput,
    billingChoice,
    taxRuleSelect,
    rangeRow,
    rangesSummaryWrapper,
    outrangedSelect,
    zoneCheck,
    zonesSummaryTarget,
    groupChecks,
    groupsSummaryTarget,
    shopChecks,
    shopsSummaryTarget
  ) {
    this.$formWrapper = $(formWrapper);
    this.freeShippingInput = freeShippingInput;
    this.transitTimeInput = transitTimeInput;
    this.$billing = $(billingChoice);
    this.$taxRuleSelect = $(taxRuleSelect);
    this.$rangeRow = $(rangeRow);
    this.$rangesSummary = $(rangesSummaryWrapper);
    this.$outrangedSelect = $(outrangedSelect);
    this.$zoneCheck = $(zoneCheck);
    this.$zonesSummaryTarget = $(zonesSummaryTarget);
    this.$groupChecks = $(groupChecks);
    this.$groupsSummaryTarget = $(groupsSummaryTarget);
    this.$shopChecks = $(shopChecks);
    this.$shopsSummaryTarget = $(shopsSummaryTarget);
    this.handle();

    return {};
  }

  /**
   * Initiates the handler
   *
   * @private
   */
  handle() {
    EventEmitter.on('formStepSwitched', () => {
      const isFreeShipping = $(`${this.freeShippingInput}:checked`).val() === '1';
      this.summarizeTransitTime(isFreeShipping);
      this.summarizeShippingCost(isFreeShipping);
      this.summarizeShippingRanges(isFreeShipping);
      this.summarizeDeliveryZones();
      this.summarizeClientGroups();
      this.summarizeShops();
    });
  }

  /**
   * Inserts free shipping and transit time summary content
   *
   * @param isFreeShipping
   */
  summarizeTransitTime(isFreeShipping) {
    const contextLangId = this.$formWrapper.data('context-lang-id');
    const defaultLangId = this.$formWrapper.data('default-lang-id');

    let transitValue = this.$formWrapper
      .find(`${this.transitTimeInput}[${contextLangId}]"]`)
      .val();

    if (transitValue === '') {
      transitValue = this.$formWrapper
        .find(`${this.transitTimeInput}[${defaultLangId}]"]`)
        .val();
    }

    // case when shipping is priced
    let transitCase = this.$formWrapper.find('#js-priced-carrier-transit');

    if (isFreeShipping) {
      transitCase.hide();
      // case when shipping is free
      transitCase = this.$formWrapper.find('#js-free-carrier-transit');
    }

    // replace place holder with selected transit value
    const transitContent = transitCase.data('carrier-transit').replace(
      '__TRANSIT_TIME__', `<b>${transitValue}</b>`,
    );

    // show content
    transitCase.html(transitContent);
    transitCase.show();
  }

  /**
   * Inserts shipping cost and tax rule summary content
   *
   * @param isFreeShipping
   */
  summarizeShippingCost(isFreeShipping) {
    const billingCasePrice = this.$formWrapper.find('#js-carrier-shipping-cost-price');
    const billingCaseWeight = this.$formWrapper.find('#js-carrier-shipping-cost-weight');

    if (isFreeShipping) {
      // hide billing content when free shipping is selected
      billingCasePrice.hide();
      billingCaseWeight.hide();

      return;
    }

    const selectedTaxRule = this.$taxRuleSelect.find(`option[value="${this.$taxRuleSelect.val()}"]`).text();

    // default case billing by weight
    let billingCase = this.$formWrapper.find('#js-carrier-shipping-cost-weight');

    // case when billing by price is selected
    if (this.$billing.find('input:checked').val() === '2') {
      billingCase.hide();
      billingCase = this.$formWrapper.find('#js-carrier-shipping-cost-price');
    }

    // replace placeholder with selected tax rule in content
    const billingContent = billingCase.data('carrier-shipping-cost')
      .replace('__TAX_RULE__', `<b>${selectedTaxRule}</b>`);

    // show the content
    billingCase.html(billingContent);
    billingCase.show();
  }

  /**
   * Inserts shipping ranges and out of range behavior summary content
   *
   * @param isFreeShipping
   */
  summarizeShippingRanges(isFreeShipping) {
    // whole ranges summary hidden by default
    this.$rangesSummary.hide();

    // show ranges summary if shipping is priced
    if (!isFreeShipping) {
      this.$rangesSummary.show();
    }

    const rangeFrom = $(this.$rangeRow).find('td:first-of-type').first().find('input').val();
    const rangeTo = $(this.$rangeRow).find('td:last-of-type').last().find('input').val();

    // range measure depends on selected billing type (price or weight ranges)
    let measure = '$';
    if (this.$billing.find('input:checked').val() === '2') {
      measure = 'kg';
    }

    const $rangeSummary = this.$rangesSummary.find('#js-range');

    // replace placeholders with range values
    const rangeText = $rangeSummary.data('range-summary')
      .replace('%1$s', `<b>${rangeFrom} ${measure}</b>`)
      .replace('%2$s', `<b>${rangeTo} ${measure}</b>`);

    // insert and show content
    $rangeSummary.html(rangeText);
    $rangeSummary.show();

    // default case when out of range behavior is "apply highest range"
    let outrangedCase = this.$rangesSummary.find('#js-outranged-highest');

    if (this.$outrangedSelect.val() === '1') {
      outrangedCase.hide();
      // case when out of range behavior "disable carrier" is selected
      outrangedCase = this.$rangesSummary.find('#js-outranged-disable');
    }

    // insert and show content
    outrangedCase.html(outrangedCase.data('outranged-summary'));
    outrangedCase.show();
  }

  /**
   * Inserts delivery zones summary content
   */
  summarizeDeliveryZones() {
    this.$zonesSummaryTarget.html('');

    $.each(this.$zoneCheck, ( i, zoneInput ) => {
      const $zoneInput = $(zoneInput);
      if ($zoneInput.is(':checked') && $zoneInput.val() !== '0') {
        this.$zonesSummaryTarget.append(`<li><b>${$zoneInput.parent().text()}</b></li>`)
      }
    });
  }

  /**
   * Inserts client groups summary content
   */
  summarizeClientGroups() {
    this.$groupsSummaryTarget.html('');

    $.each(this.$groupChecks.find('input:checked'), (i, groupInput) => {
      this.$groupsSummaryTarget.append(`<li><b>${$(groupInput).parent().text()}</b></li>`)
    });
  }

  summarizeShops() {
    this.$shopsSummaryTarget.html('');

    $.each(this.$shopChecks.find('input:checked'), (i, shopInput) => {
      this.$shopsSummaryTarget.append(`<li><b>${$(shopInput).parent().text()}</b></li>`)
    })
  }
}
