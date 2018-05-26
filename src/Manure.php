<?php
/**
 * @license MIT
 * @copyright Copyright (c) 2018
 * @author: bugbear
 * @date: 2018/4/19
 * @time: 下午5:03
 */

namespace Eclogue\Manjusaka;

use Courser\App;
use GuzzleHttp\Tests\Psr7\Str;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use PhpParser\NodeTraverser;
use Symfony\Component\Yaml\Yaml;

class Manure
{
    public $app;

    public $structure;

    protected $root;

    protected $entrance;

    protected $modelPath;

    public function __construct(App $app, string $root, string $entrance)
    {
        $this->app = $app;
        $this->root = $root;
        $this->entrance = $entrance;
        $this->structure = new Straw($this->entrance, $this->root);

    }


    /**
     * @throws \ReflectionException
     */
    public function dump()
    {
        $routes = $this->app->layer;
        $indices = [];

        foreach ($routes as $key => $layers) {
            $doc = [];
            if (is_array($layers)) {
                foreach ($layers as $k => $route) {
                    $method = $route->method;
                    $doc[$method] = [];
                    if (!empty($route->paramNames)) {
                        foreach ($route->paramNames as $name) {
                            $doc[$method]['parameters'][] = [
                                'in' => 'path',
                                'name' => $name,
                                'required' => true,
                                'description' => $name,
                                'type' => 'string'
                            ];
                        }
                    }

                    $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
                    $traverser = new NodeTraverser;
                    $catheter = new Catheter();
                    $traverser->addVisitor($catheter);
                    foreach ((array)$route->callable as $callable) {
                        if (is_array($callable)) {
                            $reflection = new \ReflectionMethod(... $callable);
                        } else {
                            $reflection = new \ReflectionFunction($callable);
                        }
                        $start = $reflection->getStartLine();
                        $end = $reflection->getEndLine();
                        $file = $reflection->getFileName();
                        $comment = $reflection->getDocComment();
                        $pattern = '#([\/|\*]+)#';
                        $comment = preg_replace($pattern, '', $comment);
                        $comment = explode(PHP_EOL, $comment);
                        $description = [];
                        foreach ($comment as $item) {
                            $item = preg_replace('#^[\s]+#', '', $item);
                            if ($item) {
                                preg_match('#^@#', $item, $match);

                                if (!empty($match)) {
                                    continue;
                                }

                                $description[] = $item;
                            }
                        }


                        $reader = new \SplFileObject($file, 'r');
                        $reader->seek($start);
                        $code = '<?php' . PHP_EOL;
                        $main = false;
                        for ($i = $start; $i < $end; $i++) {
                            $line = $reader->current();
                            $reader->seek($i);

                            $brace = preg_replace('%[\s+]%', '', $line);
                            if (!$main && $brace === '{') {
                                continue;
                            }

                            $main = true;
                            $code .= $line;

                        }

                        $stmts = $parser->parse($code);
                        $traverser->traverse($stmts);
                        $params = $catheter->params;
                        if (!empty($params['body'])) {
                            $doc[$method]['parameters'][] = $params['body'];
                        }

                        if (!empty($params['query'])) {
                            $doc[$method]['parameters'][] = $params['query'];
                        }
                    }

                    $main = [
                        'tags' => [],
                        "description" => implode('|', $description),
                        'security' => [],
                        'responses' => [
                            '200' => [
                                'description' => '',
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'message' => [
                                            'type' => 'string',
                                            'default' => 'ok'
                                        ],
                                        'data' => [
                                            'type' => 'object'
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ];
                    $doc[$method] = array_merge($doc[$method], $main);

                    $path = $route->route;
//                    $yaml = preg_replace('#([\:|\{|}]+)#', '', $path);
                    $path = preg_replace('#:([a-zA-Z0-9]+)#', '{$1}', $path);
                    $yaml = preg_replace('#([{|}]+)#', '', $path);
                    $yaml = trim($yaml, '\/');
                    $yaml = str_replace('/', '_', $yaml);
                    $yaml = $yaml . '.yaml';
                    $indices[$path] = [
                        '$ref' => $yaml
                    ];
                    $this->structure->appendRouter($yaml, $doc);
//                    file_put_contents($yaml, $res);
                }
            }
        }

        $this->structure->genRouterIndices($indices);
    }

    public function dumpModel(string $path)
    {
        $files = glob($path . '/*.php');
        $indices = [];
        foreach($files as $key => $file) {
            $class = require ($file);
            $name = basename($file, '.php');
            $yaml = $name . '.yaml';
            $indices[$name] = [
                '$ref' => $yaml
            ];
            $class .= '\\' . $name;
            $model = new $class;
            $fields = $model->fields;
            if (empty($fields)) {
                continue;
            }

            $definition = [
                'description' => 'Model of ' . $name,
                'type' => 'object',
                'properties' => [],
            ];
            $properties = [];

            foreach ($fields as $field => $schema) {
                $type = $schema['type'] === 'int' ? 'integer' : 'string';
                $properties[$field] = [
                    'type' => $type,
                    'description' => $schema['comment'] ?? '',
                ];
                if (isset($schema['default'])) {
                    $properties[$field]['default'] = [$schema['default']];
                }
            }

            $definition['properties'] = $properties;
            $this->structure->addDefinition($yaml, $definition);
        }

        $this->structure->genDefinitionIndeices($indices);
    }
}
