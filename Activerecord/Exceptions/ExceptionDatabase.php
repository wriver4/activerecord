<?php

namespace Activerecord\Exceptions;

use Activerecord\Connection;
use Activerecord\Exceptions\ExceptionActiverecord;

/**
 * Summary of file Database.
 *
 * Description of file Database.
 *
 *
 * @license
 *
 * @copyright
 *
 * @version
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */

/**
 * Thrown when there was an error performing a database operation.
 *
 * The error will be specific to whatever database you are running.
 *
 * @package Activerecord
 */
class ExceptionDatabase
        extends ExceptionActiverecord
{

    public function __construct($adapter_or_string_or_mystery)
    {
        if ($adapter_or_string_or_mystery instanceof Connection)
        {
            parent::__construct(
                    \join(", ",
                            $adapter_or_string_or_mystery->connection->errorInfo()),
                    \intval($adapter_or_string_or_mystery->connection->errorCode()));
        }
        elseif ($adapter_or_string_or_mystery instanceof \PDOStatement)
        {
            parent::__construct(
                    \join(", ", $adapter_or_string_or_mystery->errorInfo()),
                    \intval($adapter_or_string_or_mystery->errorCode()));
        }
        elseif ($adapter_or_string_or_mystery instanceof \PDOStatement)
        {
            parent::__construct(
                    \join(", ", $adapter_or_string_or_mystery->errorInfo()),
                    \intval($adapter_or_string_or_mystery->errorCode()));
        }
        else
        {
            parent::__construct($adapter_or_string_or_mystery);
        }
    }

}
