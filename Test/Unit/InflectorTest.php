<?php

namespace Test;

use Activerecord\Inflector;

class InflectorTest
        extends \PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->inflector = Inflector::instance();
    }

    public function tearDown()
    {

    }

    public function testUnderscorify()
    {
        $this->assertEquals('rm__name__bob',
                $this->inflector->variablize('rm--name  bob'));
        $this->assertEquals('One_Two_Three',
                $this->inflector->underscorify('OneTwoThree'));
    }

    public function testTableize()
    {
        $this->assertEquals('angry_people',
                $this->inflector->tableize('AngryPerson'));
        $this->assertEquals('my_sqls', $this->inflector->tableize('MySQL'));
    }

}