<?php

require_once __DIR__ . '/Database.php';

/**
 * Modelo de acceso a datos para la tabla server_metrics.
 * Implementa las operaciones CRUD basicas.
 */
class ServerMetrics
{
    /** @var Database Instancia de base de datos */
    private Database $db;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Obtiene todos los registros.
     *
     * @param string $orderBy Campo de ordenamiento
     * @param string $order Direccion ASC o DESC
     * @param int|null $limit Limite de registros
     * @return array
     */
    public function getAll(string $orderBy = 'created_at', string $order = 'DESC', ?int $limit = null): array
    {
        $sql = "SELECT * FROM server_metrics ORDER BY {$orderBy} {$order}";
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }
        return $this->db->query($sql);
    }

    /**
     * Obtiene un registro por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function getById(int $id): ?array
    {
        return $this->db->queryOne(
            "SELECT * FROM server_metrics WHERE id = ?",
            [$id]
        );
    }

    /**
     * Inserta un nuevo registro.
     *
     * @param array $data Datos del servidor
     * @return int ID del nuevo registro
     */
    public function insert(array $data): int
    {
        $this->db->execute(
            "INSERT INTO server_metrics (server_name, cpu_usage, memory_usage, hana_memory, dialog_response_time, work_processes, enqueue_locks) 
             VALUES (?, ?, ?, ?, ?, ?, ?)",
            [
                $data['server_name'],
                $data['cpu_usage'],
                $data['memory_usage'],
                $data['hana_memory'],
                $data['dialog_response_time'],
                $data['work_processes'],
                $data['enqueue_locks'],
            ]
        );

        return (int)$this->db->lastInsertId();
    }

    /**
     * Actualiza un registro existente.
     *
     * @param int $id ID del registro
     * @param array $data Datos actualizados
     * @return bool True si se actualizo correctamente
     */
    public function update(int $id, array $data): bool
    {
        $affected = $this->db->execute(
            "UPDATE server_metrics SET 
                server_name = ?, cpu_usage = ?, memory_usage = ?, 
                hana_memory = ?, dialog_response_time = ?, 
                work_processes = ?, enqueue_locks = ?
             WHERE id = ?",
            [
                $data['server_name'],
                $data['cpu_usage'],
                $data['memory_usage'],
                $data['hana_memory'],
                $data['dialog_response_time'],
                $data['work_processes'],
                $data['enqueue_locks'],
                $id,
            ]
        );

        return $affected > 0;
    }

    /**
     * Elimina un registro por su ID.
     *
     * @param int $id
     * @return bool True si se elimino correctamente
     */
    public function delete(int $id): bool
    {
        $affected = $this->db->execute(
            "DELETE FROM server_metrics WHERE id = ?",
            [$id]
        );
        return $affected > 0;
    }

    /**
     * Obtiene todos los registros como array de arrays asociativos.
     *
     * @return array
     */
    public function getAllAsArray(): array
    {
        return $this->getAll('created_at', 'ASC');
    }

    /**
     * Obtiene el numero total de registros activos.
     *
     * @return int
     */
    public function count(): int
    {
        return $this->db->getTotalRecords();
    }

    /**
     * Obtiene registros paginados.
     *
     * @param int $page Numero de pagina
     * @param int $perPage Registros por pagina
     * @return array{data: array, total: int, page: int, per_page: int}
     */
    public function getPaginated(int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $total = $this->count();

        $sql = "SELECT * FROM server_metrics ORDER BY id ASC LIMIT ? OFFSET ?";
        $data = $this->db->query($sql, [$perPage, $offset]);

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage),
        ];
    }
}
