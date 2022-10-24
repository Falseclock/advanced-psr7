<?php
/**
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2009-2022 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 */

declare(strict_types=1);

namespace Falseclock\AdvancedPSR7\Tests;

use DateTime;
use Falseclock\AdvancedPSR7\HttpRequest;
use Falseclock\Common\Lib\Utils\TextUtils;
use PHPUnit\Framework\TestCase;
use stdClass;

class HttpRequestTest extends TestCase
{
    public function testFromGlobals()
    {
        $request = HttpRequest::fromGlobals();
        self::assertInstanceOf(HttpRequest::class, $request);
    }

    public function testGetInput()
    {
        $request = HttpRequest::fromGlobals();

        self::assertIsArray($request->getInput());
    }

    public function testSetInputVar()
    {
        $request = HttpRequest::fromGlobals();

        $request->setInputVar('testVar', 123);

        self::assertArrayHasKey("testVar", $request->getInput());

        self::assertSame(123, $request->getInputVarInt("testVar"));
        self::assertSame(123, $request->getInputVar("testVar"));
    }

    public function testDropInputVar(): void
    {
        $request = HttpRequest::fromGlobals();
        $request->setInputVar('testVar', 123);
        self::assertArrayHasKey("testVar", $request->getInput());

        $request->dropInputVar('testVar');
        self::assertArrayNotHasKey("testVar", $request->getInput());
        self::assertCount(0, $request->getInput());
    }

    public function testGetInputVar()
    {
        $request = HttpRequest::fromGlobals();

        $value = $request->getInputVar("no_var", 111);
        self::assertSame(111, $value);

        $value = $request->getInputVar("no_var_null");
        self::assertNull($value);
    }

    public function testGetInputVarBoolean()
    {
        $request = HttpRequest::fromGlobals();
        $value = $request->getInputVarBoolean("no_var");
        self::assertFalse($value);

        // "1", "true", "on" и "yes"
        $request->setInputVar("bool", 1);
        $value = $request->getInputVarBoolean("bool");
        self::assertTrue($value);

        $request->setInputVar("bool", "1");
        $value = $request->getInputVarBoolean("bool");
        self::assertTrue($value);

        $request->setInputVar("bool", true);
        $value = $request->getInputVarBoolean("bool");
        self::assertTrue($value);

        $request->setInputVar("bool", "true");
        $value = $request->getInputVarBoolean("bool");
        self::assertTrue($value);

        $request->setInputVar("bool", "on");
        $value = $request->getInputVarBoolean("bool");
        self::assertTrue($value);

        $request->setInputVar("bool", "yes");
        $value = $request->getInputVarBoolean("bool");
        self::assertTrue($value);

        $request->setInputVar("bool", "");
        $value = $request->getInputVarBoolean("bool");
        self::assertFalse($value);

        $request->setInputVar("bool", null);
        $value = $request->getInputVarBoolean("bool");
        self::assertFalse($value);
    }

    public function testGetInputVarDate()
    {
        $request = HttpRequest::fromGlobals();
        $request->setInputVar("date", "2022-10-24");

        $value = $request->getInputVarDate("date");

        self::assertInstanceOf(DateTime::class, $value);

        $date = $value->format("Y-m-d");

        self::assertSame("2022-10-24", $date);

        $request->setInputVar("date", "aa-20221024");
        $value = $request->getInputVarDate("date");
        self::assertNull($value);

        $value = $request->getInputVarDate("nodate");
        self::assertNull($value);

        $value = $request->getInputVarDate("nodate", new DateTime());
        self::assertNotNull($value);
        self::assertInstanceOf(DateTime::class, $value);
    }

    public function testGetInputVarDigit()
    {
        $request = HttpRequest::fromGlobals();
        $request->setInputVar("digit", "0000000000001");
        $value = $request->getInputVarDigit("digit");
        self::assertSame("0000000000001", $value);

        $request->setInputVar("digit", "!0000000000001!");
        $value = $request->getInputVarDigit("digit");
        self::assertSame("0000000000001", $value);

        $value = $request->getInputVarDigit("digit_no", "0000000000001");
        self::assertSame("0000000000001", $value);
    }

