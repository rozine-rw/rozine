import type { LedgerKind } from '@/types/admin';

/**
 * How each ledger movement reads: its glyph in the activity feed and its colour. The labels are
 * catalog messages (`admin.ledger.kind.*`).
 */
export const KIND_META: Record<LedgerKind, { glyph: string; color: string }> = {
    investment: { glyph: '↗', color: 'text-rz-accent-app-text' },
    disbursement: { glyph: '→', color: 'text-[#1d9e75] dark:text-[#3fcda0]' },
    repayment: { glyph: '↺', color: 'text-[#1d9e75] dark:text-[#3fcda0]' },
    service_fee: { glyph: '₣', color: 'text-[#1d9e75] dark:text-[#3fcda0]' },
    repayment_fee: { glyph: '₣', color: 'text-[#1d9e75] dark:text-[#3fcda0]' },
    auditor_share: { glyph: '₣', color: 'text-[#c2661f] dark:text-[#f0a060]' },
    distribution: { glyph: '↓', color: 'text-rz-accent-app-text' },
    recovery: { glyph: '⚑', color: 'text-[#c2661f] dark:text-[#f0a060]' },
    deposit: { glyph: '+', color: 'text-rz-slate' },
    withdrawal: { glyph: '−', color: 'text-[#c2661f] dark:text-[#f0a060]' },
    secondary: { glyph: '⇄', color: 'text-[#7c3aed] dark:text-[#b199fb]' },
    secondary_fee: { glyph: '₣', color: 'text-[#7c3aed] dark:text-[#b199fb]' },
    contra: { glyph: '⇆', color: 'text-[#e5484d] dark:text-[#ff6b6f]' },
};
