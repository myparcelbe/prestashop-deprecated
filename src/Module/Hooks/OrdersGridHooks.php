<?php

namespace Gett\MyparcelBE\Module\Hooks;

use Configuration;
use Gett\MyparcelBE\Constant;
use Gett\MyparcelBE\Grid\Action\Bulk\IconBulkAction;
use Gett\MyparcelBE\Grid\Action\Bulk\IconModalBulkAction;
use Gett\MyparcelBE\Label\LabelOptionsResolver;
use Gett\MyparcelBE\Module\Hooks\Helpers\AdminOrderList;
use Gett\MyparcelBE\Module\Hooks\Helpers\AdminOrderView;
use PrestaShop\PrestaShop\Core\Grid\Column\Type\DataColumn;
use PrestaShop\PrestaShop\Core\Grid\Record\RecordCollection;

trait OrdersGridHooks
{
    public function hookActionOrderGridQueryBuilderModifier(array $params)
    {
        $allowedCarriers = array_map('intval', [
            Configuration::get(Constant::DPD_CONFIGURATION_NAME),
            Configuration::get(Constant::BPOST_CONFIGURATION_NAME),
            Configuration::get(Constant::POSTNL_CONFIGURATION_NAME),
        ]);
        $carrierIds = implode(',', $allowedCarriers);
        /** @var \Doctrine\DBAL\Query\QueryBuilder $searchQueryBuilder */
        $searchQueryBuilder = $params['search_query_builder'];

        $prefix = 'car' . $this->id;
        $searchQueryBuilder->addSelect('IF(' . $prefix . '.id_reference IN(' . $carrierIds . '), 1, 0) AS labels');
        $searchQueryBuilder->addSelect('o.id_carrier, ' . $prefix . '.id_reference AS id_carrier_reference');
        $searchQueryBuilder->addSelect('IFNULL(' . $prefix . '.name, \'\') AS delivery_info');
        $searchQueryBuilder->addSelect('o.id_cart');
        $searchQueryBuilder->leftJoin(
            'o',
            _DB_PREFIX_ . 'carrier',
            $prefix,
            'o.id_carrier = ' . $prefix . '.id_carrier'
        );
    }

    public function hookActionOrderGridDefinitionModifier(array $params)
    {
        $promptForLabelPosition = Configuration::get(Constant::LABEL_PROMPT_POSITION_CONFIGURATION_NAME);
        /** @var \PrestaShop\PrestaShop\Core\Grid\Definition\GridDefinitionInterface $definition */
        $definition = $params['definition'];
        $definition
            ->getColumns()
            ->addBefore('actions', (new DataColumn('labels'))
            ->setName($this->l('Labels', 'ordersgridhooks'))
            ->setOptions([
                'field' => 'labels',
                'clickable' => false,
            ])
        );
        $definition
            ->getColumns()
            ->addBefore('labels', (new DataColumn('delivery_info'))
                ->setName($this->l('Delivery date', 'ordersgridhooks'))
                ->setOptions([
                    'field' => 'delivery_info',
                ])
            );
        $definition->getBulkActions()->add(
            (new IconModalBulkAction('print_label'))
                ->setName('Print labels')
                ->setOptions([
                    'submit_route' => '',
                    'modal_id' => $promptForLabelPosition ? 'bulk-print' : '',
                    'material_icon' => 'download',
                ])
        );
        $definition->getBulkActions()->add(
            (new IconBulkAction('refresh_label'))
                ->setName('Refresh labels')
                ->setOptions([
                    'submit_route' => '',
                    'material_icon' => 'download',
                ])
        );
        $definition->getBulkActions()->add(
            (new IconBulkAction('create_label'))
                ->setName('Export labels')
                ->setOptions([
                    'submit_route' => '',
                    'material_icon' => 'download',
                ])
        );
        $definition->getBulkActions()->add(
            (new IconModalBulkAction('create_print_label'))
                ->setName('Export and print labels')
                ->setOptions([
                    'submit_route' => '',
                    'modal_id' => $promptForLabelPosition ? 'bulk-export-print' : '',
                    'material_icon' => 'download',
                ])
        );
    }

    public function hookActionOrderGridPresenterModifier(array &$params)
    {
        $rows = $params['presented_grid']['data']['records']->all();
        foreach ($rows as &$row) {
            if ($row['labels'] === '0') {
                $row['labels'] = '';
                continue;
            }
            $idOrder = (int) ($row['id_order'] ?? 0);
            if (!$idOrder) {
                continue;
            }
            $orderHelper = new AdminOrderView($this, (int) $row['id_order'], $this->context);
            $labelOptionsResolver = new LabelOptionsResolver();
            $promptForLabelPosition = Configuration::get(Constant::LABEL_PROMPT_POSITION_CONFIGURATION_NAME);

            $labelList = $orderHelper->getLabels();
            $labelsHtml = '';
            if (!empty($labelList)) {
                $labelsHtml = $this->get('twig')->render(
                    '@Modules/' . $this->name . '/views/PrestaShop/Admin/Common/Grid/Columns/Content/label_list.html.twig',
                    [
                        'labels' => $labelList,
                        'link' => $this->context->link,
                        'promptForLabelPosition' => $promptForLabelPosition,
                    ]
                );
            }

            $createButtonHtml = $this->get('twig')->render(
                '@Modules/' . $this->name . '/views/PrestaShop/Admin/Common/Grid/Columns/Content/create_label.html.twig',
                [
                    'idOrder' => $idOrder,
                    'labelOptions' => $labelOptionsResolver->getLabelOptions($row),
                    'allowSetOnlyRecipient' => $orderHelper->allowSetOnlyRecipient((int) $row['id_carrier_reference']),
                    'allowSetSignature' => $orderHelper->allowSetSignature((int) $row['id_carrier_reference']),
                ]
            );

            $row['labels'] = $labelsHtml . $createButtonHtml;
        }
        foreach ($rows as &$row) {
            $adminOrderList = new AdminOrderList($this);
            if (!$adminOrderList->isMyParcelCarrier($row['id_carrier_reference'])) {
                $row['delivery_info'] = '';
                continue;
            }
            if ($row['delivery_info'] === '0') {
                $row['delivery_info'] = '';
                continue;
            }

            $deliverySettings = $this->getDeliverySettingsByCart($row['id_cart']);
            try {
                if (empty($deliverySettings['date'])) {
                    continue;
                }
                $date = new \DateTime($deliverySettings['date']);
                $dateFormatted = $date->format($this->context->language->date_format_lite);
                if (!empty($dateFormatted)) {
                    $row['delivery_info'] = sprintf('[%s] %s', $dateFormatted, $row['delivery_info']);
                }
            } catch (\Exception $exception) {
            }
        }
        $params['presented_grid']['data']['records'] = new RecordCollection($rows);
    }

    public function hookDisplayAdminOrderMain($params): string
    {
        $adminOrderView = new AdminOrderView($this, (int) $params['id_order'], $this->context);

        return $adminOrderView->display();
    }

    private function getOrderId(): int
    {
        $parts = explode('/', parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        foreach ($parts as $part) {
            if (is_numeric($part)) {
                return $part;
            }
        }

        return 0;
    }
}
