<?php

namespace Fliix\ZabbixSender\Connection;

use Fliix\ZabbixSender\Resolver\OptionsResolver;
use RuntimeException;

use function is_resource;
use function sprintf;
use function strlen;
use function substr;
use function trim;

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
			'openssl s_client -quiet -connect %s:%d -psk_identity %s -psk %s -tls1_2 -cipher %s',
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

		$errorOutput = '';
		if (isset($this->pipes[2])) {
			$errorOutput = trim(stream_get_contents($this->pipes[2]) ?: '');
		}

		if ($output === false || $output === '') {
			if ($errorOutput !== '') {
				throw new RuntimeException('Failed to read PSK response: ' . $errorOutput);
			}

			return false;
		}

		return $output;
	}

	/**
	 * @inheritDoc
	 */
	public function write(string $data): false|int
	{
		if ($this->process === null || !isset($this->pipes[0])) {
			return false;
		}

		$bytesWritten = 0;
		$dataLength = strlen($data);

		while ($bytesWritten < $dataLength) {
			$written = fwrite($this->pipes[0], substr($data, $bytesWritten));
			if ($written === false || $written === 0) {
				return false;
			}

			$bytesWritten += $written;
		}

		fflush($this->pipes[0]);
		fclose($this->pipes[0]);
		unset($this->pipes[0]);

		return $bytesWritten;
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
