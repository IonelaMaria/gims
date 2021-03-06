<?php

namespace ApplicationTest\Service\Calculator;

use Doctrine\Common\Collections\ArrayCollection;

abstract class AbstractCalculator extends \ApplicationTest\Controller\AbstractController
{

    /**
     * @var \Application\Model\Geoname
     */
    protected $geoname;

    /**
     * @var \Application\Model\Geoname
     */
    protected $geoname2;

    /**
     * @var \Application\Model\Filter
     */
    protected $filter1;

    /**
     * @var \Application\Model\Filter
     */
    protected $filter11;

    /**
     * @var \Application\Model\Filter
     */
    protected $filter12;

    /**
     * @var \Application\Model\Filter
     */
    protected $filter13;

    /**
     * @var \Application\Model\Filter
     */
    protected $filter131;

    /**
     * @var \Application\Model\Filter
     */
    protected $filter132;

    /**
     * @var \Application\Model\Filter
     */
    protected $filter14;

    /**
     * @var \Application\Model\Filter
     */
    protected $filter141;

    /**
     * @var \Application\Model\Filter
     */
    protected $filter142;

    /**
     * @var \Application\Model\Filter
     */
    protected $filter2;

    /**
     * @var \Application\Model\Filter
     */
    protected $filter21;

    /**
     * @var \Application\Model\Filter
     */
    protected $filter3;

    /**
     * @var \Application\Model\Filter
     */
    protected $filter31;

    /**
     * @var \Application\Model\Filter
     */
    protected $filter32;

    /**
     * @var \Application\Model\Questionnaire
     */
    protected $questionnaire;

    /**
     * @var \Application\Model\Question\NumericQuestion
     */
    protected $question131;

    /**
     * @var \Application\Model\Question\NumericQuestion
     */
    protected $question132;

    /**
     * @var \Application\Model\Question\NumericQuestion
     */
    protected $question141;

    /**
     * @var \Application\Model\Question\NumericQuestion
     */
    protected $question142;

    /**
     * @var \Application\Model\Question\NumericQuestion
     */
    protected $question31;

    /**
     * @var \Application\Model\Question\NumericQuestion
     */
    protected $question32;

    /**
     * @var \Application\Model\Answer
     */
    protected $answer131;

    /**
     * @var \Application\Model\Answer
     */
    protected $answer132;

    /**
     * @var \Application\Model\Answer
     */
    protected $answer141;

    /**
     * @var \Application\Model\Answer
     */
    protected $answer142;

    /**
     * @var \Application\Model\Answer
     */
    protected $answer31;

    /**
     * @var \Application\Model\Answer
     */
    protected $answer32;

    /**
     * @var \Application\Model\Filter
     */
    protected $highFilter1;

    /**
     * @var \Application\Model\Filter
     */
    protected $highFilter2;

    /**
     * @var \Application\Model\Filter
     */
    protected $highFilter3;

    /**
     * @var \Application\Model\Part
     */
    protected $part1;

    /**
     * @var \Application\Model\Part
     */
    protected $part2;

    /**
     * @var \Application\Model\Part
     */
    protected $partTotal;

