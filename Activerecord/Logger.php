<?php

use Psr\Log\LogLevel;
use Psr\Log\LoggerInterface;

/**
 * A PSR-3 logger adapter for PHP-ActiveRecord
 */
class Logger
{

    /**
     * The default PSR-3 compatible log level for log entries.
     */
    const DEFAULT_LEVEL = LogLevel::DEBUG;

    /**
     * A PSR-3 logger to delegate logging to.
     *
     * @type LoggerInterface
     */
    private $logger;

    /**
     * The PSR-3 compatible log level to use for log entries.
     *
     * @see LogLevel
     * @type int
     */
    private $level;

    /**
     * Constructor
     *
     * @param LoggerInterface $logger A PSR-3 logger to delegate logging to.
     * @param int $level The PSR-3 compatible log level to use for log entries.
     */
    public function __construct(LoggerInterface $logger,
            $level = self::DEFAULT_LEVEL)
    {
        $this->logger = $logger;
        $this->level = $level ? : self::DEFAULT_LEVEL;
    }

    /**
     * Logs a message to the delegate logger.
     *
     * This method is the PHP-ActiveRecord required log method.
     *
     * @param string $message The message to log.
     * @return void
     */
    public function log($message)
    {
        $this->logger->log($this->level, $message);
    }

}