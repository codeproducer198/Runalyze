<?php

namespace Runalyze\Parser\Activity\Common\Data;

use Runalyze\Parser\Activity\Common\Data\Pause\PauseCollection;
use Runalyze\Parser\Activity\Common\Data\Round\RoundCollection;
use Runalyze\Parser\Activity\Common\Data\Round\RoundCollectionFiller;
use Runalyze\Parser\Activity\Common\Filter\FilterCollection;
use Runalyze\Activity;
use Runalyze\Data\RPE;

class ActivityDataContainer
{
    /** @var Metadata */
    public $Metadata;

    /** @var ActivityData */
    public $ActivityData;

    /** @var ContinuousData */
    public $ContinuousData;

    /** @var ContinuousDataAdapter */
    protected $ContinuousDataAdapter;

    /** @var RoundCollection */
    public $Rounds;

    /** @var PauseCollection */
    public $Pauses;

    /** @var PauseCollection */
    public $PausesToApply;

    /** @var FitDetails */
    public $FitDetails;

    /** @var WeatherData */
    public $WeatherData;

    /** @var array */
    public $RRIntervals = [];

    public function __construct()
    {
        $this->Metadata = new Metadata();
        $this->ActivityData = new ActivityData();
        $this->ContinuousData = new ContinuousData();
        $this->ContinuousDataAdapter = new ContinuousDataAdapter($this->ContinuousData);
        $this->Rounds = new RoundCollection();
        $this->Pauses = new PauseCollection();
        $this->PausesToApply = new PauseCollection();
        $this->FitDetails = new FitDetails();
        $this->WeatherData = new WeatherData();
    }

    public function __clone()
    {
        $this->Metadata = clone $this->Metadata;
        $this->ActivityData = clone $this->ActivityData;
        $this->ContinuousData = clone $this->ContinuousData;
        $this->ContinuousDataAdapter = new ContinuousDataAdapter($this->ContinuousData);
        $this->Rounds = clone $this->Rounds;
        $this->Pauses = clone $this->Pauses;
        $this->PausesToApply = clone $this->PausesToApply;
        $this->FitDetails = clone $this->FitDetails;
        $this->WeatherData = clone $this->WeatherData;
    }

    public function completeContinuousData()
    {
        $this->ContinuousDataAdapter->filterUnwantedZeros();
        $this->ContinuousDataAdapter->clearEmptyArrays();
        $this->ContinuousDataAdapter->calculateDistancesIfRequired();
        $this->ContinuousDataAdapter->correctCadenceIfRequired();
        $this->ContinuousDataAdapter->correctPreNullGpsIfRequired();

        // TSC: On the Fenix 6 sometimes the last heart-rate is NULL. Correct this here to get the last NOT NULL value and put it on the last NULL's.
        $this->ContinuousDataAdapter->correctArraySurroundedNulls($this->ContinuousData->HeartRate);
        // TSC: On old Mapjack altitudes sometimes the first value is NULL. Correct it.
        $this->ContinuousDataAdapter->correctArraySurroundedNulls($this->ContinuousData->Altitude);

        $this->completeRoundsIfRequired();
        $this->clearRoundsIfOnlyOneRoundIsThere();
        $this->applyPauses();
    }

