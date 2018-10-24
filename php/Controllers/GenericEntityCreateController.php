<?php
/**
 * Copyright (C) 2018 Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 *
 * @license GPL-3.0
 *
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Controllers;

use Addiks\SymfonyGenerics\Controllers\ControllerHelperInterface;
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\XmlFileLoader;
use Webmozart\Assert\Assert;
use XSLTProcessor;
use DOMDocument;
use ReflectionClass;
use ReflectionMethod;
use ReflectionParameter;
use Psr\Container\ContainerInterface;
use ErrorException;

final class GenericEntityCreateController
{

    /**
     * @var ControllerHelperInterface
     */
    private $controllerHelper;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $entityClass;

    /**
     * @var array<string, array<string, mixed>>
     */
    private $calls = array();

    /**
     * @var string|null
     */
    private $factory = null;

    /**
     * @var array<string, mixed>|null
     */
    private $constructArguments;

    /**
     * @var ArgumentCompilerInterface
     */
    private $argumentBuilder;

    /**
     * @var string
     */
    private $successResponse;

    public function __construct(
        ControllerHelperInterface $controllerHelper,
        ArgumentCompilerInterface $argumentBuilder,
        ContainerInterface $container,
        array $options
    ) {
        Assert::null($this->controllerHelper);
        Assert::keyExists($options, 'entity-class');
        Assert::true(class_exists($options['entity-class']));

        $options = array_merge([
            'calls' => [],
            'success-response' => "object created",
            'factory' => null,
        ], $options);

        $this->controllerHelper = $controllerHelper;
        $this->argumentBuilder = $argumentBuilder;
        $this->container = $container;
        $this->entityClass = $options['entity-class'];
        $this->successResponse = $options['success-response'];
        $this->factory = $options['factory'];

        foreach ($options['calls'] as $methodName => $arguments) {
            /** @var array $arguments */

            Assert::isArray($arguments);

            if ($methodName === 'construct') {
                $this->constructArguments = $arguments;

            } else {
                Assert::true(method_exists($this->entityClass, $methodName));

                $this->calls[$methodName] = $arguments;
            }
        }
    }

    public function createEntity(Request $request): Response
    {
        $classReflection = new ReflectionClass($this->entityClass);

        /** @var ReflectionMethod $constructorReflection */
        $constructorReflection = $classReflection->getConstructor();

        /** @var array<int, mixed> $constructArguments */
        $constructArguments = array();

        if (!empty($this->constructArguments)) {
            $constructArguments = $this->argumentBuilder->buildCallArguments(
                $constructorReflection,
                $this->constructArguments,
                $request
            );
        }

        /** @var object|null $entity */
        $entity = null;

        if (!empty($this->factory)) {
            if (is_int(strpos($this->factory, '::'))) {
                [$factoryClass, $factoryMethod] = explode('::', $this->factory, 2);

                if (!empty($factoryClass)) {
                    if ($factoryClass[0] == '@') {
                        /** @var string $factoryServiceId */
                        $factoryServiceId = substr($factoryClass, 1);

                        /** @var object|null $factoryObject */
                        $factoryObject = $this->container->get($factoryServiceId);

                        Assert::methodExists($factoryObject, $factoryMethod, sprintf(
                            "Did not find service with id '%s' that has a method '%s' to use as factory for '%s'!",
                            $factoryServiceId,
                            $factoryMethod,
                            $this->entityClass
                        ));

                        # Create by factory-service-object
                        $entity = call_user_func_array([$factoryObject, $factoryMethod], $constructArguments);

                    } else {
                        # Create by static factory-method of other class
                        $entity = call_user_func_array($this->factory, $constructArguments);
                    }
                }

            } elseif (method_exists($this->entityClass, $this->factory)) {
                # Create by static factory method on entity class
                $entity = call_user_func_array(
                    sprintf("%s::%s", $this->entityClass, $this->factory),
                    $constructArguments
                );

            } elseif (function_exists($this->factory)) {
                # Create by factory function
                $entity = call_user_func_array($this->factory, $constructArguments);
            }

        } else {
            # Create by calling the constructor directly
            $entity = $classReflection->newInstanceArgs($constructArguments);
        }

        Assert::isInstanceOf($entity, $this->entityClass);

        foreach ($this->calls as $methodName => $callArgumentConfiguration) {
            /** @var array $callArgumentConfiguration */

            /** @var ReflectionMethod $methodReflection */
            $methodReflection = $classReflection->getMethod($methodName);

            $callArguments = $this->argumentBuilder->buildCallArguments(
                $methodReflection,
                $callArgumentConfiguration,
                $request
            );

            $methodReflection->invoke($entity, $callArguments);
        }

        $this->controllerHelper->persistEntity($entity);
        $this->controllerHelper->flushORM();

        return new Response($this->successResponse, 200);
    }

}
