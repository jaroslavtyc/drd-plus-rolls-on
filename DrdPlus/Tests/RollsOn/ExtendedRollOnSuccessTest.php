<?php
namespace DrdPlus\Tests\RollsOn;

use Drd\DiceRoll\Roll;
use DrdPlus\RollsOn\BasicRollOnSuccess;
use DrdPlus\RollsOn\ExtendedRollOnSuccess;
use DrdPlus\RollsOn\RollOnQuality;
use DrdPlus\RollsOn\SimpleRollOnSuccess;
use Granam\Tests\Tools\TestWithMockery;

class ExtendedRollOnSuccessTest extends TestWithMockery
{
    /**
     * @test
     * @dataProvider provideSimpleRollsOnSuccessAndResult
     * @param RollOnQuality $expectedRollOnQuality
     * @param array $simpleRollsOnSuccess
     * @param string $expectedResultCode
     * @param bool $expectingSuccess
     */
    public function I_can_use_it(RollOnQuality $expectedRollOnQuality, array $simpleRollsOnSuccess, $expectedResultCode, $expectingSuccess)
    {
        $extendedRollOnSuccessReflection = new \ReflectionClass(ExtendedRollOnSuccess::class);
        $extendedRollOnSuccess = $extendedRollOnSuccessReflection->newInstanceArgs($simpleRollsOnSuccess);

        self::assertSame($expectedRollOnQuality, $extendedRollOnSuccess->getRollOnQuality());
        self::assertSame($expectedResultCode, $extendedRollOnSuccess->getResultCode());
        self::assertSame($expectedResultCode, (string)$extendedRollOnSuccess);
        if ($expectingSuccess) {
            self::assertFalse($extendedRollOnSuccess->isFailed());
            self::assertTrue($extendedRollOnSuccess->isSuccessful());
        } else {
            self::assertTrue($extendedRollOnSuccess->isFailed());
            self::assertFalse($extendedRollOnSuccess->isSuccessful());
        }
    }

    public function provideSimpleRollsOnSuccessAndResult()
    {
        return [
            [ // from single simple roll on success
                $rollOnQuality = $this->createRollOnQuality(5 /* roll value */),
                [
                    $this->createSimpleRollOnSuccess(9 /* difficulty */, $rollOnQuality),
                ],
                'fail',
                false /* expecting failure */
            ],
            [ // from simple roll on success and basic roll on success (which is simple roll also)
                $rollOnQuality = $this->createRollOnQuality(3 /* roll value */),
                [
                    $this->createSimpleRollOnSuccess(2 /* difficulty */, $rollOnQuality),
                    $this->createBasicRollOnSuccess(5 /* difficulty */, $rollOnQuality, true /* is successful */, 'hurray'),
                ],
                'hurray',
                true /* expecting success */
            ],
            [ // from more than three simple rolls on success with two successful and non-sequential difficulty
                $rollOnQuality = $this->createRollOnQuality(2 /* roll value */),
                [
                    $this->createSimpleRollOnSuccess(5 /* difficulty */, $rollOnQuality),
                    $this->createSimpleRollOnSuccess(1 /* difficulty */, $rollOnQuality, true /* success */, 'success'),
                    $this->createSimpleRollOnSuccess(3 /* difficulty */, $rollOnQuality),
                    $this->createSimpleRollOnSuccess(2 /* difficulty */, $rollOnQuality, true /* success */, 'better success'),
                ],
                'better success',
                true /* expecting success */
            ],
        ];
    }

    /**
     * @param $difficulty
     * @param RollOnQuality $rollOnQuality
     * @param $isSuccessful
     * @param $resultCode
     * @return \Mockery\MockInterface|SimpleRollOnSuccess
     */
    private function createSimpleRollOnSuccess($difficulty, RollOnQuality $rollOnQuality, $isSuccessful = false, $resultCode = 'foo')
    {
        return $this->createRollOnSuccess(SimpleRollOnSuccess::class, $difficulty, $rollOnQuality, $isSuccessful, $resultCode);
    }

    private function createRollOnSuccess($class, $difficulty, RollOnQuality $rollOnQuality, $isSuccessful, $resultCode)
    {
        $rollOnSuccess = $this->mockery($class);
        $rollOnSuccess->shouldReceive('getDifficulty')
            ->andReturn($difficulty);
        $rollOnSuccess->shouldReceive('isSuccessful')
            ->andReturn($isSuccessful);
        $rollOnSuccess->shouldReceive('getResultCode')
            ->andReturn($resultCode);
        $rollOnSuccess->shouldReceive('getRollOnQuality')
            ->andReturn($rollOnQuality);

        return $rollOnSuccess;
    }

    /**
     * @param $value
     * @param $rollValue
     * @param $rolledNumbers
     * @return \Mockery\MockInterface|RollOnQuality
     */
    private function createRollOnQuality($value, $rollValue = 'some roll value', $rolledNumbers = ['some', 'rolled', 'numbers'])
    {
        $rollOnQuality = $this->mockery(RollOnQuality::class);
        $rollOnQuality->shouldReceive('getPreconditionsSum')
            ->andReturn('some preconditions sum');
        $rollOnQuality->shouldReceive('getValue')
            ->andReturn($value);
        $rollOnQuality->shouldReceive('getRoll')
            ->andReturn($roll = $this->mockery(Roll::class));
        $roll->shouldReceive('getValue')
            ->andReturn($rollValue);
        $roll->shouldReceive('getRolledNumbers')
            ->andReturn($rolledNumbers);

        return $rollOnQuality;
    }

