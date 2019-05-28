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

namespace Addiks\SymfonyGenerics\Tests\Unit\Controllers;

class SampleEntity
{

    /**
     * @var bool
     */
    public $fooCalled = false;

    public $constructArgument = "foo";

    public function __construct(string $foo = "foo")
    {
        $this->constructArgument = $foo;
    }

    public static function someStaticFactory(string $foo = "foo"): SampleEntity
    {
        return new SampleEntity("static " . $foo);
    }

    public function doFoo()
    {
        $this->fooCalled = true;
    }

    public function getId()
    {
        return 'some_id';
    }

}
