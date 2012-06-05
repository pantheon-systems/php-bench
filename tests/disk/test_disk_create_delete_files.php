<?php

// create lots of small temp files then delete them

function test_disk_create_delete_files($base) {
    $iters = $base * 10;
    test_start(__FUNCTION__);

    do {
        $tmp_filename = tempnam('/tmp', 'phpbench');
        create_delete_temp_file($tmp_filename);
    } while (--$iters !== 0);

    return test_end(__FUNCTION__);
}

function test_disk_create_delete_files_enabled() {
    return TRUE;
}

function create_delete_temp_file($file_name) {
    // write 64KB files in 4KB chunks
    $file_size  = 64 * 1024;
    $chunk_size = 4 * 1024;

    $data = str_repeat('a', $chunk_size);
    $fh = fopen($file_name, 'w');
    for ($i = 0; $i < $file_size; $i += $chunk_size) {
        fwrite($fh, $data);
    }
    fclose($fh);
    unlink($file_name);
}

// testing
if (@basename($argv[0]) == basename(__FILE__)) {
    create_delete_temp_file('./test.dat');
}

?>
