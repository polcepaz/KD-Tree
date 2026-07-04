<?php

/**
 * Capa de acceso a base de datos MySQL.
 * Implementa el patron Singleton para la conexion PDO.
 */
class Database
{
    /** @var Database|null Instancia unica (Singleton) */
    private static ?Database $instance = null;

    /** @var PDO Conexion PDO */
    private PDO $connection;

    /** @var string Host del servidor MySQL */
    private string $host;

    /** @var string Nombre de la base de datos */
    private string $dbName;

    /** @var string Usuario */
    private string $username;

    /** @var string Contrasena */
    private string $password;

    /** @var int Puerto */
    private int $port;

    /**
     * Constructor privado (Singleton).
     */
    private function __construct()
    {
        $this->host = getenv('DB_HOST') ?: 'localhost';
        $this->dbName = getenv('DB_NAME') ?: 'sap_monitor';
        $this->username = getenv('DB_USER') ?: 'root';
        $this->password = getenv('DB_PASS') ?: '';
        $this->port = (int)(getenv('DB_PORT') ?: 3306);

        $this->connect();
    }

    /**
     * Establece la conexion PDO.
     */
    private function connect(): void
    {
        $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbName};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        $this->connection = new PDO($dsn, $this->username, $this->password, $options);
    }

    /**
     * Obtiene la instancia unica de la base de datos.
     *
     * @return Database
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtiene la conexion PDO.
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Ejecuta una consulta SELECT con parametros opcionales.
     *
     * @param string $sql Consulta SQL
     * @param array $params Parametros vinculados
     * @return array Resultados
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Ejecuta una consulta y devuelve una sola fila.
     *
     * @param string $sql Consulta SQL
     * @param array $params Parametros vinculados
     * @return array|null Fila o null
     */
    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Ejecuta una sentencia INSERT/UPDATE/DELETE.
     *
     * @param string $sql Sentencia SQL
     * @param array $params Parametros vinculados
     * @return int Numero de filas afectadas
     */
    public function execute(string $sql, array $params = []): int
    {
        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    /**
     * Obtiene el ultimo ID insertado.
     *
     * @return string
     */
    public function lastInsertId(): string
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Inicia una transaccion.
     */
    public function beginTransaction(): void
    {
        $this->connection->beginTransaction();
    }

    /**
     * Confirma una transaccion.
     */
    public function commit(): void
    {
        $this->connection->commit();
    }

    /**
     * Revierte una transaccion.
     */
    public function rollback(): void
    {
        $this->connection->rollback();
    }

    /**
     * Obtiene el conteo total de registros.
     *
     * @return int
     */
    public function getTotalRecords(): int
    {
        $result = $this->queryOne("SELECT COUNT(*) as total FROM server_metrics");
        return (int)($result['total'] ?? 0);
    }

    /**
     * Evita la clonacion del Singleton.
     */
    private function __clone() {}
}
