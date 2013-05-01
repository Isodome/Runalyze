<?php

/**
 * Generated by PHPUnit_SkeletonGenerator 1.2.0 on 2013-04-10 at 17:21:17.
 */
class ImporterFiletypeGPXTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var ImporterFiletypeGPX
	 */
	protected $object;

	/**
	 * Sets up the fixture, for example, opens a network connection.
	 * This method is called before a test is executed.
	 */
	protected function setUp() {
		$this->object = new ImporterFiletypeGPX;
	}

	/**
	 * Tears down the fixture, for example, closes a network connection.
	 * This method is called after a test is executed.
	 */
	protected function tearDown() { }

	/**
	 * Test: empty string
	 */
	public function testEmptyString() {
		$this->object->parseString('');

		$this->assertTrue( $this->object->failed() );
		$this->assertEmpty( $this->object->objects() );
		$this->assertNotEmpty( $this->object->getErrors() );
	}

	/**
	 * Test: incorrect xml-file 
	 */
	public function test_notGPX() {
		$this->object->parseString('<any><xml><file></file></xml></any>');

		$this->assertTrue( $this->object->failed() );
		$this->assertEmpty( $this->object->objects() );
		$this->assertNotEmpty( $this->object->getErrors() );
	}

	/**
	 * Test: standard file
	 * Filename: "wrongTime.gpx" 
	 */
	public function test_standard() {
		$this->object->parseFile('../tests/testfiles/gpx/standard.gpx');

		$this->assertFalse( $this->object->hasMultipleTrainings() );
		$this->assertFalse( $this->object->failed() );

		$this->assertEquals( 2295, $this->object->object()->getTimeInSeconds(), '', 30);
		$this->assertEquals( 5.993, $this->object->object()->getDistance(), '', 0.1);

		$this->assertTrue( $this->object->object()->hasArrayAltitude() );
		$this->assertTrue( $this->object->object()->hasPositionData() );
		$this->assertTrue( $this->object->object()->hasArrayDistance() );
		$this->assertTrue( $this->object->object()->hasArrayPace() );
		$this->assertTrue( $this->object->object()->hasArrayTime() );
	}

	/**
	 * Test: with extensions
	 */
	public function test_extensions() {
		$this->object->parseString('<?xml version="1.0" encoding="UTF-8"?>
<gpx xmlns="http://www.topografix.com/GPX/1/1" xmlns:gpxdata="http://www.cluetrust.com/XML/GPXDATA/1/0">
	<trk>
		<trkseg>
			<trkpt lat="50.7748849411" lon="6.1124603078">
				<time>2013-02-04T20:38:00Z</time>
			</trkpt>
			<trkpt lat="50.7749991026" lon="6.1125158798">
				<ele>275</ele>
				<time>2013-02-04T20:38:10Z</time>
				<extensions>
					<gpxdata:hr>125</gpxdata:hr>
					<gpxdata:temp>27</gpxdata:temp>
					<gpxdata:cadence>90</gpxdata:cadence>
				</extensions>
			</trkpt>
			<trkpt lat="50.7749992026" lon="6.1125158798">
				<ele>280</ele>
				<time>2013-02-04T20:38:20Z</time>
				<extensions>
					<gpxdata:hr>120</gpxdata:hr>
					<gpxdata:temp>26</gpxdata:temp>
					<gpxdata:cadence>90</gpxdata:cadence>
				</extensions>
			</trkpt>
		</trkseg>
	</trk>
</gpx>');

		$this->assertFalse( $this->object->hasMultipleTrainings() );
		$this->assertFalse( $this->object->failed() );

		$this->assertEquals( 20, $this->object->object()->getTimeInSeconds() );

		$this->assertEquals( array(10, 20), $this->object->object()->getArrayTime() );
		$this->assertEquals( array(275,280), $this->object->object()->getArrayAltitude() );
		$this->assertEquals( array(125,120), $this->object->object()->getArrayHeartrate() );
	}

}