<?php

/**
 * Representa un punto en el espacio k-dimensional.
 * Cada punto contiene coordenadas multidimensionales y metadatos asociados.
 */
class Point
{
    /** @var array Coordenadas del punto */
    private array $coordinates;

    /** @var int Identificador del registro en base de datos */
    private int $id;

    /** @var string Nombre del servidor */
    private string $serverName;

    /** @var string|null Fecha de creacion */
    private ?string $createdAt;

    /**
     * @param array $coordinates Vector de coordenadas
     * @param int $id Identificador unico
     * @param string $serverName Nombre del servidor
     * @param string|null $createdAt Fecha de creacion
     */
    public function __construct(
        array $coordinates,
        int $id = 0,
        string $serverName = '',
        ?string $createdAt = null
    ) {
        $this->coordinates = $coordinates;
        $this->id = $id;
        $this->serverName = $serverName;
        $this->createdAt = $createdAt;
    }

    /**
     * Obtiene el valor de una dimension especifica.
     *
     * @param int $dimension Indice de la dimension
     * @return float
     */
    public function getCoordinate(int $dimension): float
    {
        return (float)($this->coordinates[$dimension] ?? 0);
    }

    /**
     * Obtiene todas las coordenadas.
     *
     * @return array
     */
    public function getCoordinates(): array
    {
        return $this->coordinates;
    }

    /**
     * Obtiene la dimensionalidad del punto.
     *
     * @return int
     */
    public function getDimensions(): int
    {
        return count($this->coordinates);
    }

    /**
     * Obtiene el id del registro.
     *
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Obtiene el nombre del servidor.
     *
     * @return string
     */
    public function getServerName(): string
    {
        return $this->serverName;
    }

    /**
     * Obtiene la fecha de creacion.
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    /**
     * Convierte el punto a un array asociativo.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'server_name' => $this->serverName,
            'cpu_usage' => $this->coordinates[0] ?? 0,
            'memory_usage' => $this->coordinates[1] ?? 0,
            'hana_memory' => $this->coordinates[2] ?? 0,
            'dialog_response_time' => $this->coordinates[3] ?? 0,
            'work_processes' => (int)($this->coordinates[4] ?? 0),
            'enqueue_locks' => (int)($this->coordinates[5] ?? 0),
            'created_at' => $this->createdAt,
        ];
    }

    /**
     * Crea un punto desde un array asociativo (fila de base de datos).
     *
     * @param array $row
     * @return Point
     */
    public static function fromDatabaseRow(array $row): Point
    {
        return new self(
            [
                (float)$row['cpu_usage'],
                (float)$row['memory_usage'],
                (float)$row['hana_memory'],
                (float)$row['dialog_response_time'],
                (int)$row['work_processes'],
                (int)$row['enqueue_locks'],
            ],
            (int)$row['id'],
            $row['server_name'],
            $row['created_at'] ?? null
        );
    }
}
