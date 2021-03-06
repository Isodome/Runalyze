<?php

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2013-06-29 at 22:23:03.
 */
class ElevationCalculatorTest extends PHPUnit_Framework_TestCase {
	/**
	 * @var ElevationCalculator
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() {}

	/**
	 * @covers ElevationCalculator::getElevation
	 * @covers ElevationCalculator::getElevationUp
	 * @covers ElevationCalculator::getElevationDown
	 * @covers ElevationCalculator::calculateElevation
	 */
	public function testValuesSimple() {
		$Method = new ElevationMethod();
		$Method->set(ElevationMethod::NONE);
		$C = new ElevationCalculator(array(0, 10, 17, 14, 20, 3, 11, 0), $Method, 0);
		$C->calculateElevation();

		$this->assertEquals( 31, $C->getElevation() );
		$this->assertEquals( 31, $C->getElevationUp() );
		$this->assertEquals( 31, $C->getElevationDown() );
	}

	public function testValuesOnlyUp() {
		$Method = new ElevationMethod();
		$Method->set(ElevationMethod::NONE);
		$C = new ElevationCalculator(array(0, 10, 17, 20, 35), $Method, 0);
		$C->calculateElevation();

		$this->assertEquals( 35, $C->getElevation() );
		$this->assertEquals( 35, $C->getElevationUp() );
		$this->assertEquals( 0, $C->getElevationDown() );
	}

	public function testValuesOnlyDown() {
		$Method = new ElevationMethod();
		$Method->set(ElevationMethod::NONE);
		$C = new ElevationCalculator(array(0, -10, -17, -20, -35), $Method, 0);
		$C->calculateElevation();

		$this->assertEquals( 35, $C->getElevation() );
		$this->assertEquals( 0, $C->getElevationUp() );
		$this->assertEquals( 35, $C->getElevationDown() );
	}

	/**
	 * @covers ElevationCalculator::runAlgorithm
	 * @covers ElevationCalculator::runAlgorithmTreshold
	 */
	public function testAlgorithmTreshold() {
		$Method = new ElevationMethod();
		$Method->set(ElevationMethod::TRESHOLD);
		$C = new ElevationCalculator(array(0, 2, 4, 6, 5, 7, 4, 8, 10, 0), $Method, 0);
		$C->calculateElevation();

		$this->assertEquals( 14, $C->getElevation() );
		$this->assertEquals( array(0, 6, 5, 7, 4, 10, 0), $C->getElevationPointsWeeded());
		$this->assertEquals( array(0, 3, 4, 5, 6, 8, 9), $C->getIndicesOfElevationPointsWeeded());


		$C->setTreshold(1);
		$C->calculateElevation();

		$this->assertEquals( 12, $C->getElevation() );
		$this->assertEquals( array(0, 6, 4, 10, 0), $C->getElevationPointsWeeded());
		$this->assertEquals( array(0, 3, 6, 8, 9), $C->getIndicesOfElevationPointsWeeded());


		$C->setTreshold(2);
		$C->calculateElevation();

		$this->assertEquals( 10, $C->getElevation() );
		$this->assertEquals( array(0, 6, 10, 0), $C->getElevationPointsWeeded());
		$this->assertEquals( array(0, 3, 8, 9), $C->getIndicesOfElevationPointsWeeded());
	}

	/**
	 * @covers ElevationCalculator::runAlgorithm
	 * @covers ElevationCalculator::runAlgorithmDouglasPeucker
	 */
	public function testAlgorithmDouglasPeucker() {
		$Method = new ElevationMethod();
		$Method->set(ElevationMethod::DOUGLAS_PEUCKER);
		$C = new ElevationCalculator(array(0, 2, 4, 6, 5, 7, 4, 8, 10, 0), $Method, 0);
		$C->calculateElevation();

		$this->assertEquals( 14, $C->getElevation() );
		$this->assertEquals( array(0, 6, 5, 7, 4, 8, 10, 0), $C->getElevationPointsWeeded());
		$this->assertEquals( array(0, 3, 4, 5, 6, 7, 8, 9), $C->getIndicesOfElevationPointsWeeded());


		$C->setTreshold(1);
		$C->calculateElevation();

		$this->assertEquals( 14, $C->getElevation() );
		$this->assertEquals( array(0, 6, 5, 7, 4, 10, 0), $C->getElevationPointsWeeded());
		$this->assertEquals( array(0, 3, 4, 5, 6, 8, 9), $C->getIndicesOfElevationPointsWeeded());


		$C->setTreshold(2);
		$C->calculateElevation();

		$this->assertEquals( 13, $C->getElevation() );
		$this->assertEquals( array(0, 6, 7, 4, 10, 0), $C->getElevationPointsWeeded());
		$this->assertEquals( array(0, 3, 5, 6, 8, 9), $C->getIndicesOfElevationPointsWeeded());
	}

	/**
	 * @covers ElevationCalculator::runAlgorithm
	 * @covers ElevationCalculator::runAlgorithmDouglasPeucker
	 */
	public function testAlgorithmDouglasPeuckerComplicatedIndices() {
		$Method = new ElevationMethod();
		$Method->set(ElevationMethod::DOUGLAS_PEUCKER);
		$C = new ElevationCalculator(array(0, 2, 4, 6, 15, 27, 25, 18, 13, 58, 95, 94, 91, 100, 105, 127, 15, 125, 67, 65, 0), $Method, 10);
		$C->calculateElevation();

		$this->assertEquals( array(0, 6, 27, 13, 95, 91, 127, 15, 125, 67, 65, 0), $C->getElevationPointsWeeded());
		$this->assertEquals( array(0, 3, 5, 8, 10, 12, 15, 16, 17, 18, 19, 20), $C->getIndicesOfElevationPointsWeeded());
	}
}