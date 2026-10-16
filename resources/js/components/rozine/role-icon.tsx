import type { RoleApp } from '@/types';

type RoleIconProps = {
    role: RoleApp;
    className?: string;
};

/**
 * The launcher's per-audience glyphs, drawn exactly as the Suite design draws them: a 24-unit
 * canvas, stroked in the audience accent so they recolour with the theme.
 */
export function RoleIcon({ role, className }: RoleIconProps) {
    return (
        <svg
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            strokeLinecap="round"
            strokeLinejoin="round"
            aria-hidden
            className={className}
        >
            {role === 'investor' && (
                <>
                    <path d="M4 18 L9 12 L13 15 L20 6" strokeWidth="2.2" />
                    <path d="M15 6 H20 V11" strokeWidth="2.2" />
                </>
            )}
            {role === 'business' && (
                <>
                    <rect
                        x="4"
                        y="4"
                        width="9"
                        height="16"
                        rx="1.5"
                        strokeWidth="2"
                    />
                    <path d="M13 9 H20 V20 H13" strokeWidth="2" />
                    <path d="M7 8 H10 M7 12 H10" strokeWidth="1.6" />
                </>
            )}
            {role === 'auditor' && (
                <>
                    <path
                        d="M12 3 L20 6 V11 C20 16 16 19 12 21 C8 19 4 16 4 11 V6 Z"
                        strokeWidth="2"
                    />
                    <path d="M9 11.5 l2 2 4-4.2" strokeWidth="2" />
                </>
            )}
        </svg>
    );
}
