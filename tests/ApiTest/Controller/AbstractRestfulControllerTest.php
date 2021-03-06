<?php

namespace ApiTest\Controller;

use Application\Model\Answer;
use Application\Model\Filter;
use Application\Model\FilterSet;
use Application\Model\Geoname;
use Application\Model\Part;
use Application\Model\Population;
use Application\Model\Question\NumericQuestion;
use Application\Model\Questionnaire;
use Application\Model\Role;
use Application\Model\Rule\FilterGeonameUsage;
use Application\Model\Rule\FilterQuestionnaireUsage;
use Application\Model\Rule\QuestionnaireUsage;
use Application\Model\Rule\Rule;
use Application\Model\Survey;
use Application\Model\User;
use Application\Model\UserQuestionnaire;
use Application\Model\UserSurvey;
use Zend\Http\Request;

abstract class AbstractRestfulControllerTest extends \ApplicationTest\Controller\AbstractController
{

    /**
     * @var Survey
     */
    protected $survey;

    /**
     * @var Questionnaire
     */
    protected $questionnaire;

    /**
     * @var NumericQuestion
     */
    protected $question;

    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var Filter
     */
    protected $filterParent;

    /**
     * @var FilterSet
     */
    protected $filterSet;

    /**
     * @var Part
     */
    protected $part;

    /**
     * @var Part
     */
    protected $part2;

    /**
     * @var Part
     */
    protected $part3;

    /**
     * @var Answer
     */
    protected $answer;

    /**
     * @var UserSurvey
     */
    protected $userSurvey;

    /**
     * @var UserQuestionnaire
     */
    protected $userQuestionnaire1;

    /**
     * @var UserFilterSet
     */
    protected $userFilterSet;

    /**
     * @var Geoname
     */
    protected $geoname;

    /**
     * @var Rule
     */
    protected $rule;

    /**
     * @var QuestionnaireUsage
     */
    protected $questionnaireUsage;

    /**
     * @var FilterQuestionnaireUsage
     */
    protected $filterQuestionnaireUsage;

    /**
     * @var FilterGeonameUsage
     */
    protected $filterGeonameUsage;

    /**
     * @var Population
     */
    protected $population;

    /**
     * @var Role
     */
    protected $filterEditor;

    /**
     * @var Role
     */
    protected $surveyEditor;

    /**
     * @var Role
     */
    protected $questionnaireReporter;

    public function setUp()
    {
        parent::setUp();
        $this->populateStorage();
    }

