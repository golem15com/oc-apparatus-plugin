<?php namespace Golem15\Apparatus\Tests\Unit\Console;

use Golem15\Apparatus\Console\MailExportCommand;
use Golem15\Apparatus\Console\MailImportCommand;
use Golem15\Apparatus\Tests\PluginTestCase;
use ReflectionMethod;

/**
 * Covers the file format the apparatus:mail-export / apparatus:mail-import pair
 * round-trips through: the code<->filename mapping and the INI value escaping.
 *
 * Both are lossy failure modes that are invisible at a glance -- a mangled code
 * silently creates a duplicate template row instead of updating the real one, and a
 * mangled escape silently corrupts a subject line.
 */
class MailTemplateFormatTest extends PluginTestCase
{
    protected $export;
    protected $import;

    public function setUp(): void
    {
        parent::setUp();

        $this->export = new MailExportCommand();
        $this->import = new MailImportCommand();
    }

    protected function invokeProtected($object, $method, array $args)
    {
        $reflection = new ReflectionMethod($object, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $args);
    }

    protected function toFilename($code)
    {
        return $this->invokeProtected($this->export, 'sanitizeFilename', [$code]) . '.htm';
    }

    protected function toCode($filename)
    {
        return $this->invokeProtected($this->import, 'getCodeFromFilename', [$filename]);
    }

    public function testPluginCodesRoundTrip()
    {
        $codes = [
            'golem15.user::mail.activate',
            'golem15.paymentgateway::mail.order.rejectedReceivedPayment',
            'golem15.horoscope::mail.weekly-horoscope',
        ];

        foreach ($codes as $code) {
            $this->assertEquals($code, $this->toCode($this->toFilename($code)));
        }
    }

    /**
     * Regression: a module-scoped code puts :: after ONE segment, not two. The old
     * heuristic turned backend::mail.invite into backend.mail::invite, which created a
     * bogus second row rather than updating Winter's own template.
     */
    public function testModuleScopedCodesRoundTrip()
    {
        $this->assertEquals('backend::mail.invite', $this->toCode('backend.mail.invite.htm'));
        $this->assertEquals('backend::mail.restore', $this->toCode('backend.mail.restore.htm'));
    }

    public function testUnnamespacedCodesRoundTrip()
    {
        $this->assertEquals('default', $this->toCode('default.htm'));
        $this->assertEquals('system', $this->toCode('system.htm'));
    }

    /**
     * An unregistered file still has to resolve to something sensible, so the
     * author.plugin convention remains the fallback.
     */
    public function testUnknownPluginCodeFallsBackToConvention()
    {
        $this->assertEquals(
            'acme.newplugin::mail.brand.new',
            $this->toCode('acme.newplugin.mail.brand.new.htm')
        );
    }

    /**
     * Only the extension is stripped, not every occurrence of the string.
     */
    public function testOnlyTheTrailingExtensionIsStripped()
    {
        $this->assertEquals('acme.plugin::mail.htm.notice', $this->toCode('acme.plugin.mail.htm.notice.htm'));
    }

    /**
     * @dataProvider escapeValueProvider
     */
    public function testIniValuesSurviveTheFullRoundTrip($value)
    {
        $escaped = $this->invokeProtected($this->export, 'escapeIniValue', [$value]);

        // Exactly what import does: parse_ini_string first, then unescape.
        $parsed = parse_ini_string('v = "' . $escaped . '"', true)['v'];

        $this->assertEquals($value, $this->invokeProtected($this->import, 'unescapeIniValue', [$parsed]));
    }

    public function escapeValueProvider()
    {
        return [
            'plain'                  => ['Potwierdź swoje konto'],
            'newline'                => ["line one\nline two"],
            'carriage return'        => ["line one\r\nline two"],
            'double quotes'          => ['He said "hello"'],
            'backslash'              => ['C:\\Users'],
            // The case sequential str_replace corrupts: unescaping \\ first produces a
            // backslash that the \n pass then reads as an escape.
            'backslash before n'     => ['C:\\new'],
            'backslash then newline' => ["C:\\new\nnext"],
            'trailing backslash'     => ['ends with \\'],
            'literal escape text'    => ['a \\n that must stay literal'],
            'css block'              => [".button { display: block }\n@media (max-width: 500px) {\n  .x { y: 1 }\n}"],
            'empty'                  => [''],
        ];
    }

    public function testNullEscapesToEmptyString()
    {
        $this->assertSame('', $this->invokeProtected($this->export, 'escapeIniValue', [null]));
        $this->assertSame('', $this->invokeProtected($this->import, 'unescapeIniValue', [null]));
    }

    /**
     * MailParser reads only the first three sections, so a fourth would be dropped
     * without a word. Import must refuse the file instead.
     */
    public function testExtraSectionsAreRejectedRatherThanTruncated()
    {
        $content = "subject = \"x\"\n==\ntext\n==\n<p>html</p>\n==\nstray";

        $this->expectException(\Exception::class);
        $this->expectExceptionMessageMatches('/sections/');

        $this->invokeProtected($this->import, 'parseSections', [$content, 'stray.htm']);
    }

    public function testWellFormedFileParses()
    {
        $content = "subject = \"Hello\"\ndescription = \"d\"\n==\nplain\n==\n<p>rich</p>";

        $sections = $this->invokeProtected($this->import, 'parseSections', [$content, 'ok.htm']);

        $this->assertEquals('Hello', $sections['settings']['subject']);
        $this->assertEquals('plain', $sections['text']);
        $this->assertEquals('<p>rich</p>', $sections['html']);
    }

    public function testNonDefaultLocalesExcludeTheDefault()
    {
        $locales = $this->invokeProtected($this->export, 'getMailLocales', []);

        if (empty($locales)) {
            $this->markTestSkipped('No Translate plugin locales configured.');
        }

        $default = $this->invokeProtected($this->export, 'getDefaultMailLocale', []);
        $others = $this->invokeProtected($this->export, 'getNonDefaultMailLocales', []);

        $this->assertNotNull($default, 'A default locale must be resolvable.');
        $this->assertNotContains($default, $others);
        $this->assertEqualsCanonicalizing(
            array_diff(array_keys($locales), [$default]),
            $others
        );
    }

    public function testLocaleFilesLiveInASubdirectoryNotASuffix()
    {
        $reflection = new \ReflectionProperty($this->export, 'basePath');
        $reflection->setAccessible(true);
        $reflection->setValue($this->export, '/tmp/mail_templates');

        $this->assertEquals(
            '/tmp/mail_templates/templates/locales/en',
            $this->invokeProtected($this->export, 'localeDirectory', ['templates', 'en'])
        );
    }
}
