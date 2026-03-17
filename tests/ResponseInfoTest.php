<?php

namespace Fliix\ZabbixSender\Tests;

use Fliix\ZabbixSender\ResponseInfo;
use PHPUnit\Framework\TestCase;

class ResponseInfoTest extends TestCase
{
	public function testParsesResponseInfoString(): void
	{
		$info = new ResponseInfo('processed 1 failed 2 total 3 seconds spent 0.123456');

		self::assertSame(1, $info->getProcessed());
		self::assertSame(2, $info->getFailed());
		self::assertSame(3, $info->getTotal());
		self::assertSame(0.123456, $info->getSpent());
	}
}
