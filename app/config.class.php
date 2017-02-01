<?php
/**
 * @file config.class.php
 *
 * Configuration files handler.
 */
use Symfony\Component\Yaml\Parser;

class AppConfig
{
    const CONFIG_VERSION = 2016092001;

    /**
     * the config array.
     */
    public static $config = null;

    /**
     * parse a yml config file and return the result.
     *
     * On success, AppConfig will provide the newly parsed configuration after
     * parse() returns. On error, the configuration is not loaded and an
     * exception is thrown.
     *
     * @param $path
     *   The yml config file path
     *
     * @param $sub (optional)
     *   An associative array of substitutions to recursively apply. This is
     *   used to simulate "variables" in the config, mostly for paths like
     *   APPDIR etc.
     *
     * @throw InvalidArgumentException
     *  If the provided configuration path is not in sync with CONFIG_VERSION.
     *
     * @return
     *   The parsed result
     */
    public static function parse($path, $sub = null)
    {
        $yaml     = new Parser();
        $parsed   = $yaml->parse(file_get_contents($path));
        $previous = static::$config;

        // install the newly parsed config
        static::$config = static::substitute($parsed, $sub);

        // check if the version match
        $expected = static::CONFIG_VERSION;
        $version  = static::get('CONFIG_VERSION');
        $match    = ($version === $expected);
        if (!$match) {
            $msg = sprintf(
                'CONFIG_VERSION missmatch, expected %s but got %s',
                strval($expected),
                strval($version)
            );
            static::$config = $previous; // revert to the previous config.
            throw new InvalidArgumentException($msg);
        }

        return static::$config;
    }

    /**
     * getter for a config option.
     *
     * @param $key
     *   The config option needed.
     *
     * @param $default (null)
     *   The value to return if the config option is not set, default to null.
     *
     * @return
     *   The config value if it exists, null otherwise.
     *
     * <b>Example</b>
     * @code
     *   AppConfig::get('security.bcrypt_cost') # same as AppConfig::get(array('security', 'bcrypt_cost'))
     *   AppConfig::get('security.bcrypt_cost', 14);
     * @endcode
     */
    public static function get($key, $default = null)
    {
        $cfg  = static::$config;
        $desc = (is_array($key) ? $key : explode('.', strval($key)));

        foreach ($desc as $atom) {
            if (is_array($cfg) && array_key_exists($atom, $cfg))
                $cfg = $cfg[$atom];
            else
                return $default;
        }

        return $cfg;
    }

    /**
     * recursively substitute some patterns.
     *
     * NOTE: it will only recurse into array, not objects.
     *
     * @param $data
     *   The data to apply the substitutions to.
     *
     * @param $sub
     *   An associative array of substitutions to recursively apply.
     *
     * @return
     *   A deep copy of the given data after substitution.
     */
    protected static function substitute($data, $sub)
    {
        $result = $data;

        if (!is_array($sub))
            return $result;

        if (is_string($data)) {
            $result = str_replace(array_keys($sub), array_values($sub), $data);
        } else if (is_array($data)) {
            $result = array();
            foreach ($data as $key => $value)
                $result[$key] = static::substitute($value, $sub);
        }

        return $result;
    }
}
