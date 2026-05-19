<?php

declare(strict_types=1);

if (!extension_loaded('mongodb')) {
    require __DIR__ . '/mongodb.stub.php';
}
