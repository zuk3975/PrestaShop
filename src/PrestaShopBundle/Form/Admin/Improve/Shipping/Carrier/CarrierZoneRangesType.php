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

namespace PrestaShopBundle\Form\Admin\Improve\Shipping\Carrier;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CarrierZoneRangesType extends AbstractType
{
    /**
     * @var array
     */
    private $zones;
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param array $zones
     * @param TranslatorInterface $translator
     */
    public function __construct(array $zones, TranslatorInterface $translator)
    {
        $this->zones = $zones;
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('zones', ZoneCheckType::class, [
                'label' => false,
                'required' => false,
                'zones' => $this->zones,
            ])
            ->add('zone_ranges', CollectionType::class, [
                'entry_type' => ZoneRangeType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'entry_options' => [
                    'label' => false,
                ],
                'label' => false,
                'data' => [
                    [
                        'from' => '',
                        'to' => '',
                        'zone_prices' => $this->getDefaultZonePrices()
                    ]
                ]
            ]);
    }

    /**
     * @return array
     */
    private function getDefaultZonePrices(): array
    {
        $zones = [
            [
                'name' => $this->translator->trans('All', [], 'Admin.Global'),
                'id_zone' => 0,
            ],
        ];

        $zones = array_merge($zones, $this->zones);

        $zonePrices = [];
        foreach ($zones as $zone) {
            $zonePrices[] = [
                'zone_id' => $zone['id_zone'],
                'price' => ''
            ];
        }

        return $zonePrices;
    }
}
