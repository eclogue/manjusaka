<?php
/**
 * @license MIT
 * @copyright Copyright (c) 2018
 * @author: bugbear
 * @date: 2018/5/25
 * @time: 下午12:31
 */

namespace Eclogue\Manjusaka;


use Symfony\Component\Yaml\Yaml;
use Lily\Parser;

class Straw
{

    protected $entrance;

    protected $root;

    protected $mod = 0755;

    protected $structure = [];

    public function __construct($entrance, $root)
    {
        if (is_string($entrance)) {
            $entrance = Yaml::parse($entrance);
            $this->entrance = $entrance;
        } else {
            $this->entrance = $entrance;
        }

        $this->root = $root;
        $this->init();
    }

    /**
     * init structure
     */
    public function init()
    {
        if (empty($this->structure)) {
            $this->structure = [
                'root' => $this->root,
                'router' => $this->root . '/routers',
                'definition' => $this->root . '/definitions',
                'error' => $this->root . '/errors',
                'parameter' => $this->root . '/parameters',
                'output' => $this->root . '/document.json',
            ];
        }

        $this->mkdir($this->root);
        $this->mkdir($this->structure['router']);
        $this->mkdir($this->structure['definition']);
    }

    public function mkdir($dir)
    {
        if (!is_dir($dir)) {
            mkdir($dir, $this->mod);
        }
    }

    public function getStructure()
    {
        return $this->structure;
    }


    public function setMod($mod)
    {
        $this->mod = $mod;
    }

    /**
     * @throws \Exception
     */
    public function generate()
    {
        $parser = new Parser($this->root, $this->entrance);
        $documents = $parser->run();
        $documents = json_encode($documents);
        file_put_contents($this->structure['output'], $documents);
    }
}