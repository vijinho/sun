<?php

/**
 * sun.php - CLI/WEB calculating solar data
 * relies on command-line tools, tested on MacOS.
 *
 * @author Vijay Mahrra <vijay@yoyo.org>
 * @copyright (c) Copyright 2018 Vijay Mahrra
 * @license GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)
 * @url https://github.com/vijinho/sun
 * @see https://github.com/gregseth/suncalc-php
 */
date_default_timezone_set('UTC');
ini_set('default_charset', 'utf-8');
ini_set('mbstring.encoding_translation', 'On');
ini_set('mbstring.func_overload', 6);
ini_set('auto_detect_line_endings', TRUE);

//-----------------------------------------------------------------------------
// get sun class

include_once dirname(__FILE__) . '/suncalc.php';


class MySunCalc extends SunCalc
{
    /**
     * return the values as a array
     * method allows outputting values as an array
     *
     * @param string date_format format to date()
     * @param replace_keys replace key names
     * @return array sun phase information
     * @link http://php.net/manual/en/language.oop5.magic.php#object.tostring
     */
    public function toArray($date_format = 'U', $replace_keys = []): array
    {
        $data = [];
        $suntimes = $this->getSunTimes();
        // use my replacement key names
        if (empty($replace_keys)) {
            $replace_keys = [
                'nauticalDawn' => 'dawn_nautical',
                'dawn' => 'dawn',
                'sunrise' => 'sunrise_start',
                'sunriseEnd' => 'sunrise_end',
                'goldenHourEnd' => 'golden_hour_morning',
                'solarNoon' => 'noon',
                'goldenHour' => 'golden_hour_evening',
                'sunsetStart' => 'sunset_start',
                'sunset' => 'sunset_end',
                'dusk' => 'dusk',
                'nauticalDusk' => 'dusk_nautical',
                'night' => 'night',
                'nadir' => 'nadir',
                'nightEnd' => 'night_end',
            ];
        }

        foreach ($suntimes as $key => $dateObject) {
            $data[$replace_keys[$key]] = $dateObject->format($date_format);
        }

        return array_replace(array_flip($replace_keys), $data);
    }


    /**
     * returned values when called with var_dump()
     *
     * @return array|null debug info
     * @link http://php.net/manual/en/language.oop5.magic.php#object.debuginfo
     */
    public function __debugInfo(): array
    {
        return $this->toArray(false);
    }


    /**
     * return the values as a string
     * method allows outputting values as a string
     *
     * @param string date_format format to date()
     * @return string json_encode()
     * @link http://php.net/manual/en/language.oop5.magic.php#object.tostring
     */
    public function toJSON(): string
    {
        return json_encode(to_charset($this->toArray(), $date_format = 'U'), JSON_PRETTY_PRINT);
    }


    /**
     * return the values as a string
     * method allows outputting values as a string
     *
     * @return string json_encode()
     * @link http://php.net/manual/en/language.oop5.magic.php#object.tostring
     */
    public function __toString(): string
    {
        return $this->toJSON();
    }


}

//-----------------------------------------------------------------------------
// detect if run in web mode or cli

switch (php_sapi_name()) {
    case 'cli':
        break;
    default:
    case 'cli-server': // run as web-service
        define('DEBUG', 0);
        $params    = [
            'date', 'date-format', 'latitude', 'longitude', 'city-id', 'cities', 'refresh'
        ];

        // filter input variables
        $_REQUEST = array_change_key_case($_REQUEST);
        $keys     = array_intersect($params, array_keys($_REQUEST));
        $params   = [];
        foreach ($_REQUEST as $k => $v) {
            if (!in_array($k, $keys)) {
                unset($_REQUEST[$k]);
                continue;
            }
            $v = trim(strip_tags(filter_var(urldecode($v),
                        FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW)));
            if (!empty($v)) {
                $_REQUEST[$k]      = $v;
                // params to command line
                $params['--' . $k] = escapeshellarg($v);
            } else {
                $params['--' . $k] = '';
            }
        }

        // build command line
        $php = cmd_execute('which php');
        $cmd = $php[0] . ' ' . $_SERVER['SCRIPT_FILENAME'] . ' --echo ';
        foreach ($params as $k => $v) {
            $cmd .= (empty($v)) ? " $k" : " $k=$v";
        }

        // exexute command line and quit
        $data = shell_execute($cmd);
        header('Content-Type: application/json');
        echo $data['stdout'];
        exit;
}

