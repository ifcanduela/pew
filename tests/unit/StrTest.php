<?php

use pew\libs\Str;

class TestStr extends PHPUnit_Framework_TestCase
{
    public function testConstruct()
    {
        $str = new Str('this is a string');

        $this->assertEquals('this is a string', $str->string);
        $this->assertEquals('this is a string', $str);
        $this->assertEquals('this_is_a_string', $str->underscores());
        $this->assertEquals('thisIsAString', $str->underscores()->camel_case(false));
        $this->assertEquals('ThisIsAString', $str->underscores()->camel_case());
        $this->assertEquals('This Is A String', $str->title_case(true));

        $str = new Str("äachen iß bæteç");
        $this->assertEquals('äachen iß bæteç', $str->string);
        $this->assertEquals('äachen iß bæteç', $str);
        $this->assertEquals('äachen_iß_bæteç', $str->underscores());
        $this->assertEquals('äachenIßBæteç', $str->underscores()->camel_case(false));
        $this->assertEquals('ÄachenIßBæteç', $str->underscores()->camel_case());
        $this->assertEquals('Äachen Iß Bæteç', $str->title_case());
    }

    public function testJoin()
    {    
        $this->assertEquals('C:/windows/system32/drivers/etc/hosts', Str::join('/', 'C:/', 'windows/', ' system32', 'drivers/', '/etc/', 'hosts'));
        $this->assertEquals('Äachen Ýnter Môndas', Str::join(' ', 'Äachen', 'Ýnter', 'Môndas'));
    }

    public function testSplit()
    {
        $this->assertEquals(['C:', 'windows', 'system32', 'drivers', 'etc', 'hosts'], Str::split('C:/windows/system32/drivers/etc/hosts', '/'));
        $this->assertEquals(['Äachen', 'Ýnter', 'Môndas'], Str::split('Äachen×Ýnter×Môndas', '×'));
    }

    public function testCamelCase()
    {
        $this->assertEquals('WindowsSystem32DriversEtcHosts', Str::camel_case('windows system32_drivers--etc hosts'));
        $this->assertEquals('windowsSystem32DriversEtcHosts', Str::camel_case('windows system32_drivers--etc hosts', false));
        $this->assertEquals('ÄachenÝnterMôndas', Str::camel_case('äachen ýnter Môndas'));
    }

    public function testSlug()
    {
        $this->assertEquals('c-windows-system32drivers-etc-hosts', Str::slug('C:/windows/ system32drivers//etc/hosts'));
        $this->assertEquals('aeachen-ynter-mondas', Str::slug('Äachen Ýnter Môndas'));
    }

    public function testUnderscores()
    {
        $this->assertEquals('this_is_an_example_of_underscorized_string', Str::underscores('This is an example of underscorized string'));
        $this->assertEquals('this_is_an_example_of_underscorized_class_name', Str::underscores('ThisIsAnExampleOfUnderscorizedClassName', true));
        $this->assertEquals('äachen_ýnter_môndas', Str::underscores('äachen ýnter Môndas'));
        $this->assertEquals('äachen_ýnter_môndas', Str::underscores('ÄachenÝnterMôndas', true));
    }

    public function testSafeAppend()
    {
        $this->assertEquals('Filename.txt', Str::safe_append('Filename.txt', '.txt'));
        $this->assertEquals('Filename.php', Str::safe_append('Filename', '.php'));
        $this->assertEquals('Filename.äý×', Str::safe_append('Filename.äý×', '.äý×'));
        $this->assertEquals('Filename.ô»ñ', Str::safe_append('Filename', '.ô»ñ'));
    }

    public function testSafePrepend()
    {
        $this->assertEquals('Mr. Brown', Str::safe_prepend('Mr. Brown', 'Mr. '));
        $this->assertEquals('Mr. Green', Str::safe_prepend('Green', 'Mr. '));
        $this->assertEquals('Señor Marrón', Str::safe_prepend('Marrón', 'Señor '));
        $this->assertEquals('Señora Añil', Str::safe_prepend('Señora Añil', 'Señ'));
    }