    public function testGetInputVarEmail()
    {
        $request = HttpRequest::fromGlobals();
        $request->setInputVar("email", " eMail@deNgi.kg ");

        $value = $request->getInputVarEmail("email");
        self::assertSame("email@dengi.kg", $value);

        $request->setInputVar("email", " some string ");
        self::assertNull($request->getInputVarEmail("email"));

        self::assertNull($request->getInputVarEmail("email_no"));
    }

    public function testGetInputVarFloat()
    {
        $request = HttpRequest::fromGlobals();

        $request->setInputVar("float", "0.1234");
        $value = $request->getInputVarFloat("float");
        self::assertSame(0.1234, $value);

        $request->setInputVar("float", 0.1234);
        $value = $request->getInputVarFloat("float");
        self::assertSame(0.1234, $value);

        $value = $request->getInputVarFloat("float_no", 0.99999999);
        self::assertSame(0.99999999, $value);

        $value = $request->getInputVarFloat("float_null");
        self::assertNull($value);
    }

    public function testGetInputVarInt()
    {
        $request = HttpRequest::fromGlobals();

        $request->setInputVar("int", "1234");
        $value = $request->getInputVarInt("int");
        self::assertSame(1234, $value);

        $request->setInputVar("int", 1234);
        $value = $request->getInputVarInt("int");
        self::assertSame(1234, $value);

        $value = $request->getInputVarInt("int_no", 5678);
        self::assertSame(5678, $value);
    }

    public function testGetInputVarJson()
    {
        $request = HttpRequest::fromGlobals();
        $request->setInputVar("json", "[]");
        $value = $request->getInputVarJson("json");
        self::assertIsArray($value);

        $request->setInputVar("json", "{}");
        $value = $request->getInputVarJson("json");
        self::assertIsArray($value);

        $request->setInputVar("json", "{}");
        $value = $request->getInputVarJson("json", false);
        self::assertInstanceOf(stdClass::class, $value);

        $request->setInputVar("json", "[]");
        $value = $request->getInputVarJson("json", false);
        self::assertIsArray($value);

        $value = $request->getInputVarJson("json1", false);
        self::assertNull($value);
        $value = $request->getInputVarJson("json2");
        self::assertNull($value);
    }

    public function testGetInputVarString()
    {
        $request = HttpRequest::fromGlobals();
        $request->setInputVar("string", "foo");
        $value = $request->getInputVarString("string");
        self::assertSame("foo", $value);

        $request->dropInputVar("string");
        $value = $request->getInputVarString("string", "bar");
        self::assertSame("bar", $value);

        $request->setInputVar("string", "foo");
        $value = $request->getInputVarString("string", null, 2);
        self::assertSame("fo", $value);

        $request->setInputVar("string", "foo<br>");
        $value = $request->getInputVarString("string", null, 1024);
        self::assertSame("foo", $value);

        $request->setInputVar("string", "foo<br>");
        $value = $request->getInputVarString("string", null, 1024, false);
        self::assertSame("foo<br>", $value);
    }

    public function testGetInputVarUrl() {
        $request = HttpRequest::fromGlobals();

        $request->setInputVar("url", "foo");
        $value = $request->getInputVarUrl("url");
        self::assertNull($value);

        $request->setInputVar("url", "https://foo.bar");
        $value = $request->getInputVarUrl("url");
        self::assertNotNull($value);

        $value = $request->getInputVarUrl("no_url");
        self::assertNull($value);

        $request->setInputVar("url", TextUtils::randomString(2049));
        $value = $request->getInputVarUrl("url");
        self::assertNull($value);
    }

    public function testGetInputVarUUID() {
        $request = HttpRequest::fromGlobals();
        $uuid = TextUtils::randomGUUID();

        $request->setInputVar("uuid", $uuid);
        $value = $request->getInputVarUUID("uuid");
        self::assertSame($uuid, $value);

        $request->setInputVar("uuid", "!!");
        $value = $request->getInputVarUUID("uuid");
        self::assertNull($value);

        $value = $request->getInputVarUUID("uuid_no");
        self::assertNull($value);
    }
}
