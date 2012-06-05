<?php

// create a large file then randomly read chunks from it (delete it when done)

// note: this will not really test actual disk I/O as the file
// will most likely be cached by the kernel.

function test_disk_random_read($base) {
    test_start(__FUNCTION__);

    $tmp_filename = tempnam('/tmp', 'phpbench_random_read');
    write_then_random_read_file($tmp_filename);
    unlink($tmp_filename);

    return test_end(__FUNCTION__);
}

function test_disk_random_read_enabled() {
    return TRUE;
}

function write_then_random_read_file($file_name) {
    $file_size = 50 * (1024*1024); // 50 MB
    $write_chunk_size = 4  * 1024; // write in 4kb chunks
    $read_chunk_size  = 512;       // read in 512b chunks

    $data = str_repeat('a', $write_chunk_size);

    // create/write file.
    $fh = fopen($file_name, 'w');
    for ($i = 0; $i < $file_size; $i += $write_chunk_size) {
        fwrite($fh, $data);
    }
    fclose($fh);

    // jump around and do small reads from the file
    $fh = fopen($file_name, 'r');
    for ($i = 0; $i < 1000; $i++) {
        fseek($fh, rand(0, ($file_size - $read_chunk_size)));
        $tmp_buff = fread($fh, $read_chunk_size);
    }
    fclose($fh);
}

// testing
if (@basename($argv[0]) == basename(__FILE__)) {
    $file_name = './test.dat';
    write_then_random_read_file($file_name);
    echo "created file: $file_name";
}

?>
