<?php

namespace Fliix\ZabbixSender\Tests\Resolver;

use Fliix\ZabbixSender\Resolver\DataResolver;
use Fliix\ZabbixSender\ZabbixSenderInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

class DataResolverTest extends TestCase
{
	public function testResolveUsesSenderHostAsDefault(): void
	{
		$sender = $this->createStub(ZabbixSenderInterface::class);
		$sender
			->method('getOption')
			->willReturnCallback(static fn (string $option, mixed $default = null): mixed => $option === 'host' ? 'APP-HOST' : $default);

		$resolved = DataResolver::resolve([
			'key' => 'item.key',
			'value' => '42',
		], $sender);

		self::assertSame('APP-HOST', $resolved['host']);
		self::assertSame('item.key', $resolved['key']);
		self::assertSame('42', $resolved['value']);
	}

	public function testResolveRequiresHostWhenSenderHasNoHostConfigured(): void
	{
		$sender = $this->createStub(ZabbixSenderInterface::class);
		$sender->method('getOption')->willReturn(null);

		$this->expectException(MissingOptionsException::class);

		DataResolver::resolve([
			'key' => 'item.key',
			'value' => '42',
		], $sender);
	}
}
