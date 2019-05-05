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
use Addiks\SymfonyGenerics\Arguments\RequestFileArgument;
use Symfony\Component\HttpFoundation\RequestStack;
use Webmozart\Assert\Assert;

final class RequestFileArgumentFactory implements ArgumentFactory
{

    /**
     * @var RequestStack
     */
    private $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function understandsString(string $source): bool
    {
        return strpos($source, '$files.') === 0 && strlen($source) > 7;
    }

    public function understandsArray(array $source): bool
    {
        return isset($source['key']) && isset($source['type']) && $source['type'] === 'request-file';
    }

    public function createArgumentFromString(string $source): Argument
    {
        Assert::startsWith($source, '$files.');

        [, $key, $property] = explode(".", $source);

        return new RequestFileArgument($this->requestStack, $key, $property);
    }

    public function createArgumentFromArray(array $source): Argument
    {
        Assert::keyExists($source, 'key');

        /** @var string $property */
        $property = 'content';

        if (isset($source['property'])) {
            $property = $source['property'];
        }

        return new RequestFileArgument($this->requestStack, $source['key'], $property);
    }

}
