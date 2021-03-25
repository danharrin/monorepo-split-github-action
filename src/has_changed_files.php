<?php

declare(strict_types=1);

exec('git diff-index --quiet HEAD', $output, $resultCode);

// 1 = changed files
// 0 = no changed files
return (int) $resultCode;
