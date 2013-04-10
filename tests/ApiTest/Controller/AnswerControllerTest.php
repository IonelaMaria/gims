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

class AnswerControllerTest extends AbstractController
{

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

        $this->survey = new Survey();
        $this->survey->setActive(true);
        $this->survey->setName('test survey');
        $this->survey->setCode('code test survey');
        $this->survey->setYear(2010);

        $geoName = new Geoname();

        $this->category = new Category();
        $this->category->setName('foo')
            ->setOfficial(true);

        $this->questionnaire = new Questionnaire();
        $this->questionnaire->setSurvey($this->survey);
        $this->questionnaire->setDateObservationStart(new \DateTime('2010-01-01T00:00:00+0100'));
        $this->questionnaire->setDateObservationEnd(new \DateTime('2011-01-01T00:00:00+0100'));
        $this->questionnaire->setGeoname($geoName);

        $this->question = new Question();
        $this->question->setSurvey($this->survey)
            ->setSorting(1)
            ->setType(1)
            ->setCategory($this->category)
            ->setName('foo');

        $this->part = new Part();
        $this->part->setName('foo');

        $this->answer = new Answer();
        $this->answer
            ->setQuestion($this->question)
            ->setPart($this->part)
            ->setQuestionnaire($this->questionnaire);


        // create a fake user
        $this->user = new User();
        $this->user->setPassword('foo')->setName('test user');

        // Get rbac service
        $this->rbac = $this->getApplication()->getServiceManager()->get('ZfcRbac\Service\Rbac');

        // Get existing permission
        $repository = $this->getEntityManager()->getRepository('Application\Model\Permission');

        /** @var $role \Application\Model\Permission */
        $this->permission = $repository->findOneByName(\Application\Model\Permission::CAN_MANAGE_ANSWER);

        $this->role = new Role('foo');
        $this->role->addPermission($this->permission);

        // create a fake user-questionnaire
        $this->userQuestionnaire = new UserQuestionnaire();
        $this->userQuestionnaire->setUser($this->user)->setQuestionnaire($this->questionnaire)->setRole($this->role);

