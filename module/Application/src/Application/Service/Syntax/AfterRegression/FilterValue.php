<?php

namespace Application\Service\Syntax\AfterRegression;

use Application\Service\Calculator\Calculator;
use Application\Service\Syntax\Parser;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Replace {F#12,P#34,Y+0} with Filter regression value
 */
class FilterValue extends AbstractToken implements \Application\Service\Syntax\BothContextInterface
{

    public function getPattern()
    {
        return '/\{F#(\d+|current),P#(\d+|current),Y([+-]?\d+)\}/';
    }

    public function replace(Calculator $calculator, array $matches, $currentFilterId, array $questionnaires, $currentPartId, $year, ArrayCollection $alreadyUsedRules)
    {
        $filterId = $this->getId($matches[1], $currentFilterId);
        $partId = $this->getId($matches[2], $currentPartId);
        $yearOffset = $matches[3];
        $year += $yearOffset;

        // Only compute thing if in current years, to avoid infinite recursitivy in a very distant future
        if (in_array($year, $calculator->getYears())) {
            $value = $calculator->computeFlattenOneYearWithFormula($year, $filterId, $questionnaires, $partId);
        } else {
            $value = null;
        }

        return is_null($value) ? 'NULL' : $value;
    }

    public function getStructure(array $matches, Parser $parser)
    {
        $year = (int) $matches[3];
        if ($year > 0) {
            $year = '+' . $year;
        }

        return [
            'type' => 'regressionFilterValue',
            'filter' => [
                'id' => $matches[1],
                'name' => $parser->getFilterName($matches[1]),
            ],
            'part' => [
                'id' => $matches[2],
                'name' => $parser->getPartName($matches[2]),
            ],
            'year' => (string) $year,
        ];
    }
}
