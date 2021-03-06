<?php

namespace JWeiland\Events2\Tests\Unit\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Stefan Froemken <projects@jweiland.net>, jweiland.net
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Exception;
use JWeiland\Events2\Domain\Model\Time;
use JWeiland\Events2\Utility\DateTimeUtility;
use JWeiland\Events2\Utility\EventUtility;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Test case.
 *
 * @author Stefan Froemken <projects@jweiland.net>
 */
class EventUtilityTest extends UnitTestCase
{
    /**
     * @var \JWeiland\Events2\Utility\EventUtility
     */
    protected $subject;

    /**
     * set up.
     */
    public function setUp()
    {
        $this->subject = new EventUtility();
    }

    /**
     * tear down.
     */
    public function tearDown()
    {
        unset($this->subject);
    }

    /**
     * @test
     */
    public function getExceptionsForDayInitiallyReturnsEmptySplObjectStorage()
    {
        $this->assertEquals(
            new \SplObjectStorage(),
            $this->subject->getExceptionsForDay(new Event(), new Day())
        );
    }

    /**
     * dataProvider for getExceptionsForDayWithDateOfTodayReturnsAllExceptionsDefinedToEventRecord.
     */
    public function dataProviderForGetExceptionsForDayWithTypesOfToday()
    {
        $today = new \DateTime();
        $today->modify('midnight');
        $types = array('Add', 'Remove', 'Time', 'Info');

        $data = array();
        foreach ($types as $type) {
            $exceptions = new ObjectStorage();
            $exception = new Exception();
            $exception->setExceptionType($type);
            $exception->setExceptionDate($today);
            $exceptions->attach($exception);
            $expectedExceptions = new \SplObjectStorage();
            $expectedExceptions->attach($exception);
            $data['exception of type '.$type.' of today'] = array($exceptions, $expectedExceptions);
        }

        return $data;
    }

    /**
     * @test
     *
     * @dataProvider dataProviderForGetExceptionsForDayWithTypesOfToday
     */
    public function getExceptionsForDayWithDateOfTodayReturnsAllExceptionsDefinedToEventRecord($eventExceptions, $expectedExceptions)
    {
        $this->subject->injectDateTimeUtility(new DateTimeUtility());

        $currentDay = new \DateTime();
        $currentDay->modify('midnight');
        $day = new Day();
        $day->setDay($currentDay);
        $event = new Event();
        $event->setExceptions($eventExceptions);

        $this->assertEquals(
            $expectedExceptions,
            $this->subject->getExceptionsForDay($event, $day)
        );
    }

    /**
     * dataProvider for getExceptionsForDayWithDateOfYesterdayReturnsEmptySplObjectStorage.
     */
    public function dataProviderForGetExceptionsForDayWithTypesOfYesterday()
    {
        $yesterday = new \DateTime();
        $yesterday->modify('yesterday midnight');
        $types = array('Add', 'Remove', 'Time', 'Info');

        $data = array();
        foreach ($types as $type) {
            $exceptions = new ObjectStorage();
            $exception = new Exception();
            $exception->setExceptionType($type);
            $exception->setExceptionDate($yesterday);
            $exceptions->attach($exception);
            $expectedException = new \SplObjectStorage();
            $data['exception of type '.$type.' of yesterday'] = array($exceptions, $expectedException);
        }

        return $data;
    }

    /**
     * @test
     *
     * @dataProvider dataProviderForGetExceptionsForDayWithTypesOfYesterday
     */
    public function getExceptionsForDayWithDateOfYesterdayReturnsEmptySplObjectStorage($eventExceptions)
    {
        $this->subject->injectDateTimeUtility(new DateTimeUtility());

        $currentDay = new \DateTime();
        $currentDay->modify('midnight');
        $day = new Day();
        $day->setDay($currentDay);
        $event = new Event();
        $event->setExceptions($eventExceptions);

        $this->assertEquals(
            new \SplObjectStorage(),
            $this->subject->getExceptionsForDay($event, $day)
        );
    }

    /**
     * @test
     */
    public function getExceptionsForDayWithRemoveAsTypeResultsInException()
    {
        $currentDay = new \DateTime();
        $currentDay->modify('midnight');
        $day = new Day();
        $day->setDay($currentDay);

        $exceptions = new ObjectStorage();
        $removeException = new Exception();
        $removeException->setExceptionType('Remove');
        $removeException->setExceptionDate($currentDay);
        $exceptions->attach($removeException);
        $addException = new Exception();
        $addException->setExceptionType('Add');
        $addException->setExceptionDate($currentDay);
        $exceptions->attach($addException);

        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $event = new Event();
        $event->setExceptions($exceptions);

        $expectedExceptions = new \SplObjectStorage();
        $expectedExceptions->attach($removeException);

        // here we also test with lower cased type "remove"
        $this->assertEquals(
            $expectedExceptions,
            $this->subject->getExceptionsForDay($event, $day, 'remove')
        );
    }

