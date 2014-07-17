<?php

namespace ApiTest\Controller;

use Zend\Http\Request;

/**
 * @group ApiComputing
 * @group Chart
 */
class ChartControllerTest extends \ApplicationTest\Controller\AbstractController
{

    use Traits\SupressDataSetOutput;

    public function setUp()
    {
        parent::setUp();
        $this->getEntityManager()->flush();
    }

    public function testGetValidChartStructure()
    {
        $this->dispatch('/api/chart?part=1', Request::METHOD_GET);

        $this->assertResponseStatusCode(200);

        $data = $this->getJsonResponse();
        $this->assertArrayHasKey('chart', $data);
        $this->assertArrayHasKey('series', $data);
    }

    public function getValidDataProvider()
    {
        return new \ApiTest\JsonFileIterator('data/api/chart');
    }

    /**
     * @dataProvider getValidDataProvider
     * @group LongTest
     */
    public function testGetValidDataChart($params, $expectedJson, $message, $logFile)
    {
        $this->dispatch('/api/chart?' . $params, Request::METHOD_GET);

        $this->assertResponseStatusCode(200);
        $this->assertNumericJson($expectedJson, $this->getResponse()->getContent(), $message, $logFile);
    }

    public function getValidDataProviderPanel()
    {
        return new \ApiTest\JsonFileIterator('data/api/chart/getPanelFilters');
    }

    /**
     * @dataProvider getValidDataProviderPanel
     * @group LongTest
     */
    public function testGetValidDataChartPanel($params, $expectedJson, $message, $logFile)
    {
        $this->dispatch('/api/chart/getPanelFilters?' . $params, Request::METHOD_GET);

        $this->assertResponseStatusCode(200);
        $this->assertNumericJson($expectedJson, $this->getResponse()->getContent(), $message, $logFile);
    }

}
