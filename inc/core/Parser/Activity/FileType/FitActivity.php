<?php

namespace Runalyze\Parser\Activity\FileType;

use Runalyze\Activity;
use Runalyze\Activity\LapIntensity;
use Runalyze\Import\Exception\ParserException;
use Runalyze\Parser\Activity\Common\AbstractSingleParser;
use Runalyze\Parser\Activity\Common\Data\Metadata;
use Runalyze\Parser\Activity\Common\Data\Pause\Pause;
use Runalyze\Parser\Activity\Common\Data\Round\Round;
use Runalyze\Parser\Activity\Common\StrtotimeWithLocalTimezoneOffsetTrait;
use Runalyze\Profile\FitSdk\StrokeTypeProfile;
use Runalyze\Profile\Sport\Mapping\FitSdkMapping;

class FitActivity extends AbstractSingleParser
{
    use StrtotimeWithLocalTimezoneOffsetTrait;

    /** @var int [s] */
    const TIME_LIMIT_FOR_TIME_JUMP = 3600;

    /** @var string */
    const APP_ID_RUNSCRIBE_LIGHT = '80,32,146,179,176,221,69,173,137,179,71,42,178,6,10,182';

    /** @var string */
    const APP_ID_RUNSCRIBE_PLUS = '193,245,200,10,90,5,78,138,151,179,253,163,78,149,112,53';

    /** @var array */
    protected $Header = [];

    /** @var array */
    protected $Values = [];

    /** @var int */
    protected $PauseInSeconds = 0;

    /** @var int */
    protected $TimeJumpsInSeconds = 0;

    /** @var bool */
    protected $IsSwimming = false;

    /** @var bool */
    protected $IsPaused = false;

    /** @var bool */
    protected $WasPaused = false;

    /** @var int|bool */
    protected $LastStopTimestamp = false;

    /** @var float [s] */
    protected $CompressedTotalTime = 0.0;

    /** @var float [16m] */
    protected $CompressedTotalDistance16 = 0.0;

    /** @var float [16m] */
    protected $CompressedLastDistance16 = 0.0;

    /** @var string */
    protected $SoftwareVersion = '';

    /** @var array */
    protected $DeveloperFieldMappingForRecord = [
        'Power' => ['power', 1],
        'ContactTime' => ['stance_time', 10],
        'RP_Power' => ['power', 1],
        'RS_Power_AVG' => ['power', 1],
        '0_0_Power' => ['power', 1],
        'RS_ContactTime_L' => ['stance_time_left', 10],
        'RS_ContactTime_R' => ['stance_time_right', 10],
        'RS_Impact_GS_L' => ['impact_gs_left', 1],
        'RS_Impact_GS_R' => ['impact_gs_right', 1],
        'RS_Braking_GS_L' => ['braking_gs_left', 1],
        'RS_Braking_GS_R' => ['braking_gs_right', 1],
        'RS_FootStrike_L' => ['fs_type_left', 1],
        'RS_FootStrike_R' => ['fs_type_right', 1],
        'RS_Pronation_L' => ['pronation_left', 1],
        'RS_Pronation_R' => ['pronation_right', 1],
        'saturated_hemoglobin_percent' => ['smo2_0', 0.1],
        'total_hemoglobin_conc' => ['thb_0', 1]
    ];

    /** @var array */
    protected $NativeFieldMappingForRecord = [
        // TODO: native fields aller verwendeten Arrays
        3 => ['heart_rate'],
        4 => ['cadence', ['default' => 1, 'SPM' => 0.5]],
        5 => ['distance', ['default' => 1, 'm' => 100]],
        7 => ['power'],
        39 => ['vertical_oscillation', ['default' => 1, 'Centimeters' => 100]],
        40 => ['stance_time', ['default' => 1, 'Milliseconds' => 10]],
        54 => [['thb_0', 'thb_1'], ['default' => 100, 'base_type' => ['uint16' => 1]]],
        57 => [['smo2_0', 'smo2_1'], ['default' => 1, 'base_type' => ['uint16' => 0.1]]]
    ];

    /** @var array */
    protected $DeveloperFieldMappingForSession = [];

    /** @var array */
    protected $NativeFieldMappingForSession = [
        9 => ['total_distance', ['default' => 1, 'm' => 100]],
        11 => ['total_calories'],
        44 => ['pool_length', ['default' => 1, 'm' => 100]],
    ];

    /** @var array */
    protected $DeveloperFieldMappingForLap = [];

    /** @var array */
    protected $NativeFieldMappingForLap = [
        9 => ['total_distance', ['default' => 1, 'm' => 100]],
    ];

    /** @var array [developer_data_index => application_id] */
    protected $DeveloperDataAppIds = [];

    // #TSC: time of a "record" row
    protected $RecordTime = [];
    // #TSC: temporary heartrate for swimming. array-index is the lane
    protected $SwimHeartrate = [];
    protected $SwimTemperature = [];

    // temperatur of the battery block (only as fallback)
    protected $batteryTemperature = [];

    // additional infos for strength-training
    protected $fitSplitsAdditionals = null;

    /** @var FitWorkoutStep */
    protected $fitWorkoutStep;

    public function __construct()
    {
        parent::__construct();
        $this->fitSplitsAdditionals = new FitSplitsAdditionals();
        $this->fitWorkoutStep = new FitWorkoutStep();
    }

    public function parse()
    {
        throw new \RuntimeException('FitActivity does not support parse().');
    }

	/**
	 * @see https://github.com/Runalyze/Runalyze/issues/1886
	 */
    public function finishParsing()
    {
        if ('suunto' == $this->Container->Metadata->getCreator()) {
            $this->Container->ActivityData->Duration = end($this->Container->ContinuousData->Time);
            $this->Container->Rounds->add(new Round(
                end($this->Container->ContinuousData->Distance) - $this->Container->Rounds->getTotalDistance(),
                $this->Container->ActivityData->Duration - $this->Container->Rounds->getTotalDuration()
            ));
        }

        // #TSC: set the temporary collected/calculated values of swimming if we have "valid" values collected
        if ($this->IsSwimming) {
            if(array_sum($this->SwimHeartrate) > 0) {
                $this->Container->ContinuousData->HeartRate = $this->SwimHeartrate;
            } else {
                $this->Container->ContinuousData->HeartRate = [];
            }
            if(array_sum($this->SwimTemperature) > 0) {
                $this->Container->ContinuousData->Temperature = $this->SwimTemperature;
            } else {
                $this->Container->ContinuousData->Temperature = [];
            }
        }

        // #TSC: set the 
        if (empty($this->Container->ActivityData->AvgTemperature) && !empty($this->batteryTemperature)) {
            $this->Container->ActivityData->AvgTemperature = array_sum($this->batteryTemperature)/count($this->batteryTemperature);
        }

        // #TSC: Save additional infos of splits (f.e. strong-training) as JSON
        if($this->fitSplitsAdditionals->hasData()) {
            $this->Container->ActivityData->SplitsAdditional = $this->fitSplitsAdditionals->getFinishedSplitsAdditional();
        }
    }

