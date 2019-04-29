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

use Addiks\SymfonyGenerics\Arguments\ArgumentInterface;
use Closure;
use Webmozart\Assert\Assert;

final class ArgumentCall implements ArgumentInterface
{

    /**
     * @var ArgumentInterface
     */
    private $callee;

    /**
     * @var string
     */
    private $methodName;

    /**
     * @var array<ArgumentInterface>
     */
    private $arguments = array();

    public function __construct(
        ArgumentInterface $callee,
        string $methodName,
        array $arguments
    ) {
        $this->callee = $callee;
        $this->methodName = $methodName;
        $this->arguments = array_map(function (ArgumentInterface $argument): ArgumentInterface {
            return $argument;
        }, $arguments);
    }

    public function getValue()
    {
        /** @var Closure $callee */
        $callee = $this->callee->getValue();
        Assert::isCallable($callee);

        /** @var array<mixed> $arguments */
        $arguments = array_map(
            /** @return mixed */
            function (ArgumentInterface $argument) {
                return $argument->getValue();
            },
            $this->arguments
        );

        return call_user_func([$callee, $this->methodName], $arguments);
    }

}
