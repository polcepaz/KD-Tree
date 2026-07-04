<?php

/**
 * Implementa metricas de distancia para espacios multidimensionales.
 * Proporciona metodos para calcular la distancia euclidiana entre puntos.
 */
class Distance
{
    /**
     * Calcula la distancia euclidiana entre dos puntos.
     *
     * @param Point $a Primer punto
     * @param Point $b Segundo punto
     * @return float Distancia euclidiana
     */
    public static function euclidean(Point $a, Point $b): float
    {
        $sum = 0.0;
        $dims = min($a->getDimensions(), $b->getDimensions());

        for ($i = 0; $i < $dims; $i++) {
            $diff = $a->getCoordinate($i) - $b->getCoordinate($i);
            $sum += $diff * $diff;
        }

        return sqrt($sum);
    }

    /**
     * Calcula la distancia euclidiana al cuadrado (evita la raiz cuadrada para comparaciones).
     *
     * @param Point $a Primer punto
     * @param Point $b Segundo punto
     * @return float Distancia euclidiana al cuadrado
     */
    public static function squaredEuclidean(Point $a, Point $b): float
    {
        $sum = 0.0;
        $dims = min($a->getDimensions(), $b->getDimensions());

        for ($i = 0; $i < $dims; $i++) {
            $diff = $a->getCoordinate($i) - $b->getCoordinate($i);
            $sum += $diff * $diff;
        }

        return $sum;
    }

    /**
     * Calcula la distancia Manhattan entre dos puntos.
     *
     * @param Point $a Primer punto
     * @param Point $b Segundo punto
     * @return float Distancia Manhattan
     */
    public static function manhattan(Point $a, Point $b): float
    {
        $sum = 0.0;
        $dims = min($a->getDimensions(), $b->getDimensions());

        for ($i = 0; $i < $dims; $i++) {
            $sum += abs($a->getCoordinate($i) - $b->getCoordinate($i));
        }

        return $sum;
    }
}
