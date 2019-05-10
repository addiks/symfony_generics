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
use Addiks\SymfonyGenerics\Arguments\RequestArgument;
use Symfony\Component\HttpFoundation\RequestStack;
use Webmozart\Assert\Assert;

final class RequestArgumentFactory implements ArgumentFactory
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
        return 1 === preg_match('/^\$[a-zA-Z0-9_]+$/is', $source);
    }

    public function understandsArray(array $source): bool
    {
        return isset($source['key']) && isset($source['type']) && $source['type'] === 'request';
    }

    public function createArgumentFromString(string $source): Argument
    {
        Assert::true($this->understandsString($source));

        /** @var string $key */
        $key = substr($source, 1);

        return new RequestArgument($this->requestStack, $key);
    }

    public function createArgumentFromArray(array $source): Argument
    {
        Assert::keyExists($source, 'key');

        return new RequestArgument($this->requestStack, $source['key']);
    }

}