    public function setUp()
    {
        parent::setUp();

        $this->geoname = $this->getNewModelWithId('\Application\Model\Geoname')->setName('test geoname');
        $this->geoname2 = $this->getNewModelWithId('\Application\Model\Geoname')->setName('test geoname 2');

        $this->filter1 = $this->getNewModelWithId('\Application\Model\Filter')->setName('Filter 1');
        $this->filter11 = $this->getNewModelWithId('\Application\Model\Filter')->setName('Filter 1.1 (sum of 1.*.1)');
        $this->filter12 = $this->getNewModelWithId('\Application\Model\Filter')->setName('Filter 1.2 (sum of 1.*.2)');
        $this->filter13 = $this->getNewModelWithId('\Application\Model\Filter')->setName('Filter 1.3');
        $this->filter131 = $this->getNewModelWithId('\Application\Model\Filter')->setName('Filter 1.3.1');
        $this->filter132 = $this->getNewModelWithId('\Application\Model\Filter')->setName('Filter 1.3.2');
        $this->filter14 = $this->getNewModelWithId('\Application\Model\Filter')->setName('Filter 1.4');
        $this->filter141 = $this->getNewModelWithId('\Application\Model\Filter')->setName('Filter 1.4.1');
        $this->filter142 = $this->getNewModelWithId('\Application\Model\Filter')->setName('Filter 1.4.2');
        $this->filter2 = $this->getNewModelWithId('\Application\Model\Filter')->setName('Filter 2');
        $this->filter21 = $this->getNewModelWithId('\Application\Model\Filter')->setName('Filter 2.1');
        $this->filter3 = $this->getNewModelWithId('\Application\Model\Filter')->setName('Filter 3 (sum of 2.* but with children as default to)');
        $this->filter31 = $this->getNewModelWithId('\Application\Model\Filter')->setName('Filter 3.1');
        $this->filter32 = $this->getNewModelWithId('\Application\Model\Filter')->setName('Filter 3.2');

        // Define tree structure
        $this->filter1->addChild($this->filter11)->addChild($this->filter12)->addChild($this->filter13)->addChild($this->filter14);
        $this->filter13->addChild($this->filter131)->addChild($this->filter132);
        $this->filter14->addChild($this->filter141)->addChild($this->filter142);
        $this->filter2->addChild($this->filter21);
        $this->filter3->addChild($this->filter31)->addChild($this->filter32);

        // Define filters with summands
        $this->filter11->addSummand($this->filter131)->addSummand($this->filter141);
        $this->filter12->addSummand($this->filter132)->addSummand($this->filter142);
        $this->filter3->addSummand($this->filter21);

        // Define questionnaire with answers for leaf filters only
        $survey = new \Application\Model\Survey('Test survey 1');
        $survey->setCode('tst 1')->setYear(2000);

        // Create a stub for the Questionnaire class with fake ID, so we don't have to mess with database
        $this->questionnaire = $this->getNewModelWithId('\Application\Model\Questionnaire');
        $this->questionnaire->setSurvey($survey)->setGeoname($this->geoname);

        $this->question131 = new \Application\Model\Question\NumericQuestion('Question 1.3.1');
        $this->question132 = new \Application\Model\Question\NumericQuestion('Question 1.3.2');
        $this->question141 = new \Application\Model\Question\NumericQuestion('Question 1.4.1');
        $this->question142 = new \Application\Model\Question\NumericQuestion('Question 1.4.2');
        $this->question31 = new \Application\Model\Question\NumericQuestion('Question 3.1');
        $this->question32 = new \Application\Model\Question\NumericQuestion('Question 3.2');

        $this->question131->setFilter($this->filter131);
        $this->question132->setFilter($this->filter132);
        $this->question141->setFilter($this->filter141);
        $this->question142->setFilter($this->filter142);
        $this->question31->setFilter($this->filter31);
        $this->question32->setFilter($this->filter32);

        // Create a stub for the Part class
        $this->part1 = $this->getNewModelWithId('\Application\Model\Part')->setName('tst part 1');
        $this->part2 = $this->getNewModelWithId('\Application\Model\Part')->setName('tst part 2');
        $this->partTotal = $this->getNewModelWithId('\Application\Model\Part', ['isTotal' => $this->returnValue(true)])->setName('tst part total');

        $this->answer131 = new \Application\Model\Answer();
        $this->answer132 = new \Application\Model\Answer();
        $this->answer141 = new \Application\Model\Answer();
        $this->answer142 = new \Application\Model\Answer();
        $this->answer31 = new \Application\Model\Answer();
        $this->answer32 = new \Application\Model\Answer();

        $this->answer131->setPart($this->part1)->setQuestionnaire($this->questionnaire)->setQuestion($this->question131)->setValuePercent(0.1);
        $this->answer132->setPart($this->part1)->setQuestionnaire($this->questionnaire)->setQuestion($this->question132)->setValuePercent(0.01);
        $this->answer141->setPart($this->part1)->setQuestionnaire($this->questionnaire)->setQuestion($this->question141)->setValuePercent(0.001);
        $this->answer142->setPart($this->part1)->setQuestionnaire($this->questionnaire)->setQuestion($this->question142)->setValuePercent(0.0001);
        $this->answer31->setPart($this->part1)->setQuestionnaire($this->questionnaire)->setQuestion($this->question31)->setValuePercent(0.00001);
        $this->answer32->setPart($this->part1)->setQuestionnaire($this->questionnaire)->setQuestion($this->question32)->setValuePercent(0.000001);

        $this->highFilter1 = $this->getNewModelWithId('\Application\Model\Filter')->setName('improved');
        $this->highFilter2 = $this->getNewModelWithId('\Application\Model\Filter')->setName('unimproved');
        $this->highFilter3 = $this->getNewModelWithId('\Application\Model\Filter')->setName('total');

        $this->highFilter1->addChild($this->filter1);
        $this->highFilter2->addChild($this->filter2);
        $this->highFilter3->addChild($this->filter1)->addChild($this->filter2)->addChild($this->filter3);
    }

