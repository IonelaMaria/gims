<?php

namespace Application\Service\Syntax;

use Doctrine\Common\Collections\ArrayCollection;
use Application\Service\Calculator\Jmp;

/**
 * Replace {F#12,Q#all} with a list of Filter values for all questionnaires
 */
class RegressionFilterValuesList extends AbstractRegressionToken
{

    public function getPattern()
    {
        return '/\{F#(\d+|current),Q#all\}/';
    }

    public function replace(Jmp $calculator, array $matches, $currentFilterId, array $questionnaires, $currentPartId, $year, array $years, ArrayCollection $alreadyUsedRules)
    {
        $filterId = $this->getId($matches[1], $currentFilterId);

        $data = $calculator->computeFilterForAllQuestionnaires($filterId, $questionnaires, $currentPartId);

        $values = array();
        foreach ($data['values'] as $v) {
            if (!is_null($v)) {
                $values[] = $v;
            }
        }

        $values = '{' . implode(', ', $values) . '}';

        return $values;
    }

    public function getStructure(array $matches, Parser $parser)
    {
        return [
            'type' => 'regressionFilterValuesList',
            'filter' => $this->getFilterName($matches[1], $parser),
        ];
    }
}
