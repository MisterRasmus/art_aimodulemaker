<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ModuleGenerator;
use FileGenerator;
use ValidationHandler;

class ModuleGeneratorTest extends TestCase
{
    private $generator;
    private $testModuleData;

    protected function setUp(): void
    {
        $this->testModuleData = [
            'name' => 'testmodule',
            'display_name' => 'Test Module',
            'version' => '1.0.0',
            'description' => 'Test module description',
            'author' => 'Ljustema Sverige AB',
            'type' => 'payment'
        ];

        $this->generator = new ModuleGenerator(
            $this->testModuleData,
            sys_get_temp_dir(),
            new FileGenerator(),
            new ValidationHandler()
        );
    }

    public function testGenerateModule()
    {
        $result = $this->generator->generateModule();
        $this->assertTrue($result);

        $modulePath = sys_get_temp_dir() . '/testmodule';
        $this->assertDirectoryExists($modulePath);
        $this->assertFileExists($modulePath . '/testmodule.php');
        $this->assertFileExists($modulePath . '/config.xml');
    }

    public function testGeneratePaymentModule()
    {
        $result = $this->generator->generateModule();
        $this->assertTrue($result);

        $modulePath = sys_get_temp_dir() . '/testmodule';
        $requiredFiles = [
            '/controllers/front/payment.php',
            '/controllers/front/validation.php',
            '/views/templates/front/payment.tpl',
            '/views/templates/hook/payment.tpl'
        ];

        foreach ($requiredFiles as $file) {
            $this->assertFileExists($modulePath . $file);
        }
    }

    public function testGenerateReadme()
    {
        $result = $this->generator->generateModule();
        $this->assertTrue($result);

        $readmePath = sys_get_temp_dir() . '/testmodule/README.md';
        $this->assertFileExists($readmePath);

        $content = file_get_contents($readmePath);
        $this->assertStringContainsString('Test Module', $content);
        $this->assertStringContainsString('1.0.0', $content);
        $this->assertStringContainsString('Ljustema Sverige AB', $content);
    }

    public function testGenerateGitHubFiles()
    {
        $this->testModuleData['create_github_repo'] = true;
        $result = $this->generator->generateModule();
        $this->assertTrue($result);

        $modulePath = sys_get_temp_dir() . '/testmodule';
        $this->assertFileExists($modulePath . '/.gitignore');
        $this->assertFileExists($modulePath . '/.github/workflows/tests.yml');
    }

    protected function tearDown(): void
    {
        $modulePath = sys_get_temp_dir() . '/testmodule';
        if (is_dir($modulePath)) {
            $this->removeDirectory($modulePath);
        }
    }

    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }
}