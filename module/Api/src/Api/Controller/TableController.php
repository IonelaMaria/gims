<?php

namespace Api\Controller;

use Application\View\Model\NumericJsonModel;
use Application\View\Model\ExcelModel;
use Application\Model\Geoname;

class TableController extends \Application\Controller\AbstractAngularActionController
{

    private $parts; // used for cache

    /**
     * @var \Application\Service\Calculator\Calculator
     */
    private $calculator;

    /**
     * Get the calculator shared instance
     * @return \Application\Service\Calculator\Calculator
     */
    private function getCalculator()
    {
        if (!$this->calculator) {
            $this->calculator = new \Application\Service\Calculator\Calculator();
            $this->calculator->setServiceLocator($this->getServiceLocator());
        }

        return $this->calculator;
    }

    public function filterAction()
    {
        $filterSet = $this->getEntityManager()->getRepository('Application\Model\FilterSet')->findOneById($this->params()->fromQuery('filterSet'));

        $result = array();
        if ($filterSet) {

            $questionnaireParameter = $this->params()->fromQuery('questionnaire');
            $idQuestionnaires = explode(',', $questionnaireParameter);
            $questionnaireRepository = $this->getEntityManager()->getRepository('Application\Model\Questionnaire');
            $parts = $this->getEntityManager()->getRepository('Application\Model\Part')->findAll();

            foreach ($idQuestionnaires as $idQuestionnaire) {
                $questionnaire = $questionnaireRepository->find($idQuestionnaire);
                if ($questionnaire) {

                    // Do the actual computing for all filters
                    $resultOneQuestionnaire = array();
                    foreach ($filterSet->getFilters() as $filter) {
                        $resultOneQuestionnaire = array_merge($resultOneQuestionnaire, $this->computeWithChildren($questionnaire, $filter, $parts, 0, array('name')));
                    }

                    // Merge this questionnaire results with other questionnaire results
                    foreach ($resultOneQuestionnaire as $i => $data) {
                        if (isset($result[$i])) {
                            $result[$i]['values'][] = reset($data['values']);
                        } else {
                            $result[] = $data;
                        }
                    }
                }
            }
        }

        return new NumericJsonModel($result);
    }

    /**
     * Compute value for the given filter and all its children recursively.
     * @param \Application\Model\Questionnaire $questionnaire
     * @param \Application\Model\Filter $filter
     * @param array $parts
     * @param integer $level the level of the current filter in the filter tree
     * @param array $fields
     * @param array $overridenFilters
     * @param bool $userSecondLevelRules
     * @return array a list (not tree) of all filters with their values and tree level
     */
    public function computeWithChildren(\Application\Model\Questionnaire $questionnaire, \Application\Model\Filter $filter, array $parts, $level = 0, $fields = array(), $overridenFilters = array(), $userSecondLevelRules = false)
    {
        $calculator = new \Application\Service\Calculator\Calculator();
        $calculator->setServiceLocator($this->getServiceLocator());
        $calculator->setOverriddenFilters($overridenFilters);
        $hydrator = new \Application\Service\Hydrator();

        $current = array();
        $current['filter'] = $hydrator->extract($filter, $fields);
        $current['filter']['level'] = $level;

        foreach ($parts as $part) {
            $computed = $calculator->computeFilter($filter->getId(), $questionnaire->getId(), $part->getId(), $userSecondLevelRules, null);
            // Round the value
            $value = \Application\Utility::decimalToRoundedPercent($computed);
            $current['values'][0][$part->getName()] = $value;
        }

        // Compute children
        $result = array($current);
        foreach ($filter->getChildren() as $child) {
            $result = array_merge($result, $this->computeWithChildren($questionnaire, $child, $parts, $level + 1, $fields, $overridenFilters, $userSecondLevelRules));
        }

        return $result;
    }

