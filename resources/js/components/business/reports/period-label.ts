import { formatMonthYearLong } from '@/lib/rozine/format';
import type { ReportPeriod } from '@/types/business';

/** "May 2026" for a monthly report, "2025 Annual" for the year's filing. */
export const periodLabel = (
    period: ReportPeriod,
    locale: string,
    t: (code: 'business.reports.annual', values: { year: string }) => string,
): string =>
    period.kind === 'annual'
        ? t('business.reports.annual', { year: period.starts_on.slice(0, 4) })
        : formatMonthYearLong(period.starts_on, locale);