    public function readMetadataForMultiSessionFrom(Metadata $metadata)
    {
        $this->Container->Metadata->setCreator($metadata->getCreator(), $metadata->getCreatorDetails());
        $this->Container->Metadata->setTimestamp(PHP_INT_MAX, $metadata->getTimezoneOffset());
    }

    protected function readDeveloperDataId()
    {
        if ($this->Values['application_id']) {
            $this->DeveloperDataAppIds[(int)$this->Values['developer_data_index'][0]] = $this->Values['application_id'][1];
        }
    }

    protected function readFieldDescription()
    {
        switch ($this->Values['native_mesg_num'][1]) {
            case 'record':
                $this->readFieldDescriptionFor($this->NativeFieldMappingForRecord, $this->DeveloperFieldMappingForRecord);
                break;

            case 'session':
                $this->readFieldDescriptionFor($this->NativeFieldMappingForSession, $this->DeveloperFieldMappingForSession);
                break;

            case 'lap':
                $this->readFieldDescriptionFor($this->NativeFieldMappingForLap, $this->DeveloperFieldMappingForLap);
                break;
        }
    }

    protected function readFieldDescriptionFor(array &$nativeFieldMapping, array &$fieldMapping)
    {
        if (isset($this->Values['developer_data_index']) && isset($this->DeveloperDataAppIds[(int)$this->Values['developer_data_index'][0]])) {
            $this->adjustFieldDescriptionForDeveloperApp($this->DeveloperDataAppIds[(int)$this->Values['developer_data_index'][0]]);
        }

        if (!isset($this->Values['developer_data_index']) || !isset($this->Values['field_name']) || !isset($this->Values['field_definition_number'])) {
            return;
        }

        $devFieldName = str_replace(['"', ' '], ['', '_'], $this->Values['field_name'][0]);
        $fieldName = $this->Values['developer_data_index'][0].'_'.$this->Values['field_definition_number'][0].'_'.$devFieldName;
        $fieldName = preg_replace_callback('/(\W)/i', function(array $char) {
            return sprintf('_%02x_', ord($char[0]));
        }, preg_replace('/(\s+)/i', '_', $fieldName));

        if (
            isset($this->Values['native_field_num']) &&
            isset($nativeFieldMapping[$this->Values['native_field_num'][0]]) &&
            !empty($nativeFieldMapping[$this->Values['native_field_num'][0]][0])
        ) {
            $nativeFieldNum = $this->Values['native_field_num'][0];
            $unitDefinition = str_replace('"', '', $this->Values['units'][0]);

            $mappingKey = $nativeFieldMapping[$nativeFieldNum][0];
            $mappingFactor = isset($nativeFieldMapping[$nativeFieldNum][1]) ? $nativeFieldMapping[$nativeFieldNum][1] : 1;

            if (is_array($mappingFactor)) {
                if (isset($mappingFactor[$unitDefinition])) {
                    $mappingFactor = $mappingFactor[$unitDefinition];
                } elseif (isset($this->Values['fit_base_type_id']) && isset($mappingFactor['base_type']) && isset($mappingFactor['base_type'][$this->Values['fit_base_type_id'][1]])) {
                    $mappingFactor = $mappingFactor['base_type'][$this->Values['fit_base_type_id'][1]];
                } else {
                    $mappingFactor = $mappingFactor['default'];
                }
            }

            if (is_array($mappingKey)) {
                if (empty($mappingKey)) {
                    return;
                }

                $mappingKey = array_shift($nativeFieldMapping[$nativeFieldNum][0]);
            }

            $fieldMapping[$fieldName] = [$mappingKey, $mappingFactor];
        } elseif (isset($fieldMapping[$devFieldName])) {
            $fieldMapping[$fieldName] = $fieldMapping[$devFieldName];
        }
    }

    protected function adjustFieldDescriptionForDeveloperApp($appId)
    {
        if (self::APP_ID_RUNSCRIBE_LIGHT == $appId || self::APP_ID_RUNSCRIBE_PLUS == $appId) {
            $this->adjustFieldDescriptionForRunScribeLight();
        }
    }

    /**
     * @see https://github.com/ScribeLabs/garmin-connect-iq-runscribe-light/blob/master/resources/resources.xml
     */
    protected function adjustFieldDescriptionForRunScribeLight()
    {
        $fieldName = $this->Values['developer_data_index'][0].'_'.$this->Values['field_definition_number'][0].'_';
        $resultingFieldNames = [
            0 => 'impact_gs_left',
            1 => 'braking_gs_left',
            2 => 'fs_type_left',
            3 => 'pronation_left',
            6 => 'impact_gs_right',
            7 => 'braking_gs_right',
            8 => 'fs_type_right',
            9 => 'pronation_right',
            12 => 'power'
        ];

        if (isset($resultingFieldNames[(int)$this->Values['field_definition_number'][0]])) {
            $this->DeveloperFieldMappingForRecord[$fieldName] = [
                $resultingFieldNames[(int)$this->Values['field_definition_number'][0]],
                1.0
            ];
        }
    }

    /**
     * @param string $line
     */
    public function parseLine($line)
    {
        if ('=' == $line[0]) {
            if (empty($this->Header)) {
                $this->setHeaderFrom($line);
            } else {
                $this->interpretCurrentValues();

                $this->Header = [];
                $this->Values = [];
            }
        } elseif ('-' == $line[0]) {
            $this->addValueFrom($line);
        }
    }

    /**
     * @param string $line
     */
    protected function setHeaderFrom($line)
    {
        foreach (explode(' ', substr($line, 2)) as $info) {
            $info = explode('=', $info);
            $this->Header[$info[0]] = $info[1];
        }
    }

    /**
     * @param string $line
     */
    protected function addValueFrom($line)
    {
        $line = substr($line, 4);
        $values = explode('=', $line);

        if (count($values) == 3) {
            $this->Values[$values[0]] = [$values[1], $values[2]];
        } elseif (count($values) >= 2) {
            $this->Values[$values[0]] = [$values[1]];
        }
    }

