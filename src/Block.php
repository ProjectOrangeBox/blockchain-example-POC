<?php

declare(strict_types=1);

namespace Dmyers\BlockChains;

use stdClass;

class Block
{
	protected $timestampFormat = 'D, d M Y H:i:s T';
	protected $data = [];
	protected $properties = [
		'index',
		'timestamp',
		'proof-of-work',
		'transaction',
		'hashid',
	];

	public function __construct(int $index, ?string $timestamp, string $proofOfWork, string $transaction, string $hashid)
	{
		$timestamp = (!$timestamp) ? gmdate($this->timestampFormat) : $timestamp;

		$this->index = $index;
		$this->timestamp = $timestamp;
		$this->{'proof-of-work'} = $proofOfWork;
		$this->transaction = $transaction;
		$this->hashid = $hashid;
	}

	public function __set($name, $value)
	{
		$this->data[$this->exists($name)] = $value;
	}

	public function __get($name)
	{
		return $this->data[$this->exists($name)];
	}

	public function asObj(): stdClass
	{
		$stdClass = new stdClass;

		foreach ($this->properties as $property) {
			$stdClass->$property = $this->data[$property];
		}

		return $stdClass;
	}

	protected function exists(string $name): string
	{
		$lcname = strtolower($name);

		if (!in_array($lcname, $this->properties)) {
			throw new BlockChainException($name . ' Unknown property');
		}

		return $lcname;
	}
} /* end class */
