/* Portfolio — interactions publiques (sans dépendance externe). */
(() => {
  'use strict';

  const doc = document;
  const root = doc.documentElement;
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const body = doc.body;
  const motionOff = (body.getAttribute('style') || '').includes('--motion: off');
  const canAnimate = !reduceMotion && !motionOff;
  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

  /* ------------------------------ Reveal ------------------------------ */
  const reveals = [...doc.querySelectorAll('.reveal')];

  if (!canAnimate) {
    reveals.forEach((el) => el.classList.add('is-visible'));
    doc.querySelector('.hero')?.classList.add('is-ready');
  } else if ('IntersectionObserver' in window) {
    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          const delay = Number(entry.target.dataset.delay || 0);
          entry.target.style.transitionDelay = `${Math.min(delay, 6) * 70}ms`;
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        });
      },
      { rootMargin: '0px 0px -12% 0px', threshold: 0.12 }
    );
    reveals.forEach((el) => observer.observe(el));
    requestAnimationFrame(() => {
      setTimeout(() => doc.querySelector('.hero')?.classList.add('is-ready'), 60);
    });
  } else {
    reveals.forEach((el) => el.classList.add('is-visible'));
    doc.querySelector('.hero')?.classList.add('is-ready');
  }

  /* ------------------------------- Nav -------------------------------- */
  const nav = doc.getElementById('nav');
  const progress = doc.querySelector('.scroll-progress span');
  const sections = [...doc.querySelectorAll('main section[id]')];
  const navLinks = [...doc.querySelectorAll('.nav__link')];

  const onScroll = () => {
    const y = window.scrollY || root.scrollTop;
    if (nav) nav.classList.toggle('is-stuck', y > 40);

    if (progress) {
      const max = doc.body.scrollHeight - window.innerHeight;
      progress.style.width = max > 0 ? `${Math.min(100, (y / max) * 100)}%` : '0%';
    }

    if (navLinks.length) {
      let current = '';
      for (const section of sections) {
        if (section.getBoundingClientRect().top <= 120) current = section.id;
      }
      navLinks.forEach((link) => {
        const target = (link.getAttribute('href') || '').replace('#', '');
        link.classList.toggle('is-active', target === current && !!current);
      });
    }
  };

  let ticking = false;
  window.addEventListener(
    'scroll',
    () => {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(() => {
        onScroll();
        ticking = false;
      });
    },
    { passive: true }
  );
  onScroll();

  /* --------------------------- Menu mobile ---------------------------- */
  const burger = doc.querySelector('.nav__burger');
  const menu = doc.getElementById('mobile-menu');

  if (burger && menu) {
    const close = () => {
      burger.classList.remove('is-open');
      burger.setAttribute('aria-expanded', 'false');
      menu.classList.remove('is-open');
      doc.body.style.overflow = '';
      setTimeout(() => {
        if (!menu.classList.contains('is-open')) menu.hidden = true;
      }, 380);
    };

    burger.addEventListener('click', () => {
      const open = !menu.classList.contains('is-open') && menu.hidden;
      if (open) {
        menu.hidden = false;
        doc.body.style.overflow = 'hidden';
        requestAnimationFrame(() => {
          menu.classList.add('is-open');
          burger.classList.add('is-open');
          burger.setAttribute('aria-expanded', 'true');
        });
      } else {
        close();
      }
    });

    menu.querySelectorAll('a').forEach((link) => link.addEventListener('click', close));
    doc.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && menu.classList.contains('is-open')) close();
    });
  }

  /* ------------------------------ Curseur ----------------------------- */
  if (finePointer && canAnimate) {
    const cursor = doc.querySelector('.cursor');
    const label = cursor?.querySelector('.cursor__label');
    if (cursor) {
      let cx = window.innerWidth / 2;
      let cy = window.innerHeight / 2;
      let tx = cx;
      let ty = cy;

      window.addEventListener(
        'mousemove',
        (event) => {
          tx = event.clientX;
          ty = event.clientY;
          cursor.classList.add('is-active');
        },
        { passive: true }
      );

      doc.addEventListener('mouseleave', () => cursor.classList.remove('is-active'));

      const render = () => {
        cx += (tx - cx) * 0.16;
        cy += (ty - cy) * 0.16;
        cursor.style.transform = `translate(${cx}px, ${cy}px)`;
        requestAnimationFrame(render);
      };
      render();

      const interactive = 'a, button, [data-magnetic], .project, input, select, textarea';
      doc.querySelectorAll(interactive).forEach((el) => {
        el.addEventListener('mouseenter', () => {
          const text = el.dataset.cursor || '';
          cursor.classList.add('is-hover');
          if (text) {
            if (label) label.textContent = text;
            cursor.classList.add('is-label');
          }
        });
        el.addEventListener('mouseleave', () => {
          if (label) label.textContent = '';
          cursor.classList.remove('is-hover', 'is-label');
        });
      });
    }
  }

  /* ------------------------------ Aimantés ---------------------------- */
  if (finePointer && canAnimate) {
    doc.querySelectorAll('[data-magnetic]').forEach((el) => {
      const strength = 0.25;
      el.addEventListener('mousemove', (event) => {
        const rect = el.getBoundingClientRect();
        const x = (event.clientX - rect.left - rect.width / 2) * strength;
        const y = (event.clientY - rect.top - rect.height / 2) * strength;
        el.style.transform = `translate(${x.toFixed(2)}px, ${y.toFixed(2)}px)`;
      });
      el.addEventListener('mouseleave', () => {
        el.style.transform = '';
      });
    });
  }

  /* --------------------- Aperçu flottant des projets ------------------- */
  const projectsBlock = doc.querySelector('.projects');
  const previewFigure = projectsBlock?.querySelector('.projects__preview');
  const previewImage = previewFigure?.querySelector('img');

  if (projectsBlock && previewFigure && previewImage && finePointer && canAnimate) {
    let offsetX = 0;
    let offsetY = 0;

    projectsBlock.addEventListener('mousemove', (event) => {
      offsetX = event.clientX;
      offsetY = event.clientY;
      previewFigure.style.left = `${offsetX}px`;
      previewFigure.style.top = `${offsetY}px`;
    }, { passive: true });

    projectsBlock.querySelectorAll('.project').forEach((item) => {
      item.addEventListener('mouseenter', () => {
        const cover = item.dataset.cover;
        if (!cover) return;
        previewImage.src = cover;
        previewFigure.classList.add('is-visible');
      });
      item.addEventListener('mouseleave', () => previewFigure.classList.remove('is-visible'));
    });
  }

  /* -------------------------- Formulaire contact ---------------------- */
  const elapsedField = doc.querySelector('[data-elapsed]');
  const startedAt = Date.now();
  if (elapsedField) {
    const sync = () => { elapsedField.value = String(Date.now() - startedAt); };
    sync();
    setInterval(sync, 500);
  }

  /* --------------------------- Smooth anchors ------------------------- */
  doc.querySelectorAll('a[href^="#"]').forEach((link) => {
    link.addEventListener('click', (event) => {
      const id = link.getAttribute('href');
      if (!id || id === '#') return;
      const target = doc.querySelector(id);
      if (!target) return;
      event.preventDefault();
      target.scrollIntoView({ behavior: canAnimate ? 'smooth' : 'auto', block: 'start' });
      history.replaceState(null, '', id);
    });
  });
})();
