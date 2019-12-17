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

namespace Addiks\SymfonyGenerics\Arguments;

use Addiks\SymfonyGenerics\Arguments\Argument;
use Closure;
use Webmozart\Assert\Assert;
use ReflectionFunctionAbstract;
use ReflectionFunction;
use ReflectionObject;
use ReflectionClass;
use ReflectionParameter;
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;

final class ArgumentCall implements Argument
{

    /**
     * @var Argument
     */
    private $callee;

    /**
     * @var string
     */
    private $methodName;

    /**
     * @var array<Argument>
     */
    private $arguments = array();

    /**
     * @var ArgumentCompilerInterface
     */
    private $argumentCompiler;

    public function __construct(
        ArgumentCompilerInterface $argumentCompiler,
        Argument $callee,
        string $methodName,
        array $arguments
    ) {
        $this->argumentCompiler = $argumentCompiler;
        $this->callee = $callee;
        $this->methodName = $methodName;
        $this->arguments = array_map(function (Argument $argument): Argument {
            return $argument;
        }, $arguments);
    }

    public function resolve()
    {
        /** @var object|string $callee */
        $callee = $this->callee->resolve();

        /** @var ReflectionFunctionAbstract|null $methodReflection */
        $methodReflection = null;

        if (is_string($callee)) {
            Assert::classExists($callee);

            $reflectionClass = new ReflectionClass($callee);

            if ($reflectionClass->hasMethod($this->methodName)) {
                $methodReflection = $reflectionClass->getMethod($this->methodName);
            }

        } else {
            $reflectionObject = new ReflectionObject($callee);

            if ($reflectionObject->hasMethod($this->methodName)) {
                $methodReflection = $reflectionObject->getMethod($this->methodName);
            }
        }

        /** @var array<mixed> $argumentsConfiguration */
        $argumentsConfiguration = array_map(
            /** @return mixed */
            function (Argument $argument) {
                return $argument->resolve();
            },
            $this->arguments
        );

        /** @var callable $callback */
        $callback = [$callee, $this->methodName];

        /** @var array<mixed> $arguments */
        $arguments = $argumentsConfiguration;

        if (!is_null($methodReflection)) {
            $arguments = $this->argumentCompiler->buildCallArguments(
                $methodReflection,
                $argumentsConfiguration
            );
        }

        return call_user_func_array($callback, $arguments);
    }

}