    public function testEndsWith()
    {
        $this->assertTrue(Str::ends_with('Filename.txt', '.txt'));
        $this->assertTrue(Str::ends_with('Filename.txt', ['.info', 'txt']));
        $this->assertFalse(Str::ends_with('Filename.txt', ['.php']));
        $this->assertFalse(Str::ends_with('Filename.jpeg', ['.php', 'txt']));
        $this->assertTrue(Str::ends_with('Filename.äý×', '.äý×'));
        $this->assertTrue(Str::ends_with('Filename.ô»ñ', ['.ô»ñ', 'äý×']));
        $this->assertFalse(Str::ends_with('Filename.äý×', ['.ô»ñ']));
        $this->assertFalse(Str::ends_with('Filename.jpeg', ['.ô»ñ', 'äý×']));
    }

    public function testBeginsWith()
    {
        $this->assertTrue(Str::begins_with('Mr. White', 'Mr'));
        $this->assertTrue(Str::begins_with('Mr. Green', ['Sr.', 'Mr.']));
        $this->assertFalse(Str::begins_with('Sr. White', 'Mr'));
        $this->assertFalse(Str::begins_with('Lord Green', ['Sr.', 'Mr.']));
        $this->assertTrue(Str::begins_with('Señor Rojo', 'Señor'));
        $this->assertTrue(Str::begins_with('Señora Añil', ['Señora', 'Señor']));
        $this->assertFalse(Str::begins_with('Señor Rojo', 'Doctor'));
        $this->assertFalse(Str::begins_with('Barón Añil', ['Señor ', 'Doctor ']));
    }

    public function testLastAndFirst()
    {
        $this->assertEquals('Green', Str::last('Mr. Green', 5));
        $this->assertEquals('M', Str::first('Mr. Green'));
        $this->assertEquals('ße', Str::last('Straße', 2));
        $this->assertEquals('Ý', Str::first('Ýnter'));
    }

    public function testUntilAndFrom()
    {
        $this->assertEquals('Mr. Gre', Str::until('Mr. Green', 'en'));
        $this->assertEquals('n', Str::from('Mr. Green', 'ee'));
        $this->assertEquals('Äachen ', Str::until('Äachen Ýnter Môndas', 'Ý'));
        $this->assertEquals('ndas', Str::from('Äachen Ýnter Môndas', 'ô'));
    }

    public function testSubstringAndCharAt()
    {
        $this->assertEquals('. ', Str::substring('Mr. Green', 2, 2));
        $this->assertEquals('Y', Str::char_at('Mr. Yellow', 4));

        $this->assertEquals('n Ýnt', Str::substring('Äachen Ýnter Môndas', 5, 5));
        $this->assertEquals('Ý', Str::char_at('Äachen Ýnter Môndas', 7));
    }

    public function testTransliterate()
    {
        $this->assertEquals('AEachen Ynter Mondas', Str::transliterate('Äachen Ýnter Môndas'));
        $this->assertEquals('znsch', Str::transliterate('žнЩ'));
    }

    public function testUpperCase()
    {
        $this->assertEquals('ÄACHEN ÝNTER MÔNDAS', Str::upper_case('Äachen Ýnter Môndas'));
    }

    public function testLowerCase($value='')
    {
        $this->assertEquals('äachen ýnter môndas', Str::lower_case('Äachen Ýnter Môndas'));
    }

    public function testUpperCaseFirst($value='')
    {
        $this->assertEquals('Äachen ýnter môndas', Str::upper_case_first('äachen ýnter môndas'));
    }

    public function testFirstOfAndLastOf()
    {
        $this->assertEquals(10, Str::first_of('\namespace\subnamespace\ClassName', '\\', 2));
        $this->assertEquals(10, Str::last_of('\namespace\subnamespace\ClassName', '\\', 12));
        
        $this->assertFalse(Str::first_of('ClassName', '\\'));
        $this->assertFalse(Str::last_of('ClassName', '\\'));

        $this->assertEquals(7, Str::first_of('Äachen ýnter môndas', 'ý'));
        $this->assertEquals(14, Str::last_of('Äachen ýnter môndas', 'ô'));
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Class Str does not have a method called 'error'
     */
    public function testBadMethodCallException()
    {
        $s = new Str;
        $s->error('string', 'arg1', 'arg2');
    }

    /**
     * @expectedException \BadMethodCallException
     * @expectedExceptionMessage Class Str does not have a method called 'error'
     */
    public function testBadStaticMethodCallException()
    {
        Str::error('string', 'arg1', 'arg2');
    }
}
