<?php
/**
 * @noinspection RegExpRedundantEscape
 * @noinspection RegExpUnnecessaryNonCapturingGroup
 * @copyright    2007-2017 by Nurlan Mukhanov <nurike@gmail.com>
 * @license      MIT
 */

declare(strict_types=1);

namespace Falseclock\AdvancedPSR7\Utils;

use Exception;
use Throwable;

final class TextUtils
{
    const EMAIL_CHECK_REGEXP = '/^(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){255,})(?!(?:(?:\x22?\x5C[\x00-\x7E]\x22?)|(?:\x22?[^\x5C\x22]\x22?)){65,}@)(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22))(?:\.(?:(?:[\x21\x23-\x27\x2A\x2B\x2D\x2F-\x39\x3D\x3F\x5E-\x7E]+)|(?:\x22(?:[\x01-\x08\x0B\x0C\x0E-\x1F\x21\x23-\x5B\x5D-\x7F]|(?:\x5C[\x00-\x7F]))*\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-[a-z0-9]+)*\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-[a-z0-9]+)*)|(?:\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\]))$/iD';

    /**
     * Проверка email адресам на RFC валидность
     *
     * @param $email
     *
     * @return bool
     */
    public static function isEmailValid(&$email): bool
    {

        if (is_null($email))
            return false;

        if (!is_string($email))
            return false;

        $email = mb_strtolower(trim($email));

        return (bool)preg_match(self::EMAIL_CHECK_REGEXP, $email);
    }

    /**
     * @param $value
     *
     * @return string
     */
    public static function boolToString($value): string
    {
        return $value ? 'true' : 'false';
    }

    /**
     * @param string $str
     * @param array $arr
     *
     * @return bool
     */
    public static function contains(string $str, array $arr): bool
    {
        foreach ($arr as $a) {
            if (stripos($str, $a) !== false)
                return true;
        }

        return false;
    }

    /**
     * Режет текст по пробелам на заданную длинну, то есть - режет не посередине слова, а на ближайшем пробельном символе
     *
     * @param string $string
     * @param int $length
     * @param null $append - обычно это '...'
     *
     * @return string
     */
    public static function cutOnSpace(string $string, int $length, $append = null)
    {
        if (mb_strlen($string) < $length)
            return $string;
        else {
            if (!$pos = mb_strpos($string, ' ', $length))
                $pos = $length;

            return mb_substr($string, 0, $pos) . $append;
        }
    }

    /**
     * Текст из базы в HTML
     *
     * @param string|null $value
     * @param bool $forTextArea
     * @param bool $wrap
     *
     * @return mixed
     */
    public static function dbText2Html(?string $value, bool $forTextArea = false, bool $wrap = false)
    {
        if (!is_null($value) && $value != "") {
            if ($forTextArea) {
                $value = str_replace("&quot;", '"', $value);
                $value = str_replace("&#34;", '"', $value);
                $value = str_replace("&#034;", '"', $value);

                $value = str_replace("&apos;", "'", $value);
                $value = str_replace("&#39;", "'", $value);
                $value = str_replace("&#039;", "'", $value);
            } else {
                $value = str_replace("<", "&lt;", $value);
                $value = str_replace(">", "&gt;", $value);
                $value = str_replace('"', "&#34;", $value);
                $value = str_replace("'", "&#39;", $value);
            }

            $value = preg_replace("/\r\n/", "\n", $value);
            $value = preg_replace("/^\n$/", "", $value);
            $value = preg_replace("/\n{2,}/", "\n\n", $value);
            if (!$forTextArea)
                $value = preg_replace("/\n/", " <br />", $value);

            $value = preg_replace("/\t/", "    ", $value);

            //$val = trim($val);

            if ($wrap)
                $value = self::explodeWrap($value, 40, " ");
        }

        return $value;
    }

    /**
     * Разбивка текста на строки определенной длины
     *
     * @param        $text
     * @param        $chunk_length
     * @param string $replacement
     *
     * @return string
     */
    public static function explodeWrap($text, $chunk_length, string $replacement = " - ")
    {
        $string_chunks = explode(' ', $text);
        $new_string_chunks = [];

        foreach ($string_chunks as $chunk => $value) {
            if (strlen($value) >= $chunk_length) {
                $new_string_chunks[$chunk] = chunk_split($value, $chunk_length, $replacement);
            } else {
                $new_string_chunks[$chunk] = $value;
            }
        }

        return implode(' ', $new_string_chunks);
    }

