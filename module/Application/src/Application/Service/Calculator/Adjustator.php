<?php

namespace Application\Service\Calculator;

use Application\Model\Questionnaire;
use Application\Model\Part;
use Application\Model\Filter;

/**
 * Class used to "adjust" a trend line over another one by overriding some values.
 * Those overriding values are a "best-guess" effort.
 */
class Adjustator
{

    /**
     * @var Jmp
     */
    private $calculator;

    /**
     * @var Filter
     */
    private $target;

    /**
     * @var Filter
     */
    private $reference;

    /**
     * @var Filter
     */
    private $overridable;

    /**
     * @var array
     */
    private $questionnaires;

    /**
     * @var Part
     */
    private $part;

    public function setCalculator(Jmp $calculator)
    {
        $this->calculator = $calculator;
    }

    private function initObjects(Filter $target, Filter $reference, Filter $overridable, array $questionnaires, Part $part)
    {
        $this->target = $target;
        $this->reference = $reference;
        $this->overridable = $overridable;
        $this->questionnaires = $questionnaires;
        $this->part = $part;
    }

    /**
     * Returns an array containing values that should be used to override filter,
     * in order to "move" $reference as close as possible to $target, by overriding $overridable
     * @param \Application\Model\Filter $target The filter to move close to
     * @param \Application\Model\Filter $reference The filter that will move
     * @param \Application\Model\Filter $overridable The filter that will be overridden in order to move $reference
     * @param array $questionnaires
     * @param \Application\Model\Part $part
     * @return array
     */
    public function findOverriddenFilters(Filter $target, Filter $reference, Filter $overridable, array $questionnaires, Part $part)
    {
        $this->initObjects($target, $reference, $overridable, $questionnaires, $part);

        $referenceQuestionnaires = $this->getReferenceQuestionnaires();
        if (!$referenceQuestionnaires) {
            return array();
        }

        $targetValues = $this->getTargetValues($referenceQuestionnaires);

        // foreach years-target, find best value for reference
        $overriddenFilters = [];
        foreach ($referenceQuestionnaires as $q) {
            $overrideValue = $this->findBestOverrideValue($q, $targetValues[$q->getSurvey()->getYear()]);
            $overriddenFilters[$q->getId()][$this->overridable->getId()][$this->part->getId()] = $overrideValue;
        }

        return $overriddenFilters;
    }

    /**
     * Return an array containing original values before projection in order to allow difference computation
     * @param Filter $target
     * @param Filter $reference
     * @param Filter $overridable
     * @param array $questionnaires
     * @param Part $part
     * @return array$
     */
    public function getOriginalOverrideValues(Filter $target, Filter $reference, Filter $overridable, array $questionnaires, Part $part)
    {
        $this->initObjects($target, $reference, $overridable, $questionnaires, $part);

        $referenceQuestionnaires = $this->getReferenceQuestionnaires();
        if (!$referenceQuestionnaires) {
            return array();
        }

        $originalValues = [];
        $this->calculator->setOverriddenFilters(array());
        foreach ($referenceQuestionnaires as $q) {
            $originalValue = $this->calculator->computeFilter($this->overridable->getId(), $q->getId(), $this->part->getId());
            $originalValues[$q->getId()][$this->overridable->getId()][$this->part->getId()] = $originalValue;
        }

        return $originalValues;
    }

    /**
     * Find all questionnaires used for the reference filter
     * @return array
     */
    private function getReferenceQuestionnaires()
    {
        $availableQuestionnaire = [];
        foreach ($this->questionnaires as $questionnaire) {
            $result = $this->calculator->computeFilter($this->reference->getId(), $questionnaire->getId(), $this->part->getId());
            if (!is_null($result)) {
                $availableQuestionnaire[] = $questionnaire;
            }
        }

        return $availableQuestionnaire;
    }

    /**
     * Find the target value for each year (of each questionnaire)
     * @param array $referenceQuestionnaires
     * @return array [year => value]
     */
    private function getTargetValues(array $referenceQuestionnaires)
    {
        $allYears = [];
        foreach ($referenceQuestionnaires as $questionnaire) {
            $year = $questionnaire->getSurvey()->getYear();
            $allYears[] = $year;
        }

        $yearMin = min($allYears);
        $yearMax = max($allYears);

        $flattenValues = $this->calculator->computeFlattenAllYears($yearMin, $yearMax, [$this->target], $this->questionnaires, $this->part);

        $targetValues = [];
        $i = 0;
        foreach (range($yearMin, $yearMax) as $year) {
            if (in_array($year, $allYears)) {
                $targetValues[$year] = $flattenValues[0]['data'][$i];
            }
            $i++;
        }

        return $targetValues;
    }

    /**
     * Try to find the best value to use as override in order to be closest to $targetValue.
     * The algorithm is dichotomy based, and inspired by the number-guessing game (where the other guy tells you "bigger"/"smaller")
     * @param \Application\Model\Questionnaire $questionnaire
     * @param float $targetValue
     * @return float
     */
    private function findBestOverrideValue(Questionnaire $questionnaire, $targetValue)
    {
        $this->calculator->setOverriddenFilters(array());
        $margin = 0.01 * $targetValue; // Give us a margin of +/-1% around the target
        $lowerLimit = 0;
        $currentValue = $this->calculator->computeFilter($this->reference->getId(), $questionnaire->getId(), $this->part->getId());
        $overrideValue = $this->calculator->computeFilter($this->overridable->getId(), $questionnaire->getId(), $this->part->getId());
        $higherLimit = $overrideValue * 4; // We assume that the it's usually less than 4 times bigger than current value

        $attempt = 0;
        while (($currentValue > $targetValue + $margin || $currentValue < $targetValue - $margin) && $attempt < 100 && $overrideValue != 0) {

            // If we are too high, try lower
            if ($currentValue > $targetValue + $margin) {
                $higherLimit = min($overrideValue, $higherLimit);
            }
            // If we are too low, try higher
            else {
                $lowerLimit = max($overrideValue, $lowerLimit);

                // If lower and higher limit are too close, that means that the maximum assumed
                // at the beginning was too low and we need to artificially raise the maximum
                if ($lowerLimit + $margin >= $higherLimit) {
                    $higherLimit = $higherLimit * 2;
                }
            }

            $overrideValue = ($lowerLimit + $higherLimit) / 2;

            // If the overriden value is almost zero, we force it to be exactly zero
            // to allow us to break the loop early, because we will never go lower than zero
            if (0 === bccomp($overrideValue, 0, 5)) {
                $overrideValue = 0;
            }

            $overriddenFilters = [$questionnaire->getId() => [$this->overridable->getId() => [$this->part->getId() => $overrideValue]]];
            $this->calculator->setOverriddenFilters($overriddenFilters);
            $currentValue = $this->calculator->computeFilter($this->reference->getId(), $questionnaire->getId(), $this->part->getId());
            $attempt++;
        }

        return $overrideValue;
    }

}
