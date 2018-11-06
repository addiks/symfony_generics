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

namespace Addiks\SymfonyGenerics\Services;

use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Psr\Container\ContainerInterface;
use ErrorException;
use ReflectionParameter;
use ReflectionType;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;
use Addiks\SymfonyGenerics\Services\EntityRepositoryInterface;
use ReflectionClass;

final class ArgumentCompiler implements ArgumentCompilerInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var EntityRepositoryInterface
     */
    private $entityRepository;

    public function __construct(
        ContainerInterface $container,
        EntityRepositoryInterface $entityRepository
    ) {
        $this->container = $container;
        $this->entityRepository = $entityRepository;
    }

    public function buildArguments(
        array $argumentsConfiguration,
        Request $request
    ): array {
        /** @var array<int, mixed> $routeArguments */
        $routeArguments = array();

        foreach ($argumentsConfiguration as $key => $argumentConfiguration) {
            /** @var array|string $argumentConfiguration */

            /** @var string|null $parameterTypeName */
            $parameterTypeName = null;

            if (isset($argumentConfiguration['entity-class'])) {
                $parameterTypeName = $argumentConfiguration['entity-class'];
            }

            /** @var mixed $argumentValue */
            $argumentValue = $this->resolveArgumentConfiguration(
                $argumentConfiguration,
                $request,
                $parameterTypeName
            );

            $routeArguments[$key] = $argumentValue;
        }

        return $routeArguments;
    }

    public function buildCallArguments(
        ReflectionMethod $methodReflection,
        array $argumentsConfiguration,
        Request $request
    ): array {
        /** @var array<int, mixed> $callArguments */
        $callArguments = array();

        foreach ($methodReflection->getParameters() as $index => $parameterReflection) {
            /** @var ReflectionParameter $parameterReflection */

            /** @var string $parameterName */
            $parameterName = $parameterReflection->getName();

            /** @var mixed $requestValue */
            $requestValue = $request->get($parameterName);

            if (isset($argumentsConfiguration[$parameterName])) {
                /** @var array|string $argumentConfiguration */
                $argumentConfiguration = $argumentsConfiguration[$parameterName];

                Assert::true(is_string($argumentConfiguration) || is_array($argumentConfiguration));

                /** @var string|null $parameterTypeName */
                $parameterTypeName = null;

                if ($parameterReflection->hasType()) {
                    /** @var ReflectionType|null $parameterType */
                    $parameterType = $parameterReflection->getType();

                    if ($parameterType instanceof ReflectionType) {
                        $parameterTypeName = $parameterType->__toString();
                    }
                }

                /** @var mixed $argumentValue */
                $argumentValue = $this->resolveArgumentConfiguration(
                    $argumentConfiguration,
                    $request,
                    $parameterTypeName
                );

                $callArguments[$index] = $argumentValue;

            } elseif (!is_null($requestValue)) {
                $callArguments[$index] = $requestValue;
            }
        }

        return $callArguments;
    }

    /**
     * @param array|string $argumentConfiguration
     *
     * @return mixed
     */
    private function resolveArgumentConfiguration(
        $argumentConfiguration,
        Request $request,
        ?string $parameterTypeName
    ) {
        /** @var mixed $argumentValue */
        $argumentValue = null;

        if (is_array($argumentConfiguration)) {
            if (isset($argumentConfiguration['id'])) {
                $argumentValue = $this->container->get($argumentConfiguration['id']);
            }

        } else {
            if (is_int(strpos($argumentConfiguration, '::'))) {
                [$factoryClass, $factoryMethod] = explode('::', $argumentConfiguration);

                if (!empty($factoryClass)) {
                    if ($factoryClass[0] == '@') {
                        /** @var string $factoryServiceId */
                        $factoryServiceId = substr($factoryClass, 1);

                        /** @var object|null $factoryObject */
                        $factoryObject = $this->container->get($factoryServiceId);

                        Assert::methodExists($factoryObject, $factoryMethod, sprintf(
                            "Did not find service with id '%s' that has a method '%s'!",
                            $factoryServiceId,
                            $factoryMethod
                        ));

                        $factoryReflection = new ReflectionClass($factoryObject);

                        /** @var ReflectionMethod $methodReflection */
                        $methodReflection = $factoryReflection->getMethod($factoryMethod);

                        $callArguments = $this->buildCallArguments(
                            $methodReflection,
                            [], # TODO
                            $request
                        );

                        # Create by factory-service-object
                        $argumentValue = call_user_func_array([$factoryObject, $factoryMethod], $callArguments);

                    } else {
                        # Create by static factory-method of other class
                        $argumentValue = call_user_func_array($argumentConfiguration, []);
                    }
                }

            } elseif ($argumentConfiguration[0] == '$') {
                $argumentValue = $request->get(substr($argumentConfiguration, 1));

            } elseif ($argumentConfiguration[0] == '@') {
                $argumentValue = $this->container->get(substr($argumentConfiguration, 1));
            }
        }

        if (!empty($parameterTypeName)) {
            if (class_exists($parameterTypeName)) {
                $argumentValue = $this->entityRepository->findEntity($parameterTypeName, $argumentValue);
                # TODO: error handling "not an entty", "entity not found", ...
            }
        }

        return $argumentValue;
    }

}