    protected function getStubPopulationRepository()
    {
        // Create a stub for the PopulationRepository class with predetermined values, so we don't have to mess with database
        $stubPopulationRepository = $this->getMock('\Application\Repository\PopulationRepository', ['getPopulationByGeoname'], [], '', false);
        $stubPopulationRepository->expects($this->any())
                ->method('getPopulationByGeoname')
                ->will($this->returnValueMap([
                            [$this->geoname, $this->part1->getId(), 1980, null, 10],
                            [$this->geoname, $this->part1->getId(), 1981, null, 10],
                            [$this->geoname, $this->part1->getId(), 1982, null, 10],
                            [$this->geoname, $this->part1->getId(), 1983, null, 10],
                            [$this->geoname, $this->part1->getId(), 1984, null, 10],
                            [$this->geoname, $this->part1->getId(), 1985, null, 10],
                            [$this->geoname, $this->part1->getId(), 1986, null, 10],
                            [$this->geoname, $this->part1->getId(), 1987, null, 10],
                            [$this->geoname, $this->part1->getId(), 1988, null, 10],
                            [$this->geoname, $this->part1->getId(), 1989, null, 10],
                            [$this->geoname, $this->part1->getId(), 1990, null, 10],
                            [$this->geoname, $this->part1->getId(), 1991, null, 10],
                            [$this->geoname, $this->part1->getId(), 1992, null, 10],
                            [$this->geoname, $this->part1->getId(), 1993, null, 10],
                            [$this->geoname, $this->part1->getId(), 1994, null, 10],
                            [$this->geoname, $this->part1->getId(), 1995, null, 10],
                            [$this->geoname, $this->part1->getId(), 1996, null, 10],
                            [$this->geoname, $this->part1->getId(), 1997, null, 10],
                            [$this->geoname, $this->part1->getId(), 1998, null, 10],
                            [$this->geoname, $this->part1->getId(), 1999, null, 10],
                            [$this->geoname, $this->part1->getId(), 2000, null, 10],
                            [$this->geoname, $this->part1->getId(), 2001, null, 10],
                            [$this->geoname, $this->part1->getId(), 2002, null, 12],
                            [$this->geoname, $this->part1->getId(), 2003, null, 13],
                            [$this->geoname, $this->part1->getId(), 2004, null, 14],
                            [$this->geoname, $this->part1->getId(), 2005, null, 15],
                            [$this->geoname, $this->part1->getId(), 2006, null, 15],
                            [$this->geoname, $this->part1->getId(), 2007, null, 15],
                            [$this->geoname, $this->part1->getId(), 2008, null, 15],
                            [$this->geoname, $this->part1->getId(), 2009, null, 15],
                            [$this->geoname, $this->part1->getId(), 2010, null, 15],
                            [$this->geoname, $this->part1->getId(), 2011, null, 15],
                            [$this->geoname, $this->part1->getId(), 2012, null, 15],
                            [$this->geoname, $this->part1->getId(), 2013, null, 15],
                            [$this->geoname, $this->part1->getId(), 2014, null, 15],
                            [$this->geoname, $this->part1->getId(), 2015, null, 15],
                            [$this->geoname, $this->part2->getId(), 2000, null, 3],
                            [$this->geoname, $this->part2->getId(), 2001, null, 3],
                            [$this->geoname, $this->part2->getId(), 2005, null, 3],
                            [$this->geoname, $this->partTotal->getId(), 2000, null, 7],
                            [$this->geoname, $this->partTotal->getId(), 2001, null, 7],
                            [$this->geoname, $this->partTotal->getId(), 2005, null, 12],
                            [$this->geoname2, $this->part1->getId(), 1980, null, 30],
                            [$this->geoname2, $this->part1->getId(), 1981, null, 30],
                            [$this->geoname2, $this->part1->getId(), 1982, null, 30],
                            [$this->geoname2, $this->part1->getId(), 1983, null, 30],
                            [$this->geoname2, $this->part1->getId(), 1984, null, 30],
                            [$this->geoname2, $this->part1->getId(), 1985, null, 30],
                            [$this->geoname2, $this->part1->getId(), 1986, null, 30],
                            [$this->geoname2, $this->part1->getId(), 1987, null, 30],
                            [$this->geoname2, $this->part1->getId(), 1988, null, 30],
                            [$this->geoname2, $this->part1->getId(), 1989, null, 30],
                            [$this->geoname2, $this->part1->getId(), 1990, null, 30],
                            [$this->geoname2, $this->part1->getId(), 1991, null, 30],
                            [$this->geoname2, $this->part1->getId(), 1992, null, 30],
                            [$this->geoname2, $this->part1->getId(), 1993, null, 30],
                            [$this->geoname2, $this->part1->getId(), 1994, null, 30],
                            [$this->geoname2, $this->part1->getId(), 1995, null, 30],
                            [$this->geoname2, $this->part1->getId(), 1996, null, 30],
                            [$this->geoname2, $this->part1->getId(), 1997, null, 30],
                            [$this->geoname2, $this->part1->getId(), 1998, null, 30],
                            [$this->geoname2, $this->part1->getId(), 1999, null, 30],
                            [$this->geoname2, $this->part1->getId(), 2000, null, 30],
                            [$this->geoname2, $this->part1->getId(), 2001, null, 40],
                            [$this->geoname2, $this->part1->getId(), 2002, null, 50],
                            [$this->geoname2, $this->part1->getId(), 2003, null, 50],
                            [$this->geoname2, $this->part1->getId(), 2004, null, 50],
                            [$this->geoname2, $this->part1->getId(), 2005, null, 50],
                            [$this->geoname2, $this->part1->getId(), 2006, null, 50],
                            [$this->geoname2, $this->part1->getId(), 2007, null, 50],
                            [$this->geoname2, $this->part1->getId(), 2008, null, 50],
                            [$this->geoname2, $this->part1->getId(), 2009, null, 50],
                            [$this->geoname2, $this->part1->getId(), 2010, null, 50],
                            [$this->geoname2, $this->part1->getId(), 2011, null, 50],
                            [$this->geoname2, $this->part1->getId(), 2012, null, 50],
                            [$this->geoname2, $this->part1->getId(), 2013, null, 50],
                            [$this->geoname2, $this->part1->getId(), 2014, null, 50],
                            [$this->geoname2, $this->part1->getId(), 2015, null, 50],
        ]));

        return $stubPopulationRepository;
    }