//-----------------------------------------------------------------------------
// define command-line options
// see https://secure.php.net/manual/en/function.getopt.php
// : - required, :: - optional

$options = getopt("hvdrt:", [
    'help', 'verbose', 'debug', 'test', 'date:', 'date-format:', 'echo',
    'dir:', 'filename:', 'filename:', 'refresh',
    'latitude:', 'longitude:', 'cities', 'city-id:', 'search-city:',
]);

$do = [];
foreach ([
'verbose' => ['v', 'verbose'],
 'debug'   => ['d', 'debug'],
 'test'   => [null, 'test'],
 'echo'    => ['e', 'echo'],
 'refresh'   => [null, 'refresh'],
 'cities'   => [null, 'cities'],
 'search-city' => [null, 'search-city']
] as $i => $opts) {
    $do[$i] = (int) (array_key_exists($opts[0], $options) || array_key_exists($opts[1],
            $options));
}

if (array_key_exists('debug', $do) && !empty($do['debug'])) {
    $do['verbose']      = $options['verbose'] = 1;
}

ksort($do);

//-----------------------------------------------------------------------------
// defines (int) - forces 0 or 1 value

define('DEBUG', (int) $do['debug']);
define('VERBOSE', (int) $do['verbose']);
debug('OPTIONS:', $do);
define('TEST', (int) $do['test']);

if (TEST) {
    verbose(sprintf('TEST Mode. Overriding latitude and longitude with config.ini values: %f latitutde, %f longitude', $config['test']['latitude'], $config['test']['longitude']));
    $options['latitude']  = $config['test']['latitude'];
    $options['longitude'] = $config['test']['longitude'];
}

//-----------------------------------------------------------------------------
// help
if (array_key_exists('h', $options) || array_key_exists('help', $options)) {
    options:

    $readme_file = dirname(__FILE__) . '/README.md';
    if (file_exists($readme_file)) {
        $readme = file_get_contents('README.md');
        if (!empty($readme)) {
            output($readme . "\n");
        }
    }

    print join("\n",
            [
        "Usage: php sun.php",
        "Get the sun phase data using class https://github.com/gregseth/suncalc-php",
        "(Specifying any other unknown argument options will be ignored.)\n",
        "\t-h,  --help                   Display this help and exit",
        "\t-v,  --verbose                Run in verbose mode",
        "\t-d,  --debug                  Run in debug mode (implies also -v, --verbose)",
        "\t-t,  --test                   Run in test mode, using co-ordinates for Skagen, Denmark from stormyglass.ini file by default.",
        "\t-e,  --echo                   (Optional) Echo/output the result to stdout if successful",
        "\t-r,  --refresh                (Optional) Force cache-refresh",
        "\t     --search-city=<text>     Search for city using supplied text.",
        "\t     --city-id={city_id}      (Optional) Specify GeoNames city id (in cities.json file) for required latitude/longitude values",
        "\t     --latitude={-90 - 90}    (Required) Latitude (decimal degrees)",
        "\t     --longitude={-180 - 180} (Required) Longitude (decimal degrees)",
        "\t-t   --date={now}             (Optional) Date/time default 'now' see: https://secure.php.net/manual/en/function.strtotime.php",
        "\t     --dir=                   (Optional) Directory for storing files (sys_get_temp_dir() if not specified)",
        "\t-f,  --filename={output.}     (Optional) Filename for output data from operation",
        "\t     --format={json}          (Optional) Output format for output filename (reserved for future): json (default)",
    ]);

    // goto jump here if there's a problem
    errors:
    if (!empty($errors)) {
        if (is_array($errors)) {
            echo json_encode(['errors' => $errors], JSON_PRETTY_PRINT);
        }
    } else {
        output("\nNo errors occurred.\n");
    }

    goto end;
    exit;
}

//-----------------------------------------------------------------------------
// initialise variables

//-----------------------------------------------------------------------------
// initialise variables

$errors = []; // errors to be output if a problem occurred
$output = []; // data to be output at the end

