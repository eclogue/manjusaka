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


    public function appendRouter($file, array $router)
    {
        $file = $this->structure['router'] . '/' . $file;
        if (!file_exists($file)) {
            $data = Yaml::dump($router, 10, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
            file_put_contents($file, $data, FILE_APPEND | LOCK_EX);

            return true;
        }

        $doc = Yaml::parseFile($file);
        foreach ($router as $method => $route) {
            if (isset($doc[$method])) {
                continue;
            }

            $data = [$method => $route];
            $data = Yaml::dump($data, 10, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);

            file_put_contents($file, $data, FILE_APPEND | LOCK_EX);
        }

        return true;
    }

    public function addDefinition($file, $defition)
    {
        $file = $this->structure['definition'] . '/' . $file;
        if (!file_exists($file)) {
            $data = Yaml::dump($defition, 10, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
            file_put_contents($file, $data);

            return true;
        }

        $doc = Yaml::parseFile($file);
        $origin = $doc['properties'];
        $properties = $defition['properties'];
        $fields = $origin;
        foreach ($properties as $key => $field) {
            if (isset($origin[$key]) && $origin[$key] == $field) {
                continue;
            }

            $fields[$key] = $field;
        }

        foreach ($origin as $key => $value) {
            if (!isset($properties[$key]) && isset($fields[$key])) {
                unset($fields[$key]);
            }
        }

        $defition['properties'] = $fields;
        $data = Yaml::dump($defition, 10, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
        file_put_contents($file, $data);
    }

    public function genRouterIndices($router)
    {
        $file = $this->structure['router'] . '/index.yaml';
        $data = Yaml::dump($router, 10, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
        file_put_contents($file, $data);
    }

    public function genDefinitionIndeices($definition)
    {
        $file = $this->structure['definition'] . '/index.yaml';
        $data = Yaml::dump($definition, 10, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
        file_put_contents($file, $data);
    }
}