(() => {
  const source = 'kikers-visual-editor';
  const sectionSelector = '.kve-section[data-kve-section]';
  let hovered = null;
  let activeSection = null;
  let chooser = null;

  const parse = (value) => {
    try {
      return JSON.parse(value || '[]');
    } catch {
      return [];
    }
  };

  const sendEdit = (element) => {
    window.parent.postMessage({source, action: 'edit', element}, window.location.origin);
  };

  const mode = document.createElement('div');
  mode.className = 'kve-mode';
  mode.innerHTML = '<span class="kve-mode__dot"></span><span>Visual editing: click any outlined item</span>';
  document.body.appendChild(mode);

  const label = document.createElement('div');
  label.className = 'kve-label';
  label.hidden = true;
  document.body.appendChild(label);

  const outline = document.createElement('div');
  outline.className = 'kve-section-outline';
  outline.hidden = true;
  document.body.appendChild(outline);

  const toolbar = document.createElement('div');
  toolbar.className = 'kve-section-toolbar';
  toolbar.hidden = true;
  toolbar.innerHTML = '<span class="kve-section-toolbar__title"></span><button type="button">Edit section</button>';
  document.body.appendChild(toolbar);

  const sectionBounds = (section) => {
    const boxes = Array.from(section.children)
      .filter((child) => !child.classList.contains('kve-section-toolbar'))
      .map((child) => child.getBoundingClientRect())
      .filter((box) => box.width > 0 && box.height > 0);
    if (!boxes.length) return null;
    return boxes.reduce((acc, box) => ({
      left: Math.min(acc.left, box.left),
      top: Math.min(acc.top, box.top),
      right: Math.max(acc.right, box.right),
      bottom: Math.max(acc.bottom, box.bottom),
    }));
  };

  const positionSectionTools = () => {
    if (!activeSection) return;
    const bounds = sectionBounds(activeSection);
    if (!bounds) return;
    outline.hidden = false;
    outline.style.left = `${Math.max(0, bounds.left)}px`;
    outline.style.top = `${Math.max(0, bounds.top)}px`;
    outline.style.width = `${Math.max(0, bounds.right - bounds.left)}px`;
    outline.style.height = `${Math.max(0, bounds.bottom - bounds.top)}px`;

    toolbar.hidden = false;
    const top = bounds.top >= 42 ? bounds.top - 34 : Math.max(0, bounds.top);
    toolbar.style.left = `${Math.max(8, bounds.left + 8)}px`;
    toolbar.style.top = `${top}px`;
  };

  const setSection = (section) => {
    if (!section || section === activeSection) return;
    activeSection = section;
    const meta = parse(section.dataset.kveSection);
    toolbar.querySelector('.kve-section-toolbar__title').textContent = meta.label || 'Section';
    positionSectionTools();
  };

  const hideChooser = () => {
    chooser?.remove();
    chooser = null;
  };

  const showChooser = (items, x, y) => {
    hideChooser();
    chooser = document.createElement('div');
    chooser.className = 'kve-chooser';
    chooser.innerHTML = '<div class="kve-chooser__heading">Choose what to edit</div>';
    items.forEach((item) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.textContent = item.label || 'Content';
      button.addEventListener('click', () => {
        hideChooser();
        sendEdit(item);
      });
      chooser.appendChild(button);
    });
    document.body.appendChild(chooser);
    const rect = chooser.getBoundingClientRect();
    chooser.style.left = `${Math.max(12, Math.min(x, window.innerWidth - rect.width - 12))}px`;
    chooser.style.top = `${Math.max(12, Math.min(y, window.innerHeight - rect.height - 12))}px`;
  };

  const candidatesFor = (target) => {
    const items = new Map();
    let node = target instanceof Element ? target : target.parentElement;
    while (node && !node.matches(sectionSelector)) {
      if (node.hasAttribute('data-kve-items')) {
        parse(node.dataset.kveItems).forEach((item) => items.set(String(item.elementId), item));
      }
      node = node.parentElement;
    }
    return Array.from(items.values());
  };

  document.addEventListener('pointerover', (event) => {
    const target = event.target instanceof Element ? event.target.closest('[data-kve-items]') : null;
    if (hovered !== target) {
      hovered?.classList.remove('kve-is-hovered');
      hovered = target;
      hovered?.classList.add('kve-is-hovered');
    }

    const section = event.target instanceof Element ? event.target.closest(sectionSelector) : null;
    if (section) setSection(section);

    if (!target) {
      label.hidden = true;
      return;
    }
    const item = parse(target.dataset.kveItems)[0];
    if (!item) return;
    const rect = target.getBoundingClientRect();
    label.textContent = item.label || 'Editable content';
    label.hidden = false;
    label.style.left = `${Math.max(8, Math.min(rect.left, window.innerWidth - 368))}px`;
    label.style.top = `${Math.max(8, rect.top - 29)}px`;
  }, true);

  document.addEventListener('click', (event) => {
    if (chooser && !chooser.contains(event.target)) hideChooser();
    const target = event.target instanceof Element ? event.target.closest('[data-kve-items]') : null;
    if (!target) return;
    const items = candidatesFor(event.target);
    if (!items.length) return;
    event.preventDefault();
    event.stopPropagation();
    if (items.length === 1) {
      sendEdit(items[0]);
    } else {
      showChooser(items, event.clientX + 8, event.clientY + 8);
    }
  }, true);

  toolbar.querySelector('button').addEventListener('click', (event) => {
    event.preventDefault();
    event.stopPropagation();
    if (!activeSection) return;
    const meta = parse(activeSection.dataset.kveSection);
    if (meta.elementId) sendEdit(meta);
  });

  window.addEventListener('scroll', positionSectionTools, {passive: true});
  window.addEventListener('resize', positionSectionTools);
  window.addEventListener('message', (event) => {
    if (event.origin !== window.location.origin || event.data?.source !== source) return;
    if (event.data.action === 'refresh') window.location.reload();
  });
})();
