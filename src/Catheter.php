<?php
/**
 * @license MIT
 * @copyright Copyright (c) 2018
 * @author: bugbear
 * @date: 2018/5/25
 * @time: 下午12:31
 */

namespace Eclogue\Manjusaka;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class Catheter extends NodeVisitorAbstract
{
    public $params = [];

    public $bodyMethodName = 'getPayload';

    public $queryMethodName = 'getQuery';

    public function setMethodName(string $type, string $name)
    {

    }


    public function enterNode(Node $node)
    {
//        if ($node instanceof Node\Scalar\String_) {
//            $node->value = 'foo';
//        }


        if ($node instanceof Node\Expr\MethodCall) {
            if ((string)$node->name == $this->bodyMethodName) {
                $name = $node->args[0]->value->value;
                $payload = [
                    $name => [
                        'type' => 'string',
                    ]
                ];

                if ($node->args[1]) {
                    $default = $node->args[1]->value->value;
                    $payload[$name]['default'] = $default;
                }

                if (isset($this->params['body'])) {
                    $origin = $this->params['body']['schema']['properties'];
                    $this->params['body']['schema']['properties'] = $origin + $payload;
                } else {
                    $this->params['body'] = [
                        'in' => 'body',
                        'description' => 'payload params',
                        'schema' => [
                            'type' => 'object',
                            'properties' => $payload,
                        ]
                    ];
                }

            } elseif ((string)$node->name === $this->queryMethodName) {
                $name = $node->args[0]->value->value;
                $this->params['query'][] = [
                    'in' => 'query',
                    'type' => 'string',
                    'description' => 'query param:' . $name,
                    'required' => false,
                    'name' => $name,
                ];
            }
        }

    }

}