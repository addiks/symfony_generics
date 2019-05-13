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
use Addiks\SymfonyGenerics\Arguments\LiteralArgument;

final class LiteralArgumentFactory implements ArgumentFactory
{

    public function understandsString(string $source): bool
    {
        return true;
    }

    public function understandsArray(array $source): bool
    {
        return true;
    }

    public function createArgumentFromString(string $source): Argument
    {
        if (preg_match("/^\'(.*)\'$/is", $source, $matches) || preg_match("/^\"(.*)\"$/is", $source, $matches)) {
            $source = $matches[1];
        }

        return new LiteralArgument($source);
    }

    public function createArgumentFromArray(array $source): Argument
    {
        return new LiteralArgument($source);
    }

}
