import '@testing-library/jest-dom/vitest';
import { loadCatalog } from '@/lib/i18n';

/* French and Kinyarwanda are fetched on demand in the app; tests read them synchronously. */
await Promise.all([loadCatalog('fr'), loadCatalog('rw')]);
