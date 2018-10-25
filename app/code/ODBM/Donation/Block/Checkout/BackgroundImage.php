<?php
namespace ODBM\Donation\Block\Checkout;

class BackgroundImage extends \Magento\Framework\View\Element\Template
{
	protected $_session;
	protected $_productRepositoryFactory;

	public function __construct(
		\Magento\Framework\View\Element\Template\Context $context,
		\Magento\Checkout\Model\Session $session,
		\Magento\Framework\View\Element\BlockFactory $blockFactory,
		\Magento\Catalog\Api\ProductRepositoryInterfaceFactory $productRepositoryFactory,
		\Magento\Catalog\Helper\Image $imageHelper
	)
	{
		parent::__construct($context);
		$this->_session = $session;
		$this->_productRepositoryFactory = $productRepositoryFactory;
		$this->_imageHelper = $imageHelper;
	}

	public function getBackgroundImage() {
		$items = $this->_session->getQuote()->getAllVisibleItems();

		// Get first item, it's a donation
		$item = $items[0];

		$product = $this->_productRepositoryFactory->create()->getById($item->getProductId());
		$image = $product->getData('image');

		$imageUrl = $this->_imageHelper
			->init($product, 'product_page_image_large')
			->setImageFile($product->getFile())
			->getUrl();

		$image = $product->getImage();

		return $imageUrl;
	}
}