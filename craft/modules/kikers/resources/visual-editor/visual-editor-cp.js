(() => {
  if (window.KikersVisualEditorBridge) return;
  window.KikersVisualEditorBridge = true;

  const source = 'kikers-visual-editor';

  const refreshPreview = () => {
    document.querySelectorAll('iframe.lp-preview').forEach((frame) => {
      frame.contentWindow?.postMessage({source, action: 'refresh'}, window.location.origin);
    });
  };

  window.addEventListener('message', async (event) => {
    if (event.origin !== window.location.origin || event.data?.source !== source) return;
    if (event.data.action === 'inlineSave' && event.data.element?.elementId) {
      const frame = event.source;
      try {
        await Craft.sendActionRequest('POST', 'kikers/inline-editor/save', {
          data: {
            elementId: Number(event.data.element.elementId),
            siteId: Number(event.data.element.siteId || Craft.siteId),
            value: String(event.data.value ?? ''),
          },
        });
        frame?.postMessage({source, action: 'inlineSaved'}, window.location.origin);
        refreshPreview();
      } catch (error) {
        frame?.postMessage({
          source,
          action: 'inlineError',
          message: error?.response?.data?.message || error?.message || 'The text could not be saved.',
        }, window.location.origin);
      }
      return;
    }

    if (event.data.action !== 'edit' || !event.data.element?.elementId) return;

    const element = event.data.element;
    const settings = {
      elementId: Number(element.elementId),
      siteId: Number(element.siteId || Craft.siteId),
    };
    ['draftId', 'revisionId', 'ownerId'].forEach((key) => {
      if (element[key]) settings[key] = Number(element[key]);
    });

    const editor = Craft.createElementEditor('craft\\elements\\Entry', settings);
    editor.on('submit', refreshPreview);
  });
})();
