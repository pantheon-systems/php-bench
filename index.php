<?php

ignore_user_abort(TRUE);
error_reporting(E_ALL);
set_time_limit(0);
ob_implicit_flush(1);
header("Cache-Control: private, max-age=0");

define('PHPBENCH_VERSION', '0.8.1-pantheon1');
define('CSV_SEP', ',');
define('CSV_NL', "\n");
define('DEFAULT_BASE', 100);
define('MIN_BASE', 50);
define('DEFAULT_TEST_SUITE', 'cpu');
define('DEFAULT_ACTION', 'run');

function test_start($func) {
    global $GLOBAL_TEST_FUNC;
    global $GLOBAL_TEST_START_TIME;

    $GLOBAL_TEST_FUNC = $func;
    //echo sprintf('%34s', $func) . "\t";
    flush();
    list($usec, $sec) = explode(' ', microtime());
    $GLOBAL_TEST_START_TIME = $usec + $sec;
}

function test_end($func) {
    global $GLOBAL_TEST_FUNC;
    global $GLOBAL_TEST_START_TIME;

    list($usec, $sec) = explode(' ', microtime());
    $now = $usec + $sec;
    if ($func !== $GLOBAL_TEST_FUNC) {
    trigger_error('Wrong func: [' . $func . '] ' .
              'vs ' . $GLOBAL_TEST_FUNC);
    return FALSE;
    }
    if ($now < $GLOBAL_TEST_START_TIME) {
    trigger_error('Wrong func: [' . $func . '] ' .
              'vs ' . $GLOBAL_TEST_FUNC);
    return FALSE;
    }
    $duration = $now - $GLOBAL_TEST_START_TIME;
    //echo sprintf('%9.04f', $duration) . ' seconds.' . "\n";

    return $duration;
}

function test_regression($func) {
    trigger_error('* REGRESSION * [' . $func . ']' . "\n");
    die();
}

function do_tests($base, &$tests_list, &$results) {
    foreach ($tests_list as $test) {
    $results[$test] = call_user_func($test, $base, $results);
    }
}

function load_test($tests_dir, &$tests_list) {
    if (($dir = @opendir($tests_dir)) === FALSE) {
    return FALSE;
    }
    $matches = array();
    while (($entry = readdir($dir)) !== FALSE) {
    if (preg_match('/^(test_.+)[.]php$/i', $entry, $matches) <= 0) {
        continue;
    }
    $test_name = $matches[1];
    include_once($tests_dir . '/' . $entry);
    //echo 'Test [' . $test_name . '] ';
    flush();
    if (!function_exists($test_name . '_enabled')) {
        echo 'INVALID !' . "\n";
        continue;
    }
    if (call_user_func($test_name . '_enabled') !== TRUE) {
        echo 'disabled.' . "\n";
        continue;
    }
    if (!function_exists($test_name)) {
        echo 'BROKEN !' . "\n";
        continue;
    }
    array_push($tests_list, $test_name);
    //echo 'enabled.' . "\n";
    }
    closedir($dir);

    return TRUE;
}

function load_tests($test_suite, &$tests_list) {
    $ret = FALSE;
    $tests_path = "tests/$test_suite";

    if (!is_dir($tests_path)) {
        echo "Not a valid test suite. (path not found: $tests_path)\n";
        return FALSE;
    }
    if (load_test("tests/$test_suite", $tests_list) === TRUE) {
        $ret = TRUE;
    }
    if (count($tests_list) <= 0) {
        return FALSE;
    }
    asort($tests_list);

    return $ret;
}

function get_suites() {
    $suites = array();
    if ($dh = opendir('tests')) {
        while (false !== ($entry = readdir($dh))) {
            if ($entry != "." && $entry != "..") {
                $suites[] = $entry;
            }
        }
        closedir($dh);
    }
    return $suites;
}

function output_suites($suites, $format) {
    switch($format) {
        case 'json':
            echo json_encode($suites);
            break;
        case 'csv':
            echo "suite list doesn't support CSV right now\n";
            break;
        case 'html':
        default:
            echo join("\n", $suites) . "\n";
            break;
    }
}

