<?php

namespace Application\Repository;

use Application\Model\Questionnaire;
use Doctrine\ORM\Query\Expr\Join;

class QuestionRepository extends AbstractChildRepository
{

    /**
     * Returns all items with read access
     * @return array
     */
    public function getAllWithPermission($action = 'read', $search = null, $parentName = null, \Application\Model\AbstractModel $parent = null)
    {
        $qb = $this->createQueryBuilder('question')
            ->join('question.survey', 'survey', Join::WITH)
            ->where('question.' . $parentName . ' = :parent')
            ->setParameter('parent', $parent)->orderBy('question.sorting')
            ->groupBy('question.id');

        $this->addSearch($qb, $search);
        $this->addPermission($qb, 'survey', \Application\Model\Permission::getPermissionName($this, $action));

        return $qb->getQuery()->getResult();
    }

    /**
     * Changer question type directly in database
     * @todo : find a way to change type with doctrine (transform a doctrine object to another)
     * @param integer $id
     * @param \Application\Model\QuestionType $questionType
     * @return self
     */
    public function changeType($id, \Application\Model\QuestionType $questionType)
    {
        $class = \Application\Model\QuestionType::getClass($questionType);
        $dtype = strtolower(str_replace("Application\\Model\\Question\\", '', $class));

        $sql = "UPDATE question SET dtype='" . $dtype . "' WHERE id=" . $id;
        $this->getEntityManager()->getConnection()->executeUpdate($sql);

        return $this;
    }

    /**
     * Returns all items with read access and answers and choices related
     * @return array
     */
    public function getAllWithPermissionWithAnswers($action = 'read', \Application\Model\Survey $survey = null, array $questionnairesIds = null)
    {
        /** @var \Doctrine\ORM\QueryBuilder $qb */

        // Answerable questions with parts
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('question, chapter, parts')
            ->from(\Application\Model\Question\AbstractAnswerableQuestion::class, 'question')
            ->join('question.survey', 'survey', Join::WITH)
            ->join('question.parts', 'parts')
            ->leftJoin('question.chapter', 'chapter')
            ->where('question.survey = :survey')
            ->setParameter('survey', $survey)
            ->orderBy('question.sorting', 'ASC')
            ->addOrderBy('parts.id', 'ASC');
        $this->addPermission($qb, 'survey', \Application\Model\Permission::getPermissionName($this, $action));
        $questions = $qb->getQuery()->getArrayResult();

        // Chapters (question without parts)
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('question, chapter')
            ->from(\Application\Model\Question\Chapter::class, 'question')
            ->join('question.survey', 'survey', Join::WITH)
            ->leftJoin('question.chapter', 'chapter')
            ->where('question.survey = :survey')
            ->setParameter('survey', $survey)
            ->orderBy('question.sorting');
        $this->addPermission($qb, 'survey', \Application\Model\Permission::getPermissionName($this, $action));
        $chapters = $qb->getQuery()->getArrayResult();

        // merge questions and chapters
        $questions = array_merge($chapters, $questions);

        // create question index
        $questionsIndexed = [];
        foreach ($questions as $question) {
            $questionsIndexed[$question['id']] = $question;
            $questionsIndexed[$question['id']]['answers'] = ['1' => [], '2' => [], '3' => []];
        }

        // ChoiceQuestions with parts, isMultiple and choices
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('question, chapter, parts, choices')
            ->from(\Application\Model\Question\ChoiceQuestion::class, 'question')
            ->join('question.survey', 'survey', Join::WITH)
            ->join('question.parts', 'parts')
            ->leftJoin('question.choices', 'choices')
            ->leftJoin('question.chapter', 'chapter')
            ->where('question.survey = :survey')
            ->setParameter('survey', $survey)
            ->orderBy('question.sorting')
            ->addOrderBy('choices.sorting');

        // add choices to questions
        $this->addPermission($qb, 'survey', \Application\Model\Permission::getPermissionName($this, $action));
        $choiceQuestions = $qb->getQuery()->getArrayResult();
        foreach ($choiceQuestions as $question) {
            $questionsIndexed[$question['id']]['choices'] = $question['choices'];
            $questionsIndexed[$question['id']]['isMultiple'] = $question['isMultiple'];
        }

        // answers
        $answers = $this->getEntityManager()
            ->getRepository('\Application\Model\Answer')
            ->getAllAnswersInQuestionnaires($survey, $questionnairesIds);

        // add answers to questions
        foreach ($answers as $answer) {
            $answerQuestionId = $answer['question']['id'];
            $answerPartId = $answer['part']['id'];
            $answerQuestionnaireId = $answer['questionnaire']['id'];
            if (!isset($questionsIndexed[$answerQuestionId]['answers'][$answerPartId][$answerQuestionnaireId])) {
                $questionsIndexed[$answerQuestionId]['answers'][$answerPartId][$answerQuestionnaireId] = [$answer];
            } else {
                array_push($questionsIndexed[$answerQuestionId]['answers'][$answerPartId][$answerQuestionnaireId], $answer);
            }
        }

        return $questionsIndexed;
    }

    /**
     * Get one question, without taking into consideration its type
     * @param integer $id
     */
    public function getOneById($id)
    {
        $query = $this->getEntityManager()
            ->createQuery("SELECT q FROM Application\Model\Question\AbstractQuestion q WHERE q.id = :id");

        $params = [
            'id' => $id,
        ];

        $query->setParameters($params);
        $question = $query->getOneOrNullResult();

        return $question;
    }

    /**
     * Returns alternateNames for the all filters for the given questionnaire
     * @param array $filterIds
     * @param \Application\Model\Questionnaire $questionnaire
     * @return array
     */
    public function getByFiltersAndQuestionnaire(array $filterIds, Questionnaire $questionnaire)
    {
        $query = $this->getEntityManager()
            ->createQuery("SELECT q.alternateNames, filter.id AS filterId FROM Application\Model\Question\AbstractAnswerableQuestion q
                JOIN q.survey survey
                JOIN q.filter filter
                WHERE q.filter IN (:filters) AND :questionnaire MEMBER OF survey.questionnaires");

        $params = [
            'filters' => $filterIds,
            'questionnaire' => $questionnaire,
        ];

        $query->setParameters($params);
        $alternateNames = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        return $alternateNames;
    }

    /**
     * Returns alternateNames for the all filters for the given questionnaire
     * @param array $filterIds
     * @param \Application\Model\Questionnaire $questionnaire
     * @return array
     */
    public function getAbsoluteByFiltersAndQuestionnaire(array $filterIds, Questionnaire $questionnaire)
    {
        $query = $this->getEntityManager()
            ->createQuery("SELECT q.isAbsolute, filter.id AS filterId FROM Application\Model\Question\NumericQuestion q
                JOIN q.survey survey
                JOIN q.filter filter
                WHERE q.filter IN (:filters) AND :questionnaire MEMBER OF survey.questionnaires");

        $params = [
            'filters' => $filterIds,
            'questionnaire' => $questionnaire,
        ];

        $query->setParameters($params);
        $questions = $query->getResult(\Doctrine\ORM\AbstractQuery::HYDRATE_ARRAY);

        return $questions;
    }
}
