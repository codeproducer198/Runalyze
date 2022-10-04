<?php

namespace Runalyze\Tests\Parser\Activity\Data;

use Runalyze\Parser\Activity\Common\Data\ActiveRoundCalculator;
use Runalyze\Parser\Activity\Common\Data\ActivityData;
use Runalyze\Parser\Activity\Common\Data\ContinuousData;
use Runalyze\Parser\Activity\Common\Data\Pause\Pause;
use Runalyze\Parser\Activity\Common\Data\Pause\PauseCollection;
use Runalyze\Parser\Activity\Common\Data\Round\Round;
use Runalyze\Parser\Activity\Common\Data\Round\RoundCollection;

class ActiveRoundCalculatorTest extends \PHPUnit\Framework\TestCase
{
    protected $ContinuousData;

    public function setUp() : void {
        $this->ContinuousData = new ContinuousData();

        // create 10 samples
        $this->ContinuousData->Time = range(1, 10);
        $this->ContinuousData->HeartRate = range(80, 170, 10);
    }

    public function testBreakInMiddle() {
        // idx  time    hr      round   active
        // 0    1       80      1       t
        // 1    2       90      1       t
        // 2    3       100     1       t
        // 3    4       110     2       f       break
        // 4    5       120     2       f       break
        // 5    6       130     3       t
        // 6    7       140     3       t
        // 7    8       150     3       t
        // 8    9       160     3       t
        // 9    10      170     3       t

        $rounds = new RoundCollection();
        $rounds->add(new Round(1, 3, true));
        $rounds->add(new Round(0, 2, false)); // break
        $rounds->add(new Round(1, 5, true));

        $underTest = new ActiveRoundCalculator($this->ContinuousData, $rounds);

        $this->assertEquals(128, $underTest->calcAvgHeartRate());
    }

    public function testBreakSmallDiffsTimeAndRounds() {
        // idx  time    hr      round   active
        // 0    1       80      1       t
        // 1    2       90      1       t
        // 2    3       100     1       t
        // 3    4       110     2       f       break
        // 4    5       120     2       f       break
        // 5    6       130     3       t
        // 6    7       140     3       t
        // 7    8       150     3       t
        // 8    9       160     3       t
        // 9    10      170     3       t

        $rounds = new RoundCollection();
        $rounds->add(new Round(1, 2.8, true));
        $rounds->add(new Round(0, 1.8, false)); // break
        $rounds->add(new Round(1, 5.4, true));

        $underTest = new ActiveRoundCalculator($this->ContinuousData, $rounds);

        $this->assertEquals(128, $underTest->calcAvgHeartRate());
    }

    public function testBreakOneTimeSmallDiffsTimeAndRounds() {
        // idx  time    hr      round   active
        // 0    1       80      1       t
        // 1    2       90      1       t
        // 2    3       100     1       t
        // 3    4       110     2       t
        // 4    5       120     2       f       break
        // 5    6       130     3       t
        // 6    7       140     3       t
        // 7    8       150     3       t
        // 8    9       160     3       t
        // 9    10      170     3       t

        $rounds = new RoundCollection();
        $rounds->add(new Round(1, 3.8, true));
        $rounds->add(new Round(0, 0.8, false)); // break
        $rounds->add(new Round(1, 5.4, true));

        $underTest = new ActiveRoundCalculator($this->ContinuousData, $rounds);

        $this->assertEquals(126, $underTest->calcAvgHeartRate());
    }

    public function testBreakStartEnd() {
        // idx  time    hr      round   active
        // 0    1       80      1       f       break
        // 1    2       90      1       f       break
        // 2    3       100     1       f       break
        // 3    4       110     2       t
        // 4    5       120     2       t
        // 5    6       130     3       f       break
        // 6    7       140     3       f       break
        // 7    8       150     3       f       break
        // 8    9       160     3       f       break
        // 9    10      170     3       f       break

        $rounds = new RoundCollection();
        $rounds->add(new Round(1, 3, false)); // break
        $rounds->add(new Round(0, 2, true));
        $rounds->add(new Round(1, 5, false)); // break

        $underTest = new ActiveRoundCalculator($this->ContinuousData, $rounds);

        $this->assertEquals(115, $underTest->calcAvgHeartRate());
    }

    public function test2RoundsOnlyBreak() {
        // idx  time    hr      round   active
        // 0    1       80      1       f
        // 1    2       90      1       f
        // 2    3       100     1       f
        // 3    4       110     2       f
        // 4    5       120     2       f
        // 5    6       130     2       f
        // 6    7       140     2       f
        // 7    8       150     2       f
        // 8    9       160     2       f
        // 9    10      170     2       f

        $rounds = new RoundCollection();
        $rounds->add(new Round(0, 3, false)); // break
        $rounds->add(new Round(0, 7, false)); // break

        $underTest = new ActiveRoundCalculator($this->ContinuousData, $rounds);

        $this->assertNull($underTest->calcAvgHeartRate());
    }

    public function testOneRoundOnlyBreak() {
        // idx  time    hr      round   active
        // 0    1       80      1       f
        // 1    2       90      1       f
        // 2    3       100     1       f
        // 3    4       110     2       f
        // 4    5       120     2       f
        // 5    6       130     2       f
        // 6    7       140     2       f
        // 7    8       150     2       f
        // 8    9       160     2       f
        // 9    10      170     2       f

        $rounds = new RoundCollection();
        $rounds->add(new Round(0, 10, false)); // break

        $underTest = new ActiveRoundCalculator($this->ContinuousData, $rounds);

        $this->assertNull($underTest->calcAvgHeartRate());
    }

    public function testOneBreakAtEnd() {
        // idx  time    hr      round   active
        // 0    1       80      1       t
        // 1    2       90      1       t
        // 2    3       100     1       t
        // 3    4       110     2       t
        // 4    5       120     2       t
        // 5    6       130     3       t
        // 6    7       140     3       t
        // 7    8       150     3       t
        // 8    9       160     3       t
        // 9    10      170     3       f       break

        $rounds = new RoundCollection();
        $rounds->add(new Round(1, 9.2, true));
        $rounds->add(new Round(0, 0.8, false)); // break

        $underTest = new ActiveRoundCalculator($this->ContinuousData, $rounds);

        $this->assertEquals(120, $underTest->calcAvgHeartRate());

        $rounds = new RoundCollection();
        $rounds->add(new Round(1, 8.8, true));
        $rounds->add(new Round(0, 1.2, false)); // break

        $underTest = new ActiveRoundCalculator($this->ContinuousData, $rounds);

        $this->assertEquals(120, $underTest->calcAvgHeartRate());
    }
}
