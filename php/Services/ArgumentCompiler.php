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
use ReflectionParameter;
use ReflectionType;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;
use ReflectionClass;
use ReflectionFunctionAbstract;
use InvalidArgumentException;
use ReflectionException;
use Doctrine\ORM\EntityManagerInterface;
use ValueObjects\ValueObjectInterface;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ArgumentCompiler implements ArgumentCompilerInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(
        ContainerInterface $container,
        EntityManagerInterface $entityManager
    ) {
        $this->container = $container;
        $this->entityManager = $entityManager;
    }

    public function buildArguments(
        array $argumentsConfiguration,
        Request $request,
        array $additionalData = array()
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
                $parameterTypeName,
                $additionalData
            );

            $routeArguments[$key] = $argumentValue;
        }

        return $routeArguments;
    }

    public function buildCallArguments(
        ReflectionFunctionAbstract $routineReflection,
        array $argumentsConfiguration,
        Request $request,
        array $predefinedArguments = array(),
        array $additionalData = array()
    ): array {
        /** @var array<int, mixed> $callArguments */
        $callArguments = array();

        foreach ($routineReflection->getParameters() as $index => $parameterReflection) {
            /** @var ReflectionParameter $parameterReflection */

            if (isset($predefinedArguments[$index])) {
                $callArguments[] = $predefinedArguments[$index];
                continue;
            }

            /** @var string $parameterName */
            $parameterName = $parameterReflection->getName();

            /** @var mixed $requestValue */
            $requestValue = $request->get($parameterName);

            /** @var string|null $parameterTypeName */
            $parameterTypeName = null;

            if ($parameterReflection->hasType()) {
                /** @var ReflectionType|null $parameterType */
                $parameterType = $parameterReflection->getType();

                if ($parameterType instanceof ReflectionType) {
                    $parameterTypeName = $parameterType->__toString();
                }
            }

            if (isset($argumentsConfiguration[$parameterName])) {
                /** @var array|string $argumentConfiguration */
                $argumentConfiguration = $argumentsConfiguration[$parameterName];

                /** @var mixed $argumentValue */
                $argumentValue = $argumentConfiguration;

                if (is_string($argumentConfiguration) || is_array($argumentConfiguration)) {
                    $argumentValue = $this->resolveArgumentConfiguration(
                        $argumentConfiguration,
                        $request,
                        $parameterTypeName,
                        $additionalData
                    );
                }

                $callArguments[$index] = $argumentValue;

            } elseif (isset($argumentsConfiguration[$index])) {
                /** @var array|string $argumentConfiguration */
                $argumentConfiguration = $argumentsConfiguration[$index];

                /** @var mixed $argumentValue */
                $argumentValue = $argumentConfiguration;

                if (is_string($argumentConfiguration) || is_array($argumentConfiguration)) {
                    $argumentValue = $this->resolveArgumentConfiguration(
                        $argumentConfiguration,
                        $request,
                        $parameterTypeName,
                        $additionalData
                    );
                }

                $callArguments[$index] = $argumentValue;

            } elseif (!is_null($requestValue)) {
                if (!is_null($parameterTypeName)) {
                    /** @psalm-suppress UndefinedClass ValueObjects\ValueObjectInterface does not exist */
                    if (is_subclass_of($parameterTypeName, ValueObjectInterface::class)) {
                        $argumentValue = $parameterTypeName::fromNative($requestValue);

                        $callArguments[$index] = $argumentValue;
                    }

                } else {
                    $callArguments[$index] = $requestValue;
                }

            } elseif ($parameterTypeName === Request::class) {
                $callArguments[$index] = $request;

            } else {
                try {
                    $callArguments[$index] = $parameterReflection->getDefaultValue();

                } catch (ReflectionException $exception) {
                    throw new InvalidArgumentException(sprintf(
                        "Missing argument '%s' for the call to '%s'!",
                        $parameterName,
                        $routineReflection->getName()
                    ));
                }
            }
        }

        return $callArguments;
    }

    /**
     * @param array|string $argumentConfiguration
     *
     * @return mixed
     */
    public function resolveArgumentConfiguration(
        $argumentConfiguration,
        Request $request,
        ?string $parameterTypeName,
        array $additionalData = array()
    ) {
        /** @var mixed $argumentValue */
        $argumentValue = null;

        if (is_array($argumentConfiguration)) {
            if (isset($argumentConfiguration['entity-class'])) {
                $parameterTypeName = $argumentConfiguration['entity-class'];
            }

            if (isset($argumentConfiguration['service-id'])) {
                $argumentValue = $this->container->get($argumentConfiguration['service-id']);

                Assert::object($argumentValue, sprintf(
                    "Did not find service '%s'!",
                    $argumentConfiguration['service-id']
                ));

            } elseif (isset($argumentConfiguration['entity-id'])) {
                $argumentValue = $this->resolveStringArgumentConfiguration(
                    $argumentConfiguration['entity-id'],
                    $request
                );

            } elseif (class_exists($parameterTypeName)) {
                $argumentValue = $this->resolveStringArgumentConfiguration(
                    $parameterTypeName,
                    $request
                );
            }

            if (isset($argumentConfiguration['method'])) {
                $methodReflection = new ReflectionMethod($argumentValue, $argumentConfiguration['method']);

                if (!isset($argumentConfiguration['arguments'])) {
                    $argumentConfiguration['arguments'] = [];
                }

                /** @var array $callArguments */
                $callArguments = $this->buildCallArguments(
                    $methodReflection,
                    $argumentConfiguration['arguments'],
                    $request
                );

                $argumentValue = $methodReflection->invokeArgs($argumentValue, $callArguments);
            }

        } else {
            $argumentValue = $this->resolveStringArgumentConfiguration(
                $argumentConfiguration,
                $request,
                $additionalData
            );
        }

        if (!empty($parameterTypeName) && !is_object($argumentValue)) {
            if (class_exists($parameterTypeName)) {
                /** @psalm-suppress UndefinedClass ValueObjects\ValueObjectInterface does not exist */
                if (is_subclass_of($parameterTypeName, ValueObjectInterface::class)) {
                    $argumentValue = call_user_func("{$parameterTypeName}::fromNative", $argumentValue);

                } else {
                    $argumentValue = $this->entityManager->find($parameterTypeName, $argumentValue);
                }
            }
        }

        return $argumentValue;
    }

    /**
     * @return mixed
     */
    private function resolveStringArgumentConfiguration(
        string $argumentConfiguration,
        Request $request,
        array $additionalData = array()
    ) {
        /** @var mixed $argumentValue */
        $argumentValue = null;

        $argumentConfiguration = trim($argumentConfiguration);

        if (is_int(strpos($argumentConfiguration, '::'))) {
            [$factoryClass, $factoryMethod] = explode('::', $argumentConfiguration);

            /** @var array<string> $callArgumentConfigurations */
            $callArgumentConfigurations = array();

            if (is_int(strpos($factoryMethod, '('))) {
                $factoryMethod = str_replace(')', '', $factoryMethod);
                [$factoryMethod, $rawArguments] = explode('(', $factoryMethod, 2);

                foreach (explode(",", $rawArguments) as $rawArgument) {
                    /** @var string $rawArgument */

                    $callArgumentConfigurations[] = trim($rawArgument);
                }
            }

            if (!empty($factoryClass)) {
                if ($factoryClass[0] == '@') {
                    /** @var string $factoryServiceId */
                    $factoryServiceId = substr($factoryClass, 1);

                    /** @var object $factoryObject */
                    $factoryObject = $this->container->get($factoryServiceId);

                    Assert::object($factoryObject, sprintf(
                        "Could not find service with id '%s'!",
                        $factoryServiceId
                    ));

                    Assert::methodExists($factoryObject, $factoryMethod, sprintf(
                        "Method '%s' does not exist on service '%s'! (Class '%s')",
                        $factoryMethod,
                        $factoryServiceId,
                        get_class($factoryObject)
                    ));

                    /** @var array<int, mixed> $callArguments */
                    $callArguments = $this->buildCallArguments(
                        new ReflectionMethod($factoryObject, $factoryMethod),
                        $callArgumentConfigurations,
                        $request
                    );

                    $argumentValue = $this->callOnObject(
                        $factoryObject,
                        $factoryMethod,
                        $callArguments,
                        $request,
                        sprintf(
                            "Did not find service with id '%s' that has a method '%s'!",
                            $factoryServiceId,
                            $factoryMethod
                        )
                    );

                } elseif (is_int(strpos($factoryClass, '#'))) {
                    # Create by call on entity
                    [$entityClass, $idRaw] = explode('#', $factoryClass);

                    $id = $this->resolveStringArgumentConfiguration($idRaw, $request, $additionalData);

                    /** @var object $entity */
                    $entity = $this->entityManager->find($entityClass, $id);

                    Assert::object($entity, sprintf(
                        "Could not find entity '%s' with id '%s'!",
                        $entityClass,
                        $id
                    ));

                    /** @var array<int, mixed> $callArguments */
                    $callArguments = $this->buildCallArguments(
                        new ReflectionMethod($entity, $factoryMethod),
                        $callArgumentConfigurations,
                        $request
                    );

                    $argumentValue = $this->callOnObject(
                        $entity,
                        $factoryMethod,
                        $callArguments,
                        $request,
                        sprintf(
                            "Entity '%s' does not have method '%s'!",
                            $entityClass,
                            $factoryMethod
                        )
                    );

                } else {
                    $callArguments = array();

                    if (is_int(strpos($argumentConfiguration, '('))) {
                        $argumentConfiguration = str_replace(")", "", $argumentConfiguration);
                        [$argumentConfiguration, $callArgumentsRaw] = explode('(', $argumentConfiguration);

                        if (!empty($callArgumentsRaw)) {
                            foreach (explode(',', $callArgumentsRaw) as $callArgumentRaw) {
                                $callArguments[] = $this->resolveStringArgumentConfiguration(
                                    $callArgumentRaw,
                                    $request,
                                    $additionalData
                                );
                            }
                        }
                    }

                    # Create by static factory-method of other class
                    $argumentValue = call_user_func_array($argumentConfiguration, $callArguments);
                }

            } else {
                # TODO: What to do here? What could "::Something" be? A template?
            }

        } elseif ($argumentConfiguration[0] == "'" && $argumentConfiguration[strlen($argumentConfiguration) - 1] == "'") {
            $argumentValue = substr($argumentConfiguration, 1, strlen($argumentConfiguration) - 2);

        } elseif ($argumentConfiguration == '$') {
            $argumentValue = $request->getContent(false);

        } elseif (substr($argumentConfiguration, 0, 7) === '$files.') {
            /** @var FileBag $files */
            $files = $request->files;

            [, $filesKey, $fileArgument] = explode(".", $argumentConfiguration);

            /** @var UploadedFile $file */
            $file = $files->get($filesKey);

            Assert::isInstanceOf($file, UploadedFile::class, sprintf(
                "Missing request-argument '%s' as uploaded file!",
                $filesKey
            ));

            $argumentValue = [
                'object' => $file,
                'originalname' => $file->getClientOriginalName(),
                'filename' => $file->getFilename(),
                'content' => file_get_contents($file->getPathname()),
                'mimetype' => $file->getMimeType(),
            ][$fileArgument];

        } elseif ($argumentConfiguration[0] == '$') {
            $argumentValue = $request->get(substr($argumentConfiguration, 1));

        } elseif ($argumentConfiguration[0] == '@') {
            $argumentValue = $this->container->get(substr($argumentConfiguration, 1));

        } elseif ($argumentConfiguration[0] == '%') {
            /** @var string $key */
            $key = substr($argumentConfiguration, 1);

            if (is_int(strpos($key, '.'))) {
                [$key, $methodName] = explode('.', $key);

                Assert::keyExists($additionalData, $key, sprintf(
                    'Missing additional-data key "%s"',
                    $key
                ));

                $argumentValue = $additionalData[$key];

                Assert::methodExists($argumentValue, $methodName, sprintf(
                    "Missing method '%s' on '%s'!",
                    $methodName,
                    $argumentConfiguration
                ));

                $argumentValue = call_user_func([$argumentValue, $methodName]);

            } else {
                Assert::keyExists($additionalData, $key, sprintf(
                    'Missing additional-data key "%s"',
                    $key
                ));

                $argumentValue = $additionalData[$key];
            }

        } elseif (is_int(strpos($argumentConfiguration, '#'))) {
            # Create as entity
            [$entityClass, $idRaw] = explode('#', $argumentConfiguration);

            $id = $this->resolveStringArgumentConfiguration($idRaw, $request);

            $argumentValue = $this->entityManager->find($entityClass, $id);

        } else {
            $argumentValue = $argumentConfiguration;
        }

        return $argumentValue;
    }

    /**
     * @param object $object
     *
     * @return mixed
     */
    private function callOnObject(
        $object,
        string $method,
        array $callArguments,
        Request $request,
        string $methodNotExistMessage
    ) {
        Assert::methodExists($object, $method, $methodNotExistMessage);

        $objectReflection = new ReflectionClass($object);

        /** @var ReflectionMethod $methodReflection */
        $methodReflection = $objectReflection->getMethod($method);

        $callArguments = $this->buildCallArguments(
            $methodReflection,
            [], # TODO
            $request,
            $callArguments
        );

        return call_user_func_array([$object, $method], $callArguments);
    }

}
