<?php
/*
 * Copyright (c) 2014 Eltrino LLC (http://eltrino.com)
 *
 * Licensed under the Open Software License (OSL 3.0).
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://opensource.org/licenses/osl-3.0.php
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@eltrino.com so we can send you a copy immediately.
 */

namespace Diamante\ApiBundle\Handler;

use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\Validator\Validator;

class MethodParameters
{
    private $method;
    private $validator;
    private $data = [];

    public function __construct(\ReflectionMethod $method, Validator $validator)
    {
        $this->method = $method;
        $this->validator = $validator;
    }

    public function addParameterBag(ParameterBag $bag)
    {
        $this->data = array_merge($this->data, $bag->all());
    }

    public function putIn(ParameterBag $bag)
    {
        $parameters = $this->method->getParameters();

        foreach ($parameters as $parameter) {
            if ($bag->has($parameter->getName())) {
                continue;
            }

            if ($parameter->getClass()) {
                $mapper = new CommandProperties($parameter->getClass());
                $command = $mapper->map($this->data);

                $errors = $this->validator->validate($command);

                if (count($errors) > 0) {
                    $errorsString = (string)$errors;
                    throw new \InvalidArgumentException($errorsString);
                }

                $bag->set($parameter->getName(), $command);
            } elseif (array_key_exists($parameter->getName(), $this->data)) {
                $bag->set($parameter->getName(), $this->data[$parameter->getName()]);
            }
        }
    }
} 