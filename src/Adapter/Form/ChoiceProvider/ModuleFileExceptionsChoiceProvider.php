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

namespace PrestaShop\PrestaShop\Adapter\Form\ChoiceProvider;

use Dispatcher;
use PrestaShop\PrestaShop\Core\Form\FormChoiceProviderInterface;
use Symfony\Component\Translation\TranslatorInterface;

final class ModuleFileExceptionsChoiceProvider implements FormChoiceProviderInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Get choices.
     *
     * @return array
     */
    public function getChoices()
    {
        //@todo add if custom (check legacy)
        $controllersFO = Dispatcher::getControllers(_PS_FRONT_CONTROLLER_DIR_);
        ksort($controllersFO);

        $controllerTypes = [
            'admin' => $this->translator->trans('Admin modules controller', [], 'Admin.Design.Feature'),
            'front' => $this->translator->trans('Front modules controller', [], 'Admin.Design.Feature'),
        ];

        $adminModulesControllers = [];
        $frontModulesControllers = [];
        foreach ($controllerTypes as $type => $label) {
            $modulesControllers = Dispatcher::getModuleControllers($type);
            foreach ($modulesControllers as $module => $controllers) {
                foreach ($controllers as $controller) {
                    $composedValue = 'module-' . $module . '-' . $controller;
                    if ('admin' === $type) {
                        $adminModulesControllers[$composedValue] = $composedValue;
                        continue;
                    }
                    $frontModulesControllers[$composedValue] = $composedValue;
                }
            }
        }

        return [
            $this->translator->trans('____________ CORE ____________', [], 'Admin.Design.Feature') => $controllersFO,
            $this->translator->trans('Admin modules controller', [], 'Admin.Design.Feature') => $adminModulesControllers,
            $this->translator->trans('Front modules controller', array(), 'Admin.Design.Feature') => $frontModulesControllers,
        ];
    }
}
