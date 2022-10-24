<?php
/**
 * @noinspection RegExpRedundantEscape
 * @noinspection RegExpSingleCharAlternation
 * @noinspection RegExpUnnecessaryNonCapturingGroup
 */

declare(strict_types=1);

namespace Falseclock\AdvancedPSR7;

use DateTime;
use Falseclock\Common\Lib\Utils\DateUtils;
use Falseclock\Common\Lib\Utils\TextUtils;
use GuzzleHttp\Psr7\CachingStream;
use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;
use stdClass;

class HttpRequest extends ServerRequest
{
    /*
    const MAX_INT16_VALUE = 32767;
    const MAX_INT32_VALUE = 2147483647;
    const MAX_INT64_VALUE = 9223372036854775807;
    const MAX_INT8_VALUE = 127;
*/

    /** @var array merged array of $_POST and $_GET values */
    protected array $input = [];

    /**
     * @return ServerRequestInterface
     * @inheritDoc
     */
    public static function fromGlobals(): HttpRequest
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $headers = getallheaders();
        $uri = self::getUriFromGlobals();
        $body = new CachingStream(new LazyOpenStream('php://input', 'r+'));
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';

        $serverRequest = new HttpRequest($method, $uri, $headers, $body, $protocol, $_SERVER);

        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        return $serverRequest
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withUploadedFiles(self::normalizeFiles($_FILES))
            ->withInput();
    }

    /**
     * Combine $_POST and $_GET
     * @return HttpRequest
     */
    private function withInput(): HttpRequest
    {
        $new = clone $this;
        $new->input = $this->getQueryParams();

        $post = $new->getParsedBody();

        if (is_array($post))
            $new->input = array_merge($new->input, $post);

        return $new;
    }

    /**
     * @return array
     */
    public function getInput(): array
    {
        return $this->input;
    }

    /**
     * Функция сброса параметра, если необходимо передать в другой класс или метод, которому данная переменна может мешать логике.
     *
     * @param string $paramName Наименование параметра
     *
     * @return HttpRequest $this
     */
    public function dropInputVar(string $paramName): ServerRequestInterface
    {
        if (isset($this->input[$paramName]))
            unset($this->input[$paramName]);

        return $this;
    }

    /**
     * Функция получения переданной переменной через POST и GET.
     *
     * @param string $paramName Наименование переменной, которая присутствует в POST или GET
     * @param null $defaultValue Значение по умолчанию, если переменная не была задана
     *
     * @return mixed|null
     */
    public function getInputVar(string $paramName, $defaultValue = null): mixed
    {
        if (isset($this->input[$paramName]))
            return $this->input[$paramName];

        return $defaultValue;
    }

    /**
     * Функция проверяет наличие значения
     * - если значение есть, то возвращается true, иначе - false
     * @param string $paramName
     * @param bool $defaultValue
     *
     * @return bool
     */
    public function getInputVarBoolean(string $paramName, bool $defaultValue = false): bool
    {
        if (isset($this->input[$paramName]))
            return filter_var($this->input[$paramName], FILTER_VALIDATE_BOOLEAN);

        return $defaultValue;
    }

    /**
     * @param string $paramName
     * @param DateTime|null $defaultValue
     * @param string $dateFormat
     *
     * @return DateTime|null
     */
    public function getInputVarDate(string $paramName, DateTime $defaultValue = null, string $dateFormat = "Y-m-d"): ?DateTime
    {
        if (isset($this->input[$paramName])) {

            if (!DateUtils::isDateStringValid($this->input[$paramName]))
                return $defaultValue ?? null;

            return DateTime::createFromFormat($dateFormat, $this->input[$paramName]) ?? null;
        }

        return $defaultValue ?? null;
    }

    /**
     * возвращение длинных цифровых строк
     *
     * @param string $paramName
     * @param string|null $defaultValue
     *
     * @return string|null
     */
    public function getInputVarDigit(string $paramName, string $defaultValue = null): string|null
    {
        if (isset($this->input[$paramName])) {

            $this->input[$paramName] = (string)preg_replace("/[^0-9]/", "", $this->input[$paramName]);

            return $this->input[$paramName];
        }

        return $defaultValue;
    }

    /**
     * @param string $paramName
     *
     * @return string|null
     */
    public function getInputVarEmail(string $paramName): ?string
    {
        if (isset($this->input[$paramName])) {
            if (!TextUtils::isEmailValid($this->input[$paramName]))
                return null;
            else
                return $this->input[$paramName];
        }

        return null;
    }

    /**
     * Функция выбирает значение, приведя его к float
     *
     * @param string $paramName Наименование переменной, которая присутствует в POST или GET
     * @param float|null $defaultValue Значение по умолчанию, если переменная не была задана
     *
     * @return float|null
     */
    public function getInputVarFloat(string $paramName, float $defaultValue = null): ?float
    {
        if (isset($this->input[$paramName]))
            return TextUtils::toFloat((string)$this->input[$paramName]);

        return $defaultValue;
    }

    /**
     * Получение чисел
     *
     * @param string $paramName
     * @param int|null $defaultValue
     * @return int|null
     */
    public function getInputVarInt(string $paramName, int $defaultValue = null): int|null
    {
        if (isset($this->input[$paramName]) and $this->input[$paramName] != "") {

            $integer = $this->input[$paramName];

            return intval(preg_replace("/[^0-9]/", "", (string)$integer));
        }

        return $defaultValue;
    }

    /**
     * @param string $paramName
     * @param bool $associative
     * @return array|stdClass|null
     */
    public function getInputVarJson(string $paramName, bool $associative = true): mixed
    {
        if (isset($this->input[$paramName])) {
            return json_decode(htmlspecialchars_decode($this->input[$paramName]), $associative);

            //return TextUtils::htmlSpecialChars($array);
        }

        return null;
    }

    /**
     * Функция выводить значение предварительно отфильтровав его и обрезав, если нужно
     *
     * @param string $paramName Наименование переменной, которая присутствует в POST или GET
     * @param string|null $defaultValue
     * @param int $maxLength
     * @param bool $stripTags
     * @return string|null
     */
    public function getInputVarString(string $paramName, string $defaultValue = null, int $maxLength = 4096, bool $stripTags = true): ?string
    {
        if (isset($this->input[$paramName])) {

            $string = mb_substr($this->input[$paramName], 0, $maxLength);

            if ($stripTags)
                $string = strip_tags($string);

            return $string;
        }

        return $defaultValue;
    }

    /**
     * @param string $paramName
     * @param bool $sanitize
     *
     * @return string|null
     */
    public function getInputVarUrl(string $paramName, bool $sanitize = true): string|null
    {
        if (isset($this->input[$paramName])) {
            $url = trim($this->input[$paramName]);

            if (strlen($url) > 2048)
                return null;

            if (!filter_var($url, FILTER_VALIDATE_URL))
                return null;

            //$path = parse_url($url, PHP_URL_PATH);
            //$encoded_path = array_map('urlencode', explode('/', $path));
            //$url = str_replace($path, implode('/', $encoded_path), $url);

            // Remove all illegal characters from an url
            if ($sanitize)
                $url = filter_var($url, FILTER_SANITIZE_URL);

            return $url;
        }

        return null;
    }

    /**
     * Функция выбирает значение, приведя его к UUID
     *
     * @param string $paramName Наименование переменной, которая присутствует в POST или GET
     * @return string|null
     */
    public function getInputVarUUID(string $paramName): string|null
    {
        if (isset($this->input[$paramName])) {
            $out = $this->input[$paramName];

            //к типу
            $uuid = strtolower(trim($out));
            if (!preg_match('/^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}$/', $uuid)) {
                return null;
            }

            return $uuid;
        }

        return null;
    }

    /**
     * Функция вызывается для эмуляции создания пришедшей переменной через GET или POST.
     *
     * @param string $paramName Наименование параметра
     * @param mixed $value Значение параметра
     *
     * @return $this
     */
    public function setInputVar(string $paramName, mixed $value): HttpRequest
    {
        $this->input[$paramName] = $value;

        return $this;
    }
}