    protected function getStubAnswerRepository()
    {
        // Create a stub for the AnswerRepository class with predetermined values, so we don't have to mess with database
        $stubAnswerRepository = $this->getMock('\Application\Repository\AnswerRepository', ['getValue', 'getQuestionNameIfNonNullAnswer'], [], '', false);
        $stubAnswerRepository->expects($this->any())
                ->method('getValue')
                ->will($this->returnCallback(function ($questionnaireId, $filterId, $partId) {
                            $questionnaire = $this->getModel('\Application\Model\Questionnaire', $questionnaireId);
                            foreach ($questionnaire->getAnswers() as $answer) {
                                $answerFilter = $answer->getQuestion()->getFilter();
                                if ($answerFilter->getId() == $filterId && $answer->getPart()->getId() == $partId) {
                                    return $answer->getValuePercent();
                                }
                            }

                            return null;
                        })
        );

        $stubAnswerRepository->expects($this->any())
                ->method('getQuestionNameIfNonNullAnswer')
                ->will($this->returnCallback(function ($questionnaireId, $filterId) {

                            $questionnaire = $this->getModel('\Application\Model\Questionnaire', $questionnaireId);
                            foreach ($questionnaire->getAnswers() as $answer) {
                                if ($answer->getQuestion()->getFilter()->getId() == $filterId && !is_null($answer->getValuePercent())) {
                                    return $answer->getQuestion()->getName();
                                }
                            }

                            return null;
                        }));

        return $stubAnswerRepository;
    }

    protected function getStubQuestionnaireRepository()
    {
        $stubQuestionnaireRepository = $this->getMock('\Application\Repository\QuestionnaireRepository', ['getAllForComputing'], [], '', false);

        $stubQuestionnaireRepository->expects($this->any())
                ->method('getAllForComputing')
                ->will($this->returnCallback(function (array $geonames) {
                            $res = [];
                            foreach ($geonames as $g) {
                                $res = array_merge($res, $g->getQuestionnaires()->toArray());
                            }

                            return $res;
                        })
        );

        return $stubQuestionnaireRepository;
    }