//-----------------------------------------------------------------------------
// output format

$format = '';
if (!empty($options['format'])) {
    $format = $options['format'];
}
switch ($format) {
    default:
    case 'json':
        $format = 'json';
}
define('OUTPUT_FORMAT', $format);
verbose("OUTPUT_FORMAT: $format");

//-----------------------------------------------------------------------------
// get dir and file for output

$dir = sys_get_temp_dir();
if (!empty($options['dir'])) {
    $dir = $options['dir'];
}
$dircheck = realpath($dir);
if (empty($dircheck) || !is_dir($dircheck)) {
    $errors[] = "You must specify a valid directory!";
    goto errors;
}

$output_filename = !empty($options['filename']) ? $options['filename'] : '';

//-----------------------------------------------------------------------------
// list cities
//
// data for cities from http://download.geonames.org/export/dump/
$data_dir  = realpath(dirname(__FILE__)) . '/data';
$cities_file = $data_dir . '/cities15000.tsv';
if (!file_exists($cities_file)) {
    $errors[] = "Missing cities file: $cities_file";
    goto errors;
}
define('FILE_TSV_CITIES', $cities_file);

if ($do['cities']) {
    $data = [];
    $output_filename = realpath($dir) . '/' . 'cities.json';
    // remove old-file if refresh
    if ($do['refresh']) {
        unlink($output_filename);
    }
    if (file_exists($output_filename)) {
        $data = json_load($output_filename);
        if (is_array($data) && count($data)) {
            debug("Cached city data loaded from: $output_filename");
        } else {
            debug("Cached file data not found for: $cache_file");
        }
    }
    if (empty($data)) {
        $data = getCities();
    }
    // save cities and finish
    goto output;
}

//-----------------------------------------------------------------------------
// get latitude, longitude

if (!empty($options['city-id'])) {
    $city_id = (int) $options['city-id'];
    if ($city_id < 1) {
        $errors[] = "Invalid city id: $city_id";
        goto errors;
    }
    $city = getCities($city_id);
    if (empty($city)) {
        $errors[] = "City not found with id: $city_id";
        goto errors;
    }
    debug("Found city with id: $city_id", $city);
    $city = $city[$city_id];
    $options['longitude'] = $city['longitude'];
    $options['latitude'] = $city['latitude'];
}

// search for city
if (!empty($options['search-city'])) {
    $search = trim(strtolower($options['search-city']));
    $cities = getCities();
    // removes all unmatched cities
    foreach ($cities as $i => $city) {
        if (false !== stristr($city['city'], $search) || false !== stristr($city['ascii'], $search)) {
            continue;
        }
        if (empty($city['names'])) {
            continue;
        }
        foreach ($city['names'] as $name) {
            if (false !== stristr($name, $search)) {
                continue;
            }
        }
        unset($cities[$i]);
    }
    if (empty($cities)) {
        $errors[] = "No matching cities matched for city: '$search'";
        goto errors;
    }
    $data = $cities;
    goto output;
}

$latitude = array_key_exists('latitude', $options) ? (float) $options['latitude']
        : null;
$latitude = (float) $latitude;
if (-90 > $latitude || 90 < $latitude) {
    $errors[] = "You must specify a value for latitude (-90 to 90)!";
    goto errors;
}
verbose("Latitude: $latitude");

$longitude = array_key_exists('longitude', $options) ? (float) $options['longitude']
        : null;
if (-180 > $longitude || 180 < $longitude) {
    $errors[] = "You must specify a value for longitude (-180 to 180)!";
    goto errors;
}
verbose("Longitude: $longitude");

//-----------------------------------------------------------------------------
// get date from/to from command-line

$date = 0;
if (!empty($options['date'])) {
    $date = $options['date'];
}
if (!empty($date)) {
    $date = strtotime($date);
    if (false === $date) {
        $errors[] = sprintf("Unable to parse --date: %s", $options['date']);
        goto errors;
    }

    verbose(sprintf("Fetching results FROM date/time '%s': %s",
            $options['date'], gmdate('r', $date)));
}
if (empty($date)) {
    $date = time();
}
$dateObject = new DateTime(date('Y-m-d H:i:s', $date));