        $this->getEntityManager()->persist($this->user);
        $this->getEntityManager()->persist($this->role);
        $this->getEntityManager()->persist($this->userQuestionnaire);
        $this->getEntityManager()->persist($this->part);
        $this->getEntityManager()->persist($this->category);
        $this->getEntityManager()->persist($geoName);
        $this->getEntityManager()->persist($this->survey);
        $this->getEntityManager()->persist($this->questionnaire);
        $this->getEntityManager()->persist($this->question);
        $this->getEntityManager()->persist($this->answer);
        $this->getEntityManager()->flush();
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
            case 'get':
                $route = sprintf(
                    '/api/answer/%s',
                    $this->answer->getId()
                );
                break;
            case 'post':
                $route = '/api/answer';
                break;
            case 'put':
                $route = sprintf(
                    '/api/answer/%s?id=%s',
                    $this->answer->getId(),
                    $this->answer->getId()
                );
                break;
            default:
                $route = '';

        }
        return $route;
    }

    private function getJsonResponse()
    {
        $content = $this->getResponse()->getContent();
        $json = Json::decode($content, Json::TYPE_ARRAY);

        $this->assertTrue(is_array($json));
        return $json;
    }

    /**
     * @test
     * @group AnswerApi
     */
    public function dispatchRouteForAnswerReturnsStatus200()
    {
        $this->dispatch($this->getRoute('get'), Request::METHOD_GET);
        $this->assertResponseStatusCode(200);
    }

    /**
     * @test
     * @group AnswerApi
     */
    public function ensureOnlyAllowedFieldAreDisplayedInResponseForAnswer()
    {
        $this->dispatch($this->getRoute('get'), Request::METHOD_GET);
        $allowedFields = array('id', 'valuePercent', 'valueAbsolute', 'part', 'question');
        foreach ($this->getJsonResponse() as $key => $value) {
            $this->assertTrue(in_array($key, $allowedFields));
        }
    }

    /**
     * @test
     * @group AnswerApi
     */
    public function getFakeAnswerAndCheckWhetherIdAreCorresponding()
    {
        $this->dispatch($this->getRoute('get'), Request::METHOD_GET);
        $actual = $this->getJsonResponse();
        $this->assertSame($this->answer->getId(), $actual['id']);
    }

    /**
     * @test
     * @group AnswerApi
     */
    public function updateAnswerAndCheckWhetherValuePercentIsDifferentFromOriginalValue()
    {
        $this->rbac->setIdentity($this->user);

        $expected = $this->answer->getValuePercent();
        $data = array(
            'valuePercent' => 0.2,
        );

        $this->dispatch($this->getRoute('put'), Request::METHOD_PUT, $data);
        $actual = $this->getJsonResponse();
        $this->assertNotEquals($expected, $actual['valuePercent']);
    }

    /**
     * @test
     * @group AnswerApi
     */
    public function canUpdateValuePercentOfAnswer()
    {
        $this->rbac->setIdentity($this->user);

        $expected = $this->answer->getValuePercent() + 0.2;
        $data = array(
            'valuePercent' => $expected,
        );

        $this->dispatch($this->getRoute('put'), Request::METHOD_PUT, $data);
        $actual = $this->getJsonResponse();
        $this->assertEquals($expected, $actual['valuePercent']);
    }

    /**
     * @test
     * @group AnswerApi
     */
    public function updateAnAnswerWillReturn201AsCode()
    {
        $this->rbac->setIdentity($this->user);

        $expected = $this->answer->getValuePercent() + 0.2;
        $data = array(
            'valuePercent' => $expected,
        );

        $this->dispatch($this->getRoute('put'), Request::METHOD_PUT, $data);
        $this->assertResponseStatusCode(201);
    }

    /**
     * @test
     * @group AnswerApi
     */
    public function postANewAnswerWithNestedObjectWillCreateIt()
    {
        $this->rbac->setIdentity($this->user);
        // Question
        $data = array(
            'valuePercent'  => 0.6,
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
        $actual = $this->getJsonResponse();
        $this->assertEquals($data['valuePercent'], $actual['valuePercent']);
    }

    /**
     * @test
     * @group AnswerApi
     */
    public function postANewAnswerWithFlatObjectWillCreateIt()
    {
        $this->rbac->setIdentity($this->user);

        // Question
        $data = array(
            'valuePercent'  => 0.6,
            'question'      => $this->question->getId(),
            'questionnaire' => $this->questionnaire->getId(),
            'part'          => $this->part->getId(),
        );

        $this->dispatch($this->getRoute('post'), Request::METHOD_POST, $data);
        $actual = $this->getJsonResponse();
        $this->assertEquals($data['valuePercent'], $actual['valuePercent']);
    }

    /**
     * @test
     * @group AnswerApi
     */
    public function postANewAnswerReturnsStatusCode401ForUserWithRoleAnonymous()
    {
        // Question
        $data = array(
            'valuePercent'  => 0.6,
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
     * @group AnswerApi
     */
    public function postANewAnswerReturnsStatusCode201ForUserWithRoleReporter()
    {
        $this->rbac->setIdentity($this->user);
        // Question
        $data = array(
            'valuePercent'  => 0.6,
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
        $this->assertResponseStatusCode(201);
    }

    /**
     * @test
     * @group AnswerApi
     */
    public function updateAnAnswerAsAnonymousReturnsStatusCode401()
    {
        $expected = $this->answer->getValuePercent() + 0.2;
        $data = array(
            'valuePercent' => $expected,
        );

        $this->dispatch($this->getRoute('put'), Request::METHOD_PUT, $data);
        // @todo comment me out once permission will be enabled (=> GUI handling)
        #$this->assertResponseStatusCode(401);
    }

    /**
     * @test
     * @group AnswerApi
     */
    public function updateAnAnswerWithRoleReporterReturnsStatusCode201()
    {
        $this->rbac->setIdentity($this->user);
        $expected = $this->answer->getValuePercent() + 0.2;
        $data = array(
            'valuePercent' => $expected,
        );

        $this->dispatch($this->getRoute('put'), Request::METHOD_PUT, $data);
        $this->assertResponseStatusCode(201);
    }

}