    /**
     * @return void
     */
    protected function populateStorage()
    {
        $this->survey = new Survey('test survey');
        $this->survey->setIsActive(true);
        $this->survey->setCode('code test survey');
        $this->survey->setYear(2010);

        $this->geoname = new Geoname('test geoname');

        $this->filter = new Filter('foo');
        $this->filterParent = new Filter('bar');
        $this->filterParent->addChild($this->filter);

        $this->filterSet = new FilterSet('foo filterSet');
        $this->filterSet->addFilter($this->filter);
        $this->filterSet2 = new FilterSet('bar filterSet'); // no permissions given to this filterset

        $this->questionnaire = new Questionnaire();
        $this->questionnaire->setSurvey($this->survey);
        $this->questionnaire->setDateObservationStart(new \DateTime('2010-01-01T00:00:00+0100'));
        $this->questionnaire->setDateObservationEnd(new \DateTime('2011-01-01T00:00:00+0100'));
        $this->questionnaire->setGeoname($this->geoname);

        $this->question = new NumericQuestion('test question');
        $this->question->setSurvey($this->survey)->setSorting(1)->setFilter($this->filter);

        $this->part = new Part('test part 1');
        $this->part2 = new Part('test part 2');
        $this->part3 = new Part('test part 3');

        $this->answer = new Answer();
        $this->answer->setQuestion($this->question)->setQuestionnaire($this->questionnaire)->setPart($this->part)->setValuePercent(0.55);

        // Get existing roles
        $roleRepository = $this->getEntityManager()->getRepository(\Application\Model\Role::class);
        $this->surveyEditor = $roleRepository->findOneByName('Survey editor');
        $this->questionnaireReporter = $roleRepository->findOneByName('Questionnaire reporter');
        $this->filterEditor = $roleRepository->findOneByName('Filter editor');

        // Define user as survey editor
        $this->userSurvey = new UserSurvey();
        $this->userSurvey->setUser($this->user)->setSurvey($this->survey)->setRole($this->surveyEditor);

        // Define user as questionnaire reporter (the guy who answer the questionnaire)
        $this->userQuestionnaire1 = new UserQuestionnaire();
        $this->userQuestionnaire1->setUser($this->user)->setQuestionnaire($this->questionnaire)->setRole($this->questionnaireReporter);

        // Define user as "Filter editor" for FilterSet
        $this->userFilterSet = new \Application\Model\UserFilterSet();
        $this->userFilterSet->setUser($this->user)->setFilterSet($this->filterSet)->setRole($this->filterEditor);

        $this->rule = new Rule('test rule');
        $this->rule->setFormula('=2 * 3');

        $this->questionnaireUsage = new QuestionnaireUsage();
        $this->questionnaireUsage->setJustification('tests')->setRule($this->rule)->setPart($this->part)->setQuestionnaire($this->questionnaire);

        $this->filterQuestionnaireUsage = new FilterQuestionnaireUsage();
        $this->filterQuestionnaireUsage->setJustification('tests')->setRule($this->rule)->setPart($this->part)->setQuestionnaire($this->questionnaire)->setFilter($this->filter);

        $this->filterGeonameUsage = new FilterGeonameUsage();
        $this->filterGeonameUsage->setJustification('tests')->setRule($this->rule)->setPart($this->part)->setGeoname($this->geoname)->setFilter($this->filter);

        $this->population = new Population();
        $this->population->setGeoname($this->geoname)->setPart($this->part)->setYear(2000)->setPopulation(55555)->setQuestionnaire($this->questionnaire);

        $this->getEntityManager()->persist($this->filterSet);
        $this->getEntityManager()->persist($this->filterSet2);
        $this->getEntityManager()->persist($this->userFilterSet);
        $this->getEntityManager()->persist($this->user);
        $this->getEntityManager()->persist($this->userSurvey);
        $this->getEntityManager()->persist($this->userQuestionnaire1);
        $this->getEntityManager()->persist($this->part);
        $this->getEntityManager()->persist($this->part2);
        $this->getEntityManager()->persist($this->part3);
        $this->getEntityManager()->persist($this->filter);
        $this->getEntityManager()->persist($this->filterParent);
        $this->getEntityManager()->persist($this->geoname);
        $this->getEntityManager()->persist($this->survey);
        $this->getEntityManager()->persist($this->questionnaire);
        $this->getEntityManager()->persist($this->question);
        $this->getEntityManager()->persist($this->answer);
        $this->getEntityManager()->persist($this->rule);
        $this->getEntityManager()->persist($this->questionnaireUsage);
        $this->getEntityManager()->persist($this->filterQuestionnaireUsage);
        $this->getEntityManager()->persist($this->filterGeonameUsage);
        $this->getEntityManager()->persist($this->population);

        // Prevent new objects to be created by the current user, otherwise it would bypass all ACL checks if we are the creator
        $this->identityProvider->setIdentity(null);
        $this->getEntityManager()->flush();

        // After flushed in DB, we clear EM identiy cache, to be sure that we actually reload object from database
        $this->getEntityManager()->clear();
        $reloadedUser = $this->getEntityManager()->merge($this->user);
        $this->user = $reloadedUser;
        $this->identityProvider->setIdentity($reloadedUser);
    }

    /**
     * Create a second questionnaire on which we don't have access
     * @return \Application\Model\Questionnaire
     */
    protected function createAnotherQuestionnaire()
    {
        // Reload things from DB
        $this->survey = $this->getEntityManager()->merge($this->survey);
        $this->geoname = $this->getEntityManager()->merge($this->geoname);
        $this->part = $this->getEntityManager()->merge($this->part);
        $this->rule = $this->getEntityManager()->merge($this->rule);

        $questionnaire = new Questionnaire();
        $questionnaire->setSurvey($this->survey);
        $questionnaire->setDateObservationStart(new \DateTime('2010-01-01T00:00:00+0100'));
        $questionnaire->setDateObservationEnd(new \DateTime('2011-01-01T00:00:00+0100'));
        $questionnaire->setGeoname($this->geoname);
        $this->getEntityManager()->persist($questionnaire);

        $this->identityProvider->setIdentity(null);
        $this->getEntityManager()->flush();
        $this->identityProvider->setIdentity($this->user);

        return $questionnaire;
    }

