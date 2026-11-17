    <!-- ========================= MODAL EDITAR ========================= -->
    <!-- JavaScript llena los campos con los datos de la tarjeta (atributo data-editar) -->
    <dialog id="modalEditar" class="modal" aria-labelledby="modalEditarTitulo">

        <form action="acciones/actualizar.php" method="POST" class="modal-form">
            <?= $camposPost ?>
            <input type="hidden" name="id" id="editId">

            <div class="modal-head">
                <h2 id="modalEditarTitulo">Editar</h2>
                <button type="button" class="modal-close" data-cerrar-modal aria-label="Cerrar">✕</button>
            </div>

            <div class="modal-grid">

                <div class="form-group">
                    <label for="editTitulo">Título</label>
                    <input type="text" id="editTitulo" name="titulo" maxlength="150" required>
                </div>

                <div class="form-group">
                    <label for="editArtista">Artista o banda</label>
                    <input type="text" id="editArtista" name="artista" maxlength="150" required>
                </div>

                <div class="form-group">
                    <label for="editTipo">Tipo</label>
                    <select id="editTipo" name="tipo" required>
                        <?php foreach (TIPOS as $valor => $nombre): ?>
                            <option value="<?= e($valor) ?>"><?= e($nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editGenero">Género</label>
                    <select id="editGenero" name="genero" required>
                        <?php foreach (GENEROS as $valor => $nombre): ?>
                            <option value="<?= e($valor) ?>"><?= e($nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editPrioridad">Prioridad</label>
                    <select id="editPrioridad" name="prioridad" required>
                        <?php foreach (PRIORIDADES as $valor => $nombre): ?>
                            <option value="<?= e($valor) ?>"><?= e($nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editEstado">Estado</label>
                    <select id="editEstado" name="estado" required>
                        <?php foreach (ESTADOS as $valor => $nombre): ?>
                            <option value="<?= e($valor) ?>"><?= e($nombre) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="editCalificacion">Calificación</label>
                    <select id="editCalificacion" name="calificacion">
                        <option value="0">Sin calificar</option>
                        <?php for ($s = 1; $s <= 5; $s++): ?>
                            <option value="<?= $s ?>"><?= str_repeat('★', $s) ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="form-group full">
                    <label for="editNotas">Notas</label>
                    <textarea id="editNotas" name="notas" rows="3"></textarea>
                </div>

            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-light" data-cerrar-modal>Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar cambios</button>
            </div>
        </form>

    </dialog>
