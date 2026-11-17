        <!-- ========================= INICIO ========================= -->
        <section id="inicio" class="hero">

            <div class="hero-content">

                <span class="eyebrow">TU MÚSICA · TU ESPACIO</span>

                <h2>
                    Organiza tu música.<br>
                    <span>Disfruta tu progreso.</span>
                </h2>

                <p>
                    Busca canciones en Spotify, guárdalas en tu biblioteca, define
                    tus prioridades y escúchalas sin salir de MusicTrack.
                </p>

                <div class="hero-buttons">
                    <a href="#biblioteca" class="btn btn-primary">♪ Ver mi biblioteca</a>
                    <a href="#registrar" class="btn btn-light">＋ Registrar música</a>
                </div>

                <div class="hero-mini">
                    <span>✦</span>
                    Todo tu universo musical en un solo lugar
                </div>

            </div>

            <div class="hero-art" aria-hidden="true">

                <div class="glow"></div>

                <div class="vinyl">
                    <div class="vinyl-label">
                        <strong>MT</strong>
                        <small>♫</small>
                    </div>
                    <div class="vinyl-hole"></div>
                </div>

                <div class="floating-card card-one">
                    ♫
                    <b><?= $conteos['escuchado'] ?></b>
                    <small>escuchados</small>
                </div>

                <div class="floating-card card-two">
                    ♡
                    <b><?= $conteos['pendiente'] ?></b>
                    <small>pendientes</small>
                </div>

            </div>

        </section>
