<?php /** @noinspection RegExpRedundantEscape */
/** @noinspection RegExpSingleCharAlternation */
/** @noinspection RegExpUnnecessaryNonCapturingGroup */

declare(strict_types=1);

namespace Falseclock\AdvancedPSR7;

use DateTime;
use Falseclock\Common\Lib\Utils\DateUtils;
use Falseclock\Common\Lib\Utils\TextUtils;
use GuzzleHttp\Psr7\CachingStream;
use GuzzleHttp\Psr7\LazyOpenStream;
use GuzzleHttp\Psr7\ServerRequest;
use Psr\Http\Message\ServerRequestInterface;

class HttpRequest extends ServerRequest
{
    const MAX_INT16_VALUE = 32767;
    const MAX_INT32_VALUE = 2147483647;
    const MAX_INT64_VALUE = 9223372036854775807;
    const MAX_INT8_VALUE = 127;

    /**
     * @return ServerRequestInterface
     * @inheritDoc
     */
    public static function fromGlobals(): ServerRequestInterface
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $headers = getallheaders();
        $uri = self::getUriFromGlobals();
        $body = new CachingStream(new LazyOpenStream('php://input', 'r+'));
        $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? str_replace('HTTP/', '', $_SERVER['SERVER_PROTOCOL']) : '1.1';

        $serverRequest = new HttpRequest($method, $uri, $headers, $body, $protocol, $_SERVER);

        return $serverRequest
            ->withCookieParams($_COOKIE)
            ->withQueryParams($_GET)
            ->withParsedBody($_POST)
            ->withUploadedFiles(self::normalizeFiles($_FILES));
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
        if (isset($this->queryParams[$paramName]))
            $this->queryParams[$paramName] = null;

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
        if (isset($this->queryParams[$paramName]))
            return $this->queryParams[$paramName];

