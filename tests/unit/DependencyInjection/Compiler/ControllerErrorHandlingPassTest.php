<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Tests\Unit\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Addiks\SymfonyGenerics\DependencyInjection\Compiler\ControllerErrorHandlingPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use InvalidArgumentException;

final class ControllerErrorHandlingPassTest extends TestCase
{

    /**
     * @test
     */
    public function shouldProcessErrorHandlingServiceDefinition()
    {
        /** @var Definition $errorHandlerDefinition */
        $errorHandlerDefinition = $this->createMock(Definition::class);
        $errorHandlerDefinition->expects($this->once())->method('addTag')->with(
            $this->equalTo('symfony_generics.decorates'),
            $this->equalTo(['abc' => 'def', 'foo' => 'bar', 'decorates' => 'some-controller-id'])
        );

        /** @var ContainerBuilder $container */
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->exactly(2))->method('findTaggedServiceIds')->will($this->returnValueMap([
            [
                'symfony_generics.error_handler',
                false,
                ['some-errorhandler-id' => [['key' => 'some-key', 'abc' => 'def']]],
            ],
            [
                'symfony_generics.error_handler.some-key',
                false,
                ['some-controller-id' => [['foo' => 'bar']]],
            ]
        ]));
        $container->expects($this->once())->method('getDefinition')->with(
            $this->equalTo('some-errorhandler-id')
        )->willReturn($errorHandlerDefinition);

        $compilerPass = new ControllerErrorHandlingPass();
        $compilerPass->process($container);
    }

    /**
     * @test
     */
    public function shouldRejectErrorHandlerWithoutKey()
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var ContainerBuilder $container */
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())->method('findTaggedServiceIds')->will($this->returnValueMap([
            [
                'symfony_generics.error_handler',
                false,
                ['some-errorhandler-id' => [['abc' => 'def']]],
            ],
        ]));

        $compilerPass = new ControllerErrorHandlingPass();
        $compilerPass->process($container);
    }

}