    protected function interpretCurrentValues()
    {
        if (isset($this->Header['NAME'])) {
            switch ($this->Header['NAME']) {
                case 'file_id':
                    $this->readFileId();
                    break;

                case 'file_creator':
                    break;

                case 'device_info':
                    $this->readDeviceInfo();
                    break;

                case 'sport':
                    $this->readSport();
                    break;

                case 'event':
                    $this->readEvent();
                    break;

                case 'record':
                    $this->readRecord();
                    break;

                case 'hrv':
                    $this->readHRV();
                    break;

                case 'lap':
                    $this->readLap();
                    break;

                case 'session':
                    $this->readSession();
                    break;

                case 'length':
                    $this->readLength();
                    break;

                case 'developer_data_id':
                    $this->readDeveloperDataId();
                    break;

                case 'field_description':
                    $this->readFieldDescription();
                    break;

                case 'battery':
                    $this->readBattery(); // #TSC
                    break;

                case 'activity':
                    break;

                case 'user_profile':
                    $this->readUserProfile();
                    break;

                case 'set': // #TSC a "set" of Krafttraining are interpreded as laps
                    $this->readSet();
                    break;

                case 'workout': // #TSC
                    $this->readWorkout();
                    break;

                case 'workout_step': // #TSC
                    $this->fitWorkoutStep->collectWorkoutstep($this->Values);
                    break;

                case 'split': // #TSC
                    $this->readSplit();
                    break;

                case 'time_in_zone': // #TSC
                    $this->readTimeInZone();
                    break;
            }
        } elseif (isset($this->Header['NUMBER'])) {
            switch ($this->Header['NUMBER']) {
                case 79:
                    $this->readUndocumentedUserData();
                    break;

                case 140:
                    $this->readUndocumentedDataBlob140();
                    break;
            }
        }
    }

    protected function readFileId()
    {
        if (isset($this->Values['type']) && $this->Values['type'][1] != 'activity') {
            throw new ParserException('FIT file is not specified as activity.');
        }

        if (isset($this->Values['time_created'])) {
            $this->Container->Metadata->setTimestampAndTimezoneOffsetWithUtcFixFrom((string)$this->Values['time_created'][1]);
        }

        if (isset($this->Values['manufacturer'])) {
            $this->Container->Metadata->setCreator($this->Values['manufacturer'][1]);
        }
    }

    protected function readDeviceInfo()
    {
        if (isset($this->Values['device_index']) && $this->Values['device_index'][0] == 0) {
            if (isset($this->Values['garmin_product'])) {
                $this->Container->Metadata->setCreator($this->Values['garmin_product'][1]);
            }

            if (isset($this->Values['software_version'])) {
                $this->SoftwareVersion = $this->Values['software_version'][1];

                $this->Container->Metadata->setCreator($this->Container->Metadata->getCreator(), 'firmware '.$this->SoftwareVersion);
            }
        }
    }

    protected function readSession()
    {
        $this->mapDeveloperFieldsToNativeFieldsFor($this->DeveloperFieldMappingForSession);

        // #TSC swiming or STRENGTH_TRAINING have a "special" belastungs-time
        if (($this->IsSwimming 
                || $this->Container->Metadata->getInternalSportId() == \Runalyze\Profile\Sport\SportProfile::STRENGTH_TRAINING
                || $this->Container->Metadata->getInternalSportId() == \Runalyze\Profile\Sport\SportProfile::HIIT_CARDIO)
                && isset($this->Values['total_active_time'])) {
            // #TSC: if special activity and "total_active_time" is available, this is the real swim time without rests (this was introduced with a new FW-version)
            $this->Container->ActivityData->Duration += round($this->Values['total_active_time'][0] / 1e3);
        } else {
            // if no "new" total_active_time or "no swimming" use the "default" time
            if (isset($this->Values['total_timer_time'])) {
                $this->Container->ActivityData->Duration += round($this->Values['total_timer_time'][0] / 1e3);
            }
        }

        if (isset($this->Values['total_elapsed_time'])) {
            $this->Container->ActivityData->ElapsedTime += round($this->Values['total_elapsed_time'][0] / 1e3);
        }

        if (isset($this->Values['total_distance'])) {
            $this->Container->ActivityData->Distance += round($this->Values['total_distance'][0] / 1e5, 3);
        }

        if (isset($this->Values['total_calories'])) {
            $this->Container->ActivityData->EnergyConsumption += $this->Values['total_calories'][0];
        }

        if (isset($this->Values['total_strokes'])) {
            $this->Container->ActivityData->TotalStrokes = $this->Values['total_strokes'][0];
        }

        if (isset($this->Values['avg_swimming_cadence'])) {
            $this->Container->ActivityData->AvgCadence = $this->Values['avg_swimming_cadence'][0];
        }

        if (isset($this->Values['pool_length'])) {
            $this->Container->ActivityData->PoolLength = $this->Values['pool_length'][0];
        }

        // #TSC add respiration rate AVG + MAX
        if (isset($this->Values['avg_respiration_rate'])) {
            $this->Container->ActivityData->AvgRespirationRate = round($this->Values['avg_respiration_rate'][0] / 100, 0);
        }
        if (isset($this->Values['max_respiration_rate'])) {
            $this->Container->ActivityData->MaxRespirationRate = round($this->Values['max_respiration_rate'][0] / 100, 0);
        }

        if (isset($this->Values['sport'])) {
            // #TSC in the session (same as NAME=sport) the sport is also stored
            // not sure whats the best way and what to use in which case (and when they can differ); but use both as implementation before
            $this->readSportProfile();
        }

        // #TSC: save effect (import at least is greater 0 - see also inc/core/Parser/Activity/Common/Filter/OutOfRangeValueFilter.php)
        if (isset($this->Values['total_training_effect']) && $this->Values['total_training_effect'][0] > 0 && $this->Values['total_training_effect'][0] <= 99.0) {
            $this->Container->FitDetails->TrainingEffect = $this->Values['total_training_effect'][0] / 10;
        }

        // #TSC: save anaerob effect (import at least is greater 0 - see also inc/core/Parser/Activity/Common/Filter/OutOfRangeValueFilter.php)
        if (isset($this->Values['total_anaerobic_training_effect']) && $this->Values['total_anaerobic_training_effect'][0] > 0 && $this->Values['total_anaerobic_training_effect'][0] <= 99.0) {
            $this->Container->FitDetails->AnaerobicTrainingEffect = $this->Values['total_anaerobic_training_effect'][0] / 10;
        }

        // #TSC: fit ascent / descent
        if (isset($this->Values['total_ascent']) && $this->Values['total_ascent'][0] != 0) {
            $this->Container->FitDetails->TotalAscent = $this->Values['total_ascent'][0];
        }
        if (isset($this->Values['total_descent']) && $this->Values['total_descent'][0] != 0) {
            $this->Container->FitDetails->TotalDescent = $this->Values['total_descent'][0];
        }

        // #TSC: fit self evaluation
        if (isset($this->Values['self_evaluation_felt'])) {
            $v = Activity\SelfEvaluationFeeling::fromFitToEnum($this->Values['self_evaluation_felt'][0]);
            $this->Container->FitDetails->SelfEvaluationFeeling = $v;
        }
        if (isset($this->Values['self_evaluation_perceived_effort'])) {
            $this->Container->FitDetails->SelfEvaluationPerceivedEffort = $this->Values['self_evaluation_perceived_effort'][0] / 10;
        }

        // #TSC: set avg temperature
        if (isset($this->Values['avg_temperature']) && $this->Values['avg_temperature'][0] != 0) {
            $this->Container->ActivityData->AvgTemperature = $this->Values['avg_temperature'][0];
        }

        // #TSC: total cycles (for strength training)
        if (isset($this->Values['total_cycles']) && $this->Values['total_cycles'][0] > 0) {
            $this->Container->ActivityData->TotalCycles = $this->Values['total_cycles'][0];
        }

        // #TSC: training load peak
        if (isset($this->Values['xxx168']) && $this->Values['xxx168'][0] > 0) {
            $this->Container->FitDetails->LoadPeak = round($this->Values['xxx168'][0] / 65536, 0);
        }
    }

