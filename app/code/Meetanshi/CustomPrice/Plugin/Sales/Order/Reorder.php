<?php

namespace Meetanshi\CustomPrice\Plugin\Sales\Order;

use Magento\Framework\Registry;
use Meetanshi\CustomPrice\Helper\Data;

class Reorder {

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    private $cart;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    private $resultRedirectFactory;

    /**
     * @var \Magento\Sales\Controller\AbstractController\OrderLoaderInterface
     */
    private $orderLoader;

    /**
     * @var Registry
     */
    private $registry;
    private $helper;

    public function __construct(
        \Magento\Sales\Controller\AbstractController\OrderLoaderInterface  $orderLoader,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory,
        \Magento\Checkout\Model\Cart $cart,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        Registry $registry,
        Data $data
    ) {

        $this->registry = $registry;
        $this->orderLoader = $orderLoader;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->cart = $cart;
        $this->messageManager = $messageManager;
        $this->helper = $data;
    }
    public function aroundExecute(\Magento\Sales\Controller\AbstractController\Reorder $subject, callable $proceed){
        $result = $this->orderLoader->load($subject->getRequest());
        if ($result instanceof \Magento\Framework\Controller\ResultInterface) {
            return $result;
        }
        $order = $this->registry->registry('current_order');
        $resultRedirect = $this->resultRedirectFactory->create();
        $cart = $this->cart;
        $items = $order->getItemsCollection();
        $flg = 0;
        $url = '';

        foreach ($items as $item) {
            try{
                $productId = $item->getProductId();
                if ($this->helper->isEnableCustomPrice() && $this->helper->isCustomPrice($productId) == '1'){
                    $url = $this->helper->getProductPageUrl($productId);
                    $flg = 1;
                    break;
                }else {
                    $cart->addOrderItem($item);
                }
            }catch (\Magento\Framework\Exception\LocalizedException $e) {
                if ($this->cart->getUseNotice(true)) {
                    $this->messageManager->addNoticeMessage($e->getMessage());
                } else {
                    $this->messageManager->addErrorMessage($e->getMessage());
                }
                return $resultRedirect->setPath('*/*/history');
            } catch (\Exception $e) {
                $this->messageManager->addExceptionMessage(
                    $e,
                    __('We can\'t add this item to your shopping cart right now.')
                );
                return $resultRedirect->setPath('checkout/cart');
            }
        }

        if ($flg){
            $this->messageManager->addNoticeMessage(__('Please enter the price and add to cart to reorder.'));
            return $resultRedirect->setPath($url);
        }
        $cart->save();
        return $resultRedirect->setPath('checkout/cart');
    }
}
