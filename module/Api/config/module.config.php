<?php

return array(
    'controllers' => array(
        'invokables' => array(
            'Api\Controller\Questionnaire' => 'Api\Controller\QuestionnaireController',
            'Api\Controller\Question' => 'Api\Controller\QuestionController',
            'Api\Controller\Answer' => 'Api\Controller\AnswerController',
            'Api\Controller\Category' => 'Api\Controller\CategoryController',
            'Api\Controller\Survey' => 'Api\Controller\SurveyController',
            'Api\Controller\Filter' => 'Api\Controller\FilterController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'api' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/api',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Api\Controller',
                    ),
                ),
                'child_routes' => array(
                    // The following is a route to simplify getting started creating
                    // new controllers and actions without needing to create a new
                    // module. Simply drop new controllers in, and you can access them
                    // using the path /api/:controller/:id
                    'default' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/[:controller[/:id]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id' => '[0-9]+',
                            ),
                            'defaults' => array(
                                '__NAMESPACE__' => 'Api\Controller',
                            ),
                        ),
                    ),
                    'question' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/questionnaire/:idQuestionnaire/[:controller[/:id]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'idQuestionnaire' => '[0-9]+',
                                'id' => '[0-9]+',
                            ),
                            'defaults' => array(),
                        ),
                    ),
                    // This route allow to execute something on a questionnaire (eg:computing results)
                    'questionnaire_actions' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/questionnaire/:idQuestionnaire/[:action]',
                            'constraints' => array(
                                'action' => '(compute|a)', // Define here allowed actions: (action1|action2|action3)
                                'idQuestionnaire' => '[0-9]+',
                            ),
                            'defaults' => array(
                                '__NAMESPACE__' => 'Api\Controller',
                                'controller' => 'questionnaire',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'strategies' => array(
            'ViewJsonStrategy',
        ),
    ),
);
