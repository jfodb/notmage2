<?php

namespace Psuedo\Magentofixed\Plugin;

use Magento\Sales\Api\OrderRepositoryInterface;

class ShipOrderNotFraud
{
    protected $orderRepository;
    public static $FRAUD = 'fraud';

    public function __construct(
		OrderRepositoryInterface $orderRepository
	) {
        $this->orderRepository = $orderRepository;
    }

    public function beforeExecute(
		\Magento\Sales\Model\ShipOrder $shipOrder,
		$orderId,
		array $items = [],
		$notify = false,
		$appendComment = false,
		\Magento\Sales\Api\Data\ShipmentCommentCreationInterface $comment = null,
		array $tracks = [],
		array $packages = [],
		\Magento\Sales\Api\Data\ShipmentCreationArgumentsInterface $arguments = null
	) {

		/* Presently not linked in or used, but could be needed if we have to change the order status sooner */
        if ($orderId) {
            $order = $this->orderRepository->get($orderId);
            $order->getStatus();
        }
    }
}
