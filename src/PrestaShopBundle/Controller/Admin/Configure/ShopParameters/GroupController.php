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

namespace PrestaShopBundle\Controller\Admin\Configure\ShopParameters;

use Group;
use PrestaShopBundle\Bridge\AdminController\ControllerConfiguration;
use PrestaShopBundle\Bridge\AdminController\FrameworkBridgeControllerInterface;
use PrestaShopBundle\Bridge\AdminController\FrameworkBridgeControllerListTrait;
use PrestaShopBundle\Bridge\AdminController\FrameworkBridgeControllerTrait;
use PrestaShopBundle\Bridge\Helper\Listing\HelperBridge\HelperListBridge;
use PrestaShopBundle\Bridge\Helper\Listing\HelperListConfiguration;
use PrestaShopBundle\Bridge\Smarty\FrameworkControllerSmartyTrait;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class GroupController extends FrameworkBundleAdminController implements FrameworkBridgeControllerInterface
{
    use FrameworkBridgeControllerTrait;
    use FrameworkBridgeControllerListTrait;
    use FrameworkControllerSmartyTrait;

    public function indexAction(Request $request): Response
    {
        $this->setHeaderToolbarActions();

        $helperListConfiguration = $this->buildListConfiguration(
            'id_group',
            '',
            'id_group',
            $request->attributes->get('_route')
        );

        $this->setListFields($helperListConfiguration);
        $this->setListActions($helperListConfiguration);
        $this->processFilters($request, $helperListConfiguration);

        return $this->renderSmarty($this->getHelperListBridge()->generateList($helperListConfiguration));
    }

    /**
     * {@inheritDoc}
     */
    public function getControllerConfiguration(): ControllerConfiguration
    {
        return $this->buildControllerConfiguration(
            'group',
            Group::class,
            'AdminGroups'
        );
    }

    /**
     * @return void
     */
    private function setHeaderToolbarActions(): void
    {
        $controllerConfiguration = $this->getControllerConfiguration();
        $index = $controllerConfiguration->legacyCurrentIndex;
        $token = $controllerConfiguration->token;

        $controllerConfiguration
            ->addHeaderToolbarAction('new_group', [
                'href' => $index . '&addgroup&token=' . $token,
                'desc' => $this->trans('Add new group', 'Admin.Shopparameters.Feature'),
                'icon' => 'process-icon-new',
            ])
        ;
    }

    /**
     * Build actions for list.
     *
     * @return void
     */
    private function setListActions(HelperListConfiguration $helperListConfiguration): void
    {
        $helperListConfiguration
            ->addRowAction('view')
            ->addRowAction('edit')
            ->addRowAction('delete')
            ->addToolbarAction('new', [
                'href' => $this->controllerConfiguration->legacyCurrentIndex . '&addgroup&token=' . $this->controllerConfiguration->token,
                'desc' => $this->trans('Add new', 'Admin.Actions'),
            ])
            ->addBulkAction('delete', [
                'text' => $this->trans('Delete selected', 'Admin.Actions'),
                'icon' => 'icon-trash',
                'confirm' => $this->trans('Delete selected items?', 'Admin.Notifications.Warning'),
            ])
        ;
    }

    /**
     * @param HelperListConfiguration $helperListConfiguration
     */
    private function setListFields(HelperListConfiguration $helperListConfiguration): void
    {
        $helperListConfiguration->setFieldsList([
            'id_group' => [
                'title' => $this->trans('ID', 'Admin.Global'),
                'align' => 'center',
                'class' => 'fixed-width-xs',
            ],
            'name' => [
                'title' => $this->trans('Group name', 'Admin.Shopparameters.Feature'),
                'filter_key' => 'b!name',
            ],
            'reduction' => [
                'title' => $this->trans('Discount (%)', 'Admin.Shopparameters.Feature'),
                'align' => 'right',
                'type' => 'percent',
            ],
            'nb' => [
                'title' => $this->trans('Members', 'Admin.Shopparameters.Feature'),
                'align' => 'center',
                'havingFilter' => true,
            ],
            'show_prices' => [
                'title' => $this->trans('Show prices', 'Admin.Shopparameters.Feature'),
                'align' => 'center',
                'type' => 'bool',
                'orderby' => false,
            ],
            //@todo: date filter doesnt work in front
            'date_add' => [
                'title' => $this->trans('Creation date', 'Admin.Shopparameters.Feature'),
                'type' => 'date',
                'align' => 'right',
            ],
        ]);
    }

    /**
     * @return HelperListBridge
     */
    private function getHelperListBridge(): HelperListBridge
    {
        return $this->get('prestashop.bridge.helper.listing.helper_bridge.helper_list_bridge');
    }
}
