  </main>
</div>

<div class="modal" id="media-modal" hidden>
  <div class="modal__backdrop" data-close-modal></div>
  <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="media-modal-title">
    <header class="modal__head">
      <h2 id="media-modal-title">Médiathèque</h2>
      <button type="button" class="modal__close" data-close-modal aria-label="Fermer">✕</button>
    </header>
    <div class="modal__body" id="media-grid">
      <p class="muted">Chargement…</p>
    </div>
    <footer class="modal__foot">
      <a href="<?= e(u('/admin/media.php')) ?>" class="btn btn--ghost">Gérer les médias</a>
      <span class="muted">Cliquez sur une image pour la sélectionner.</span>
    </footer>
  </div>
</div>

<script>window.APP_BASE = <?= json_encode(base_path(), JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="<?= e(u('/js/admin.js')) ?>" defer></script>
</body>
</html>
