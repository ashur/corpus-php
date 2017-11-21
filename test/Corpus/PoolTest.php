<?php

/*
 * This file is part of Corpus
 */
namespace Corpus;

use PHPUnit\Framework\TestCase;

class PoolTest extends TestCase
{
	public function test_addCorpus()
	{
		$corpus = new Corpus( 'foo', ['bar'] );

		$pool = new Pool();
		$pool->addCorpus( $corpus );

		$history = new History();
		$item = $pool->getRandomItem( $history );

		$this->assertEquals( 'bar', $item );
	}

	public function test_getRandomItem_ignoresExhaustedCorpora()
	{
		$corpora[] = new Corpus( 'fruits', ['apple','banana'] );
		$corpora[] = new Corpus( 'vegetables', ['broccoli','carrot'] );

		$historyItems = ['fruits' => ['apple'], 'vegetables' => ['broccoli','carrot']];
		$history = new History( $historyItems );

		$this->assertFalse( $history->hasDomainItem( 'fruits', 'banana' ) );

		$pool = new Pool();
		foreach( $corpora as $corpus )
		{
			$pool->addCorpus( $corpus );
		}

		$item = $pool->getRandomItem( $history );

		$this->assertEquals( 'banana', $item );
		$this->assertTrue( $history->hasDomainItem( 'fruits', 'banana' ) );
	}

	public function test_getRandomItem_withExhaustedPool_removesAllDomains()
	{
		$fruitItems = ['apple','banana'];
		$vegetableItems = ['broccoli','carrot'];

		$corpora[] = new Corpus( 'fruits', $fruitItems );
		$corpora[] = new Corpus( 'vegetables', $vegetableItems );

		$historyItems = ['fruits' => $fruitItems, 'vegetables' => $vegetableItems];
		$history = new History( $historyItems );

		$pool = new Pool();
		foreach( $corpora as $corpus )
		{
			$pool->addCorpus( $corpus );
		}

		$poolItems = array_merge( $fruitItems, $vegetableItems );
		for( $i=0; $i<count( $poolItems); $i++ )
		{
			$item = $pool->getRandomItem( $history );
			$this->assertContains( $item, $poolItems );
		}
	}

	/**
	 * @expectedException	UnderflowException
	 */
	public function test_getRandomItem_withEmptyCorpusPool_throwsException()
	{
		$pool = new Pool();
		$history = new History();
		$pool->getRandomItem( $history );
	}
}
