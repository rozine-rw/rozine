/**
 * The Auditor design's own animations (design L25–37) that the shared stylesheet does not carry.
 * React hoists and de-duplicates this style by `href`, so it loads once per page. `rz-aud-spin`
 * gives the OCR spinner the keyframes the design names (`rz-spin`) but never defines.
 */
const KEYFRAMES = `
@keyframes rz-aud-ping { 0% { transform: scale(.5); opacity: .7; } 100% { transform: scale(2.4); opacity: 0; } }
@keyframes rz-aud-pulse { 0%, 100% { box-shadow: 0 0 0 0 rgba(194,102,31,.4); } 50% { box-shadow: 0 0 0 7px rgba(194,102,31,0); } }
@keyframes rz-aud-screen { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
@keyframes rz-aud-spin { to { transform: rotate(360deg); } }
@media (prefers-reduced-motion: reduce) {
  [class*='rz-aud-'] { animation: none !important; }
}
`;

export function AuditorKeyframes() {
    return (
        <style href="rz-auditor-keyframes" precedence="default">
            {KEYFRAMES}
        </style>
    );
}
