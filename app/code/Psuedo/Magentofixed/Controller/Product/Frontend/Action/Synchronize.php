<?php
/**
 * Created by PhpStorm.
 * User: ppostma
 * Date: 2019-03-28
 * Time: 19:22
 */

namespace Psuedo\Magentofixed\Controller\Product\Frontend\Action;


class Synchronize
{

	private $context;

	/**
	 * @var Synchronizer
	 */
	private $synchronizer;

	/**
	 * @var JsonFactory
	 */
	private $jsonFactory;

	/**
	 * @param Context $context
	 * @param Synchronizer $synchronizer
	 * @param JsonFactory $jsonFactory
	 */
	public function __construct(
		Context $context,
		Synchronizer $synchronizer,
		JsonFactory $jsonFactory
	) {
		parent::__construct($context, $synchronizer, $jsonFactory);
		$this->context = $context;
		$this->synchronizer = $synchronizer;
		$this->jsonFactory = $jsonFactory;
	}

	/**
	 * This is handle for synchronizing between frontend and backend product actions:
	 *  - visit product page (recently_viewed)
	 *  - compare products (recently_compared)
	 *  - etc...
	 * It comes in next format: [
	 *  'type_id' => 'recently_*',
	 *  'ids' => [
	 *      'product_id' => "$id",
	 *      'added_at' => "JS_TIMESTAMP"
	 *  ]
	 * ]
	 *
	 *
	 * @inheritdoc
	 */
	public function execute()
	{
		$resultJson = $this->jsonFactory->create();

		try {
			$productsData = $this->getRequest()->getParam('ids', []);
			$typeId = $this->getRequest()->getParam('type_id', null);
			$this->synchronizer->syncActions($productsData, $typeId);
		} catch (\Exception $e) {
			$resultJson->setStatusHeader(
				\Zend\Http\Response::STATUS_CODE_400,
				\Zend\Http\AbstractMessage::VERSION_11,
				'Bad Request'
			);
		}

		return $resultJson->setData([]);
	}
}