    protected function readSport()
    {
        $this->readSportProfile();

        // #TSC: set original sport-name to notes
        if (isset($this->Values['name'])) {
            $v = "Profile: " . substr($this->Values['name'][0], 1, -1);
            if(!empty($this->Container->Metadata->getNotes())) {
                $v = $v . "\n".$this->Container->Metadata->getNotes();
            }
            $this->Container->Metadata->setNotes($v);
        }
    }

    /**
     * #TSC
     * reads the sports-type (running, climbing...) from the FIT and set the internalId for this activity.
     */
    protected function readSportProfile()
    {
        // if the sport is already set, ignore further
        if (!empty($this->Container->Metadata->getInternalSportId()) && !empty($this->Container->Metadata->getSportName())) {
            return;
        }

        if (isset($this->Values['sport'])) {
            $fitSdkMapping = new FitSdkMapping();

            // the subsport is available (think this is in the most cases)
            if (isset($this->Values['sub_sport'])) {
                // now check if we found a mapping mit sport & subsport
                $internalId = $fitSdkMapping->sportWithSubsportToInternal($this->Values['sport'][0], $this->Values['sub_sport'][0]);
                if (isset($internalId) && isset($this->Values['name'])) {
                    // yeah, something like sport=31=rock_climbing & sub_sport=69 (=Boulder) was found.
                    // use the name attribute (=Bouldering) and not the "sport" (was rock_climbing) as in the "normal" case
                    // not sure if this is necessary here, but be on the secure side ;-)
                    $name = substr($this->Values['name'][0], 1, -1);
                }
            }

            // no mapping with subsport found, try it with only "sport" (the original case)
            if (!isset($internalId)) {
                $internalId = $fitSdkMapping->toInternal($this->Values['sport'][0]);
            }
            if (!isset($name) && isset($this->Values['sport'])) {
                $name = $this->Values['sport'][1];
            }

            // set the examined values
            $this->Container->Metadata->setInternalSportId($internalId);
            $this->Container->Metadata->setSportName($name);
        } elseif (isset($this->Values['name'])) {
            // no sport available, set the name so later the "string" can be resolved to the interalid
            $this->Container->Metadata->setSportName(substr($this->Values['name'][0], 1, -1));
        }
    }

    protected function readUserProfile()
    {
        if (isset($this->Values['xxx39'])) {
            $this->Container->FitDetails->VO2maxEstimate = round((float)$this->Values['xxx39'][1] * 3.5, 2);
        }
    }

    protected function readUndocumentedUserData()
    {
        if (isset($this->Values['unknown0']) && 0.0 == $this->Container->FitDetails->VO2maxEstimate) {
            // TSC: used on fenix6
            $this->Container->FitDetails->VO2maxEstimate = round((int)$this->Values['unknown0'][1] * 3.5 / 1024, 2);
        }
    }

    protected function readUndocumentedDataBlob140()
    {
        if (isset($this->Values['performance_condition_end'])) {
            $this->Container->FitDetails->PerformanceConditionEnd = 100 + (float)$this->Values['performance_condition_end'][1];
        }

        // #TSC: set recovery time (in minutes) from 140er unknown9, if not already set
        // thanks to: https://github.com/GoldenCheetah/GoldenCheetah/blob/master/src/FileIO/FitRideFile.cpp line 844
        if (is_null($this->Container->FitDetails->RecoveryTime) && isset($this->Values['recovery_time'])) {
            $this->Container->FitDetails->RecoveryTime = round((int)$this->Values['recovery_time'][1], 0);
        }

        // #TSC: set Running Lactate Threshold Heart Rate, (in bpm) from 140er unknown14
        if (isset($this->Values['lactate_threshold_hr_bpm'])) {
            $lthr = (int)$this->Values['lactate_threshold_hr_bpm'][1];
            if ($lthr > 0 && $lthr <= 250) {
                $this->Container->FitDetails->LactateThresholdHR = $lthr;
            }
        }
    }

