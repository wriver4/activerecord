<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Test\Activerecord;

use Activerecord\Inflector;

/**
 * Description of InflectorTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class InflectorTest
        extends PHPUnit_Framework_TestCase
{

    public function setUp()
    {
        $this->inflector = Inflector::instance();
    }

    public function tearDown()
    {

    }

    public function test_underscorify()
    {
        $this->assertEquals('rm__name__bob',
                $this->inflector->variablize('rm--name  bob'));
        $this->assertEquals('One_Two_Three',
                $this->inflector->underscorify('OneTwoThree'));
    }

    public function test_tableize()
    {
        $this->assertEquals('angry_people',
                $this->inflector->tableize('AngryPerson'));
        $this->assertEquals('my_sqls', $this->inflector->tableize('MySQL'));
    }

    public function test_keyify()
    {
        $this->assertEquals('building_type_id',
                $this->inflector->keyify('BuildingType'));
    }

}