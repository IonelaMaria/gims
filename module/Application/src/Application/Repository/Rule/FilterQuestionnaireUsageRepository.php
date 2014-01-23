<?php

namespace Application\Repository\Rule;

use Doctrine\Common\Collections\ArrayCollection;

class FilterQuestionnaireUsageRepository extends \Application\Repository\AbstractRepository
{

    /**
     * @var array $cache [questionnaireId => [filterId => [partId => value]]]
     */
    private $cache = array();

    /**
     * Return the first FilterQuestionUsage
     * @param integer $questionnaireId
     * @param integer $filterId
     * @param integer $partId
     * @param boolean $useSecondLevelRules
     * @param \Doctrine\Common\Collections\ArrayCollection $excluded
     * @return FilterQuestionnaireUsage|null
     */
    public function getFirst($questionnaireId, $filterId, $partId, $useSecondLevelRules, ArrayCollection $excluded)
    {
        // If no cache for questionnaire, fill the cache
        if (!isset($this->cache[$questionnaireId])) {

            // First we found which geoname is used for the given questionnaire
            $geonameId = $this->getEntityManager()->getRepository('Application\Model\Geoname')->getIdByQuestionnaireId($questionnaireId);

            // Then we get all data for the geoname
            $qb = $this->createQueryBuilder('filterQuestionnaireUsage')
                    ->select('filterQuestionnaireUsage, questionnaire, filter, rule')
                    ->join('filterQuestionnaireUsage.questionnaire', 'questionnaire')
                    ->join('filterQuestionnaireUsage.filter', 'filter')
                    ->join('filterQuestionnaireUsage.rule', 'rule')
                    ->andWhere('questionnaire.geoname = :geoname')
                    ->orderBy('filterQuestionnaireUsage.isSecondLevel DESC, filterQuestionnaireUsage.sorting, filterQuestionnaireUsage.id')
            ;

            $qb->setParameters(array(
                'geoname' => $geonameId,
            ));

            $res = $qb->getQuery()->getResult();

            // Ensure that we hit the cache next time, even if we have no results at all
            $this->cache[$questionnaireId] = array();

            // Restructure cache to be [questionnaireId => [filterId => [partId => value]]]
            foreach ($res as $filterQuestionnaireUsage) {
                $this->cache[$filterQuestionnaireUsage->getQuestionnaire()->getId()][$filterQuestionnaireUsage->getFilter()->getId()][$filterQuestionnaireUsage->getPart()->getId()][] = $filterQuestionnaireUsage;
            }
        }

        if (isset($this->cache[$questionnaireId][$filterId][$partId]))
            $possible = $this->cache[$questionnaireId][$filterId][$partId];
        else
            $possible = array();

        // Returns the first non-excluded and according to its level
        foreach ($possible as $filterQuestionnaireUsage) {
            if (($useSecondLevelRules || !$filterQuestionnaireUsage->isSecondLevel()) && !$excluded->contains($filterQuestionnaireUsage))
                return $filterQuestionnaireUsage;
        }

        return null;
    }

}
