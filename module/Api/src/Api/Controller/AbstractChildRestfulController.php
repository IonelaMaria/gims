<?php

namespace Api\Controller;

use Zend\View\Model\JsonModel;

/**
 * This is a controller for objects which are dependent on a parent object.
 * Child objects cannot be listed without specifying a parent.
 *
 * This controller is used with the route /api/subobject
 *
 * Eg: Questions are dependent on the parent Survey, URL would be: /api/survey/1/question
 */
abstract class AbstractChildRestfulController extends AbstractRestfulController
{

    /**
     * @var \Application\Model\AbstractModel
     */
    private $parent;

    /**
     * Returns the parent object
     * @return \Application\Model\AbstractModel
     */
    protected function getParent()
    {
        $id = $this->params('idParent');
        if (!$this->parent && $id) {
            $object = ucfirst($this->params('parent'));
            if ($object == 'Chapter') {
                $object = 'Question\\' . $object;
            }
            $this->parent = $this->getEntityManager()->getRepository('Application\Model\\' . $object)->find($id);
        }

        return $this->parent;
    }

    public function getList()
    {
        $parent = $this->getParent();

        if (!$parent) {
            $this->getResponse()->setStatusCode(400);

            return new JsonModel(array('message' => 'Cannot list all items without a valid parent. Use URL similar to: /api/parent/1/child'));
        }

        $userSurveys = $this->getRepository()->findBy(array($this->params('parent') => $parent));

        return new JsonModel($this->hydrator->extractArray($userSurveys, $this->getJsonConfig()));
    }

}