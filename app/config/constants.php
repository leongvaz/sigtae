<?php
/**
 * Constantes de negocio: niveles jerárquicos, estados, permisos
 */
return [
    // Niveles jerárquicos (1 = más alto)
    'niveles' => [
        'jefe_departamento' => 1,
        'jefe_oficina'      => 2,
        'supervisor'        => 3,
    ],

    // Estados de tarea (se calculan automáticamente según reglas)
    'estados_tarea' => [
        'asignada',      // Recién creada
        'en_proceso',    // Dentro del plazo, sin evidencia
        'incumplimiento',// Venció y no hay evidencia
        'vencida',       // Evidencia después del vencimiento
        'atendida',      // Evidencia dentro del tiempo
    ],

    // Dictamen de evaluación
    'dictamen' => [
        'satisfactoria',
        'satisfactoria_fuera_tiempo',
        'requiere_correccion',
        'no_presentada',
    ],

    // Prioridades y peso para desempeño ponderado (opcional)
    'prioridad_peso' => [
        'alta'   => 3,
        'media'  => 2,
        'baja'   => 1,
    ],

    // Tipos de tarea (catálogo)
    'categorias_tarea' => [
        'revision',
        'entrega_documental',
        'inspeccion',
        'seguimiento',
        'validacion',
        'atencion_correctiva',
    ],

    // Tipos de evento para historial
    'tipos_evento' => [
        'tarea_creada',
        'tarea_editada',
        'cambio_fecha_limite',
        'cambio_responsable',
        'cambio_estado',
        'evidencia_subida',
        'evidencia_eliminada',
        'comentario_agregado',
        'evaluacion_registrada',
        'delegacion_aplicada',
    ],
];
