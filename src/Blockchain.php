<?php

declare(strict_types=1);

namespace Dmyers\BlockChains;

use stdClass;

class Blockchain
{
	const JSONFLAGS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

	protected $path = '';
	protected $chain = [];
	protected $initTransaction = 'init';
	protected $initPOW = 'First Block';
	protected $hashAlgo = 'sha256';

	public function __construct(string $path)
	{
		/* chain file */
		$this->path = $path;

		$this->read();
	}

	public function read()
	{
		if (!file_exists($this->path)) {
			$this->createFile();
		}

		$JsonChain = json_decode(file_get_contents($this->path));

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new \Exception('JSON file "' . $this->path . '" is not valid JSON.');
		}

		$this->chain = [];

		foreach ($JsonChain as $JsonObject) {
			$this->chain[] = new Block(
				$JsonObject->index,
				$JsonObject->timestamp,
				$JsonObject->{'proof-of-work'},
				$JsonObject->transaction,
				$JsonObject->hashid,
			);
		}
	}

	public function createFile()
	{
		$block = $this->firstBlock();

		$block->hashid = $this->hash($block->transaction, $block);

		$this->chain[] = $block;

		$this->write();
	}

	public function write()
	{
		$array = [];

		foreach ($this->chain as $block) {
			$array[] = $block->asObj();
		}

		file_put_contents($this->path, json_encode($array, self::JSONFLAGS));
	}

	public function add(string $transaction, string $pow)
	{
		$block = $this->makeBlock($transaction, $pow);

		$block->hashid = $this->hash($block->transaction, $this->getLastBlock());

		$this->chain[] = $block;

		$this->write();
	}

	public function verify(): bool
	{
		foreach ($this->chain as $index => $block) {
			$previousBlock = ($index === 0) ? $this->firstBlock() : $this->getByIndex($block->index - 1);

			if ($this->hash($block->transaction, $previousBlock) !== $block->hashid) {
				throw new \Exception('Verification Failed Record Index: ' . $block->index, $block->index);
			}
		}

		return true;
	}

	public function getById(string $hashid)
	{
		$foundBlock = null;

		foreach ($this->chain as $block) {
			if ($block->hashid == $hashid) {
				$foundBlock = $block;
				break;
			}
		}

		if ($foundBlock === null) {
			throw new \Exception('Could not find the block with the hash id of ' . $hashid);
		}

		return $foundBlock;
	}

	public function getByIndex(int $index)
	{
		$foundBlock = null;

		foreach ($this->chain as $block) {
			if ($block->index == $index) {
				$foundBlock = $block;
				break;
			}
		}

		if ($foundBlock === null) {
			throw new \Exception('Could not find the block with the index of ' . $index);
		}

		return $foundBlock;
	}

	public function getLastIndex(): int
	{
		$block = $this->getLastBlock();

		return $block->index;
	}

	public function getNextIndex(): int
	{
		return $this->getLastIndex() + 1;
	}

	public function getLastBlock()
	{
		return end($this->chain);
	}

	public function makeBlock(string $transaction, string $pow): Block
	{
		return new Block($this->getNextIndex(), null, $pow, $transaction, '');
	}

	public function firstBlock(): Block
	{
		return new Block(0, null, $this->initPOW, $this->initTransaction, '');
	}

	public function hash(string $transaction, Block $previousBlock): string
	{
		return hash($this->hashAlgo, $previousBlock->hashid . $previousBlock->index . $previousBlock->timestamp . $transaction);
	}
} /* end class */
