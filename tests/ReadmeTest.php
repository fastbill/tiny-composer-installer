<?php

class ReadmeTest extends \PHPUnit\Framework\TestCase
{

    protected static $readme;

    protected $locRegex = '/This is less than (\d+) lines of PHP\./';
    protected $halfRegex = '/About half of these (\d+) lines is/';

    public static function setUpBeforeClass()
    {
        self::$readme = file_get_contents(__DIR__ . '/../README.md');
    }

    public function testContainsLOCStatement()
    {
        $this->assertTrue(
            (bool)preg_match($this->locRegex, self::$readme, $matches),
            'Missing statement about number of lines of code in README.'
        );
        return (int)$matches[1];
    }

    /**
     * @param int $lessThanNumber The number of lines the README claims the script has less than.
     * @depends testContainsLOCStatement
     */
    public function testBothLOCStatementsUseSameNumber($lessThanNumber)
    {
        $this->assertTrue(
            (bool)preg_match($this->halfRegex, self::$readme, $matches),
            'Missing statement about "half of these lines" in README.'
        );
        $this->assertEquals($lessThanNumber, (int)$matches[1], 'README uses different numbers for lines of code.');
    }

    /**
     * @param int $lessThanNumber The number of lines the README claims the script has less than.
     * @depends testContainsLOCStatement
     */
    public function testLOCStatementIsCorrect($lessThanNumber)
    {
        $actualLOC = count(file(__DIR__ . '/../tiny-composer-installer.php'));
        $this->assertInternalType('int', $actualLOC, 'Could not read number of lines in script.');
        $this->assertLessThan($lessThanNumber, $actualLOC, 'README is lying about the number of lines of code.');
    }

}
