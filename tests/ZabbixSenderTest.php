<?php

namespace Fliix\ZabbixSender\Tests;

use Fliix\ZabbixSender\ZabbixSender;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ZabbixSenderTest extends TestCase
{
	public function testGetOptionReturnsConfiguredAndDefaultValues(): void
	{
		$sender = new ZabbixSender([
			'server' => '127.0.0.1',
			'host' => 'TEST-HOST',
		]);

		self::assertSame('TEST-HOST', $sender->getOption('host'));
		self::assertSame('fallback', $sender->getOption('not-set', 'fallback'));
	}

	public function testSendInBatchModeReturnsTrueWithoutExecution(): void
	{
		$sender = new ZabbixSender([
			'server' => '127.0.0.1',
			'host' => 'TEST-HOST',
		]);

		$result = $sender->batch()->send('custom.key', '42');

		self::assertTrue($result);
	}

	public function testGetLastResponseInfoThrowsDuringBatchMode(): void
	{
		$sender = new ZabbixSender([
			'server' => '127.0.0.1',
			'host' => 'TEST-HOST',
		]);

		$sender->batch();

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Unable to get last response info during batch processing.');

		$sender->getLastResponseInfo();
	}
}
