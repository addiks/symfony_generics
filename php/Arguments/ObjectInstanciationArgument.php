<?php
/**
 * Copyright (C) 2019 Gerrit Addiks.
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
use Webmozart\Assert\Assert;
use ReflectionClass;

final class ObjectInstanciationArgument implements Argument
{

    /** @var Argument */
    private $objectClass;

    /** @var array<Argument> */
    private $arguments;

    /** @param array<Argument> $arguments */
    public function __construct(
        Argument $objectClass,
        array $arguments = array()
    ) {
        $this->objectClass = $objectClass;
        $this->arguments = array_map(function (Argument $argument): Argument {
            return $argument;
        }, $arguments);
    }

    public function resolve()
    {
        /** @var string $objectClass */
        $objectClass = (string)$this->objectClass->resolve();

        Assert::classExists($objectClass);

        $reflection = new ReflectionClass($objectClass);

        /** @var array<mixed> $arguments */
        $arguments = array_map(function (Argument $argument) {
            return $argument->resolve();
        }, $this->arguments);

        /** @var object $object */
        $object = $reflection->newInstanceArgs($arguments);

        return $object;
    }

}
