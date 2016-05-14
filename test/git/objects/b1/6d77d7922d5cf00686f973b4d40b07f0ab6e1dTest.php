<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of 6d77d7922d5cf00686f973b4d40b07f0ab6e1dTest
 *
 * @author mark weisser <mark at whizbangdevelopers.com>
 */
class 6d77d7922d5cf00686f973b4d40b07f0ab6e1dTest
        extends PHPUnit_Framework_TestCase
{

    /**
     * @var \RemoteWebDriver
     */
    protected $webDriver;

    public function setUp()
    {
        $capabilities = array(
            \WebDriverCapabilityType::BROWSER_NAME => 'firefox');
        $this->webDriver = RemoteWebDriver::create('http://localhost:4444/wd/hub',
                        $capabilities);
    }

    public function tearDown()
    {
        $this->webDriver->close();
    }

    protected $url = 'http://www.netbeans.org/';

    public function testSimple()
    {
        $this->webDriver->get($this->url);
        // checking that page title contains word 'NetBeans'
        $this->assertContains('NetBeans', $this->webDriver->getTitle());
    }

}