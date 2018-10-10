<?php

namespace ODBM\Donation\Block\Checkout;
use Magento\Checkout\Model\Session;

class BackgroundImage extends \Magento\Framework\View\Element\Template
{
	protected $_session;
	protected $_productRepositoryFactory;
	protected $_blockFactory;

	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
		Session $session,
		\Magento\Framework\View\Element\BlockFactory $blockFactory,
		 \Magento\Catalog\Api\ProductRepositoryInterfaceFactory $productRepositoryFactory
	)
	{
		parent::__construct($context);
		$this->_session = $session;
		$this->_productRepositoryFactory = $productRepositoryFactory;
		$this->_blockFactory = $blockFactory;
	}

	public function getBackgroundImage() {
		$items = $this->_session->getQuote()->getAllVisibleItems();

		// Get first item, it's a donation
		$item = $items[0];

		$product = $this->_productRepositoryFactory->create()->getById($item->getProductId());
		$image = $product->getData('image');

		$objectManager  = \Magento\Framework\App\ObjectManager::getInstance();
		$helperImport   = $objectManager->get('\Magento\Catalog\Helper\Image');

    $imageUrl = $helperImport
			->init($product, 'product_page_image_large')
			->setImageFile($product->getFile())
			->getUrl();

		$image = $product->getImage();

		return $imageUrl;
	}
}