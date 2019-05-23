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
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\RequestStack;
use Addiks\SymfonyGenerics\Tests\Integration\Arguments\ServiceSample;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;

require_once(__DIR__ . '/ServiceSample.php');

final class ArgumentCompilerTest extends TestCase
{

    /**
     * @var ArgumentCompiler
     */
    private $compiler;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var XmlFileLoader
     */
    private $loader;

    /**
     * @var EntityManager
     */
    private $entityManager;

    public function setUp()
    {
        $this->container = new ContainerBuilder();

        $this->loader = new XmlFileLoader(
            $this->container,
            new FileLocator(__DIR__ . '/../../..')
        );

        $this->loader->load('services.xml');

        $this->entityManager = $this->createMock(EntityManager::class);

        $this->container->set('doctrine.orm.entity_manager', $this->entityManager);
        $this->container->set('request_stack', new RequestStack());

        $this->compiler = $this->container->get('symfony_generics.argument_compiler');
    }

    /**
     * @test
     * @dataProvider dataProviderForShouldBuildArguments
     */
    public function shouldBuildArguments($expectedResult, $argumentsConfiguration)
    {
        /** @var Request $request */
        $request = $this->createMock(Request::class);
        $request->method("getContent")->willReturn("request-content");
        $request->method("get")->will($this->returnValueMap([
            ['foo', null, 'foo-result'],
        ]));

        /** @var UploadedFile $file */
        $file = $this->createMock(UploadedFile::class);
        $file->method('getPathname')->willReturn('data://,some-file-content');
        $file->method('getClientOriginalName')->willReturn('some-original-file-name');
        $file->method('getFilename')->willReturn('some-file-name');
        $file->method('getMimeType')->willReturn('some/mime-type');

        $request->files = $this->createMock(FileBag::class);
        $request->files->expects($this->any())->method('get')->with($this->equalTo('foo'))->willReturn($file);

        $someService = new ServiceSample();

        $this->entityManager->method('find')->will($this->returnValueMap([
            ['Foo\\Bar\\Baz', 'foo-result', null, null, $someService],
            ['Foo\\Bar\\Baz', '123', null, null, $someService],
        ]));

        $this->container->get('request_stack')->push($request);

        $this->container->set('some.service', $someService);
        $this->container->set('some_service', $someService);

        /** @var array $additionalData */
        $additionalData = [
            'some_additional_argument' => 'addArgRes',
            'some_argument_service' => $someService
        ];

        /** @var mixed $actualResult */
        $actualResult = $this->compiler->buildArguments([$argumentsConfiguration], $request, $additionalData);

        $this->assertEquals($expectedResult, $actualResult[0]);
    }

    public function dataProviderForShouldBuildArguments()
    {
        return array(
            [null, ""],
            [true, "true"],
            [false, "false"],
            [null, "null"],
            ["literal", "literal"],
            ["literal", "'literal'"],
            ["literal", '"literal"'],
            ["request-content", '$'],
            ["foo-result", '$foo'],
            ["some-file-content", '$files.foo'],
            [$this->createMock(UploadedFile::class), '$files.foo.object'],
            ["some-original-file-name", '$files.foo.originalname'],
            ["some-file-name", '$files.foo.filename'],
            ["some-file-content", '$files.foo.content'],
            ["some/mime-type", '$files.foo.mimetype'],
            [new ServiceSample(), 'Foo\\Bar\\Baz#123'],
            [new ServiceSample(), 'Foo\\Bar\\Baz#$foo'],
            ["#qwe#foo#", 'Foo\\Bar\\Baz#$foo::bar'],
            ["#baz#foo#", 'Foo\\Bar\\Baz#$foo::bar(baz)'],
            ["#qwe#foo#", '@some.service::bar'],
            ["#qwe#foo#", '  @some.service::bar  '],
            ["#qwe#foo#", "\n@some.service::bar\n"],
            ["#asd#baz#", '@some_service::bar(\'asd\', baz)'],
            ["addArgRes", '%some_additional_argument%'],
            ["#qwe#foo#", '%some_argument_service%::bar'],
        );
    }

}
