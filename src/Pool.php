<?php

/*
 * This file is part of Corpus
 */
namespace Corpus;

class Pool
{
	/**
	 * Array of Corpus objects
	 *
	 * @var	array
	 */
	protected $corpora=[];

	/**
	 * Adds a Corpus to the array of corpora
	 *
	 * @param	Corpus\Corpus
	 *
	 * @return	void
	 */
	public function addCorpus( Corpus $corpus )
	{
		$this->corpora[] = $corpus;
	}

	/**
	 * Returns a random item from a random Corpus object
	 *
	 * @param	Corpus\History	$history
	 *
	 * @return	mixed
	 */
	public function getRandomItem( History &$history )
	{
		if( count( $this->corpora ) < 1 )
		{
			throw new \UnderflowException( 'Corpus pool is empty' );
		}

		$corpora = $this->corpora;
		shuffle( $corpora );

		foreach( $corpora as $corpus )
		{
			if( $corpus->isExhausted( $history ) )
			{
				continue;
			}

			return $corpus->getRandomItem( $history );
		}

        /* All corpora are exhausted, remove domains from History */
		foreach( $corpora as $corpus )
		{
			$historyDomain = $corpus->getName();
			$history->removeDomain( $historyDomain );
		}

		return $this->getRandomItem( $history );
	}
}
