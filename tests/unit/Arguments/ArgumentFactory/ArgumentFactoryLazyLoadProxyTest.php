<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Arguments\ArgumentFactory;

use PHPUnit\Framework\TestCase;
use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\ArgumentFactoryLazyLoadProxy;
use Addiks\SymfonyGenerics\Arguments\ArgumentFactory\ArgumentFactory;
use Psr\Container\ContainerInterface;
use Addiks\SymfonyGenerics\Arguments\Argument;
use InvalidArgumentException;

final class ArgumentFactoryLazyLoadProxyTest extends TestCase
{

    /**
     * @var ArgumentFactoryLazyLoadProxy
     */
    private $factory;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ArgumentFactory
     */
    private $loadedArgumentFactory;

    public function setUp()
    {
        $this->container = $this->createMock(ContainerInterface::class);
        $this->loadedArgumentFactory = $this->createMock(ArgumentFactory::class);

        $this->factory = new ArgumentFactoryLazyLoadProxy($this->container, 'some-service-id');
    }

    /**
     * @test
     * @dataProvider dataProviderShouldAskInnerFactoryIfUnderstandsString
     */
    public function shouldAskInnerFactoryIfUnderstandsString(bool $expectedResult, string $source)
    {
        $this->container->expects($this->once())->method('get')->with(
            $this->equalTo('some-service-id')
        )->willReturn($this->loadedArgumentFactory);

        $this->loadedArgumentFactory->expects($this->once())->method('understandsString')->with(
            $this->equalTo($source)
        )->willReturn($expectedResult);

        /** @var bool $actualResult */
        $actualResult = $this->factory->understandsString($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderShouldAskInnerFactoryIfUnderstandsString()
    {
        return array(
            [true, 'foo'],
            [false, 'foo'],
        );
    }

    /**
     * @test
     * @dataProvider dataProviderShouldAskInnerFactoryIfUnderstandsArray
     */
    public function shouldAskInnerFactoryIfUnderstandsArray(bool $expectedResult, array $source)
    {
        $this->container->expects($this->once())->method('get')->with(
            $this->equalTo('some-service-id')
        )->willReturn($this->loadedArgumentFactory);

        $this->loadedArgumentFactory->expects($this->once())->method('understandsArray')->with(
            $this->equalTo($source)
        )->willReturn($expectedResult);

        /** @var bool $actualResult */
        $actualResult = $this->factory->understandsArray($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderShouldAskInnerFactoryIfUnderstandsArray()
    {
        return array(
            [true, ['foo']],
            [false, ['foo']],
        );
    }

    /**
     * @test
     * @dataProvider dataProviderShouldCreateArgumentFromStringUsingInnerFactory
     */
    public function shouldCreateArgumentFromStringUsingInnerFactory(Argument $expectedResult, string $source)
    {
        $this->container->expects($this->once())->method('get')->with(
            $this->equalTo('some-service-id')
        )->willReturn($this->loadedArgumentFactory);

        $this->loadedArgumentFactory->expects($this->once())->method('createArgumentFromString')->with(
            $this->equalTo($source)
        )->willReturn($expectedResult);

        /** @var mixed $actualResult */
        $actualResult = $this->factory->createArgumentFromString($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderShouldCreateArgumentFromStringUsingInnerFactory()
    {
        return array(
            [$this->createMock(Argument::class), 'foo'],
            [$this->createMock(Argument::class), 'foo'],
        );
    }

    /**
     * @test
     * @dataProvider dataProviderShouldCreateArgumentFromArrayUsingInnerFactory
     */
    public function shouldCreateArgumentFromArrayUsingInnerFactory(Argument $expectedResult, array $source)
    {
        $this->container->expects($this->once())->method('get')->with(
            $this->equalTo('some-service-id')
        )->willReturn($this->loadedArgumentFactory);

        $this->loadedArgumentFactory->expects($this->once())->method('createArgumentFromArray')->with(
            $this->equalTo($source)
        )->willReturn($expectedResult);

        /** @var mixed $actualResult */
        $actualResult = $this->factory->createArgumentFromArray($source);

        $this->assertEquals($expectedResult, $actualResult);
    }

    public function dataProviderShouldCreateArgumentFromArrayUsingInnerFactory()
    {
        return array(
            [$this->createMock(Argument::class), ['foo']],
            [$this->createMock(Argument::class), ['foo']],
        );
    }

    /**
     * @test
     */
    public function shouldRejectInvalidService()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            "Expected service 'some-service-id' to be instance of '%s'!",
            ArgumentFactory::class
        ));

        $container = $this->createMock(ContainerInterface::class);
        $container->expects($this->once())->method('get')->with(
            $this->equalTo('some-service-id')
        )->willReturn(null);

        $factory = new ArgumentFactoryLazyLoadProxy($container, 'some-service-id');
        $factory->createArgumentFromArray([]);
    }

}
