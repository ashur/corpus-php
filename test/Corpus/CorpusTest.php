<?php

/*
 * This file is part of Corpus
 */
namespace Corpus;

use PHPUnit\Framework\TestCase;

class CorpusTest extends TestCase
{
	/**
	 * @expectedException	DomainException
	 */
	public function test_createFromJSONEncodedFile_withInvalidDomainThrowsException()
	{
		$items = ['aioli', 'ajvar', 'amba'];
		$data = [
			'description'	=> 'A list of condiments',
			'toppings'	=> $items
		];
		$json = json_encode( $data );

		$corpusFileStub = $this->createMock( \Cranberry\Filesystem\File::class );
		$corpusFileStub
			->method( 'getContents' )
			->willReturn( $json );

		$corpus = Corpus::createFromJSONEncodedFile( $corpusFileStub, 'condiments', 'garnishes' );
	}

	/**
	 * @expectedException	InvalidArgumentException
	 */
	public function test_createFromJSONEncodedFile_withInvalidJSONThrowsException()
	{
		$corpusFileStub = $this->createMock( \Cranberry\Filesystem\File::class );
		$corpusFileStub
			->method( 'getContents' )
			->willReturn( 'this is not valid JSON ' . microtime( true ) );

		$corpus = Corpus::createFromJSONEncodedFile( $corpusFileStub, 'condiments' );
	}

	public function test_createFromJSONEncodedFile_withVariadicSelectors()
	{
		$fruits = ['apple','banana','pear'];
		$grains = ['barley','millet','oatmeal'];

		$data = [
			'description' => 'A list of condiments',
			'data' => [
				'fruits' => $fruits,
				'grains' => $grains
			]
		];
		$json = json_encode( $data );

		$corpusFileStub = $this->createMock( \Cranberry\Filesystem\File::class );
		$corpusFileStub
			->method( 'getContents' )
			->willReturn( $json );

		$corpusFileStub
			->method( 'getBasename' )
			->willReturn( 'condiments' );	// This stub method is simulating a
											// SplFileInfo::getBasename('.json')

		$corpus = Corpus::createFromJSONEncodedFile( $corpusFileStub, 'data', 'fruits' );

		$this->assertEquals( $fruits, $corpus->getAllItems() );
	}

	/**
	 * @expectedException	\RuntimeException
	 */
	public function test_createFromJSONEncodedFile_withSelectionOfNonArray_throwsException()
	{
		$data = [
			'description' => 'A list of condiments',
			'data' => [
				'fruits' => ['apple','banana','pear']
			]
		];
		$json = json_encode( $data );

		$corpusFileStub = $this->createMock( \Cranberry\Filesystem\File::class );
		$corpusFileStub
			->method( 'getContents' )
			->willReturn( $json );

		$corpusFileStub
			->method( 'getBasename' )
			->willReturn( 'condiments' );	// This stub method is simulating a
											// SplFileInfo::getBasename('.json')

		$corpus = Corpus::createFromJSONEncodedFile( $corpusFileStub, 'description' );
	}

	public function test_getAllItems()
	{
		$items = ['aioli', 'ajvar', 'amba'];
		$corpusName = 'name-' . microtime( true );
		$corpus = new Corpus( $corpusName, $items );

		$this->assertEquals( $items, $corpus->getAllItems() );
	}

	public function test_getName()
	{
		$corpusName = 'name-' . microtime( true );
		$corpus = new Corpus( $corpusName, [] );

		$this->assertEquals( $corpusName, $corpus->getName() );
	}

	public function test_getRandomItem()
	{
		$items = ['aioli', 'ajvar', 'amba'];
		$corpusName = 'name-' . microtime( true );
		$corpus = new Corpus( $corpusName, $items );

		$this->assertTrue( in_array( $corpus->getRandomItem(), $items ) );
	}
}