    /**
     * dataProvider for getExceptionsForDayWithDifferentTypesResultsInEmptySplObjectStorage.
     */
    public function dataProviderForGetExceptionsForDayWithDifferentTypes()
    {
        $today = new \DateTime();
        $today->modify('midnight');
        $types = array('Add', 'Time', 'Info');

        $data = array();
        foreach ($types as $type) {
            $expectedException = new \SplObjectStorage();
            $exceptions = new ObjectStorage();
            // create exception with different type each run
            $exception = new Exception();
            $exception->setExceptionType($type);
            $exception->setExceptionDate($today);
            $exceptions->attach($exception);
            $expectedException->attach($exception);
            // add an invalid exception to test filtering
            $exception = new Exception();
            $exception->setExceptionType('Remove');
            $exception->setExceptionDate($today);
            $exceptions->attach($exception);
            $data['exception of type '.$type.' of today'] = array($exceptions, $type, $expectedException);
        }

        return $data;
    }

    /**
     * @test
     *
     * @dataProvider dataProviderForGetExceptionsForDayWithDifferentTypes
     */
    public function getExceptionsForDayWithDifferentTypesResultsInEmptySplObjectStorage($eventExceptions, $type, $expectedException)
    {
        $currentDay = new \DateTime();
        $currentDay->modify('midnight');
        $day = new Day();
        $day->setDay($currentDay);
        $event = new Event();
        $event->setExceptions($eventExceptions);

        $this->subject->injectDateTimeUtility(new DateTimeUtility());

        $this->assertEquals(
            $expectedException,
            $this->subject->getExceptionsForDay($event, $day, $type)
        );
    }

    /**
     * @test
     */
    public function getExceptionsForDayWithMultipleTypesResultsInExceptionsWithAddAndRemove()
    {
        $today = new \DateTime();
        $today->modify('midnight');

        // create three exceptions
        $exceptions = new ObjectStorage();
        $addException = new Exception();
        $addException->setExceptionType('Add');
        $addException->setExceptionDate($today);
        $removeException = new Exception();
        $removeException->setExceptionType('Remove');
        $removeException->setExceptionDate($today);
        $infoException = new Exception();
        $infoException->setExceptionType('Info');
        $infoException->setExceptionDate($today);
        $exceptions->attach($addException);
        $exceptions->attach($removeException);
        $exceptions->attach($infoException);

        $expectedException = new \SplObjectStorage();
        $expectedException->attach($addException);
        $expectedException->attach($removeException);

        $event = new Event();
        $event->setExceptions($exceptions);
        $day = new Day();
        $day->setDay($today);

        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->assertEquals(
            $expectedException,
            $this->subject->getExceptionsForDay($event, $day, 'add, remove')
        );
    }

    /**
     * Data Provider with invalid values for get times for day.
     */
    public function dataProviderWithInvalidValuesForGetTimesForDay()
    {
        $invalidValue = array();
        $invalidValue['null'] = array(null, null);
        $invalidValue['strings'] = array('Test123', 'Hello');
        $invalidValue['integer'] = array(123, -532);
        $invalidValue['array'] = array(array(123), array('Hello'));
        $invalidValue['wrong object'] = array(new \stdClass(), new \stdClass());

        return $invalidValue;
    }

    /**
     * @test
     *
     * @param mixed $invalidValue
     * @dataProvider dataProviderWithInvalidValuesForGetTimesForDay
     * @expectedException \PHPUnit_Framework_Error
     */
    public function getTimesForDayWithWrongParametersThrowsException($invalidValue)
    {
        $this->subject->getTimesForDay($invalidValue, $invalidValue);
    }

