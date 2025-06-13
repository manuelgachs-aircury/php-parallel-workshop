<?php declare(strict_types=1);

require_once 'src/Database.php';
require_once 'src/Scryfall.php';

use parallel\{Runtime, Channel, Future};

// ---------------------------------------------------------------------------------------------------------------------
// Class
class ThreadPool
{
    public static function send_msg(string $msg): void
    {
        $now = date("Y-m-d H:i:s");
        printf("[%s] %s".PHP_EOL, $now, $msg);
    }

    public static function perform_task(Channel $task_buffer, Channel $result_buffer, int $thread_name): void
    {
        while(true) {
            $task = $task_buffer->recv();

            if (null === $task) {
                self::send_msg("Stopping thread {$thread_name}");
                break;
            }

            $result = $task($thread_name);
            $result_buffer->send($result);
        }
    }

    public static function create_threads(int $n_threads): array
    {
        ThreadPool::send_msg("Creating threads");
        $threads = [];
        for ($i = 0; $i < $n_threads; ++$i) {
            $threads[] = new Runtime();
        }

        return $threads;
    }

    public static function receive_results(Channel $result_buffer, int $n_tasks): void
    {
        ThreadPool::send_msg("Recollecting task results");
        $results = [];
        for  ($i = 0; $i < $n_tasks; ++$i) {
            $results[] = $result_buffer->recv();
        }

        foreach ($results as $result) {
            ThreadPool::send_msg($result);
        }
    }

    public static function close_pool(array $futures, array $pool): void
    {
        ThreadPool::send_msg("Waiting for threads to finish");
        foreach ($futures as $future) {
            $future->value();
        }

        ThreadPool::send_msg("Closing threads");
        foreach ($pool as $pool_item) {
            $pool_item->close();
        }
    }
}

// ---------------------------------------------------------------------------------------------------------------------
// Pattern
function load_card(int $thread_name, int $task_num): string
{
    $start = microtime(true);
    // Make some task randomly harder
    if (24 > rand(1, 50)) {
        sleep(1);
    }

    $client = new Scryfall();
    $card = $client->fetch_random();

    $db = new Database('cards_tp.db', false, false);
    $db->insert_card($card['scryfall_id'], $card['name'], $card['set_code'], $card['collector_number']);

    $time_elapsed_secs = round(microtime(true) - $start, 4);
    ThreadPool::send_msg("Thread {$thread_name} finished task {$task_num} ({$time_elapsed_secs}s)");

    return "Card {$card['name']} | {$card['set_code']} fetched";
}

function run_thread_pool(int $max_threads, int $n_tasks): void
{
    $tasks_buffer = new Channel($n_tasks + $max_threads);
    $results_buffer = new Channel($n_tasks + $max_threads);
    $threads = ThreadPool::create_threads($max_threads);
    new Database('cards_tp.db');
    
    for ($i = 1; $i <= $n_tasks; ++$i) {
        ThreadPool::send_msg("Adding task {$i}");
        $tasks_buffer->send(function (int $thread_name) use ($i) {
            return load_card($thread_name, $i);
        });
    }

    // Add the stop signals for the threads
    for ($i = 1; $i <= $max_threads; ++$i) {
        $tasks_buffer->send(null);
    }

    $futures = [];
    foreach ($threads as $thread_name => $thread) {
        ThreadPool::send_msg("Starting tasks for thread {$thread_name}");
        $thread->run(function () use ($tasks_buffer, $results_buffer, $thread_name) {
            require_once 'thread_pool.php';
            ThreadPool::perform_task($tasks_buffer, $results_buffer, $thread_name);
        });
    }

    // Collect results
    ThreadPool::receive_results($results_buffer, $n_tasks);

    // End
    ThreadPool::close_pool($futures, $threads);
}
