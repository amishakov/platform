<?php declare(strict_types=1);

use App\Application\i18n;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

if (!function_exists('array_add')) {
    /**
     * Add an element to an array using "dot" notation if it doesn't exist.
     */
    function array_add(array $array, float|int|string $key, mixed $value): array
    {
        return Arr::add($array, $key, $value);
    }
}

if (!function_exists('array_collapse')) {
    /**
     * Collapse an array of arrays into a single array.
     */
    function array_collapse(array $array): array
    {
        return Arr::collapse($array);
    }
}

if (!function_exists('array_divide')) {
    /**
     * Divide an array into two arrays. One with keys and the other with values.
     */
    function array_divide(array $array): array
    {
        return Arr::divide($array);
    }
}

if (!function_exists('array_dot')) {
    /**
     * Flatten a multi-dimensional associative array with dots.
     */
    function array_dot(array $array, string $prepend = ''): array
    {
        return Arr::dot($array, $prepend);
    }
}

if (!function_exists('array_except')) {
    /**
     * Get all of the given array except for a specified array of keys.
     */
    function array_except(array $array, array|float|int|string $keys): array
    {
        return Arr::except($array, $keys);
    }
}

if (!function_exists('array_first')) {
    /**
     * Return the first element in an array passing a given truth test.
     */
    function array_first(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        return Arr::first($array, $callback, $default);
    }
}

if (!function_exists('array_flatten')) {
    /**
     * Flatten a multi-dimensional array into a single level.
     */
    function array_flatten(array $array, int $depth = INF): array
    {
        return Arr::flatten($array, $depth);
    }
}

if (!function_exists('array_forget')) {
    /**
     * Remove one or many array items from a given array using "dot" notation.
     */
    function array_forget(array &$array, array|float|int|string $keys): void
    {
        Arr::forget($array, $keys);
    }
}

if (!function_exists('array_get')) {
    /**
     * Get an item from an array using "dot" notation.
     *
     * @param mixed $array
     */
    function array_get($array, null|int|string $key, mixed $default = null): mixed
    {
        return Arr::get($array, $key, $default);
    }
}

if (!function_exists('array_has')) {
    /**
     * Check if an item or items exist in an array using "dot" notation.
     */
    function array_has(array|\ArrayAccess $array, array|string $keys): bool
    {
        return Arr::has($array, $keys);
    }
}

if (!function_exists('array_last')) {
    /**
     * Return the last element in an array passing a given truth test.
     */
    function array_last(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        return Arr::last($array, $callback, $default);
    }
}

if (!function_exists('array_only')) {
    /**
     * Get a subset of the items from the given array.
     */
    function array_only(array $array, array|string $keys): array
    {
        return Arr::only($array, $keys);
    }
}

if (!function_exists('array_pluck')) {
    /**
     * Pluck an array of values from an array.
     */
    function array_pluck(array $array, null|array|int|string $value, null|array|string $key = null): array
    {
        return Arr::pluck($array, $value, $key);
    }
}

if (!function_exists('array_prepend')) {
    /**
     * Push an item onto the beginning of an array.
     */
    function array_prepend(array $array, mixed $value, mixed $key = null): array
    {
        return Arr::prepend($array, $value, $key);
    }
}

if (!function_exists('array_pull')) {
    /**
     * Get a value from the array, and remove it.
     */
    function array_pull(array &$array, int|string $key, mixed $default = null): mixed
    {
        return Arr::pull($array, $key, $default);
    }
}

if (!function_exists('array_random')) {
    /**
     * Get a random value from an array.
     */
    function array_random(array $array, ?int $num = null): mixed
    {
        return Arr::random($array, $num);
    }
}

if (!function_exists('array_set')) {
    /**
     * Set an array item to a given value using "dot" notation.
     * If no key is given to the method, the entire array will be replaced.
     */
    function array_set(array &$array, null|int|string $key, mixed $value): array
    {
        return Arr::set($array, $key, $value);
    }
}

if (!function_exists('array_sort')) {
    /**
     * Sort the array by the given callback or attribute name.
     */
    function array_sort(array $array, null|array|callable|string $callback = null): array
    {
        return Arr::sort($array, $callback);
    }
}

if (!function_exists('array_sort_desc')) {
    /**
     * Sort the array by the given callback or attribute name.
     */
    function array_sort_desc(array $array, null|array|callable|string $callback = null): array
    {
        return Arr::sortDesc($array, $callback);
    }
}

if (!function_exists('array_sort_recursive')) {
    /**
     * Recursively sort an array by keys and values.
     */
    function array_sort_recursive(array $array): array
    {
        return Arr::sortRecursive($array);
    }
}

if (!function_exists('array_where')) {
    /**
     * Filter the array using the given callback.
     */
    function array_where(array $array, callable $callback): array
    {
        return Arr::where($array, $callback);
    }
}

if (!function_exists('str_escape')) {
    /**
     * Escape strings
     */
    function str_escape(array|string $input): array|string
    {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = str_escape($value);
            }
        } else {
            $input = htmlspecialchars(strip_tags($input), ENT_QUOTES);
        }

        return $input;
    }
}

