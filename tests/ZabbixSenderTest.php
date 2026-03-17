<?php

namespace Fliix\ZabbixSender\Tests;

use Fliix\ZabbixSender\ZabbixSender;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
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

	public function testPackedDataUsesZabbixHeaderAndPayloadLength(): void
	{
		$sender = new ZabbixSender([
			'server' => '127.0.0.1',
			'host' => 'TEST-HOST',
		]);

		$reflection = new ReflectionClass($sender);
		$method = $reflection->getMethod('packedData');
		$method->setAccessible(true);

		$packet = $method->invoke($sender, [[
			'host' => 'TEST-HOST',
			'key' => 'custom.key',
			'value' => '42',
		]]);

		self::assertSame("ZBXD\1", substr($packet, 0, 5));

		$parts = unpack('Vlow/Vhigh', substr($packet, 5, 8));
		$payload = substr($packet, 13);

		self::assertSame(strlen($payload), $parts['low']);
		self::assertSame(0, $parts['high']);
	}
}