    /**
     * @param $difficulty
     * @param RollOnQuality $rollOnQuality
     * @param bool $isSuccessful
     * @param string $resultCode
     * @return \Mockery\MockInterface|BasicRollOnSuccess
     */
    private function createBasicRollOnSuccess($difficulty, RollOnQuality $rollOnQuality, $isSuccessful = false, $resultCode = 'foo')
    {
        return $this->createRollOnSuccess(BasicRollOnSuccess::class, $difficulty, $rollOnQuality, $isSuccessful, $resultCode);
    }

    /**
     * @test
     * @expectedException \DrdPlus\RollsOn\Exceptions\ExpectedSimpleRollsOnSuccessOnly
     */
    public function I_can_create_it_only_from_simple_rolls_on_success()
    {
        $rollOnQuality = $this->createRollOnQuality(123);

        new ExtendedRollOnSuccess(
            $this->createSimpleRollOnSuccess(1, $rollOnQuality),
            $this->createBasicRollOnSuccess(2, $rollOnQuality),
            $this->createSimpleRollOnSuccess(3, $rollOnQuality),
            new ExtendedRollOnSuccess($this->createSimpleRollOnSuccess(23, $rollOnQuality))
        );
    }

    /**
     * @test
     * @expectedException \DrdPlus\RollsOn\Exceptions\EveryDifficultyShouldBeUnique
     */
    public function I_can_use_only_unique_difficulties()
    {
        $rollOnQuality = $this->createRollOnQuality(123);

        new ExtendedRollOnSuccess(
            $this->createSimpleRollOnSuccess(1, $rollOnQuality),
            $this->createBasicRollOnSuccess(1, $rollOnQuality)
        );
    }

    /**
     * @test
     * @expectedException \DrdPlus\RollsOn\Exceptions\EverySuccessCodeShouldBeUnique
     */
    public function I_can_use_only_unique_success_codes()
    {
        $rollOnQuality = $this->createRollOnQuality(1);

        new ExtendedRollOnSuccess(
            $this->createSimpleRollOnSuccess(1, $rollOnQuality, true, 'success'),
            $this->createBasicRollOnSuccess(2, $rollOnQuality, true, 'success')
        );
    }

    /**
     * @test
     */
    public function I_can_use_non_unique_success_codes_if_no_success_happens_on_them()
    {
        $rollOnQuality = $this->createRollOnQuality(1);

        $extendedRollOnSuccess = new ExtendedRollOnSuccess(
            $this->createSimpleRollOnSuccess(1, $rollOnQuality, true, 'success'),
            $this->createBasicRollOnSuccess(2, $rollOnQuality, false /* failure for this roll */, 'success' /* code used only on success */)
        );
        self::assertTrue($extendedRollOnSuccess->isSuccessful());
    }

    /**
     * @test
     */
    public function I_can_use_different_instances_with_same_values_of_rolls_on_quality()
    {
        $firstRollOnQuality = $this->createRollOnQuality(1);
        $similarRollOnQuality = $this->createRollOnQuality(1);
        $extendedRollOnSuccess = new ExtendedRollOnSuccess(
            $this->createSimpleRollOnSuccess(1, $firstRollOnQuality),
            $this->createBasicRollOnSuccess(2, $similarRollOnQuality)
        );
        self::assertEquals($similarRollOnQuality, $extendedRollOnSuccess->getRollOnQuality());
    }

    /**
     * @test
     * @dataProvider provideSimpleRollsWithDifferentRollsOnQuality
     * @expectedException \DrdPlus\RollsOn\Exceptions\RollOnQualityHasToBeTheSame
     */
    public function I_can_not_use_different_rolls_on_quality(SimpleRollOnSuccess $firstSimpleRoll, SimpleRollOnSuccess $secondSimpleRoll)
    {
        new ExtendedRollOnSuccess($firstSimpleRoll, $secondSimpleRoll);
    }

    public function provideSimpleRollsWithDifferentRollsOnQuality()
    {
        return [
            [ // different roll on quality value
                $this->createSimpleRollOnSuccess(5, $this->createRollOnQuality(1)),
                $this->createSimpleRollOnSuccess(9, $this->createRollOnQuality(2))
            ],
            [ // different roll on quality roll value
                $this->createSimpleRollOnSuccess(5, $this->createRollOnQuality(1, 1)),
                $this->createSimpleRollOnSuccess(9, $this->createRollOnQuality(1, 2))
            ],
            [ // different roll on quality rolled numbers
                $this->createSimpleRollOnSuccess(5, $this->createRollOnQuality(1, 2, [1, 2])),
                $this->createSimpleRollOnSuccess(9, $this->createRollOnQuality(1, 2, [1, 3]))
            ],
        ];
    }
}