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

    public $responseMethodName = 'json';

    public $statusMethodName = 'withStatus';

    public $response = [];

    public $variable = '';



    public function enterNode(Node $node)
    {
        if (!$node instanceof Node\Expr\MethodCall) {
            return null;
        }

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
                    'name' => 'body',
                    'schema' => [
                        'type' => 'object',
                        'properties' => $payload,
                    ]
                ];
            }

        } elseif ((string)$node->name === $this->queryMethodName) {
            $name = $node->args[0]->value->value;
            $this->params['query'] = [
                'in' => 'query',
                'type' => 'string',
                'description' => 'query param:' . $name,
                'required' => false,
                'name' => $name,
            ];
        } elseif ((string)$node->name === $this->responseMethodName) {
            $parent = $node->var;
            $value = $node->args[0]->value;
            $status = 200;
            if ($parent &&
                $parent->name &&
                (string)$parent->name->name == $this->statusMethodName) {
                $status = $parent->args[0]->value->value;
            }

            $data = $this->getChildArray($value);
            if (intval($status) >= 400) {
                $response = $this->response[$status] ?? [];
                foreach ($data as $key => &$item) {
                    if (isset($response[$key])) {
                        $enum = $response[$key]['enum'] ?? [];
                        $item['enum'] = array_merge($enum, $item['enum']);
                    }
                }
            }


            $this->response[$status] = $data;
        }

    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof Node\Expr\MethodCall) {
            return null;
        }


        if ((string)$node->name === $this->responseMethodName) {
//            $this->status = 200;
        }


    }

    public function afterTraverse(array $nodes)
    {
//        var_export($this->response);
    }


    public function getChildArray($node, $level = 0)
    {
        $values = [];
        if (!$node instanceof Node\Expr\Array_) {
            return $values;
        }

        $items = $node->items;
        foreach ($items as $index => $item) {
            $key = $item->key->value;
            $value = $item->value;
            $data = '';
            $type = 'string';
            if ($value instanceof Node\Expr\Variable) {
                $data = $value->name;
                $type = 'string';
            } elseif (isset($value->value)) {
                $data = $value->value;
                $type = gettype($data);
            } elseif ($value instanceof Node\Expr\Array_) {
                $data = $this->getChildArray($value);
                $type = 'object';
            }

            $values[$key] = [
                'type' => $type,
            ];
            if ($type === 'object') {
                $values[$key]['properties'] = $data;
            } else {
                $values[$key]['enum'][] = $data;
            }
        }

        return $values;
    }

    public function __set(string $type, string $name)
    {
        if (isset($this->$type)) {
            $this->$type = $name;
        }
    }

}