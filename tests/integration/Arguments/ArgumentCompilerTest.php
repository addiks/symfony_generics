<?php
/**
 * Copyright (C) 2017  Gerrit Addiks.
 * This package (including this file) was released under the terms of the GPL-3.0.
 * You should have received a copy of the GNU General Public License along with this program.
 * If not, see <http://www.gnu.org/licenses/> or send me a mail so i can send you a copy.
 * @license GPL-3.0
 * @author Gerrit Addiks <gerrit@addiks.de>
 */

namespace Addiks\SymfonyGenerics\Tests\Integration\Arguments;

use PHPUnit\Framework\TestCase;
use Addiks\SymfonyGenerics\Services\ArgumentCompiler;
use Addiks\SymfonyGenerics\Services\NewArgumentCompiler;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;

final class ArgumentCompilerTest extends TestCase
{

    /**
     * @var ArgumentCompiler
     */
    private $oldCompiler;

    /**
     * @var NewArgumentCompiler
     */
    private $newCompiler;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var XmlFileLoader
     */
    private $loader;

    public function setUp()
    {
        $this->container = new ContainerBuilder();

        $this->loader = new XmlFileLoader(
            $this->container,
            new FileLocator(__DIR__ . '/../../..')
        );

        $this->loader->load('services.xml');

        $this->container->set('doctrine.orm.entity_manager', $this->createMock(EntityManager::class));
        $this->container->set('request_stack', new RequestStack());

        $this->oldCompiler = $this->container->get('symfony_generics.argument_compiler');
        $this->newCompiler = $this->container->get('symfony_generics.argument_compiler.new');
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldBuildArguments
     */
    public function shouldBuildArguments($expectedResult, string $argumentsConfiguration, array $additionalData)
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);
        $request->method("getContent")->willReturn("request-content");

        $this->container->get('request_stack')->push($request);

        /** @var mixed $actualOldResult */
        $actualOldResult = $this->oldCompiler->buildArguments([$argumentsConfiguration], $request, $additionalData);

        /** @var mixed $actualOldResult */
        $actualNewResult = $this->newCompiler->buildArguments([$argumentsConfiguration], $request, $additionalData);

        $this->assertEquals($expectedResult, $actualNewResult[0]);
        $this->assertEquals($expectedResult, $actualOldResult[0]);
    }

    public function dataProviderForShouldBuildArguments()
    {
        return array(
            [null, "", []],
            ["request-content", "$", []],
        );
    }

}