    public function questionnaireAction()
    {
        $p = $this->params()->fromQuery('country');
        if (!$p) {
            $countryIds = array(-1);
        } else {
            $countryIds = explode(',', $p);
        }
        $countries = $this->getEntityManager()->getRepository('Application\Model\Country')->findById($countryIds);

        /** @var \Application\Model\FilterSet $filterSet */
        $filterSet = $this->getEntityManager()->getRepository('Application\Model\FilterSet')->findOneById($this->params()->fromQuery('filterSet'));
        $parts = $this->getEntityManager()->getRepository('Application\Model\Part')->findAll();
        $calculator = new \Application\Service\Calculator\Calculator();
        $calculator->setServiceLocator($this->getServiceLocator());

        $result = array();
        $columns = array(
            'country' => 'Country',
            'iso3' => 'ISO-3',
            'survey' => 'Survey',
            'year' => 'Year',
        );
        $legends = [];

        foreach ($countries as $country) {
            $questionnaires = $this->getEntityManager()->getRepository('Application\Model\Questionnaire')->getAllForComputing($country->getGeoname());
            if (!$filterSet) {
                continue;
            }

            foreach ($parts as $part) {
                foreach ($filterSet->getFilters() as $filter) {
                    $columnNames = $this->getColumnNames($filterSet, $part, $filter->getName());
                    $columnId = 'f' . $filter->getId() . 'p' . $part->getId();
                    $columns[$columnId] = $columnNames['short'];
                    $legends[] = $columnNames;

                    $data = $calculator->computeFilterForAllQuestionnaires($filter->getId(), $questionnaires, $part->getId());
                    foreach ($data['values'] as $questionnaireId => $value) {
                        if (!isset($result[$questionnaireId])) {
                            $result[$questionnaireId] = array(
                                'country' => $country->getName(),
                                'iso3' => $country->getIso3(),
                                'survey' => $data['surveys'][$questionnaireId],
                                'year' => $data['years'][$questionnaireId],
                            );
                        }

                        $result[$questionnaireId][$columnId] = \Application\Utility::decimalToRoundedPercent($value);
                    }
                }
            }
        }
        $finalResult = array(
            'columns' => $columns,
            'legends' => $legends,
            'data' => array_values($result),
        );

        $filename = $this->params('filename');
        if ($filename) {
            return new ExcelModel($filename, $finalResult);
        } else {
            return new NumericJsonModel($finalResult);
        }
    }

    public function countryAction()
    {
        $geonameIds = explode(',', $this->params()->fromQuery('geonames', -1));
        $years = $this->getWantedYears($this->params()->fromQuery('years'));
        $geonames = $this->getEntityManager()->getRepository('Application\Model\Geoname')->findById($geonameIds);

        /** @var \Application\Model\FilterSet $filterSet */
        $filterSet = $this->getEntityManager()->getRepository('Application\Model\FilterSet')->findOneById($this->params()->fromQuery('filterSet'));
        $parts = $this->getEntityManager()->getRepository('Application\Model\Part')->findAll();

        $result = array();
        $columns = array(
            'country' => 'Country',
            'iso3' => 'ISO-3',
            'year' => 'Year',
        );
        $legends = [];

        // Build population acronyms and add them to columns definition
        $populationAcronyms = array();
        $partsById = []; // used for cache
        foreach ($parts as $part) {
            $partsById[$part->getId()] = $part;
            $acronym = 'P' . strtoupper($part->getName()[0]);
            $populationAcronyms[$part->getId()] = $acronym;
            $columns[$acronym] = $acronym;
            $legends[] = [
                'short' => $acronym,
                'long' => 'Population, ' . $part->getName(),
            ];
        }

        foreach ($geonames as $geoname) {

            if (!$filterSet) {
                continue;
            }

            $allYearsComputed = $this->getAllYearsComputed($parts, $filterSet, $geoname);
            $filteredYearsComputed = $this->filterYears($allYearsComputed, $years);

            foreach ($years as $year) {

                // country info columns
                $countryData = array(
                    'country' => $geoname->getName(),
                    'iso3' => $geoname->getCountry() ? $geoname->getCountry()->getIso3() : null,
                    'year' => $year
                );

                // population columns
                $populationData = array();
                foreach ($parts as $part) {
                    $populationData[$populationAcronyms[$part->getId()]] = $this->getPopulation($geoname, $part->getId(), $year);
                }

                $statsData = array();
                $count = 1;
                foreach ($filteredYearsComputed as $partId => $filters) {

                    foreach ($filters as $filter) {
                        $columnId = 'c' . $count;
                        $columnNames = $this->getColumnNames($filterSet, $partsById[$partId], $filter['name']);
                        $columns[$columnId] = $columnNames['short'];
                        $legends[] = $columnNames;

                        $value = $filter['data'][$year];
                        $statsData[$columnId] = \Application\Utility::decimalToRoundedPercent($value);

                        // Do absolute version
                        $columnId = $columnId . 'a';
                        $columnNames['short'] = $columnNames['short'] . 'a';
                        $columnNames['long'] = $columnNames['long'] . ' (absolute value)';
                        $legends[] = $columnNames;
                        $columns[$columnId] = $columnNames['short'];
                        $statsData[$columnId] = is_null($value) ? null : (int) ($value * $this->getPopulation($geoname, $partId, $year));
                        $count++;
                    }
                }

                $result[] = array_merge($countryData, $populationData, $statsData);
            }
        }

        $finalResult = array(
            'columns' => $columns,
            'legends' => $legends,
            'data' => array_values($result)
        );

        $filename = $this->params('filename');
        if ($filename) {
            return new ExcelModel($filename, $finalResult);
        } else {
            return new NumericJsonModel($finalResult);
        }
    }

