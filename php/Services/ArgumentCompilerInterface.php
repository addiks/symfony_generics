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

use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;
use ReflectionFunctionAbstract;

interface ArgumentCompilerInterface
{

    public function understandsArgumentString(string $argumentConfiguration): bool;

    /**
     * @param array|string $argument
     *
     * @return mixed
     */
    public function buildArgument(
        $argumentConfiguration,
        array $additionalData = array()
    );

    public function buildArguments(
        array $argumentsConfiguration,
        array $additionalData = array()
    ): array;

    public function buildCallArguments(
        ReflectionFunctionAbstract $methodReflection,
        array $argumentsConfiguration,
        array $predefinedArguments = array(),
        array $additionalData = array()
    ): array;

}