    protected function getStubFilterQuestionnaireUsageRepository()
    {
        // Create a stub for the FilterQuestionnaireUsageRepository class with predetermined values, so we don't have to mess with database
        $stubFilterQuestionnaireUsageRepository = $this->getMock('\Application\Repository\Rule\FilterQuestionnaireUsageRepository', ['getFirst'], [], '', false);
        $stubFilterQuestionnaireUsageRepository->expects($this->any())
                ->method('getFirst')
                ->will($this->returnCallback(function ($questionnaireId, $filterId, $partId, $useSecondStepRules, ArrayCollection $alreadyUsedFormulas) {

                            $filter = $this->getModel('\Application\Model\Filter', $filterId);
                            foreach ($filter->getFilterQuestionnaireUsages() as $filterQuestionnaireUsage) {
                                if (($useSecondStepRules || !$filterQuestionnaireUsage->isSecondStep()) && $filterQuestionnaireUsage->getRule() && $filterQuestionnaireUsage->getQuestionnaire()->getId() == $questionnaireId && $filterQuestionnaireUsage->getPart()->getId() == $partId && !$alreadyUsedFormulas->contains($filterQuestionnaireUsage)) {
                                    return $filterQuestionnaireUsage;
                                }
                            }

                            return null;
                        })
        );

        return $stubFilterQuestionnaireUsageRepository;
    }

    protected function getStubFilterGeonameUsageRepository()
    {
        // Create a stub for the FilterQuestionnaireUsageRepository class with predetermined values, so we don't have to mess with database
        $stubFilterQuestionnaireUsageRepository = $this->getMock('\Application\Repository\Rule\FilterGeonameUsageRepository', ['getFirst'], [], '', false);
        $stubFilterQuestionnaireUsageRepository->expects($this->any())
                ->method('getFirst')
                ->will($this->returnCallback(function ($geonameId, $filterId, $partId, ArrayCollection $alreadyUsedFormulas) {

                            $geoname = $this->getModel('\Application\Model\Geoname', $geonameId);
                            foreach ($geoname->getFilterGeonameUsages() as $filterGeonameUsage) {
                                if ($filterGeonameUsage->getRule() && $filterGeonameUsage->getFilter()->getId() == $filterId && $filterGeonameUsage->getPart()->getId() == $partId && !$alreadyUsedFormulas->contains($filterGeonameUsage->getRule())) {
                                    return $filterGeonameUsage;
                                }
                            }

                            return null;
                        })
        );

        return $stubFilterQuestionnaireUsageRepository;
    }

    protected function getStubFilterRepository()
    {
        $stubFilterRepository = $this->getMock('\Application\Repository\FilterRepository', ['findOneById', 'getSummandIds', 'getChildrenIds'], [], '', false);

        $stubFilterRepository->expects($this->any())->method('getSummandIds')
                ->will($this->returnCallback(function ($filterId) {
                            $filter = $this->getModel('\Application\Model\Filter', $filterId);

                            return $filter->getSummands()->map(function ($f) {
                                        return $f->getId();
                                    })->toArray();
                        }));

        $stubFilterRepository->expects($this->any())->method('getChildrenIds')
                ->will($this->returnCallback(function ($filterId) {
                            $filter = $this->getModel('\Application\Model\Filter', $filterId);

                            return $filter->getChildren()->map(function ($f) {
                                        return $f->getId();
                                    })->toArray();
                        }));

        $stubFilterRepository->expects($this->any())
                ->method('findOneById')
                ->will($this->returnCallback(function ($filterId) {
                            return $this->getModel('\Application\Model\Filter', $filterId);
                        }));

        return $stubFilterRepository;
    }

    /**
     *
     * @return \Application\Service\Calculator\Calculator
     */
    protected function getNewCalculator()
    {
        $calculator = new \Application\Service\Calculator\Calculator();
        $calculator->setPopulationRepository($this->getStubPopulationRepository());
        $calculator->setAnswerRepository($this->getStubAnswerRepository());
        $calculator->setFilterRepository($this->getStubFilterRepository());
        $calculator->setFilterQuestionnaireUsageRepository($this->getStubFilterQuestionnaireUsageRepository());
        $calculator->setQuestionnaireRepository($this->getStubQuestionnaireRepository());
        $calculator->setFilterGeonameUsageRepository($this->getStubFilterGeonameUsageRepository());

        $calculator->setServiceLocator($this->getApplicationServiceLocator());

        return $calculator;
    }
}
