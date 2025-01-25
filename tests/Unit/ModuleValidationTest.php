<?php
namespace PrestaShop\Module\RlAimodulemaker\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PrestaShop\Module\RlAimodulemaker\ModuleBuilder\ValidationHandler;

class ModuleValidationTest extends TestCase
{
    private $validator;

    protected function setUp(): void
    {
        if (!function_exists('pSQL')) {
            function pSQL($string) {
                return addslashes($string);
            }
        }
        $this->validator = new ValidationHandler();
    }

    public function testModuleNameValidation()
    {
        $this->assertTrue($this->validator->validateModuleName('mymodule'));
        $this->assertTrue($this->validator->validateModuleName('my_module_123'));
        $this->assertFalse($this->validator->validateModuleName('123module'));
        $this->assertFalse($this->validator->validateModuleName('My-Module'));
    }

    public function testVersionValidation()
    {
        $this->assertTrue($this->validator->validateVersion('1.0.0'));
        $this->assertTrue($this->validator->validateVersion('2.1.3'));
        $this->assertFalse($this->validator->validateVersion('1.0'));
        $this->assertFalse($this->validator->validateVersion('1.0.0.0'));
    }

    public function testSecurityValidation()
    {
        $code = 'SELECT * FROM table WHERE id = ' . $_GET['id'];
        $issues = $this->validator->validateSecurity($code);
        $this->assertNotEmpty($issues);

        $id = 1;
        $code = 'SELECT * FROM table WHERE id = ' . pSQL($id);
        $issues = $this->validator->validateSecurity($code);
        $this->assertEmpty($issues);
    }

    public function testPrestashopCompatibility()
    {
        $code = 'class MyModule extends Module { }';
        $issues = $this->validator->validateCompatibility($code);
        $this->assertNotEmpty($issues);

        $code = 'if (!defined("_PS_VERSION_")) exit;
                class MyModule extends Module { }';
        $issues = $this->validator->validateCompatibility($code);
        $this->assertEmpty($issues);
    }
}