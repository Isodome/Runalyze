<?php

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2014-09-15 at 20:35:10.
 */
class GenderTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var Gender
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		$this->object = new Gender;
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() {
		
	}

	/**
	 * @covers Gender::hasGender
	 * @covers Gender::isMale
	 * @covers Gender::isFemale
	 */
	public function testHasGender() {
		$this->assertFalse( $this->object->hasGender() );
		$this->assertFalse( $this->object->isMale() );
		$this->assertFalse( $this->object->isFemale() );

		$this->object->set( Gender::MALE );
		$this->assertTrue( $this->object->hasGender() );
		$this->assertTrue( $this->object->isMale() );
		$this->assertFalse( $this->object->isFemale() );

		$this->object->set( Gender::FEMALE );
		$this->assertTrue( $this->object->hasGender() );
		$this->assertFalse( $this->object->isMale() );
		$this->assertTrue( $this->object->isFemale() );
	}

}
