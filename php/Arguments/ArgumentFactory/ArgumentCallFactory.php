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

namespace Addiks\SymfonyGenerics\Arguments\ArgumentFactory;

use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\ArgumentFactory;
use Addiks\SymfonyGenerics\Arguments\Argument;
use Addiks\SymfonyGenerics\Arguments\ArgumentCall;
use Webmozart\Assert\Assert;
use Addiks\SymfonyGenerics\Services\ArgumentCompilerInterface;
use Addiks\SymfonyGenerics\Arguments\NullArgument;
use Addiks\SymfonyGenerics\Arguments\ObjectInstanciationArgument;

final class ArgumentCallFactory implements ArgumentFactory
{

    /**
     * @var ArgumentCompilerInterface
     */
    private $argumentCompiler;

    /**
     * @var ArgumentFactory
     */
    private $argumentFactory;

    public function __construct(
        ArgumentCompilerInterface $argumentCompiler,
        ArgumentFactory $argumentFactory
    ) {
        $this->argumentCompiler = $argumentCompiler;
        $this->argumentFactory = $argumentFactory;
    }

    public function understandsString(string $source): bool
    {
        return 1 === preg_match("/^[^\:\>]+(\:\:|\-\>)[a-zA-Z0-9_-]+/is", $source);
    }

    public function understandsArray(array $source): bool
    {
        return isset($source['method']) && isset($source['callee']);
    }

    public function createArgumentFromString(string $source): Argument
    {
        Assert::true($this->understandsString($source));

        /** @var array<Argument> $arguments */
        $arguments = array();

        $source = str_replace('->', '::', $source);
        
        /** @var int|false $operatorPosition */
        $operatorPosition = strrpos($source, '::');

        /** @var int|false $argumentsPosition */
        $argumentsPosition = strrpos($source, '(');

        /** @var string $sourceWithoutArguments */
        $sourceWithoutArguments = $source;

        if (is_int($argumentsPosition) && $argumentsPosition > $operatorPosition) {
            /** @var string $argumentsSources */
            $argumentsSources = substr($source, $argumentsPosition + 1);

            /** @var int|false $argumentsEndPosition */
            $argumentsEndPosition = strpos($argumentsSources, ')');

            if ($argumentsEndPosition === false) {
                $argumentsSources = str_replace(')', '', $argumentsSources);

            } else {
                $argumentsSources = substr($argumentsSources, 0, $argumentsEndPosition);
            }

            if (!empty($argumentsSources)) {
                foreach (explode(',', $argumentsSources) as $argumentsSource) {
                    $arguments[] = $this->argumentFactory->createArgumentFromString(trim($argumentsSource));
                }
            }

            $sourceWithoutArguments = substr($source, 0, $argumentsPosition);
        }

        /** @var array<string> $sourceParts */
        $sourceParts = explode('::', $sourceWithoutArguments);

        $methodName = array_pop($sourceParts);

        $calleeSource = implode('::', $sourceParts);

        /** @var Argument $callee */
        $callee = $this->argumentFactory->createArgumentFromString($calleeSource);

        return new ArgumentCall($this->argumentCompiler, $callee, $methodName, $arguments);
    }

    public function createArgumentFromArray(array $source): Argument
    {
        Assert::keyExists($source, 'method');
        Assert::keyExists($source, 'callee');

        /** @var Argument $callee */
        $callee = null;

        if (is_array($source['callee'])) {
            $callee = $this->argumentFactory->createArgumentFromArray($source['callee']);

        } else {
            $callee = $this->argumentFactory->createArgumentFromString($source['callee']);
        }

        /** @var array<Argument> $arguments */
        $arguments = array();

        if (isset($source['arguments'])) {
            foreach ($source['arguments'] as $argumentsSource) {
                /** @var array|string $argumentsSource */

                if (is_array($argumentsSource)) {
                    $arguments[] = $this->argumentFactory->createArgumentFromArray($argumentsSource);

                } elseif (is_null($argumentsSource)) {
                    $arguments[] = new NullArgument();

                } else {
                    $arguments[] = $this->argumentFactory->createArgumentFromString($argumentsSource);
                }
            }
        }

        /** @var string $methodName */
        $methodName = $source['method'];

        if ($methodName === '__construct') {
            return new ObjectInstanciationArgument($callee, $arguments);

        } else {
            return new ArgumentCall($this->argumentCompiler, $callee, $source['method'], $arguments);
        }
    }

}
