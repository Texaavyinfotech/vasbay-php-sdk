<?php

/*
 * Voice broadcast (OBD): play a recorded audio file to a number. Places a real call and uses voice credits.
 * The account needs the voice service; upload the audio first in the panel (Voice Call › Audio files).
 *
 *   php examples/voice-call.php               # lists audio files and caller IDs
 *   php examples/voice-call.php 9895XXXXXX 12 # calls the number with audio file 12
 */

require __DIR__ . '/bootstrap.php';

use Texaavy\Vasbay\Exceptions\VasbayException;

$vasbay = vasbay();

try {
    if (! isset($argv[2])) {
        foreach ($vasbay->voice()->files() as $f) {
            printf("file %d: %s (%d s)\n", $f['id'], $f['name'], $f['seconds']);
        }
        echo 'Caller IDs: ', json_encode($vasbay->voice()->callerIds()->data()), PHP_EOL;
        exit(0);
    }

    $call = $vasbay->voice()->send(['to' => [$argv[1]], 'fileId' => (int) $argv[2], 'name' => 'SDK example']);
    echo 'Campaign ', $call['id'], ': ', $call['state'], PHP_EOL;
    if ($call->meta('ignored_numbers')) {
        echo 'Ignored: ', implode(', ', $call->meta('ignored_numbers')), PHP_EOL;
    }
} catch (VasbayException $e) {
    fwrite(STDERR, 'Failed: [' . $e->getErrorCode() . '] ' . $e->getMessage() . PHP_EOL);
    exit(1);
}
