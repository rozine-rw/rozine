/**
 * The rozine wordmark, which flips between its blue and white cut depending on
 * the surface it sits on.
 */
export function PulseWordmark({ className }: { className: string }) {
    return (
        <>
            <img
                src="/images/rozine-wordmark-blue.png"
                alt="rozine"
                className={`block dark:hidden ${className}`}
            />
            <img
                src="/images/rozine-wordmark-white.png"
                alt="rozine"
                className={`hidden dark:block ${className}`}
            />
        </>
    );
}
