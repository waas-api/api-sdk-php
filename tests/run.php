<?php

require __DIR__ . '/bootstrap.php';
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$passed = 0;
$failed = 0;
foreach (array_merge(glob(__DIR__ . '/Unit/*.php'), glob(__DIR__ . '/Contract/*.php')) as $file) {
    foreach (require $file as $name => $test) {
        try {
            $test();
            $passed++;
            echo 'PASS ' . $name . "\n";
        } catch (\Exception $e) {
            $failed++;
            fwrite(STDERR, 'FAIL ' . $name . ': ' . $e . "\n");
        } catch (Throwable $e) {
            $failed++;
            fwrite(STDERR, 'FAIL ' . $name . ': ' . $e . "\n");
        }
    }
}
echo "{$passed} passed, {$failed} failed\n";
exit($failed === 0 ? 0 : 1);
