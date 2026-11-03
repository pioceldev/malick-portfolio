/* Back-office — interactions et médiathèque. */
(() => {
  'use strict';

  const doc = document;

  /* --------------------------- Menu mobile ---------------------------- */
  const burger = doc.getElementById('admin-burger');
  const sidebar = doc.getElementById('sidebar');
  if (burger && sidebar) {
    burger.addEventListener('click', () => {
      const open = sidebar.classList.toggle('is-open');
      burger.setAttribute('aria-expanded', String(open));
    });
    doc.addEventListener('click', (event) => {
      if (!sidebar.classList.contains('is-open')) return;
      if (sidebar.contains(event.target) || burger.contains(event.target)) return;
      sidebar.classList.remove('is-open');
      burger.setAttribute('aria-expanded', 'false');
    });
  }

  /* ------------------------ Confirmations ----------------------------- */
  doc.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirm || 'Confirmer cette action ?')) {
        event.preventDefault();
      }
    });
  });

  /* -------------------------- Aperçu fichiers ------------------------- */
  doc.querySelectorAll('input[type="file"]').forEach((input) => {
    input.addEventListener('change', () => {
      const files = [...(input.files || [])];
      if (!files.length) return;

      const dropzoneHint = input.closest('.dropzone')?.querySelector('[data-file-name]');
      if (dropzoneHint) {
        dropzoneHint.textContent =
          files.length === 1 ? files[0].name : `${files.length} fichiers sélectionnés`;
      }

      const preview = input.closest('.field, .media-input')?.querySelector('[data-preview-for]');
      const file = files[0];
      if (preview && file && file.type.startsWith('image/')) {
        preview.innerHTML = `<img src="${URL.createObjectURL(file)}" alt="">`;
      }
    });
  });

  /* -------------------------- Médiathèque ----------------------------- */
  const modal = doc.getElementById('media-modal');
  const grid = doc.getElementById('media-grid');
  let pickerTarget = null;
  let pickerGallery = null;
  let cache = null;

  const closeModal = () => {
    if (!modal) return;
    modal.hidden = true;
    pickerTarget = null;
    pickerGallery = null;
  };

  const renderMedia = (items) => {
    if (!grid) return;
    if (!items.length) {
      grid.innerHTML = '<p class="muted">Aucun média. Importez des fichiers depuis la médiathèque.</p>';
      return;
    }
    const wrapper = doc.createElement('div');
    wrapper.className = 'modal__grid';
    items.forEach((item) => {
      if (!String(item.mime).startsWith('image/')) return;
      const button = doc.createElement('button');
      button.type = 'button';
      button.className = 'modal__item';
      button.innerHTML = `<img src="/uploads/${encodeURIComponent(item.filename)}" alt="${item.alt || ''}" loading="lazy"><span>${item.filename}</span>`;
      button.addEventListener('click', () => selectMedia(item.filename));
      wrapper.appendChild(button);
    });
    grid.innerHTML = '';
    grid.appendChild(wrapper);
  };

  const selectMedia = (filename) => {
    if (pickerGallery) {
      addGalleryItem(filename);
    } else if (pickerTarget) {
      const input = doc.querySelector(`[data-media-value="${pickerTarget}"]`);
      if (input) input.value = filename;
      const preview = doc.querySelector(`[data-preview-for="${pickerTarget}"]`);
      if (preview) preview.innerHTML = `<img src="/uploads/${encodeURIComponent(filename)}" alt="">`;
    }
    closeModal();
  };

  const openModal = async (mode, target) => {
    if (!modal || !grid) return;
    pickerGallery = mode === 'gallery' ? true : null;
    pickerTarget = mode === 'field' ? target : null;
    modal.hidden = false;
    grid.innerHTML = '<p class="muted">Chargement…</p>';
    try {
      if (!cache) {
        const response = await fetch('/admin/api-media.php', { headers: { Accept: 'application/json' } });
        cache = await response.json();
      }
      renderMedia(cache);
    } catch {
      grid.innerHTML = '<p class="muted">Impossible de charger la médiathèque.</p>';
    }
  };

  doc.querySelectorAll('[data-open-media]').forEach((button) => {
    button.addEventListener('click', () => openModal('field', button.dataset.openMedia));
  });

  doc.querySelectorAll('[data-open-media-gallery]').forEach((button) => {
    button.addEventListener('click', () => openModal('gallery'));
  });

  modal?.querySelectorAll('[data-close-modal]').forEach((el) => el.addEventListener('click', closeModal));
  doc.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal && !modal.hidden) closeModal();
  });

  /* ---------------------------- Galerie ------------------------------- */
  const galleryRoot = () => doc.querySelector('[data-gallery-root]');

  const syncGallery = () => {
    const root = galleryRoot();
    if (!root) return;
    const list = root.querySelector('[data-gallery-list]');
    const json = root.querySelector('[data-gallery-json]');
    const files = [...list.querySelectorAll('li')].map((li) => li.dataset.file);
    if (json) json.value = JSON.stringify(files);
  };

  const bindRemove = (button) => {
    button.addEventListener('click', () => {
      button.closest('li')?.remove();
      syncGallery();
    });
  };

  function addGalleryItem(filename) {
    const root = galleryRoot();
    if (!root) return;
    const list = root.querySelector('[data-gallery-list]');
    const li = doc.createElement('li');
    li.dataset.file = filename;
    li.innerHTML = `<img src="/uploads/${encodeURIComponent(filename)}" alt=""><button type="button" class="gallery-input__remove" aria-label="Retirer">✕</button>`;
    bindRemove(li.querySelector('button'));
    list.appendChild(li);
    syncGallery();
  }

  doc.querySelectorAll('[data-gallery-remove]').forEach(bindRemove);
  doc.querySelectorAll('[data-clear-media]').forEach((button) => {
    button.addEventListener('click', () => {
      const key = button.dataset.clearMedia;
      const input = doc.querySelector(`[data-media-value="${key}"]`);
      if (input) input.value = '';
      const preview = doc.querySelector(`[data-preview-for="${key}"]`);
      if (preview) preview.innerHTML = '<span class="muted">Aucun fichier</span>';
    });
  });

  /* ----------------------------- Slug auto ---------------------------- */
  const slugField = doc.querySelector('input[data-slug-target]');
  if (slugField) {
    const source = doc.querySelector(`input[name="${slugField.dataset.slugTarget}"]`);
    let touched = slugField.value.length > 0;
    slugField.addEventListener('input', () => { touched = true; });
    source?.addEventListener('input', () => {
      if (touched) return;
      slugField.value = source.value
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '')
        .slice(0, 80);
    });
  }

  /* --------------------------- Onglets réglages ----------------------- */
  const tabs = [...doc.querySelectorAll('.settings__tabs a[href^="#"]')];
  const panels = tabs.map((tab) => doc.querySelector(tab.getAttribute('href'))).filter(Boolean);

  if (tabs.length && 'IntersectionObserver' in window) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          tabs.forEach((tab) =>
            tab.classList.toggle('is-active', tab.getAttribute('href') === `#${entry.target.id}`)
          );
        });
      },
      { rootMargin: '-90px 0px -60% 0px' }
    );
    panels.forEach((panel) => observer.observe(panel));
  }
})();
