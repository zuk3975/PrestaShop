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

namespace PrestaShopBundle\Form\Admin\Sell\Product\Options;

use PrestaShop\PrestaShop\Core\Form\FormChoiceProviderInterface;
use PrestaShopBundle\Form\Admin\Type\IconButtonType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class AttachedFilesType extends TranslatorAwareType
{
    /**
     * @var FormChoiceProviderInterface
     */
    private $attachmentNameByIdChoiceProvider;

    /**
     * @param TranslatorInterface $translator
     * @param array<int, array<string, mixed>> $locales
     * @param FormChoiceProviderInterface $attachmentNameByIdChoiceProvider
     */
    public function __construct(
        TranslatorInterface $translator,
        array $locales,
        FormChoiceProviderInterface $attachmentNameByIdChoiceProvider
    ) {
        parent::__construct($translator, $locales);
        $this->attachmentNameByIdChoiceProvider = $attachmentNameByIdChoiceProvider;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('attachment_ids', ChoiceType::class, [
                'choices' => $this->attachmentNameByIdChoiceProvider->getChoices(),
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                // placeholder false is important to avoid empty option in select input despite required being false
                'placeholder' => false,
            ])
//            ->add('attachments_list', CollectionType::class, [
//                'entry_type' => ListedAttachmentType::class,
//            ])
            ->add('attach_new_file', IconButtonType::class, [
                'label' => $this->trans('Attach a new file', 'Admin.Catalog.Feature'),
                'icon' => 'add_circle',
                'attr' => [
                    'class' => 'btn-outline-secondary',
                ],
            ])
        ;
    }
}
