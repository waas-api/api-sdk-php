<?php

/** SQLite integration example, requiring pdo_sqlite. Domain writes and receipt share one transaction. */
final class EventStore
{
    /**
     * @readonly
     * @var \PDO
     */
    private $db;
    public function __construct(PDO $db)
    {
        $this->db = $db;
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->exec('CREATE TABLE IF NOT EXISTS callback_receipts (event_key TEXT PRIMARY KEY, response TEXT NOT NULL)');
    }
    public function once($key, callable $applyBusiness)
    {
        $this->db->exec('BEGIN IMMEDIATE');
        try {
            $query = $this->db->prepare('SELECT response FROM callback_receipts WHERE event_key = ?');
            $query->execute([$key]);
            $existing = $query->fetchColumn();
            if ($existing !== false) {
                $this->db->exec('COMMIT');
                return json_decode($existing, true, 512, 0);
            }
            $response = $applyBusiness($this->db);
            $save = $this->db->prepare('INSERT INTO callback_receipts(event_key,response) VALUES (?,?)');
            $save->execute([$key, json_encode($response, 0)]);
            $this->db->exec('COMMIT');
            return $response;
        } catch (\Exception $e) {
            $this->db->exec('ROLLBACK');
            throw $e;
        } catch (Throwable $e) {
            $this->db->exec('ROLLBACK');
            throw $e;
        }
    }
}
