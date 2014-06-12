<?php

namespace ApiTest\Controller;

/**
 * @group Rest
 */
class UserQuestionnaireControllerTest extends AbstractChildRestfulControllerTest
{

    protected function getAllowedFields()
    {
        return array('id', 'user', 'role', 'questionnaire');
    }

    protected function getTestedObject()
    {
        return $this->userQuestionnaire1;
    }

    protected function getPossibleParents()
    {
        return [
            $this->user,
            $this->questionnaire,
        ];
    }

}
