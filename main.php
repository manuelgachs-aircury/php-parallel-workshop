<?php declare(strict_types=1);

require_once 'producer_consumer.php';
require_once 'thread_pool.php';

$to_run = $argv[1];

if ('pc' ===  $to_run) {
    run_producer_consumer('cards.db', 6, 8, 5);
} else {
    run_thread_pool(4, 12);
}
