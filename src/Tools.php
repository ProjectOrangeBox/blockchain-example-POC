<?php

namespace Dmyers\BlockChains;

class Tools
{
	public function randomness(): string
	{
		$sentences = [
			['Don', 'Donna', 'Dwayne', 'Dan', 'Jen', 'Peter', 'Andrew', 'Zack', 'Jake', 'Tom', 'Mike', 'Sally', 'Amanada', 'Grace'],
			['Meyers', 'Gunn', 'White', 'Blackman', 'Cook', 'Jeffers', 'Jefferson', 'Thomas', 'Pidcock', 'Greenman', 'Sanders', 'Whitehead']
		];

		return $sentences[0][rand(0, count($sentences[0]) - 1)] . ' ' . $sentences[1][rand(0, count($sentences[1]) - 1)];
	}
} /* end class */
