<?php

/**
 * Vista progreso: la incluye index.php, que define estas variables.
 *
 * @var array<string, int> $conteos
 * @var int $total
 * @var int $porcentaje
 * @var array{conteos: array<string, int>, total: int, porcentaje: int, porGenero: list<array<string, mixed>>, topArtistas: list<array<string, mixed>>, promedio: ?float, calificadas: int} $stats
 */

?>
        <!-- ========================= PROGRESO Y ESTADÍSTICAS ========================= -->
        <section id="progreso" class="section">

            <div class="section-heading">
                <div>
                    <span class="section-kicker">RESUMEN</span>
                    <h2>Mi progreso musical</h2>
                </div>
                <p>Un vistazo rápido a tu colección.</p>
            </div>

            <div class="stats-grid">

                <article class="stat-card purple">
                    <div class="stat-icon">◷</div>
                    <div>
                        <h3>Pendientes</h3>
                        <strong><?= $conteos['pendiente'] ?></strong>
                        <p>Por explorar</p>
                    </div>
                </article>

                <article class="stat-card blue">
                    <div class="stat-icon">▶</div>
                    <div>
                        <h3>Escuchando</h3>
                        <strong><?= $conteos['escuchando'] ?></strong>
                        <p>En proceso</p>
                    </div>
                </article>

                <article class="stat-card pink">
                    <div class="stat-icon">✓</div>
                    <div>
                        <h3>Escuchados</h3>
                        <strong><?= $conteos['escuchado'] ?></strong>
                        <p>Completados</p>
                    </div>
                </article>

            </div>

            <!-- Barra de avance general -->
            <div class="progress-overview">
                <div class="progress-text">
                    <span>Has escuchado <b><?= $conteos['escuchado'] ?></b> de <b><?= $total ?></b></span>
                    <b><?= $porcentaje ?>%</b>
                </div>
                <div class="progress-bar" role="progressbar" aria-valuenow="<?= $porcentaje ?>" aria-valuemin="0" aria-valuemax="100">
                    <span style="width: <?= $porcentaje ?>%"></span>
                </div>
            </div>

            <?php if ($total > 0): ?>
                <!-- Estadísticas -->
                <div class="insights-grid">

                    <article class="insight-card">
                        <h3>Por género</h3>
                        <?php $maxGenero = max(array_column($stats['porGenero'], 'total')); ?>
                        <ul class="bar-chart">
                            <?php foreach ($stats['porGenero'] as $fila): ?>
                                <li>
                                    <span class="bar-label"><?= e(etiqueta(GENEROS, $fila['genero'])) ?></span>
                                    <span class="bar-track">
                                        <span class="bar-fill" style="width: <?= round($fila['total'] * 100 / $maxGenero) ?>%"></span>
                                    </span>
                                    <b><?= (int) $fila['total'] ?></b>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </article>

                    <article class="insight-card">
                        <h3>Artistas más guardados</h3>
                        <ol class="top-list">
                            <?php foreach ($stats['topArtistas'] as $fila): ?>
                                <li>
                                    <span><?= e($fila['artista']) ?></span>
                                    <b><?= (int) $fila['total'] ?></b>
                                </li>
                            <?php endforeach; ?>
                        </ol>
                    </article>

                    <article class="insight-card rating-card">
                        <h3>Calificación promedio</h3>
                        <?php if ($stats['promedio'] !== null): ?>
                            <strong><?= number_format($stats['promedio'], 1) ?></strong>
                            <div class="stars-static" aria-hidden="true">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="<?= $i <= round($stats['promedio']) ? 'on' : '' ?>">★</span>
                                <?php endfor; ?>
                            </div>
                            <p><?= $stats['calificadas'] ?> de <?= $total ?> calificadas</p>
                        <?php else: ?>
                            <p>Aún no has calificado nada. Usa las estrellas de cada tarjeta.</p>
                        <?php endif; ?>
                    </article>

                </div>
            <?php endif; ?>

        </section>