    /**
     * Делает первую букву в строке маленькой
     *
     * @param string $string
     *
     * @return string
     */
    public static function downFirst(string $string)
    {
        $firstOne = mb_strtolower(mb_substr($string, 0, 1));

        return $firstOne . mb_substr($string, 1);
    }

    /**
     * @param string $string
     * @param        $endsWith
     *
     * @return bool
     */
    public static function endsWith(string $string, $endsWith): bool
    {
        $length = strlen($endsWith);
        if ($length == 0) {
            return true;
        }

        return (substr($string, -$length) === $endsWith);
    }

    /**
     * Размер файла в человеко-понятном виде
     *
     * @param float $size
     * @param int $precision
     * @param array $units
     * @param boolean $spacePunctuation
     *
     * @return string
     */
    public static function friendlyFileSize($size, $precision = 2, $units = [null, 'k', 'M', 'G', 'T', 'P'], $spacePunctuation = false)
    {
        if ($size > 0) {
            $index = min((int)log($size, 1024), count($units) - 1);

            return round($size / pow(1024, $index), $precision) . ($spacePunctuation ? ' ' : null) . $units[$index];
        }

        return 0;
    }

    /**
     * Получение пути относительно корневого значения
     *
     * @param $url
     *
     * @return string
     */
    public static function getPathFromUrl($url)
    {
        $parsed = parse_url($url);

        if ($parsed === false or !isset($parsed['path']))
            return '/';
        else
            return $parsed['path'];
    }

    /**
     * Получение по сути домена или IP адреса из URL
     *
     * @param $url
     *
     * @return bool|string
     */
    public static function getRootFromUrl($url)
    {
        if (strpos($url, '//') !== false && (strpos($url, '//') + 2) < strlen($url))
            $offset = strpos($url, '//') + 2;
        else
            $offset = 0;

        return substr($url, 0, strpos($url, '/', $offset) + 1);
    }

