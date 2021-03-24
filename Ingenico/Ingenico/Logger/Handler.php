<?php

namespace Ingenico\Ingenico\Logger;

use Monolog\Logger;
use Magento\Framework\Filesystem\DriverInterface;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    protected $fileName = null;
    protected $loggerType = Logger::INFO;
    public function __construct(
        DriverInterface $filesystem,
        $filePath = null,
        $fileName = null
    ) {
        $this->fileName = '/var/log/ingenico' . date("Ymd") . '.log';
        parent::__construct($filesystem, $filePath, $fileName);
    }
}
