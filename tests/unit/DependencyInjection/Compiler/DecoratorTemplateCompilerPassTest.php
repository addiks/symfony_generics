<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks;

use PHPUnit\Framework\TestCase;
use Addiks\SymfonyGenerics\DependencyInjection\Compiler\DecoratorTemplateCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use InvalidArgumentException;

final class BarTest extends TestCase
{

    /**
     * @test
     */
    public function shouldApplyDecoratorTemplate()
    {
        /** @var Reference $oldReference */
        $oldReference = $this->createMock(Reference::class);
        $oldReference->method('__toString')->willReturn('some-string-representation');
        $oldReference->method('getInvalidBehavior')->willReturn(1);

        /** @var Definition $decoratorDefinition */
        $decoratorDefinition = $this->createMock(Definition::class);
        $decoratorDefinition->method('getTags')->willReturn([
            'some.tag.name' => 'foo'
        ]);
        $decoratorDefinition->expects($this->once())->method('setTags')->with([
        ]);
        $decoratorDefinition->method('getArguments')->willReturn([
            'foo',
            ['bar', 123, '%some_param%'],
            ['abc' => ['def' => $oldReference]],
            new Reference('some-decorator-service-id.inner', 1)
        ]);
        $decoratorDefinition->expects($this->once())->method('setArguments')->with([
            'foo',
            ['bar', 123, 'some-resolved'],
            ['abc' => ['def' => new Reference('some-string-representation', 1)]],
            new Reference('decorated_b55aec8c54b06f8899468dfbe954991d.inner', 1)
        ]);
        $decoratorDefinition->expects($this->once())->method('setDecoratedService')->with(
            $this->equalTo('some-decorated-service-id')
        );

        /** @var ContainerBuilder $container */
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())->method('findTaggedServiceIds')->with(
            $this->equalTo("some.tag.name")
        )->willReturn([
            'some-decorator-service-id' => [
                ['decorates' => 'some-decorated-service-id', 'some_param' => 'some-resolved']
            ]
        ]);
        $container->expects($this->once())->method('getDefinition')->with(
            $this->equalTo('some-decorator-service-id')
        )->willReturn($decoratorDefinition);
        $container->expects($this->once())->method('setDefinition')->with(
            $this->equalTo("decorated_b55aec8c54b06f8899468dfbe954991d"),
            $this->equalTo($decoratorDefinition)
        );
        $container->expects($this->once())->method('removeDefinition')->with(
            $this->equalTo("some-decorator-service-id")
        );

        $compilerPass = new DecoratorTemplateCompilerPass("some.tag.name");
        $compilerPass->process($container);
    }

    /**
     * @test
     */
    public function shouldExpectDecoratesKeyInTags()
    {
        $this->expectException(InvalidArgumentException::class);

        /** @var ContainerBuilder $container */
        $container = $this->createMock(ContainerBuilder::class);
        $container->expects($this->once())->method('findTaggedServiceIds')->with(
            $this->equalTo("some.tag.name")
        )->willReturn([
            'some-decorator-service-id' => [
                ['some_param' => 'some-resolved']
            ]
        ]);

        $compilerPass = new DecoratorTemplateCompilerPass("some.tag.name");
        $compilerPass->process($container);
    }

}
