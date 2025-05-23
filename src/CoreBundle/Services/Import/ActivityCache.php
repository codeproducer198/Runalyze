<?php

namespace Runalyze\Bundle\CoreBundle\Services\Import;

use Runalyze\Bundle\CoreBundle\Entity\Account;
use Runalyze\Bundle\CoreBundle\Entity\Training;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class ActivityCache
{
    /** @var AdapterInterface */
    protected $Cache;

    public function __construct(AdapterInterface $cache)
    {
        $this->Cache = $cache;
    }

    /**
     * @param Training $activity
     * @return string
     */
    public function save(Training $activity)
    {
        $hash = $this->getRandomHash($activity->getAccount());
        $item = $this->Cache->getItem($hash);
        $item->set($activity);

        $this->Cache->save($item);

        return $hash;
    }

    /**
     * @param string|null $hash
     * @param Training|null $activityToMerge
     * @param bool $removerAfterwards
     * @return Training|null
     */
    public function get($hash, Training $activityToMerge = null, $removerAfterwards = false)
    {
        if (null === $hash || '' == $hash) {
            return $activityToMerge;
        }

        /** @var Training $context */
        $activity = $this->Cache->getItem($hash)->get();

        if ($removerAfterwards) {
            $this->remove($hash);
        }

        if (!($activity instanceof Training)) {
            return $activityToMerge;
        }

        if (null !== $activityToMerge) {
            return $this->merge($activity, $activityToMerge);
        }

        return $activity;
    }

    /**
     * @param string $hash
     */
    public function remove($hash)
    {
        $this->Cache->deleteItem($hash);
    }

    /**
     * @param Training $activityFromCache
     * @param Training $activityToMerge
     * @return Training
     */
    public function merge(Training $activityFromCache, Training $activityToMerge)
    {
        $this->mergeActivityDataFromParserThatIsNotInForm($activityFromCache, $activityToMerge);

        $activityToMerge->setRoute($activityFromCache->getRoute());
        $activityToMerge->setTrackdata($activityFromCache->getTrackdata());
        $activityToMerge->setSwimdata($activityFromCache->getSwimdata());
        $activityToMerge->setHrv($activityFromCache->getHrv());
        $activityToMerge->setRaceresult($activityFromCache->getRaceResult());

        $activityToMerge->getAdapter()->setAccountToRelatedEntities();

        return $activityToMerge;
    }

    protected function mergeActivityDataFromParserThatIsNotInForm(Training $activityFromCache, Training $activityToMerge)
    {
        $activityToMerge->setTimezoneOffset($activityFromCache->getTimezoneOffset());
        $activityToMerge->setElapsedTime($activityFromCache->getElapsedTime());
        $activityToMerge->setFitVO2maxEstimate($activityFromCache->getFitVO2maxEstimate());
        $activityToMerge->setFitHrvAnalysis($activityFromCache->getFitHrvAnalysis());
        $activityToMerge->setFitRecoveryTime($activityFromCache->getFitRecoveryTime());
        $activityToMerge->setFitTrainingEffect($activityFromCache->getFitTrainingEffect());
        $activityToMerge->setFitAnaerobicTrainingEffect($activityFromCache->getFitAnaerobicTrainingEffect()); // #TSC: transfer anaerobic eff
        $activityToMerge->setFitPerformanceCondition($activityFromCache->getFitPerformanceCondition());
        $activityToMerge->setFitPerformanceConditionEnd($activityFromCache->getFitPerformanceConditionEnd());
        $activityToMerge->setFitLactateThresholdHR($activityFromCache->getFitLactateThresholdHR()); // #TSC: transfer LTHR
        $activityToMerge->setFitTotalAscent($activityFromCache->getFitTotalAscent()); // #TSC: transfer ascent
        $activityToMerge->setFitTotalDescent($activityFromCache->getFitTotalDescent()); // #TSC: transfer descent
        $activityToMerge->setFitSelfEvaluationFeeling($activityFromCache->getFitSelfEvaluationFeeling()); // #TSC: transfer...
        $activityToMerge->setFitSelfEvaluationPreceivedEffort($activityFromCache->getFitSelfEvaluationPreceivedEffort()); // #TSC: transfer...
        $activityToMerge->setFitLoadPeak($activityFromCache->getFitLoadPeak()); // #TSC: transfer...
        $activityToMerge->setFitRunTime($activityFromCache->getFitRunTime()); // #TSC: transfer...
        $activityToMerge->setFitWalkTime($activityFromCache->getFitWalkTime()); // #TSC: transfer...
        $activityToMerge->setFitStandTime($activityFromCache->getFitStandTime()); // #TSC: transfer...
        $activityToMerge->setFitSecondsInHrZones($activityFromCache->getFitSecondsInHrZones()); // #TSC: transfer...
        $activityToMerge->setAvgRespirationRate($activityFromCache->getAvgRespirationRate()); // #TSC: transfer...
        $activityToMerge->setMaxRespirationRate($activityFromCache->getMaxRespirationRate()); // #TSC: transfer...
        $activityToMerge->setPowerCalculated($activityFromCache->isPowerCalculated());
        $activityToMerge->setGroundcontact($activityFromCache->getGroundcontact());
        $activityToMerge->setGroundcontactBalance($activityFromCache->getGroundcontactBalance());
        $activityToMerge->setVerticalOscillation($activityFromCache->getVerticalOscillation());
        $activityToMerge->setAvgImpactGsLeft($activityFromCache->getAvgImpactGsLeft());
        $activityToMerge->setAvgImpactGsRight($activityFromCache->getAvgImpactGsRight());
        $activityToMerge->setAvgBrakingGsLeft($activityFromCache->getAvgBrakingGsLeft());
        $activityToMerge->setAvgBrakingGsRight($activityFromCache->getAvgBrakingGsRight());
        $activityToMerge->setAvgFootstrikeTypeLeft($activityFromCache->getAvgFootstrikeTypeLeft());
        $activityToMerge->setAvgFootstrikeTypeRight($activityFromCache->getAvgFootstrikeTypeRight());
        $activityToMerge->setAvgPronationExcursionLeft($activityFromCache->getAvgPronationExcursionLeft());
        $activityToMerge->setAvgPronationExcursionRight($activityFromCache->getAvgPronationExcursionRight());
        $activityToMerge->setCreator($activityFromCache->getCreator());
        $activityToMerge->setCreatorDetails($activityFromCache->getCreatorDetails());
        $activityToMerge->setActivityId($activityFromCache->getActivityId());
        $activityToMerge->setPulseAvgActive($activityFromCache->getPulseAvgActive()); // #TSC: transfer hr
        $activityToMerge->setMaxHrUser($activityFromCache->getMaxHrUser()); // #TSC: transfer hr
        $activityToMerge->setHrZoneBounds($activityFromCache->getHrZoneBounds()); // #TSC: transfer hr
        $activityToMerge->setTotalCycles($activityFromCache->getTotalCycles()); // #TSC: transfer
        $activityToMerge->setSplitsAdditional($activityFromCache->getSplitsAdditional()); // #TSC: transfer
        $activityToMerge->setPaceGoal($activityFromCache->getPaceGoal()); // #TSC: transfer

        if (null !== $activityFromCache->isPowerCalculated()) {
            $activityToMerge->setPower($activityFromCache->getPower());
        }
    }

    /**
     * @param Account|null $account
     * @return string
     */
    protected function getRandomHash(Account $account = null)
    {
        return hash('sha256', ($account ? $account->getName() : '').uniqid());
    }
}
