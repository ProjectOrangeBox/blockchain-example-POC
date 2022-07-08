<?php

declare(strict_types=1);

namespace Dmyers\BlockChains;

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

	public function getChain(): array
	{
		$chainArray = [];

		foreach ($this->chain as $block) {
			$chainArray[] = $block->asObj();
		}

		return $chainArray;
	}

	public function add(string $transaction, string $pow)
	{
		$block = $this->makeBlock($this->getNextIndex(), $transaction, $pow);

		$block->hashid = $this->hash($block, $this->getLastBlock());

		$this->chain[] = $block;

		$this->write();
	}

	public function verify(): bool
	{
		foreach ($this->chain as $block) {
			$previousBlock = ($block->index === 0) ? $this->makeBlock(0, $this->initTransaction, $this->initPOW) : $this->getByIndex($block->index - 1);

			if ($this->hash($block, $previousBlock) !== $block->hashid) {
				throw new BlockChainCorruptException('Verification Failed Record Index: ' . $block->index, $block->index);
			}
		}

		return true;
	}

	public function getById(string $hashid): Block
	{
		$foundBlock = null;

		foreach ($this->chain as $block) {
			if ($block->hashid == $hashid) {
				$foundBlock = $block;
				break;
			}
		}

		if ($foundBlock === null) {
			throw new BlockChainException('Could not find the block with the hash id of ' . $hashid);
		}

		return $foundBlock;
	}

	public function getByIndex(int $index): Block
	{
		$foundBlock = null;

		foreach ($this->chain as $block) {
			if ($block->index == $index) {
				$foundBlock = $block;
				break;
			}
		}

		if ($foundBlock === null) {
			throw new BlockChainException('Could not find the block with the index of ' . $index);
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

	public function getLastBlock(): Block
	{
		return end($this->chain);
	}

	/* protected */

	protected function makeBlock(int $index, string $transaction, string $pow): Block
	{
		return new Block($index, null, $pow, $transaction, '');
	}

	protected function hash(Block $block, Block $previousBlock): string
	{
		return hash($this->hashAlgo, $previousBlock->hashid . $previousBlock->index . $previousBlock->timestamp . $block->transaction);
	}

	protected function read()
	{
		if (!file_exists($this->path)) {
			$this->createFile();
		}

		$JsonChain = json_decode(file_get_contents($this->path));

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new BlockChainException('The file "' . $this->path . '" is not valid JSON.');
		}

		$this->chain = [];

		foreach ($JsonChain as $JsonObject) {
			$this->chain[] = new Block($JsonObject->index, $JsonObject->timestamp, $JsonObject->{'proof-of-work'}, $JsonObject->transaction, $JsonObject->hashid);
		}
	}

	protected function createFile()
	{
		$block = $this->makeBlock(0, $this->initTransaction, $this->initPOW);

		$block->hashid = $this->hash($block, $block);

		$this->chain[] = $block;

		$this->write();
	}

	protected function write(): bool
	{
		$jsonString = json_encode($this->getChain(), self::JSONFLAGS);

		return (bool)file_put_contents($this->path, $jsonString, LOCK_EX);
	}
} /* end class */
