<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_TimeSync
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * @category   Zend
 * @package    Zend_TimeSync
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_TimeSync
 */
class Zend_TimeSyncTest extends PHPUnit\Framework\TestCase
{
    public $timeservers = array(
        // invalid servers
        'server_a' => 'ntp://be.foo.bar.org',
        'server_b' => 'sntp://be.foo.bar.org',
        'server_c' => 'sntp://foo:bar@be.foo.bar.org:123',

        // valid servers
        'server_d' => 'ntp://be.pool.ntp.org',
        'server_e' => 'ntp://time.windows.com',
        'server_f' => 'sntp://time-C.timefreq.bldrdoc.gov'
    );

    /**
     * Test for object initialisation
     *
     * @return void
     */
    public function testInitTimeserver()
    {
        $server = new Zend_TimeSync();

        $this->assertInstanceOf(Zend_TimeSync::class, $server);
    }

    /**
     * Test for object initialisation with multiple timeservers
     *
     * @return void
     */
    public function testInitTimeservers()
    {
        $server = new Zend_TimeSync($this->timeservers);
        $result = $server->getServer('server_f');

        $this->assertInstanceOf(Zend_TimeSync_Protocol::class, $result);
    }

    /**
     * Test for object initialisation with a single timeserver that will
     * default to the default scheme (ntp), because no scheme is supplied
     *
     * @return void
     */
    public function testInitDefaultScheme()
    {
        $server = new Zend_TimeSync('time.windows.com', 'windows_time');
        $server->setServer('windows_time');
        $result = $server->getServer();

        $this->assertInstanceOf(Zend_TimeSync_Ntp::class, $result);
    }

    /**
     * Test for object initialisation with a single NTP timeserver
     *
     * @return void
     */
    public function testInitNtpScheme()
    {
        $server = new Zend_TimeSync('ntp://time.windows.com', 'windows_time');
        $server->setServer('windows_time');
        $result = $server->getServer();

        $this->assertInstanceOf(Zend_TimeSync_Ntp::class, $result);
    }

    /**
     * Test for object initialisation with a single SNTP timeserver
     *
     * @return void
     */
    public function testInitSntpScheme()
    {
        $server = new Zend_TimeSync('sntp://time.zend.com', 'windows_time');
        $server->setServer('windows_time');
        $result = $server->getServer();

        $this->assertInstanceOf(Zend_TimeSync_Sntp::class, $result);
    }

    /**
     * Test for object initialisation with an unsupported scheme. This will
     * cause the default scheme to be used (ntp)
     *
     * @return void
     */
    public function testInitUnknownScheme()
    {
        $this->expectException(Zend_TimeSync_Exception::class);
        $server = new Zend_TimeSync('http://time.windows.com', 'windows_time');
    }

    /**
     * Test setting a single option
     *
     * @return void
     */
    public function testSetOption()
    {
        $timeout = 5;

        $server = new Zend_TimeSync();
        $server->setOptions(array('timeout' => $timeout));

        $this->assertEquals($timeout, $server->getOptions('timeout'));
    }

    /**
     * Test setting an array of options
     *
     * @return void
     */
    public function testSetOptions()
    {
        $options = array(
            'timeout' => 5,
            'foo'     => 'bar'
        );

        $server = new Zend_TimeSync();
        $server->setOptions($options);

        $this->assertEquals($options['timeout'], $server->getOptions('timeout'));
        $this->assertEquals($options['foo'], $server->getOptions('foo'));
    }

    /**
     * Test getting an option that is not set
     *
     * @return void
     */
    public function testGetInvalidOptionKey()
    {
        $server = new Zend_TimeSync();

        $this->expectException(Zend_TimeSync_Exception::class);
        $result = $server->getOptions('foobar');
    }

    /**
     * Test marking a none existing timeserver as current
     *
     * @return void
     */
    public function testSetUnknownCurrent()
    {
        $server = new Zend_TimeSync();

        $this->expectException(Zend_TimeSync_Exception::class);
        $server->setServer('unknown_alias');
    }

    /**
     * Test getting the current timeserver when none is set
     *
     * @return void
     */
    public function testGetUnknownCurrent()
    {
        $server = new Zend_TimeSync();

        $this->expectException(Zend_TimeSync_Exception::class);
        $result = $server->getServer();
    }

    /**
     * Test getting a none existing timeserver
     *
     * @return void
     */
    public function testGetUnknownServer()
    {
        $server = new Zend_TimeSync();

        $this->expectException(Zend_TimeSync_Exception::class);
        $result = $server->getServer('none_existing_server_alias');
    }

    /**
     * Test getting a date using the fallback mechanism, will try to
     * return the date from the first server that returns a valid result
     *
     * @return void
     */
    public function testGetDate()
    {
        $server = new Zend_TimeSync($this->timeservers);

        try {
            $result = $server->getDate();
            $this->assertInstanceOf(Zend_Date::class, $result);
        } catch (Zend_TimeSync_Exception $e) {
            $this->assertContains('all timeservers are bogus', $e->getMessage());
        }
    }

    /**
     * Test getting a date from an ntp timeserver
     *
     * @return void
     */
    public function testGetNtpDate()
    {
        $server = new Zend_TimeSync('ntp://time.windows.com', 'time_windows');

        try {
            $result = $server->getDate();
            $this->assertInstanceOf(Zend_Date::class, $result);
        } catch (Zend_TimeSync_Exception $e) {
            $this->assertContains('all timeservers are bogus', $e->getMessage());
        }
    }

    /**
     * Test getting a date from an sntp timeserver
     *
     * @return void
     */
    public function testGetSntpDate()
    {
        $server = new Zend_TimeSync('sntp://time-C.timefreq.bldrdoc.gov');

        try {
            $result = $server->getDate();
            $this->assertInstanceOf(Zend_Date::class, $result);
        } catch (Zend_TimeSync_Exception $e) {
            $this->assertContains('all timeservers are bogus', $e->getMessage());
        }
    }

    /**
     * Test getting a date from an invalid timeserver
     *
     * @return void
     */
    public function testGetInvalidDate()
    {
        $servers = array(
            'server_a' => 'dummy-ntp-timeserver.com',
            'server_b' => 'another-dummy-ntp-timeserver.com'
        );

        $server = new Zend_TimeSync($servers);

        try {
            $result = $server->getDate();
        } catch (Zend_TimeSync_Exception $e) {
            $exceptions = $e->get();

            foreach ($exceptions as $key => $exception) {
                $this->assertInstanceOf(Zend_TimeSync_Exception::class, $exception);
            }
        }
    }

    /**
     * Test walking through the server list
     *
     * @return void
     */
    public function testWalkServers()
    {
        $servers = new Zend_TimeSync($this->timeservers);

        foreach ($servers as $key => $server) {
            $this->assertInstanceOf(Zend_TimeSync_Protocol::class, $server);
        }
    }

    /**
     * Test getting info returned from the server
     *
     * @return void
     */
    public function testGetInfo()
    {
        $server = new Zend_TimeSync('time.windows.com');
        try {
            $date   = $server->getDate();
            $result = $server->getInfo();

            $this->assertTrue(count($result) > 0);
        } catch (Zend_TimeSync_Exception  $e) {
            // nothing
        }
    }
}
