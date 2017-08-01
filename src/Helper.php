<?php

namespace MaDnh\LaravelDevHelper;

use File;
use Illuminate\Database\Eloquent\Model;

class Helper
{
    public static function beautyPath($path)
    {
        return str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Rename array fields
     * @param array $array Array assign as reference
     * @param array $rule Rules, item key is destination name, value is source name
     * @return mixed
     */
    public static function arrayRename(&$array, array $rule)
    {
        $renamed_keys = [];
        foreach ($rule as $dest => $src) {
            if (array_key_exists($src, $array)) {
                $array[$dest] = $array[$src];
                $renamed_keys[] = $src;
            }
        }

        foreach ($renamed_keys as $renamed) {
            unset($array[$renamed]);
        }

        return $array;
    }

    /**
     * Parse OrderBy info
     * @param $value
     * @param array $fields OrderBy-able fields
     * @param null $default_field
     * @param string $default_dir Default direction, ASC
     * @return array
     */
    public static function parserOrderByInfo($value, $fields, $default_field = null, $default_dir = 'ASC')
    {
        $field = $default_field;
        $direction = $default_dir;

        if (is_string($value)) {
            if (in_array($value, $fields)) {
                $field = $value;
            }
        } else if (is_array($value) && !empty($value)) {
            $tmp_value = array_shift($value);
            if (in_array($tmp_value, $fields)) {
                $field = $tmp_value;
            }

            if (!empty($value)) {
                $tmp_value = array_shift($value);
                if (in_array(strtolower($tmp_value), ['asc', 'desc'])) {
                    $direction = strtoupper($tmp_value);
                } else {
                    $direction = $tmp_value ? 'ASC' : 'DESC';
                }
            }
        }

        return [
            'field' => $field,
            'dir' => $direction
        ];
    }

    /**
     * Get dirty attributes of model
     * @param Model $model
     * @return array Array with fields: old, new - contain old and new attributes value
     */
    public static function parseModelDirty($model)
    {
        $result = [];
        $dirty = $model->getDirty();

        if (!empty($dirty)) {
            $result['old'] = array_only($model->getOriginal(), array_keys($dirty));
            $result['new'] = $dirty;
        }

        return $result;
    }

    /**
     * Cast value to boolean, if value is one of strings as 'no', 'off', '0', 'false' then cast to false
     * @param mixed $value
     * @return bool
     */
    public static function castToBoolean($value)
    {
        return self::cast($value, 'bool');
    }

    public static function moveItemInArrayToTop($array, $searchField, $value)
    {
        $key = array_search($value, array_column($array, $searchField));

        if (!empty($key)) {
            $item = $array[$key];
            unset($array[$key]);
            array_unshift($array, $item);
        }

        return $array;
    }

    /**
     * @return null
     */
    public static function firstNotEmpty()
    {
        $items = func_get_args();
        foreach ($items as $item) {
            if (!empty($item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Append string to file name
     * @param string $file_name File name of file path
     * @param string $append_info
     * @param string $file_ext If not special then auto detect
     * @return string
     */
    public static function appendFilename($file_name, $append_info, $file_ext = '')
    {
        if (empty($file_ext)) {
            $file_ext = File::extension($file_name);
        }

        return File::name($file_name) . $append_info . '.' . $file_ext;
    }


    public static function notEmpty($value)
    {
        return !empty($value);
    }

    /**
     * Return value if exists in array, else return first item of array or default value
     * @param mixed $value
     * @param array $array
     * @param mixed $default
     * @return mixed|null
     */
    public static function arrayOneOf(&$value, $array, $default = null)
    {
        if (in_array($value, $array)) {
            return $value;
        }

        if (func_num_args() == 2) {
            return head($array);
        }

        return $default;
    }

    /**
     * Rename fields of a assoc array
     * @param array $array
     * @param array $rename_guide destination_key => source_key
     * @param bool $remove_old_key Remove old key
     * @return array
     */
    public static function arrayTransform($array, $rename_guide, $remove_old_key = true)
    {
        $renamed = [];

        foreach ($rename_guide as $dest_key => $src_key) {
            if (array_key_exists($src_key, $array)) {
                $array[$dest_key] = $array[$src_key];
                $renamed[$src_key] = 0;
            }
        }

        if ($remove_old_key && !empty($renamed)) {
            foreach ($renamed as $renamed_key) {
                unset($array[$renamed_key]);
            }
        }

        return $array;
    }

    /**
     * Rename fields of a assoc array, reserve order of keys
     * @param array $array
     * @param array $rename_guide destination_key => source_key
     * @param bool $remove_old_key Remove old key
     * @return array
     * @throws \Exception
     */
    public static function arrayTransformReverseOrder($array, $rename_guide, $remove_old_key = true)
    {
        $array_keys = array_keys($array);
        $array_values = array_values($array);
        $rename_guide_flip = [];

        foreach ($rename_guide as $dest_key => $src_key) {
            if (!array_key_exists($src_key, $rename_guide_flip)) {
                if ($remove_old_key) {
                    $rename_guide_flip[$src_key] = [$dest_key];
                } else {
                    $rename_guide_flip[$src_key] = [$src_key, $dest_key];
                }
            } else {
                $rename_guide_flip[$src_key][] = $dest_key;
            }
        }

        foreach ($rename_guide_flip as $src_key => $dest_key_arr) {
            if (false === $index = array_search($src_key, $array_keys)) {
                continue;
            }

            array_splice($array_keys, $index, 1, $dest_key_arr);
            array_splice($array_values, $index, 1, array_fill(0, count($dest_key_arr), $array[$src_key]));
        }

        return array_combine($array_keys, $array_values);
    }

    public static function removeDir($path)
    {
        $dir = opendir($path);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                $full = $path . '/' . $file;
                if (is_dir($full)) {
                    self::removeDir($full);
                } else {
                    unlink($full);
                }
            }
        }
        closedir($dir);
        rmdir($path);
    }

    public static function objectToArray($object)
    {
        if (method_exists($object, 'toArray')) {
            return $object->toArray();
        }
        if (method_exists($object, '__toArray')) {
            return (array)$object;
        }
        if (method_exists($object, 'toString')) {
            return (array)$object->toString();
        }
        if (method_exists($object, '__toString')) {
            return (array)(string)$object;
        }


        $array = [];
        foreach ($object as $key => $value) {
            $array[$key] = $value;
        }

        return $array;
    }

    public static function isNumericArray($array)
    {
        foreach (array_keys($array) as $key) {
            if (!is_int($key)) {
                return false;
            }
        }

        return true;
    }

    public static function arrayInOrder($array, $cast_object_to_array = true)
    {
        $result_array = [];
        $keys = array_keys($array);
        if (self::isNumericArray($array)) {
            $result_array = array_slice($array, 0);
            sort($result_array);
        } else {
            sort($keys);
            foreach ($keys as $key) {
                if (is_array($array[$key])) {
                    $result_array[$key] = self::arrayInOrder($array[$key], $cast_object_to_array);
                } else if (is_object($array[$key]) && $cast_object_to_array) {
                    $result_array[$key] = self::objectToArray($array[$key]);
                } else {
                    $result_array[$key] = $array[$key];
                }
            }
        }

        return $result_array;
    }

    public static function hashIdOfArray($array)
    {
        $array_ordered = self::arrayInOrder($array);

        return md5(serialize($array_ordered));
    }

    /**
     * Create hash content of value
     * Warning: boolean value will convert to string of 'true' and 'false', prepended with a string to make sure it value
     * is difference with a pure string 'true', 'false'. Convert boolean value to integer or string value before hash if need.
     * @param mixed $value
     * @param bool $reorder_array Reorder array values before hash
     * @param string $algorithm Hash algorithm, default is sha1
     * @return string
     */
    public static function hashValue($value, $reorder_array = true, $algorithm = 'sha1')
    {
        $special_prefix = '(P@\'-3@M$3\2<>y:4-{]Z.V}$~;\:r'; //DO NOT EDIT THIS VALUE!!!
        $value_as_string = null;
        if (is_string($value) || is_numeric($value)) {
            $value_as_string = (string)$value;
        } else if (is_bool($value)) {
            $value_as_string = $special_prefix . '_' . ($value ? 'true' : 'false');
        } else if (is_null($value)) {
            $value_as_string = $special_prefix . '_NULL';
        } else if (is_array($value) || is_object($value)) {
            if (is_array($value)) {
                $value_as_string = serialize($reorder_array ? self::arrayInOrder($value) : $value);
            } else {
                $value_as_string = serialize($reorder_array ? self::arrayInOrder(self::objectToArray($value)) : self::objectToArray($value));
            }
        }

        return hash($algorithm, $value_as_string);
    }

    public static function stdClassToArray(&$obj)
    {
        $obj = (array)$obj;
    }

    public static function arrayShiftIndex($array, $shift_value)
    {
        $result = [];
        $keys = array_keys($array);
        foreach ($keys as $key) {
            if (is_numeric($key)) {
                $result[$key + $shift_value] = $array[$key];
            } else {
                $result[$key] = $array[$key];
            }
        }

        return $result;
    }

    public static function publishPathToUrl($publish_path, $full = true)
    {
        $sub_path = substr($publish_path, strlen(public_path()) + 1);

        return $full ? asset($sub_path) : $sub_path;
    }

    public static function pathBuild()
    {
        return implode(DIRECTORY_SEPARATOR, array_flatten(func_get_args()));
    }

    /**
     * Remove anything which isn't a word, whitespace, number
     * or any of the following characters -_~,;[]().
     * If you don't need to handle multi-byte characters
     * you can use preg_replace rather than mb_ereg_replace
     * @param $filename
     * @return string
     */
    public static function cleanFilename($filename)
    {
        $filename = mb_ereg_replace("([^\w\s\d\-_~,;\[\]\(\).])", '', $filename);
        //Remove any runs of periods
        $filename = mb_ereg_replace("([\.]{2,})", '', $filename);

        return $filename;
    }

    public static function sanitizeFileName($dangerousFilename, $platform = 'Unix')
    {
        if (in_array(strtolower($platform), array('unix', 'linux'))) {
            // our list of "dangerous characters", add/remove
            // characters if necessary
            $dangerousCharacters = array(" ", '"', "'", "&", "/", "\\", "?", "#");
        } else {
            // no OS matched? return the original filename then...
            return $dangerousFilename;
        }

        // every forbidden character is replace by an underscore
        return str_replace($dangerousCharacters, '_', $dangerousFilename);
    }

    /**
     * Return a timestamp as DateTime object.
     *
     * @param  mixed $value
     * @return \Carbon\Carbon
     */
    public static function asDateTime($value)
    {
        // If this value is already a Carbon instance, we shall just return it as is.
        // This prevents us having to re-instantiate a Carbon instance when we know
        // it already is one, which wouldn't be fulfilled by the DateTime check.
        if ($value instanceof \Carbon\Carbon) {
            return $value;
        }

        // If the value is already a DateTime instance, we will just skip the rest of
        // these checks since they will be a waste of time, and hinder performance
        // when checking the field. We will just return the DateTime right away.
        if ($value instanceof \DateTimeInterface) {
            return new \Carbon\Carbon(
                $value->format('Y-m-d H:i:s.u'), $value->getTimezone()
            );
        }

        // If this value is an integer, we will assume it is a UNIX timestamp's value
        // and format a Carbon object from this timestamp. This allows flexibility
        // when defining your date fields as they might be UNIX timestamps here.
        if (is_numeric($value)) {
            return \Carbon\Carbon::createFromTimestamp($value);
        }

        // If the value is in simply year, month, day format, we will instantiate the
        // Carbon instances from that format. Again, this provides for simple date
        // fields on the database, while still supporting Carbonized conversion.
        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value)) {
            return \Carbon\Carbon::createFromFormat('Y-m-d', $value)->startOfDay();
        }

        // Finally, we will just assume this date is in the format used by default on
        // the database connection and use that format to create the Carbon object
        // that is returned back out to the developers after we convert it here.
        return \Carbon\Carbon::createFromFormat('Y-m-d H:i:s', $value);
    }

    /**
     * @param $value
     * @param $targetType
     * @return mixed
     */
    public static function cast($value, $targetType)
    {
        if (is_null($value)) {
            return $value;
        }

        switch ($targetType) {
            case 'int':
            case 'integer':
                return (int)$value;
            case 'real':
            case 'float':
            case 'double':
                return (float)$value;
            case 'string':
                return (string)$value;
            case 'bool':
            case 'boolean':
                if (is_string($value) && in_array(strtolower(trim($value)), ['no', 'off', 0, '0', 'false'])) {
                    return false;
                }
                return (bool)$value;
            case 'object':
                return json_decode($value, false);
            case 'array':
            case 'json':
                return json_decode($value, true);
            case 'collection':
                return collect(json_decode($value, true));
            case 'date':
            case 'datetime':
                return self::asDateTime($value);
            case 'timestamp':
                return self::asDateTime($value)->getTimestamp();
            default:
                return $value;
        }
    }

    /**
     * Return callback that prepend value to other
     * Use as callback of array_map
     * @param string|number $string
     * @return \Closure
     */
    public static function prependCb($string)
    {
        return function ($value) use ($string) {
            return $string . $value;
        };
    }

    /**
     * Return callback that prepend value to other
     * Use as callback of array_map
     * @param string|number $string
     * @return \Closure
     */
    public static function appendCb($string)
    {
        return function ($value) use ($string) {
            return $value . $value;
        };
    }

    /**
     * Make source array keys is string or item' value
     * Ex: ['a', 'b' => 'B'] => ['a' => 'a', 'b' =>'B']
     *
     * @param $array
     * @return array
     */
    public static function arrayHasKeys($array)
    {
        $result = [];

        foreach ($array as $index => $item) {
            $result[is_int($index) ? $item : $index] = $item;
        }

        return $result;
    }
}