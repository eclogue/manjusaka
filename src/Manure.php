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
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use PhpParser\NodeTraverser;

class Manure
{
    public $app;

    public $structure;

    public function __construct(App $app)
    {
        $this->app = $app;
    }


    /**
     * @throws \ReflectionException
     */
    public function run()
    {

        $routes = $this->app->layer;
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
                        'tags' => '',
                        'description' => '',
                        'security' => [],
                        'responses' => [
                            '200' => [
                                'description' => '',
                                "schema" => [
                                    "type" => "object",
                                    "properties" => [
                                        "message" => [
                                            "type" => 'string',
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
                    $yaml = preg_replace('#[\:|\{|}+]#', '', $path);
                    $yaml = trim($yaml, '\/');
                    $yaml = str_replace('/', '_', $yaml);
                    echo $yaml;
                }
            }
        }
    }
}
