<?php


class DownloadTest extends \PHPUnit\Framework\TestCase
{

    protected static $officialInstaller;
    protected static $officialComposer;

    public static function setUpBeforeClass()
    {
        if (!is_string(static::$officialInstaller)) {
            static::$officialInstaller = tempnam(sys_get_temp_dir(), 'official-composer-installer-');
            $installerContents = file_get_contents('https://getcomposer.org/installer');
            static::assertInternalType('string', $installerContents, 'Could not download official installer.');
            file_put_contents(static::$officialInstaller, $installerContents);
        }
        if (!is_string(static::$officialComposer)) {
            static::$officialComposer = tempnam(sys_get_temp_dir(), 'official-composer-');
            $result = static::execute(implode(' ', [
                'php',
                escapeshellarg(static::$officialInstaller),
                '--quiet',
                escapeshellarg('--install-dir=' . dirname(static::$officialComposer)),
                escapeshellarg('--filename=' . basename(static::$officialComposer)),
            ]));
            static::assertSame(0, $result['rc'], 'Official installer failed.');
        }
    }

    public static function tearDownAfterClass()
    {
        if (is_string(static::$officialInstaller)) {
            unlink(static::$officialInstaller);
            static::$officialInstaller = null;
        }
        if (is_string(static::$officialComposer)) {
            unlink(static::$officialComposer);
            static::$officialComposer = null;
        }
    }

    protected static function execute($command)
    {
        exec($command, $output, $rc);
        return [
            'cmd' => $command,
            'out' => $output,
            'rc'  => $rc,
        ];
    }

    protected static function executeTCI(array $params)
    {
        return static::execute(implode(' ', array_merge([
            'php',
            escapeshellarg(__DIR__ . '/../tiny-composer-installer.php'),
        ], $params)));
    }

    /**
     * @medium
     */
    public function testTinyAndOfficialDownloadTheSameComposer()
    {
        $tinyFile = tempnam(sys_get_temp_dir(), 'tiny-composer-');
        $result = static::executeTCI([escapeshellarg($tinyFile)]);
        $this->assertSame(0, $result['rc'], 'Tiny installer failed.');
        $this->assertFileEquals(static::$officialComposer, $tinyFile, 'Tiny installer downloads a different file');
        unlink($tinyFile);
    }

    /**
     * @medium
     */
    public function testDefaultOutputFilename()
    {
        $default = 'composer.phar';
        $this->assertFileNotExists($default, "$default already exists -- test environment clean?");
        $result = static::executeTCI([]);
        $this->assertSame(0, $result['rc'], 'TCI failed.');
        $this->assertSame(realpath($default), $result['out'][0], "TCI didn't write to $default.");
        unlink($default);
    }

    /**
     * @medium
     */
    public function testRelativeOutputFilename()
    {
        $destination = sprintf('composer.%d.phar', time());
        $this->assertFileNotExists($destination, "$destination already exists -- test environment clean?");
        $result = static::executeTCI([$destination]);
        $this->assertSame(0, $result['rc'], 'TCI failed.');
        $this->assertSame(realpath($destination), $result['out'][0], "TCI didn't write to $destination.");
        unlink($destination);
    }

}
