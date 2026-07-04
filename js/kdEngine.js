/**
 * kdEngine.js — Motor didactico de pasos para el KD-Tree.
 * Convierte el event_log de PHP en una secuencia de pasos visualizables.
 */
const KDEngine = {
    /**
     * Convierte event_log de construccion en pasos.
     * @param {Array} eventLog - del buildTree/rebuild PHP
     * @returns {Array} pasos
     */
    buildSteps: function (eventLog) {
        const steps = [];
        if (!eventLog || !eventLog.length) return steps;

        let visitedIds = [];

        for (const ev of eventLog) {
            const nodeMatch = ev.description.match(/ID=(\d+)/);
            const nodeId = nodeMatch ? parseInt(nodeMatch[1]) : null;
            const serverMatch = ev.description.match(/'([^']+)'/);
            const serverName = serverMatch ? serverMatch[1] : '';
            const dimMatch = ev.description.match(/d(\d)/);
            const dim = dimMatch ? parseInt(dimMatch[1]) : null;

            switch (ev.type) {
                case 'BUILD':
                    steps.push({
                        phase: 'build',
                        nodeId: null, nodeIds: [], visitedIds: [...visitedIds],
                        prunedIds: [], bestId: null,
                        message: ev.description,
                        dim: null, splitVal: null,
                    });
                    break;

                case 'SPLIT': {
                    const valMatch = ev.description.match(/umbral=([\d.]+)/);
                    const splitVal = valMatch ? parseFloat(valMatch[1]) : null;
                    const leftMatch = ev.description.match(/izquierda: (\d+)/);
                    const rightMatch = ev.description.match(/derecha: (\d+)/);
                    steps.push({
                        phase: 'split',
                        nodeId, nodeIds: nodeId ? [nodeId] : [],
                        visitedIds: [...visitedIds],
                        prunedIds: [], bestId: null,
                        message: `<strong>Nivel ${ev.metadata?.level ?? '?'}</strong> — ` +
                            `corte por d${dim} en ${splitVal?.toFixed(1)}<br>` +
                            `↳ ${leftMatch ? leftMatch[1] : '0'} izq · ${rightMatch ? rightMatch[1] : '0'} der`,
                        dim, splitVal,
                    });
                    if (nodeId) visitedIds.push(nodeId);
                    break;
                }

                case 'CREATE_NODE':
                    steps.push({
                        phase: 'create',
                        nodeId, nodeIds: nodeId ? [nodeId] : [],
                        visitedIds: [...visitedIds],
                        prunedIds: [], bestId: null,
                        message: `Nodo <strong>${serverName}</strong> (ID=${nodeId}) creado en nivel ${ev.metadata?.level ?? '?'}`,
                        dim, splitVal: ev.metadata?.split_value ?? null,
                    });
                    if (nodeId && !visitedIds.includes(nodeId)) visitedIds.push(nodeId);
                    break;

                case 'REBALANCE':
                    steps.push({
                        phase: 'build',
                        nodeId: null, nodeIds: [], visitedIds: [],
                        prunedIds: [], bestId: null,
                        message: ev.description,
                        dim: null, splitVal: null,
                    });
                    visitedIds = [];
                    break;

                default:
                    break;
            }
        }

        // Paso final
        if (steps.length > 0) {
            steps.push({
                phase: 'done',
                nodeId: null, nodeIds: [], visitedIds: [...visitedIds],
                prunedIds: [], bestId: null,
                message: `<strong>Construccion completada</strong> — ${visitedIds.length} nodos generados`,
                dim: null, splitVal: null,
            });
        }

        return steps;
    },

    /**
     * Convierte event_log de busqueda NN en pasos.
     * @param {Array} eventLog - del nearestNeighbor PHP
     * @returns {Array} pasos
     */
    searchSteps: function (eventLog) {
        const steps = [];
        if (!eventLog || !eventLog.length) return steps;

        let visitedIds = [];
        let prunedIds = [];
        let bestId = null;

        for (const ev of eventLog) {
            const nodeMatch = ev.description.match(/'([^']+)'/);
            const serverName = nodeMatch ? nodeMatch[1] : '';
            const idMatch = ev.description.match(/ID=(\d+)/) || ev.description.match(/'[^']+'.*?(\d+)/);
            let nodeId = null;
            if (idMatch) nodeId = parseInt(idMatch[1]);

            const distMatch = ev.description.match(/distancia[:\s]+([\d.]+)/);
            const dist = distMatch ? parseFloat(distMatch[1]) : null;

            switch (ev.type) {
                case 'NN_SEARCH':
                    steps.push({
                        phase: 'search-start',
                        nodeId: null, nodeIds: [], visitedIds: [],
                        prunedIds: [], bestId: null,
                        message: ev.description,
                        dim: null, splitVal: null,
                    });
                    break;

                case 'VISIT': {
                    // Extraer ID del mensaje
                    const parts = ev.description.match(/ID=(\d+)/);
                    const vid = parts ? parseInt(parts[1]) : null;
                    if (vid && !visitedIds.includes(vid)) visitedIds.push(vid);
                    steps.push({
                        phase: 'visit',
                        nodeId: vid, nodeIds: vid ? [vid] : [],
                        visitedIds: [...visitedIds],
                        prunedIds: [...prunedIds],
                        bestId, bestDist: null,
                        message: `Visitando <strong>${serverName}</strong>`,
                        dim: null, splitVal: null,
                    });
                    break;
                }

                case 'COMPARE': {
                    const dMatch = ev.description.match(/: ([\d.]+)$/);
                    const dVal = dMatch ? parseFloat(dMatch[1]) : null;
                    steps.push({
                        phase: 'compare',
                        nodeId, nodeIds: nodeId ? [nodeId] : [],
                        visitedIds: [...visitedIds],
                        prunedIds: [...prunedIds],
                        bestId, bestDist: dVal,
                        message: `Distancia: <strong>${dVal?.toFixed(2) ?? '?'}</strong>`,
                        dim: null, splitVal: null,
                    });
                    break;
                }

                case 'UPDATE_BEST': {
                    const bMatch = ev.description.match(/'([^']+)'/);
                    const bName = bMatch ? bMatch[1] : '';
                    const bdMatch = ev.description.match(/distancia\s+([\d.]+)/);
                    const bDist = bdMatch ? parseFloat(bdMatch[1]) : null;
                    // Extraer ID del servidor
                    const bidMatch = ev.description.match(/ID=(\d+)/);
                    if (bidMatch) bestId = parseInt(bidMatch[1]);
                    steps.push({
                        phase: 'update-best',
                        nodeId: bestId, nodeIds: bestId ? [bestId] : [],
                        visitedIds: [...visitedIds],
                        prunedIds: [...prunedIds],
                        bestId, bestDist: bDist,
                        message: `⭐ Nuevo mejor: <strong>${bName}</strong> a distancia ${bDist?.toFixed(2) ?? '?'}`,
                        dim: null, splitVal: null,
                    });
                    break;
                }

                case 'TRAVERSE':
                    steps.push({
                        phase: 'traverse',
                        nodeId, nodeIds: nodeId ? [nodeId] : [],
                        visitedIds: [...visitedIds],
                        prunedIds: [...prunedIds],
                        bestId, bestDist: null,
                        message: ev.description,
                        dim: null, splitVal: null,
                    });
                    break;

                case 'PRUNE':
                    // Extraer ID del nodo podado
                    prunedIds.push(nodeId);
                    steps.push({
                        phase: 'prune',
                        nodeId, nodeIds: prunedIds.length > 0 ? [prunedIds[prunedIds.length - 1]] : [],
                        visitedIds: [...visitedIds],
                        prunedIds: [...prunedIds],
                        bestId, bestDist: null,
                        message: `✂ <strong>Poda:</strong> ${ev.description.replace(/^Poda:?\s*/i, '')}`,
                        dim: null, splitVal: null,
                    });
                    break;

                case 'EXPLORE_OTHER':
                    steps.push({
                        phase: 'explore',
                        nodeId, nodeIds: nodeId ? [nodeId] : [],
                        visitedIds: [...visitedIds],
                        prunedIds: [...prunedIds],
                        bestId, bestDist: null,
                        message: `🔄 ${ev.description}`,
                        dim: null, splitVal: null,
                    });
                    break;

                case 'NN_RESULT': {
                    const fMatch = ev.description.match(/'([^']+)'/);
                    const fName = fMatch ? fMatch[1] : '';
                    const fdMatch = ev.description.match(/distancia\s+([\d.]+)/);
                    const fDist = fdMatch ? parseFloat(fdMatch[1]) : null;
                    steps.push({
                        phase: 'found',
                        nodeId: bestId, nodeIds: bestId ? [bestId] : [],
                        visitedIds: [...visitedIds],
                        prunedIds: [...prunedIds],
                        bestId, bestDist: fDist,
                        message: `<strong>Vecino mas cercano:</strong> ${fName} a distancia ${fDist?.toFixed(2) ?? '?'}`,
                        dim: null, splitVal: null,
                    });
                    break;
                }

                default:
                    break;
            }
        }

        return steps;
    },

    /**
     * Convierte event_log de busqueda por rango en pasos.
     */
    rangeSteps: function (eventLog) {
        const steps = [];
        if (!eventLog || !eventLog.length) return steps;

        let visitedIds = [];
        let foundIds = [];

        for (const ev of eventLog) {
            const nameMatch = ev.description.match(/'([^']+)'/);
            const serverName = nameMatch ? nameMatch[1] : '';
            const idMatch = ev.description.match(/ID=(\d+)/);
            const nodeId = idMatch ? parseInt(idMatch[1]) : null;
            const distMatch = ev.description.match(/distancia[=:]\s*([\d.]+)/);
            const dist = distMatch ? parseFloat(distMatch[1]) : null;

            switch (ev.type) {
                case 'RANGE_SEARCH':
                    steps.push({
                        phase: 'search-start',
                        nodeId: null, nodeIds: [], visitedIds: [],
                        foundIds: [], prunedIds: [],
                        message: ev.description,
                    });
                    break;

                case 'VISIT':
                    if (nodeId && !visitedIds.includes(nodeId)) visitedIds.push(nodeId);
                    steps.push({
                        phase: 'visit',
                        nodeId, nodeIds: nodeId ? [nodeId] : [],
                        visitedIds: [...visitedIds], foundIds: [...foundIds],
                        prunedIds: [],
                        message: `Visitando <strong>${serverName}</strong>`,
                    });
                    break;

                case 'FOUND':
                    if (nodeId && !foundIds.includes(nodeId)) foundIds.push(nodeId);
                    if (nodeId && !visitedIds.includes(nodeId)) visitedIds.push(nodeId);
                    steps.push({
                        phase: 'found',
                        nodeId, nodeIds: [nodeId],
                        visitedIds: [...visitedIds], foundIds: [...foundIds],
                        prunedIds: [],
                        message: `✅ <strong>${serverName}</strong> DENTRO del rango (dist=${dist?.toFixed(1) ?? '?'})`,
                    });
                    break;

                case 'COMPARE':
                    steps.push({
                        phase: 'compare',
                        nodeId, nodeIds: nodeId ? [nodeId] : [],
                        visitedIds: [...visitedIds], foundIds: [...foundIds],
                        prunedIds: [],
                        message: `${serverName} — distancia ${dist?.toFixed(1) ?? '?'}`,
                    });
                    break;

                case 'RANGE_RESULT':
                    steps.push({
                        phase: 'done',
                        nodeId: null, nodeIds: [], visitedIds: [...visitedIds],
                        foundIds: [...foundIds], prunedIds: [],
                        message: `<strong>Busqueda completada:</strong> ${foundIds.length} puntos encontrados`,
                    });
                    break;

                default:
                    break;
            }
        }

        return steps;
    },
};
