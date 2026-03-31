<?php

namespace Makaew\Store\Exchange;

use Bitrix\Main\Loader;
use Bitrix\Sale\Order;

/**
 * Обработчик выгрузки заказов в 1С.
 *
 * Формирует XML в формате CommerceML для передачи
 * заказов и их статусов в систему 1С:Предприятие.
 */
class ExportHandler
{
    /** @var Logger */
    private Logger $logger;

    /** Маппинг статусов Битрикс → 1С */
    private const STATUS_MAP = [
        'N' => 'Новый',
        'P' => 'Оплачен',
        'F' => 'Выполнен',
        'D' => 'Отменён',
    ];

    public function __construct()
    {
        $this->logger = new Logger();
    }

    /**
     * Выгрузить заказы для обмена с 1С.
     *
     * @param \DateTime|null $fromDate Дата, начиная с которой выгружать
     * @return string XML-строка в формате CommerceML
     */
    public function exportOrders(?\DateTime $fromDate = null): string
    {
        Loader::includeModule('sale');

        $filter = ['=LID' => SITE_ID];

        if ($fromDate) {
            $filter['>=DATE_INSERT'] = \Bitrix\Main\Type\DateTime::createFromPhp($fromDate);
        }

        $orders = Order::getList([
            'filter' => $filter,
            'order' => ['DATE_INSERT' => 'ASC'],
            'limit' => 500,
        ]);

        $xml = $this->buildXmlHeader();

        $count = 0;
        while ($orderData = $orders->fetch()) {
            $order = Order::load($orderData['ID']);
            if ($order) {
                $xml .= $this->buildOrderXml($order);
                $count++;
            }
        }

        $xml .= $this->buildXmlFooter();

        $this->logger->info("Exported {$count} orders to 1C");

        return $xml;
    }

    /**
     * Формирование XML заголовка.
     */
    private function buildXmlHeader(): string
    {
        return <<<XML
        <?xml version="1.0" encoding="UTF-8"?>
        <КоммерческаяИнформация ВерсияСхемы="2.10" ДатаФормирования="{$this->getCurrentDateTime()}">
        XML;
    }

    /**
     * Формирование XML одного заказа.
     */
    private function buildOrderXml(Order $order): string
    {
        $orderId = $order->getId();
        $date = $order->getDateInsert()->format('Y-m-d');
        $time = $order->getDateInsert()->format('H:i:s');
        $price = number_format($order->getPrice(), 2, '.', '');
        $currency = $order->getCurrency();
        $status = self::STATUS_MAP[$order->getField('STATUS_ID')] ?? 'Новый';

        $xml = <<<XML

          <Документ>
            <Ид>{$orderId}</Ид>
            <Номер>{$orderId}</Номер>
            <Дата>{$date}</Дата>
            <Время>{$time}</Время>
            <Валюта>{$currency}</Валюта>
            <Сумма>{$price}</Сумма>
            <Статус>{$status}</Статус>
            <Товары>
        XML;

        // Товары заказа
        $basket = $order->getBasket();
        if ($basket) {
            /** @var \Bitrix\Sale\BasketItem $item */
            foreach ($basket as $item) {
                $itemName = htmlspecialchars($item->getField('NAME'));
                $itemQty = $item->getQuantity();
                $itemPrice = number_format($item->getPrice(), 2, '.', '');
                $itemTotal = number_format($item->getFinalPrice(), 2, '.', '');
                $xmlId = $this->getProductXmlId($item->getProductId());

                $xml .= <<<XML

                <Товар>
                  <Ид>{$xmlId}</Ид>
                  <Наименование>{$itemName}</Наименование>
                  <Количество>{$itemQty}</Количество>
                  <ЦенаЗаЕдиницу>{$itemPrice}</ЦенаЗаЕдиницу>
                  <Сумма>{$itemTotal}</Сумма>
                </Товар>
        XML;
            }
        }

        $xml .= <<<XML

            </Товары>
          </Документ>
        XML;

        return $xml;
    }

    private function buildXmlFooter(): string
    {
        return "\n</КоммерческаяИнформация>\n";
    }

    /**
     * Получить XML_ID товара по ID элемента каталога.
     */
    private function getProductXmlId(int $productId): string
    {
        Loader::includeModule('iblock');

        $element = \CIBlockElement::GetList(
            [],
            ['ID' => $productId],
            false,
            ['nTopCount' => 1],
            ['XML_ID']
        )->Fetch();

        return $element['XML_ID'] ?? (string) $productId;
    }

    private function getCurrentDateTime(): string
    {
        return date('Y-m-d\TH:i:s');
    }
}
