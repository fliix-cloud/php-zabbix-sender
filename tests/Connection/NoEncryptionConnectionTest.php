<?php

namespace Fliix\ZabbixSender\Tests\Connection;

use Fliix\ZabbixSender\Connection\UnencryptedConnection;
use PHPUnit\Framework\TestCase;

class NoEncryptionConnectionTest extends TestCase
{
	public function testReadAndWriteReturnFalseWhenNotOpened(): void
	{
		$connection = new UnencryptedConnection([
			'server' => '127.0.0.1',
		]);

		self::assertFalse($connection->write('payload'));
		self::assertFalse($connection->read());
	}

	public function testCloseIsNoopWhenNotOpened(): void
	{
		$connection = new UnencryptedConnection([
			'server' => '127.0.0.1',
		]);

		$connection->close();

		self::assertFalse($connection->read());
	}
}
