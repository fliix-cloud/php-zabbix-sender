<?php

namespace Fliix\ZabbixSender\Connection;

use Fliix\ZabbixSender\Resolver\OptionsResolver;
use RuntimeException;

use function is_resource;
use function sprintf;

/**
 * Implements PSK (Pre-Shared Key) TLS connections to Zabbix Server.
 *
 * Uses TLS 1.2 with PSK cipher suites via the system's openssl binary.
 * TLS 1.3 is explicitly disabled because PSK cipher suites (RFC 4279) are
 * not supported in TLS 1.3 and are disabled by default in OpenSSL 3.x.
 *
 * Compatible with Zabbix 7.x.
 *
 * @internal
 */
final class PskConnection implements ConnectionInterface
{
	/**
	 * @var resource|null
	 */
	private $process = null;

	private array $pipes = [];

	private string $command;


	public function __construct(array $options)
	{
		$options = OptionsResolver::resolve($options);

		// Use TLS 1.2 explicitly: PSK cipher suites are part of TLS 1.2 (RFC 4279)
		// and are not available in TLS 1.3. OpenSSL 3.x disables them by default,
		// so we force TLS 1.2 and specify an accepted cipher for Zabbix compatibility.
		$cipher = $options['tls-cipher'] ?? 'PSK-AES256-CBC-SHA';

		$this->command = sprintf(
			'openssl s_client -connect %s:%d -psk_identity %s -psk %s -tls1_2 -cipher %s',
			escapeshellarg($options['server']),
			(int) $options['port'],
			escapeshellarg($options['tls-psk-identity']),
			escapeshellarg($options['tls-psk']),
			escapeshellarg($cipher)
		);
	}

	/**
	 * @inheritDoc
	 */
	public function open(): void
	{
		if ($this->process !== null) {
			throw new RuntimeException('Connection is already open.');
		}

		$descriptors = [
			0 => ['pipe', 'r'], // stdin
			1 => ['pipe', 'w'], // stdout
			2 => ['pipe', 'w'], // stderr
		];

		$this->process = proc_open($this->command, $descriptors, $this->pipes);

		if (!is_resource($this->process)) {
			throw new RuntimeException('Failed to open connection.');
		}
	}

	/**
	 * @inheritDoc
	 */
	public function read(): false|string
	{
		if ($this->process === null || !isset($this->pipes[1])) {
			return false;
		}

		$output = stream_get_contents($this->pipes[1]);
		return $output ?: false;
	}

	/**
	 * @inheritDoc
	 */
	public function write(string $data): false|int
	{
		if ($this->process === null || !isset($this->pipes[0])) {
			return false;
		}

		// Write data to stdin
		$bytesWritten = fwrite($this->pipes[0], $data);
		return $bytesWritten ?: false;
	}

	/**
	 * @inheritDoc
	 */
	public function close(): void
	{
		if ($this->process === null) {
			return;
		}

		foreach ($this->pipes as $pipe) {
			if (is_resource($pipe)) {
				fclose($pipe);
			}
		}

		proc_close($this->process);
		$this->process = null;
		$this->pipes = [];
	}
}
