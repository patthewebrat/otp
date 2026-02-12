<?php

namespace Tests\Unit;

use App\Http\Controllers\SharedFileController;
use ReflectionMethod;
use Tests\TestCase;

class SharedFileHelperTest extends TestCase
{
    private SharedFileController $controller;

    private ReflectionMethod $returnBytes;

    private ReflectionMethod $formatBytes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new SharedFileController;

        $this->returnBytes = new ReflectionMethod($this->controller, 'returnBytes');
        $this->formatBytes = new ReflectionMethod($this->controller, 'formatBytes');
    }

    // --- returnBytes tests ---

    public function test_return_bytes_parses_kilobytes(): void
    {
        $this->assertEquals(2048, $this->returnBytes->invoke($this->controller, '2K'));
    }

    public function test_return_bytes_parses_megabytes(): void
    {
        $this->assertEquals(2 * 1024 * 1024, $this->returnBytes->invoke($this->controller, '2M'));
    }

    public function test_return_bytes_parses_gigabytes(): void
    {
        $this->assertEquals(1024 * 1024 * 1024, $this->returnBytes->invoke($this->controller, '1G'));
    }

    public function test_return_bytes_parses_plain_bytes(): void
    {
        $this->assertEquals(512, $this->returnBytes->invoke($this->controller, '512'));
    }

    public function test_return_bytes_handles_lowercase_suffix(): void
    {
        $this->assertEquals(2048, $this->returnBytes->invoke($this->controller, '2k'));
    }

    // --- formatBytes tests ---

    public function test_format_bytes_displays_bytes(): void
    {
        $this->assertEquals('100 B', $this->formatBytes->invoke($this->controller, 100));
    }

    public function test_format_bytes_displays_kilobytes(): void
    {
        $this->assertEquals('1 KB', $this->formatBytes->invoke($this->controller, 1024));
    }

    public function test_format_bytes_displays_megabytes(): void
    {
        $this->assertEquals('1 MB', $this->formatBytes->invoke($this->controller, 1024 * 1024));
    }

    public function test_format_bytes_displays_gigabytes(): void
    {
        $this->assertEquals('1 GB', $this->formatBytes->invoke($this->controller, 1024 * 1024 * 1024));
    }

    public function test_format_bytes_handles_zero(): void
    {
        $this->assertEquals('0 B', $this->formatBytes->invoke($this->controller, 0));
    }

    public function test_format_bytes_respects_precision(): void
    {
        $this->assertEquals('1.5 KB', $this->formatBytes->invoke($this->controller, 1536, 1));
    }
}