    /**
     * @test
     */
    public function getTimesForDayWithEventIncludingOneExceptionReturnsOneTimeObject()
    {
        $today = new \DateTime();
        $today->modify('midnight');
        $exceptions = new ObjectStorage();
        $time = new Time();
        $time->setTimeBegin('12:34');
        $exception = new Exception();
        $exception->setExceptionDate($today);
        $exception->setExceptionType('Add');
        $exception->setExceptionTime($time);
        $exceptions->attach($exception);
        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time);
        $event = new Event();
        $event->setExceptions($exceptions);
        $day = new Day();
        $day->setDay($today);

        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDay($event, $day)
        );
    }

    /**
     * @test
     */
    public function getTimesForDayWithEventIncludingOneExceptionWithoutTimeReturnsEmptySplObjectStorage()
    {
        $today = new \DateTime();
        $today->modify('midnight');
        $exceptions = new ObjectStorage();
        $exception = new Exception();
        $exception->setExceptionDate($today);
        $exception->setExceptionType('Add');
        $exceptions->attach($exception);
        $expectedTimes = new \SplObjectStorage();
        $event = new Event();
        $event->setExceptions($exceptions);
        $day = new Day();
        $day->setDay($today);

        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDay($event, $day)
        );
    }

    /**
     * @test
     */
    public function getTimesForDayWithEventIncludingDifferentTimesReturnsTwoTimeObjects()
    {
        $today = new \DateTime();
        $today->modify('midnight');
        $differentTimes = new ObjectStorage();
        $expectedTimes = new \SplObjectStorage();

        // add valid time record
        $time = new Time();
        $time->setTimeBegin('12:34');
        $time->setWeekday($today->format('l'));
        $differentTimes->attach($time);
        $expectedTimes->attach($time);

        // add valid time record
        $time = new Time();
        $time->setTimeBegin('04:57');
        $time->setWeekday($today->format('l'));
        $differentTimes->attach($time);
        $expectedTimes->attach($time);

        // add time record with wrong weekday to test if protected function getDifferentTimesForDay works correctly
        $tomorrow = clone $today;
        $tomorrow->modify('tomorrow');
        $time = new Time();
        $time->setTimeBegin('04:57');
        $time->setWeekday($tomorrow->format('l'));
        $differentTimes->attach($time);

        $event = new Event();
        $event->setDifferentTimes($differentTimes);
        $day = new Day();
        $day->setDay($today);

        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDay($event, $day)
        );
    }

    /**
     * @test
     */
    public function getTimesForDayWithEventTimeReturnsOneTimeObject()
    {
        $today = new \DateTime();
        $today->modify('midnight');
        $time = new Time();
        $time->setTimeBegin('12:34');
        $expectedTimes = new \SplObjectStorage();
        $expectedTimes->attach($time);
        $event = new Event();
        $event->setEventTime($time);
        $day = new Day();
        $day->setDay($today);

        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDay($event, $day)
        );
    }

    /**
     * this test also tests the protected method getTimesFromEvent
     * As long as time is not an instance of time the record will not be added to SplObjectStorage.
     *
     * @test
     */
    public function getTimesForDayWithoutEventTimeReturnsEmptySplObjectStorage()
    {
        $today = new \DateTime();
        $today->modify('midnight');
        $event = new Event();
        $day = new Day();
        $day->setDay($today);

        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->assertEquals(
            new \SplObjectStorage(),
            $this->subject->getTimesForDay($event, $day)
        );
    }

    /**
     * @test
     */
    public function getTimesForDayWithEventTimeAndMultipleTimesReturnsCollectionOfTimes()
    {
        $today = new \DateTime();
        $today->modify('midnight');

        $expectedTimes = new \SplObjectStorage();
        $multipleTimes = new ObjectStorage();

        $time = new Time();
        $time->setTimeBegin('12:34');
        $expectedTimes->attach($time);

        $multipleTime = new Time();
        $multipleTime->setTimeBegin('12:34');
        $expectedTimes->attach($multipleTime);
        $multipleTimes->attach($multipleTime);

        $multipleTime = new Time();
        $multipleTime->setTimeBegin('12:34');
        $expectedTimes->attach($multipleTime);
        $multipleTimes->attach($multipleTime);

        $event = new Event();
        $event->setEventTime($time);
        $event->setMultipleTimes($multipleTimes);
        // with following we also test protected function getTimesFromEvent
        $event->setSameDay(true); // only if this is true the times will be merged
        $day = new Day();
        $day->setDay($today);

        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDay($event, $day)
        );
    }

    /**
     * @test
     */
    public function getTimesForDayWithEventTimeAndMultipleTimesReturnsTimeOfEventOnly()
    {
        $today = new \DateTime();
        $today->modify('midnight');

        $expectedTimes = new \SplObjectStorage();
        $multipleTimes = new ObjectStorage();

        $time = new Time();
        $time->setTimeBegin('12:34');
        $expectedTimes->attach($time);

        $multipleTime = new Time();
        $multipleTime->setTimeBegin('12:34');
        $multipleTimes->attach($multipleTime);

        $multipleTime = new Time();
        $multipleTime->setTimeBegin('12:34');
        $multipleTimes->attach($multipleTime);

        $event = new Event();
        $event->setEventTime($time);
        $event->setMultipleTimes($multipleTimes);
        $event->setSameDay(false); // if FALSE, only the time from event will be returned
        $day = new Day();
        $day->setDay($today);

        $this->subject->injectDateTimeUtility(new DateTimeUtility());
        $this->assertEquals(
            $expectedTimes,
            $this->subject->getTimesForDay($event, $day)
        );
    }
}
