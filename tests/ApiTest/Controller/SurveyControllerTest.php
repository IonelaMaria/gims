<?php

namespace ApiTest\Controller;

use Application\Model\Answer;
use Application\Model\Category;
use Application\Model\Geoname;
use Application\Model\Part;
use Application\Model\Permission;
use Application\Model\Question;
use Application\Model\Questionnaire;
use Application\Model\Role;
use Application\Model\Survey;
use Application\Model\User;
use Application\Model\UserQuestionnaire;
use ApplicationTest\Controller\AbstractController;
use Zend\Http\Request;
use Zend\Json\Json;

class SurveyControllerTest extends AbstractController
{
    use ControllerTrait;

    /**
     * @var Survey
     */
    private $survey;

    /**
     * @var Questionnaire
     */
    private $questionnaire;

    /**
     * @var Question
     */
    private $question;

    /**
     * @var Category
     */
    private $category;

    /**
     * @var Part
     */
    private $part;

    /**
     * @var Answer
     */
    private $answer;

    /**
     * @var User
     */
    private $user;

    /**
     * @var \ZfcRbac\Service\Rbac
     */
    private $rbac;

    /**
     * @var Permission
     */
    private $permission;

    /**
     * @var UserQuestionnaire
     */
    private $userQuestionnaire;

    /**
     * @var Role
     */
    private $role;

    public function setUp()
    {
        parent::setUp();

        $this->populateStorage();
    }

    /**
     * Get suitable route for GET method.
     *
     * @param string $method
     *
     * @return string
     */
    private function getRoute($method)
    {
        switch ($method) {
            case 'delete':
            case 'get':
                $route = sprintf(
                    '/api/survey/%s',
                    $this->survey->getId()
                );
                break;
            case 'post':
                $route = '/api/survey';
                break;
            case 'put':
                $route = sprintf(
                    '/api/survey/%s?id=%s',
                    $this->survey->getId(),
                    $this->survey->getId()
                );
                break;
            default:
                $route = '';

        }
        return $route;
    }

    /**
     * @test
     * @group SurveyApi
     */
    public function dispatchRouteForSurveyReturnsStatus200()
    {
        $this->dispatch($this->getRoute('get'), Request::METHOD_GET);
        $this->assertResponseStatusCode(200);
    }

    /**
     * @test
     * @group SurveyApi
     */
    public function ensureOnlyAllowedFieldAreDisplayedInResponseForSurvey()
    {
        $this->dispatch($this->getRoute('get'), Request::METHOD_GET);
        $allowedFields = array('id', 'name', 'code', 'active', 'year');
        foreach ($this->getJsonResponse() as $key => $value) {
            $this->assertTrue(in_array($key, $allowedFields));
        }
    }

    /**
     * @test
     * @group SurveyApi
     */
    public function getFakeSurveyAndCheckWhetherIdsAreCorresponding()
    {
        $this->dispatch($this->getRoute('get'), Request::METHOD_GET);
        $actual = $this->getJsonResponse();
        $this->assertSame($this->survey->getId(), $actual['id']);
    }

    /**
     * @test
     * @group SurveyApi
     */
    public function updateNameOfSurveyAndCheckWhetherOriginalNameIsDifferentFromUpdatedValue()
    {
        $this->rbac->setIdentity($this->user);

        $expected = $this->survey->getName();
        $data = array(
            'name' => $this->survey->getName() . 'foo',
        );

        $this->dispatch($this->getRoute('put'), Request::METHOD_PUT, $data);
        $actual = $this->getJsonResponse();
        $this->assertNotEquals($expected, $actual['name']);
    }


    /**
     * @test
     * @group SurveyApi
     */
    public function updateAnSurveyWillReturn201AsCode()
    {
        $this->rbac->setIdentity($this->user);

        $expected = $this->survey->getName() . 'foo';
        $data = array(
            'name' => $expected,
        );

        $this->dispatch($this->getRoute('put'), Request::METHOD_PUT, $data);
        $this->assertResponseStatusCode(201);
    }

    /**
     * @test
     * @group SurveyApi
     */
    public function postANewSurveyAndCheckResponseReturnsIt()
    {
        $this->rbac->setIdentity($this->user);

        // Survey
        $data = array(
            'name' => 'new-survey',
            'code' => 100,
            'year' => 2013,
        );

        $this->dispatch($this->getRoute('post'), Request::METHOD_POST, $data);
        $actual = $this->getJsonResponse();
        $this->assertEquals($data['name'], $actual['name']);
    }


    /**
     * @test
     * @group SurveyApi
     */
    public function postANewSurveyReturnsStatusCode401ForUserWithRoleAnonymous()
    {
        // Question
        $data = array(
            'name'          => 0.6,
            'question'      => array(
                'id' => $this->question->getId()
            ),
            'questionnaire' => array(
                'id' => $this->questionnaire->getId()
            ),
            'part'          => array(
                'id' => $this->part->getId()
            ),
        );


        $this->dispatch($this->getRoute('post'), Request::METHOD_POST, $data);
        // @todo comment me out once permission will be enabled (=> GUI handling)
        #$this->assertResponseStatusCode(401);
    }

    /**
     * @test
     * @group SurveyApi
     */
    public function postANewSurveyReturnsStatusCode201ForUserWithRoleReporter()
    {
        $this->rbac->setIdentity($this->user);
        // Question
        $data = array(
            'name' => 'new-survey',
            'code' => 100,
            'year' => 2013,
        );

        $this->dispatch($this->getRoute('post'), Request::METHOD_POST, $data);
        $this->assertResponseStatusCode(201);
    }

    /**
     * @test
     * @group SurveyApi
     */
    public function updateAnSurveyAsAnonymousReturnsStatusCode401()
    {
        $expected = $this->survey->getName() . 'foo';
        $data = array(
            'name' => $expected,
        );

        $this->dispatch($this->getRoute('put'), Request::METHOD_PUT, $data);
        // @todo comment me out once permission will be enabled (=> GUI handling)
        #$this->assertResponseStatusCode(401);
    }

    /**
     * @test
     * @group SurveyApi
     */
    public function updateAnSurveyWithRoleReporterReturnsStatusCode201()
    {
        $this->rbac->setIdentity($this->user);
        $expected = $this->survey->getName() . 'foo';
        $data = array(
            'name' => $expected,
        );

        $this->dispatch($this->getRoute('put'), Request::METHOD_PUT, $data);
        $this->assertResponseStatusCode(201);
    }

    /**
     * @test
     * @group SurveyApi
     */
    public function deleteSurveyMustReturnStatusCode200()
    {
        $this->rbac->setIdentity($this->user);
        $this->dispatch($this->getRoute('delete'), Request::METHOD_DELETE);
        $this->assertResponseStatusCode(200);
    }

    /**
     * @test
     * @group SurveyApi
     */
    public function deleteSurveyMustContainsMessageDeletedSuccessfully()
    {
        $this->rbac->setIdentity($this->user);
        $this->dispatch($this->getRoute('delete'), Request::METHOD_DELETE);
        $this->assertEquals($this->getJsonResponse()['message'], 'deleted successfully');
    }

    /**
     * @test
     * @group SurveyApi
     */
    public function deleteASurveyWhichDoesNotExistReturnsStatusCode404()
    {
        $this->rbac->setIdentity($this->user);
        $this->dispatch('/api/survey/' . ($this->survey->getId() + 1), Request::METHOD_DELETE);
        $this->assertResponseStatusCode(404);
    }
}