function generate_summary($test_suite, $iterations, &$results) {
    $output = array();
    $output['time'] = time();
    $output['date'] = date(DATE_RFC822);
    $output['total_time'] = 0.0;
    $output['iterations'] = $iterations;
    $output['results'] = $results;
    $output['hostname'] = gethostname();
    $output['host'] = gethostbyname(gethostname());
    $output['test_suite'] = $test_suite;
    foreach ($results as $test => $time) {
      $output['total_time'] += $time;
    }
    if ($output['total_time'] <= 0.0) {
      die('Not enough iterations, please try with more.' . "\n");
    }
    $output['percentile_times'] = array();
    foreach ($results as $test => $time) {
      $output['percentile_times'][$test] = $time * 100.0 / $output['total_time'];
    }
    $output['score'] = round((float) $iterations * 10.0 / $output['total_time']);
    if (function_exists('php_uname')) {
      $output['php_uname'] = php_uname();
    }
    if (function_exists('phpversion')) {
      $output['phpversion'] = phpversion();
    }
    return $output;
}

function output_summary($output, $format) {
    if ($format == 'json') {
        echo json_encode($output);
    } else {
        output_summary_html($output);
    }
}

function output_summary_html($output) {
    echo '<h2>Php Benchmark: '.round($output['score']).'</h2>';
    echo '<ul>';
    echo '<li>Date: ' . $output['date'] . "</li>\n";
    echo '<li>System: ' . $output['php_uname'] . "</li>\n";
    echo '<li>Hostname: ' . $output['hostname'] . "</li>\n";
    echo '<li>Host: ' . $output['host'] . "</li>\n";
    echo '<li>PHP version: ' . $output['phpversion'] . "</li>\n";
    echo '<li>Iterations: ' . $output['iterations'] . "</li>\n";
    echo '<li>Test Suite: ' . $output['test_suite'] . "</li>\n";
    echo
      '<li>PHPBench Version: ' . PHPBENCH_VERSION . "</li>\n" .
      '<li>Tests: ' . count($output['results']) . "</li>\n" .
      '<li>Total time: ' . round($output['total_time']) . ' seconds' . "</li>\n";
    echo "</ul>\n";
    echo "<h3>Usage:</h3>\n";
    echo "<ul>\n";
    echo "<li>Use ?iterations=999 to specify iterations</li>\n";
    echo "<li>Use ?action=list_suites to list available test suites</li>\n";
    echo "<li>Use ?suite=name to specify test suite to run (default: " . DEFAULT_TEST_SUITE . ")</li>\n";
    echo "<li>Use ?format=json to output json results</li>\n";
    echo '</ul>';
}

// if run from command line, convert argv to $_GET so that we can run like this:
//  $ php index.php suite=disk iterations=100
if (PHP_SAPI === 'cli') {
    parse_str(implode('&', array_slice($argv, 1)), $_GET);    
}

$test_suite = DEFAULT_TEST_SUITE;
if (array_key_exists('suite', $_GET)) {
    $test_suite = $_GET['suite'];
}

$iterations = DEFAULT_BASE;
if (array_key_exists('iterations', $_GET)) {
  $iterations = intval($_GET['iterations']);
}

$tests_list = array();
$results = array();
if (load_tests($test_suite, $tests_list) === FALSE) {
    die('Unable to load tests');
}

// Get Output Format
$output_format = 'html';
if (array_key_exists('format', $_GET)) {
    $output_format = $_GET['format'];
}

// Run the specified action (or default action)
$action = DEFAULT_ACTION;
if (array_key_exists('action', $_GET)) {
    $action = $_GET['action'];
}
switch($action) {
    case 'list_suites':
        $suites = get_suites();
        output_suites($suites, $output_format);
        break;
    case 'run':
    default:
        do_tests($iterations, $tests_list, $results);
        $summary = generate_summary($test_suite, $iterations, $results);
        output_summary($summary, $output_format);
        break;
}

?>