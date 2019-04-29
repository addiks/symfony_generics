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
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use Webmozart\Assert\Assert;

final class RequestArgument implements ArgumentInterface
{

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var string
     */
    private $key;

    public function __construct(
        RequestStack $requestStack,
        string $key
    ) {
        $this->requestStack = $requestStack;
        $this->key = $key;
    }

    public function getValue()
    {
        /** @var Request $request */
        $request = $this->requestStack->getCurrentRequest();

        Assert::isInstanceOf(
            $request,
            Request::class,
            "Cannot resolve request-argument without active request!"
        );

        return $request->get($this->key);
    }

}