        return $defaultValue;
    }

    /**
     * Функция проверяет наличие значения
     * - если значение есть, то возвращается true, иначе - false
     *
     * @param string $paramName
     * @param bool $defaultValue
     *
     * @return bool
     */
    public function getInputVarBoolean(string $paramName, bool $defaultValue = false): bool
    {
        if (isset($this->queryParams[$paramName]))
            return filter_var($this->queryParams[$paramName], FILTER_VALIDATE_BOOLEAN);

        return $defaultValue;
    }

    /**
     * @param string $paramName
     * @param string $dateFormat
     * @param DateTime|null $defaultValue
     *
     * @return DateTime|null
     */
    public function getInputVarDate(string $paramName, string $dateFormat = "Y-m-d", DateTime $defaultValue = null): ?DateTime
    {
        if (isset($this->queryParams[$paramName])) {

            if (DateUtils::isDateStringValid($this->queryParams[$paramName]))
                return $defaultValue ?? null;

            return DateTime::createFromFormat($dateFormat, $this->queryParams[$paramName]) ?? null;
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
    public function getInputVarDigit(string $paramName, string $defaultValue = null): ?string
    {
        if (isset($this->queryParams[$paramName])) {
            $out = $this->queryParams[$paramName];

            if (is_array($out)) {
                foreach ($out as &$value) {
                    $value = preg_replace("/[^0-9]/", "", $value);
                }
            } else {
                //к типу
                $out = preg_replace("/[^0-9]/", "", $out);
            }

            return (string)$out;
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
        if (isset($this->queryParams[$paramName])) {
            if (!TextUtils::isEmailValid($this->queryParams[$paramName]))
                return null;
            else
                return $this->queryParams[$paramName];
        }

        return null;
    }

    /**
     * Функция выбирает значение, приведя его к float
     *
     * @param string $paramName Наименование переменной, которая присутствует в POST или GET
     * @param float|null $defaultValue Значение по умолчанию, если переменная не была задана
     *
     * @return float
     */
    public function getInputVarFloat(string $paramName, float $defaultValue = null): ?float
    {
        if (isset($this->queryParams[$paramName])) {
            $out = $this->queryParams[$paramName];

            if (is_array($this->queryParams[$paramName]))
                $out = $out[$this->queryParams[$paramName]];

            return TextUtils::toFloat($out);
        }

        return $defaultValue;
    }

    /**
     * Получение чисел 1-byte integer
     *
     * @param string $paramName
     * @param int|int[]|null $defaultValue
     * @param bool $removeZeroValues
     * @param bool $removeDuplicates
     * @param int $maxArrayLength
     *
     * @return int|int[]|null
     */
    public function getInputVarInt8(string $paramName, $defaultValue = null, bool $removeZeroValues = false, bool $removeDuplicates = false, int $maxArrayLength = -1)
    {
        $int = $this->getInputVarInt64($paramName, $defaultValue, $removeZeroValues, $removeDuplicates, $maxArrayLength);

        return $this->filterInt($int, $defaultValue, $removeZeroValues, $removeDuplicates, $maxArrayLength, self::MAX_INT8_VALUE);
    }

    /**
     * Получение чисел 2-byte integer
     *
     * @param string $paramName
     * @param int|int[]|null $defaultValue
     * @param bool $removeZeroValues
     * @param bool $removeDuplicates
     * @param int $maxArrayLength
     *
     * @return int|int[]|null
     */
    public function getInputVarInt16(string $paramName, $defaultValue = null, bool $removeZeroValues = false, bool $removeDuplicates = false, int $maxArrayLength = -1)
    {
        $int = $this->getInputVarInt64($paramName, $defaultValue, $removeZeroValues, $removeDuplicates, $maxArrayLength);

        return $this->filterInt($int, $defaultValue, $removeZeroValues, $removeDuplicates, $maxArrayLength, self::MAX_INT16_VALUE);
    }

    /**
     * Получение чисел 4-byte integer
     *
     * @param string $paramName
     * @param int|int[]|null $defaultValue
     * @param bool $removeZeroValues
     * @param bool $removeDuplicates
     * @param int $maxArrayLength
     *
     * @return int|int[]|null
     */
    public function getInputVarInt32(string $paramName, $defaultValue = null, bool $removeZeroValues = false, bool $removeDuplicates = false, int $maxArrayLength = -1)
    {
        $int = $this->getInputVarInt64($paramName, $defaultValue, $removeZeroValues, $removeDuplicates, $maxArrayLength);

        return $this->filterInt($int, $defaultValue, $removeZeroValues, $removeDuplicates, $maxArrayLength, self::MAX_INT32_VALUE);
    }

    /**
     * @param int|int[] $int
     * @param int|int[]|null $defaultValue
     * @param bool $removeZeroValues
     * @param bool $removeDuplicates
     * @param int $maxArrayLength
     * @param int $maxValue
     *
     * @return array|int|mixed|null
     */
    private function filterInt($int, $defaultValue, bool $removeZeroValues, bool $removeDuplicates, int $maxArrayLength, int $maxValue)
    {
        if (is_array($int)) {
            foreach ($int as $index => $value) {
                if ($removeZeroValues && $value == 0) {
                    unset($int[$index]);
                    continue;
                }
                if ($value > $maxValue) {
                    unset($int[$index]);
                }
            }
            if ($removeDuplicates) {
                $int = array_unique($int);
            }

            if ($maxArrayLength > 0) {
                if (count($int) > $maxArrayLength) {
                    //throw new Exception("Длина массива больше разрешенной");
                    return $defaultValue;
                }
            }

            return count($int) ? $int : $defaultValue;
        } else {
            if ($int <= $maxValue) {
                if ($removeZeroValues and $int == 0) {
                    return null;
                }

                return $int;
            }
        }

        return $defaultValue;
    }

    /**
     * Функция выбирает значение, приведя его к integer
     *
     * @param string $paramName Наименование переменной, которая присутствует в POST или GET
     * @param int|int[]|null $defaultValue Значение по умолчанию, если переменная не была задана
     * @param bool $removeZeroValues
     * @param bool $removeDuplicates
     * @param int $maxArrayLength
     *
     * @return int|int[]|null
     */
    public function getInputVarInt64(string $paramName, $defaultValue = null, bool $removeZeroValues = false, bool $removeDuplicates = false, int $maxArrayLength = -1)
    {

        if (isset($this->queryParams[$paramName]) and $this->queryParams[$paramName] != "") {
            $out = $this->queryParams[$paramName];

            if (is_array($out)) {
                foreach ($out as &$value) {
                    $value = intval(preg_replace("/[^0-9]/", "", $value));
                }
            } else {
                //к типу
                $out = intval(preg_replace("/[^0-9]/", "", $out));
            }

            if (is_array($out)) {
                if ($removeDuplicates) {
                    $out = array_unique($out, SORT_NUMERIC);
                }
                if ($removeZeroValues) {
                    $out = array_values(array_diff($out, [0]));
                }
                if ($maxArrayLength > 0) {
                    if (count($out) > $maxArrayLength) {
                        return $defaultValue;
                        //throw new Exception("Длина массива больше разрешенной");
                    }
                }

                if (!count($out)) {
                    return null;
                }
            }

            return $out;
        }

        return $defaultValue;
    }

    /**
     * @param $paramName
     *
     * @return array|string|null
     */
    public function getInputVarJson($paramName)
    {
        if (isset($this->queryParams[$paramName])) {
            $array = json_decode(htmlspecialchars_decode($this->queryParams[$paramName]), true);

            return TextUtils::htmlSpecialChars($array);
        }

        return null;
    }

    /**
     * Функция выводить значение предварительно отфильтровав его и обрезав, если нужно
     *
     * @param string $paramName Наименование переменной, которая присутствует в POST или GET
     * @param int $length Макисмальная дозволенная длина строковой переменной
     * @param string|null $defaultValue
     * @param string|null $pattern
     * @param boolean $filterHtml Фильтрация HTML символов для предотвращения XSS атак.
     *
     * @return string|null
     */
    public function getInputVarStr(string $paramName, int $length = 4096, string $defaultValue = null, string $pattern = null, bool $filterHtml = true): ?string
    {
        if (isset($this->queryParams[$paramName])) {
            $out = trim($this->queryParams[$paramName]);

            if ($length) {
                $out = mb_substr($out, 0, $length);
            }

            //разэкранирование
            //if(get_magic_quotes_gpc()) {
            //	$out = stripslashes($out);
            //}

            if (isset($pattern)) {
                if (!preg_match($pattern, $out)) {
                    return $defaultValue;
                }
            }

            //преобразование в спецсимволы
            if ($filterHtml == true) {
                //фильтрация тэгов
                $out = strip_tags($out);
            }

            return $out;
        }

        return $defaultValue;
    }

    /**
     * @param string $paramName
     * @param string|null $defaultValue
     * @param bool $sanitize
     *
     * @return string|null
     * @todo сделать возможность массива
     */
    public function getInputVarUrl(string $paramName, string $defaultValue = null, bool $sanitize = true): ?string
    {

        if (isset($this->queryParams[$paramName])) {
            if (!is_scalar($this->queryParams[$paramName]))
                return $defaultValue;

            $url = trim($this->queryParams[$paramName]);

            if (strlen($url) > 2048)
                return $defaultValue;

            $path = parse_url($url, PHP_URL_PATH);
            $encoded_path = array_map('urlencode', explode('/', $path));
            $url = str_replace($path, implode('/', $encoded_path), $url);

            // Remove all illegal characters from a url
            if ($sanitize)
                $url = filter_var($url, FILTER_SANITIZE_URL);

            if (filter_var($url, FILTER_VALIDATE_URL))
                return $url;
        }

        return $defaultValue;
    }

    /**
     * Функция выбирает значение, приведя его к UUID
     *
     * @param string $paramName Наименование переменной, которая присутствует в POST или GET
     * @param float|null $defaultValue Значение по умолчанию, если переменная не была задана
     *
     * @return string|null
     */
    public function getInputVarUUID(string $paramName, $defaultValue = null)
    {
        if (isset($this->queryParams[$paramName])) {
            $out = $this->queryParams[$paramName];

            if (is_array($this->queryParams[$paramName])) {
                $out = $out[$this->queryParams[$paramName]];
            }

            //к типу
            $out = strtolower(trim($out));
            if (!preg_match('/^[a-fA-F0-9]{8}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{4}-[a-fA-F0-9]{12}$/', $out)) {
                return $defaultValue;
            }

            return $out;
        }

        return $defaultValue;
    }

    /**
     * Функция вызывается для эмуляции создания пришедшей переменной через GET или POST.
     *
     * @param string $paramName Наименование параметра
     * @param mixed $value Значение параметра
     *
     * @return $this
     */
    public function setInputVar(string $paramName, $value): HttpRequest
    {
        $this->queryParams[$paramName] = $value;

        return $this;
    }
}