    /**
     * Create a second user and login with him
     * @return \Application\Model\User
     */
    protected function createAnotherUser()
    {
        $anotherUser = new User('another test user');
        $anotherUser->setPassword('123');
        $this->getEntityManager()->persist($anotherUser);
        $this->getEntityManager()->flush();
        $this->identityProvider->setIdentity($anotherUser);

        return $anotherUser;
    }

    public function testCommonRestActions()
    {
        $this->subtestGetOne();
        $this->subtestGetOneWithFields();
        $this->subtestAnonymousCannotDelete();
        $this->subtestMemberCanDelete();
        $this->subtestMemberCannotDeleteNonExisting();
    }

    protected function subtestGetOne()
    {
        $this->dispatch($this->getRoute('get'), Request::METHOD_GET);
        $this->assertResponseStatusCode(200);

        $actual = $this->getJsonResponse();
        $allowedFields = $this->getAllowedFields();
        foreach ($actual as $key => $value) {
            $this->assertTrue(in_array($key, $allowedFields), "API should not return non-allowed field: '" . $key . "'");
        }

        $this->assertSame($this->getTestedObject()->getId(), $actual['id'], 'should be the same ID that what we asked');
        $this->assertArrayNotHasKey('nonExistingField', $actual);
    }

    protected function subtestGetOneWithFields()
    {
        // Then test we can get specific fields
        $this->dispatch($this->getRoute('get') . '?fields=metadata,nonExistingField', Request::METHOD_GET);
        $this->assertResponseStatusCode(200);

        $actual = $this->getJsonResponse();
        $this->assertSame($this->getTestedObject()->getId(), $actual['id'], 'should be the same ID that what we asked');
        $this->assertArrayNotHasKey('nonExistingField', $actual, 'unknown fields should be silently ignored');

        $metadata = [
            'dateCreated',
            'dateModified',
            'creator',
            'modifier',
        ];

        foreach ($metadata as $key => $val) {
            $metadata = is_string($key) ? $key : $val;
            $this->assertArrayHasKey($metadata, $actual, 'metadata shortcut should be expanded to common well-known fields');
        }
    }

    protected function subtestAnonymousCannotDelete()
    {
        // Anonymous should not be able to delete anything
        $this->identityProvider->setIdentity(null);
        $route = $this->getRoute('delete');
        $this->dispatch($route, Request::METHOD_DELETE);
        $this->assertResponseStatusCode(403);
    }

    protected function subtestMemberCanDelete()
    {
        // Logged user should be able to delete
        $this->identityProvider->setIdentity($this->user);
        $this->dispatch($this->getRoute('delete'), Request::METHOD_DELETE);
        $this->assertResponseStatusCode(204);
        $this->assertEquals($this->getJsonResponse()['message'], 'Deleted successfully');
    }

    protected function subtestMemberCannotDeleteNonExisting()
    {
        $this->dispatch($this->getRoute('delete'), Request::METHOD_DELETE);
        $this->assertResponseStatusCode(404);
    }

    protected function getRoute($method)
    {
        $parts = explode('\\', (get_class($this->getTestedObject())));
        $classname = lcfirst(end($parts));
        if ($classname == 'numericQuestion') {
            $classname = 'question';
        }

        $id = $this->getTestedObject()->getId();

        switch ($method) {
            case 'getList':
            case 'post':
                $route = "/api/$classname";
                break;
            case 'get':
            case 'put':
            case 'delete':
                $route = "/api/$classname/$id";
                break;
            default:
                throw new \Exception("Unsupported route '$method' for $classname");
        }

        return $route;
    }

    abstract protected function getAllowedFields();

    abstract protected function getTestedObject();
}
