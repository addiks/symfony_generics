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
use Addiks\SymfonyGenerics\Arguments\RequestFileArgument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\RequestStack;
use InvalidArgumentException;

final class RequestFileArgumentTest extends TestCase
{

    /**
     * @test
     */
    public function shouldResolveRequestFileArgumentToObject()
    {
        /** @var UploadedFile $file */
        $file = $this->createMock(UploadedFile::class);
        $file->method('getPathname')->willReturn("data://,some-data-in-file");

        /** @var Request $request */
        $request = $this->createMock(Request::class);
        $request->files = $this->createMock(FileBag::class);
        $request->files->expects($this->once())->method('get')->with(
            $this->equalTo("some-key")
        )->willReturn($file);

        /** @var RequestStack $requestStack */
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method("getCurrentRequest")->willReturn($request);

        $argument = new RequestFileArgument($requestStack, "some-key", "object");

        /** @var mixed $actualResult */
        $actualResult = $argument->resolve();

        $this->assertSame($file, $actualResult);
    }

    /**
     * @test
     */
    public function shouldResolveRequestFileArgumentToOriginalname()
    {
        /** @var UploadedFile $file */
        $file = $this->createMock(UploadedFile::class);
        $file->method('getPathname')->willReturn("data://,some-data-in-file");
        $file->method('getClientOriginalName')->willReturn("some-original-name");

        /** @var Request $request */
        $request = $this->createMock(Request::class);
        $request->files = $this->createMock(FileBag::class);
        $request->files->expects($this->once())->method('get')->with(
            $this->equalTo("some-key")
        )->willReturn($file);

        /** @var RequestStack $requestStack */
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method("getCurrentRequest")->willReturn($request);

        $argument = new RequestFileArgument($requestStack, "some-key", "originalname");

        /** @var mixed $actualResult */
        $actualResult = $argument->resolve();

        $this->assertEquals("some-original-name", $actualResult);
    }

    /**
     * @test
     */
    public function shouldResolveRequestFileArgumentToFilename()
    {
        /** @var UploadedFile $file */
        $file = $this->createMock(UploadedFile::class);
        $file->method('getPathname')->willReturn("data://,some-data-in-file");
        $file->method('getFilename')->willReturn("some-file-name");

        /** @var Request $request */
        $request = $this->createMock(Request::class);
        $request->files = $this->createMock(FileBag::class);
        $request->files->expects($this->once())->method('get')->with(
            $this->equalTo("some-key")
        )->willReturn($file);

        /** @var RequestStack $requestStack */
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method("getCurrentRequest")->willReturn($request);

        $argument = new RequestFileArgument($requestStack, "some-key", "filename");

        /** @var mixed $actualResult */
        $actualResult = $argument->resolve();

        $this->assertEquals("some-file-name", $actualResult);
    }

    /**
     * @test
     */
    public function shouldResolveRequestFileArgumentToContent()
    {
        /** @var UploadedFile $file */
        $file = $this->createMock(UploadedFile::class);
        $file->method('getPathname')->willReturn("data://,some-data-in-file");

        /** @var Request $request */
        $request = $this->createMock(Request::class);
        $request->files = $this->createMock(FileBag::class);
        $request->files->expects($this->once())->method('get')->with(
            $this->equalTo("some-key")
        )->willReturn($file);

        /** @var RequestStack $requestStack */
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method("getCurrentRequest")->willReturn($request);

        $argument = new RequestFileArgument($requestStack, "some-key", "content");

        /** @var mixed $actualResult */
        $actualResult = $argument->resolve();

        $this->assertEquals("some-data-in-file", $actualResult);
    }

    /**
     * @test
     */
    public function shouldResolveRequestFileArgumentToMimeType()
    {
        /** @var UploadedFile $file */
        $file = $this->createMock(UploadedFile::class);
        $file->method('getPathname')->willReturn("data://,some-data-in-file");
        $file->method('getMimeType')->willReturn("text/some-mime-type");

        /** @var Request $request */
        $request = $this->createMock(Request::class);
        $request->files = $this->createMock(FileBag::class);
        $request->files->expects($this->once())->method('get')->with(
            $this->equalTo("some-key")
        )->willReturn($file);

        /** @var RequestStack $requestStack */
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method("getCurrentRequest")->willReturn($request);

        $argument = new RequestFileArgument($requestStack, "some-key", "mimetype");

        /** @var mixed $actualResult */
        $actualResult = $argument->resolve();

        $this->assertEquals("text/some-mime-type", $actualResult);
    }

    /**
     * @test
     */
    public function shouldRejectResolvingWithoutRequest()
    {
        $this->expectException(InvalidArgumentException::class);

        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getCurrentRequest')->willReturn(null);

        $argument = new RequestFileArgument($requestStack, "some-key", "content");
        $argument->resolve();
    }

}
