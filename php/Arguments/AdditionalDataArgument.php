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

use Addiks\SymfonyGenerics\Arguments\Argument;
use Addiks\SymfonyGenerics\Arguments\ArgumentContextInterface;

final class AdditionalDataArgument implements Argument
{

    /**
     * @var string
     */
    private $key;

    /**
     * @var ArgumentContextInterface
     */
    private $context;

    public function __construct(
        string $key,
        ArgumentContextInterface $context
    ) {
        $this->key = $key;
        $this->context = $context;
    }

    public function resolve()
    {
        return $this->context->get($this->key);
    }

}
