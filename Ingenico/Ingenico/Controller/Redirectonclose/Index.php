<?php

namespace Ingenico\Ingenico\Controller\Redirectonclose;

use Magento\Checkout\Model\Session;

class Index extends \Magento\Framework\App\Action\Action
{
	protected $_checkoutSession;

	function __construct(
		Session $checkoutSession,
		\Magento\Framework\App\Action\Context $context

	) {
		$this->checkoutSession = $checkoutSession;
		parent::__construct($context);
	}

	public function execute()
	{
		$this->checkoutSession->restoreQuote();
		$quote = $this->checkoutSession->getQuote();
		$quote->setIsActive(true);
		echo json_encode(['success' => true]);
	}
}
