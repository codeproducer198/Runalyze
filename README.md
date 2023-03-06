# [RUNALYZE v4.3.0] Release with minor fixes/changes.

This fork of Runalyze is a release fork with all needed dependencies and can be used directly with [Docker](https://github.com/2er0/runalyze-docker).
I have done some small fixes/imporvements.
Because it based on the [Release 4.3.0](https://github.com/Runalyze/Runalyze/releases/tag/v4.3.0) i hope it is more future-proof in an "old" docker container.

I host it on a private Pine64 Rock64 SOC computer to host my family activities (running, walking, mountain climbing, swimming). It runs on a Debian Buster/ARM64 in a Docker container serviced with docker-compose. Debian 11 Bullseye supports PHP 7.4 and it runs without problems with some PHP warnings. As input GPS devices i use Garmin Forerunner 45S, Garmin Fenix 6 and Android-Handy with ApeMap/OruxMaps. I import my tacks without use of Garmin-Tools (like Garmin Connect) and so i think no of my private sensible health-data is transmit to the "public" cloud.
If you need the Debian 10 Buster/PHP 7.3 version see the GIT tag `v4.3.0_Debian10_PHP73`; but this version is no more maintenance.

With my other Github project [Clone of Tkl2Gpx](https://github.com/codeproducer198/Tkl2Gpx) i have imported my old running activities from the year 2012 until now into RUNALYZE. These old tracks are record with a GPS MapJack watch and transformed to GPX files imported via RUNYLZE bulk-job.

Here some fixes/improvements i have done in RUNALYZE (see details in the commits):
* Fixes some small bugs until the base release is running on my environment (missing DB attribute, wrong/missing number values, ...)
* Fixes some small bugs while importing FIT files from Garmin
* Batch/Bulk-imports can now set/override the sports type
* Imports from MapJack watch/GPX and Garmin FR45 & Fenix6/FIT results in errors because missing heart-rates and altitutes. Now the NULL will be filled.
* Sport types hiking and (new) mountain climbing.
* Imported filename is stored in title attribute.
* Temperature of FIT files are stored in the temp attribute as average value.
* 2020-09-27: Import Garmin FIT "total_training_effect"-attribute (Aerob Training Effect) already with greater 0.0 (and not even 1.0)
* 2020-09-27: Garmin FIT "total_anaerobic_training_effect" attribute as "Anaerobic Training Effect" is imported
	* Store it in the DB (runalyze_training.fit_anaerobic_training_effect)
	* Added on the dataset configuration
	* Also add field on statistic heart-rate view (activity main page)
	* **Migration 20200926230800 is necessary!**
* 2020-09-29: Garmin FIT attributes as fit_lactate_threshold_hr, fit_total_ascent/descent (the watch original values) are imported
	* Store it in the DB (runalyze_training.fit_anaerobic_training_effect)
	* Shown in the statistic view (activity main page) under 'Miscellaneous' panel
	* **Migration 20200928150400 is necessary!**
* 2020-09-29: Show further FIT file attributes (recovery-time, training-effect, creator, vo2max, performance-conditions, hvr) on statistic view (activity main page) under 'Miscellaneous' panel
* 2020-10-06: Loading weather data now uses the time in the middle of the activity _(start + (duration / 2))_.
* 2020-10-07: Add support for [meteostat](https://meteostat.net) historical weather data while editing a activity or while bulk import. Usable with setting the new "meteostatnet_api_key" in config.yml.
* 2020-10-17 to 2020-11-04: Import heartrate and temperature of Fenix 6 for swimming activities.
* 2020-11-04: Some fixing of correlate trackdata to laps/swim-lanes.
* 2020-11-09: Auto detection of type "interval-training" (detection only works in batch/bulk-mode). You must configure a training-type with short-cut "IT" to your sports in the configuration to use this feature.
* 2021-01-02: Fix "Start date" of an existing equipment is set to the stored date (not to current date).
* 2021-01-02: While batch/bulk-importing of activities main equipment of the sports are assigned to new activities
	* only main equipment-type are considered (set your equipment type as main equipment in the sports configuration)
	* only equipment-type with single-choice considered
	* a unique/time-ranged equipment must exists for this sport; if multiple equipments are found, nothing is assigned and a warning is logged while importing
	* keep in mind, that f. e. multiple shoes can not be mapped because no shoe can be clearly identified
* 2021-01-10: Add API-REST service /api/import/activity for uploading and importing activities for an account
	* The POST request must be a "multipart/form-data" to support multiple times in one request.
	* This new API uses the existing command ActivityBulkImportCommand
	* If no problems occur and all uploaded files are imported successfully a HTTP state 200 is returned otherwise (duplicates or failed) a state 202 is returned.
	* If there are technical trouble (while creating the tmp-folder, moving files ...) a HTTP state 500 is returned.
	* Additional infos and the output of the "runalyze:activity:bulk-import" are set in the response content as "Content-Type: text/plain"
	* Example curl to use: curl -k -u "<runalyze-account>:<pwd>" -H "Accept-Language: de,en" -X POST https://<domain>/api/import/activity -F 'file1=@afile.fit' -F 'file2=@another.fit' [-w 'HttpCode: %{http_code}\n']
* 2021-11-05: Add tests(-folder) from the archived repository to ensure dependency changes
	1. database preperation script `tests/scripts/preparetests.sh` (one time action per DB instance)
	2. wrapper script for all tests `tests/scripts/runtests.sh`
	* some tests has errors (i think is caused by the usage of PHP7); see `runtests.sh` for expected failures
	* `tests` folder can be excluded for production environment
* 2021-11-09: Upgrade PHP dependencies to newer/recent versions
	* only deps where none or only few code changes necessary
	* changes affected composer-files, vendor folder, database migrations, tests
	* Clone `laufhannes/GpxTrackPoster` to `codeproducer198/GpxTrackPoster` and use this dependency (commit from Mar 11, 2017)
* 2021-11-14: Upgrade web dependencies to newer/recent versions
	* only javascript deps where no error occur while the resource build or the app works
	* changes affected `bower.json`, `web/vendor` folder
* 2021-11-14: Upgrade to a newer Runalyze Glossary version (fixed commit-id dev-master#d7e2540bf51dc1aabe4dbdce85af96e8203311b6)
	* Seems the last version which works with the v4.3.0
* 2021-11-14: Garmins _Performance Condition_ is now saved in the purpose column for the newest watches
	* _Performance Condition_ in the "fit detail" section of an selected activity is now also displayed as a value between -20/+20
	* Supported watches are: Forerunner 640 & 645 & 935 & 945 & 735, Fenix 3 & 5 & 6
	* Caution: the search page still uses values based on 100; if you search for "+5" use "105" ;-)
	* The value is stored so far in the column `runalyze_training.fit_hrv_analysis`; to fix your database you can use this sqls to move the values to `fit_performance_condition`:
	`update runalyze_training set fit_performance_condition = fit_hrv_analysis where fit_performance_condition is null and fit_hrv_analysis is not null and fit_performance_condition_end is not null;`
	and
	`update runalyze_training set fit_hrv_analysis = null where fit_performance_condition = fit_hrv_analysis is not null and fit_performance_condition_end is not null;`
* 2021-12-19: Add Garmins _Performance Condition_ and _Respiration Rate_ from the FIT activity files to new database-continuous-data columns and show it as diagrams
	* Store _Performance Condition_ (not the single "start" and "end" value of the _FIT details_) in `runalyze_trackdata.performance_condition` while importing FIT file
	* Store _Respiration Rate_ in `runalyze_trackdata.respiration_rate` while importing FIT file
	* Show _Performance Condition_ as line diagram in the _Miscellaneous_ panel if available
	* Show _Respiration Rate_ as line diagram in the _HVR_ panel if available (resp-rate is only available for me with my Fenix 6 if i use a _HRM-Run_ sensor)
	* Do same fixes to diagrams so not every time-tick needs a value (needed for _Performance Condition_ because tracking starts from one kilometer)
	* **Migration 20211215213500 is necessary!**
* 2021-12-20: Support Garmins _Respiration Rate_ (avg & max) and _self-evaluation_-fields (feeling & perceived effort) for FIT activity import and show the values
	* New db columns in table `training`: `fit_self_evaluation_feeling`, `fit_self_evaluation_perceived_effort`, `avg_respiration_rate`, `max_respiration_rate`
	* Display and search for _AVG Respiration Rate_ in the dataview settings/activity table and in the HRV detail section
	* Show all values in the _FIT details_ section
	* _self-evaluation_-fields are only available with the Fenix watches with a firmware 19.20 and you must activate _self-evaluation_ for your the activities
	* Parsing _self-evaluation_-effort with the enum `SelfEvaluationPerceivedEffort` to a human readable text same as Garmin Connect shows
	* **Migration 20211219161500 is necessary!**
* 2021-12-20: Use [codeproducer198 glossary](https://github.com/codeproducer198/Runalyze-Glossary) and add links to new entries: respiration, running dynamics, self-evaluation
* 2021-12-22: Fix previous hack of setting _null_-values to _0_ in the continous data of GroundContactTime, VerticalOscillation, VerticalOscillation, Strokes, StrokeType, Cadence, Temperature
	* Some calculations (f.e. balance in the round detail view) result in small failure outcomes because _0_ is calculated - _null_ will be ignored for the calculation; mostly in the round details
    * To fix the _0_ to _null_ for GroundContactTime, VerticalOscillation, VerticalOscillation you can use following sql commands:
		```
		update runalyze_trackdata set groundcontact = regexp_replace(regexp_replace(groundcontact, '\\|0\\|', '\\|\\|'), '\\|0\\|', '\\|\\|')                 where groundcontact is not null and groundcontact like '%|0|%';
		update runalyze_trackdata set groundcontact = regexp_replace(groundcontact, '^0\\|', '\\|')                                                           where groundcontact is not null and groundcontact like '0|%';
		update runalyze_trackdata set groundcontact = regexp_replace(groundcontact, '\\|0$', '\\|')                                                           where groundcontact is not null and groundcontact like '%|0';
		update runalyze_trackdata set vertical_oscillation = regexp_replace(regexp_replace(vertical_oscillation, '\\|0\\|', '\\|\\|'), '\\|0\\|', '\\|\\|')   where vertical_oscillation is not null and vertical_oscillation like '%|0|%';
		update runalyze_trackdata set vertical_oscillation = regexp_replace(vertical_oscillation, '^0\\|', '\\|')                                             where vertical_oscillation is not null and vertical_oscillation like '0|%';
		update runalyze_trackdata set vertical_oscillation = regexp_replace(vertical_oscillation, '\\|0$', '\\|')                                             where vertical_oscillation is not null and vertical_oscillation like '%|0';
		update runalyze_trackdata set groundcontact_balance = regexp_replace(regexp_replace(groundcontact_balance, '\\|0\\|', '\\|\\|'), '\\|0\\|', '\\|\\|') where groundcontact_balance is not null and groundcontact_balance like '%|0|%';
		update runalyze_trackdata set groundcontact_balance = regexp_replace(groundcontact_balance, '^0\\|', '\\|')                                           where groundcontact_balance is not null and groundcontact_balance like '0|%';
		update runalyze_trackdata set groundcontact_balance = regexp_replace(groundcontact_balance, '\\|0$', '\\|')                                           where groundcontact_balance is not null and groundcontact_balance like '%|0';
		```
		This will fix little failure displays of the _running dynamics_ in the round view and the diagrams.
		It has no effect on the average/summarys of the _running dynamics_.
	* For the other datas a fix is not so easy and makes no sense
	* From now on the missing values are stored with a _null_ in the related array index and the calculation/display is fixed to handle these _null_-values indexes
* 2021-12-27: Add _GAP_/GradientAdjustedPace value in dataview (based on the existing Minetti algorithm)
	* Only for running activities and not stored in the database; only calculated while displaying
	* I'am not happy with the results, but I'll leave it that way for now; for me it always display the same or higher as the "normal" pace (perhaps the up/down is almost the same for me)
* 2022-01-30: New activity icons and activities
	* Add 12 new activity icons (climbing, snow activities, golf, kayak, tennis, surfing ...)
	* New activity _snow shoeing_ and _cross-country-skiing_ can be added to your account and are recognised while importing from Garmin Fenix 6
* 2022-08-29:
	* Fix importing the power average produced by Stryd (thanks to tgradl)
* 2022-08-30: 
	* New activity _bouldering_ and _indoor climbing_ can be added to your account and are recognised while importing from Garmin Fenix 6
	* Limitation: the routes (how many and difficulty), rest times and some other values are not imported yet -> hope this comes in the future when i understand the boulder/climbing FIT structure
* 2022-11-20:
	* For swimming Garmin's Firmware writes _rest_-laps in the FIT files (when using LAP button on the watch). This are considered:
	1. While importing such activity, this laps are set to "deactivate/ruhe" and will not shown (or special shown) in the laps widgets
	2. SWOLF calculation ignore _rest_-laps
	3. _lanes_ widget shows more details if the activity has _rest_-lanes
	4. _time_ of the activity is the real swimming time (without rests times)
	5. Optimize/fix the _lanes_-windows assignement of _trackdata_ to laps based on the time
	6. Implementation depends on `StrokeTypeProfile::BREAK`
* 2022-11-27:
	* New _average heart-rate for active rounds_ is a hr-value, only calculated based on active rounds/laps (inactive/"Ruhe" will ignored); this is relevant if you swimming with a Fenix and use the LAP button for rests/breaks
	* New db field `runalyze_training.pulse_avg_active`
	* Field is on the details view in the heart-rate section (only if the rate differs from the normal), in the dataset and in the search formular
	* If there no inactive rounds (means only active rounds), the normal avg heart-rate is set in this new field
	* **Migration 20221123203500 is necessary!** it will fill the `pulse_avg_active` with the content of `pulse_avg`
* 2022-11-28:
	* Optimize temperature determination on the activity (attribute `training.temperature`)
	1. if available: use the avg temperature from the FIT `session`
	2. if no data is set until now -> if available: use the avg temperature from the FIT `battery` recodings (every 5 minutes)
	3. if no data is set until now -> if available: use the average of stored temperatures in the track-/continuous-data
	4. if bulk import and outdoor-sport with external weather is successful loaded, use this data
	* show always the temperature in the dataset if available (independent of indoor/outdoor)
* 2022-11-29: Auto detection of type "Regeneration Run" (_RG_) while importing activity based on self-evaluation
	* You must configure a training-type with short-cut _RG_ to your sports (of default _RG_ exists)
	* Detection only works in batch/bulk import mode
	* Works on Fenix self-evaluation (Selbsteinschätzung), where you set
		* `perceived effort` = 1 ("empfundene Anstrengung", very light/sehr leicht)
		* and `feel evaluation` = 5 ("Gefühl", very strong/sehr stark)
* 2022-12-04: 
	* New activity _strength training_ (=Krafttraining) can be added to your account and are recognised while importing from Garmin Fenix 6
	* New database field `training.total_cycles` will be stored the total cycles/iterations of activity; this info be also available in the dataset-view and in the _miscellaneous_ section
	* Small changes to show laps and details of laps, if no distance is stored and the sport is a "no distance"-sport
	* Limitation: only the sets (active and rest) will be stored as "laps" with the duration; no cycles, type and wights on set/lap-level
	* Note: `total_cycles` can also be imported in other sports activities
	* **Migration 20221204190000 is necessary!**
* 2022-12-05: Set default decimal for distances from 2 to 3 decimal places; this affected detail views; this is better for swimming distances
* 2022-12-06: Garmin FITs _training load peak_ (=Trainingsbelastung) is now support (after a long time of search ;-))
	* New database field `training.fit_load_peak` will be stored the "Belastung"
	* Importing the field, available in the dataset and shown on the "FIT detail" section of a selected activity
	* Add glossary entry for _training load peak_; available on the "FIT detail" with the _?_
	* **Migration 20221206170000 is necessary!**
* 2022-12-07: Auto detection of type "Tempo run" (_TR_) while importing activity based on self-evaluation
	* You must configure a training-type with short-cut _TR_ to your sports (default _TR_ exists)
	* Detection only works in batch/bulk import mode
	* Works on Fenix self-evaluation (Selbsteinschätzung), where you set
		* `perceived effort` = 10 ("empfundene Anstrengung", maximum)
		* and `feel evaluation` = 1 ("Gefühl", very weak/sehr schwach)
* 2022-12-11: Show the pauses in a table on the _miscellaneous_-section
	* The pause table is only available if pauses exists
	* Add the pause number/index in the leaflet's tooltip
* 2022-12-11: Tune the combined section (heart-rate, pace, elevation)
	* Show additionally the GAP in the _Pace & heartrate_ diagram
	* Show additionally the GAP and gradient in the _Pace & heartrate & elevation_ diagram
	* Show additionally "average heart-rate for active rounds", GAP and FITs-training-effects in the combined table
* 2022-12-12: Optimize the storing of elevations regarding _original_ and _corrected_ values
	* Until now: if you have a barometric-supported device, the _corrected_ elevations additionally stored in `route.elevations_corrected` and the _original_ and _corrected_ are the same;
	  this was necessary to suppress the correction of "good barometric" data; in this cases it was not full clear in the UI about this
	* _"Correction" in this context means the altitude correction via GeoTiff due "bad" GPS elevations_
	* Now: if you have a barometric-supported device, the original elevations are stored only in `route.elevations_original` and the new flag `route.altitude_barometric` is set to true
	* In the _elevation calculation_-window now it's a clear note, if the data are from a barometric device and if there are corrected or not
	* As before, the data of barometric-supported devices are not corrected, even the configuration _user wants elevations to be corrected_ is set
	* By default all existing routes will be set to `route.altitude_barometric` = 0 = false; if you want to "migrate" existing routes to barometric-device use this:
	  `update runalyze_route set elevations_corrected = null, altitude_barometric = 1 where elevations_original = elevations_corrected and elevations_corrected is not null and elevations_source = '' and altitude_barometric = 0;`
	* If available show the ascent/desent from your device on the _elevation calculation_-window for better comparison of the calculated values
	* **Migration 20221212190000 is necessary!**
* 2022-12-27: Retrieve route name, cities and additional infos regarding your route of the activity and store it while bulk importing
	* Based on the route (GPS) coordinates, the informations are fetched from the OpenStreetMap API `Overpass`
	* If a activity has route coordinates following will be determined:
		1. peaks and sattles (order by elevation; first highest)
		2. places
		    - city>town>village>hamlet...; order by OSM-"size" of the place
		    - (alpine) huts
		    - waters (sea, lakes...)
		    - forests/woods
		3. hiking ways (like Kramersteig or Maximiliansweg)
		4. any tourism information's
	* Where possible, the _DE_ (german) `name` of OSM is used, otherwise the `name`
	* In normal cases (when all informations are available), the `training.route`, `route.name` and `route.cities` contains 1. and 2.; if the limit of 255 chars is exceeded, it will cut clean at the latest fitted name
	* The names will be seperated with the ` - ` delimiter (to extract the _often places_)
	* The `training.notes` includes informations of 3. and 4. in a section marked with `Strecken-Info` at the end of existing text
	* If some information are not found in OSM, this priority can changed
	* This works only on the bulk import
	* It's also possible to update existing activities with the command `/usr/bin/php /app/runalyze/bin/console runalyze:activity:bulk-routeval --env=prod [--nexted] [--override] username trainingId`
		* `--nexted` fetch the next activity and also update it; so long as next activities exists (like multi update)
		* `--override` overrides possible existing data with the newest determined informations
	* With the configration parameters `osm_overpass_url` or/and `osm_overpass_proxy` you can configure the `Overpass` API endpoint and a proxy-server. A good `Overpass` (without limits) is `https://overpass.kumi.systems/api/interpreter` or your own local instance. The proxy can be used for `Tor` to "hide" your IP. If no `osm_overpass_url` is configured, no determination is occured
* 2022-12-29: Weather loading is processed only, if the bulk-imported activity is "outside"
	* Support of HTTP proxy via configuration `weather_proxy` for _OpenWeatherMap_ and [MeteostatNet](https://meteostat.net/)
	* If loading via _MeteostatNet_ fails, a `Exception` will be thrown
* 2022-12-29: Fix JS error in dataview datepicker; add 20 previous years in dataview datepicker
* 2023-01-08: Add support for sports on equipment level is now relevant while bulk import
	* Currently only sports can be assigned on equipment types (=category); now these sports from the type level can be detailed on equipment level
	* This affects for a better automatic assigment during bulk imports and is **now required** for bulk import
	* This also means if you want no assignment while bulk imports, do not assign a sports on this equipment
	* Fixing: now the equipment duration&distance will be decreased, when a activities is being deleted
	* Limitations:
		* Automatic equipment assignment only works on bulk import (command) and if multiple active equipments for one type are found, this assignment will be ignored
		* If you remove in the equipment-type dialog a assigned sports, please remove it also on the equipments (if you has assigned it too); i've skimp the programm support for this ;-)
		* Not sure: but it's possible that the Runalyze backup feature is no more working with my changed regarding `equipment_spor`; test it before use it in production
	* **Migration 20230101190000 is necessary!** to create the new table `runalyze_equipment_spor`
	* **Don't forget to adds sports to your equipments, when you want auto assignment during bulk import**
* 2023-01-11: Fixing: don't loose read-only _power_ value when update a activity via the edit dialog
* 2023-01-11: Change the Borg RPE (values from 6-20) to the Borg CR10 scale (values 1-10) and Emoji's for _self-evaluation feeling_
	* When the imported activity has a Garmin Fenix self-evaluation (Selbsteinschätzung), this value will be also used for the RPE; this will not affected when the self-evaluation is used for the auto detection of the activity type (see #2022-12-07)
	* Adapt the glossary
	* During the migration existing `training.fit_self_evaluation_perceived_effort` will be assumed to RPE (if it not already set manually)
	* **Migration 20230111150000 is necessary!** to migrate your existing values to the Borg CR10
* 2023-02-03: Store additional informations of _strength training_ (=Krafttraining) in the new database field `training.splits_additional`
	* This includes the FIT informations about weight, repetitions and the excercises (only in english Sdk names) name based on the FitSdk
	* The new field `training.splits_additional` is a JSON structure, which can be used in future for other activities; details of structure see `inc/core/Parser/Activity/FileType/FitSplitsAdditionals.php`
		* Limitations of `training.splits_additional`: if you edit/change a activity, these data is not updated! but this is a general problem of Runalyze
	* The activity details are shown in the UI laps-window table; all available values of the JSON will be shown dynamic
	* **Migration 20230201200000 is necessary!**
* 2023-02-05: Show more swimming details on the UI laps-window table
	* strokes/lane, Swolf, Swolf-cycles, total-lanes, total-strokes for one (active) lane (these data are constructed from the `swimdata` array)
	* store additionally the total-strokes of one lane in the `training.splits_additional` (for better further SQL analysis)
* 2023-02-05: Store and show _caloriens_ on details on the UI laps-window table per lap
* 2023-02-19: New activity-type _E-MTB Mountain_ can be added to your account and are recognised while importing from Garmin Fenix 6
* 2023-02-25: Increase limit of concurrent Routenets and support of _Position interval_
	* Limit of shown routes at once is increased from 50 to 250; set this for your fits in `plugin/RunalyzePluginStat_Strecken/class.RunalyzePluginStat_Strecken.php`-`MAX_ROUTES_ON_NET` regarding your concurrent users, memory settings...
	* With _Position interval_ you can set to show only _every n GPS route position_ (default=All=1 show every point); this reduce the total number of shown positions and therefore the number of shown routes increases; so it is possible to show in total 1250 routes with every fifth position/point
	* Add a tooltip on the routenet-page for the routes with date, name and distance
* 2023-02-26: Fix a mismatch in the lap-table and lap-popup; in some activities a additional (1 second) round is shown in the popup
* 2023-03-06: Upgrade to PHP 7.4 and Debian 11
	* Upgrade to `phpunit` v8.5 including adaptions of the tests
	* Update of some dependencies to newer (minor) versions
	* Adapt poster generations (type _circular_) `inkscape` v1.0.2 of Debian 11 (change in the parameters)

Please notice:
* All the changes are only done for me to use this great product for me.
* I don't take any responsibility if you running this version on your infrastructure and have problems.
* The extensions i made was done on a release version. So i do not build a release. I have no translations for the new features (always use german language).

## Database migration

For Migration of the Database use the commands:
- Check state: `/usr/bin/php /app/runalyze/bin/console doctrine:migrations:status --env=prod --show-versions`
- Do it: `/usr/bin/php /app/runalyze/bin/console doctrine:migrations:migrate --env=prod [--dry-run]`
- Rollback to an previous version: `/usr/bin/php /app/runalyze/bin/console doctrine:migrations:migrate --env=prod <VersionAufDieZurückgerolltWerdenSoll>`
Notice: Do the migration with your "project" user.
