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

	public function test_getRandomItem_addsItemToHistory()
	{
		$corpusName = 'name-' . microtime( true );
		$corpusItems = ['aioli', 'ajvar', 'amba'];
		$corpus = new Corpus( $corpusName, $corpusItems );

		/* One Corpus item remains unused */
		$historyItems = ['aioli', 'ajvar'];
		$history = new History( [$corpusName => $historyItems] );

		$this->assertFalse( $history->hasDomainItem( $corpusName, 'amba' ) );

		$randomCorpusItem = $corpus->getRandomItem( $history );

		$this->assertTrue( $history->hasDomainItem( $corpusName, 'amba' ) );
	}

	public function test_getRandomItem_returnsItemNotFoundInHistory()
	{
		$corpusName = 'name-' . microtime( true );
		$corpusItems = ['aioli', 'ajvar', 'amba'];
		$corpus = new Corpus( $corpusName, $corpusItems );

		/* One Corpus item remains unused */
		$historyItems = ['aioli', 'ajvar'];
		$history = new History( [$corpusName => $historyItems] );

		$randomCorpusItem = $corpus->getRandomItem( $history );

		$this->assertEquals( 'amba', $randomCorpusItem );
	}

	public function test_getRandomItem_callsHistory_removeDomain_whenExhausted()
	{
		$corpusName = 'name-' . microtime( true );
		$corpusItems = ['aioli', 'ajvar', 'amba'];
		$corpus = new Corpus( $corpusName, $corpusItems );

		/* Ensure that Corpus is exhausted */
		$historyItems = $corpusItems;

		$historyMock = $this
			->getMockBuilder( History::class )
			->disableOriginalConstructor()
			->setMethods( ['getAllDomainItems','hasDomain', 'removeDomain'] )
			->getMock();
		$historyMock
			->method( 'hasDomain' )
			->willReturn( true );
		$historyMock
			->method( 'getAllDomainItems' )
			->willReturn( $historyItems );

		$this->assertTrue( $corpus->isExhausted( $historyMock ) );

		$historyMock
			->expects( $this->once() )
			->method( 'removeDomain' );

		$corpus->getRandomItem( $historyMock );
	}

	public function test_isExhausted_callsHistory_hasDomain()
	{
		$corpusName = 'name-' . microtime( true );
		$corpus = new Corpus( $corpusName, ['aioli', 'ajvar', 'amba'] );

		/* Corpus::isExhausted should call History::hasDomain */
		$historyMock = $this
			->getMockBuilder( History::class )
			->disableOriginalConstructor()
			->setMethods( ['hasDomain'] )
			->getMock();
		$historyMock
			->expects( $this->once() )
			->method( 'hasDomain' )
			->with( $corpusName );

		/* If the Corpus domain is not present in History, the Corpus is not
		   exhausted. */
		$historyMock
			->method( 'hasDomain' )
			->willReturn( false );

		$isExhausted = $corpus->isExhausted( $historyMock );

		$this->assertFalse( $isExhausted );
	}

	public function provider_isExhausted_diffsCorpusAndHistoryItems() : array
	{
		$corpusItems = ['aioli', 'ajvar', 'amba'];
		return [
			[$corpusItems, [], false],
			[$corpusItems, ['aioli'], false],
			[$corpusItems, ['chutney','guacamole','ketchup'], false],

			[$corpusItems, ['aioli', 'amba', 'ajvar'], true],
			[$corpusItems, ['aioli', 'ajvar', 'amba'], true],
			[$corpusItems, ['aioli', 'ajvar', 'amba', 'ketchup'], true],
		];
	}

	/**
	 * @dataProvider	provider_isExhausted_diffsCorpusAndHistoryItems
	 */
	public function test_isExhausted_diffsCorpusAndHistoryItems( array $corpusItems, array $historyItems, bool $shouldBeExhausted )
	{
		$corpusName = 'name-' . microtime( true );
		$corpus = new Corpus( $corpusName, $corpusItems );

		/* Corpus::isExhausted should call History::getAllDomainItems */
		$historyMock = $this
			->getMockBuilder( History::class )
			->disableOriginalConstructor()
			->setMethods( ['getAllDomainItems','hasDomain'] )
			->getMock();
		$historyMock
			->method( 'hasDomain' )
			->willReturn( true );
		$historyMock
			->method( 'getAllDomainItems' )
			->willReturn( $historyItems );
		$historyMock
			->expects( $this->once() )
			->method( 'getAllDomainItems' )
			->with( $corpusName );

		$isExhausted = $corpus->isExhausted( $historyMock );

		$this->assertEquals( $shouldBeExhausted, $isExhausted );
	}
}
