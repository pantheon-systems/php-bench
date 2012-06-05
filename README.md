PHPBench - Pantheon's Fork
=========================

This is Pantheon's version of PHPBench, based on PHPBench 0.8.1 from http://phpbench.pureftpd.org


PHPBench is a benchmark suite for PHP.
  
It performs a large number of simple tests in order to bench various
aspects of the PHP interpreter.

PHPBench can be used to compare hardware, operating systems, PHP versions,
PHP accelerators and caches, compiler options, etc.

Tests are separated into related suites, such as 'cpu', 'disk', etc. Custom
tests can be easily added to the suite.

Basic Usage (cli)
-----------------

Tests live in the `tests/<suite_name>` directory.

To run phpbench with all defaults including the default test suite ('cpu'):

  $ php ./index.php

To run the 'disk' test suite with 200 iterations:

  $ php ./index.php suite=disk iterations=200

Want JSON output?

  $ php ./index.php format=json

List available test suites:

  $ php ./index action=list_suites

Basic Usage (Web)
-----------------

Similar to CLI usage. Parameters are passed via GET:

  http://<server>/index.php?suite=disk

List available suites:

  http://<server>/index.php?action=list_suites&format=json

Returns JSON array:

  ['cpu','disk']