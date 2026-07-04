<?php

/**
 * Representa un nodo del KD-Tree.
 * Cada nodo contiene un punto, referencias a los subarboles izquierdo y derecho,
 * la dimension de corte y el nivel en el arbol.
 */
class KDNode
{
    /** @var Point Punto almacenado en el nodo */
    public Point $point;

    /** @var KDNode|null Subarbol izquierdo */
    public ?KDNode $left;

    /** @var KDNode|null Subarbol derecho */
    public ?KDNode $right;

    /** @var int Dimension utilizada para la division en este nivel */
    public int $dimension;

    /** @var int Nivel del nodo en el arbol (0 = raiz) */
    public int $level;

    /** @var float Valor de corte en la dimension correspondiente */
    public float $splitValue;

    /**
     * @param Point $point Punto del nodo
     * @param int $dimension Dimension de corte
     * @param int $level Nivel del nodo
     */
    public function __construct(Point $point, int $dimension = 0, int $level = 0)
    {
        $this->point = $point;
        $this->left = null;
        $this->right = null;
        $this->dimension = $dimension;
        $this->level = $level;
        $this->splitValue = $point->getCoordinate($dimension);
    }

    /**
     * Determina si este nodo es una hoja.
     *
     * @return bool
     */
    public function isLeaf(): bool
    {
        return $this->left === null && $this->right === null;
    }

    /**
     * Convierte el nodo a un array para serializacion JSON.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'point' => $this->point->toArray(),
            'dimension' => $this->dimension,
            'level' => $this->level,
            'split_value' => $this->splitValue,
            'left' => $this->left ? $this->left->toArray() : null,
            'right' => $this->right ? $this->right->toArray() : null,
        ];
    }
}