    /**
     * Из шестнадцатеричной в двоичную систему
     *
     * @param string $hex
     *
     * @return integer
     * @throws Throwable
     */
    public static function hex2Binary(string $hex): int
    {
        $length = strlen($hex);

        if ($length % 2 != 0)
            throw new Exception("Possibly not integer value");

        $out = null;
        for ($i = 0; $i < $length; $i += 2) {
            $out .= pack('C', hexdec(substr($hex, $i, 2)));
        }

        return (int)$out;
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    public static function isBase64(string $string): bool
    {
        if (base64_encode(base64_decode($string)) === $string) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param $string
     *
     * @return bool
     */
    public static function isInteger($string): bool
    {
        return self::isDigitsOnly($string) and bccomp($string, (string)2147483648) !== 1;
    }

    /**
     * @param $string
     *
     * @return bool
     */
    public static function isDigitsOnly($string): bool
    {
        return preg_match("/^\d+$/", $string);
    }

    /**
     * @param $string
     *
     * @return bool
     */
    public static function isJson($string): bool
    {
        json_decode($string);

        return (json_last_error() == JSON_ERROR_NONE);
    }

    /**
     * @param string $string
     *
     * @return bool
     */
    public static function isUTF8(string $string): bool
    {

        // From http://w3.org/International/questions/qa-forms-utf-8.html
        return preg_match('%^(?:
          [\x09\x0A\x0D\x20-\x7E]            # ASCII
        | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
        |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
        | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
        |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
        |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
        | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
        |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
    )*$%xs',
                $string
            ) == 1;
    }

    /**
     * @param $string
     *
     * @return string|string[]
     */
    public static function nl2br($string)
    {
        return str_replace(["\r\n", "\r", "\n"], "<br />", $string);
    }

    /**
     * @param string|null $password
     * @param string|int $algorithm
     * @param int $cost
     *
     * @return bool|string
     */
    public static function passwordHash(?string $password = null, $algorithm = PASSWORD_BCRYPT, int $cost = 11)
    {
        return password_hash($password ?? TextUtils::randomString(), $algorithm, ['cost' => $cost]);
    }

    /**
     * Генерация случайного пароля или идентификатора сессии
     *
     * @param int $length
     *
     * @return string
     */
    public static function randomString(int $length = 8): string
    {
        $random = "";
        $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $codeAlphabet .= "abcdefghijklmnopqrstuvwxyz";
        $codeAlphabet .= "0123456789";
        $max = strlen($codeAlphabet);

        for ($i = 0; $i < $length; $i++) {
            $random .= $codeAlphabet[self::cryptoRandSecure(0, $max - 1)];
        }

        return $random;
    }

    /**
     * @param $min
     * @param $max
     *
     * @return int
     */
    public static function cryptoRandSecure($min, $max): int {
        $range = $max - $min;
        if($range < 1)
            return $min; // not so random...
        $log = ceil(log($range, 2));
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1;        // length in bits
        $filter = (1 << $bits) - 1;    // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        }
        while($rnd > $range);

        return $min + $rnd;
    }

    /**
     * Функция перевода пхп-шного массива в постгресовский
     *
     * @param array $array
     *
     * @return string
     */
    public static function pgArray(array $array): string
    {
        return '{' . (implode(",", $array)) . '}';
    }

    /**
     * Функция создания постгресовского UUID
     *
     * @param $string
     *
     * @return string|null
     */
    public static function pgUUID($string)
    {
        $string = preg_replace("/[^0-9a-zA-Z]/", "", $string);

        if (strlen($string) != 32) {
            return null;
        }

        $string = strtolower($string);
        // 0-8      8-4  12-4 16-4 20-12
        // 3a7ea730-4d95-0e33-6a5c-cbce64fc8ab7

        return substr($string, 0, 8) . "-" . substr($string, 8, 4) . "-" . substr($string, 12, 4) . "-" . substr($string, 16, 4) . "-" . substr($string, 20, 12);
    }

    /**
     * Функция перевода массива потсгреса в пхп-шный массив
     *
     * @param $string
     *
     * @return array
     */
    public static function php_array($string)
    {
        if (!$string) {
            return [];
        }

        return explode(",", self::pgUnArray($string));
    }

    /**
     * Функция удаления скобок с постгресовского массива
     *
     * @param $string
     *
     * @return bool|string
     */
    public static function pgUnArray($string)
    {
        return substr(substr($string, 1), 0, -1);
    }

    /**
     * @return string
     */
    public static function pseudoRandInt64(): string
    {
        $comps = explode(' ', microtime());

        return sprintf('%d%06d%03d', $comps[1], $comps[0] * 1000000, rand(0, 999));
    }

    /**
     * @return string
     */
    public static function randomGUUID(): string
    {
        if (function_exists('com_create_guid') === true)
            /** @noinspection PhpUndefinedFunctionInspection */
            return trim(com_create_guid(), '{}');

        try {
            $data = random_bytes(16);
        } catch (Throwable) {
            $data = openssl_random_pseudo_bytes(16);
        }
        assert(strlen($data) == 16);

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * @param int $length
     *
     * @return int
     */
    public static function randomPin(int $length = 4)
    {
        return rand(pow(10, $length - 1), pow(10, $length) - 1);
    }

    /**
     * @param $string
     *
     * @return false|string
     */
    public static function removeBomUtf8($string)
    {
        if (substr($string, 0, 3) == chr(hexdec('EF')) . chr(hexdec('BB')) . chr(hexdec('BF'))) {
            return substr($string, 3);
        } else {
            return $string;
        }
    }

    /**
     * htmlentities() do not support hexadecimal numeric character references yet.
     *
     * @see http://www.w3.org/TR/REC-html40/charset.html#entities
     * @see http://www.w3.org/TR/REC-html40/sgml/entities.html
     * @see http://php.net/htmlentities
     *
     * @param string $text
     *
     * @return mixed
     */
    public static function safeAmp(string $text)
    {
        return preg_replace('/&(?!(#(([0-9]+)|(x[0-9A-F]+))' . '|([a-z][a-z0-9]*));)/i', '&amp;', $text);
    }

    /**
     * Функция отрезает все лишние символы слева и справа
     *
     * @param $string
     *
     * @return mixed
     */
    public static function safeTrim($string)
    {
        if ($string) {
            // обрез начальных пробелов
            $string = preg_replace("/^\s*/", "", $string);

            // обрез конечных пробелов
            $string = preg_replace("/\s*$/", "", $string);
        }

        return $string;
    }

    /**
     * @param $string
     * @param $startsWith
     *
     * @return bool
     */
    public static function startsWith($string, $startsWith): bool
    {
        $length = strlen($startsWith);

        return (substr($string, 0, $length) === $startsWith);
    }

    /**
     * @param                $string
     * @param false $capitalizeFirstCharacter
     * @param array|string[] $search
     *
     * @return string
     */
    public static function toCamelCase($string, bool $capitalizeFirstCharacter = false, array $search = ['_', '-']): string
    {

        $string = preg_replace('/([a-z])([A-Z])/', "\\1 \\2", $string);
        $string = preg_replace('@[^a-zA-Z0-9\-_ ]+@', '', $string);
        $string = str_replace($search, ' ', $string);
        $string = str_replace(' ', '', ucwords(strtolower($string)));
        if (!$capitalizeFirstCharacter)
            $string = lcfirst($string);

        return $string;
    }

    /**
     * Приведение значения к типу float
     *
     * @param $num
     *
     * @return float
     */
    public static function toFloat($num): float
    {
        $dotPos = strrpos($num, '.');
        $commaPos = strrpos($num, ',');
        $sep = (($dotPos > $commaPos) && $dotPos) ? $dotPos : ((($commaPos > $dotPos) && $commaPos) ? $commaPos : false);

        if (!$sep) {
            return floatval(preg_replace("/[^0-9]/", "", $num));
        }

        return floatval(preg_replace("/[^0-9]/", "", substr($num, 0, $sep)) . '.' . preg_replace("/[^0-9]/", "", substr($num, $sep + 1, strlen($num))));
    }

    /**
     * @param      $variable
     * @param bool $forceObject
     *
     * @return string
     */
    public static function toVueJsonProp($variable, bool $forceObject = false): string
    {
        if (!is_null($variable)) {
            if ($forceObject)
                $variable = json_encode($variable, JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT);
            else
                $variable = json_encode($variable, JSON_UNESCAPED_UNICODE);
            $variable = str_replace(["'", '`'], ["&#039;", "&#096;"], $variable);

            //$variable = str_replace([ '\\' ], [ "&#092;" ], $variable);
            return str_replace(["&"], ["\\&"], $variable);
        } else {
            return "null";
        }
    }

    /**
     * @param $variable
     *
     * @return string
     * @deprecated
     */
    public static function toVueProp($variable): string
    {
        if (!is_null($variable))
            return '"' . htmlspecialchars($variable) . '"';
        else
            return "null";
    }

    /**
     * @param int $length
     *
     * @return false|string
     */
    public static function uniqueString(int $length = 14)
    {

        try {
            $bytes = random_bytes((int)ceil($length / 2));
        } catch (Throwable $e) {
            $bytes = openssl_random_pseudo_bytes((int)ceil($length / 2));
        }

        return substr(bin2hex($bytes), 0, $length);
    }

    /**
     * Делает первую букву в слове большой
     *
     * @param string $string
     *
     * @return string
     */
    public static function upFirst(string $string): string
    {
        $firstOne = mb_strtoupper(mb_substr($string, 0, 1));

        return $firstOne . mb_substr($string, 1);
    }

    /**
     * Расшифровка URLов, закодированных в base64 методом urlSafeBase64Encode
     *
     * @param string $string
     *
     * @return string
     */
    public static function urlSafeBase64Decode(string $string): string
    {
        $data = str_replace(['-', '_'], ['+', '/'], $string);

        $mod4 = strlen($data) % 4;

        if ($mod4) {
            $data .= substr('====', $mod4);
        }

        return base64_decode($data);
    }

    /**
     * Кодирование URLов в base64 без потери спесимволов
     *
     * @param string $string
     *
     * @return string
     */
    public static function urlSafeBase64Encode(string $string): string
    {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($string));
    }

    /**
     * Перекодирование строк в разные кодировки
     *
     * @param        $in_str
     * @param string $charset
     *
     * @return string
     */
    public function encode($in_str, string $charset = "koi8-r"): string
    {
        $out_str = $in_str;

        // define start delimiter, end delimiter and spacer
        $end = "?=";
        $start = "=?" . $charset . "?B?";
        $spacer = $end . "\r\n " . $start;

        // determine length of encoded text within chunks
        // and ensure length is even
        $length = 75 - strlen($start) - strlen($end);
        $length = floor($length / 2) * 2;

        // encode the string and split it into chunks
        // with spacers after each chunk
        $out_str = base64_encode($out_str);
        $out_str = chunk_split($out_str, $length, $spacer);

        // remove trailing spacer and
        // add start and end delimiters
        $spacer = preg_quote($spacer);
        $out_str = preg_replace("/" . $spacer . "$/", "", $out_str);

        return $start . $out_str . $end;
    }
}
