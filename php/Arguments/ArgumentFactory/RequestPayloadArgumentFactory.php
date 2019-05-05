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
use Addiks\SymfonyGenerics\Arguments\RequestPayloadArgument;
use Symfony\Component\HttpFoundation\RequestStack;
use Webmozart\Assert\Assert;

final class RequestPayloadArgumentFactory implements ArgumentFactory
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
        return $source === '$';
    }

    public function understandsArray(array $source): bool
    {
        return isset($source['type']) && $source['type'] === 'request-payload';
    }

    public function createArgumentFromString(string $source): Argument
    {
        Assert::eq('$', $source);

        return new RequestPayloadArgument($this->requestStack);
    }

    public function createArgumentFromArray(array $source): Argument
    {
        return new RequestPayloadArgument($this->requestStack);
    }

}