//-----------------------------------------------------------------------------
// date format

$date_format = 'U';
if (!empty($options['date-format'])) {
    $date_format = $options['date-format'];
    if (false === date($date_format)) {
        $errors[] = "Invalid date format: $date_format";
        goto errors;
    }
}

//-----------------------------------------------------------------------------
// MAIN
// set up request params for sg_point_request($request_params)

$sun = new MySunCalc($dateObject, $latitude, $longitude);
$data = $sun->toArray($date_format);
$data['timestamp'] = $date;
$data['datestamp'] = date('r', $date);
if (!empty($city)) {
    $data['city'] = $city;
}

//-----------------------------------------------------------------------------
// final output of data

output:

// display any errors
if (!empty($errors)) {
    goto errors;
}

// set data to write to file
if (is_array($data) && !empty($data)) {
    $output = $data;
}

// only write/display output if we have some!
if (!empty($output)) {

    if (!empty($output_filename)) {
        $file = $output_filename;
        switch (OUTPUT_FORMAT) {
            default:
            case 'json':
                $save = json_save($file, $output);
                if (true !== $save) {
                    $errors[] = "\nFailed encoding JSON output file:\n\t$file\n";
                    $errors[] = "\nJSON Error: $save\n";
                    goto errors;
                } else {
                    verbose(sprintf("JSON written to output file:\n\t%s (%d bytes)\n",
                            $file, filesize($file)));
                }
                break;
        }

    }

    // output data if --echo
    if ($do['echo']) {
        echo json_encode($output, JSON_PRETTY_PRINT);
    }
}

end:

debug(sprintf("Memory used (%s) MB (current/peak).", get_memory_used()));
output("\n");
exit;


//-----------------------------------------------------------------------------
// functions used above

/**
 * Output string, to STDERR if available
 *
 * @param  string { string to output
 * @param  boolean $STDERR write to stderr if it is available
 */
function output($text, $STDERR = true)
{
    if (!empty($STDERR) && defined('STDERR')) {
        fwrite(STDERR, $text);
    } else {
        echo $text;
    }
}


/**
 * Dump debug data if DEBUG constant is set
 *
 * @param  optional string $string string to output
 * @param  optional mixed $data to dump
 * @return boolean true if string output, false if not
 */
function debug($string = '', $data = [])
{
    if (DEBUG) {
        output(trim('[D ' . get_memory_used() . '] ' . $string) . "\n");
        if (!empty($data)) {
            output(print_r($data, 1));
        }
        return true;
    }
    return false;
}


/**
 * Output string if VERBOSE constant is set
 *
 * @param  string $string string to output
 * @param  optional mixed $data to dump
 * @return boolean true if string output, false if not
 */
function verbose($string, $data = [])
{
    if (VERBOSE && !empty($string)) {
        output(trim('[V' . ((DEBUG) ? ' ' . get_memory_used() : '') . '] ' . $string) . "\n");
        if (!empty($data)) {
            output(print_r($data, 1));
        }
        return true;
    }
    return false;
}


/**
 * Return the memory used by the script, (current/peak)
 *
 * @return string memory used
 */
function get_memory_used()
{
    return(
        ceil(memory_get_usage() / 1024 / 1024) . '/' .
        ceil(memory_get_peak_usage() / 1024 / 1024));
}


/**
 * Execute a command and return streams as an array of
 * stdin, stdout, stderr
 *
 * @param  string $cmd command to execute
 * @return array|false array $streams | boolean false if failure
 * @see    https://secure.php.net/manual/en/function.proc-open.php
 */
function shell_execute($cmd)
{
    $process = proc_open(
        $cmd,
        [
        ['pipe', 'r'],
        ['pipe', 'w'],
        ['pipe', 'w']
        ], $pipes
    );
    if (is_resource($process)) {
        $streams = [];
        foreach ($pipes as $p => $v) {
            $streams[] = stream_get_contents($pipes[$p]);
        }
        proc_close($process);
        return [
            'stdin'  => $streams[0],
            'stdout' => $streams[1],
            'stderr' => $streams[2]
        ];
    }
    return false;
}


