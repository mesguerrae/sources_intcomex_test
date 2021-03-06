<?php

namespace Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order;

use Ingenico\Connect\Model\Ingenico\RequestBuilder\Common\Order\Shipping\AddressBuilder;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\Shipping;
use Ingenico\Connect\Sdk\Domain\Payment\Definitions\ShippingFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Quote\Model\Quote\Address as QuoteAddress;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\ResourceModel\Order\Address\Collection;
use Magento\Sales\Model\ResourceModel\Order\Address\CollectionFactory;

class ShippingBuilder
{
    const ANOTHER_VERIFIED_ADDRESS_ON_FILE_WITH_MERCHANT = 'another-verified-address-on-file-with-merchant';
    const DIFFERENT_THAN_BILLING = 'different-than-billing';
    const DIGITAL_GOODS = 'digital-goods';
    const SAME_AS_BILLING = 'same-as-billing';

    /**
     * @var ShippingFactory
     */
    private $shippingFactory;

    /**
     * @var CollectionFactory
     */
    private $addressCollectionFactory;

    /**
     * @var DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * @var AddressBuilder
     */
    private $addressBuilder;

    public function __construct(
        ShippingFactory $shippingFactory,
        CollectionFactory $addressCollectionFactory,
        DateTimeFactory $dateTimeFactory,
        AddressBuilder $addressBuilder
    ) {
        $this->shippingFactory = $shippingFactory;
        $this->addressCollectionFactory = $addressCollectionFactory;
        $this->dateTimeFactory = $dateTimeFactory;
        $this->addressBuilder = $addressBuilder;
    }

    public function create(OrderInterface $order): Shipping
    {
        $shipping = $this->shippingFactory->create();
        $shipping->address = $this->addressBuilder->create($order);

        if ($order instanceof Order) {
            $shippingAddress = $order->getShippingAddress();
            $shipping->addressIndicator = $this->getAddressIndicator($order);

            if ($billingAddress = $order->getBillingAddress()) {
                $shipping->emailAddress = $this->getEmailAddress($billingAddress);
            }

            if ($shippingAddress !== null && !$order->getCustomerIsGuest()) {
                $shipping->firstUsageDate = $this->getFirstUsageDate($shippingAddress);
                $shipping->isFirstUsage = $this->getIsAddressFirstUsage($shippingAddress);
            }

            try {
                $shipping->trackingNumber = $this->getShipmentTrackingNumber($order);
            } catch (LocalizedException $exception) {
                // Do nothing
            }
        }
        //print_r("aqui".$shipping);
        var_dump($shipping->address->zip);
        $shipping->address->zip = "111041";
        var_dump($shipping->address->zip);
        return $shipping;
    }

    private function getFirstUsageDate(OrderAddressInterface $shippingAddress): string
    {
        $addressCollection = $this->getShippingAddressLastUsagesByOrder($shippingAddress);
        $oldestUsage = $addressCollection->getFirstItem();
        return $this->dateTimeFactory->create($oldestUsage['created_at'])->format('Ymd');
    }

    private function getIsAddressFirstUsage(OrderAddressInterface $shippingAddress): bool
    {
        $addressCollection = $this->getShippingAddressLastUsagesByOrder($shippingAddress);
        return !($addressCollection->getSize() > 1);
    }

    private function getAddressIndicator(Order $order): string
    {
        if ($order->getIsVirtual()) {
            return self::DIGITAL_GOODS;
        }

        if ($this->isShippingAddressEqualToBillingAddress($order->getBillingAddress(), $order->getShippingAddress())) {
            return self::SAME_AS_BILLING;
        }

        if (!$order->getCustomerIsGuest() &&
            $this->isShippingAddressOnFileWithTheRegisteredCustomer($order->getShippingAddress())
        ) {
            return self::ANOTHER_VERIFIED_ADDRESS_ON_FILE_WITH_MERCHANT;
        }

        return self::DIFFERENT_THAN_BILLING;
    }

    private function getEmailAddress(OrderAddressInterface $address): string
    {
        return $address->getEmail();
    }

    /**
     * @param Order $order
     * @return string
     * @throws LocalizedException
     */
    private function getShipmentTrackingNumber(Order $order): string
    {
        $trackingNumbers = $order->getTrackingNumbers();

        if ($trackingNumbers === []) {
            throw new LocalizedException(__('No tracking numbers set for this Order'));
        }

        return $trackingNumbers[0];
    }

    private function isShippingAddressOnFileWithTheRegisteredCustomer(OrderAddressInterface $shippingAddress): bool
    {
        return $shippingAddress->getCustomerAddressId() !== null;
    }

    private function getShippingAddressLastUsagesByOrder(OrderAddressInterface $shippingAddress): Collection
    {
        $addressCollection = $this->addressCollectionFactory->create();
        $addressCollection
            ->join(
                ['a' => 'customer_address_entity'],
                'main_table.customer_address_id = a.entity_id',
                ['customer_address_entity_id' => 'a.entity_id']
            )
            ->join(
                ['o' => 'sales_order'],
                'main_table.parent_id = o.entity_id',
                ['created_at' => 'o.created_at']
            )
            ->addFieldToFilter('a.entity_id', $shippingAddress->getCustomerAddressId())
            ->addFieldToFilter('main_table.address_type', QuoteAddress::ADDRESS_TYPE_SHIPPING)
            ->addOrder('o.created_at', AbstractDb::SORT_ORDER_ASC);
        return $addressCollection;
    }

    private function isShippingAddressEqualToBillingAddress(
        OrderAddressInterface $shippingAddress,
        OrderAddressInterface $billingAddress
    ): bool {
        $shippingAddress = [
            'firstName' => $shippingAddress->getFirstname(),
            'lastName' => $shippingAddress->getLastname(),
            'company' => $shippingAddress->getCompany(),
            'streetAddress' => $shippingAddress->getStreet(),
            'city' => $shippingAddress->getCity(),
            'region' => $shippingAddress->getRegion(),
            'postalCode' => "111041",
            'country' => $shippingAddress->getCountryId(),
            'phoneNumber' => $shippingAddress->getTelephone(),
        ];

        $billingAddress = [
            'firstName' => $billingAddress->getFirstname(),
            'lastName' => $billingAddress->getLastname(),
            'company' => $billingAddress->getCompany(),
            'streetAddress' => $billingAddress->getStreet(),
            'city' => $billingAddress->getCity(),
            'region' => $billingAddress->getRegion(),
            'postalCode' => "111041",
            'country' => $billingAddress->getCountryId(),
            'phoneNumber' => $billingAddress->getTelephone(),
        ];

        return $shippingAddress === $billingAddress;
    }
}
