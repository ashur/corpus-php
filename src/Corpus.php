<?php

/*
 * This file is part of Corpus
 */
namespace Corpus;

use Cranberry\Filesystem\File;

class Corpus
{
	/**
	 * @var	array
	 */
	protected $items=[];

	/**
	 * @var	string
	 */
	protected $name;

	/**
	 * @param	string	$name
	 *
	 * @param	array	$items
	 *
	 * @return	void
	 */
	public function __construct( string $name, array $items )
	{
		$this->name = $name;
		$this->items = $items;
	}

	/**
	 * Factory method for creating Corpus object using a file containing
	 * JSON-encoded data
	 *
	 * @param	Cranberry\Filesystem\File	$file
	 *
	 * @param	array	$selectors	An array of selector strings
	 *
	 * @throws	InvalidArgumentException	If file contents cannot be decoded
	 *
	 * @throws	DomainException				If specified selector not found in data
	 *
	 * @return	Corpus\Corpus
	 */
	static public function createFromJSONEncodedFile( File $file, string ...$selectors ) : self
	{
		$corpusData = json_decode( $file->getContents(), true );
		if( json_last_error() != JSON_ERROR_NONE )
		{
			throw new \InvalidArgumentException( "Could not decode '{$file}': ." . json_last_error_msg(), json_last_error() );
		}

		$corpusName = $file->getBasename( '.json' );

		$selectorsString = implode( '.', $selectors );
		$corpusItems = $corpusData;

		while( ($selector = array_shift( $selectors )) != null )
		{
			if( !array_key_exists( $selector, $corpusItems ) )
			{
				throw new \DomainException( "Selector '{$selectorsString}' not found in '{$file}'." );
			}

			$corpusItems = $corpusItems[$selector];
		}

		if( !is_array( $corpusItems ) )
		{
			$exceptionMessage = sprintf( "Invalid selection '%s': Must be of the type array, %s given.", $selectorsString, gettype( $corpusItems ) );
			throw new \RuntimeException( $exceptionMessage );
		}

		return new self( $corpusName, $corpusItems );
	}

	/**
	 * Returns array of all corpus items
	 *
	 * @return	array
	 */
	public function getAllItems() : array
	{
		return $this->items;
	}

	/**
	 * Returns corpus name
	 *
	 * @return	string
	 */
	public function getName() : string
	{
		return $this->name;
	}

	/**
	 * Returns a random corpus item
	 *
	 * @return	mixed
	 */
	public function getRandomItem()
	{
		$index = array_rand( $this->items );
		return $this->items[$index];
	}
}
