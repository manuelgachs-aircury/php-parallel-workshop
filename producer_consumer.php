<?php declare(strict_types=1);

require_once 'src/Database.php';
require_once 'src/Scryfall.php';

use parallel\{Runtime, Channel, Future};

// ---------------------------------------------------------------------------------------------------------------------
// Class
class ProducerConsumer
{
    static function send_msg(string $msg): void
    {
        $now = date_format(date_create(), 'H:i:s.u');
        printf("[%s] %s".PHP_EOL, $now, $msg);
    }

    static function produce(Channel $buffer, int $n_cards): void
    {
        $client = new Scryfall();
        for ($i = 0; $i < $n_cards; ++$i) {
            $card = $client->fetch_random();
            $buffer->send($card);
            ProducerConsumer::send_msg("Producer sent a card");
        }

        // We need to send a signal that the producer has finished
        $buffer->send(null);
        ProducerConsumer::send_msg("Producer stopping");
    }

    static function consume(Channel $buffer, Database $db, int $timeout): void
    {
        while (true) {
            sleep($timeout);
            $card = $buffer->recv();
            ProducerConsumer::send_msg("Consumer received a card");

            // The producer has finished
            if (null === $card) {
                $buffer->close();
                ProducerConsumer::send_msg("Consumer stopping");
                break;
            }

            $db->insert_card($card['scryfall_id'], $card['name'], $card['set_code'], $card['collector_number']);
        }
    }
}

// ---------------------------------------------------------------------------------------------------------------------
// Pattern
function run_producer_consumer(string $db_name, int $buffer_size, int $n_cards, int $consumer_timeout = 0): void
{
    $producer = new Runtime();
    $consumer = new Runtime();

    $buffer = new Channel($buffer_size);

    $producer_future = $producer->run(function () use ($buffer, $n_cards) {
        require_once 'producer_consumer.php';
        ProducerConsumer::send_msg("Starting producer");
        ProducerConsumer::produce($buffer, $n_cards);
    });

    $consumer_future =  $consumer->run(function () use ($buffer, $db_name, $consumer_timeout) {
        require_once 'producer_consumer.php';
        ProducerConsumer::send_msg("Starting consumer");
        $db = new Database($db_name); // Parallel does not accept non-serializable variables in the closure
        ProducerConsumer::consume($buffer, $db, $consumer_timeout);
    });

    $producer_future->value();
    $consumer_future->value();
    $producer->close();
    $consumer->close();
}
