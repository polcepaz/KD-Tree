# Implementación de un Índice Espacial Basado en KD-Tree para la Búsqueda Multidimensional de Estados Operacionales Similares en Infraestructura Corporativa SAP

**Trabajo Final de Investigación**  
Estructuras de Datos y Algoritmos Avanzados  

---

## 1. Resumen

*(Escribir un resumen de 200-300 palabras describiendo el problema, la solución y los resultados principales.)*

El monitoreo de infraestructura SAP genera grandes volúmenes de datos multidimensionales. Cada estado operacional de un servidor puede representarse mediante un vector de 6 dimensiones: CPU, Memoria, HANA Memory, Dialog Response Time, Work Processes y Enqueue Locks. Este trabajo implementa un índice espacial basado en KD-Tree desde cero, desarrollado completamente en PHP, con una interfaz web interactiva en JavaScript/Canvas. Los experimentos demuestran que el KD-Tree reduce el tiempo de búsqueda en **27x** comparado con búsqueda secuencial para 5000 registros, validando la eficiencia de las estructuras de partición del espacio en problemas reales de administración de infraestructura.

**Palabras clave**: KD-Tree, búsqueda multidimensional, nearest neighbor, SAP, estructuras de datos.

---

## 2. Introducción

### 2.1 Contexto
Las infraestructuras SAP corporativas generan métricas operacionales continuas. Encontrar estados similares entre miles de servidores es una operación frecuente para diagnóstico y optimización. La búsqueda secuencial O(n) se vuelve ineficiente a medida que el dataset crece.

### 2.2 Problema
Se requiere un mecanismo de indexación que permita búsquedas multidimensionales en tiempo sublineal sobre vectores de 6 dimensiones, visualizando además el funcionamiento interno del algoritmo con fines didácticos.

### 2.3 Objetivos
- **General**: Diseñar e implementar un índice espacial KD-Tree para búsquedas multidimensionales en métricas SAP.
- **Específicos**:
  1. Implementar el algoritmo KD-Tree desde cero en PHP (sin librerías externas)
  2. Desarrollar una interfaz web interactiva que visualice la estructura del árbol
  3. Realizar experimentos comparativos contra búsqueda secuencial
  4. Documentar la complejidad algorítmica y los resultados obtenidos

---

## 3. Marco Teórico

### 3.1 Estructuras de Partición del Espacio
*(Describir las estructuras de partición: Quadtrees, Octrees, BSP-Trees, KD-Trees. Comparar brevemente sus características.)*

### 3.2 KD-Tree (K-Dimensional Tree)
Un KD-Tree es un árbol binario de búsqueda que organiza puntos en un espacio k-dimensional. Cada nivel del árbol alterna la dimensión de corte, dividiendo el espacio mediante hiperplanos perpendiculares a cada eje coordenado.

**Construcción**:
1. En el nivel raíz, se ordenan los puntos por la dimensión 0 (CPU)
2. Se elige la mediana como nodo raíz
3. Puntos menores van al subárbol izquierdo, mayores al derecho
4. Se repite recursivamente alternando la dimensión (d0, d1, ..., d5, d0, ...)

**Complejidad**: O(n log n) promedio, O(n²) peor caso.

### 3.3 Nearest Neighbor Search (NN)
El algoritmo de búsqueda del vecino más cercano recorre el árbol desde la raíz:
1. Se calcula la distancia al punto actual
2. Se desciende por la rama más cercana según la dimensión de corte
3. Al retroceder, se verifica si |Δ| < mejor_distancia
4. Si es así, se explora la otra rama; si no, se **poda**

**Complejidad**: O(log n) promedio, O(n) peor caso.

### 3.4 Métrica de Distancia
Se utiliza la **Distancia Euclidiana** para espacios 6D:
```
d(P,Q) = √[Σ(pi - qi)²]  para i=0..5
```

### 3.5 La Maldición de la Dimensionalidad
A medida que aumenta el número de dimensiones, el espacio se vuelve cada vez más disperso, y la eficiencia de los KD-Trees se degrada. Para dimensiones muy altas (>20), se prefieren técnicas como LSH o HNSW.

---

## 4. Metodología

### 4.1 Arquitectura del Sistema
*(Diagrama de arquitectura: Usuario → HTML/CSS/JS → PHP → MySQL)*

