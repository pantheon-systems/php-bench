<?php

// create a large file then read it (delete it when done)

// note: this will not really test actual disk I/O as the file
// will most likely be cached by the kernel.

function test_disk_read_write_large_file($base) {
    //$iters = $base * 10;
    test_start(__FUNCTION__);

    $tmp_filename = tempnam('/tmp', 'phpbench_largefile');
    write_then_read_large_file($tmp_filename);
    unlink($tmp_filename);

    return test_end(__FUNCTION__);
}

function test_disk_read_write_large_file_enabled() {
    return TRUE;
}

function write_then_read_large_file($file_name) {
    $file_size = 100 * (1024*1024); // 100 MB
    $chunk_size = 4  * 1024; // write in 4kb chunks
    $data = str_repeat('a', $chunk_size);

    // create/write file.
    $fh = fopen($file_name, 'w');
    for ($i = 0; $i < $file_size; $i += $chunk_size) {
        fwrite($fh, $data);
    }
    fclose($fh);

    // read file back
    $fh = fopen($file_name, 'r');
    while (!feof($fh)) {
        $tmp_buff = fread($fh, $chunk_size * 2);
    }
    fclose($fh);
}

// testing
if (@basename($argv[0]) == basename(__FILE__)) {
    $file_name = './test.dat';
    write_then_read_large_file($file_name);
    echo "created file: $file_name";
}

?>
