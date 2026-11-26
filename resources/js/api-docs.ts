import redocScriptUrl from 'redoc/bundles/redoc.standalone.js?url';

declare global {
  interface Window {
    Redoc?: {
      init: (
        specUrl: string,
        options?: Record<string, unknown>,
        container?: HTMLElement | null,
      ) => void | (() => void);
    };
  }
}

const container = document.getElementById('redoc');

if (!container) {
  throw new Error('No se encontró el contenedor de documentación.');
}

const script = document.createElement('script');
script.src = redocScriptUrl;
script.onload = () => {
  window.Redoc?.init('/api-docs.json', {
    hideDownloadButton: true,
    nativeScrollbars: true,
    scrollYOffset: 0,
    tagSort: 'alpha',
    operationsSort: 'alpha',
    theme: {
      typography: {
        fontFamily: 'ui-sans-serif, system-ui, sans-serif',
        fontSize: '14px',
      },
      sidebar: {
        width: '300px',
      },
    },
  }, container);
};
script.onerror = () => {
  container.textContent = 'No se pudo cargar la documentación de la API.';
};

document.head.appendChild(script);
