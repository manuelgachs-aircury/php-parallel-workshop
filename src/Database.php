<?php declare(strict_types=1);

class Database
{
    private SQLite3 $connection;

    public function __construct(string $dbName, bool $tear_down = true, bool $init = true)
    {
        $this->connection = new SQLite3($dbName);

        if ($tear_down) {
            $this->tear_down();
        }

        if ($init) {
            $this->init_db();
        }
    }

    private function tear_down(): void
    {
        // Destroy everything
        $this->connection->exec("DROP TABLE IF EXISTS cards");
    }

    private function init_db(): void
    {
        $this->connection->exec("CREATE TABLE IF NOT EXISTS cards (
            id INTEGER PRIMARY KEY,
            scryfall_id TEXT,
            name TEXT,
            set_code TEXT,
            collector_number TEXT
        )");
    }

    public function insert_card(string $scryfall_id, string $name, string $set_code, string $collector_number): void
    {
        $this->connection->busyTimeout(1000000); // Wait for the DB to be unlocked
        $stmt = $this->connection->prepare("
            INSERT INTO cards (scryfall_id, name, set_code, collector_number) VALUES 
            (:scryfall_id, :name, :set_code, :collector_number)
        ");

        $stmt->bindParam(':scryfall_id', $scryfall_id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':set_code', $set_code);
        $stmt->bindParam(':collector_number', $collector_number);
        $stmt->execute();
    }
}