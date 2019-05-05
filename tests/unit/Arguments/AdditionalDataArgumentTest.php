<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Tests\Unit\Arguments;

use PHPUnit\Framework\TestCase;
use Addiks\SymfonyGenerics\Arguments\AdditionalDataArgument;
use Addiks\SymfonyGenerics\Arguments\ArgumentContextInterface;

final class AdditionalDataArgumentTest extends TestCase
{

    /**
     * @test
     */
    public function shouldResolveAdditionalDataArgument()
    {
        $context = $this->createMock(ArgumentContextInterface::class);
        $context->method('get')->willReturn(12345);
        $argument = new AdditionalDataArgument("some-key", $context);
        $this->assertEquals(12345, $argument->resolve());
    }

}
