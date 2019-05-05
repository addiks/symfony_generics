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
use Addiks\SymfonyGenerics\Arguments\AdditionalDataArgument;
use Webmozart\Assert\Assert;
use Addiks\SymfonyGenerics\Arguments\ArgumentContextInterface;

final class AdditionalDataArgumentFactory implements ArgumentFactory
{

    /**
     * @var ArgumentContextInterface
     */
    private $context;

    public function __construct(ArgumentContextInterface $context)
    {
        $this->context = $context;
    }

    public function understandsString(string $source): bool
    {
        return strpos($source, '%') === 0 && strlen($source) > 1;
    }

    public function understandsArray(array $source): bool
    {
        return isset($source['key']) && isset($source['type']) && $source['type'] === 'additional-data';
    }

    public function createArgumentFromString(string $source): Argument
    {
        Assert::startsWith($source, '%');

        /** @var string $key */
        $key = substr($source, 1);

        return new AdditionalDataArgument($key, $this->context);
    }

    public function createArgumentFromArray(array $source): Argument
    {
        Assert::keyExists($source, 'key');

        return new AdditionalDataArgument($source['key'], $this->context);
    }

}
