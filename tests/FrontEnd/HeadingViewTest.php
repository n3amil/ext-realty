<?php
/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Test case.
 *
 * @package TYPO3
 * @subpackage tx_realty
 *
 * @author Saskia Metzler <saskia@merlin.owl.de>
 */
class tx_realty_FrontEnd_HeadingViewTest extends tx_phpunit_testcase {
	/**
	 * @var tx_realty_pi1_HeadingView
	 */
	private $fixture;

	/**
	 * @var tx_oelib_testingFramework
	 */
	private $testingFramework;

	protected function setUp() {
		$this->testingFramework = new tx_oelib_testingFramework('tx_realty');
		$this->testingFramework->createFakeFrontEnd();

		/** @var tslib_fe $frontEndController */
		$frontEndController = $GLOBALS['TSFE'];
		$this->fixture = new tx_realty_pi1_HeadingView(
			array('templateFile' => 'EXT:realty/pi1/tx_realty_pi1.tpl.htm'), $frontEndController->cObj
		);
	}

	protected function tearDown() {
		$this->testingFramework->cleanUp();
	}


	/////////////////////////////
	// Testing the heading view
	/////////////////////////////

	/**
	 * @test
	 */
	public function renderReturnsNonEmptyResultForShowUidOfExistingRecord() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'test title'));

		$this->assertNotEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsNoUnreplacedMarkersWhileTheResultIsNonEmpty() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'test title'));

		$result = $this->fixture->render(
			array('showUid' => $realtyObject->getUid())
		);

		$this->assertNotEquals(
			'',
			$result
		);
		$this->assertNotContains(
			'###',
			$result
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsTheRealtyObjectsTitleForValidRealtyObject() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'test title'));

		$this->assertContains(
			'test title',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsTheRealtyObjectsTitleHtmlspecialcharedForValidRealtyObject() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => 'test</br>title'));

		$this->assertContains(
			htmlspecialchars('test</br>title'),
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}

	/**
	 * @test
	 */
	public function renderReturnsEmptyResultForEmptyTitleOfValidRealtyObject() {
		$realtyObject = tx_oelib_MapperRegistry::get('tx_realty_Mapper_RealtyObject')
			->getLoadedTestingModel(array('title' => ''));

		$this->assertEquals(
			'',
			$this->fixture->render(array('showUid' => $realtyObject->getUid()))
		);
	}
}