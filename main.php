<?php

use Dmyers\BlockChains\Block;
use Dmyers\BlockChains\Tools;
use Dmyers\BlockChains\Blockchain;

require 'vendor/autoload.php';

$tools = new Tools;

$chainFile = __DIR__ . '/chain.json';

unlink($chainFile);

$blockchain = new Blockchain($chainFile);

try {
	$blockchain->verify();

	echo 'Verified' . chr(10);
} catch (Exception $e) {
	var_dump($e->getMessage());
	var_dump($e->getCode());
}

for ($i = 0; $i <= 10; $i++) {
	$blockchain->add($tools->randomness(), 'BC-' . md5(microtime()));
}

try {
	$blockchain->verify();

	echo 'Verified' . chr(10);
} catch (Exception $e) {
	var_dump($e->getMessage());
	var_dump($e->getCode());
}

//var_dump($blockchain->getChain());
