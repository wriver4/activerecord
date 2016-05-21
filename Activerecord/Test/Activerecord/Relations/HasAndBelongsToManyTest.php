<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Activerecord\Test\Activerecord\Relations;

use Activerecord\Table;
use Activerecord\Exceptions\ExceptionUndefinedProperty;
use Activerecord\Exceptions\ExceptionReadOnly;
use Activerecord\Test\Helpers\DatabaseTest;

/**
 * Description of HasAndBelongsToManyTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class HasAndBelongsToManyTest
        extends DatabaseTest
{

    protected $relationship_name;
    protected $relationship_names = [];

    public function setUp($connection_name = null)
    {
        parent::setUp($connection_name);
    }

    public function tearDown()
    {

    }

}