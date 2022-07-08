<?php

declare(strict_types=1);

namespace Dmyers\BlockChains;

/**
 * Simple Block Chain example
 * 
 * This would need to lock and unlock the block chain file 
 * while generating and writing a new entry
 * so it wouldn't work for production but, it's a nice proof of concept of
 * the ideas behind block chain
 */

class Blockchain
{
	const JSONFLAGS = JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT;

	protected $path = '';
	protected $chain = [];
	protected $initTransaction = 'init';
	protected $initPOW = 'First Block';
	protected $hashAlgo = 'sha256';

	/**
	 * Method __construct
	 *
	 * @param string $path [file path to block chain json file]
	 *
	 * @return Blockchain
	 */
	public function __construct(string $path)
	{
		/* chain file */
		$this->path = $path;

		if (!file_exists($this->path)) {
			$this->createFile();
		}

		$this->read();
	}

	/**
	 * Method add
	 *
	 * @param string $transaction [string representation of transaction]
	 * @param string $pow [string representation of proof of work]
	 *
	 * @return bool
	 */
	public function add(string $transaction, string $pow): bool
	{
		$block = $this->getBlock($transaction, $pow);

		$block->hashid = $this->hash($block, $this->getLastBlock());

		$this->chain[] = $block;

		$this->write();

		return true;
	}

	/**
	 * Method verify
	 *
	 * @return bool
	 */
	public function verify(): bool
	{
		foreach ($this->chain as $block) {
			$previousBlock = ($block->index === 0) ? $this->getInitBlock() : $this->getByIndex($block->index - 1);

			if ($this->hash($block, $previousBlock) !== $block->hashid) {
				throw new BlockChainCorruptException('Verification Failed Record Index: ' . $block->index, $block->index);
			}
		}

		return true;
	}

	/**
	 * Method getById
	 *
	 * @param string $hashid [hash id of block chain object]
	 *
	 * @return Block
	 */
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

	/**
	 * Method getByIndex
	 *
	 * @param int $index [index of block chain object]
	 *
	 * @return Block
	 */
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

	/**
	 * Method getLastIndex
	 *
	 * @return int
	 */
	public function getLastIndex(): int
	{
		$block = $this->getLastBlock();

		return $block->index;
	}

	/**
	 * Method getNextIndex
	 *
	 * @return int
	 */
	public function getNextIndex(): int
	{
		return $this->getLastIndex() + 1;
	}

	/**
	 * Method getLastBlock
	 *
	 * @return Block
	 */
	public function getLastBlock(): Block
	{
		return end($this->chain);
	}

	/**
	 * Method getChain
	 *
	 * @return array
	 */
	public function getChain(): array
	{
		$chainArray = [];

		foreach ($this->chain as $block) {
			$chainArray[] = $block->asObj();
		}

		return $chainArray;
	}

	/* protected */

	protected function getBlock(string $transaction, string $pow): Block
	{
		return new Block($this->getNextIndex(), null, $pow, $transaction, '');
	}

	protected function getInitBlock(): Block
	{
		return new Block(0, null, $this->initTransaction, $this->initPOW, '');
	}

	protected function hash(Block $block, Block $previousBlock): string
	{
		return hash($this->hashAlgo, $previousBlock->hashid . $previousBlock->index . $previousBlock->timestamp . $block->transaction);
	}

	protected function read(): int
	{
		$JsonChain = json_decode(file_get_contents($this->path));

		if (json_last_error() !== JSON_ERROR_NONE) {
			throw new BlockChainException('The file "' . $this->path . '" is not valid JSON.');
		}

		$this->chain = [];

		foreach ($JsonChain as $JsonObject) {
			$this->chain[] = new Block($JsonObject->index, $JsonObject->timestamp, $JsonObject->{'proof-of-work'}, $JsonObject->transaction, $JsonObject->hashid);
		}

		return count($this->chain);
	}

	protected function createFile(): int
	{
		$block = $this->getInitBlock();

		$block->hashid = $this->hash($block, $block);

		$this->chain[] = $block;

		$this->write();

		return count($this->chain);
	}

	protected function write(): int
	{
		file_put_contents($this->path, json_encode($this->getChain(), self::JSONFLAGS), LOCK_EX);

		return count($this->chain);
	}
} /* end class */