    protected function readEvent()
    {
        if (isset($this->Values['event']) && isset($this->Values['data'])) {
            switch ((int)$this->Values['event'][1]) {
                case 37:
                    $this->Container->FitDetails->VO2maxEstimate = (int)$this->Values['data'][1];
                    return;

                case 38:
                    // #TSC: set recovery time if not set
                    if(is_null($this->Container->FitDetails->RecoveryTime)) {
                        $this->Container->FitDetails->RecoveryTime = (int)$this->Values['data'][1];
                    }
                    return;

                case 39:
                    $creator = $this->Container->Metadata->getCreator();

                    // TODO: this may need more device and firmware specific conditions
                    if ($this->hasPerformanceCondition($creator) && is_numeric($this->Values['data'][1])) {
                        $v = (int)$this->Values['data'][1];
                        if ($v >= 0 && $v <= 255) {
                            // must set the original value around 100, because the UI substracts 100 - see class.SectionMiscellaneousRow.php
                            // (and there is inc/core/Parser/Activity/Common/Filter/OutOfRangeValueFilter.php)
                            $this->Container->FitDetails->PerformanceCondition = $v;
                        }
                    } else {
                        $this->Container->FitDetails->HrvAnalysis = (int)$this->Values['data'][1];
                    }

                    return;
            }
        }

        if (!isset($this->Values['event']) || $this->Values['event'][1] != 'timer' || !isset($this->Values['event_type'])) {
            return;
        }

        $thisTimestamp = $this->strtotime((string)$this->Values['timestamp'][1]);

        if (!empty($this->Container->ContinuousData->Time) && ($this->Values['event_type'][1] == 'stop_all' || $this->Values['event_type'][1] == 'stop')) {
            $this->IsPaused = true;
            $this->LastStopTimestamp = $thisTimestamp;
        } elseif ($this->Values['event_type'][1] == 'start') {
            if ($this->IsPaused && ($thisTimestamp - $this->Container->Metadata->getTimestamp()) < end($this->Container->ContinuousData->Time)) {
                $this->Container->PausesToApply->add(new Pause(
                    $this->LastStopTimestamp - $this->Container->Metadata->getTimestamp(),
                    $thisTimestamp - $this->LastStopTimestamp
                ));
            } elseif ($this->IsPaused) {
                $this->WasPaused = true;
            }

            $this->IsPaused = false;

            if ($this->LastStopTimestamp === false) {
                $this->Container->Metadata->setTimestampAndTimezoneOffsetWithUtcFixFrom((string)$this->Values['timestamp'][1]);
            } elseif ($thisTimestamp > $this->LastStopTimestamp) {
                $this->PauseInSeconds += $thisTimestamp - $this->LastStopTimestamp;
            }
        }
    }

    /**
     * read data from battery block.
     * #TSC
     */
    protected function readBattery() {
        // set temperature of battery; besser als gar nichts ;-) will stored every 5 minutes on a Fenix 6
        if (isset($this->Values['temperature'])) {
            $this->batteryTemperature[] = (int)$this->Values['temperature'][0];
        }
    }

    /**
     * TSC: list of devices which support Performance Condition
     * Mapping number to device see https://github.com/Runalyze/devices/blob/master/src/Mapping/GarminFitSdkMapping.php
     */
    protected function hasPerformanceCondition($creator) {
        if (substr($creator, 0, 5) === 'fr630' || $creator == '2156' || $creator == '2310' || $creator == '2311' ||                             // GARMIN_FORERUNNER_630
            substr($creator, 0, 5) === 'fr645' || $creator == '2886' || $creator == '3003' || $creator == '2888' || $creator == '3004' ||       // GARMIN_FORERUNNER_645
            substr($creator, 0, 5) === 'fr935' || $creator == '2691' || $creator == '2833' ||                                                   // GARMIN_FORERUNNER_935
            $creator == '3113' || $creator == '3441' ||                                                                                         // GARMIN_FORERUNNER_945
            substr($creator, 0, 7) === 'fr735xt' || $creator == '2158' || $creator == '2533' || $creator == '2534' || $creator == '2158' ||     // GARMIN_FORERUNNER_735_X_T
                $creator == '2533' || $creator == '2534' ||
            substr($creator, 0, 6) === "fenix3" ||                                                                                              // GARMIN_FENIX 3...
            $creator == '2050' || $creator == '2188' || $creator == '2293' || $creator == '2407' || $creator == '2408' || $creator == '2189' ||
            $creator == '2375' || $creator == '2432' || $creator == '2413' || $creator == '2473' || $creator == '2474' || $creator == '2475' ||
            $creator == '2476' || $creator == '2477' ||
            substr($creator, 0, 6) === "fenix5" ||                                                                                              // GARMIN FENIX 5...
            $creator == '2697' || $creator == '3110' || $creator == '2796' || $creator == '2797' || $creator == '2544' || $creator == '2604' ||
            $creator == '2798' || $creator == '3089' || $creator == '3135' || $creator == '2900' || $creator == '3134' || $creator == '3111' ||
            $creator == '2798' || $creator == '3089' || $creator == '3135' || $creator == '2900' || $creator == '3134' || $creator == '3111' ||
            substr($creator, 0, 6) === "fenix6" ||                                                                                              // GARMIN_FENIX 6...
            $creator == '3287' || $creator == '3288' || $creator == '3289' || $creator == '3290' || $creator == '3291' || $creator == '3512' ||
            $creator == '3513' || $creator == '3514' || $creator == '3515' || $creator == '3516'
            ) {
            return true;
        } else {
            return false;
        }
    }