/**
 * Execute a command and return output of stdout or throw exception of stderr
 *
 * @param  string $cmd command to execute
 * @param  boolean $split split returned results? default on newline
 * @param  string $exp regular expression to preg_split to split on
 * @return mixed string $stdout | Exception if failure
 * @see    shell_execute($cmd)
 */
function cmd_execute($cmd, $split = true, $exp = "/\n/")
{
    $result = shell_execute($cmd);
    if (!empty($result['stderr'])) {
        throw new Exception($result['stderr']);
    }
    $data = $result['stdout'];
    if (empty($split) || empty($exp) || empty($data)) {
        return $data;
    }
    return preg_split($exp, $data);
}


/**
 * Encode array character encoding recursively
 *
 * @param mixed $data
 * @param string $to_charset convert to encoding
 * @param string $from_charset convert from encoding
 * @return mixed
 */
function to_charset($data, $to_charset = 'UTF-8', $from_charset = 'auto')
{
    if (is_numeric($data)) {
        $float = (string) (float) $data;
        if (is_int($data)) {
            return (int) $data;
        } else if (is_float($data) || $data === $float) {
            return (float) $data;
        } else {
            return (int) $data;
        }
    } else if (is_string($data)) {
        return mb_convert_encoding($data, $to_charset, $from_charset);
    } else if (is_array($data)) {
        foreach ($data as $key => $value) {
            $data[$key] = to_charset($value, $to_charset, $from_charset);
        }
    } else if (is_object($data)) {
        foreach ($data as $key => $value) {
            $data->$key = to_charset($value, $to_charset, $from_charset);
        }
    }
    return $data;
}


/**
 * Load a json file and return a php array of the content
 *
 * @param  string $file the json filename
 * @return string|array error string or data array
 */
function json_load($file)
{
    $data = [];
    if (file_exists($file)) {
        $data = to_charset(file_get_contents($file));
        $data = json_decode(
            mb_convert_encoding($data, 'UTF-8', "auto"), true, 512,
            JSON_OBJECT_AS_ARRAY || JSON_BIGINT_AS_STRING
        );
    }
    if (null === $data) {
        return json_last_error_msg();
    }
    if (is_array($data)) {
        $data = to_charset($data);
    }
    return $data;
}


/**
 * Save data array to a json
 *
 * @param  string $file the json filename
 * @param  array $data data to save
 * @param  string optional $prepend string to prepend in the file
 * @param  string optional $append string to append to the file
 * @return boolean true|string TRUE if success or string error message
 */
function json_save($file, $data, $prepend = '', $append = '')
{
    if (empty($data)) {
        return 'No data to write to file.';
    }
    if (is_array($data)) {
        $data = to_charset($data);
    }
    if (!file_put_contents($file,
            $prepend . json_encode($data, JSON_PRETTY_PRINT) . $append)) {
        $error = json_last_error_msg();
        if (empty($error)) {
            $error = sprintf("Unknown Error writing file: '%s' (Prepend: '%s', Append: '%s')",
                $file, $prepend, $append);
        }
        return $error;
    }
    return true;
}


/**
 * Read in geonames cities TSV file
 *
 * @param optional int $id id of city to find
 * @return array of cities or city
 * @see http://download.geonames.org/export/dump/
 * @url http://www.geonames.org/export/
 */
function getCities($id = 0) {
    $fh = fopen(FILE_TSV_CITIES, 'r');
    $cities = [];
    if (!empty($id)) {
        $id = (int) $id;
        if ($id < 1) {
            return [];
        }
    }
    while ($data = fgetcsv($fh, 0, "\t")) {
        $data = to_charset($data);
        $geoname_id = (int) $data[0];
        $city = [
            'id' => $data[0],
            'country_code' => $data[8],
            'state' => $data[10],
            'city' => $data[1],
            'ascii' => $data[2],
            'names' => preg_split('/,/', $data[3]),
            'latitude' => (float) $data[4],
            'longitude' => (float) $data[5],
            'elevation' => (int) $data[16],
            'population' => empty($data[15]) ? null : (int) $data[15],
            'timezone' => $data[17],
        ];
        if (empty($id)) {
            $cities[$geoname_id] = $city;
        } else if ($id === $geoname_id) {
            $cities = [$geoname_id => $city];
            break;
        }
    }
    fclose($fh);
    return $cities;
}
