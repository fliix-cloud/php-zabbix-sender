<?php

namespace Fliix\ZabbixSender\Tests\Resolver;

use Fliix\ZabbixSender\Resolver\OptionsResolver;
use PHPUnit\Framework\TestCase;

class OptionsResolverTest extends TestCase
{
	public function testResolveSetsDefaultConnectionTypeToUnencrypted(): void
	{
		$options = OptionsResolver::resolve([
			'server' => '127.0.0.1',
		]);

		self::assertSame('unencrypted', $options['connection_type']);
	}

	public function testResolveSetsConnectionTypeToPskWhenTlsConnectIsPsk(): void
	{
		$options = OptionsResolver::resolve([
			'server' => '127.0.0.1',
			'tls-connect' => 'psk',
			'tls-psk-identity' => 'TEST-HOST',
			'tls-psk' => 'bb6586c35826ee585b622dc046ea5dad',
		]);

		self::assertSame('psk', $options['connection_type']);
	}

	public function testResolveAcceptsInternalConnectionTypeOption(): void
	{
		$options = OptionsResolver::resolve([
			'server' => '127.0.0.1',
			'connection_type' => 'psk',
			'tls-connect' => 'psk',
			'tls-psk-identity' => 'TEST-HOST',
			'tls-psk' => 'bb6586c35826ee585b622dc046ea5dad',
		]);

		self::assertSame('psk', $options['connection_type']);
	}
}