    protected function readRecord()
    {
        if (
            $this->IsPaused || // Should not happen?
            count($this->Values) == 1 ||
            (!isset($this->Values['compressed_speed_distance']) && !isset($this->Values['timestamp']))
        ) {
            return;
        }

        if ($this->IsSwimming) {
            // #TSC: if swimming, also save this values (if available)
            $this->Container->ContinuousData->HeartRate[] = isset($this->Values['heart_rate']) ? (int)$this->Values['heart_rate'][0] : null;
            $this->Container->ContinuousData->Temperature[] = isset($this->Values['temperature']) ? (int)$this->Values['temperature'][0] : null;

            // save also the timestamp of the record
            $this->RecordTime[] = isset($this->Values['timestamp']) ? (int)$this->Values['timestamp'][0] : null;

            return;
        }

        $this->mapDeveloperFieldsToNativeFieldsFor($this->DeveloperFieldMappingForRecord);

        if (isset($this->Values['compressed_speed_distance'])) {
            $time = $this->parseCompressedSpeedDistance();
            $last = -1;
        } else {
            if (empty($this->Container->ContinuousData->Time)) {
                $startTime = $this->strtotime((string)$this->Values['timestamp'][1]);

                if ($startTime < $this->Container->Metadata->getTimestamp()) {
                    $this->Container->Metadata->setTimestampAndTimezoneOffsetWithUtcFixFrom((string)$this->Values['timestamp'][1]);
                }
            }
            $time = $this->strtotime((string)$this->Values['timestamp'][1]) - $this->Container->Metadata->getTimestamp() - $this->PauseInSeconds - $this->TimeJumpsInSeconds;
            $last = end($this->Container->ContinuousData->Time);

            if ($this->WasPaused) {
                $pause = new Pause($last, $this->strtotime((string)$this->Values['timestamp'][1]) - $this->LastStopTimestamp);
                $pause->setHeartRateDetails(
                    end($this->Container->ContinuousData->HeartRate),
                    isset($this->Values['heart_rate']) ? (int)$this->Values['heart_rate'][0] : null
                );

                $this->Container->Pauses->add($pause);
                $this->WasPaused = false;
            } elseif ($time - $last > self::TIME_LIMIT_FOR_TIME_JUMP) {
                $this->TimeJumpsInSeconds += $time - $last;

                return;
            }
        }

        if ($time < $last) {
            return;
        }

        // #TSC: save also the timestamp of the record
        $this->RecordTime[] = isset($this->Values['timestamp']) ? (int)$this->Values['timestamp'][0] : null;

        $this->Container->ContinuousData->Latitude[] = isset($this->Values['position_lat']) ? substr($this->Values['position_lat'][1], 0, -4) : null;
        $this->Container->ContinuousData->Longitude[] = isset($this->Values['position_long']) ? substr($this->Values['position_long'][1], 0, -4) : null;

        // TSC: check if altitude baro is not yet set; try to set it one time
        if(is_null($this->Container->ContinuousData->IsAltitudeDataBarometric)) {
            // TSC: check if "enhanced_altitude" is existing - if true => reading alti by Baro!
            if(isset($this->Values['enhanced_altitude']) && $this->Values['enhanced_altitude'][0] != 0) {
                $this->Container->ContinuousData->IsAltitudeDataBarometric = true;
            } else if(isset($this->Values['altitude']) && $this->Values['altitude'][0] != 0) {
                $this->Container->ContinuousData->IsAltitudeDataBarometric = false;
            }
        }
        
        if($this->Container->ContinuousData->IsAltitudeDataBarometric) {
            $this->Container->ContinuousData->Altitude[] = isset($this->Values['enhanced_altitude']) && $this->Values['enhanced_altitude'][0] != 0 ? substr($this->Values['enhanced_altitude'][1], 0, -4) : null;
        } else {
            $this->Container->ContinuousData->Altitude[] = isset($this->Values['altitude']) && $this->Values['altitude'][0] != 0 ? substr($this->Values['altitude'][1], 0, -4) : null;
        }

        $this->Container->ContinuousData->Distance[] = isset($this->Values['distance']) ? $this->Values['distance'][0] / 1e5 : end($this->Container->ContinuousData->Distance);
        $this->Container->ContinuousData->HeartRate[] = isset($this->Values['heart_rate']) ? (int)$this->Values['heart_rate'][0] : null;
        $this->Container->ContinuousData->Cadence[] = isset($this->Values['cadence']) ? (int)$this->Values['cadence'][0] : null;
        $this->Container->ContinuousData->Power[] = isset($this->Values['power']) ? (int)$this->Values['power'][0] : null;
        $this->Container->ContinuousData->LeftRightBalance[] = isset($this->Values['left_right_balance']) ? (int)$this->Values['left_right_balance'][0] : null;
        $this->Container->ContinuousData->Temperature[] = isset($this->Values['temperature']) ? (int)$this->Values['temperature'][0] : null;
        // #TSC added performance-condition and respiration-rate
        $this->Container->ContinuousData->PerformanceCondition[] = isset($this->Values['perf_cond']) ? (int)$this->Values['perf_cond'][0] : null;
        // respiration-rate is 1000er; means 3500 => 35 per minute
        $this->Container->ContinuousData->RespirationRate[] = isset($this->Values['respiration_rate']) ? round((int)($this->Values['respiration_rate'][0] / 100), 0) : null;

        $this->Container->ContinuousData->Time[] = $time;

        //Running Dynamics
        $this->Container->ContinuousData->GroundContactTime[] = isset($this->Values['stance_time']) ? round($this->Values['stance_time'][0]/10) : (
            isset($this->Values['stance_time_left']) && isset($this->Values['stance_time_right']) ? round(($this->Values['stance_time_left'][0] + $this->Values['stance_time_right'][0]) / 2 / 10) : null
        );
        $this->Container->ContinuousData->VerticalOscillation[] = isset($this->Values['vertical_oscillation']) ? round($this->Values['vertical_oscillation'][0] / 10) : null;
        $this->Container->ContinuousData->GroundContactBalance[] = isset($this->Values['stance_time_balance']) ? (int)$this->Values['stance_time_balance'][0] : (
            isset($this->Values['stance_time_left']) && isset($this->Values['stance_time_right']) ? round(10000 * $this->Values['stance_time_left'][0] / ($this->Values['stance_time_left'][0] + $this->Values['stance_time_right'][0])) : null
        );

        // Fit developer fields
        $this->Container->ContinuousData->MuscleOxygenation[] = isset($this->Values['smo2_0']) ? (int)$this->Values['smo2_0'][0] : null;
        $this->Container->ContinuousData->MuscleOxygenation_2[] = isset($this->Values['smo2_1']) ? (int)$this->Values['smo2_1'][0] : null;
        $this->Container->ContinuousData->TotalHaemoglobin[] = isset($this->Values['thb_0']) ? (int)$this->Values['thb_0'][0] : null;
        $this->Container->ContinuousData->TotalHaemoglobin_2[] = isset($this->Values['thb_1']) ? (int)$this->Values['thb_1'][0] : null;

        // RunScribe fields
        $this->Container->ContinuousData->ImpactGsLeft[] = isset($this->Values['impact_gs_left']) ? round($this->Values['impact_gs_left'][0], 1) : null;
        $this->Container->ContinuousData->ImpactGsRight[] = isset($this->Values['impact_gs_right']) ? round($this->Values['impact_gs_right'][0], 1) : null;
        $this->Container->ContinuousData->BrakingGsLeft[] = isset($this->Values['braking_gs_left']) ? round($this->Values['braking_gs_left'][0], 1) : null;
        $this->Container->ContinuousData->BrakingGsRight[] = isset($this->Values['braking_gs_right']) ? round($this->Values['braking_gs_right'][0], 1) : null;
        $this->Container->ContinuousData->FootstrikeTypeLeft[] = isset($this->Values['fs_type_left']) ? (int)$this->Values['fs_type_left'][0] : null;
        $this->Container->ContinuousData->FootstrikeTypeRight[] = isset($this->Values['fs_type_right']) ? (int)$this->Values['fs_type_right'][0] : null;
        $this->Container->ContinuousData->PronationExcursionLeft[] = isset($this->Values['pronation_left']) ? round($this->Values['pronation_left'][0], 1) : null;
        $this->Container->ContinuousData->PronationExcursionRight[] = isset($this->Values['pronation_right']) ? round($this->Values['pronation_right'][0], 1) : null;

        if ($time === $last) {
            $this->mergeRecord();
        }
    }

    protected function mapDeveloperFieldsToNativeFieldsFor(array $developerFieldMapping)
    {
        foreach ($developerFieldMapping as $devFieldName => $nativeData) {
            $nativeFieldName = $nativeData[0];
            $nativeFactor = $nativeData[1];

            if (isset($this->Values[$devFieldName]) && ($this->Values[$devFieldName][0] != 0 || !isset($this->Values[$nativeFieldName]))) {
                $this->Values[$devFieldName][0] *= $nativeFactor;
                $this->Values[$nativeFieldName] = $this->Values[$devFieldName];
            }
        }
    }

