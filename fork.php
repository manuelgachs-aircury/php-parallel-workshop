<?php declare(strict_types=1);

function simple_fork(): void
{
    $pid = pcntl_fork();
    if ($pid === -1) {
        throw new RuntimeException('Unable to fork process');
    } elseif ($pid) {
        printf("Parent process with PID %d" . PHP_EOL, $pid);
    } else {
        printf("Child process with PID %d" . PHP_EOL, $pid);
        exit(11);
    }

    pcntl_waitpid($pid, $status);
    if (pcntl_wifexited($status)) {
        printf("Child process exited with status %d" . PHP_EOL, pcntl_wexitstatus($status));
    }
}

function fork_loop(): void
{
    $max_processes = 4;
    $current_processes = 0;
    $pid = getmypid();

    while ($current_processes < $max_processes) {
        if (!$pid) {
            break;
        }
        $pid = pcntl_fork();
        $current_processes++;
        if ($pid) {
            printf("Process with PID %d forked (current_processes = %d)" . PHP_EOL, $pid, $current_processes);
        }
        sleep(1);
    }
    if ($pid === 0) {
        printf("Child process with PID (current_processes = %d)" . PHP_EOL, $current_processes);
        exit(0);
    }

    pcntl_wait($status);
}

function fork_loop_with_exception(): void
{
    $max_processes = 3;
    $current_processes = 0;
    $pid = getmypid();

    while ($current_processes < $max_processes) {
        if (!$pid) {
            break;
        }
        $pid = pcntl_fork();
        $current_processes++;
        if ($pid) {
            printf("Process with PID %d forked (current_processes = %d)" . PHP_EOL, $pid, $current_processes);
        }
        sleep(1);
    }
    if ($pid === 0) {
        // Random exception
        if (getmypid() % 2 === 0) {
            throw new RuntimeException("Random exception");
        }
        printf("Child process with PID exited correctly" . PHP_EOL);
        exit(0);
    }

    pcntl_wait($status);
}

//simple_fork();
//fork_loop();
fork_loop_with_exception();