    public function completeActivityData()
    {
        $this->ActivityData->completeFromContinuousData($this->ContinuousData, $this->Rounds);
        $this->ActivityData->completeFromRounds($this->Rounds);
        $this->ActivityData->completeFromPauses($this->Pauses);

        // #TSC: set average-temp from the clock/FIT data if available
        if(!empty(($this->ActivityData->AvgTemperature))) {
            // first use the session (or battery) attribute
            $this->WeatherData->Temperature = $this->ActivityData->AvgTemperature;
        } else {
            // second calc the avg from the temp's
            $this->WeatherData->Temperature = $this->ContinuousDataAdapter->getAverageTemperatur();
        }

        $fitSelfEvalEffortUsed = false;
        // check that the type belongs to sport "running" not needed here; will be checked further in ActivityDataContainerToActivityContextConverter.tryToSetTypeFor
        if (empty($this->Metadata->getTypeName()) && !empty($this->FitDetails)) {
            // #TSC set RG=Regeneration Run
            if ($this->FitDetails->SelfEvaluationPerceivedEffort == 1 // anstrengung=sehr leicht=1
                && $this->FitDetails->SelfEvaluationFeeling == Activity\SelfEvaluationFeeling::VERY_STRONG) { // gefuehl=sehr stark=5
                $this->Metadata->setTypeName('RG');
                $fitSelfEvalEffortUsed = true;
            } else if($this->FitDetails->SelfEvaluationPerceivedEffort == 1 // anstrengung=sehr leicht=1
                && $this->FitDetails->SelfEvaluationFeeling == Activity\SelfEvaluationFeeling::STRONG) { // gefuehl=stark=4
                // #TSC GA=Basic Endurance=Grundlagen Ausdauer
                $this->Metadata->setTypeName('GA');
                $fitSelfEvalEffortUsed = true;
            } else if($this->FitDetails->SelfEvaluationPerceivedEffort == 10 // anstrengung=maximum=10
                && $this->FitDetails->SelfEvaluationFeeling == Activity\SelfEvaluationFeeling::VERY_WEAK) { // gefuehl=sehr schwach=1
                // #TSC TR=tempo run
                $this->Metadata->setTypeName('TR');
                $fitSelfEvalEffortUsed = true;
            }
        }

        // #TSC: map FIT SelfEvaluationPerceivedEffort to RPE
        if(!$fitSelfEvalEffortUsed && !empty($this->FitDetails->SelfEvaluationPerceivedEffort)) {
            $this->ActivityData->RPE = RPE::getString($this->FitDetails->SelfEvaluationPerceivedEffort);
        }

        // #TSC if a Pace-Goal run, set it if no other type is already set
        if (empty($this->Metadata->getTypeName()) && !empty($this->ActivityData->PaceGoal)) {
            // if PG not exists, no error and no mapping will be done
            $this->Metadata->setTypeName('PG');
        }
    }

    public function filterActivityData(FilterCollection $filter)
    {
        $filter->filter($this);
    }

    protected function completeRoundsIfRequired()
    {
        if (!$this->Rounds->isEmpty() && !empty($this->ContinuousData->Time) && !empty($this->ContinuousData->Distance)) {
            if ($this->Rounds->getTotalDuration() == 0) {
                (new RoundCollectionFiller($this->Rounds))->fillTimesFromArray(
                    $this->ContinuousData->Time,
                    $this->ContinuousData->Distance
                );
            } elseif ($this->Rounds->getTotalDistance() == 0.0) {
                (new RoundCollectionFiller($this->Rounds))->fillDistancesFromArray(
                    $this->ContinuousData->Time,
                    $this->ContinuousData->Distance
                );
            }


            // #TSC check if rounds includes intervals
            // check that the type belongs to sport "running" not needed here; will be checked further in ActivityDataContainerToActivityContextConverter.tryToSetTypeFor
            if (empty($this->Metadata->getTypeName()) && $this->Rounds->hasIntervalRounds()) {
                // search type by short-cut - it must be configured as short-cut "IT" in the master-data
                $this->Metadata->setTypeName('IT');
            }
        }
    }

    protected function clearRoundsIfOnlyOneRoundIsThere()
    {
        if (!$this->Rounds->isEmpty() && $this->Rounds->count() == 1) {
            $this->Rounds->clear();
        }
    }

    protected function applyPauses()
    {
        if (!$this->PausesToApply->isEmpty() && !empty($this->ContinuousData->Time)) {
            $this->Pauses = $this->ContinuousDataAdapter->applyPauses($this->PausesToApply);
            $this->PausesToApply->clear();

            $this->ContinuousDataAdapter->reIndexArrays();
        }
    }
}