    protected function mergeRecord()
    {
        end($this->Container->ContinuousData->Time);
        $i = key($this->Container->ContinuousData->Time);

        foreach ($this->Container->ContinuousData->getPropertyNamesOfArrays() as $key) {
            if (array_key_exists($i, $this->Container->ContinuousData->{$key})) {
                $last = $this->Container->ContinuousData->{$key}[$i - 1];
                $current = array_pop($this->Container->ContinuousData->{$key});

                if ($current != 0 && $last == 0) {
                    $this->Container->ContinuousData->{$key}[$i - 1] = $current;
                }
            }
        }
    }

    /**
     * @see FIT SDK, e.g. at https://github.com/dgaff/fitsdk/blob/7f38d911388b7cdc3db7bf0318239352928faa8b/c/examples/decode/decode.c#L132-L146
     * @return int current time
     */
    protected function parseCompressedSpeedDistance()
    {
        $values = explode(',', $this->Values['compressed_speed_distance'][1]);

        if (count($values) == 3) {
            $speed100 = $values[0] | (($values[1] & 0x0F) << 8);

            $distance16 = ($values[1] >> 4) | ($values[2] << 4);
            $distance16diff = ($distance16 - $this->CompressedLastDistance16) & 0x0FFF;
            $this->CompressedTotalDistance16 += $distance16diff;
            $this->CompressedLastDistance16 = $distance16;

            $this->CompressedTotalTime += ($distance16diff / 16.0) / ($speed100 / 100.0);
            $this->Values['distance'][0] = 100 * $this->CompressedTotalDistance16 / 16.0;
        }

        return round($this->CompressedTotalTime);
    }

    protected function readLap()
    {
        $this->mapDeveloperFieldsToNativeFieldsFor($this->DeveloperFieldMappingForLap);

        if (isset($this->Values['total_timer_time']) && isset($this->Values['total_distance']) && round($this->Values['total_timer_time'][0] / 1e3) > 0) {
            $r = new Round(
                $this->Values['total_distance'][0] / 1e5,
                $this->Values['total_timer_time'][0] / 1e3,
                $this->isActiveLap() // #TSC is swimming and no real activity in this lap ==> pause/rest
            );

            // if there is a Garmin training with workout(_steps) some more details can be set/stored
            $splitAdds = null;
            if (isset($this->Values['wkt_step_index'])) {
                $i = $this->Values['wkt_step_index'][0];

                // #TSC set more detailed intensity (like warmup or recovery) for this lap
                $v = $this->fitWorkoutStep->getIntensity($i);
                if (!is_null($v)) {
                    // special case: if we have a lap with time and distance and its marked as REST we change it to RECOVERY
                    // if i use Fenix6 with interval created on the device, the laps between the intervals are pause - but i want RECOVERY
                    if ($v->isRest()) {
                        $v = LapIntensity::getInstanceRecovery();
                    }
                    $r->setIntensity($v);
                }

                // #TSC set custom target from workout_step (like pace or HR)
                $splitAdds = $this->fitWorkoutStep->getSplitAdditionals($i);
            }

            $this->Container->Rounds->add($r);

            // #TSC additional infos for this lap
            $this->fitSplitsAdditionals->collectLap($this->Values, $this->isActiveLap(), $this->IsSwimming, $splitAdds);
        }
    }

    /**
     * #TSC read the sets of a strength-training as "normal" laps
     */
    protected function readSet()
    {
        $round = $this->fitSplitsAdditionals->getSetRoundAndCollect($this->Values);
        if(!is_null($round)) {
            $this->Container->Rounds->add($round);
        }   
    }

    // "NAME=lenght" is a swim lane
    protected function readLength()
    {
        if (!$this->IsSwimming) {
            foreach ($this->Container->ContinuousData->getPropertyNamesOfArrays() as $key) {
                // TSC: do not delete here heart-rate and temperature
                // before the first lap is reading the both values are already written
                if($key != 'Temperature' && $key != 'HeartRate') {
                    $this->Container->ContinuousData->{$key} = [];
                }
            }

            $this->IsSwimming = true;
        }

        $this->Container->ContinuousData->Strokes[] = isset($this->Values['total_strokes']) ? (int)$this->Values['total_strokes'][0] : null;

        // #TSC: if there is a rest/pause-lap ("length_type=0=idle" means nothink happens in this interval), set the BREAK-type
        $stroketype = isset($this->Values['swim_stroke']) ? (int)$this->Values['swim_stroke'][0] : null;
        if($stroketype == null && isset($this->Values['length_type']) && $this->Values['length_type'][0] == '0') {
            $this->Container->ContinuousData->StrokeType[] = StrokeTypeProfile::BREAK;
        } else {
            $this->Container->ContinuousData->StrokeType[] = $stroketype;
        }

        $this->Container->ContinuousData->Cadence[] = isset($this->Values['avg_swimming_cadence']) ? (int)$this->Values['avg_swimming_cadence'][0] : null;

        if (empty($this->Container->ContinuousData->Time)) {
            // at first time set the start time and the first "round/lane" duration
            $this->Container->Metadata->setTimestampAndTimezoneOffsetWithUtcFixFrom((string)$this->Values['start_time'][1]);
            $this->Container->ContinuousData->Time[] = round(((int)$this->Values['total_timer_time'][0]) / 1000);
        } else {
            // save the duration of this lane
            $this->Container->ContinuousData->Time[] = end($this->Container->ContinuousData->Time) + round(((int)$this->Values['total_timer_time'][0]) / 1000);
        }

        // #TSC: calculate the heart-rate / temp for this lane
        if(isset($this->Values['start_time'][0]) && isset($this->Values['total_timer_time'][0])) {
            $this->setSwimAdditionalInfos();
        }
    }

