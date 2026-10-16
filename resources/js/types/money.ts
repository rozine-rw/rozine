/**
 * Exact money as the server sends it (engineering contract §4): an integer decimal string of whole
 * Rwandan francs. The client formats it for display and never does arithmetic that decides
 * anything.
 */
export type Money = {
    currency: 'RWF';
    amount: string;
};
