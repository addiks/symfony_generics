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

                /** @var mixed $argumentValue */
                $argumentValue = null;

                /** @var bool $doSet */
                $doSet = true;

                if (is_array($argumentConfiguration)) {
                    if (isset($argumentConfiguration['id'])) {
                        $argumentValue = $this->container->get($argumentConfiguration['id']);
                    }

                } else {
                    if ($argumentConfiguration[0] === '@') {
                        $argumentValue = $this->container->get(substr($argumentConfiguration, 1));

                    } elseif ($argumentConfiguration[0] === '$') {
                        $argumentValue = $request->get(substr($argumentConfiguration, 1));
                    }
                }

                if ($parameterReflection->hasType()) {
                    /** @var ReflectionType $parameterType */
                    $parameterType = $parameterReflection->getType();

                    /** @var string $parameterTypeName */
                    $parameterTypeName = $parameterType->getName();

                    if (class_exists($parameterTypeName)) {
                        $argumentValue = $this->entityRepository->findEntity($parameterTypeName, $argumentValue);
                        # TODO: error handling "not an entty", "entity not found", ...
                    }
                }

                if ($doSet) {
                    $callArguments[$index] = $argumentValue;
                }

            } elseif (!is_null($requestValue)) {
                $callArguments[$index] = $requestValue;
            }
        }

        return $callArguments;
    }

}
