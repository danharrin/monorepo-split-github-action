<?php

declare(strict_types=1);

$gitDiffIndex = exec('git diff-index --quiet HEAD', $output, $resultCode);

var_dump($resultCode);
if ($resultCode === 1) {
    echo 'some files have changed' . PHP_EOL;
} else {
    echo 'no files to change' . PHP_EOL;
}