    /**
     * set the heartrate and temperature of the "record" sets to the swim-lanes.
     * for example we have swim 80 lanes, we have 80 "NAME=length"-records but 8000 "NAME=record"-records.
     * so we must "map" per time of the start-/stop-time of the lane and the "NAME=record" timestamps with "NAME=record" belongs to which lane ans build the average.
     * #TSC
     */
    protected function setSwimAdditionalInfos() {
        // get start- and end-time of this lane
        $startTime = (int)$this->Values['start_time'][0];
        $endTime = (int)($startTime + round(((int)$this->Values['total_timer_time'][0]) / 1000));

        // get the relevant records-times for this lane (inside the start/end-time)
        $recordTimes = array_filter($this->RecordTime, function($v, $k) use ($startTime, $endTime) {
            return isset($v) && $v >= $startTime && $v <= $endTime;
        }, ARRAY_FILTER_USE_BOTH);

        // get the indexes of recordTimes to find the corresponing heartrate and temperature
        $heartsVal = [];
        $tempVal = [];
        forEach(array_keys($recordTimes) as &$i) {
            if(isset($this->Container->ContinuousData->HeartRate[$i])) {
                $heartsVal[] = $this->Container->ContinuousData->HeartRate[$i];
            }
            if(isset($this->Container->ContinuousData->Temperature[$i])) {
                $tempVal[]   = $this->Container->ContinuousData->Temperature[$i];
            }
        }

        // calculate the avg of the current lane
        if(count($heartsVal) > 0) {
            $this->SwimHeartrate[] = round(array_sum($heartsVal)/count($heartsVal));
        } else {
            $this->SwimHeartrate[] = null;
        }

        if(count($tempVal) > 0) {
            $this->SwimTemperature[] = round(array_sum($tempVal)/count($tempVal));
        } else {
            $this->SwimTemperature[] = null;
        }
    }

    protected function readHRV()
    {
        if (!$this->IsPaused) {
            $values = explode(',', $this->Values['time'][1]);

            foreach ($values as $value) {
                if ($value != '65535') {
                    $this->Container->RRIntervals[] = 1000 * (double)substr($value, 0, -2);
                }
            }
        }
    }

    /**
     * #TSC
     * is lap active or pause/rest.
     * primary for swimming.
     */
    protected function isActiveLap() {
        if ($this->IsSwimming
            && isset($this->Values['total_distance']) && $this->Values['total_distance'][0] == "0"
            && isset($this->Values['total_strokes']) && $this->Values['total_strokes'][0] == "0") {
            // this is for lap and StrokeTypeProfile::BREAK (for lanes) can't be used here
            return false;
        } else {
            return true;
        }
    }

    /**
     * read some info from the workout.
     * #TSC
     */
    protected function readWorkout()
    {
        // #TSC: set workout-name to end of the notes
        if (isset($this->Values['wkt_name'])) {
            // remove leading "
            $n = ltrim($this->Values['wkt_name'][0], '"');
            $n = rtrim($n, '"');

            $v = "Workout name: " . $n;
            if(!empty($this->Container->Metadata->getNotes())) {
                $v = $this->Container->Metadata->getNotes() . "\n" . $v;
            }
            $this->Container->Metadata->setNotes($v);
        }
    }

    /**
     * read some info from the split.
     * this can include Garmins PacePro or infos about Walk/Inactiv/Run
     * #TSC
     */
    protected function readSplit()
    {
        if (!isset($this->Values['split_type']) || !isset($this->Values['total_timer_time'])) {
            return;
        }

        $split_type = $this->Values['split_type'][0];
        $total_timer_time_s = round((int)($this->Values['total_timer_time'][0]) / 1000);

        // pace-pro
        if ($split_type == 0
            && isset($this->Values['total_distance']) 
            && isset($this->Values['pacep_plan_duration']) && isset($this->Values['pacep_plan_distance'])) {
            // init value
            if (!isset($this->Container->ActivityData->PaceGoal)) {
                $this->Container->ActivityData->PaceGoal = array();
            }
        
            $split = array();

            // real running values
            $split['t_s'] = $total_timer_time_s;
            $split['dist_m'] = round((int)($this->Values['total_distance'][0]) / 100);

            // pace-pro plan values
            $split['pp_t_s'] = round((int)($this->Values['pacep_plan_duration'][0]) / 1000);
            $split['pp_dist_m'] = round((int)($this->Values['pacep_plan_distance'][0]) / 100);

            // add it to the existing JSON array
            $this->Container->ActivityData->PaceGoal[] = $split;
            return;
        }

        // initialize the values for run/walk/stand
        if (!isset($this->Container->FitDetails->RunTime) &&
            ($split_type == 17 || $split_type == 18 || $split_type == 22)) {
            $this->Container->FitDetails->RunTime = 0;
            $this->Container->FitDetails->WalkTime = 0;
            $this->Container->FitDetails->StandTime = 0;
        }

        // cumulate run/walk/stand
        switch ($split_type) {
            case 17: // run time
                $this->Container->FitDetails->RunTime += $total_timer_time_s;
                return;
            case 18: // walk time
                $this->Container->FitDetails->WalkTime += $total_timer_time_s;
                return;
            case 22: // stand time
                $this->Container->FitDetails->StandTime += $total_timer_time_s;
                return;
        }
    }

    /**
     * read some info from the time_in_zone.
     * #TSC
     */
    protected function readTimeInZone()
    {
        // there is a time_in_zone for every lap and one for the whole session
        if (isset($this->Values['reference_mesg']) && $this->Values['reference_mesg'][1] == 'session') {
            // max hr of the user
            if (isset($this->Values['max_hr']) && $this->Values['max_hr'][0] != 0) {
                $this->Container->ActivityData->MaxHeartRateUser = (int)$this->Values['max_hr'][0];
            }

            // the zones: example "104,129,144,153,160,182"
            if (isset($this->Values['hr_zone_high_boundary'])) {
                $zones = explode(",", $this->Values['hr_zone_high_boundary'][1]);
                if(count($zones) > 0) {
                    $this->Container->ActivityData->HeartRateZoneBounds = $zones;
                }
            }

            // time in zones: example "19.997 s,160.374 s,604.612 s,1754.220 s,2425.061 s,1260.011 s,0.000 s"
            if (isset($this->Values['time_in_hr_zone'])) {
                $str = $this->Values['time_in_hr_zone'][1];
                // remove the unit
                $str = str_replace(" s", "", $str);

                $zones = explode(",", $str);
                if(count($zones) > 0) {
                    // round to ganzzahl
                    foreach($zones as &$v) {
                        $v = round($v);
                    }

                    $this->Container->FitDetails->SecondsInHrZones = $zones;
                }
            }
            /*
            the mapping of the examples values are
            hr_zone_high_boundary (bpm)  104 129 144  153  160  182
            time_in_hr_zone (seconds)     20 160 605 1754 2425 1260 0
            Bereich 1   104 - 128 bpm Warm up   =>  160s =  2:40
            Bereich 2   129 - 143 bpm Leicht    =>  605s = 10:05
            Bereich 3   144 - 152 bpm Aerob     => 1754s = 29:14
            Bereich 4   153 - 159 bpm Intensiv  => 2425s = 40:25
            Bereich 5   > 160 bpm      Maximal  => 1260s = 21:00
            <104 there is no presentation in garmin connect and for >182 now seconds availabe (and i think the max HR shall be set)
            */
        }
    }
}