### 4.2 Tecnologías Utilizadas
| Componente | Tecnología |
|---|---|
| Frontend | HTML5, CSS3, JavaScript ES6, Bootstrap 5, Canvas API |
| Backend | PHP 8 (POO, SOLID) |
| Base de Datos | MySQL 8 |
| Servidor | XAMPP (Apache 2.4 + PHP 8 + MySQL 8) |

### 4.3 Dataset
Se generaron 5000 registros simulados de servidores SAP con 6 dimensiones:
- **d0**: CPU (%)
- **d1**: Memoria (%)
- **d2**: SAP HANA Memory (%)
- **d3**: Dialog Response Time (ms)
- **d4**: Work Processes ocupados
- **d5**: Enqueue Locks

### 4.4 Diseño Experimental
- **Escalabilidad**: 100, 500, 1000, 3000, 5000 registros
- **Benchmark**: 50 consultas aleatorias comparando KD-Tree vs Secuencial
- **Variación de Dimensiones**: 2D, 4D, 6D sobre 1000 registros
- **Métricas**: tiempo de construcción, tiempo de búsqueda, comparaciones, altura del árbol, speedup

---

## 5. Resultados Experimentales

### 5.1 Escalabilidad

| Tamaño | Build (ms) | KD-Tree (ms) | Secuencial (ms) | Speedup | Altura |
|---|---|---|---|---|---|
| 100 | 1.27 | 0.094 | 0.17 | 1.84x | 7 |
| 500 | 7.93 | 0.075 | 0.80 | 10.61x | 10 |
| 1000 | 16.09 | 0.104 | 1.30 | 12.47x | 11 |
| 3000 | 58.50 | 0.122 | 4.06 | 33.26x | 12 |
| 5000 | 114.55 | 0.265 | 7.20 | 27.18x | 14 |

*(Incluir gráfico de escalabilidad: tiempo vs tamaño del dataset)*

### 5.2 Análisis de Complejidad

*(Gráfico comparando el crecimiento O(log n) del KD-Tree vs O(n) de la búsqueda secuencial)*

### 5.3 Variación de Dimensiones

*(Tabla y gráfico mostrando cómo cambia el rendimiento al variar de 2D a 6D)*

---

## 6. Discusión

### 6.1 Interpretación de Resultados
El KD-Tree demuestra una ventaja creciente a medida que el dataset aumenta. A 5000 registros, la búsqueda KD-Tree es **27x más rápida** que la secuencial, validando la complejidad O(log n) del algoritmo.

### 6.2 Limitaciones
- La construcción del árbol escala O(n log n), lo cual es aceptable para menos de 100,000 puntos
- En dimensiones muy altas (>20), el KD-Tree pierde eficacia (maldición de la dimensionalidad)
- La implementación actual en PHP introduce overhead de interpretación; una implementación en C++ sería aún más rápida

### 6.3 Trabajo Futuro
- Implementar R-Trees para datos geoespaciales
- Explorar HNSW para búsqueda en alta dimensionalidad
- Migrar el backend a Python/C# para mejor rendimiento

---

## 7. Conclusiones

1. Se implementó exitosamente un KD-Tree desde cero en PHP para 6 dimensiones
2. La búsqueda del vecino más cercano muestra speedup de **27x** para 5000 registros
3. La visualización interactiva en Canvas facilita la comprensión del algoritmo
4. Los experimentos confirman que el KD-Tree escala logarítmicamente, validando su aplicación en entornos SAP

---

## 8. Referencias

1. Bentley, J. L. (1975). "Multidimensional Binary Search Trees Used for Associative Searching". Communications of the ACM.
2. Friedman, J. H., Bentley, J. L., & Finkel, R. A. (1977). "An Algorithm for Finding Best Matches in Logarithmic Expected Time". ACM Transactions on Mathematical Software.
3. Malkov, Y. A., & Yashunin, D. A. (2018). "Efficient and Robust Approximate Nearest Neighbor Search Using Hierarchical Navigable Small World Graphs". IEEE Transactions on Pattern Analysis and Machine Intelligence.
4. Samet, H. (2006). "Foundations of Multidimensional and Metric Data Structures". Morgan Kaufmann.

---

**Nota**: Este documento es una plantilla. Los gráficos, tablas exactas y capturas de pantalla deben insertarse desde la aplicación en ejecución.
