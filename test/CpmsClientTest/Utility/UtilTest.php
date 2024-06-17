<?php
namespace CpmsClientTest\Utility;

use CpmsClient\Utility\Util;
use PHPUnit\Framework\TestCase;

/**
 * Class UtilTest
 *
 * @package CpmsClientTest\Utility
 */
class UtilTest extends TestCase
{
    protected $runTestInSeparateProcess = null;

    protected $backupStaticAttributes = null;

    public function testAppendQueryParam(): void
    {
        $url  = 'http://google.com';
        $url2 = 'http://google.com?home=1';
        $url3 = 'google.com?home=1';

        $time = time();

        $output = Util::appendQueryString($url, array('time' => $time));
        $this->assertSame($url . '?time=' . $time, $output);

        $output = Util::appendQueryString($url2, array('time' => $time));
        $this->assertSame($url2 . '&time=' . $time, $output);

        $output = Util::appendQueryString($url3, array('time' => $time));
        $this->assertSame($url2 . '&time=' . $time, $output);
    }

    public function testException(): void
    {
        $runtimeException = new \RuntimeException('My message');
        $exception        = new \Exception('Top exception', 102, $runtimeException);
        $string           = Util::processException($exception);

        $this->assertNotEmpty($string);
    }
}