    /**
     * Returns the population for the geoname or its children total
     * @param \Application\Model\Geoname $geoname
     * @param integer $partId
     * @param integer $year
     * @return integer
     */
    private function getPopulation(\Application\Model\Geoname $geoname, $partId, $year)
    {
        $populationRepository = $this->getEntityManager()->getRepository('Application\Model\Population');
        $pop = $populationRepository->getOneByGeoname($geoname, $partId, $year);

        if ($pop) {
            return $pop->getPopulation();
        } else {
            $populationTotal = null;
            foreach ($geoname->getChildren() as $child) {
                $populationTotal += $this->getPopulation($child, $partId, $year);
            }

            return $populationTotal;
        }
    }

    /**
     * @param $parts
     * @param \Application\Model\FilterSet $filterSet
     * @param \Application\Model\Geoname $geoname
     * @return array all data ordered by part
     */
    private function getAllYearsComputed($parts, \Application\Model\FilterSet $filterSet, Geoname $geoname)
    {
        $aggregator = new \Application\Service\Calculator\Aggregator();
        $aggregator->setCalculator($this->getCalculator());

        $dataPerPart = array();
        foreach ($parts as $part) {
            $dataPerPart[$part->getId()] = $aggregator->computeFlattenAllYears(1980, 2015, $filterSet->getFilters()->toArray(), $geoname, $part);
        }

        return $dataPerPart;
    }

    /**
     * @param $fieldParts
     * @param $years
     * @return array Filter ordered by part and with only wanted years.
     */
    private function filterYears($fieldParts, $years)
    {
        $finalFieldsets = array();
        foreach ($fieldParts as $partId => $filters) {
            $finalFieldsets[$partId] = array();
            foreach ($filters as $filter) {
                $yearsData = array();
                foreach ($years as $year) {
                    $yearsData[$year] = $filter['data'][$year - 1980];
                }
                $tmpFieldset = array(
                    'name' => $filter['name'],
                    'data' => $yearsData
                );
                $finalFieldsets[$partId][] = $tmpFieldset;
            }
        }

        return $finalFieldsets;
    }

    /**
     * Decode the syntax of wanted years
     * @param $years
     * @return array of years
     */
    private function getWantedYears($years)
    {
        $ranges = explode(',', $years);
        $finalYears = [];
        foreach ($ranges as $range) {
            $range = trim($range, ' ');
            if (!strpos($range, '-')) {
                $finalYears[] = $range;
            } else {
                $startAndEndYear = explode('-', $range);
                $finalYears = array_merge($finalYears, range($startAndEndYear[0], $startAndEndYear[1]));
            }
        }
        $finalYears = array_unique($finalYears);
        sort($finalYears);

        return $finalYears;
    }

    /**
     * Retrieve column names, short and long version
     * @param \Application\Model\FilterSet $filterset
     * @param \Application\Model\Part $part
     * @param string $filterName
     * @return array ['short' => short name, 'long' => long name]
     */
    private function getColumnNames(\Application\Model\FilterSet $filterset, \Application\Model\Part $part, $filterName)
    {
        // Filterset first letter
        $filtersetL = substr($filterset->getName(), 0, 1);

        // Part first letter
        $partL = substr($part->getName(), 0, 1);

        // Filter first letters of each word
        $filterL = '';
        $words = explode(' ', $filterName);
        foreach ($words as $word) {
            $filterL .= substr($word, 0, 1);
        }

        return [
            'short' => strtoupper($filtersetL . $partL . $filterL),
            'long' => implode(', ', [preg_split('/\W/', $filterset->getName())[0], $part->getName(), $filterName]),
        ];
    }

}