if (!function_exists('str_mask_email')) {
    /**
     * Mask email
     */
    function str_mask_email(string $email): string
    {
        if ($email) {
            $email = explode('@', $email);
            $name = implode('@', array_slice($email, 0, count($email) - 1));
            $len = (int) floor(mb_strlen($name) / 2);

            return mb_substr($name, 0, $len) . str_repeat('*', $len) . '@' . end($email);
        }

        return '';
    }
}

if (!function_exists('str_rem_tags')) {
    /**
     * Mask email
     */
    function str_rem_tags(string $str): string
    {
        $str = preg_replace('/<[^>]*>/', ' ', $str);
        $str = html_entity_decode($str);
        $str = str_replace(["\r", "\0", "\x0B", "\xC2", "\xA0"], '', $str);
        $str = str_replace(["\n", "\t"], ' ', $str);

        return trim(preg_replace('/ {2,}/', ' ', $str));
    }
}

if (!function_exists('blank')) {
    /**
     * Determine if the given value is "blank".
     */
    function blank(mixed $value): bool
    {
        if (is_null($value)) {
            return true;
        }
        if (is_string($value)) {
            return trim($value) === '';
        }
        if (is_numeric($value) || is_bool($value)) {
            return false;
        }
        if ($value instanceof Countable) {
            return count($value) === 0;
        }

        return empty($value);
    }
}

if (!function_exists('__')) {
    /**
     * Locale helper
     */
    function __(null|array|Collection|string $singular, ?string $plural = null, ?int $count = null): array|Collection|string
    {
        return $singular ? i18n::getLocale($singular, $plural, $count) : '';
    }
}

if (!function_exists('array_serialize')) {
    function array_serialize(mixed $array): mixed
    {
        // switch type
        switch (true) {
            case is_a($array, Collection::class):
                $array = $array->all();

                break;

            case is_a($array, EloquentModel::class):
                $array = $array->toArray();

                break;
        }

        // decode data
        foreach ($array as $key => $item) {
            switch (true) {
                case is_array($item):
                    $array[$key] = array_serialize($item);

                    break;

                case is_a($item, Collection::class):
                    $array[$key] = array_serialize($item->all());

                    break;

                case is_a($item, \Ramsey\Uuid\Uuid::class):
                case is_a($item, \Ramsey\Uuid\Lazy\LazyUuidFromString::class):
                    $array[$key] = (string) $item;

                    break;

                case is_a($item, \DateTime::class):
                    $array[$key] = $item->format(\App\Domain\References\Date::DATETIME);

                    break;
            }
        }

        return $array;
    }
}

if (!function_exists(('datetime'))) {
    function datetime(null|Carbon|DateTime|int|string $value = null): Carbon
    {
        return match (true) {
            is_string($value), is_numeric($value) => new Carbon($value),
            is_a($value, DateTime::class) => Carbon::instance($value),
            is_a($value, Carbon::class) => clone $value,
            default => Carbon::now(),
        };
    }
}

if (!function_exists('convertPhpToJsMomentFormat')) {
    function convertPhpToJsMomentFormat(string $phpFormat): string
    {
        $replacements = [
            'A' => 'A',      // for the sake of escaping below
            'a' => 'a',      // for the sake of escaping below
            'B' => '',       // Swatch internet time (.beats), no equivalent
            'c' => 'YYYY-MM-DD[T]HH:mm:ssZ', // ISO 8601
            'D' => 'ddd',
            'd' => 'DD',
            'e' => 'zz',     // deprecated since version 1.6.0 of moment.js
            'F' => 'MMMM',
            'G' => 'H',
            'g' => 'h',
            'H' => 'HH',
            'h' => 'hh',
            'I' => '',       // Daylight Saving Time? => moment().isDST();
            'i' => 'mm',
            'j' => 'D',
            'L' => '',       // Leap year? => moment().isLeapYear();
            'l' => 'dddd',
            'M' => 'MMM',
            'm' => 'MM',
            'N' => 'E',
            'n' => 'M',
            'O' => 'ZZ',
            'o' => 'YYYY',
            'P' => 'Z',
            'r' => 'ddd, DD MMM YYYY HH:mm:ss ZZ', // RFC 2822
            'S' => 'o',
            's' => 'ss',
            'T' => 'z',      // deprecated since version 1.6.0 of moment.js
            't' => '',       // days in the month => moment().daysInMonth();
            'U' => 'X',
            'u' => 'SSSSSS', // microseconds
            'v' => 'SSS',    // milliseconds (from PHP 7.0.0)
            'W' => 'W',      // for the sake of escaping below
            'w' => 'e',
            'Y' => 'YYYY',
            'y' => 'YY',
            'Z' => '',       // time zone offset in minutes => moment().zone();
            'z' => 'DDD',
        ];

        // Converts escaped characters.
        foreach ($replacements as $from => $to) {
            $replacements['\\' . $from] = '[' . $from . ']';
        }

        return strtr($phpFormat, $replacements);
    }
}
