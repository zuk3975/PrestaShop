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

namespace PrestaShopBundle\Form\Admin\Sell\Product\Combination;

use PrestaShop\PrestaShop\Core\Form\FormChoiceAttributeProviderInterface;
use PrestaShop\PrestaShop\Core\Form\FormChoiceProviderInterface;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

/**
 * Form to edit Combination details.
 */
class CombinationFormType extends TranslatorAwareType
{
    /**
     * @var FormChoiceProviderInterface
     */
    private $productImageIdsChoiceProvider;

    /**
     * @var FormChoiceAttributeProviderInterface
     */
    private $productImageAttrChoiceProvider;

    /**
     * @param TranslatorInterface $translator
     * @param array $locales
     * @param FormChoiceProviderInterface $productImageIdsChoiceProvider
     * @param FormChoiceAttributeProviderInterface $productImageAttrChoiceProvider
     */
    public function __construct(
        TranslatorInterface $translator,
        array $locales,
        FormChoiceProviderInterface $productImageIdsChoiceProvider,
        FormChoiceAttributeProviderInterface $productImageAttrChoiceProvider
    ) {
        parent::__construct($translator, $locales);
        $this->productImageIdsChoiceProvider = $productImageIdsChoiceProvider;
        $this->productImageAttrChoiceProvider = $productImageAttrChoiceProvider;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', HiddenType::class)
            ->add('stock', CombinationStockType::class, [
                'label' => $this->trans('Stock', 'Admin.Catalog.Feature'),
                'label_attr' => [
                    'title' => 'h2',
                ],
            ])
            ->add('price_impact', CombinationPriceImpactType::class, [
                'label' => $this->trans('Price and impact', 'Admin.Catalog.Feature'),
                'label_attr' => [
                    'title' => 'h2',
                ],
            ])
            ->add('details', CombinationDetailsType::class, [
                'label' => $this->trans('Specific references', 'Admin.Catalog.Feature'),
                'label_attr' => [
                    'title' => 'h2',
                ],
            ])
            ->add('image_ids', ChoiceType::class, [
                'choices' => $this->productImageIdsChoiceProvider->getChoices(),
                'choices_attr' => $this->productImageAttrChoiceProvider->getChoicesAttributes(),
            ])
        ;
    }
}
