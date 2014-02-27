<?php

namespace Application\View\Renderer;

use Traversable;
use Zend\View\Exception;
use Zend\View\Model\ModelInterface as Model;

class ExcelRenderer extends \Zend\View\Renderer\PhpRenderer
{

    /**
     * Constructor.
     *
     *
     * @todo handle passing helper plugin manager, options
     * @todo handle passing filter chain, options
     * @todo handle passing variables object, options
     * @todo handle passing resolver object, options
     * @param array $config Configuration key-value pairs.
     */
    public function __construct($config = array())
    {
        parent::__construct($config);
    }

    /**
     * Processes a view script and returns the output.
     *
     * @param  string|Model $nameOrModel Either the template to use, or a
     *                                   ViewModel. The ViewModel must have the
     *                                   template as an option in order to be
     *                                   valid.
     * @param  null|array|Traversable $values Values to use when rendering. If none
     *                                provided, uses those in the composed
     *                                variables container.
     * @return string The script output.
     * @throws Exception\DomainException if a ViewModel is passed, but does not
     *                                   contain a template option.
     * @throws Exception\InvalidArgumentException if the values passed are not
     *                                            an array or ArrayAccess object
     * @throws Exception\RuntimeException if the template cannot be rendered
     */
    public function render($nameOrModel, $values = null)
    {
        $askedFilename = $nameOrModel->getFilename();

        $allowedExtensions = array('xlsx', 'csv');
        $filename = pathinfo($askedFilename, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($askedFilename, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowedExtensions)) {
            $extension = reset($allowedExtensions);
        }
        $filename = $filename . '.' . $extension;

        $workbook = new \PHPExcel();
        $workbook->getProperties()->setCreator('GIMS');
        $workbook->getProperties()->setLastModifiedBy('GIMS');
        $workbook->getProperties()->setTitle($filename);
        $workbook->getProperties()->setSubject($filename);
        $workbook->getProperties()->setDescription('Generated by GIMS');

        // Inject the workbook in the view
        $nameOrModel->workbook = $workbook;

        // Render the workbook
        parent::render($nameOrModel, $values);

        // Save Excel 2007 file or CSV
        if ($extension == 'xlsx') {
            header("Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
            $objWriter = new \PHPExcel_Writer_Excel2007($workbook);
        } else {
            header("Content-type: text/csv");
            $objWriter = new \PHPExcel_Writer_CSV($workbook);
        }

        // Save file on disk so we can get its size
        $dir = 'data/cache/phpexcel';
        @mkdir($dir);
        $tempPath = $dir . '/' . time() . '_' . $filename;
        $objWriter->save($tempPath);

        // Send common headers
        header('Content-Description: File Transfer');
        header('Content-Transfer-Encoding: binary');
        header("Content-Disposition: attachment; filename=\"$filename\"");
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0,pre-check=0");
        header('Pragma: no-cache');
        header('Content-Length: ' . filesize($tempPath));

        // Send the file
        ob_clean();
        flush();
        @readfile($tempPath);

        unlink($tempPath);
    }

}
