<?php
/**
 * Created by PhpStorm.
 * User: mirocillik
 * Date: 05/11/13
 * Time: 14:48
 */

namespace Keboola\Temp;

class Temp
{
    /**
     * @var String
     */
    protected $prefix;

    /**
     * @var \SplFileInfo[]
     */
    protected $files = array();

    /**
     * @var Bool
     */
    protected $preserveRunFolder = false;

    /**
     *
     * If temp folder needs to be deterministic, you can use ID as the last part of folder name
     *
     * @var string
     */
    protected $id = "";

    public function __construct($prefix = '')
    {
        $this->prefix = $prefix;
        $this->id = uniqid("run-", true);
    }

    public function initRunFolder()
    {
        clearstatcache();
        if (!file_exists($this->getTmpPath()) && !is_dir($this->getTmpPath())) {
            mkdir($this->getTmpPath(), 0777, true);
        }
    }

    /**
     * @param bool $value
     */
    public function setPreserveRunFolder($value)
    {
        $this->preserveRunFolder = $value;
    }

    /**
     * Get path to temp directory
     *
     * @return string
     */
    protected function getTmpPath()
    {
        $tmpDir = sys_get_temp_dir();
        if (!empty($this->prefix)) {
            $tmpDir .= "/" . $this->prefix;
        }
        $tmpDir .= "/" . $this->id;
        return $tmpDir;
    }

    /**
     * Returns path to temp folder for current request
     *
     * @return string
     */
    public function getTmpFolder()
    {
        return $this->getTmpPath();
    }

    /**
     * Create empty file in TMP directory
     *
     * @param string $suffix filename suffix
     * @param bool $preserve
     * @throws \Exception
     * @return \SplFileInfo
     */
    public function createTmpFile($suffix = null, $preserve = false)
    {
        $file = uniqid();

        if ($suffix) {
            $file .= '-' . $suffix;
        }

        return $this->createFile($file, $preserve);
    }

    /**
     * Creates named temporary file
     *
     * @param $fileName
     * @param bool $preserve
     * @return \SplFileInfo
     * @throws \Exception
     */
    public function createFile($fileName, $preserve = false)
    {
        $this->initRunFolder();

        $fileInfo = new \SplFileInfo($this->getTmpPath() . '/' . $fileName);

        touch($fileInfo->getPathname());
        $this->files[] = array(
            'file'  => $fileInfo,
            'preserve'  => $preserve
        );
        chmod($fileInfo->getPathname(), 0600);

        return $fileInfo;
    }

    /**
     * Set temp id
     *
     * @param $id
     */
    public function setId($id) {
        $this->id = $id;
    }

    /**
     * Delete all files created by syrup component run
     */
    function __destruct()
    {
        $preserveRunFolder = $this->preserveRunFolder;

        foreach ($this->files as $file) {
            if ($file['preserve']) {
                $preserveRunFolder = true;
            }
            if (file_exists($file['file']) && is_file($file['file']) && !$file['preserve']) {
                unlink($file['file']->getPathname());
            }
        }

        if (!$preserveRunFolder && is_dir($this->getTmpPath())) {
            rmdir($this->getTmpPath());
        }
    }
}
