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

namespace Addiks\SymfonyGenerics\Controllers\API;

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
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionObject;
use Addiks\SymfonyGenerics\Events\EntityInteractionEvent;

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
     * @var array<string, mixed>
     */
    private $constructArguments = array();

    /**
     * @var ArgumentCompilerInterface
     */
    private $argumentBuilder;

    /**
     * @var string
     */
    private $successResponse;

    /**
     * @var string|null
     */
    private $authorizationAttribute;

    /**
     * @var string|null
     */
    private $successRedirectRoute;

    /**
     * @var array
     */
    private $successRedirectArguments;

    /**
     * @var integer
     */
    private $successRedirectStatus;

    public function __construct(
        ControllerHelperInterface $controllerHelper,
        ArgumentCompilerInterface $argumentBuilder,
        ContainerInterface $container,
        array $options
    ) {
        Assert::null($this->controllerHelper);
        Assert::keyExists($options, 'entity-class');

        $options = array_merge([
            'calls' => [],
            'success-response' => "object created",
            'factory' => null,
            'authorization-attribute' => null,
            'arguments' => [],
            'success-redirect' => null,
            'success-redirect-arguments' => [],
            'success-redirect-status' => 303,
        ], $options);

        $this->controllerHelper = $controllerHelper;
        $this->argumentBuilder = $argumentBuilder;
        $this->container = $container;
        $this->entityClass = $options['entity-class'];
        $this->successResponse = $options['success-response'];
        $this->factory = $options['factory'];
        $this->authorizationAttribute = $options['authorization-attribute'];
        $this->constructArguments = $options['arguments'];
        $this->successRedirectRoute = $options['success-redirect'];
        $this->successRedirectArguments = $options['success-redirect-arguments'];
        $this->successRedirectStatus = (int)$options['success-redirect-status'];

        foreach ($options['calls'] as $methodName => $arguments) {
            /** @var array $arguments */

            Assert::isArray($arguments);
            Assert::true(method_exists($this->entityClass, $methodName));

            $this->calls[$methodName] = $arguments;
        }
    }

    public function __invoke(): Response
    {
        /** @var Request $request */
        $request = $this->controllerHelper->getCurrentRequest();

        Assert::isInstanceOf($request, Request::class, "Cannot use controller outside of request-scope!");

        return $this->createEntity($request);
    }

    public function createEntity(Request $request): Response
    {
        /** @var object|null $factoryObject */
        $factoryObject = null;

        if (!empty($this->authorizationAttribute)) {
            $this->controllerHelper->denyAccessUnlessGranted($this->authorizationAttribute, $request);
        }

        /** @var ReflectionFunctionAbstract $constructorReflection */
        $constructorReflection = $this->findConstructorReflection($factoryObject);

        /** @var array<int, mixed> $constructArguments */
        $constructArguments = $this->argumentBuilder->buildCallArguments(
            $constructorReflection,
            $this->constructArguments,
            $request
        );

        /** @var object $entity */
        $entity = $this->createEntityByConstructor($constructorReflection, $constructArguments, $factoryObject);

        $this->performPostCreationCalls($entity, $request);

        if (!empty($this->authorizationAttribute)) {
            $this->controllerHelper->denyAccessUnlessGranted($this->authorizationAttribute, $entity);
        }

        $this->controllerHelper->persistEntity($entity);

        $this->controllerHelper->dispatchEvent("symfony_generics.entity_interaction", new EntityInteractionEvent(
            $this->entityClass,
            null, # TODO: get id via reflection
            $entity,
            "__construct",
            $constructArguments
        ));

        $this->controllerHelper->flushORM();

        if (!empty($this->successRedirectRoute)) {
            /** @var array $redirectArguments */
            $redirectArguments = $this->argumentBuilder->buildArguments(
                $this->successRedirectArguments,
                $request
            );
            $redirectArguments['entityId'] = $entity->getId(); # TODO: getId might not always exist! get id via doctrine

            return $this->controllerHelper->redirectToRoute(
                $this->successRedirectRoute,
                $redirectArguments,
                $this->successRedirectStatus
            );
        }

        return new Response($this->successResponse, 200);
    }

    /**
     * @param object $factoryObject
     */
    private function findConstructorReflection(&$factoryObject = null): ReflectionFunctionAbstract
    {
        /** @var ReflectionFunctionAbstract|null $constructorReflection */
        $constructorReflection = null;

        if (!empty($this->factory)) {
            if (is_int(strpos($this->factory, '::'))) {
                [$factoryClass, $factoryMethod] = explode('::', $this->factory, 2);

                if (!empty($factoryClass)) {
                    if ($factoryClass[0] == '@') {
                        # Create by factory-service-object

                        $factoryObject = $this->container->get(substr($factoryClass, 1));

                        Assert::object($factoryObject, sprintf(
                            "Did not find service '%s'!",
                            substr($factoryClass, 1)
                        ));

                        $constructorReflection = (new ReflectionObject($factoryObject))->getMethod($factoryMethod);

                    } else {
                        # Create by static factory-method of other class

                        $constructorReflection = (new ReflectionClass($factoryClass))->getMethod($factoryMethod);
                    }

                } else {
                    throw new ErrorException(sprintf(
                        "Invalid constructor definition: '%s'!",
                        $this->factory
                    ));
                }

            } elseif (method_exists($this->entityClass, $this->factory)) {
                # Create by static factory method on entity class

                $constructorReflection = (new ReflectionClass($this->entityClass))->getMethod($this->factory);

            } elseif (function_exists($this->factory)) {
                # Create by factory function

                $constructorReflection = new ReflectionFunction($this->factory);
            }

        } else {
            # Create by calling the constructor directly

            $constructorReflection = (new ReflectionClass($this->entityClass))->getConstructor();
        }

        return $constructorReflection;
    }

    /**
     * @param object|null $factoryObject
     *
     * @return object
     */
    private function createEntityByConstructor(
        ReflectionFunctionAbstract $constructorReflection,
        array $constructArguments,
        $factoryObject
    ) {
        /** @var object|null $entity */
        $entity = null;

        if ($constructorReflection instanceof ReflectionMethod) {
            if ($constructorReflection->isConstructor()) {
                $entity = $constructorReflection->getDeclaringClass()->newInstanceArgs($constructArguments);

            } elseif ($constructorReflection->isStatic()) {
                $entity = $constructorReflection->invokeArgs(null, $constructArguments);

            } else {
                $entity = $constructorReflection->invokeArgs($factoryObject, $constructArguments);
            }

        } elseif ($constructorReflection instanceof ReflectionFunction) {
            $entity = $constructorReflection->invokeArgs($constructArguments);
        }

        Assert::isInstanceOf($entity, $this->entityClass);

        return $entity;
    }

    /**
     * @param object $entity
     */
    private function performPostCreationCalls($entity, Request $request): void
    {
        $classReflection = new ReflectionClass($this->entityClass);

        foreach ($this->calls as $methodName => $callArgumentConfiguration) {
            /** @var array $callArgumentConfiguration */

            /** @var ReflectionMethod $methodReflection */
            $methodReflection = $classReflection->getMethod($methodName);

            $callArguments = $this->argumentBuilder->buildCallArguments(
                $methodReflection,
                $callArgumentConfiguration,
                $request
            );

            $methodReflection->invokeArgs($entity, $callArguments);
        }
    }

}
