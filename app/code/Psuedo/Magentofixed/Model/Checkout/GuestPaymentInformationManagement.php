<?php
/**
 * Created by PhpStorm.
 * User: ppostma
 * Date: 2019-06-10
 * Time: 11:23
 */

namespace Psuedo\Magentofixed\Model\Checkout;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\ResourceConnection;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Quote\Model\Quote;


class GuestPaymentInformationManagement extends \Magento\Checkout\Model\GuestPaymentInformationManagement
{


	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;  /*f*!@#$@ng idiots making values private*/

	/**
	 * @var ResourceConnection
	 */
	protected $connectionPool;

	/**
	 * @param \Magento\Quote\Api\GuestBillingAddressManagementInterface $billingAddressManagement
	 * @param \Magento\Quote\Api\GuestPaymentMethodManagementInterface $paymentMethodManagement
	 * @param \Magento\Quote\Api\GuestCartManagementInterface $cartManagement
	 * @param \Magento\Checkout\Api\PaymentInformationManagementInterface $paymentInformationManagement
	 * @param \Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory
	 * @param CartRepositoryInterface $cartRepository
	 * @param ResourceConnection|null
	 * @codeCoverageIgnore
	 */
	public function __construct(
		\Magento\Quote\Api\GuestBillingAddressManagementInterface $billingAddressManagement,
		\Magento\Quote\Api\GuestPaymentMethodManagementInterface $paymentMethodManagement,
		\Magento\Quote\Api\GuestCartManagementInterface $cartManagement,
		\Magento\Checkout\Api\PaymentInformationManagementInterface $paymentInformationManagement,
		\Magento\Quote\Model\QuoteIdMaskFactory $quoteIdMaskFactory,
		CartRepositoryInterface $cartRepository,
		ResourceConnection $connectionPool = null
	) {
		$this->billingAddressManagement = $billingAddressManagement;
		$this->paymentMethodManagement = $paymentMethodManagement;
		$this->cartManagement = $cartManagement;
		$this->paymentInformationManagement = $paymentInformationManagement;
		$this->quoteIdMaskFactory = $quoteIdMaskFactory;
		$this->cartRepository = $cartRepository;
		$this->connectionPool = $connectionPool ?: ObjectManager::getInstance()->get(ResourceConnection::class);

		parent::__construct($billingAddressManagement, $paymentMethodManagement, $cartManagement, $paymentInformationManagement, $quoteIdMaskFactory, $cartRepository, $connectionPool);

		$this->connectionPool = $connectionPool;
	}

	/**
	 * {@inheritDoc}
	 */
	public function savePaymentInformationAndPlaceOrder(
		$cartId,
		$email,
		\Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
		\Magento\Quote\Api\Data\AddressInterface $billingAddress = null
	) {
		$salesConnection = $this->connectionPool->getConnection('sales');
		$checkoutConnection = $this->connectionPool->getConnection('checkout');
		$salesConnection->beginTransaction();
		$checkoutConnection->beginTransaction();

		try {
			$this->savePaymentInformation($cartId, $email, $paymentMethod, $billingAddress);
			try {
				$orderId = $this->cartManagement->placeOrder($cartId);

			} catch (\Magento\Framework\Exception\LocalizedException $e) {
				//lets add these steps and find out why this is failing
				$this->getLogger()->critical("Failed to save transaction after processes completed, localized");
				$this->getLogger()->critical($e);

				//pass through code
				if($e->getCode() && $e->getCode() > 199)
					$code = $e->getCode();
				else
					$code = 500;

				//throw exception, with correct http code included
				throw new CouldNotSaveException(
					__($e->getMessage()),
					$e,
					$code
				);
			} catch (\Exception $e) {
				$this->getLogger()->critical("Failed to save transaction after processes completed, general");
				$this->getLogger()->critical($e);

				//pass through code
				if($e->getCode() && $e->getCode() > 199)
					$code = $e->getCode();
				else
					$code = 500;

				throw new CouldNotSaveException(
					__('An error occurred on the server. Please try to place the order again.'),
					$e,
					$code
				);
			}
			$salesConnection->commit();
			$checkoutConnection->commit();
		} catch (\Exception $e) {
			$salesConnection->rollBack();
			$checkoutConnection->rollBack();
			throw $e;
		}

		return $orderId;
	}


	/**
	 * Get logger instance
	 *
	 * @return \Psr\Log\LoggerInterface
	 *
	 */
	protected function getLogger()
	{
		if (!$this->logger) {
			$this->logger = \Magento\Framework\App\ObjectManager::getInstance()->get(\Psr\Log\LoggerInterface::class);
		}
		return $this->logger;
	}

	/**
	 * Limits shipping rates request by carrier from shipping address.
	 *
	 * @param Quote $quote
	 *
	 * @return void
	 * @see \Magento\Shipping\Model\Shipping::collectRates
	 */
	protected function limitShippingCarrier(Quote $quote) : void
	{
		$shippingAddress = $quote->getShippingAddress();
		if ($shippingAddress && $shippingAddress->getShippingMethod()) {
			$shippingDataArray = explode('_', $shippingAddress->getShippingMethod());
			$shippingCarrier = array_shift($shippingDataArray);
			$shippingAddress->setLimitCarrier($shippingCarrier);
		}
	}
}
