import { Head, router } from '@inertiajs/react';
import { Component, Fragment, createElement } from 'react';

import { store as storeBusiness } from '@/routes/site/business';
import { store as storeInvestor } from '@/routes/site/investor';

import '../../css/site.css';

/**
 * The rozine.rw marketing site, ported from the Claude Design canvas export at
 * docs/resources/Rozine.rw (standalone).html.
 *
 * The markup, inline styles and animation logic are a mechanical translation of
 * that export so the rendered result stays pixel-identical to the design.
 * Regenerate rather than hand-edit when the design changes.
 */

declare module 'react' {
    interface CSSProperties {
        [key: `--${string}`]: string | number | undefined;
    }
}

type SiteState = Record<string, any>;
type SiteVals = Record<string, any>;

export default class Home extends Component<Record<string, never>, SiteState> {
    private _raf = 0;

    private _sm?: ReturnType<typeof setTimeout>;

    private _tgt?: { dep: number; ret: number };

    private _rzRO?: ResizeObserver;

    state: SiteState = Object.assign(
        {
            page: 'inv',
            B: {
                rev: 80000000,
                exp: 62000000,
                term: 6,
                biz: '',
                contact: '',
                mode: 'phone',
                sent: false,
                sheetOpen: false,
                bfaq: 0,
            },
            H: { open: '' },
        },
        {
            depIdx: 22,
            term: 6,
            band: 1,
            faq: 0,
            name: '',
            contact: '',
            country: '',
            mode: 'phone',
            sent: false,
            sheetOpen: false,
        },
    );

    bSet(patch: any) {
        this.setState((st: any) => ({
            B: Object.assign(
                {},
                st.B,
                typeof patch === 'function' ? patch(st.B) : patch,
            ),
        }));
    }
    hSet(patch: any) {
        this.setState((st) => ({ H: Object.assign({}, st.H, patch) }));
    }
    go(p: string) {
        return (e: any) => {
            if (e && e.preventDefault) {
                e.preventDefault();
            }

            this.setState({ page: p });
            window.scrollTo(0, 0);
        };
    }

    componentDidMount() {
        this._sync();
        this._rzWatch();
    }
    _rzMask() {
        const layer = document.querySelector(
            '[data-rz-glass]',
        ) as HTMLElement | null;

        if (!layer) {
            return;
        }

        const host = layer.parentElement as HTMLElement;
        const H = host.scrollHeight || host.getBoundingClientRect().height;

        if (!H) {
            return;
        }

        const hostTop = host.getBoundingClientRect().top + window.scrollY;
        const bands: number[][] = [];
        document.querySelectorAll('[data-rz-blue]').forEach((el) => {
            const r = el.getBoundingClientRect();
            const top = r.top + window.scrollY - hostTop;
            bands.push([
                Math.max(0, (top / H) * 100),
                Math.min(100, ((top + r.height) / H) * 100),
            ]);
        });
        bands.sort((a, b) => a[0] - b[0]);

        if (!bands.length) {
            layer.style.maskImage = 'linear-gradient(#0000,#0000)';
            layer.style.webkitMaskImage = 'linear-gradient(#0000,#0000)';

            return;
        }

        const parts = [];
        let cur = 0;
        bands.forEach(([a, b]) => {
            parts.push(
                'transparent ' + cur.toFixed(3) + '% ' + a.toFixed(3) + '%',
            );
            parts.push('#000 ' + a.toFixed(3) + '% ' + b.toFixed(3) + '%');
            cur = b;
        });
        parts.push('transparent ' + cur.toFixed(3) + '% 100%');
        const g = 'linear-gradient(to bottom, ' + parts.join(', ') + ')';
        layer.style.maskImage = g;
        layer.style.webkitMaskImage = g;
    }
    _rzWatch() {
        this._rzMask();
        requestAnimationFrame(() => this._rzMask());
        setTimeout(() => this._rzMask(), 400);
        setTimeout(() => this._rzMask(), 1200);

        if (!this._rzRO && window.ResizeObserver) {
            this._rzRO = new ResizeObserver(() => this._rzMask());
            this._rzRO.observe(document.body);
        }

        window.addEventListener('resize', () => this._rzMask());
    }

    componentDidUpdate() {
        this._sync();
    }
    componentWillUnmount() {
        cancelAnimationFrame(this._raf);
    }
    chargeFor(dep: number) {
        const B = [
            [150000000, 4.5],
            [75000000, 5],
            [25000000, 6],
            [5000000, 7],
            [1000000, 8],
            [0, 10],
        ];

        return B.find((b) => dep >= b[0])![1];
    }
    _sync() {
        const s = this.state;
        const flat = (2 * s.term + 4 + s.band * 2) / 100;
        const dep = this.depFor(s.depIdx),
            ret = dep * flat * (1 - this.chargeFor(dep) / 100);

        if (this._tgt && this._tgt.dep === dep && this._tgt.ret === ret) {
            return;
        }

        this._tgt = { dep, ret };
        cancelAnimationFrame(this._raf);
        const from = s.disp || { dep: 0, ret: 0 },
            t0 = performance.now(),
            D = 560;
        const step = (now: number) => {
            const p = Math.min(1, (now - t0) / D),
                e = 1 - Math.pow(1 - p, 3);
            this.setState({
                disp: {
                    dep: from.dep + (dep - from.dep) * e,
                    ret: from.ret + (ret - from.ret) * e,
                },
            });

            if (p < 1) {
                this._raf = requestAnimationFrame(step);
            }
        };
        this._raf = requestAnimationFrame(step);
    }

    shareBtns() {
        const R = createElement;
        const dl = R(
            'svg',
            {
                width: 15,
                height: 15,
                viewBox: '0 0 24 24',
                fill: 'none',
                stroke: 'currentColor',
                strokeWidth: 2,
                strokeLinecap: 'round',
                strokeLinejoin: 'round',
            },
            R('path', { d: 'M12 3v12m0 0 4.5-4.5M12 15l-4.5-4.5M4 19.5h16' }),
        );
        const flash = (m: string) => {
            this.setState({ shareMsg: m });
            clearTimeout(this._sm);
            this._sm = setTimeout(() => this.setState({ shareMsg: '' }), 2200);
        };

        return [
            {
                label: 'Download',
                text: 'Save card',
                icon: dl,
                bg: '#f8fafd',
                fg: '#0c1830',
                on: () => flash('Card saved to your device'),
            },
        ];
    }
    rwf(n: number) {
        return 'RWF ' + Math.round(n || 0).toLocaleString('en-US');
    }
    depFor(i: any) {
        const lo = 5000,
            hi = 200e6,
            t = Math.max(0, Math.min(40, +i || 0)) / 40;
        const v = lo * Math.pow(hi / lo, t);

        if (v < 1e5) {
            return Math.round(v / 5e3) * 5e3;
        }

        if (v < 1e6) {
            return Math.round(v / 5e4) * 5e4;
        }

        if (v < 1e7) {
            return Math.round(v / 5e5) * 5e5;
        }

        return Math.round(v / 5e6) * 5e6;
    }
    siteContact(mode: string, code: string, raw: string): string {
        const value = String(raw || '').trim();

        if (mode === 'email') {
            return value.toLowerCase();
        }

        const cc = String(code || '+250').replace(/[^0-9]/g, '');
        let local = this.digits(value).replace(/^0+/, '');

        if (cc && local.startsWith(cc)) {
            local = local.slice(cc.length);
        }

        return cc + local;
    }
    digits(s: any) {
        return String(s || '').replace(/[^0-9]/g, '');
    }

    invVals() {
        const s = this.state;
        // flat = 2*term + 4 + bandOffset (0/2/4); Rozine's return charge comes off the profit.
        const flat = (2 * s.term + 4 + s.band * 2) / 100;
        const dep = this.depFor(s.depIdx);
        const charge = this.chargeFor(dep);
        const ret = dep * flat * (1 - charge / 100);
        const c = String(s.contact || '').trim();
        const contactOk =
            (s.mode || 'phone') === 'phone'
                ? this.digits(c).length >= 9
                : /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(c);
        const nm = String(s.name || '').trim();
        const ok =
            nm.length > 1 &&
            contactOk &&
            String(s.country || '').trim().length > 1;

        const _btn = (on: boolean) =>
            on
                ? { bg: '#1e3aff', fg: '#ffffff', bd: '#1e3aff' }
                : { bg: '#f8fafd', fg: '#69748a', bd: '#e2e8f2' };
        const terms = [3, 4, 5, 6].map((t: number) =>
            Object.assign(
                { label: t + ' months', set: () => this.setState({ term: t }) },
                _btn(s.term === t),
            ),
        );
        const bands = ['Strong', 'Stable', 'Distressed'].map(
            (label: string, i: number) =>
                Object.assign(
                    { label, set: () => this.setState({ band: i }) },
                    _btn(s.band === i),
                ),
        );

        const fm = (i: number) => (s.faq === i ? '−' : '+');
        const ftog = (i: number) => () =>
            this.setState((st: any) => ({ faq: st.faq === i ? -1 : i }));

        return {
            depIdx: s.depIdx,
            onDep: (e: any) => this.setState({ depIdx: +e.target.value }),
            depStr: this.rwf(dep),
            terms,
            bands,
            backStr: this.rwf(s.disp ? s.disp.dep + s.disp.ret : dep + ret),
            retStr: '+ ' + this.rwf(s.disp ? s.disp.ret : ret),
            retPctStr:
                (flat * (100 - charge)).toFixed(1) +
                '% in ' +
                s.term +
                ' months',
            monStr: this.rwf(
                (s.disp ? s.disp.dep + s.disp.ret : dep + ret) / s.term,
            ),

            f0: s.faq === 0,
            f1: s.faq === 1,
            f2: s.faq === 2,
            f3: s.faq === 3,
            f0m: fm(0),
            f1m: fm(1),
            f2m: fm(2),
            f3m: fm(3),
            faq0: ftog(0),
            faq1: ftog(1),
            faq2: ftog(2),
            faq3: ftog(3),

            termStr: s.term + ' months',
            bandStr: ['Strong', 'Stable', 'Distressed'][s.band],

            sheetOpen: s.sheetOpen,
            openSheet: () => this.setState({ sheetOpen: true }),
            closeSheet: () => this.setState({ sheetOpen: false }),
            stop: (e: any) => e.stopPropagation(),

            fName: s.name,
            setName: (e: any) => this.setState({ name: e.target.value }),
            modes: [
                ['phone', 'Phone'],
                ['email', 'Email'],
            ].map(([k, l]: string[]) => {
                const on = (s.mode || 'phone') === k;

                return {
                    label: l,
                    set: () => this.setState({ mode: k, contact: '' }),
                    bg: on ? '#ffffff' : 'transparent',
                    fg: on ? '#0c1830' : '#8894a8',
                    sh: on ? '0 1px 4px rgba(12,24,48,.14)' : 'none',
                };
            }),
            contactMode: (s.mode || 'phone') === 'phone' ? 'tel' : 'email',
            contactPh:
                (s.mode || 'phone') === 'phone'
                    ? '078 000 0000'
                    : 'you@example.com',
            fContact: s.contact,
            setContact: (e: any) => this.setState({ contact: e.target.value }),
            fCountry: s.country,
            setCountry: (e: any) => this.setState({ country: e.target.value }),
            codeDisp: (s.mode || 'phone') === 'phone' ? 'block' : 'none',
            fCode: s.code || '+250',
            setCode: (e: any) => this.setState({ code: e.target.value }),
            codes: [
                ['+250', '🇷🇼 +250'],
                ['+254', '🇰🇪 +254'],
                ['+256', '🇺🇬 +256'],
                ['+255', '🇹🇿 +255'],
                ['+257', '🇧🇮 +257'],
                ['+243', '🇨🇩 +243'],
                ['+27', '🇿🇦 +27'],
                ['+1', '🇺🇸 +1'],
                ['+44', '🇬🇧 +44'],
            ].map(([v, l]: string[]) => ({ v, l })),
            cardName: nm.toUpperCase() || 'YOUR NAME',
            cardCountry: String(s.country || '').trim() || 'Rwanda',
            shareMsg: s.shareMsg || '',
            shareBtns: this.shareBtns(),
            subDisabled: !ok,
            subBg: ok ? '#1e3aff' : '#eef1f7',
            subFg: ok ? '#ffffff' : '#8894a8',
            subCur: ok ? 'pointer' : 'not-allowed',
            submit: () => {
                if (!ok || this.state.submitting) {
                    return;
                }

                this.setState({ submitting: true, submitErr: '' });
                router.post(
                    storeInvestor.url(),
                    {
                        name: nm,
                        contact_method: s.mode || 'phone',
                        contact: this.siteContact(s.mode || 'phone', s.code, c),
                        country: String(s.country || '').trim(),
                        pledge_amount: dep,
                    },
                    {
                        preserveScroll: true,
                        preserveState: true,
                        onSuccess: () =>
                            this.setState({ sent: true, submitting: false }),
                        onError: (errors: any) =>
                            this.setState({
                                submitting: false,
                                submitErr:
                                    (Object.values(
                                        errors || {},
                                    )[0] as string) ||
                                    'Something went wrong. Please try again.',
                            }),
                    },
                );
            },
            firstName: nm.split(' ')[0] || 'thanks',
            sent: s.sent,
            notSent: !s.sent,
            submitErr: s.submitErr || '',
        };
    }
    bChargeFor(S: number, m: number) {
        const s = Math.max(40, Math.min(92, S)),
            mm = Math.max(3, Math.min(6, m));
        const off = Math.round((1 - (s - 40) / 52) * 4 * 2) / 2;

        return (
            Math.round(Math.max(10, Math.min(20, 2 * mm + 4 + off)) * 10) / 10
        );
    }

    bShareBtns() {
        const R = createElement;
        const dl = R(
            'svg',
            {
                width: 15,
                height: 15,
                viewBox: '0 0 24 24',
                fill: 'none',
                stroke: 'currentColor',
                strokeWidth: 2,
                strokeLinecap: 'round',
                strokeLinejoin: 'round',
            },
            R('path', { d: 'M12 3v12m0 0 4.5-4.5M12 15l-4.5-4.5M4 19.5h16' }),
        );
        const flash = (m: string) => {
            this.bSet({ shareMsg: m });
            clearTimeout(this._sm);
            this._sm = setTimeout(() => this.bSet({ shareMsg: '' }), 2200);
        };

        return [
            {
                label: 'Download',
                text: 'Save card',
                icon: dl,
                bg: '#f8fafd',
                fg: '#0c1830',
                on: () => flash('Card saved to your device'),
            },
        ];
    }

    bizVals() {
        const s = this.state.B;
        const rev = s.rev || 0,
            exp = s.exp || 0,
            m = s.term;
        const over = rev > 0 && exp > 0 && exp >= rev;
        const margin = rev > 0 && exp < rev ? (rev - exp) / rev : 0;
        // typical sector, five years trading; the accountant sets the rest
        const score = Math.max(
            40,
            Math.min(92, 50 + 7.5 + 4 + Math.min(margin, 0.45) * 42),
        );
        const charge = this.bChargeFor(score, m);
        const monSurplus = Math.max(0, rev - exp) / 12;
        const afford = monSurplus / 1.25; // 1.25x cover on every payment
        let raw =
            rev > 0
                ? Math.min((afford * m) / (1 + charge / 100), rev * 0.35)
                : 0;
        raw = Math.floor(raw / 1e5) * 1e5;
        const MIN = 3e6,
            MAX = 1e8;
        const qual = Math.min(raw, MAX);
        const ok = qual >= MIN && !over;
        const total = qual * (1 + charge / 100);
        const cc = String(s.contact || '').trim();
        const contactOk =
            (s.mode || 'phone') === 'phone'
                ? cc.replace(/[^0-9]/g, '').length >= 9
                : /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(cc);
        const RW: Record<string, string[]> = {
            'Kigali City': ['Gasabo', 'Kicukiro', 'Nyarugenge'],
            Northern: ['Burera', 'Gakenke', 'Gicumbi', 'Musanze', 'Rulindo'],
            Southern: [
                'Gisagara',
                'Huye',
                'Kamonyi',
                'Muhanga',
                'Nyamagabe',
                'Nyanza',
                'Nyaruguru',
                'Ruhango',
            ],
            Eastern: [
                'Bugesera',
                'Gatsibo',
                'Kayonza',
                'Kirehe',
                'Ngoma',
                'Nyagatare',
                'Rwamagana',
            ],
            Western: [
                'Karongi',
                'Ngororero',
                'Nyabihu',
                'Nyamasheke',
                'Rubavu',
                'Rusizi',
                'Rutsiro',
            ],
        };
        const prov = s.prov || '';
        const leadOk =
            String(s.biz || '').trim().length > 1 &&
            contactOk &&
            prov &&
            s.dist;

        const _btn = (on: boolean) => ({
            bd: on ? '#1e3aff' : '#e2e8f2',
            bg: on ? '#1e3aff' : '#fff',
            fg: on ? '#fff' : '#69748a',
        });

        const bft = (i: number) => () =>
            this.bSet((p: any) => ({ bfaq: p.bfaq === i ? -1 : i }));

        return {
            bfaq0: bft(0),
            bfaq1: bft(1),
            bfaq2: bft(2),
            bfaq3: bft(3),
            bf0: s.bfaq === 0,
            bf1: s.bfaq === 1,
            bf2: s.bfaq === 2,
            bf3: s.bfaq === 3,
            bf0m: s.bfaq === 0 ? '−' : '+',
            bf1m: s.bfaq === 1 ? '−' : '+',
            bf2m: s.bfaq === 2 ? '−' : '+',
            bf3m: s.bfaq === 3 ? '−' : '+',
            bfa0: 'Every raise runs for 30 days. If it fills within that time, the money reaches you within 24 hours. If it does not fill the full amount in 30 days, every investor who subscribed is refunded and the raise comes off the investor feed.',
            bfa2: 'Yes. The flat charge is agreed up front and never grows, so paying early costs you nothing extra. Paying on time or early makes borrowing again faster and cheaper.',
            bfa3: 'Talk to us before you do. The monthly accountant check usually shows a rough patch coming, so we can work with you. Missing a payment without warning incurs penalties and may block you from borrowing again.',
            revStr: rev ? rev.toLocaleString('en-US') : '',
            expStr: exp ? exp.toLocaleString('en-US') : '',
            setRev: (e: any) =>
                this.bSet({ rev: +this.digits(e.target.value) || 0 }),
            setExp: (e: any) =>
                this.bSet({ exp: +this.digits(e.target.value) || 0 }),

            showLeft: !!(rev && exp),
            leftLabel: over
                ? 'YOUR COSTS ARE HIGHER THAN YOUR SALES'
                : 'LEFT OVER EACH MONTH',
            leftStr: over ? 'Check the figures' : this.rwf(monSurplus),
            leftFg: over ? '#c0392b' : '#1d9e75',
            leftBg: over ? 'rgba(192,57,43,.07)' : 'rgba(29,158,117,.08)',
            leftBd: over ? 'rgba(192,57,43,.25)' : 'rgba(29,158,117,.25)',

            terms: [3, 4, 5, 6].map((n: number) =>
                Object.assign(
                    { label: n + ' months', set: () => this.bSet({ term: n }) },
                    _btn(s.term === n),
                ),
            ),

            qualStr: ok ? this.rwf(qual) : '—',
            chargePctStr: ok
                ? charge.toFixed(1).replace(/\.0$/, '') + '%'
                : '—',
            monStr: ok ? this.rwf(total / m) : '—',
            totalStr: ok ? this.rwf(total) : '—',
            termStr: m + ' months',

            startDisabled: !ok,
            startBg: ok ? '#ffffff' : 'rgba(255,255,255,.14)',
            startFg: ok ? '#1428a4' : 'rgba(255,255,255,.45)',
            startCur: ok ? 'pointer' : 'not-allowed',
            sheetOpen: s.sheetOpen,
            openSheet: () => {
                if (ok) {
                    this.bSet({ sheetOpen: true });
                }
            },
            closeSheet: () => this.bSet({ sheetOpen: false }),
            stop: (e: any) => e.stopPropagation(),

            fBiz: s.biz,
            setBiz: (e: any) => this.bSet({ biz: e.target.value }),
            modes: [
                ['phone', 'Phone'],
                ['email', 'Email'],
            ].map(([k, l]: string[]) => {
                const on = (s.mode || 'phone') === k;

                return {
                    label: l,
                    set: () => this.bSet({ mode: k, contact: '' }),
                    bg: on ? '#ffffff' : 'transparent',
                    fg: on ? '#0c1830' : '#8894a8',
                    sh: on ? '0 1px 4px rgba(12,24,48,.14)' : 'none',
                };
            }),
            contactMode: (s.mode || 'phone') === 'phone' ? 'tel' : 'email',
            contactPh:
                (s.mode || 'phone') === 'phone'
                    ? '078 000 0000'
                    : 'you@business.rw',
            fContact: s.contact,
            setContact: (e: any) => this.bSet({ contact: e.target.value }),
            codeDisp: (s.mode || 'phone') === 'phone' ? 'block' : 'none',
            fCode: s.code || '+250',
            setCode: (e: any) => this.bSet({ code: e.target.value }),
            codes: [
                ['+250', '🇷🇼 +250'],
                ['+254', '🇰🇪 +254'],
                ['+256', '🇺🇬 +256'],
                ['+255', '🇹🇿 +255'],
                ['+257', '🇧🇮 +257'],
                ['+243', '🇨🇩 +243'],
                ['+27', '🇿🇦 +27'],
                ['+1', '🇺🇸 +1'],
                ['+44', '🇬🇧 +44'],
            ].map(([v, l]: string[]) => ({ v, l })),
            provinces: Object.keys(RW),
            districts: RW[prov] || [],
            fProv: prov,
            setProv: (e: any) => this.bSet({ prov: e.target.value, dist: '' }),
            fDist: s.dist || '',
            setDist: (e: any) => this.bSet({ dist: e.target.value }),
            distDisabled: !prov,
            distCur: prov ? 'pointer' : 'not-allowed',
            shareMsg: s.shareMsg || '',
            shareBtns: this.bShareBtns(),
            cardBiz:
                String(s.biz || '')
                    .trim()
                    .toUpperCase() || 'YOUR BUSINESS',
            cardLoc: (s.dist ? s.dist : prov) || 'Rwanda',
            subDisabled: !leadOk,
            subBg: leadOk ? '#1e3aff' : '#eef1f7',
            subFg: leadOk ? '#ffffff' : '#8894a8',
            subCur: leadOk ? 'pointer' : 'not-allowed',
            submit: () => {
                if (!leadOk || this.state.B.submitting) {
                    return;
                }

                this.bSet({ submitting: true, submitErr: '' });
                router.post(
                    storeBusiness.url(),
                    {
                        name: String(s.biz || '').trim(),
                        contact_method: s.mode || 'phone',
                        contact: this.siteContact(
                            s.mode || 'phone',
                            s.code,
                            cc,
                        ),
                        province: prov,
                        district: s.dist,
                        annual_revenue: rev,
                        annual_costs: exp,
                        term_months: m,
                    },
                    {
                        preserveScroll: true,
                        preserveState: true,
                        onSuccess: () =>
                            this.bSet({ sent: true, submitting: false }),
                        onError: (errors: any) =>
                            this.bSet({
                                submitting: false,
                                submitErr:
                                    (Object.values(
                                        errors || {},
                                    )[0] as string) ||
                                    'Something went wrong. Please try again.',
                            }),
                    },
                );
            },
            bizName: String(s.biz || '').trim() || 'your business',
            sent: s.sent,
            notSent: !s.sent,
            submitErr: s.submitErr || '',

            note: over
                ? 'Enter costs lower than your sales and we can size a loan.'
                : rev <= 0 || exp <= 0
                  ? 'Enter your last 12 months and see what you could borrow.'
                  : qual < MIN
                    ? 'On these figures you fall under the RWF 3,000,000 minimum. A longer term lowers each payment.'
                    : 'Indicative only. The final amount and charge depend on your sector, years trading and what the accountant finds.',
        };
    }

    helpVals() {
        const open = this.state.H.open;
        const item = (gi: number, qi: number, q: string, a: string) => {
            const id = 'g' + gi + '-' + qi;

            return {
                q,
                a,
                open: open === id,
                mark: open === id ? '−' : '+',
                toggle: () => this.hSet({ open: open === id ? '' : id }),
            };
        };

        return {
            groups: [
                {
                    n: '01',
                    title: 'Getting started',
                    items: [
                        item(
                            0,
                            0,
                            'How much do I need to start?',
                            'RWF 5,000, and there is no maximum. That is the price of one note, the same on every deal, so a RWF 10,000,000 raise is simply 2,000 notes and you buy as many as you want. Nothing to decode per deal.',
                        ),
                        item(
                            0,
                            1,
                            'Who can invest?',
                            'Anyone aged 18 or over with a phone. In Rwanda, a national ID; foreign nationals living here can register with a passport number. You don\u2019t need a bank account; mobile money works.',
                        ),
                        item(
                            0,
                            2,
                            'Can I invest if I don\u2019t live in Rwanda?',
                            'Yes. You register with your passport and fund by bank transfer. Every note is priced in Rwandan francs, so your money is converted on the way in and converted back when you withdraw. Because each conversion costs you a spread, moving fewer, larger amounts is cheaper than topping up every week.',
                        ),
                        item(
                            0,
                            3,
                            'How do I put money in?',
                            'Mobile money (MTN or Airtel) or a bank transfer. It shows up in your Rozine account within minutes, sitting at a licensed bank until you lend it.',
                        ),
                        item(
                            0,
                            4,
                            'Do I need to understand finance?',
                            'No. Each deal shows three things: what you’d earn, for how long, and how risky it is. If you can compare two of those cards, you can invest. And a Customer Support agent is one email away.',
                        ),
                    ],
                },
                {
                    n: '02',
                    title: 'Your money',
                    items: [
                        item(
                            1,
                            0,
                            'When do I get paid?',
                            'Every month. The business repays a piece of your money plus profit monthly, and by the end of the term, six months at most, everything is back.',
                        ),
                        item(
                            1,
                            1,
                            'How do I take money out?',
                            'From your Rozine account to mobile money or your bank, whenever you like. Money that\u2019s currently lent out comes back through the monthly payments first.',
                        ),
                        item(
                            1,
                            2,
                            'Can I get my money before the term ends?',
                            'Usually, yes. You sell your position to another investor, normally at a small discount. And since part of your money returns every month anyway, you\u2019re never waiting for all of it at once.',
                        ),
                        item(
                            1,
                            3,
                            'What about tax?',
                            'Your profit from lending may be taxable in Rwanda. We give you a simple yearly statement showing exactly what you earned, so declaring it is easy. If you’re unsure, email our Customer Support or contact a professional accountant.',
                        ),
                        item(
                            1,
                            4,
                            'Are there any fees for me?',
                            'Three, and all of them come off profit or a transaction, never off your principal. A return charge on your profit each time a business repays you: 10% at the base, falling to 8%, 7%, 6%, 5% and 4.5% as the amount you have invested passes RWF 1M, 5M, 25M, 75M and 150M. 0.5% when you withdraw to mobile money or your bank. And if you sell a note early, 1% on the sale, or 0.1% if you are the one buying. Putting money in and investing it cost nothing.',
                        ),
                    ],
                },
                {
                    n: '03',
                    title: 'When things go wrong',
                    items: [
                        item(
                            2,
                            0,
                            'What if a business pays late?',
                            'The reserve fund steps in so some of your monthly payment still arrives while we recover the money, and the business incurs penalties or is blocked from borrowing again.',
                        ),
                        item(
                            2,
                            1,
                            'What if a business fails completely?',
                            'The reserve fund covers some of the loss first. If it isn’t enough, you can lose the part of your money still with that business. That’s the real risk of lending. Anyone who tells you otherwise is lying.',
                        ),
                        item(
                            2,
                            2,
                            'Who actually owes me the money?',
                            'The business owes you, directly. Your money sits at a licensed bank and every loan is recorded in your name, never in Rozine’s, so your claim is on the business and its repayments and is held independently of us.',
                        ),
                        item(
                            2,
                            3,
                            'Is my money guaranteed?',
                            'No. This is lending, and it carries risks. However, we check every business in person, the reserve fund absorbs first losses, and no business can hold too much of anyone’s money as multiple investors share a loan in a business together hence distributing the risk and increasing odds of profit, but there is no absolute guarantee.',
                        ),
                    ],
                },
            ],
        };
    }

    renderVals() {
        const p = this.state.page || 'inv';
        const on = {
            bg: '#fff',
            fg: '#0c1830',
            sh: '0 1px 4px rgba(12,24,48,.16)',
        };
        const off = { bg: 'transparent', fg: '#69748a', sh: 'none' };
        const ai = p === 'inv' ? on : off,
            ab = p === 'biz' ? on : off;
        const nav = {
            pgInv: p === 'inv',
            pgBiz: p === 'biz',
            pgHelp: p === 'help',
            goInv: this.go('inv'),
            goBiz: this.go('biz'),
            goHelp: this.go('help'),
            segIBg: ai.bg,
            segIFg: ai.fg,
            segISh: ai.sh,
            segBBg: ab.bg,
            segBFg: ab.fg,
            segBSh: ab.sh,
            helpFg: p === 'help' ? '#0c1830' : '#69748a',
        };
        const vals =
            p === 'biz'
                ? this.bizVals()
                : p === 'help'
                  ? this.helpVals()
                  : this.invVals();

        return Object.assign(nav, vals);
    }

    render() {
        const {
            backStr,
            bandStr,
            bands,
            bf0,
            bf0m,
            bf1,
            bf1m,
            bf2,
            bf2m,
            bf3,
            bf3m,
            bfa0,
            bfa2,
            bfa3,
            bfaq0,
            bfaq1,
            bfaq2,
            bfaq3,
            bizName,
            cardBiz,
            cardCountry,
            cardLoc,
            cardName,
            chargePctStr,
            closeSheet,
            codeDisp,
            codes,
            contactMode,
            contactPh,
            depIdx,
            depStr,
            distCur,
            distDisabled,
            districts,
            expStr,
            f0,
            f0m,
            f1,
            f1m,
            f3,
            f3m,
            fBiz,
            fCode,
            fContact,
            fCountry,
            fDist,
            fName,
            fProv,
            faq0,
            faq1,
            faq3,
            firstName,
            goBiz,
            goHelp,
            goInv,
            groups,
            helpFg,
            leftBd,
            leftBg,
            leftFg,
            leftLabel,
            leftStr,
            modes,
            monStr,
            notSent,
            note,
            onDep,
            openSheet,
            pgBiz,
            pgHelp,
            pgInv,
            provinces,
            qualStr,
            retPctStr,
            retStr,
            revStr,
            segBBg,
            segBFg,
            segBSh,
            segIBg,
            segIFg,
            segISh,
            sent,
            setBiz,
            setCode,
            setContact,
            setCountry,
            setDist,
            setExp,
            setName,
            setProv,
            setRev,
            shareBtns,
            shareMsg,
            sheetOpen,
            showLeft,
            startBg,
            startCur,
            startDisabled,
            startFg,
            stop,
            subBg,
            subCur,
            subDisabled,
            subFg,
            submit,
            termStr,
            terms,
            totalStr,
            submitErr,
        }: SiteVals = this.renderVals();

        return (
            <>
                <Head title="Rozine — invest in Rwandan businesses" />
                <div
                    id="top"
                    className="rz-site"
                    style={{
                        position: 'relative',
                        width: '100%',
                        overflowX: 'clip',
                        background: '#fff',
                    }}
                >
                    <div
                        aria-hidden="true"
                        style={{
                            position: 'fixed',
                            inset: '0',
                            zIndex: '0',
                            pointerEvents: 'none',
                            overflow: 'hidden',
                        }}
                    >
                        <div
                            style={{
                                position: 'absolute',
                                top: '-14%',
                                left: '56%',
                                width: 'min(760px,86vw)',
                                aspectRatio: '1',
                                borderRadius: '50%',
                                background:
                                    'radial-gradient(circle, rgba(30,58,255,.10) 0%, rgba(30,58,255,0) 66%)',
                            }}
                        ></div>
                        <div
                            style={{
                                position: 'absolute',
                                top: '34%',
                                left: '-16%',
                                width: 'min(660px,82vw)',
                                aspectRatio: '1',
                                borderRadius: '50%',
                                background:
                                    'radial-gradient(circle, rgba(20,40,164,.085) 0%, rgba(20,40,164,0) 66%)',
                            }}
                        ></div>
                        <div
                            style={{
                                position: 'absolute',
                                top: '74%',
                                left: '38%',
                                width: 'min(600px,78vw)',
                                aspectRatio: '1',
                                borderRadius: '50%',
                                background:
                                    'radial-gradient(circle, rgba(29,158,117,.07) 0%, rgba(29,158,117,0) 66%)',
                            }}
                        ></div>
                    </div>
                    <div style={{ position: 'relative', zIndex: '1' }}>
                        <header
                            style={{
                                position: 'sticky',
                                top: '0',
                                zIndex: '90',
                                background: 'rgba(255,255,255,.66)',
                                backdropFilter: 'blur(22px) saturate(1.8)',
                                WebkitBackdropFilter:
                                    'blur(22px) saturate(1.8)',
                                borderBottom: '1px solid rgba(12,24,48,.07)',
                            }}
                        >
                            <div
                                style={{
                                    maxWidth: '1200px',
                                    margin: '0 auto',
                                    padding: '0 clamp(18px,4vw,44px)',
                                    height: '66px',
                                    display: 'flex',
                                    alignItems: 'center',
                                    gap: 'clamp(12px,3vw,40px)',
                                }}
                            >
                                <a
                                    href="#top"
                                    onClick={goInv}
                                    style={{
                                        flex: '0 0 auto',
                                        display: 'flex',
                                        alignItems: 'center',
                                    }}
                                >
                                    <img
                                        src="/site/img/ad3c8717.png"
                                        alt="rozine"
                                        style={{
                                            height: '22px',
                                            width: 'auto',
                                            display: 'block',
                                        }}
                                    />
                                </a>
                                <nav
                                    style={{
                                        display: 'flex',
                                        alignItems: 'center',
                                        gap: 'clamp(12px,2vw,28px)',
                                        marginLeft: 'auto',
                                        fontSize: '14px',
                                        fontWeight: '500',
                                    }}
                                >
                                    <span
                                        className="rz-seg"
                                        style={{
                                            display: 'flex',
                                            gap: '4px',
                                            padding: '3px',
                                            borderRadius: '999px',
                                            background: '#eef1f7',
                                        }}
                                    >
                                        <a
                                            href="#top"
                                            onClick={goInv}
                                            style={{
                                                padding: '7px 15px',
                                                borderRadius: '999px',
                                                fontSize: '13.5px',
                                                fontWeight: '600',
                                                whiteSpace: 'nowrap',
                                                transition:
                                                    'background .2s, color .2s',
                                                background: segIBg,
                                                color: segIFg,
                                                boxShadow: segISh,
                                            }}
                                            style-hover="color:#0c1830"
                                        >
                                            <span className="rz-seg-long">
                                                For investors
                                            </span>
                                            <span className="rz-seg-short">
                                                Investors
                                            </span>
                                        </a>
                                        <a
                                            href="#top"
                                            onClick={goBiz}
                                            style={{
                                                padding: '7px 15px',
                                                borderRadius: '999px',
                                                fontSize: '13.5px',
                                                fontWeight: '600',
                                                whiteSpace: 'nowrap',
                                                transition:
                                                    'background .2s, color .2s',
                                                background: segBBg,
                                                color: segBFg,
                                                boxShadow: segBSh,
                                            }}
                                            style-hover="color:#0c1830"
                                        >
                                            <span className="rz-seg-long">
                                                For businesses
                                            </span>
                                            <span className="rz-seg-short">
                                                Businesses
                                            </span>
                                        </a>
                                    </span>
                                    <a
                                        href="#top"
                                        onClick={goHelp}
                                        style={{
                                            color: helpFg,
                                            fontWeight: '600',
                                        }}
                                    >
                                        Help
                                    </a>
                                </nav>
                            </div>
                            <div
                                className="rz-mobile-only"
                                style={{
                                    display: 'none',
                                    gap: '20px',
                                    overflowX: 'auto',
                                    padding: '0 18px 12px',
                                    fontSize: '13.5px',
                                    fontWeight: '500',
                                    whiteSpace: 'nowrap',
                                }}
                            >
                                <a
                                    href="#top"
                                    onClick={goHelp}
                                    style={{ color: helpFg, fontWeight: '600' }}
                                >
                                    Help
                                </a>
                            </div>
                        </header>

                        {pgInv ? (
                            <>
                                <section
                                    id="top"
                                    style={{
                                        position: 'relative',
                                        zIndex: '1',
                                        background:
                                            'linear-gradient(180deg, rgba(248,250,253,.55) 0%, rgba(248,250,253,0) 100%)',
                                    }}
                                >
                                    <div
                                        style={{
                                            position: 'absolute',
                                            inset: '0',
                                            overflow: 'clip',
                                            pointerEvents: 'none',
                                        }}
                                    >
                                        <div
                                            style={{
                                                position: 'absolute',
                                                inset: '-20%',
                                                pointerEvents: 'none',
                                            }}
                                        >
                                            <div
                                                style={{
                                                    position: 'absolute',
                                                    top: '8%',
                                                    left: '52%',
                                                    width: '640px',
                                                    height: '640px',
                                                    borderRadius: '50%',
                                                    background:
                                                        'radial-gradient(circle, rgba(30,58,255,.14) 0%, rgba(30,58,255,0) 65%)',
                                                }}
                                            ></div>
                                            <div
                                                style={{
                                                    position: 'absolute',
                                                    top: '30%',
                                                    left: '18%',
                                                    width: '520px',
                                                    height: '520px',
                                                    borderRadius: '50%',
                                                    background:
                                                        'radial-gradient(circle, rgba(20,40,164,.10) 0%, rgba(20,40,164,0) 65%)',
                                                }}
                                            ></div>
                                            <div
                                                style={{
                                                    position: 'absolute',
                                                    top: '-6%',
                                                    left: '30%',
                                                    width: '420px',
                                                    height: '420px',
                                                    borderRadius: '50%',
                                                    background:
                                                        'radial-gradient(circle, rgba(29,158,117,.08) 0%, rgba(29,158,117,0) 65%)',
                                                }}
                                            ></div>
                                        </div>
                                    </div>

                                    <div
                                        style={{
                                            position: 'relative',
                                            maxWidth: '1200px',
                                            margin: '0 auto',
                                            padding:
                                                'clamp(52px,8vw,104px) clamp(18px,4vw,44px) clamp(56px,8vw,104px)',
                                            display: 'flex',
                                            flexWrap: 'wrap',
                                            gap: 'clamp(32px,4vw,64px)',
                                            alignItems: 'center',
                                        }}
                                    >
                                        <div
                                            style={{
                                                flex: '1 1 400px',
                                                minWidth: '0',
                                            }}
                                        >
                                            <div
                                                style={{
                                                    display: 'inline-flex',
                                                    alignItems: 'center',
                                                    gap: '9px',
                                                    padding:
                                                        '7px 15px 7px 11px',
                                                    borderRadius: '999px',
                                                    background: '#fff',
                                                    border: '1px solid #e2e8f2',
                                                    animation:
                                                        'rz-pop .6s ease both',
                                                }}
                                            >
                                                <span
                                                    style={{
                                                        position: 'relative',
                                                        width: '7px',
                                                        height: '7px',
                                                        borderRadius: '50%',
                                                        background: '#1d9e75',
                                                        flex: '0 0 auto',
                                                    }}
                                                >
                                                    <span
                                                        style={{
                                                            position:
                                                                'absolute',
                                                            inset: '0',
                                                            borderRadius: '50%',
                                                            background:
                                                                '#1d9e75',
                                                            animation:
                                                                'rz-ring 2.4s ease-out infinite',
                                                        }}
                                                    ></span>
                                                </span>
                                                <span
                                                    style={{
                                                        fontSize: '12.5px',
                                                        fontWeight: '600',
                                                        color: '#69748a',
                                                    }}
                                                >
                                                    Investing in Rwanda, for
                                                    Rwanda
                                                </span>
                                            </div>
                                            <h1
                                                style={{
                                                    margin: 'clamp(18px,2.6vw,26px) 0 0',
                                                    fontSize:
                                                        'clamp(38px,5vw,64px)',
                                                    lineHeight: '1.02',
                                                    fontWeight: '800',
                                                    letterSpacing: '-.045em',
                                                    color: '#0c1830',
                                                    animation:
                                                        'rz-pop .6s ease both .08s',
                                                }}
                                            >
                                                Earn up to{' '}
                                                <span
                                                    style={{ color: '#1e3aff' }}
                                                >
                                                    18%
                                                </span>{' '}
                                                lending to profitable
                                                businesses.
                                            </h1>
                                            <p
                                                style={{
                                                    margin: 'clamp(18px,2.2vw,26px) 0 0',
                                                    maxWidth: '520px',
                                                    fontSize:
                                                        'clamp(16px,1.4vw,19px)',
                                                    lineHeight: '1.6',
                                                    color: '#69748a',
                                                    animation:
                                                        'rz-pop .6s ease both .16s',
                                                    textWrap: 'pretty',
                                                }}
                                            >
                                                Buy 3 - 6 month debt notes in
                                                audited private businesses,
                                                checked by licensed accountants.
                                                From just RWF 5,000.&nbsp;
                                            </p>
                                        </div>

                                        <div
                                            id="calc"
                                            style={{
                                                flex: '0 1 372px',
                                                minWidth: '0',
                                                maxWidth: '400px',
                                                animation:
                                                    'rz-pop .7s ease both .2s',
                                            }}
                                        >
                                            <div
                                                style={{
                                                    borderRadius: '22px',
                                                    background:
                                                        'rgba(255,255,255,.8)',
                                                    backdropFilter:
                                                        'blur(24px) saturate(1.6)',
                                                    WebkitBackdropFilter:
                                                        'blur(24px) saturate(1.6)',
                                                    border: '1px solid rgba(12,24,48,.1)',
                                                    boxShadow:
                                                        '0 2px 10px rgba(12,24,48,.08), 0 18px 46px -10px rgba(12,24,48,.24), 0 46px 110px -20px rgba(12,24,48,.34)',
                                                    overflow: 'hidden',
                                                }}
                                            >
                                                <div
                                                    style={{
                                                        padding:
                                                            'clamp(18px,2vw,22px)',
                                                    }}
                                                >
                                                    <h2
                                                        style={{
                                                            margin: '0',
                                                            fontSize: '19px',
                                                            fontWeight: '800',
                                                            letterSpacing:
                                                                '-.03em',
                                                            lineHeight: '1.1',
                                                        }}
                                                    >
                                                        What would{' '}
                                                        <span
                                                            style={{
                                                                color: '#1e3aff',
                                                            }}
                                                        >
                                                            you
                                                        </span>{' '}
                                                        earn?
                                                    </h2>
                                                    <div
                                                        style={{
                                                            marginTop: '15px',
                                                            display: 'flex',
                                                            justifyContent:
                                                                'space-between',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '10px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '12px',
                                                                fontWeight:
                                                                    '600',
                                                                color: '#69748a',
                                                            }}
                                                        >
                                                            You put in
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '19px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#0c1830',
                                                            }}
                                                        >
                                                            {depStr}
                                                        </span>
                                                    </div>
                                                    <input
                                                        type="range"
                                                        className="rz-range"
                                                        min="0"
                                                        max="40"
                                                        step="1"
                                                        value={depIdx}
                                                        onChange={onDep}
                                                        style={{
                                                            marginTop: '10px',
                                                        }}
                                                    />
                                                    <div
                                                        style={{
                                                            marginTop: '5px',
                                                            display: 'flex',
                                                            justifyContent:
                                                                'space-between',
                                                            fontSize: '11px',
                                                            fontWeight: '600',
                                                            color: '#8894a8',
                                                        }}
                                                    >
                                                        <span>RWF 5,000</span>
                                                        <span>RWF 200M</span>
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '14px',
                                                            fontSize: '12px',
                                                            fontWeight: '600',
                                                            color: '#69748a',
                                                        }}
                                                    >
                                                        For how long
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '7px',
                                                            display: 'flex',
                                                            gap: '7px',
                                                        }}
                                                    >
                                                        {(terms ?? []).map(
                                                            (
                                                                t: any,
                                                                tI: number,
                                                            ) => (
                                                                <Fragment
                                                                    key={tI}
                                                                >
                                                                    <button
                                                                        onClick={
                                                                            t.set
                                                                        }
                                                                        style={{
                                                                            flex: '1 1 0',
                                                                            height: '35px',
                                                                            borderRadius:
                                                                                '10px',
                                                                            border: `1.5px solid ${t.bd}`,
                                                                            background:
                                                                                t.bg,
                                                                            color: t.fg,
                                                                            fontSize:
                                                                                '12.5px',
                                                                            fontWeight:
                                                                                '600',
                                                                            cursor: 'pointer',
                                                                            transition:
                                                                                'all .2s',
                                                                        }}
                                                                    >
                                                                        {
                                                                            t.label
                                                                        }
                                                                    </button>
                                                                </Fragment>
                                                            ),
                                                        )}
                                                    </div>
                                                    <div
                                                        className="rz-hide-mobile"
                                                        style={{
                                                            marginTop: '13px',
                                                            fontSize: '12px',
                                                            fontWeight: '600',
                                                            color: '#69748a',
                                                        }}
                                                    >
                                                        Rating band{' '}
                                                        <span
                                                            style={{
                                                                fontWeight:
                                                                    '500',
                                                                color: '#8894a8',
                                                            }}
                                                        >
                                                            (riskier pays more)
                                                        </span>
                                                    </div>
                                                    <div
                                                        className="rz-hide-mobile"
                                                        style={{
                                                            marginTop: '7px',
                                                            display: 'flex',
                                                            gap: '7px',
                                                        }}
                                                    >
                                                        {(bands ?? []).map(
                                                            (
                                                                b: any,
                                                                bI: number,
                                                            ) => (
                                                                <Fragment
                                                                    key={bI}
                                                                >
                                                                    <button
                                                                        onClick={
                                                                            b.set
                                                                        }
                                                                        style={{
                                                                            flex: '1 1 0',
                                                                            height: '35px',
                                                                            borderRadius:
                                                                                '10px',
                                                                            border: `1.5px solid ${b.bd}`,
                                                                            background:
                                                                                b.bg,
                                                                            color: b.fg,
                                                                            fontSize:
                                                                                '12px',
                                                                            fontWeight:
                                                                                '600',
                                                                            cursor: 'pointer',
                                                                            transition:
                                                                                'all .2s',
                                                                        }}
                                                                    >
                                                                        {
                                                                            b.label
                                                                        }
                                                                    </button>
                                                                </Fragment>
                                                            ),
                                                        )}
                                                    </div>
                                                </div>
                                                <div
                                                    style={{
                                                        padding:
                                                            '16px clamp(18px,2vw,22px) clamp(18px,2vw,22px)',
                                                        background: '#1428a4',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            display: 'flex',
                                                            justifyContent:
                                                                'space-between',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '10px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '12px',
                                                                fontWeight:
                                                                    '600',
                                                                color: 'rgba(255,255,255,.55)',
                                                            }}
                                                        >
                                                            You get back
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    'clamp(22px,2.1vw,26px)',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.03em',
                                                                color: '#fff',
                                                                lineHeight: '1',
                                                            }}
                                                        >
                                                            {backStr}
                                                        </span>
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '11px',
                                                            display: 'flex',
                                                            flexDirection:
                                                                'column',
                                                            gap: '1px',
                                                            background:
                                                                'rgba(255,255,255,.14)',
                                                            borderRadius:
                                                                '11px',
                                                            overflow: 'hidden',
                                                        }}
                                                    >
                                                        <div
                                                            style={{
                                                                padding:
                                                                    '9px 13px',
                                                                background:
                                                                    'rgba(255,255,255,.05)',
                                                                display: 'flex',
                                                                justifyContent:
                                                                    'space-between',
                                                                gap: '10px',
                                                            }}
                                                        >
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '13px',
                                                                    color: 'rgba(255,255,255,.65)',
                                                                }}
                                                            >
                                                                Your profit
                                                            </span>
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '13.5px',
                                                                    fontWeight:
                                                                        '700',
                                                                    color: '#4ecb9d',
                                                                }}
                                                            >
                                                                {retStr}
                                                            </span>
                                                        </div>
                                                        <div
                                                            style={{
                                                                padding:
                                                                    '9px 13px',
                                                                background:
                                                                    'rgba(255,255,255,.05)',
                                                                display: 'flex',
                                                                justifyContent:
                                                                    'space-between',
                                                                gap: '10px',
                                                            }}
                                                        >
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '13px',
                                                                    color: 'rgba(255,255,255,.65)',
                                                                }}
                                                            >
                                                                That's a return
                                                                of
                                                            </span>
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '13.5px',
                                                                    fontWeight:
                                                                        '700',
                                                                    color: '#fff',
                                                                }}
                                                            >
                                                                {retPctStr}
                                                            </span>
                                                        </div>
                                                        <div
                                                            style={{
                                                                padding:
                                                                    '9px 13px',
                                                                background:
                                                                    'rgba(255,255,255,.05)',
                                                                display: 'flex',
                                                                justifyContent:
                                                                    'space-between',
                                                                gap: '10px',
                                                            }}
                                                        >
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '13px',
                                                                    color: 'rgba(255,255,255,.65)',
                                                                }}
                                                            >
                                                                Paid to you
                                                                monthly
                                                            </span>
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '13.5px',
                                                                    fontWeight:
                                                                        '700',
                                                                    color: '#fff',
                                                                }}
                                                            >
                                                                {monStr}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <button
                                                        onClick={openSheet}
                                                        style={{
                                                            marginTop: '14px',
                                                            width: '100%',
                                                            height: '46px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            gap: '9px',
                                                            border: 'none',
                                                            borderRadius:
                                                                '12px',
                                                            background: '#fff',
                                                            color: '#1428a4',
                                                            fontSize: '14.5px',
                                                            fontWeight: '600',
                                                            cursor: 'pointer',
                                                            fontFamily:
                                                                'inherit',
                                                            transition:
                                                                'background .2s',
                                                        }}
                                                        style-hover="background:#e8ecff; color:#1428a4"
                                                    >
                                                        Start investing
                                                    </button>
                                                    <p
                                                        style={{
                                                            margin: '10px 0 0',
                                                            fontSize: '11.5px',
                                                            lineHeight: '1.6',
                                                            color: 'rgba(255,255,255,.5)',
                                                        }}
                                                    >
                                                        No money moves now.
                                                        Indicative only.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section
                                    style={{
                                        position: 'relative',
                                        zIndex: '0',
                                        marginTop: 'clamp(-140px,-11vw,-72px)',
                                        padding: '0',
                                    }}
                                >
                                    <div
                                        className="rz-strip"
                                        style={{
                                            overflow: 'hidden',
                                            maskImage:
                                                'linear-gradient(to right, transparent, #000 6%, #000 94%, transparent)',
                                            WebkitMaskImage:
                                                'linear-gradient(to right, transparent, #000 6%, #000 94%, transparent)',
                                        }}
                                    >
                                        <div className="rz-track">
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/9eb44d0e.png"
                                                    alt="Retail"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '26px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.86), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Retail
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '5px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                            }}
                                                        >
                                                            12.6%
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '11px',
                                                                fontWeight:
                                                                    '500',
                                                                color: 'rgba(255,255,255,.66)',
                                                            }}
                                                        >
                                                            average return, 6
                                                            months
                                                        </span>
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/52d04172.png"
                                                    alt="Transport and logistics"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '26px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.86), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Transport &amp;
                                                        logistics
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '5px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                            }}
                                                        >
                                                            13.3%
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '11px',
                                                                fontWeight:
                                                                    '500',
                                                                color: 'rgba(255,255,255,.66)',
                                                            }}
                                                        >
                                                            average return, 6
                                                            months
                                                        </span>
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/3aa9a334.png"
                                                    alt="Hospitality"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '26px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.86), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Hospitality
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '5px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                            }}
                                                        >
                                                            12.0%
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '11px',
                                                                fontWeight:
                                                                    '500',
                                                                color: 'rgba(255,255,255,.66)',
                                                            }}
                                                        >
                                                            average return, 6
                                                            months
                                                        </span>
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/9d882722.png"
                                                    alt="Construction and property"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '26px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.86), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Construction &amp;
                                                        property
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '5px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                            }}
                                                        >
                                                            13.9%
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '11px',
                                                                fontWeight:
                                                                    '500',
                                                                color: 'rgba(255,255,255,.66)',
                                                            }}
                                                        >
                                                            average return, 6
                                                            months
                                                        </span>
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/bcba6e76.png"
                                                    alt="Restaurant and bar"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '26px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.86), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Restaurant &amp; bar
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '5px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                            }}
                                                        >
                                                            12.6%
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '11px',
                                                                fontWeight:
                                                                    '500',
                                                                color: 'rgba(255,255,255,.66)',
                                                            }}
                                                        >
                                                            average return, 6
                                                            months
                                                        </span>
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/f30acdf7.png"
                                                    alt="Agriculture"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '26px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.86), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Agriculture
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '5px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                            }}
                                                        >
                                                            11.3%
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '11px',
                                                                fontWeight:
                                                                    '500',
                                                                color: 'rgba(255,255,255,.66)',
                                                            }}
                                                        >
                                                            average return, 6
                                                            months
                                                        </span>
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/3a42ae59.png"
                                                    alt="Food production"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '26px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.86), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Food production
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '5px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                            }}
                                                        >
                                                            12.0%
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '11px',
                                                                fontWeight:
                                                                    '500',
                                                                color: 'rgba(255,255,255,.66)',
                                                            }}
                                                        >
                                                            average return, 6
                                                            months
                                                        </span>
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/4f918ed8.png"
                                                    alt="Professional services"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '26px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.86), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Professional services
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '5px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                            }}
                                                        >
                                                            11.3%
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '11px',
                                                                fontWeight:
                                                                    '500',
                                                                color: 'rgba(255,255,255,.66)',
                                                            }}
                                                        >
                                                            average return, 6
                                                            months
                                                        </span>
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/9eb44d0e.png"
                                                    alt="Retail"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '26px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.86), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Retail
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '5px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                            }}
                                                        >
                                                            12.6%
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '11px',
                                                                fontWeight:
                                                                    '500',
                                                                color: 'rgba(255,255,255,.66)',
                                                            }}
                                                        >
                                                            average return, 6
                                                            months
                                                        </span>
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/52d04172.png"
                                                    alt="Transport and logistics"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '26px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.86), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Transport &amp;
                                                        logistics
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '5px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                            }}
                                                        >
                                                            13.3%
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '11px',
                                                                fontWeight:
                                                                    '500',
                                                                color: 'rgba(255,255,255,.66)',
                                                            }}
                                                        >
                                                            average return, 6
                                                            months
                                                        </span>
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/3aa9a334.png"
                                                    alt="Hospitality"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '26px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.86), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Hospitality
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '5px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                            }}
                                                        >
                                                            12.0%
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '11px',
                                                                fontWeight:
                                                                    '500',
                                                                color: 'rgba(255,255,255,.66)',
                                                            }}
                                                        >
                                                            average return, 6
                                                            months
                                                        </span>
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/9d882722.png"
                                                    alt="Construction and property"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '26px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.86), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Construction &amp;
                                                        property
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '5px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                            }}
                                                        >
                                                            13.9%
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '11px',
                                                                fontWeight:
                                                                    '500',
                                                                color: 'rgba(255,255,255,.66)',
                                                            }}
                                                        >
                                                            average return, 6
                                                            months
                                                        </span>
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/bcba6e76.png"
                                                    alt="Restaurant and bar"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '26px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.86), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Restaurant &amp; bar
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '5px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                            }}
                                                        >
                                                            12.6%
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '11px',
                                                                fontWeight:
                                                                    '500',
                                                                color: 'rgba(255,255,255,.66)',
                                                            }}
                                                        >
                                                            average return, 6
                                                            months
                                                        </span>
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/f30acdf7.png"
                                                    alt="Agriculture"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '26px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.86), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Agriculture
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '5px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                            }}
                                                        >
                                                            11.3%
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '11px',
                                                                fontWeight:
                                                                    '500',
                                                                color: 'rgba(255,255,255,.66)',
                                                            }}
                                                        >
                                                            average return, 6
                                                            months
                                                        </span>
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/3a42ae59.png"
                                                    alt="Food production"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '26px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.86), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Food production
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '5px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                            }}
                                                        >
                                                            12.0%
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '11px',
                                                                fontWeight:
                                                                    '500',
                                                                color: 'rgba(255,255,255,.66)',
                                                            }}
                                                        >
                                                            average return, 6
                                                            months
                                                        </span>
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/4f918ed8.png"
                                                    alt="Professional services"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '26px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.86), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Professional services
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '5px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                            }}
                                                        >
                                                            11.3%
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '11px',
                                                                fontWeight:
                                                                    '500',
                                                                color: 'rgba(255,255,255,.66)',
                                                            }}
                                                        >
                                                            average return, 6
                                                            months
                                                        </span>
                                                    </div>
                                                </figcaption>
                                            </figure>
                                        </div>
                                    </div>
                                </section>

                                <section
                                    id="how"
                                    style={{
                                        maxWidth: '1200px',
                                        margin: '0 auto',
                                        padding:
                                            'clamp(64px,9vw,124px) clamp(18px,4vw,44px) 0',
                                    }}
                                >
                                    <div
                                        className="rz-how-grid"
                                        style={{
                                            display: 'grid',
                                            gridTemplateColumns:
                                                'minmax(240px,1fr) 2fr',
                                            gap: 'clamp(28px,4vw,72px)',
                                            alignItems: 'start',
                                        }}
                                    >
                                        <div
                                            className="rz-rise"
                                            style={{
                                                position: 'sticky',
                                                top: '96px',
                                            }}
                                        >
                                            <div
                                                className="rz-kick"
                                                style={{
                                                    fontSize: '12.5px',
                                                    fontWeight: '700',
                                                    letterSpacing: '.1em',
                                                    color: '#1e3aff',
                                                }}
                                            >
                                                01 · HOW IT WORKS
                                            </div>
                                            <h2
                                                style={{
                                                    margin: '14px 0 0',
                                                    fontSize:
                                                        'clamp(32px,4.4vw,54px)',
                                                    lineHeight: '1',
                                                    fontWeight: '800',
                                                    letterSpacing: '-.045em',
                                                    textWrap: 'balance',
                                                }}
                                            >
                                                How investing on Rozine works
                                            </h2>
                                        </div>
                                        <div
                                            className="rz-rise"
                                            style={{
                                                minWidth: '0',
                                                display: 'flex',
                                                flexDirection: 'column',
                                            }}
                                        >
                                            <div
                                                className="rz-step"
                                                style={{
                                                    position: 'relative',
                                                    overflow: 'hidden',
                                                    padding:
                                                        'clamp(26px,3vw,38px) 0',
                                                    borderTop:
                                                        '1px solid rgba(12,24,48,.11)',
                                                }}
                                            >
                                                <span
                                                    aria-hidden="true"
                                                    style={{
                                                        position: 'absolute',
                                                        right: '-.03em',
                                                        top: '50%',
                                                        transform:
                                                            'translateY(-50%)',
                                                        fontSize:
                                                            'clamp(92px,11vw,164px)',
                                                        fontWeight: '800',
                                                        letterSpacing: '-.06em',
                                                        lineHeight: '.8',
                                                        color: 'transparent',
                                                        WebkitTextStroke:
                                                            '1.4px rgba(12,24,48,.11)',
                                                        pointerEvents: 'none',
                                                    }}
                                                >
                                                    01
                                                </span>
                                                <div
                                                    style={{
                                                        position: 'relative',
                                                        maxWidth: '34em',
                                                    }}
                                                >
                                                    <h3
                                                        style={{
                                                            margin: '0',
                                                            fontSize:
                                                                'clamp(21px,2.1vw,27px)',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.03em',
                                                        }}
                                                    >
                                                        You earn up to 18%
                                                    </h3>
                                                    <p
                                                        style={{
                                                            margin: '11px 0 0',
                                                            fontSize: '15.5px',
                                                            lineHeight: '1.68',
                                                            color: '#69748a',
                                                            textWrap: 'pretty',
                                                        }}
                                                    >
                                                        Invest from RWF 5,000,
                                                        for 3 to 6 months. Every
                                                        business carries its own
                                                        flat rate, up to 18%,
                                                        shown to you before you
                                                        invest.
                                                    </p>
                                                </div>
                                            </div>
                                            <div
                                                className="rz-step"
                                                style={{
                                                    position: 'relative',
                                                    overflow: 'hidden',
                                                    padding:
                                                        'clamp(26px,3vw,38px) 0',
                                                    borderTop:
                                                        '1px solid rgba(12,24,48,.11)',
                                                }}
                                            >
                                                <span
                                                    aria-hidden="true"
                                                    style={{
                                                        position: 'absolute',
                                                        right: '-.03em',
                                                        top: '50%',
                                                        transform:
                                                            'translateY(-50%)',
                                                        fontSize:
                                                            'clamp(92px,11vw,164px)',
                                                        fontWeight: '800',
                                                        letterSpacing: '-.06em',
                                                        lineHeight: '.8',
                                                        color: 'transparent',
                                                        WebkitTextStroke:
                                                            '1.4px rgba(12,24,48,.11)',
                                                        pointerEvents: 'none',
                                                    }}
                                                >
                                                    02
                                                </span>
                                                <div
                                                    style={{
                                                        position: 'relative',
                                                        maxWidth: '34em',
                                                    }}
                                                >
                                                    <h3
                                                        style={{
                                                            margin: '0',
                                                            fontSize:
                                                                'clamp(21px,2.1vw,27px)',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.03em',
                                                        }}
                                                    >
                                                        We check every business
                                                    </h3>
                                                    <p
                                                        style={{
                                                            margin: '11px 0 0',
                                                            fontSize: '15.5px',
                                                            lineHeight: '1.68',
                                                            color: '#69748a',
                                                            textWrap: 'pretty',
                                                        }}
                                                    >
                                                        Licensed accountants
                                                        audit each business and
                                                        check its financial
                                                        records before it can
                                                        borrow, and again every
                                                        month until you’re
                                                        repaid.
                                                    </p>
                                                </div>
                                            </div>
                                            <div
                                                className="rz-step"
                                                style={{
                                                    position: 'relative',
                                                    overflow: 'hidden',
                                                    padding:
                                                        'clamp(26px,3vw,38px) 0',
                                                    borderTop:
                                                        '1px solid rgba(12,24,48,.11)',
                                                }}
                                            >
                                                <span
                                                    aria-hidden="true"
                                                    style={{
                                                        position: 'absolute',
                                                        right: '-.03em',
                                                        top: '50%',
                                                        transform:
                                                            'translateY(-50%)',
                                                        fontSize:
                                                            'clamp(92px,11vw,164px)',
                                                        fontWeight: '800',
                                                        letterSpacing: '-.06em',
                                                        lineHeight: '.8',
                                                        color: 'transparent',
                                                        WebkitTextStroke:
                                                            '1.4px rgba(12,24,48,.11)',
                                                        pointerEvents: 'none',
                                                    }}
                                                >
                                                    03
                                                </span>
                                                <div
                                                    style={{
                                                        position: 'relative',
                                                        maxWidth: '34em',
                                                    }}
                                                >
                                                    <h3
                                                        style={{
                                                            margin: '0',
                                                            fontSize:
                                                                'clamp(21px,2.1vw,27px)',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.03em',
                                                        }}
                                                    >
                                                        You’re paid every month
                                                    </h3>
                                                    <p
                                                        style={{
                                                            margin: '11px 0 0',
                                                            fontSize: '15.5px',
                                                            lineHeight: '1.68',
                                                            color: '#69748a',
                                                            textWrap: 'pretty',
                                                        }}
                                                    >
                                                        Money lands in your
                                                        account monthly, and
                                                        everything is back
                                                        within six months. Take
                                                        it out, reinvest it in
                                                        other businesses, or
                                                        sell your notes to other
                                                        investors if you need it
                                                        sooner.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section
                                    style={{
                                        maxWidth: '1200px',
                                        margin: '0 auto',
                                        padding:
                                            'clamp(60px,9vw,120px) clamp(18px,4vw,44px) 0',
                                    }}
                                >
                                    <div
                                        className="rz-rise"
                                        style={{ maxWidth: '660px' }}
                                    >
                                        <h2
                                            style={{
                                                margin: '0',
                                                fontSize:
                                                    'clamp(30px,4vw,48px)',
                                                lineHeight: '1.05',
                                                fontWeight: '800',
                                                letterSpacing: '-.04em',
                                                textWrap: 'balance',
                                            }}
                                        >
                                            Your app experience
                                        </h2>
                                        <p
                                            style={{
                                                margin: '16px 0 0',
                                                fontSize:
                                                    'clamp(15.5px,1.3vw,18px)',
                                                lineHeight: '1.6',
                                                color: '#69748a',
                                            }}
                                        >
                                            What you see when you lend, and how
                                            you can set to invest automatically.
                                        </p>
                                    </div>

                                    <div
                                        className="rz-rise"
                                        style={{
                                            marginTop: 'clamp(30px,4vw,48px)',
                                            display: 'grid',
                                            gridTemplateColumns:
                                                'repeat(auto-fit,minmax(320px,1fr))',
                                            gap: 'clamp(18px,2.4vw,28px)',
                                        }}
                                    >
                                        <div
                                            style={{
                                                padding:
                                                    'clamp(22px,2.6vw,30px)',
                                                borderRadius: '22px',
                                                background:
                                                    'rgba(255,255,255,.58)',
                                                backdropFilter:
                                                    'blur(18px) saturate(1.45)',
                                                WebkitBackdropFilter:
                                                    'blur(18px) saturate(1.45)',
                                                border: '1px solid rgba(12,24,48,.09)',
                                                boxShadow:
                                                    '0 1px 2px rgba(12,24,48,.03), 0 20px 40px -24px rgba(12,24,48,.16)',
                                            }}
                                        >
                                            <div
                                                className="rz-kick"
                                                style={{
                                                    fontSize: '12.5px',
                                                    fontWeight: '700',
                                                    letterSpacing: '.1em',
                                                    color: '#1e3aff',
                                                }}
                                            >
                                                RETAIL INVESTING
                                            </div>
                                            <h3
                                                style={{
                                                    margin: '9px 0 0',
                                                    fontSize: '22px',
                                                    fontWeight: '700',
                                                    letterSpacing: '-.025em',
                                                }}
                                            >
                                                Pick a business and see the
                                                numbers
                                            </h3>
                                            <div
                                                style={{
                                                    position: 'relative',
                                                    maxWidth: '320px',
                                                    margin: '22px auto 0',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/6f63c223.png"
                                                    alt="The Rozine app showing a business open for lending"
                                                    style={{
                                                        display: 'block',
                                                        width: '100%',
                                                        height: 'auto',
                                                        borderRadius: '18px',
                                                    }}
                                                />
                                                <span
                                                    style={{
                                                        position: 'absolute',
                                                        top: '10%',
                                                        left: '1.5%',
                                                        zIndex: '2',
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent:
                                                            'center',
                                                        width: '27px',
                                                        height: '27px',
                                                        borderRadius: '50%',
                                                        background: '#1e3aff',
                                                        color: '#fff',
                                                        fontSize: '13px',
                                                        fontWeight: '800',
                                                        boxShadow:
                                                            '0 0 0 4px rgba(255,255,255,.92), 0 6px 16px -6px rgba(30,58,255,.7)',
                                                    }}
                                                >
                                                    1
                                                </span>
                                                <span
                                                    style={{
                                                        position: 'absolute',
                                                        top: '19%',
                                                        left: '29%',
                                                        zIndex: '2',
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent:
                                                            'center',
                                                        width: '27px',
                                                        height: '27px',
                                                        borderRadius: '50%',
                                                        background: '#1e3aff',
                                                        color: '#fff',
                                                        fontSize: '13px',
                                                        fontWeight: '800',
                                                        boxShadow:
                                                            '0 0 0 4px rgba(255,255,255,.92), 0 6px 16px -6px rgba(30,58,255,.7)',
                                                    }}
                                                >
                                                    2
                                                </span>
                                                <span
                                                    style={{
                                                        position: 'absolute',
                                                        top: '40.5%',
                                                        left: '1.5%',
                                                        zIndex: '2',
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent:
                                                            'center',
                                                        width: '27px',
                                                        height: '27px',
                                                        borderRadius: '50%',
                                                        background: '#1e3aff',
                                                        color: '#fff',
                                                        fontSize: '13px',
                                                        fontWeight: '800',
                                                        boxShadow:
                                                            '0 0 0 4px rgba(255,255,255,.92), 0 6px 16px -6px rgba(30,58,255,.7)',
                                                    }}
                                                >
                                                    3
                                                </span>
                                                <span
                                                    style={{
                                                        position: 'absolute',
                                                        top: '75.5%',
                                                        left: '1.5%',
                                                        zIndex: '2',
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent:
                                                            'center',
                                                        width: '27px',
                                                        height: '27px',
                                                        borderRadius: '50%',
                                                        background: '#1e3aff',
                                                        color: '#fff',
                                                        fontSize: '13px',
                                                        fontWeight: '800',
                                                        boxShadow:
                                                            '0 0 0 4px rgba(255,255,255,.92), 0 6px 16px -6px rgba(30,58,255,.7)',
                                                    }}
                                                >
                                                    4
                                                </span>
                                            </div>
                                            <div
                                                style={{
                                                    marginTop: '22px',
                                                    display: 'flex',
                                                    flexDirection: 'column',
                                                    gap: '11px',
                                                }}
                                            >
                                                <div
                                                    style={{
                                                        display: 'flex',
                                                        gap: '11px',
                                                        alignItems:
                                                            'flex-start',
                                                    }}
                                                >
                                                    <span
                                                        style={{
                                                            flex: '0 0 auto',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            width: '21px',
                                                            height: '21px',
                                                            marginTop: '1px',
                                                            borderRadius: '50%',
                                                            background:
                                                                'rgba(30,58,255,.1)',
                                                            color: '#1e3aff',
                                                            fontSize: '11.5px',
                                                            fontWeight: '800',
                                                        }}
                                                    >
                                                        1
                                                    </span>
                                                    <p
                                                        style={{
                                                            margin: '0',
                                                            fontSize: '14px',
                                                            lineHeight: '1.55',
                                                            color: '#69748a',
                                                        }}
                                                    >
                                                        Your wallet, and the
                                                        return charge rate.
                                                    </p>
                                                </div>
                                                <div
                                                    style={{
                                                        display: 'flex',
                                                        gap: '11px',
                                                        alignItems:
                                                            'flex-start',
                                                    }}
                                                >
                                                    <span
                                                        style={{
                                                            flex: '0 0 auto',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            width: '21px',
                                                            height: '21px',
                                                            marginTop: '1px',
                                                            borderRadius: '50%',
                                                            background:
                                                                'rgba(30,58,255,.1)',
                                                            color: '#1e3aff',
                                                            fontSize: '11.5px',
                                                            fontWeight: '800',
                                                        }}
                                                    >
                                                        2
                                                    </span>
                                                    <p
                                                        style={{
                                                            margin: '0',
                                                            fontSize: '14px',
                                                            lineHeight: '1.55',
                                                            color: '#69748a',
                                                        }}
                                                    >
                                                        Audited and rated before
                                                        it ever reaches you.
                                                    </p>
                                                </div>
                                                <div
                                                    style={{
                                                        display: 'flex',
                                                        gap: '11px',
                                                        alignItems:
                                                            'flex-start',
                                                    }}
                                                >
                                                    <span
                                                        style={{
                                                            flex: '0 0 auto',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            width: '21px',
                                                            height: '21px',
                                                            marginTop: '1px',
                                                            borderRadius: '50%',
                                                            background:
                                                                'rgba(30,58,255,.1)',
                                                            color: '#1e3aff',
                                                            fontSize: '11.5px',
                                                            fontWeight: '800',
                                                        }}
                                                    >
                                                        3
                                                    </span>
                                                    <p
                                                        style={{
                                                            margin: '0',
                                                            fontSize: '14px',
                                                            lineHeight: '1.55',
                                                            color: '#69748a',
                                                        }}
                                                    >
                                                        How much is funded, how
                                                        long is left, and how
                                                        many others are in.
                                                    </p>
                                                </div>
                                                <div
                                                    style={{
                                                        display: 'flex',
                                                        gap: '11px',
                                                        alignItems:
                                                            'flex-start',
                                                    }}
                                                >
                                                    <span
                                                        style={{
                                                            flex: '0 0 auto',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            width: '21px',
                                                            height: '21px',
                                                            marginTop: '1px',
                                                            borderRadius: '50%',
                                                            background:
                                                                'rgba(30,58,255,.1)',
                                                            color: '#1e3aff',
                                                            fontSize: '11.5px',
                                                            fontWeight: '800',
                                                        }}
                                                    >
                                                        4
                                                    </span>
                                                    <p
                                                        style={{
                                                            margin: '0',
                                                            fontSize: '14px',
                                                            lineHeight: '1.55',
                                                            color: '#69748a',
                                                        }}
                                                    >
                                                        Choose your amount and
                                                        see exactly what you
                                                        earn, before you commit.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            style={{
                                                padding:
                                                    'clamp(22px,2.6vw,30px)',
                                                borderRadius: '22px',
                                                background:
                                                    'rgba(255,255,255,.58)',
                                                backdropFilter:
                                                    'blur(18px) saturate(1.45)',
                                                WebkitBackdropFilter:
                                                    'blur(18px) saturate(1.45)',
                                                border: '1px solid rgba(12,24,48,.09)',
                                                boxShadow:
                                                    '0 1px 2px rgba(12,24,48,.03), 0 20px 40px -24px rgba(12,24,48,.16)',
                                            }}
                                        >
                                            <div
                                                className="rz-kick"
                                                style={{
                                                    fontSize: '12.5px',
                                                    fontWeight: '700',
                                                    letterSpacing: '.1em',
                                                    color: '#1e3aff',
                                                }}
                                            >
                                                AUTOMATED INVESTING
                                            </div>
                                            <h3
                                                style={{
                                                    margin: '9px 0 0',
                                                    fontSize: '22px',
                                                    fontWeight: '700',
                                                    letterSpacing: '-.025em',
                                                }}
                                            >
                                                Or set your rules once and leave
                                                it
                                            </h3>
                                            <div
                                                style={{
                                                    position: 'relative',
                                                    maxWidth: '320px',
                                                    margin: '22px auto 0',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/16a048af.png"
                                                    alt="The Rozine app showing automatic lending rules"
                                                    style={{
                                                        display: 'block',
                                                        width: '100%',
                                                        height: 'auto',
                                                        borderRadius: '18px',
                                                    }}
                                                />
                                                <span
                                                    style={{
                                                        position: 'absolute',
                                                        top: '23%',
                                                        left: '1.5%',
                                                        zIndex: '2',
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent:
                                                            'center',
                                                        width: '27px',
                                                        height: '27px',
                                                        borderRadius: '50%',
                                                        background: '#1e3aff',
                                                        color: '#fff',
                                                        fontSize: '13px',
                                                        fontWeight: '800',
                                                        boxShadow:
                                                            '0 0 0 4px rgba(255,255,255,.92), 0 6px 16px -6px rgba(30,58,255,.7)',
                                                    }}
                                                >
                                                    1
                                                </span>
                                                <span
                                                    style={{
                                                        position: 'absolute',
                                                        top: '39%',
                                                        left: '1.5%',
                                                        zIndex: '2',
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent:
                                                            'center',
                                                        width: '27px',
                                                        height: '27px',
                                                        borderRadius: '50%',
                                                        background: '#1e3aff',
                                                        color: '#fff',
                                                        fontSize: '13px',
                                                        fontWeight: '800',
                                                        boxShadow:
                                                            '0 0 0 4px rgba(255,255,255,.92), 0 6px 16px -6px rgba(30,58,255,.7)',
                                                    }}
                                                >
                                                    2
                                                </span>
                                                <span
                                                    style={{
                                                        position: 'absolute',
                                                        top: '49.2%',
                                                        left: '44.5%',
                                                        zIndex: '2',
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent:
                                                            'center',
                                                        width: '27px',
                                                        height: '27px',
                                                        borderRadius: '50%',
                                                        background: '#1e3aff',
                                                        color: '#fff',
                                                        fontSize: '13px',
                                                        fontWeight: '800',
                                                        boxShadow:
                                                            '0 0 0 4px rgba(255,255,255,.92), 0 6px 16px -6px rgba(30,58,255,.7)',
                                                    }}
                                                >
                                                    3
                                                </span>
                                                <span
                                                    style={{
                                                        position: 'absolute',
                                                        top: '79.5%',
                                                        left: '1.5%',
                                                        zIndex: '2',
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent:
                                                            'center',
                                                        width: '27px',
                                                        height: '27px',
                                                        borderRadius: '50%',
                                                        background: '#1e3aff',
                                                        color: '#fff',
                                                        fontSize: '13px',
                                                        fontWeight: '800',
                                                        boxShadow:
                                                            '0 0 0 4px rgba(255,255,255,.92), 0 6px 16px -6px rgba(30,58,255,.7)',
                                                    }}
                                                >
                                                    4
                                                </span>
                                            </div>
                                            <div
                                                style={{
                                                    marginTop: '22px',
                                                    display: 'flex',
                                                    flexDirection: 'column',
                                                    gap: '11px',
                                                }}
                                            >
                                                <div
                                                    style={{
                                                        display: 'flex',
                                                        gap: '11px',
                                                        alignItems:
                                                            'flex-start',
                                                    }}
                                                >
                                                    <span
                                                        style={{
                                                            flex: '0 0 auto',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            width: '21px',
                                                            height: '21px',
                                                            marginTop: '1px',
                                                            borderRadius: '50%',
                                                            background:
                                                                'rgba(30,58,255,.1)',
                                                            color: '#1e3aff',
                                                            fontSize: '11.5px',
                                                            fontWeight: '800',
                                                        }}
                                                    >
                                                        1
                                                    </span>
                                                    <p
                                                        style={{
                                                            margin: '0',
                                                            fontSize: '14px',
                                                            lineHeight: '1.55',
                                                            color: '#69748a',
                                                        }}
                                                    >
                                                        How much of your money
                                                        gets invested each
                                                        month.
                                                    </p>
                                                </div>
                                                <div
                                                    style={{
                                                        display: 'flex',
                                                        gap: '11px',
                                                        alignItems:
                                                            'flex-start',
                                                    }}
                                                >
                                                    <span
                                                        style={{
                                                            flex: '0 0 auto',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            width: '21px',
                                                            height: '21px',
                                                            marginTop: '1px',
                                                            borderRadius: '50%',
                                                            background:
                                                                'rgba(30,58,255,.1)',
                                                            color: '#1e3aff',
                                                            fontSize: '11.5px',
                                                            fontWeight: '800',
                                                        }}
                                                    >
                                                        2
                                                    </span>
                                                    <p
                                                        style={{
                                                            margin: '0',
                                                            fontSize: '14px',
                                                            lineHeight: '1.55',
                                                            color: '#69748a',
                                                        }}
                                                    >
                                                        The most you want to
                                                        invest in any single
                                                        business.
                                                    </p>
                                                </div>
                                                <div
                                                    style={{
                                                        display: 'flex',
                                                        gap: '11px',
                                                        alignItems:
                                                            'flex-start',
                                                    }}
                                                >
                                                    <span
                                                        style={{
                                                            flex: '0 0 auto',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            width: '21px',
                                                            height: '21px',
                                                            marginTop: '1px',
                                                            borderRadius: '50%',
                                                            background:
                                                                'rgba(30,58,255,.1)',
                                                            color: '#1e3aff',
                                                            fontSize: '11.5px',
                                                            fontWeight: '800',
                                                        }}
                                                    >
                                                        3
                                                    </span>
                                                    <p
                                                        style={{
                                                            margin: '0',
                                                            fontSize: '14px',
                                                            lineHeight: '1.55',
                                                            color: '#69748a',
                                                        }}
                                                    >
                                                        Your charge falls the
                                                        more you lend. It shows
                                                        the investment threshold
                                                        that unlocks each
                                                        discount.
                                                    </p>
                                                </div>
                                                <div
                                                    style={{
                                                        display: 'flex',
                                                        gap: '11px',
                                                        alignItems:
                                                            'flex-start',
                                                    }}
                                                >
                                                    <span
                                                        style={{
                                                            flex: '0 0 auto',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            width: '21px',
                                                            height: '21px',
                                                            marginTop: '1px',
                                                            borderRadius: '50%',
                                                            background:
                                                                'rgba(30,58,255,.1)',
                                                            color: '#1e3aff',
                                                            fontSize: '11.5px',
                                                            fontWeight: '800',
                                                        }}
                                                    >
                                                        4
                                                    </span>
                                                    <p
                                                        style={{
                                                            margin: '0',
                                                            fontSize: '14px',
                                                            lineHeight: '1.55',
                                                            color: '#69748a',
                                                        }}
                                                    >
                                                        Whether you buy into new
                                                        businesses first, or
                                                        from investors exiting
                                                        early, or both.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section
                                    id="protection"
                                    data-rz-blue=""
                                    style={{
                                        position: 'relative',
                                        overflow: 'clip',
                                        marginTop: 'clamp(64px,9vw,124px)',
                                        background:
                                            'radial-gradient(130% 110% at 80% 0%, #1e3aff 0%, #1428a4 32%, #0a1440 72%, #070f30 100%)',
                                    }}
                                >
                                    <span
                                        aria-hidden="true"
                                        style={{
                                            position: 'absolute',
                                            inset: '0',
                                            opacity: '.45',
                                            backgroundImage:
                                                'linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px)',
                                            backgroundSize: '100% 48px',
                                            pointerEvents: 'none',
                                        }}
                                    ></span>
                                    <div
                                        style={{
                                            position: 'relative',
                                            maxWidth: '1200px',
                                            margin: '0 auto',
                                            padding:
                                                'clamp(60px,8vw,104px) clamp(18px,4vw,44px)',
                                        }}
                                    >
                                        <div
                                            className="rz-rise"
                                            style={{ maxWidth: '640px' }}
                                        >
                                            <div
                                                className="rz-kick"
                                                style={{
                                                    fontSize: '12.5px',
                                                    fontWeight: '700',
                                                    letterSpacing: '.1em',
                                                    color: '#7f92ff',
                                                }}
                                            >
                                                02 · YOUR PROTECTION
                                            </div>
                                            <h2
                                                style={{
                                                    margin: '11px 0 0',
                                                    fontSize:
                                                        'clamp(30px,4vw,48px)',
                                                    lineHeight: '1.05',
                                                    fontWeight: '800',
                                                    letterSpacing: '-.04em',
                                                    color: '#fff',
                                                }}
                                            >
                                                The journey of your franc.
                                            </h2>
                                            <p
                                                style={{
                                                    margin: '16px 0 0',
                                                    fontSize:
                                                        'clamp(15.5px,1.3vw,18px)',
                                                    lineHeight: '1.6',
                                                    color: 'rgba(255,255,255,.62)',
                                                }}
                                            >
                                                Here is one franc of your money
                                                from the moment you put it in
                                                until it comes back, including
                                                what happens when things go
                                                wrong.
                                            </p>
                                        </div>
                                        <div
                                            className="rz-rise"
                                            style={{
                                                marginTop:
                                                    'clamp(28px,4vw,44px)',
                                                maxWidth: '780px',
                                                display: 'flex',
                                                flexDirection: 'column',
                                            }}
                                        >
                                            <div
                                                style={{
                                                    display: 'flex',
                                                    gap: 'clamp(16px,2.5vw,28px)',
                                                }}
                                            >
                                                <div
                                                    style={{
                                                        flex: '0 0 auto',
                                                        display: 'flex',
                                                        flexDirection: 'column',
                                                        alignItems: 'center',
                                                    }}
                                                >
                                                    <div
                                                        className="rz-badge"
                                                        style={{
                                                            width: '40px',
                                                            height: '40px',
                                                            borderRadius:
                                                                '12px',
                                                            background: '#fff',
                                                            color: '#0a1440',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            fontSize: '16px',
                                                            fontWeight: '800',
                                                            boxShadow:
                                                                '0 6px 18px -6px rgba(0,0,0,.7)',
                                                        }}
                                                    >
                                                        1
                                                    </div>
                                                    <div
                                                        className="rz-line"
                                                        style={{
                                                            flex: '1 1 auto',
                                                            width: '2px',
                                                            background:
                                                                'linear-gradient(#fff,rgba(255,255,255,.25))',
                                                            opacity: '.5',
                                                            margin: '8px 0',
                                                        }}
                                                    ></div>
                                                </div>
                                                <div
                                                    style={{
                                                        paddingBottom:
                                                            'clamp(24px,3vw,36px)',
                                                        minWidth: '0',
                                                    }}
                                                >
                                                    <h3
                                                        style={{
                                                            margin: '6px 0 0',
                                                            fontSize: '20px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.025em',
                                                            color: '#fff',
                                                        }}
                                                    >
                                                        You put money in
                                                    </h3>
                                                    <p
                                                        style={{
                                                            margin: '8px 0 0',
                                                            fontSize: '15.5px',
                                                            lineHeight: '1.7',
                                                            color: 'rgba(255,255,255,.6)',
                                                        }}
                                                    >
                                                        It sits at our licensed
                                                        partner bank, in your
                                                        name, and shows in your
                                                        Rozine wallet. Rozine
                                                        can never spend it.
                                                    </p>
                                                </div>
                                            </div>
                                            <div
                                                style={{
                                                    display: 'flex',
                                                    gap: 'clamp(16px,2.5vw,28px)',
                                                }}
                                            >
                                                <div
                                                    style={{
                                                        flex: '0 0 auto',
                                                        display: 'flex',
                                                        flexDirection: 'column',
                                                        alignItems: 'center',
                                                    }}
                                                >
                                                    <div
                                                        className="rz-badge"
                                                        style={{
                                                            width: '40px',
                                                            height: '40px',
                                                            borderRadius:
                                                                '12px',
                                                            background: '#fff',
                                                            color: '#0a1440',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            fontSize: '16px',
                                                            fontWeight: '800',
                                                            boxShadow:
                                                                '0 6px 18px -6px rgba(0,0,0,.7)',
                                                        }}
                                                    >
                                                        2
                                                    </div>
                                                    <div
                                                        className="rz-line"
                                                        style={{
                                                            flex: '1 1 auto',
                                                            width: '2px',
                                                            background:
                                                                'linear-gradient(#fff,rgba(255,255,255,.25))',
                                                            opacity: '.5',
                                                            margin: '8px 0',
                                                        }}
                                                    ></div>
                                                </div>
                                                <div
                                                    style={{
                                                        paddingBottom:
                                                            'clamp(24px,3vw,36px)',
                                                        minWidth: '0',
                                                    }}
                                                >
                                                    <h3
                                                        style={{
                                                            margin: '6px 0 0',
                                                            fontSize: '20px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.025em',
                                                            color: '#fff',
                                                        }}
                                                    >
                                                        A business asks to
                                                        borrow
                                                    </h3>
                                                    <p
                                                        style={{
                                                            margin: '8px 0 0',
                                                            fontSize: '15.5px',
                                                            lineHeight: '1.7',
                                                            color: 'rgba(255,255,255,.6)',
                                                        }}
                                                    >
                                                        A licensed accountant
                                                        visits in person and
                                                        checks the books, the
                                                        sales records and the
                                                        bank statements. Only
                                                        businesses with solid
                                                        profit year after year
                                                        are approved.
                                                    </p>
                                                </div>
                                            </div>
                                            <div
                                                style={{
                                                    display: 'flex',
                                                    gap: 'clamp(16px,2.5vw,28px)',
                                                }}
                                            >
                                                <div
                                                    style={{
                                                        flex: '0 0 auto',
                                                        display: 'flex',
                                                        flexDirection: 'column',
                                                        alignItems: 'center',
                                                    }}
                                                >
                                                    <div
                                                        className="rz-badge"
                                                        style={{
                                                            width: '40px',
                                                            height: '40px',
                                                            borderRadius:
                                                                '12px',
                                                            background: '#fff',
                                                            color: '#0a1440',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            fontSize: '16px',
                                                            fontWeight: '800',
                                                            boxShadow:
                                                                '0 6px 18px -6px rgba(0,0,0,.7)',
                                                        }}
                                                    >
                                                        3
                                                    </div>
                                                    <div
                                                        className="rz-line"
                                                        style={{
                                                            flex: '1 1 auto',
                                                            width: '2px',
                                                            background:
                                                                'linear-gradient(#fff,rgba(255,255,255,.25))',
                                                            opacity: '.5',
                                                            margin: '8px 0',
                                                        }}
                                                    ></div>
                                                </div>
                                                <div
                                                    style={{
                                                        paddingBottom:
                                                            'clamp(24px,3vw,36px)',
                                                        minWidth: '0',
                                                    }}
                                                >
                                                    <h3
                                                        style={{
                                                            margin: '6px 0 0',
                                                            fontSize: '20px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.025em',
                                                            color: '#fff',
                                                        }}
                                                    >
                                                        You lend, and it's
                                                        written in your name
                                                    </h3>
                                                    <p
                                                        style={{
                                                            margin: '8px 0 0',
                                                            fontSize: '15.5px',
                                                            lineHeight: '1.7',
                                                            color: 'rgba(255,255,255,.6)',
                                                        }}
                                                    >
                                                        The loan is recorded in{' '}
                                                        <strong
                                                            style={{
                                                                color: '#fff',
                                                            }}
                                                        >
                                                            your
                                                        </strong>{' '}
                                                        name, so the business
                                                        owes you directly. And
                                                        no single business can
                                                        take more than a small
                                                        slice of all investors'
                                                        money. The system blocks
                                                        it automatically.
                                                    </p>
                                                </div>
                                            </div>
                                            <div
                                                style={{
                                                    display: 'flex',
                                                    gap: 'clamp(16px,2.5vw,28px)',
                                                }}
                                            >
                                                <div
                                                    style={{
                                                        flex: '0 0 auto',
                                                        display: 'flex',
                                                        flexDirection: 'column',
                                                        alignItems: 'center',
                                                    }}
                                                >
                                                    <div
                                                        className="rz-badge"
                                                        style={{
                                                            width: '40px',
                                                            height: '40px',
                                                            borderRadius:
                                                                '12px',
                                                            background: '#fff',
                                                            color: '#0a1440',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            fontSize: '16px',
                                                            fontWeight: '800',
                                                            boxShadow:
                                                                '0 6px 18px -6px rgba(0,0,0,.7)',
                                                        }}
                                                    >
                                                        4
                                                    </div>
                                                    <div
                                                        className="rz-line"
                                                        style={{
                                                            flex: '1 1 auto',
                                                            width: '2px',
                                                            background:
                                                                'linear-gradient(#fff,rgba(255,255,255,.25))',
                                                            opacity: '.5',
                                                            margin: '8px 0',
                                                        }}
                                                    ></div>
                                                </div>
                                                <div
                                                    style={{
                                                        paddingBottom:
                                                            'clamp(24px,3vw,36px)',
                                                        minWidth: '0',
                                                    }}
                                                >
                                                    <h3
                                                        style={{
                                                            margin: '6px 0 0',
                                                            fontSize: '20px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.025em',
                                                            color: '#fff',
                                                        }}
                                                    >
                                                        You're repaid every
                                                        month
                                                    </h3>
                                                    <p
                                                        style={{
                                                            margin: '8px 0 0',
                                                            fontSize: '15.5px',
                                                            lineHeight: '1.7',
                                                            color: 'rgba(255,255,255,.6)',
                                                        }}
                                                    >
                                                        Every month, a piece of
                                                        your money plus your
                                                        profit lands in your
                                                        Rozine account. An
                                                        accountant audits the
                                                        business monthly to
                                                        report on its status.
                                                    </p>
                                                </div>
                                            </div>
                                            <div
                                                style={{
                                                    display: 'flex',
                                                    gap: 'clamp(16px,2.5vw,28px)',
                                                }}
                                            >
                                                <div
                                                    style={{
                                                        flex: '0 0 auto',
                                                        display: 'flex',
                                                        flexDirection: 'column',
                                                        alignItems: 'center',
                                                    }}
                                                >
                                                    <div
                                                        className="rz-badge"
                                                        style={{
                                                            width: '40px',
                                                            height: '40px',
                                                            borderRadius:
                                                                '12px',
                                                            background:
                                                                '#4ecb9d',
                                                            color: '#04240f',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            fontSize: '16px',
                                                            fontWeight: '800',
                                                            boxShadow:
                                                                '0 6px 18px -6px rgba(78,203,157,.8)',
                                                        }}
                                                    >
                                                        5
                                                    </div>
                                                </div>
                                                <div style={{ minWidth: '0' }}>
                                                    <h3
                                                        style={{
                                                            margin: '6px 0 0',
                                                            fontSize: '20px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.025em',
                                                            color: '#fff',
                                                        }}
                                                    >
                                                        6 months in the longest
                                                        term
                                                    </h3>
                                                    <p
                                                        style={{
                                                            margin: '8px 0 0',
                                                            fontSize: '15.5px',
                                                            lineHeight: '1.7',
                                                            color: 'rgba(255,255,255,.6)',
                                                        }}
                                                    >
                                                        Every loan runs for 3 to
                                                        6 months, and you see
                                                        that information before
                                                        you invest. By the final
                                                        month, your principal
                                                        plus your profit is
                                                        returned.
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section
                                    style={{
                                        maxWidth: '1200px',
                                        margin: '0 auto',
                                        padding:
                                            'clamp(60px,9vw,120px) clamp(18px,4vw,44px) 0',
                                    }}
                                >
                                    <div
                                        className="rz-rise"
                                        style={{ maxWidth: '640px' }}
                                    >
                                        <h2
                                            style={{
                                                margin: '0',
                                                fontSize:
                                                    'clamp(28px,3.6vw,44px)',
                                                lineHeight: '1.05',
                                                fontWeight: '800',
                                                letterSpacing: '-.04em',
                                            }}
                                        >
                                            And when things go wrong?
                                        </h2>
                                        <p
                                            style={{
                                                margin: '14px 0 0',
                                                fontSize:
                                                    'clamp(15.5px,1.3vw,17.5px)',
                                                lineHeight: '1.65',
                                                color: '#69748a',
                                            }}
                                        >
                                            They sometimes do. Here is exactly
                                            what happens.
                                        </p>
                                    </div>
                                    <div
                                        className="rz-rise"
                                        style={{
                                            marginTop: 'clamp(28px,4vw,44px)',
                                            display: 'grid',
                                            gridTemplateColumns:
                                                'repeat(auto-fit,minmax(300px,1fr))',
                                            gap: 'clamp(16px,2vw,24px)',
                                        }}
                                    >
                                        <div
                                            style={{
                                                padding:
                                                    'clamp(24px,2.8vw,32px)',
                                                borderRadius: '20px',
                                                background:
                                                    'rgba(255,255,255,.58)',
                                                backdropFilter:
                                                    'blur(18px) saturate(1.45)',
                                                WebkitBackdropFilter:
                                                    'blur(18px) saturate(1.45)',
                                                border: '1px solid rgba(12,24,48,.09)',
                                                boxShadow:
                                                    '0 1px 2px rgba(12,24,48,.03), 0 20px 40px -24px rgba(12,24,48,.16)',
                                            }}
                                        >
                                            <h3
                                                style={{
                                                    margin: '0',
                                                    fontSize: '19px',
                                                    fontWeight: '700',
                                                    letterSpacing: '-.02em',
                                                }}
                                            >
                                                A business pays late
                                            </h3>
                                            <p
                                                style={{
                                                    margin: '10px 0 0',
                                                    fontSize: '15px',
                                                    lineHeight: '1.7',
                                                    color: '#69748a',
                                                }}
                                            >
                                                We work with the business on a
                                                loan restructure. The reserve
                                                fund keeps some of your monthly
                                                payment arriving.
                                            </p>
                                        </div>
                                        <div
                                            style={{
                                                position: 'relative',
                                                overflow: 'hidden',
                                                padding:
                                                    'clamp(24px,2.8vw,32px)',
                                                borderRadius: '20px',
                                                background:
                                                    'linear-gradient(150deg, #1e3aff 0%, #1428a4 62%, #0f1f7a 100%)',
                                                boxShadow:
                                                    '0 26px 52px -26px rgba(20,40,164,.55)',
                                            }}
                                        >
                                            <span
                                                aria-hidden="true"
                                                style={{
                                                    position: 'absolute',
                                                    right: '-14%',
                                                    top: '-38%',
                                                    width: '64%',
                                                    aspectRatio: '1',
                                                    borderRadius: '50%',
                                                    background:
                                                        'radial-gradient(circle, rgba(255,255,255,.2), transparent 68%)',
                                                }}
                                            ></span>
                                            <div
                                                style={{ position: 'relative' }}
                                            >
                                                <h3
                                                    style={{
                                                        margin: '0',
                                                        fontSize: '19px',
                                                        fontWeight: '700',
                                                        letterSpacing: '-.02em',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    A business fails completely
                                                </h3>
                                                <p
                                                    style={{
                                                        margin: '10px 0 0',
                                                        fontSize: '15px',
                                                        lineHeight: '1.7',
                                                        color: 'rgba(255,255,255,.72)',
                                                    }}
                                                >
                                                    The reserve fund covers some
                                                    of the loss. That's the real
                                                    risk, and why we never call
                                                    your money guarantee.
                                                </p>
                                            </div>
                                        </div>
                                        <div
                                            style={{
                                                padding:
                                                    'clamp(24px,2.8vw,32px)',
                                                borderRadius: '20px',
                                                background:
                                                    'rgba(255,255,255,.58)',
                                                backdropFilter:
                                                    'blur(18px) saturate(1.45)',
                                                WebkitBackdropFilter:
                                                    'blur(18px) saturate(1.45)',
                                                border: '1px solid rgba(12,24,48,.09)',
                                                boxShadow:
                                                    '0 1px 2px rgba(12,24,48,.03), 0 20px 40px -24px rgba(12,24,48,.16)',
                                            }}
                                        >
                                            <h3
                                                style={{
                                                    margin: '0',
                                                    fontSize: '19px',
                                                    fontWeight: '700',
                                                    letterSpacing: '-.02em',
                                                }}
                                            >
                                                The loan is in your name
                                            </h3>
                                            <p
                                                style={{
                                                    margin: '10px 0 0',
                                                    fontSize: '15px',
                                                    lineHeight: '1.7',
                                                    color: '#69748a',
                                                }}
                                            >
                                                Your money sits at a licensed
                                                bank, never with Rozine, and
                                                every loan is recorded in your
                                                name. The business owes and pays
                                                you directly.
                                            </p>
                                        </div>
                                    </div>
                                </section>

                                <section
                                    style={{
                                        maxWidth: '1200px',
                                        margin: '0 auto',
                                        padding:
                                            'clamp(60px,9vw,120px) clamp(18px,4vw,44px) 0',
                                    }}
                                >
                                    <div
                                        className="rz-rise"
                                        data-rz-blue=""
                                        style={{
                                            position: 'relative',
                                            overflow: 'hidden',
                                            borderRadius: '24px',
                                            background: '#1428a4',
                                            padding: 'clamp(28px,3.5vw,48px)',
                                            display: 'flex',
                                            flexWrap: 'wrap',
                                            gap: 'clamp(24px,4vw,56px)',
                                            alignItems: 'center',
                                        }}
                                    >
                                        <div
                                            style={{
                                                position: 'relative',
                                                zIndex: '1',
                                                flex: '1 1 340px',
                                                minWidth: '0',
                                            }}
                                        >
                                            <h2
                                                style={{
                                                    margin: '0',
                                                    fontSize:
                                                        'clamp(24px,3vw,36px)',
                                                    fontWeight: '800',
                                                    letterSpacing: '-.035em',
                                                    lineHeight: '1.1',
                                                    color: '#fff',
                                                }}
                                            >
                                                The safety fund.
                                            </h2>
                                            <p
                                                style={{
                                                    margin: '14px 0 0',
                                                    fontSize: '15.5px',
                                                    lineHeight: '1.7',
                                                    color: 'rgba(255,255,255,.65)',
                                                    maxWidth: '520px',
                                                }}
                                            >
                                                Every business that borrows pays
                                                a small extra amount into one
                                                shared pot. If a business fails
                                                to pay, the pot pays you first.
                                                A very bad year could empty it,
                                                but one bad business doesn't
                                                come straight out of your
                                                pocket.
                                            </p>
                                        </div>
                                        <div
                                            style={{
                                                position: 'relative',
                                                zIndex: '1',
                                                flex: '0 1 320px',
                                                minWidth: '0',
                                                display: 'flex',
                                                flexDirection: 'column',
                                                gap: '10px',
                                            }}
                                        >
                                            <div
                                                style={{
                                                    padding: '16px 18px',
                                                    borderRadius: '14px',
                                                    background:
                                                        'rgba(255,255,255,.07)',
                                                    display: 'flex',
                                                    justifyContent:
                                                        'space-between',
                                                    gap: '12px',
                                                }}
                                            >
                                                <span
                                                    style={{
                                                        fontSize: '14px',
                                                        color: 'rgba(255,255,255,.6)',
                                                    }}
                                                >
                                                    Who fills it
                                                </span>
                                                <span
                                                    style={{
                                                        fontSize: '14px',
                                                        fontWeight: '700',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    Every borrower
                                                </span>
                                            </div>
                                            <div
                                                style={{
                                                    padding: '16px 18px',
                                                    borderRadius: '14px',
                                                    background:
                                                        'rgba(255,255,255,.07)',
                                                    display: 'flex',
                                                    justifyContent:
                                                        'space-between',
                                                    gap: '12px',
                                                }}
                                            >
                                                <span
                                                    style={{
                                                        fontSize: '14px',
                                                        color: 'rgba(255,255,255,.6)',
                                                    }}
                                                >
                                                    Who it pays
                                                </span>
                                                <span
                                                    style={{
                                                        fontSize: '14px',
                                                        fontWeight: '700',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    You, the investor
                                                </span>
                                            </div>
                                            <div
                                                style={{
                                                    padding: '16px 18px',
                                                    borderRadius: '14px',
                                                    background:
                                                        'rgba(255,255,255,.07)',
                                                    display: 'flex',
                                                    justifyContent:
                                                        'space-between',
                                                    gap: '12px',
                                                }}
                                            >
                                                <span
                                                    style={{
                                                        fontSize: '14px',
                                                        color: 'rgba(255,255,255,.6)',
                                                    }}
                                                >
                                                    Is it unlimited
                                                </span>
                                                <span
                                                    style={{
                                                        fontSize: '14px',
                                                        fontWeight: '700',
                                                        color: '#4ecb9d',
                                                    }}
                                                >
                                                    No.
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section
                                    style={{
                                        maxWidth: '1200px',
                                        margin: '0 auto',
                                        padding:
                                            'clamp(60px,9vw,120px) clamp(18px,4vw,44px) 0',
                                    }}
                                >
                                    <div
                                        className="rz-rise"
                                        style={{
                                            display: 'flex',
                                            flexWrap: 'wrap',
                                            gap: 'clamp(28px,4.5vw,64px)',
                                            alignItems: 'flex-start',
                                        }}
                                    >
                                        <div
                                            style={{
                                                flex: '1 1 280px',
                                                minWidth: '0',
                                            }}
                                        >
                                            <h2
                                                style={{
                                                    margin: '0',
                                                    fontSize:
                                                        'clamp(28px,3.4vw,42px)',
                                                    lineHeight: '1.05',
                                                    fontWeight: '800',
                                                    letterSpacing: '-.04em',
                                                }}
                                            >
                                                Questions people ask.
                                            </h2>
                                        </div>
                                        <div
                                            style={{
                                                flex: '1 1 460px',
                                                minWidth: '0',
                                            }}
                                        >
                                            <div
                                                className="rz-glass"
                                                style={{
                                                    marginBottom: '9px',
                                                    borderRadius: '16px',
                                                    overflow: 'hidden',
                                                    background:
                                                        'rgba(255,255,255,.58)',
                                                    backdropFilter:
                                                        'blur(18px) saturate(1.45)',
                                                    WebkitBackdropFilter:
                                                        'blur(18px) saturate(1.45)',
                                                    border: '1px solid rgba(12,24,48,.09)',
                                                    boxShadow:
                                                        '0 1px 2px rgba(12,24,48,.03), 0 20px 40px -24px rgba(12,24,48,.16)',
                                                }}
                                            >
                                                <button
                                                    onClick={faq0}
                                                    style={{
                                                        width: '100%',
                                                        padding: '18px 20px',
                                                        display: 'flex',
                                                        alignItems: 'baseline',
                                                        gap: '16px',
                                                        background: 'none',
                                                        border: 'none',
                                                        cursor: 'pointer',
                                                        textAlign: 'left',
                                                        fontFamily: 'inherit',
                                                    }}
                                                >
                                                    <span
                                                        style={{
                                                            flex: '1 1 auto',
                                                            fontSize:
                                                                'clamp(16px,1.5vw,18px)',
                                                            fontWeight: '600',
                                                            letterSpacing:
                                                                '-.02em',
                                                        }}
                                                    >
                                                        Can you lose money?
                                                    </span>
                                                    <span
                                                        style={{
                                                            flex: '0 0 auto',
                                                            width: '22px',
                                                            height: '22px',
                                                            borderRadius: '7px',
                                                            background:
                                                                'rgba(30,58,255,.09)',
                                                            color: '#1e3aff',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            lineHeight: '1',
                                                        }}
                                                    >
                                                        {f0m}
                                                    </span>
                                                </button>
                                                {f0 ? (
                                                    <>
                                                        <p
                                                            style={{
                                                                margin: '0',
                                                                padding:
                                                                    '0 20px 19px',
                                                                maxWidth:
                                                                    '560px',
                                                                fontSize:
                                                                    '15px',
                                                                lineHeight:
                                                                    '1.7',
                                                                color: '#69748a',
                                                                animation:
                                                                    'rz-pop .3s ease both',
                                                            }}
                                                        >
                                                            You can lose money,
                                                            and returns are not
                                                            guaranteed. Rozine
                                                            uses three
                                                            strategies to limit
                                                            losses: only
                                                            businesses with
                                                            proven profit are
                                                            shown to you, the
                                                            reserve fund pays
                                                            you back some of a
                                                            loss, and no
                                                            investor can take
                                                            more than 50% of a
                                                            business’s raise
                                                            target, so several
                                                            investors share the
                                                            risk together.
                                                        </p>
                                                    </>
                                                ) : null}
                                            </div>
                                            <div
                                                className="rz-glass"
                                                style={{
                                                    marginBottom: '9px',
                                                    borderRadius: '16px',
                                                    overflow: 'hidden',
                                                    background:
                                                        'rgba(255,255,255,.58)',
                                                    backdropFilter:
                                                        'blur(18px) saturate(1.45)',
                                                    WebkitBackdropFilter:
                                                        'blur(18px) saturate(1.45)',
                                                    border: '1px solid rgba(12,24,48,.09)',
                                                    boxShadow:
                                                        '0 1px 2px rgba(12,24,48,.03), 0 20px 40px -24px rgba(12,24,48,.16)',
                                                }}
                                            >
                                                <button
                                                    onClick={faq1}
                                                    style={{
                                                        width: '100%',
                                                        padding: '18px 20px',
                                                        display: 'flex',
                                                        alignItems: 'baseline',
                                                        gap: '16px',
                                                        background: 'none',
                                                        border: 'none',
                                                        cursor: 'pointer',
                                                        textAlign: 'left',
                                                        fontFamily: 'inherit',
                                                    }}
                                                >
                                                    <span
                                                        style={{
                                                            flex: '1 1 auto',
                                                            fontSize:
                                                                'clamp(16px,1.5vw,18px)',
                                                            fontWeight: '600',
                                                            letterSpacing:
                                                                '-.02em',
                                                        }}
                                                    >
                                                        How does Rozine make
                                                        money?
                                                    </span>
                                                    <span
                                                        style={{
                                                            flex: '0 0 auto',
                                                            width: '22px',
                                                            height: '22px',
                                                            borderRadius: '7px',
                                                            background:
                                                                'rgba(30,58,255,.09)',
                                                            color: '#1e3aff',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            lineHeight: '1',
                                                        }}
                                                    >
                                                        {f1m}
                                                    </span>
                                                </button>
                                                {f1 ? (
                                                    <>
                                                        <div
                                                            style={{
                                                                padding:
                                                                    '0 20px 19px',
                                                                maxWidth:
                                                                    '560px',
                                                                fontSize:
                                                                    '15px',
                                                                lineHeight:
                                                                    '1.7',
                                                                color: '#69748a',
                                                                animation:
                                                                    'rz-pop .3s ease both',
                                                            }}
                                                        >
                                                            <p
                                                                style={{
                                                                    margin: '0',
                                                                }}
                                                            >
                                                                We take a return
                                                                charge on your
                                                                profit each time
                                                                a business
                                                                repays you.
                                                                Never on your
                                                                principal.
                                                            </p>
                                                            <div
                                                                style={{
                                                                    marginTop:
                                                                        '12px',
                                                                    display:
                                                                        'flex',
                                                                    flexDirection:
                                                                        'column',
                                                                    gap: '6px',
                                                                }}
                                                            >
                                                                <div
                                                                    style={{
                                                                        display:
                                                                            'flex',
                                                                        alignItems:
                                                                            'baseline',
                                                                        gap: '10px',
                                                                    }}
                                                                >
                                                                    <span
                                                                        aria-hidden="true"
                                                                        style={{
                                                                            flex: '0 0 auto',
                                                                            width: '5px',
                                                                            height: '5px',
                                                                            borderRadius:
                                                                                '50%',
                                                                            background:
                                                                                '#1e3aff',
                                                                            position:
                                                                                'relative',
                                                                            top: '-3px',
                                                                        }}
                                                                    ></span>
                                                                    <span
                                                                        style={{
                                                                            flex: '0 0 auto',
                                                                            width: '44px',
                                                                            fontWeight:
                                                                                '700',
                                                                            color: '#0c1830',
                                                                        }}
                                                                    >
                                                                        10%
                                                                    </span>
                                                                    <span>
                                                                        under
                                                                        RWF
                                                                        1,000,000
                                                                    </span>
                                                                </div>
                                                                <div
                                                                    style={{
                                                                        display:
                                                                            'flex',
                                                                        alignItems:
                                                                            'baseline',
                                                                        gap: '10px',
                                                                    }}
                                                                >
                                                                    <span
                                                                        aria-hidden="true"
                                                                        style={{
                                                                            flex: '0 0 auto',
                                                                            width: '5px',
                                                                            height: '5px',
                                                                            borderRadius:
                                                                                '50%',
                                                                            background:
                                                                                '#1e3aff',
                                                                            position:
                                                                                'relative',
                                                                            top: '-3px',
                                                                        }}
                                                                    ></span>
                                                                    <span
                                                                        style={{
                                                                            flex: '0 0 auto',
                                                                            width: '44px',
                                                                            fontWeight:
                                                                                '700',
                                                                            color: '#0c1830',
                                                                        }}
                                                                    >
                                                                        8%
                                                                    </span>
                                                                    <span>
                                                                        RWF
                                                                        1,000,000
                                                                        and up
                                                                    </span>
                                                                </div>
                                                                <div
                                                                    style={{
                                                                        display:
                                                                            'flex',
                                                                        alignItems:
                                                                            'baseline',
                                                                        gap: '10px',
                                                                    }}
                                                                >
                                                                    <span
                                                                        aria-hidden="true"
                                                                        style={{
                                                                            flex: '0 0 auto',
                                                                            width: '5px',
                                                                            height: '5px',
                                                                            borderRadius:
                                                                                '50%',
                                                                            background:
                                                                                '#1e3aff',
                                                                            position:
                                                                                'relative',
                                                                            top: '-3px',
                                                                        }}
                                                                    ></span>
                                                                    <span
                                                                        style={{
                                                                            flex: '0 0 auto',
                                                                            width: '44px',
                                                                            fontWeight:
                                                                                '700',
                                                                            color: '#0c1830',
                                                                        }}
                                                                    >
                                                                        7%
                                                                    </span>
                                                                    <span>
                                                                        RWF
                                                                        5,000,000
                                                                        and up
                                                                    </span>
                                                                </div>
                                                                <div
                                                                    style={{
                                                                        display:
                                                                            'flex',
                                                                        alignItems:
                                                                            'baseline',
                                                                        gap: '10px',
                                                                    }}
                                                                >
                                                                    <span
                                                                        aria-hidden="true"
                                                                        style={{
                                                                            flex: '0 0 auto',
                                                                            width: '5px',
                                                                            height: '5px',
                                                                            borderRadius:
                                                                                '50%',
                                                                            background:
                                                                                '#1e3aff',
                                                                            position:
                                                                                'relative',
                                                                            top: '-3px',
                                                                        }}
                                                                    ></span>
                                                                    <span
                                                                        style={{
                                                                            flex: '0 0 auto',
                                                                            width: '44px',
                                                                            fontWeight:
                                                                                '700',
                                                                            color: '#0c1830',
                                                                        }}
                                                                    >
                                                                        6%
                                                                    </span>
                                                                    <span>
                                                                        RWF
                                                                        25,000,000
                                                                        and up
                                                                    </span>
                                                                </div>
                                                                <div
                                                                    style={{
                                                                        display:
                                                                            'flex',
                                                                        alignItems:
                                                                            'baseline',
                                                                        gap: '10px',
                                                                    }}
                                                                >
                                                                    <span
                                                                        aria-hidden="true"
                                                                        style={{
                                                                            flex: '0 0 auto',
                                                                            width: '5px',
                                                                            height: '5px',
                                                                            borderRadius:
                                                                                '50%',
                                                                            background:
                                                                                '#1e3aff',
                                                                            position:
                                                                                'relative',
                                                                            top: '-3px',
                                                                        }}
                                                                    ></span>
                                                                    <span
                                                                        style={{
                                                                            flex: '0 0 auto',
                                                                            width: '44px',
                                                                            fontWeight:
                                                                                '700',
                                                                            color: '#0c1830',
                                                                        }}
                                                                    >
                                                                        5%
                                                                    </span>
                                                                    <span>
                                                                        RWF
                                                                        75,000,000
                                                                        and up
                                                                    </span>
                                                                </div>
                                                                <div
                                                                    style={{
                                                                        display:
                                                                            'flex',
                                                                        alignItems:
                                                                            'baseline',
                                                                        gap: '10px',
                                                                    }}
                                                                >
                                                                    <span
                                                                        aria-hidden="true"
                                                                        style={{
                                                                            flex: '0 0 auto',
                                                                            width: '5px',
                                                                            height: '5px',
                                                                            borderRadius:
                                                                                '50%',
                                                                            background:
                                                                                '#1e3aff',
                                                                            position:
                                                                                'relative',
                                                                            top: '-3px',
                                                                        }}
                                                                    ></span>
                                                                    <span
                                                                        style={{
                                                                            flex: '0 0 auto',
                                                                            width: '44px',
                                                                            fontWeight:
                                                                                '700',
                                                                            color: '#0c1830',
                                                                        }}
                                                                    >
                                                                        4.5%
                                                                    </span>
                                                                    <span>
                                                                        RWF
                                                                        150,000,000
                                                                        and up
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <div
                                                                style={{
                                                                    marginTop:
                                                                        '13px',
                                                                    display:
                                                                        'flex',
                                                                    flexWrap:
                                                                        'wrap',
                                                                    gap: '9px',
                                                                }}
                                                            >
                                                                <div
                                                                    style={{
                                                                        flex: '1 1 236px',
                                                                        minWidth:
                                                                            '0',
                                                                        padding:
                                                                            '13px 15px',
                                                                        borderRadius:
                                                                            '13px',
                                                                        background:
                                                                            'rgba(30,58,255,.05)',
                                                                        border: '1px solid rgba(30,58,255,.12)',
                                                                    }}
                                                                >
                                                                    <div
                                                                        style={{
                                                                            fontSize:
                                                                                '13px',
                                                                            fontWeight:
                                                                                '700',
                                                                            color: '#0c1830',
                                                                        }}
                                                                    >
                                                                        You
                                                                        invest
                                                                        RWF
                                                                        500,000
                                                                    </div>
                                                                    <div
                                                                        style={{
                                                                            marginTop:
                                                                                '2px',
                                                                            fontSize:
                                                                                '12px',
                                                                        }}
                                                                    >
                                                                        at 18%
                                                                        for 6
                                                                        months
                                                                    </div>
                                                                    <div
                                                                        style={{
                                                                            marginTop:
                                                                                '7px',
                                                                            display:
                                                                                'flex',
                                                                            flexDirection:
                                                                                'column',
                                                                            gap: '3px',
                                                                            fontSize:
                                                                                '13px',
                                                                        }}
                                                                    >
                                                                        <div
                                                                            style={{
                                                                                display:
                                                                                    'flex',
                                                                                justifyContent:
                                                                                    'space-between',
                                                                                gap: '8px',
                                                                            }}
                                                                        >
                                                                            <span>
                                                                                Profit
                                                                                earned
                                                                            </span>
                                                                            <span
                                                                                style={{
                                                                                    flex: '0 0 auto',
                                                                                    whiteSpace:
                                                                                        'nowrap',
                                                                                    fontWeight:
                                                                                        '600',
                                                                                    color: '#0c1830',
                                                                                }}
                                                                            >
                                                                                RWF
                                                                                90,000
                                                                            </span>
                                                                        </div>
                                                                        <div
                                                                            style={{
                                                                                display:
                                                                                    'flex',
                                                                                justifyContent:
                                                                                    'space-between',
                                                                                gap: '8px',
                                                                            }}
                                                                        >
                                                                            <span
                                                                                style={{
                                                                                    whiteSpace:
                                                                                        'nowrap',
                                                                                }}
                                                                            >
                                                                                Our
                                                                                10%
                                                                                charge
                                                                            </span>
                                                                            <span
                                                                                style={{
                                                                                    flex: '0 0 auto',
                                                                                    whiteSpace:
                                                                                        'nowrap',
                                                                                    fontWeight:
                                                                                        '600',
                                                                                    color: '#0c1830',
                                                                                }}
                                                                            >
                                                                                −
                                                                                RWF
                                                                                9,000
                                                                            </span>
                                                                        </div>
                                                                        <div
                                                                            style={{
                                                                                display:
                                                                                    'flex',
                                                                                justifyContent:
                                                                                    'space-between',
                                                                                gap: '8px',
                                                                                paddingTop:
                                                                                    '5px',
                                                                                borderTop:
                                                                                    '1px solid rgba(12,24,48,.1)',
                                                                            }}
                                                                        >
                                                                            <span>
                                                                                You
                                                                                keep
                                                                            </span>
                                                                            <span
                                                                                style={{
                                                                                    flex: '0 0 auto',
                                                                                    whiteSpace:
                                                                                        'nowrap',
                                                                                    fontWeight:
                                                                                        '700',
                                                                                    color: '#1e3aff',
                                                                                }}
                                                                            >
                                                                                RWF
                                                                                81,000
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                    <div
                                                                        style={{
                                                                            marginTop:
                                                                                '7px',
                                                                            fontSize:
                                                                                '12px',
                                                                        }}
                                                                    >
                                                                        Plus
                                                                        your RWF
                                                                        500,000
                                                                        back in
                                                                        full.
                                                                    </div>
                                                                </div>
                                                                <div
                                                                    style={{
                                                                        flex: '1 1 236px',
                                                                        minWidth:
                                                                            '0',
                                                                        padding:
                                                                            '13px 15px',
                                                                        borderRadius:
                                                                            '13px',
                                                                        background:
                                                                            'rgba(30,58,255,.05)',
                                                                        border: '1px solid rgba(30,58,255,.12)',
                                                                    }}
                                                                >
                                                                    <div
                                                                        style={{
                                                                            fontSize:
                                                                                '13px',
                                                                            fontWeight:
                                                                                '700',
                                                                            color: '#0c1830',
                                                                        }}
                                                                    >
                                                                        You
                                                                        invest
                                                                        RWF
                                                                        10,000,000
                                                                    </div>
                                                                    <div
                                                                        style={{
                                                                            marginTop:
                                                                                '2px',
                                                                            fontSize:
                                                                                '12px',
                                                                        }}
                                                                    >
                                                                        at 13.5%
                                                                        for 4
                                                                        months
                                                                    </div>
                                                                    <div
                                                                        style={{
                                                                            marginTop:
                                                                                '7px',
                                                                            display:
                                                                                'flex',
                                                                            flexDirection:
                                                                                'column',
                                                                            gap: '3px',
                                                                            fontSize:
                                                                                '13px',
                                                                        }}
                                                                    >
                                                                        <div
                                                                            style={{
                                                                                display:
                                                                                    'flex',
                                                                                justifyContent:
                                                                                    'space-between',
                                                                                gap: '8px',
                                                                            }}
                                                                        >
                                                                            <span>
                                                                                Profit
                                                                                earned
                                                                            </span>
                                                                            <span
                                                                                style={{
                                                                                    flex: '0 0 auto',
                                                                                    whiteSpace:
                                                                                        'nowrap',
                                                                                    fontWeight:
                                                                                        '600',
                                                                                    color: '#0c1830',
                                                                                }}
                                                                            >
                                                                                RWF
                                                                                1,350,000
                                                                            </span>
                                                                        </div>
                                                                        <div
                                                                            style={{
                                                                                display:
                                                                                    'flex',
                                                                                justifyContent:
                                                                                    'space-between',
                                                                                gap: '8px',
                                                                            }}
                                                                        >
                                                                            <span
                                                                                style={{
                                                                                    whiteSpace:
                                                                                        'nowrap',
                                                                                }}
                                                                            >
                                                                                Our
                                                                                7%
                                                                                charge
                                                                            </span>
                                                                            <span
                                                                                style={{
                                                                                    flex: '0 0 auto',
                                                                                    whiteSpace:
                                                                                        'nowrap',
                                                                                    fontWeight:
                                                                                        '600',
                                                                                    color: '#0c1830',
                                                                                }}
                                                                            >
                                                                                −
                                                                                RWF
                                                                                94,500
                                                                            </span>
                                                                        </div>
                                                                        <div
                                                                            style={{
                                                                                display:
                                                                                    'flex',
                                                                                justifyContent:
                                                                                    'space-between',
                                                                                gap: '8px',
                                                                                paddingTop:
                                                                                    '5px',
                                                                                borderTop:
                                                                                    '1px solid rgba(12,24,48,.1)',
                                                                            }}
                                                                        >
                                                                            <span>
                                                                                You
                                                                                keep
                                                                            </span>
                                                                            <span
                                                                                style={{
                                                                                    flex: '0 0 auto',
                                                                                    whiteSpace:
                                                                                        'nowrap',
                                                                                    fontWeight:
                                                                                        '700',
                                                                                    color: '#1e3aff',
                                                                                }}
                                                                            >
                                                                                RWF
                                                                                1,255,500
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                    <div
                                                                        style={{
                                                                            marginTop:
                                                                                '7px',
                                                                            fontSize:
                                                                                '12px',
                                                                        }}
                                                                    >
                                                                        Plus
                                                                        your RWF
                                                                        10,000,000
                                                                        back in
                                                                        full.
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <p
                                                                style={{
                                                                    margin: '12px 0 0',
                                                                }}
                                                            >
                                                                Your rate is set
                                                                by how much you
                                                                have invested
                                                                and still have
                                                                active on the
                                                                day of the
                                                                repayment,
                                                                checked each
                                                                month. Invest
                                                                more and every
                                                                repayment after
                                                                that is charged
                                                                at the lower
                                                                rate.
                                                            </p>
                                                        </div>
                                                    </>
                                                ) : null}
                                            </div>
                                            <div
                                                className="rz-glass"
                                                style={{
                                                    borderRadius: '16px',
                                                    overflow: 'hidden',
                                                    background:
                                                        'rgba(255,255,255,.58)',
                                                    backdropFilter:
                                                        'blur(18px) saturate(1.45)',
                                                    WebkitBackdropFilter:
                                                        'blur(18px) saturate(1.45)',
                                                    border: '1px solid rgba(12,24,48,.09)',
                                                    boxShadow:
                                                        '0 1px 2px rgba(12,24,48,.03), 0 20px 40px -24px rgba(12,24,48,.16)',
                                                }}
                                            >
                                                <button
                                                    onClick={faq3}
                                                    style={{
                                                        width: '100%',
                                                        padding: '18px 20px',
                                                        display: 'flex',
                                                        alignItems: 'baseline',
                                                        gap: '16px',
                                                        background: 'none',
                                                        border: 'none',
                                                        cursor: 'pointer',
                                                        textAlign: 'left',
                                                        fontFamily: 'inherit',
                                                    }}
                                                >
                                                    <span
                                                        style={{
                                                            flex: '1 1 auto',
                                                            fontSize:
                                                                'clamp(16px,1.5vw,18px)',
                                                            fontWeight: '600',
                                                            letterSpacing:
                                                                '-.02em',
                                                        }}
                                                    >
                                                        Who actually owes me the
                                                        money?
                                                    </span>
                                                    <span
                                                        style={{
                                                            flex: '0 0 auto',
                                                            width: '22px',
                                                            height: '22px',
                                                            borderRadius: '7px',
                                                            background:
                                                                'rgba(30,58,255,.09)',
                                                            color: '#1e3aff',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            lineHeight: '1',
                                                        }}
                                                    >
                                                        {f3m}
                                                    </span>
                                                </button>
                                                {f3 ? (
                                                    <>
                                                        <p
                                                            style={{
                                                                margin: '0',
                                                                padding:
                                                                    '0 20px 19px',
                                                                maxWidth:
                                                                    '560px',
                                                                fontSize:
                                                                    '15px',
                                                                lineHeight:
                                                                    '1.7',
                                                                color: '#69748a',
                                                                animation:
                                                                    'rz-pop .3s ease both',
                                                            }}
                                                        >
                                                            The business owes
                                                            you, directly. Every
                                                            loan is recorded in
                                                            your name and your
                                                            money sits at a
                                                            licensed bank, never
                                                            with Rozine. Your
                                                            claim is on the
                                                            business and its
                                                            repayments, held
                                                            independently of
                                                            Rozine.
                                                        </p>
                                                    </>
                                                ) : null}
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                {sheetOpen ? (
                                    <>
                                        <div
                                            onClick={closeSheet}
                                            style={{
                                                position: 'fixed',
                                                inset: '0',
                                                zIndex: '200',
                                                background: 'rgba(12,24,48,.5)',
                                                backdropFilter: 'blur(3px)',
                                                display: 'flex',
                                                alignItems: 'center',
                                                justifyContent: 'center',
                                                padding: '18px',
                                                overflowY: 'auto',
                                            }}
                                        >
                                            <div
                                                onClick={stop}
                                                style={{
                                                    width: '100%',
                                                    maxWidth: '420px',
                                                    margin: 'auto',
                                                    borderRadius: '20px',
                                                    background: '#fff',
                                                    boxShadow:
                                                        '0 40px 90px -30px rgba(12,24,48,.5)',
                                                    overflow: 'hidden',
                                                    animation:
                                                        'rz-pop .28s ease both',
                                                }}
                                            >
                                                <div
                                                    style={{
                                                        padding:
                                                            '20px 22px 18px',
                                                        background: '#1428a4',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            display: 'flex',
                                                            alignItems:
                                                                'flex-start',
                                                            justifyContent:
                                                                'space-between',
                                                            gap: '14px',
                                                        }}
                                                    >
                                                        <div>
                                                            <div
                                                                style={{
                                                                    fontSize:
                                                                        '11px',
                                                                    fontWeight:
                                                                        '700',
                                                                    letterSpacing:
                                                                        '.12em',
                                                                    color: 'rgba(255,255,255,.5)',
                                                                }}
                                                            >
                                                                YOU ARE STARTING
                                                                WITH
                                                            </div>
                                                            <div
                                                                style={{
                                                                    marginTop:
                                                                        '6px',
                                                                    fontSize:
                                                                        '26px',
                                                                    fontWeight:
                                                                        '800',
                                                                    letterSpacing:
                                                                        '-.03em',
                                                                    color: '#fff',
                                                                    lineHeight:
                                                                        '1',
                                                                }}
                                                            >
                                                                {depStr}
                                                            </div>
                                                        </div>
                                                        <button
                                                            onClick={closeSheet}
                                                            style={{
                                                                flex: '0 0 auto',
                                                                width: '30px',
                                                                height: '30px',
                                                                display: 'flex',
                                                                alignItems:
                                                                    'center',
                                                                justifyContent:
                                                                    'center',
                                                                border: 'none',
                                                                borderRadius:
                                                                    '9px',
                                                                background:
                                                                    'rgba(255,255,255,.12)',
                                                                color: '#fff',
                                                                fontSize:
                                                                    '15px',
                                                                cursor: 'pointer',
                                                                fontFamily:
                                                                    'inherit',
                                                            }}
                                                        >
                                                            ✕
                                                        </button>
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '14px',
                                                            display: 'flex',
                                                            flexDirection:
                                                                'column',
                                                            gap: '1px',
                                                            background:
                                                                'rgba(255,255,255,.14)',
                                                            borderRadius:
                                                                '11px',
                                                            overflow: 'hidden',
                                                        }}
                                                    >
                                                        <div
                                                            style={{
                                                                padding:
                                                                    '9px 13px',
                                                                background:
                                                                    'rgba(255,255,255,.06)',
                                                                display: 'flex',
                                                                justifyContent:
                                                                    'space-between',
                                                                gap: '10px',
                                                            }}
                                                        >
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '12.5px',
                                                                    color: 'rgba(255,255,255,.6)',
                                                                }}
                                                            >
                                                                For how long
                                                            </span>
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '13px',
                                                                    fontWeight:
                                                                        '700',
                                                                    color: '#fff',
                                                                }}
                                                            >
                                                                {termStr}
                                                            </span>
                                                        </div>
                                                        <div
                                                            style={{
                                                                padding:
                                                                    '9px 13px',
                                                                background:
                                                                    'rgba(255,255,255,.06)',
                                                                display: 'flex',
                                                                justifyContent:
                                                                    'space-between',
                                                                gap: '10px',
                                                            }}
                                                        >
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '12.5px',
                                                                    color: 'rgba(255,255,255,.6)',
                                                                }}
                                                            >
                                                                Rating band
                                                            </span>
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '13px',
                                                                    fontWeight:
                                                                        '700',
                                                                    color: '#fff',
                                                                }}
                                                            >
                                                                {bandStr}
                                                            </span>
                                                        </div>
                                                        <div
                                                            style={{
                                                                padding:
                                                                    '9px 13px',
                                                                background:
                                                                    'rgba(255,255,255,.06)',
                                                                display: 'flex',
                                                                justifyContent:
                                                                    'space-between',
                                                                gap: '10px',
                                                            }}
                                                        >
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '12.5px',
                                                                    color: 'rgba(255,255,255,.6)',
                                                                }}
                                                            >
                                                                You get back
                                                            </span>
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '13px',
                                                                    fontWeight:
                                                                        '700',
                                                                    color: '#fff',
                                                                }}
                                                            >
                                                                {backStr}
                                                            </span>
                                                        </div>
                                                        <div
                                                            style={{
                                                                padding:
                                                                    '9px 13px',
                                                                background:
                                                                    'rgba(255,255,255,.06)',
                                                                display: 'flex',
                                                                justifyContent:
                                                                    'space-between',
                                                                gap: '10px',
                                                            }}
                                                        >
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '12.5px',
                                                                    color: 'rgba(255,255,255,.6)',
                                                                }}
                                                            >
                                                                Your profit
                                                            </span>
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '13px',
                                                                    fontWeight:
                                                                        '700',
                                                                    color: '#4ecb9d',
                                                                }}
                                                            >
                                                                {retStr}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                {notSent ? (
                                                    <>
                                                        <div
                                                            style={{
                                                                padding:
                                                                    '20px 22px 22px',
                                                            }}
                                                        >
                                                            <p
                                                                style={{
                                                                    margin: '0 0 15px',
                                                                    fontSize:
                                                                        '13.5px',
                                                                    lineHeight:
                                                                        '1.6',
                                                                    color: '#69748a',
                                                                }}
                                                            >
                                                                Nothing is
                                                                committed yet.
                                                                Leave your
                                                                details and a
                                                                real person will
                                                                call you, show
                                                                you the
                                                                businesses open
                                                                right now, and
                                                                set you up.
                                                            </p>
                                                            <div
                                                                style={{
                                                                    display:
                                                                        'flex',
                                                                    flexDirection:
                                                                        'column',
                                                                    gap: '11px',
                                                                }}
                                                            >
                                                                <div>
                                                                    <div
                                                                        style={{
                                                                            fontSize:
                                                                                '12px',
                                                                            fontWeight:
                                                                                '600',
                                                                            color: '#69748a',
                                                                        }}
                                                                    >
                                                                        Full
                                                                        name
                                                                    </div>
                                                                    <input
                                                                        value={
                                                                            fName
                                                                        }
                                                                        onChange={
                                                                            setName
                                                                        }
                                                                        placeholder="Your name"
                                                                        style={{
                                                                            marginTop:
                                                                                '6px',
                                                                            width: '100%',
                                                                            height: '44px',
                                                                            padding:
                                                                                '0 14px',
                                                                            boxSizing:
                                                                                'border-box',
                                                                            borderRadius:
                                                                                '11px',
                                                                            border: '1.5px solid #e2e8f2',
                                                                            background:
                                                                                '#f8fafd',
                                                                            outline:
                                                                                'none',
                                                                            fontSize:
                                                                                '14.5px',
                                                                            fontFamily:
                                                                                'inherit',
                                                                            color: '#0c1830',
                                                                        }}
                                                                    />
                                                                </div>
                                                                <div>
                                                                    <div
                                                                        style={{
                                                                            display:
                                                                                'flex',
                                                                            alignItems:
                                                                                'center',
                                                                            justifyContent:
                                                                                'space-between',
                                                                            gap: '10px',
                                                                        }}
                                                                    >
                                                                        <div
                                                                            style={{
                                                                                fontSize:
                                                                                    '12px',
                                                                                fontWeight:
                                                                                    '600',
                                                                                color: '#69748a',
                                                                            }}
                                                                        >
                                                                            How
                                                                            should
                                                                            we
                                                                            reach
                                                                            you?
                                                                        </div>
                                                                        <div
                                                                            style={{
                                                                                display:
                                                                                    'flex',
                                                                                gap: '5px',
                                                                                padding:
                                                                                    '3px',
                                                                                borderRadius:
                                                                                    '9px',
                                                                                background:
                                                                                    '#f0f3f9',
                                                                            }}
                                                                        >
                                                                            {(
                                                                                modes ??
                                                                                []
                                                                            ).map(
                                                                                (
                                                                                    md: any,
                                                                                    mdI: number,
                                                                                ) => (
                                                                                    <Fragment
                                                                                        key={
                                                                                            mdI
                                                                                        }
                                                                                    >
                                                                                        <button
                                                                                            onClick={
                                                                                                md.set
                                                                                            }
                                                                                            style={{
                                                                                                padding:
                                                                                                    '5px 12px',
                                                                                                border: 'none',
                                                                                                borderRadius:
                                                                                                    '7px',
                                                                                                background:
                                                                                                    md.bg,
                                                                                                color: md.fg,
                                                                                                fontSize:
                                                                                                    '11.5px',
                                                                                                fontWeight:
                                                                                                    '700',
                                                                                                cursor: 'pointer',
                                                                                                fontFamily:
                                                                                                    'inherit',
                                                                                                boxShadow:
                                                                                                    md.sh,
                                                                                            }}
                                                                                        >
                                                                                            {
                                                                                                md.label
                                                                                            }
                                                                                        </button>
                                                                                    </Fragment>
                                                                                ),
                                                                            )}
                                                                        </div>
                                                                    </div>
                                                                    <div
                                                                        style={{
                                                                            marginTop:
                                                                                '6px',
                                                                            display:
                                                                                'flex',
                                                                            gap: '8px',
                                                                        }}
                                                                    >
                                                                        <select
                                                                            value={
                                                                                fCode
                                                                            }
                                                                            onChange={
                                                                                setCode
                                                                            }
                                                                            style={{
                                                                                display:
                                                                                    codeDisp,
                                                                                flex: '0 0 auto',
                                                                                width: '102px',
                                                                                height: '44px',
                                                                                padding:
                                                                                    '0 12px',
                                                                                boxSizing:
                                                                                    'border-box',
                                                                                borderRadius:
                                                                                    '11px',
                                                                                border: '1.5px solid #e2e8f2',
                                                                                background:
                                                                                    '#f8fafd',
                                                                                outline:
                                                                                    'none',
                                                                                fontSize:
                                                                                    '14.5px',
                                                                                fontFamily:
                                                                                    'inherit',
                                                                                color: '#0c1830',
                                                                                cursor: 'pointer',
                                                                            }}
                                                                        >
                                                                            {(
                                                                                codes ??
                                                                                []
                                                                            ).map(
                                                                                (
                                                                                    c: any,
                                                                                    cI: number,
                                                                                ) => (
                                                                                    <Fragment
                                                                                        key={
                                                                                            cI
                                                                                        }
                                                                                    >
                                                                                        <option
                                                                                            value={
                                                                                                c.v
                                                                                            }
                                                                                        >
                                                                                            {
                                                                                                c.l
                                                                                            }
                                                                                        </option>
                                                                                    </Fragment>
                                                                                ),
                                                                            )}
                                                                        </select>
                                                                        <input
                                                                            value={
                                                                                fContact
                                                                            }
                                                                            onChange={
                                                                                setContact
                                                                            }
                                                                            inputMode={
                                                                                contactMode
                                                                            }
                                                                            placeholder={
                                                                                contactPh
                                                                            }
                                                                            style={{
                                                                                flex: '1 1 auto',
                                                                                minWidth:
                                                                                    '0',
                                                                                height: '44px',
                                                                                padding:
                                                                                    '0 12px',
                                                                                boxSizing:
                                                                                    'border-box',
                                                                                borderRadius:
                                                                                    '11px',
                                                                                border: '1.5px solid #e2e8f2',
                                                                                background:
                                                                                    '#f8fafd',
                                                                                outline:
                                                                                    'none',
                                                                                fontSize:
                                                                                    '14.5px',
                                                                                fontFamily:
                                                                                    'inherit',
                                                                                color: '#0c1830',
                                                                            }}
                                                                        />
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <div
                                                                        style={{
                                                                            fontSize:
                                                                                '12px',
                                                                            fontWeight:
                                                                                '600',
                                                                            color: '#69748a',
                                                                        }}
                                                                    >
                                                                        Country
                                                                        of
                                                                        residence
                                                                    </div>
                                                                    <input
                                                                        value={
                                                                            fCountry
                                                                        }
                                                                        onChange={
                                                                            setCountry
                                                                        }
                                                                        placeholder="Rwanda"
                                                                        style={{
                                                                            marginTop:
                                                                                '6px',
                                                                            width: '100%',
                                                                            height: '44px',
                                                                            padding:
                                                                                '0 14px',
                                                                            boxSizing:
                                                                                'border-box',
                                                                            borderRadius:
                                                                                '11px',
                                                                            border: '1.5px solid #e2e8f2',
                                                                            background:
                                                                                '#f8fafd',
                                                                            outline:
                                                                                'none',
                                                                            fontSize:
                                                                                '14.5px',
                                                                            fontFamily:
                                                                                'inherit',
                                                                            color: '#0c1830',
                                                                        }}
                                                                    />
                                                                </div>
                                                            </div>
                                                            <button
                                                                onClick={submit}
                                                                disabled={
                                                                    subDisabled
                                                                }
                                                                style={{
                                                                    marginTop:
                                                                        '16px',
                                                                    width: '100%',
                                                                    height: '48px',
                                                                    border: 'none',
                                                                    borderRadius:
                                                                        '12px',
                                                                    background:
                                                                        subBg,
                                                                    color: subFg,
                                                                    fontSize:
                                                                        '15px',
                                                                    fontWeight:
                                                                        '600',
                                                                    cursor: subCur,
                                                                    fontFamily:
                                                                        'inherit',
                                                                    transition:
                                                                        'background .2s',
                                                                }}
                                                            >
                                                                Send my details
                                                            </button>
                                                            {submitErr ? (
                                                                <div
                                                                    style={{
                                                                        marginTop:
                                                                            '9px',
                                                                        fontSize:
                                                                            '11.5px',
                                                                        fontWeight:
                                                                            '600',
                                                                        color: '#c0392b',
                                                                        textAlign:
                                                                            'center',
                                                                    }}
                                                                >
                                                                    {submitErr}
                                                                </div>
                                                            ) : null}
                                                            <p
                                                                style={{
                                                                    margin: '11px 0 0',
                                                                    fontSize:
                                                                        '11.5px',
                                                                    lineHeight:
                                                                        '1.6',
                                                                    color: '#8894a8',
                                                                }}
                                                            >
                                                                We use your
                                                                details only to
                                                                contact you
                                                                about lending on
                                                                Rozine.
                                                            </p>
                                                        </div>
                                                    </>
                                                ) : null}

                                                {sent ? (
                                                    <>
                                                        <div
                                                            style={{
                                                                padding:
                                                                    '26px 22px 24px',
                                                                textAlign:
                                                                    'center',
                                                            }}
                                                        >
                                                            <span
                                                                style={{
                                                                    display:
                                                                        'inline-flex',
                                                                    alignItems:
                                                                        'center',
                                                                    justifyContent:
                                                                        'center',
                                                                    width: '46px',
                                                                    height: '46px',
                                                                    borderRadius:
                                                                        '14px',
                                                                    background:
                                                                        'rgba(29,158,117,.12)',
                                                                }}
                                                            >
                                                                <svg
                                                                    width="24"
                                                                    height="24"
                                                                    viewBox="0 0 24 24"
                                                                    fill="none"
                                                                    stroke="#1d9e75"
                                                                    strokeWidth="2.6"
                                                                    strokeLinecap="round"
                                                                    strokeLinejoin="round"
                                                                >
                                                                    <path d="M4 12.5l5 5L20 6.5" />
                                                                </svg>
                                                            </span>
                                                            <div
                                                                style={{
                                                                    marginTop:
                                                                        '14px',
                                                                    fontSize:
                                                                        '19px',
                                                                    fontWeight:
                                                                        '800',
                                                                    letterSpacing:
                                                                        '-.025em',
                                                                    color: '#0c1830',
                                                                }}
                                                            >
                                                                Got it,{' '}
                                                                {firstName}.
                                                            </div>
                                                            <p
                                                                style={{
                                                                    margin: '9px auto 0',
                                                                    maxWidth:
                                                                        '320px',
                                                                    fontSize:
                                                                        '14px',
                                                                    lineHeight:
                                                                        '1.65',
                                                                    color: '#69748a',
                                                                }}
                                                            >
                                                                We'll be in
                                                                touch within two
                                                                working days
                                                                about your{' '}
                                                                {depStr}.
                                                                Nothing moves
                                                                until you say
                                                                so.
                                                            </p>
                                                            <div
                                                                style={{
                                                                    margin: '0 auto',
                                                                    width: '100%',
                                                                    maxWidth:
                                                                        '360px',
                                                                    containerType:
                                                                        'inline-size',
                                                                }}
                                                            >
                                                                <div
                                                                    style={{
                                                                        position:
                                                                            'relative',
                                                                        width: '100%',
                                                                        aspectRatio:
                                                                            '1.585',
                                                                        borderRadius:
                                                                            '4.222cqw',
                                                                        overflow:
                                                                            'hidden',
                                                                        background:
                                                                            'radial-gradient(150% 130% at 14% 0%, #4a63ff 0%, #1e3aff 38%, #0a1440 100%)',
                                                                        boxShadow:
                                                                            '0 5.556cqw 11.111cqw -3.333cqw rgba(30,58,255,.5), inset 0 0.222cqw 0 rgba(255,255,255,.3)',
                                                                        textAlign:
                                                                            'left',
                                                                    }}
                                                                >
                                                                    <div
                                                                        style={{
                                                                            position:
                                                                                'absolute',
                                                                            left: '-30%',
                                                                            top: '-30%',
                                                                            width: '160%',
                                                                            height: '160%',
                                                                            opacity:
                                                                                '.11',
                                                                            transform:
                                                                                'rotate(-30deg)',
                                                                            backgroundImage:
                                                                                'url("/site/img/ed8e027e.png")',
                                                                            backgroundRepeat:
                                                                                'repeat',
                                                                            backgroundSize:
                                                                                '4cqw 2.22cqw',
                                                                        }}
                                                                    ></div>
                                                                    <div
                                                                        style={{
                                                                            position:
                                                                                'absolute',
                                                                            right: '-15.556cqw',
                                                                            top: '-15.556cqw',
                                                                            width: '51.111cqw',
                                                                            height: '51.111cqw',
                                                                            borderRadius:
                                                                                '50%',
                                                                            background:
                                                                                'radial-gradient(closest-side, rgba(255,255,255,.2), transparent)',
                                                                        }}
                                                                    ></div>
                                                                    <div
                                                                        style={{
                                                                            position:
                                                                                'relative',
                                                                            height: '100%',
                                                                            padding:
                                                                                '5.778cqw 6.222cqw',
                                                                            display:
                                                                                'flex',
                                                                            flexDirection:
                                                                                'column',
                                                                        }}
                                                                    >
                                                                        <div
                                                                            style={{
                                                                                display:
                                                                                    'flex',
                                                                                alignItems:
                                                                                    'flex-start',
                                                                                justifyContent:
                                                                                    'space-between',
                                                                                gap: '2cqw',
                                                                            }}
                                                                        >
                                                                            <img
                                                                                src="/site/img/25d454a5.png"
                                                                                alt="rozine"
                                                                                style={{
                                                                                    height: '7cqw',
                                                                                    width: 'auto',
                                                                                    display:
                                                                                        'block',
                                                                                }}
                                                                            />
                                                                            <span
                                                                                style={{
                                                                                    fontSize:
                                                                                        '2.533cqw',
                                                                                    fontWeight:
                                                                                        '700',
                                                                                    letterSpacing:
                                                                                        '.14em',
                                                                                    color: 'rgba(255,255,255,.72)',
                                                                                    whiteSpace:
                                                                                        'nowrap',
                                                                                }}
                                                                            >
                                                                                INVESTOR
                                                                                NOTE
                                                                            </span>
                                                                        </div>
                                                                        <div
                                                                            style={{
                                                                                marginTop:
                                                                                    'auto',
                                                                            }}
                                                                        >
                                                                            <div
                                                                                style={{
                                                                                    fontSize:
                                                                                        '3cqw',
                                                                                    fontWeight:
                                                                                        '600',
                                                                                    letterSpacing:
                                                                                        '.1em',
                                                                                    color: 'rgba(255,255,255,.82)',
                                                                                    textTransform:
                                                                                        'uppercase',
                                                                                }}
                                                                            >
                                                                                {
                                                                                    cardName
                                                                                }{' '}
                                                                                <span
                                                                                    style={{
                                                                                        color: 'rgba(255,255,255,.9)',
                                                                                    }}
                                                                                >
                                                                                    ·
                                                                                </span>{' '}
                                                                                <span
                                                                                    style={{
                                                                                        fontWeight:
                                                                                            '400',
                                                                                    }}
                                                                                >
                                                                                    {
                                                                                        cardCountry
                                                                                    }
                                                                                </span>
                                                                            </div>
                                                                            <div
                                                                                style={{
                                                                                    marginTop:
                                                                                        '1.667cqw',
                                                                                    fontSize:
                                                                                        '2.333cqw',
                                                                                    fontWeight:
                                                                                        '700',
                                                                                    letterSpacing:
                                                                                        '.13em',
                                                                                    color: 'rgba(255,255,255,.62)',
                                                                                }}
                                                                            >
                                                                                PUTTING
                                                                                TO
                                                                                WORK
                                                                            </div>
                                                                            <div
                                                                                style={{
                                                                                    marginTop:
                                                                                        '0.667cqw',
                                                                                    fontSize:
                                                                                        '10cqw',
                                                                                    fontWeight:
                                                                                        '800',
                                                                                    letterSpacing:
                                                                                        '-.03em',
                                                                                    color: '#fff',
                                                                                    lineHeight:
                                                                                        '1',
                                                                                }}
                                                                            >
                                                                                {
                                                                                    depStr
                                                                                }
                                                                            </div>
                                                                            <div
                                                                                style={{
                                                                                    marginTop:
                                                                                        '2.667cqw',
                                                                                    display:
                                                                                        'flex',
                                                                                    alignItems:
                                                                                        'flex-end',
                                                                                    justifyContent:
                                                                                        'space-between',
                                                                                    gap: '2cqw',
                                                                                }}
                                                                            >
                                                                                <div
                                                                                    style={{
                                                                                        display:
                                                                                            'flex',
                                                                                        gap: '4.333cqw',
                                                                                    }}
                                                                                >
                                                                                    <div>
                                                                                        <div
                                                                                            style={{
                                                                                                fontSize:
                                                                                                    '2.333cqw',
                                                                                                letterSpacing:
                                                                                                    '.1em',
                                                                                                whiteSpace:
                                                                                                    'nowrap',
                                                                                                color: 'rgba(255,255,255,.6)',
                                                                                            }}
                                                                                        >
                                                                                            YOU
                                                                                            GET
                                                                                            BACK
                                                                                        </div>
                                                                                        <div
                                                                                            style={{
                                                                                                marginTop:
                                                                                                    '0.444cqw',
                                                                                                fontSize:
                                                                                                    '3.833cqw',
                                                                                                fontWeight:
                                                                                                    '700',
                                                                                                whiteSpace:
                                                                                                    'nowrap',
                                                                                                color: '#fff',
                                                                                            }}
                                                                                        >
                                                                                            {
                                                                                                backStr
                                                                                            }
                                                                                        </div>
                                                                                    </div>
                                                                                    <div>
                                                                                        <div
                                                                                            style={{
                                                                                                fontSize:
                                                                                                    '2.333cqw',
                                                                                                letterSpacing:
                                                                                                    '.1em',
                                                                                                whiteSpace:
                                                                                                    'nowrap',
                                                                                                color: 'rgba(255,255,255,.6)',
                                                                                            }}
                                                                                        >
                                                                                            TERM
                                                                                        </div>
                                                                                        <div
                                                                                            style={{
                                                                                                marginTop:
                                                                                                    '0.444cqw',
                                                                                                fontSize:
                                                                                                    '3.833cqw',
                                                                                                fontWeight:
                                                                                                    '700',
                                                                                                whiteSpace:
                                                                                                    'nowrap',
                                                                                                color: '#fff',
                                                                                            }}
                                                                                        >
                                                                                            {
                                                                                                termStr
                                                                                            }
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div
                                                                                    style={{
                                                                                        display:
                                                                                            'flex',
                                                                                        alignItems:
                                                                                            'center',
                                                                                        gap: '1.333cqw',
                                                                                        fontSize:
                                                                                            '3.5cqw',
                                                                                        fontWeight:
                                                                                            '700',
                                                                                        letterSpacing:
                                                                                            '.03em',
                                                                                        whiteSpace:
                                                                                            'nowrap',
                                                                                        color: '#fff',
                                                                                    }}
                                                                                >
                                                                                    <svg
                                                                                        viewBox="0 0 24 24"
                                                                                        fill="none"
                                                                                        style={{
                                                                                            width: '4.667cqw',
                                                                                            height: '4.667cqw',
                                                                                            flexShrink:
                                                                                                '0',
                                                                                        }}
                                                                                    >
                                                                                        <circle
                                                                                            cx="12"
                                                                                            cy="12"
                                                                                            r="9"
                                                                                            stroke="#fff"
                                                                                            strokeWidth="1.7"
                                                                                        />
                                                                                        <path
                                                                                            d="M3.2 12h17.6M12 3c2.7 2.6 2.7 15.4 0 18M12 3c-2.7 2.6-2.7 15.4 0 18"
                                                                                            stroke="#fff"
                                                                                            strokeWidth="1.5"
                                                                                        />
                                                                                    </svg>
                                                                                    rozine.rw
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div
                                                                    style={{
                                                                        marginTop:
                                                                            '11px',
                                                                        display:
                                                                            'flex',
                                                                        gap: '7px',
                                                                    }}
                                                                >
                                                                    {(
                                                                        shareBtns ??
                                                                        []
                                                                    ).map(
                                                                        (
                                                                            sb: any,
                                                                            sbI: number,
                                                                        ) => (
                                                                            <Fragment
                                                                                key={
                                                                                    sbI
                                                                                }
                                                                            >
                                                                                <button
                                                                                    onClick={
                                                                                        sb.on
                                                                                    }
                                                                                    title={
                                                                                        sb.label
                                                                                    }
                                                                                    style={{
                                                                                        flex: '1',
                                                                                        height: '38px',
                                                                                        borderRadius:
                                                                                            '10px',
                                                                                        border: '1px solid #e2e8f2',
                                                                                        background:
                                                                                            sb.bg,
                                                                                        color: sb.fg,
                                                                                        cursor: 'pointer',
                                                                                        display:
                                                                                            'flex',
                                                                                        alignItems:
                                                                                            'center',
                                                                                        justifyContent:
                                                                                            'center',
                                                                                        gap: '6px',
                                                                                        fontSize:
                                                                                            '11.5px',
                                                                                        fontWeight:
                                                                                            '600',
                                                                                        fontFamily:
                                                                                            'inherit',
                                                                                    }}
                                                                                >
                                                                                    {
                                                                                        sb.icon
                                                                                    }
                                                                                    {
                                                                                        sb.text
                                                                                    }
                                                                                </button>
                                                                            </Fragment>
                                                                        ),
                                                                    )}
                                                                </div>
                                                                <div
                                                                    style={{
                                                                        marginTop:
                                                                            '7px',
                                                                        minHeight:
                                                                            '15px',
                                                                        fontSize:
                                                                            '11.5px',
                                                                        fontWeight:
                                                                            '600',
                                                                        color: '#1d9e75',
                                                                    }}
                                                                >
                                                                    {shareMsg}
                                                                </div>
                                                            </div>
                                                            <div
                                                                style={{
                                                                    marginTop:
                                                                        '16px',
                                                                }}
                                                            >
                                                                <button
                                                                    onClick={
                                                                        closeSheet
                                                                    }
                                                                    style={{
                                                                        padding:
                                                                            '12px 24px',
                                                                        borderRadius:
                                                                            '999px',
                                                                        border: '1.5px solid #e2e8f2',
                                                                        background:
                                                                            'none',
                                                                        color: '#0c1830',
                                                                        fontSize:
                                                                            '14px',
                                                                        fontWeight:
                                                                            '600',
                                                                        cursor: 'pointer',
                                                                        fontFamily:
                                                                            'inherit',
                                                                    }}
                                                                >
                                                                    Close
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </>
                                                ) : null}
                                            </div>
                                        </div>
                                    </>
                                ) : null}
                            </>
                        ) : null}

                        {pgBiz ? (
                            <>
                                <section
                                    style={{
                                        position: 'relative',
                                        overflow: 'clip',
                                        background:
                                            'linear-gradient(180deg, rgba(248,250,253,.55) 0%, rgba(248,250,253,0) 100%)',
                                    }}
                                >
                                    <div
                                        style={{
                                            position: 'absolute',
                                            inset: '-20%',
                                            pointerEvents: 'none',
                                        }}
                                    >
                                        <div
                                            style={{
                                                position: 'absolute',
                                                top: '10%',
                                                left: '50%',
                                                width: '560px',
                                                height: '560px',
                                                borderRadius: '50%',
                                                background:
                                                    'radial-gradient(circle, rgba(30,58,255,.12) 0%, rgba(30,58,255,0) 65%)',
                                            }}
                                        ></div>
                                    </div>
                                    <div
                                        style={{
                                            position: 'relative',
                                            maxWidth: '1200px',
                                            margin: '0 auto',
                                            padding:
                                                'clamp(48px,7vw,90px) clamp(18px,4vw,44px)',
                                        }}
                                    >
                                        <div
                                            style={{
                                                display: 'flex',
                                                flexWrap: 'wrap',
                                                gap: 'clamp(32px,4vw,64px)',
                                                alignItems: 'center',
                                            }}
                                        >
                                            <div
                                                style={{
                                                    flex: '1 1 400px',
                                                    minWidth: '0',
                                                }}
                                            >
                                                <div
                                                    style={{
                                                        fontSize: '13px',
                                                        fontWeight: '700',
                                                        letterSpacing: '.08em',
                                                        color: '#1e3aff',
                                                        animation:
                                                            'rz-pop .6s ease both',
                                                    }}
                                                >
                                                    FOR BUSINESSES
                                                </div>
                                                <h1
                                                    style={{
                                                        margin: '14px 0 0',
                                                        fontSize:
                                                            'clamp(34px,4.6vw,56px)',
                                                        lineHeight: '1.02',
                                                        fontWeight: '800',
                                                        letterSpacing: '-.04em',
                                                        animation:
                                                            'rz-pop .6s ease both .08s',
                                                        textWrap: 'balance',
                                                    }}
                                                >
                                                    Borrow up to
                                                    <br />
                                                    <span
                                                        style={{
                                                            color: '#1e3aff',
                                                        }}
                                                    >
                                                        RWF 100,000,000
                                                    </span>{' '}
                                                    without collateral.
                                                </h1>
                                                <p
                                                    style={{
                                                        margin: '18px 0 0',
                                                        maxWidth: '520px',
                                                        fontSize:
                                                            'clamp(16px,1.4vw,18.5px)',
                                                        lineHeight: '1.65',
                                                        color: '#69748a',
                                                        animation:
                                                            'rz-pop .6s ease both .16s',
                                                        textWrap: 'pretty',
                                                    }}
                                                >
                                                    Borrow from RWF 3 million
                                                    for 3 to 6 months,
                                                    collateral free.&nbsp;
                                                </p>
                                            </div>

                                            <div
                                                id="gauge"
                                                style={{
                                                    position: 'relative',
                                                    zIndex: '3',
                                                    flex: '0 1 372px',
                                                    minWidth: '0',
                                                    maxWidth: '400px',
                                                    animation:
                                                        'rz-pop .7s ease both .2s',
                                                }}
                                            >
                                                <div
                                                    style={{
                                                        borderRadius: '22px',
                                                        background:
                                                            'rgba(255,255,255,.8)',
                                                        backdropFilter:
                                                            'blur(24px) saturate(1.6)',
                                                        WebkitBackdropFilter:
                                                            'blur(24px) saturate(1.6)',
                                                        border: '1px solid rgba(12,24,48,.1)',
                                                        boxShadow:
                                                            '0 1px 2px rgba(12,24,48,.05), 0 32px 64px -28px rgba(12,24,48,.28)',
                                                        overflow: 'hidden',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            padding:
                                                                'clamp(18px,2vw,22px)',
                                                        }}
                                                    >
                                                        <h2
                                                            style={{
                                                                margin: '0',
                                                                fontSize:
                                                                    '19px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.03em',
                                                                lineHeight:
                                                                    '1.1',
                                                            }}
                                                        >
                                                            How much could{' '}
                                                            <span
                                                                style={{
                                                                    color: '#1e3aff',
                                                                }}
                                                            >
                                                                you
                                                            </span>{' '}
                                                            borrow?
                                                        </h2>
                                                        <div
                                                            style={{
                                                                marginTop:
                                                                    '15px',
                                                                fontSize:
                                                                    '12px',
                                                                fontWeight:
                                                                    '600',
                                                                color: '#69748a',
                                                            }}
                                                        >
                                                            <span
                                                                style={{
                                                                    color: 'rgb(139, 144, 155)',
                                                                    fontSize:
                                                                        '11px',
                                                                    backgroundColor:
                                                                        'rgba(255, 255, 255, 0.03)',
                                                                }}
                                                            >
                                                                Total revenue
                                                                over the last 12
                                                                months
                                                            </span>
                                                        </div>
                                                        <div
                                                            style={{
                                                                marginTop:
                                                                    '7px',
                                                                display: 'flex',
                                                                alignItems:
                                                                    'center',
                                                                height: '42px',
                                                                borderRadius:
                                                                    '11px',
                                                                border: '1.5px solid #e2e8f2',
                                                                background:
                                                                    '#f8fafd',
                                                                overflow:
                                                                    'hidden',
                                                            }}
                                                        >
                                                            <span
                                                                style={{
                                                                    padding:
                                                                        '0 9px 0 13px',
                                                                    fontSize:
                                                                        '10.5px',
                                                                    fontWeight:
                                                                        '700',
                                                                    letterSpacing:
                                                                        '.06em',
                                                                    color: '#8894a8',
                                                                }}
                                                            >
                                                                RWF
                                                            </span>
                                                            <input
                                                                value={revStr}
                                                                onChange={
                                                                    setRev
                                                                }
                                                                inputMode="numeric"
                                                                placeholder="0"
                                                                style={{
                                                                    flex: '1 1 auto',
                                                                    minWidth:
                                                                        '0',
                                                                    height: '100%',
                                                                    padding:
                                                                        '0 13px 0 0',
                                                                    border: 'none',
                                                                    background:
                                                                        'none',
                                                                    outline:
                                                                        'none',
                                                                    fontFamily:
                                                                        'inherit',
                                                                    fontSize:
                                                                        '17px',
                                                                    fontWeight:
                                                                        '800',
                                                                    letterSpacing:
                                                                        '-.02em',
                                                                    color: '#0c1830',
                                                                    textAlign:
                                                                        'right',
                                                                }}
                                                            />
                                                        </div>
                                                        <div
                                                            style={{
                                                                marginTop:
                                                                    '13px',
                                                                fontSize:
                                                                    '12px',
                                                                fontWeight:
                                                                    '600',
                                                                color: '#69748a',
                                                            }}
                                                        >
                                                            <div
                                                                className="mb-1.5 text-[11px] font-semibold text-[var(--rz-muted)]"
                                                                style={{
                                                                    border: '0px solid oklch(0.269 0 0)',
                                                                    margin: '0px 0px 6px',
                                                                    padding:
                                                                        '0px',
                                                                    fontSize:
                                                                        '11px',
                                                                    '--tw-font-weight':
                                                                        '600',
                                                                    color: 'rgb(139, 144, 155)',
                                                                    backgroundColor:
                                                                        'rgba(255, 255, 255, 0.03)',
                                                                }}
                                                            >
                                                                Total costs over
                                                                the last 12
                                                                months
                                                            </div>
                                                        </div>
                                                        <div
                                                            style={{
                                                                marginTop:
                                                                    '7px',
                                                                display: 'flex',
                                                                alignItems:
                                                                    'center',
                                                                height: '42px',
                                                                borderRadius:
                                                                    '11px',
                                                                border: '1.5px solid #e2e8f2',
                                                                background:
                                                                    '#f8fafd',
                                                                overflow:
                                                                    'hidden',
                                                            }}
                                                        >
                                                            <span
                                                                style={{
                                                                    padding:
                                                                        '0 9px 0 13px',
                                                                    fontSize:
                                                                        '10.5px',
                                                                    fontWeight:
                                                                        '700',
                                                                    letterSpacing:
                                                                        '.06em',
                                                                    color: '#8894a8',
                                                                }}
                                                            >
                                                                RWF
                                                            </span>
                                                            <input
                                                                value={expStr}
                                                                onChange={
                                                                    setExp
                                                                }
                                                                inputMode="numeric"
                                                                placeholder="0"
                                                                style={{
                                                                    flex: '1 1 auto',
                                                                    minWidth:
                                                                        '0',
                                                                    height: '100%',
                                                                    padding:
                                                                        '0 13px 0 0',
                                                                    border: 'none',
                                                                    background:
                                                                        'none',
                                                                    outline:
                                                                        'none',
                                                                    fontFamily:
                                                                        'inherit',
                                                                    fontSize:
                                                                        '17px',
                                                                    fontWeight:
                                                                        '800',
                                                                    letterSpacing:
                                                                        '-.02em',
                                                                    color: '#0c1830',
                                                                    textAlign:
                                                                        'right',
                                                                }}
                                                            />
                                                        </div>
                                                        {showLeft ? (
                                                            <>
                                                                <div
                                                                    style={{
                                                                        marginTop:
                                                                            '10px',
                                                                        display:
                                                                            'flex',
                                                                        alignItems:
                                                                            'center',
                                                                        justifyContent:
                                                                            'space-between',
                                                                        gap: '10px',
                                                                        padding:
                                                                            '9px 12px',
                                                                        borderRadius:
                                                                            '10px',
                                                                        background:
                                                                            leftBg,
                                                                        border: `1px solid ${leftBd}`,
                                                                    }}
                                                                >
                                                                    <span
                                                                        style={{
                                                                            fontSize:
                                                                                '10.5px',
                                                                            fontWeight:
                                                                                '700',
                                                                            letterSpacing:
                                                                                '.07em',
                                                                            color: leftFg,
                                                                        }}
                                                                    >
                                                                        {
                                                                            leftLabel
                                                                        }
                                                                    </span>
                                                                    <span
                                                                        style={{
                                                                            fontSize:
                                                                                '13px',
                                                                            fontWeight:
                                                                                '800',
                                                                            color: leftFg,
                                                                        }}
                                                                    >
                                                                        {
                                                                            leftStr
                                                                        }
                                                                    </span>
                                                                </div>
                                                            </>
                                                        ) : null}
                                                        <div
                                                            style={{
                                                                marginTop:
                                                                    '13px',
                                                                fontSize:
                                                                    '12px',
                                                                fontWeight:
                                                                    '600',
                                                                color: '#69748a',
                                                            }}
                                                        >
                                                            Choose repayment
                                                            term
                                                        </div>
                                                        <div
                                                            style={{
                                                                marginTop:
                                                                    '7px',
                                                                display: 'flex',
                                                                gap: '7px',
                                                            }}
                                                        >
                                                            {(terms ?? []).map(
                                                                (
                                                                    t: any,
                                                                    tI: number,
                                                                ) => (
                                                                    <Fragment
                                                                        key={tI}
                                                                    >
                                                                        <button
                                                                            onClick={
                                                                                t.set
                                                                            }
                                                                            style={{
                                                                                flex: '1 1 0',
                                                                                height: '35px',
                                                                                borderRadius:
                                                                                    '10px',
                                                                                border: `1.5px solid ${t.bd}`,
                                                                                background:
                                                                                    t.bg,
                                                                                color: t.fg,
                                                                                fontSize:
                                                                                    '12.5px',
                                                                                fontWeight:
                                                                                    '600',
                                                                                cursor: 'pointer',
                                                                                transition:
                                                                                    'all .2s',
                                                                            }}
                                                                        >
                                                                            {
                                                                                t.label
                                                                            }
                                                                        </button>
                                                                    </Fragment>
                                                                ),
                                                            )}
                                                        </div>
                                                    </div>
                                                    <div
                                                        style={{
                                                            padding:
                                                                '16px clamp(18px,2vw,22px) clamp(18px,2vw,22px)',
                                                            background:
                                                                '#1428a4',
                                                        }}
                                                    >
                                                        <div
                                                            style={{
                                                                display: 'flex',
                                                                justifyContent:
                                                                    'space-between',
                                                                alignItems:
                                                                    'baseline',
                                                                gap: '10px',
                                                            }}
                                                        >
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '12px',
                                                                    fontWeight:
                                                                        '600',
                                                                    color: 'rgba(255,255,255,.55)',
                                                                }}
                                                            >
                                                                You could borrow
                                                            </span>
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        'clamp(22px,2.1vw,26px)',
                                                                    fontWeight:
                                                                        '800',
                                                                    letterSpacing:
                                                                        '-.03em',
                                                                    color: '#fff',
                                                                    lineHeight:
                                                                        '1',
                                                                }}
                                                            >
                                                                {qualStr}
                                                            </span>
                                                        </div>
                                                        <div
                                                            style={{
                                                                marginTop:
                                                                    '11px',
                                                                display: 'flex',
                                                                flexDirection:
                                                                    'column',
                                                                gap: '1px',
                                                                background:
                                                                    'rgba(255,255,255,.14)',
                                                                borderRadius:
                                                                    '11px',
                                                                overflow:
                                                                    'hidden',
                                                            }}
                                                        >
                                                            <div
                                                                style={{
                                                                    padding:
                                                                        '9px 13px',
                                                                    background:
                                                                        'rgba(255,255,255,.05)',
                                                                    display:
                                                                        'flex',
                                                                    justifyContent:
                                                                        'space-between',
                                                                    gap: '10px',
                                                                }}
                                                            >
                                                                <span
                                                                    style={{
                                                                        fontSize:
                                                                            '13px',
                                                                        color: 'rgba(255,255,255,.65)',
                                                                    }}
                                                                >
                                                                    One flat
                                                                    charge
                                                                </span>
                                                                <span
                                                                    style={{
                                                                        fontSize:
                                                                            '13.5px',
                                                                        fontWeight:
                                                                            '700',
                                                                        color: '#fff',
                                                                    }}
                                                                >
                                                                    {
                                                                        chargePctStr
                                                                    }
                                                                </span>
                                                            </div>
                                                            <div
                                                                style={{
                                                                    padding:
                                                                        '9px 13px',
                                                                    background:
                                                                        'rgba(255,255,255,.05)',
                                                                    display:
                                                                        'flex',
                                                                    justifyContent:
                                                                        'space-between',
                                                                    gap: '10px',
                                                                }}
                                                            >
                                                                <span
                                                                    style={{
                                                                        fontSize:
                                                                            '13px',
                                                                        color: 'rgba(255,255,255,.65)',
                                                                    }}
                                                                >
                                                                    You repay
                                                                    monthly
                                                                </span>
                                                                <span
                                                                    style={{
                                                                        fontSize:
                                                                            '13.5px',
                                                                        fontWeight:
                                                                            '700',
                                                                        color: '#fff',
                                                                    }}
                                                                >
                                                                    {monStr}
                                                                </span>
                                                            </div>
                                                            <div
                                                                style={{
                                                                    padding:
                                                                        '9px 13px',
                                                                    background:
                                                                        'rgba(255,255,255,.05)',
                                                                    display:
                                                                        'flex',
                                                                    justifyContent:
                                                                        'space-between',
                                                                    gap: '10px',
                                                                }}
                                                            >
                                                                <span
                                                                    style={{
                                                                        fontSize:
                                                                            '13px',
                                                                        color: 'rgba(255,255,255,.65)',
                                                                    }}
                                                                >
                                                                    Total you
                                                                    repay
                                                                </span>
                                                                <span
                                                                    style={{
                                                                        fontSize:
                                                                            '13.5px',
                                                                        fontWeight:
                                                                            '700',
                                                                        color: '#4ecb9d',
                                                                    }}
                                                                >
                                                                    {totalStr}
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <button
                                                            onClick={openSheet}
                                                            disabled={
                                                                startDisabled
                                                            }
                                                            style={{
                                                                marginTop:
                                                                    '14px',
                                                                width: '100%',
                                                                height: '46px',
                                                                display: 'flex',
                                                                alignItems:
                                                                    'center',
                                                                justifyContent:
                                                                    'center',
                                                                gap: '9px',
                                                                border: 'none',
                                                                borderRadius:
                                                                    '12px',
                                                                background:
                                                                    startBg,
                                                                color: startFg,
                                                                fontSize:
                                                                    '14.5px',
                                                                fontWeight:
                                                                    '600',
                                                                cursor: startCur,
                                                                fontFamily:
                                                                    'inherit',
                                                                transition:
                                                                    'background .2s',
                                                            }}
                                                        >
                                                            Start borrowing{' '}
                                                            <span
                                                                style={{
                                                                    opacity:
                                                                        '.55',
                                                                }}
                                                            >
                                                                →
                                                            </span>
                                                        </button>
                                                        <p
                                                            style={{
                                                                margin: '10px 0 0',
                                                                fontSize:
                                                                    '11.5px',
                                                                lineHeight:
                                                                    '1.6',
                                                                color: 'rgba(255,255,255,.5)',
                                                            }}
                                                        >
                                                            {note}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section
                                    style={{
                                        position: 'relative',
                                        zIndex: '0',
                                        marginTop: 'clamp(-140px,-11vw,-72px)',
                                        padding: '0',
                                    }}
                                >
                                    <div
                                        className="rz-strip"
                                        style={{
                                            overflow: 'hidden',
                                            maskImage:
                                                'linear-gradient(to right, transparent, #000 6%, #000 94%, transparent)',
                                            WebkitMaskImage:
                                                'linear-gradient(to right, transparent, #000 6%, #000 94%, transparent)',
                                        }}
                                    >
                                        <div className="rz-track">
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/9eb44d0e.png"
                                                    alt="Retail"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '34px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.9), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Retail
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '6px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            RWF 8.4M
                                                        </span>
                                                        <span
                                                            aria-hidden="true"
                                                            style={{
                                                                fontSize:
                                                                    '12px',
                                                                color: 'rgba(255,255,255,.34)',
                                                            }}
                                                        >
                                                            ·
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '13.5px',
                                                                fontWeight:
                                                                    '700',
                                                                letterSpacing:
                                                                    '-.01em',
                                                                color: '#fff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            4 months
                                                        </span>
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '1px',
                                                            fontSize: '11px',
                                                            fontWeight: '500',
                                                            color: 'rgba(255,255,255,.6)',
                                                            whiteSpace:
                                                                'nowrap',
                                                        }}
                                                    >
                                                        average raise and term
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/52d04172.png"
                                                    alt="Transport and logistics"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '34px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.9), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Transport &amp;
                                                        logistics
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '6px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            RWF 14.2M
                                                        </span>
                                                        <span
                                                            aria-hidden="true"
                                                            style={{
                                                                fontSize:
                                                                    '12px',
                                                                color: 'rgba(255,255,255,.34)',
                                                            }}
                                                        >
                                                            ·
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '13.5px',
                                                                fontWeight:
                                                                    '700',
                                                                letterSpacing:
                                                                    '-.01em',
                                                                color: '#fff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            5 months
                                                        </span>
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '1px',
                                                            fontSize: '11px',
                                                            fontWeight: '500',
                                                            color: 'rgba(255,255,255,.6)',
                                                            whiteSpace:
                                                                'nowrap',
                                                        }}
                                                    >
                                                        average raise and term
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/3aa9a334.png"
                                                    alt="Hospitality"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '34px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.9), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Hospitality
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '6px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            RWF 22.5M
                                                        </span>
                                                        <span
                                                            aria-hidden="true"
                                                            style={{
                                                                fontSize:
                                                                    '12px',
                                                                color: 'rgba(255,255,255,.34)',
                                                            }}
                                                        >
                                                            ·
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '13.5px',
                                                                fontWeight:
                                                                    '700',
                                                                letterSpacing:
                                                                    '-.01em',
                                                                color: '#fff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            6 months
                                                        </span>
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '1px',
                                                            fontSize: '11px',
                                                            fontWeight: '500',
                                                            color: 'rgba(255,255,255,.6)',
                                                            whiteSpace:
                                                                'nowrap',
                                                        }}
                                                    >
                                                        average raise and term
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/9d882722.png"
                                                    alt="Construction and property"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '34px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.9), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Construction &amp;
                                                        property
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '6px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            RWF 31.0M
                                                        </span>
                                                        <span
                                                            aria-hidden="true"
                                                            style={{
                                                                fontSize:
                                                                    '12px',
                                                                color: 'rgba(255,255,255,.34)',
                                                            }}
                                                        >
                                                            ·
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '13.5px',
                                                                fontWeight:
                                                                    '700',
                                                                letterSpacing:
                                                                    '-.01em',
                                                                color: '#fff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            6 months
                                                        </span>
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '1px',
                                                            fontSize: '11px',
                                                            fontWeight: '500',
                                                            color: 'rgba(255,255,255,.6)',
                                                            whiteSpace:
                                                                'nowrap',
                                                        }}
                                                    >
                                                        average raise and term
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/bcba6e76.png"
                                                    alt="Restaurant and bar"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '34px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.9), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Restaurant &amp; bar
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '6px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            RWF 9.6M
                                                        </span>
                                                        <span
                                                            aria-hidden="true"
                                                            style={{
                                                                fontSize:
                                                                    '12px',
                                                                color: 'rgba(255,255,255,.34)',
                                                            }}
                                                        >
                                                            ·
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '13.5px',
                                                                fontWeight:
                                                                    '700',
                                                                letterSpacing:
                                                                    '-.01em',
                                                                color: '#fff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            4 months
                                                        </span>
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '1px',
                                                            fontSize: '11px',
                                                            fontWeight: '500',
                                                            color: 'rgba(255,255,255,.6)',
                                                            whiteSpace:
                                                                'nowrap',
                                                        }}
                                                    >
                                                        average raise and term
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/f30acdf7.png"
                                                    alt="Agriculture"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '34px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.9), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Agriculture
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '6px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            RWF 12.8M
                                                        </span>
                                                        <span
                                                            aria-hidden="true"
                                                            style={{
                                                                fontSize:
                                                                    '12px',
                                                                color: 'rgba(255,255,255,.34)',
                                                            }}
                                                        >
                                                            ·
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '13.5px',
                                                                fontWeight:
                                                                    '700',
                                                                letterSpacing:
                                                                    '-.01em',
                                                                color: '#fff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            5 months
                                                        </span>
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '1px',
                                                            fontSize: '11px',
                                                            fontWeight: '500',
                                                            color: 'rgba(255,255,255,.6)',
                                                            whiteSpace:
                                                                'nowrap',
                                                        }}
                                                    >
                                                        average raise and term
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/3a42ae59.png"
                                                    alt="Food production"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '34px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.9), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Food production
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '6px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            RWF 17.4M
                                                        </span>
                                                        <span
                                                            aria-hidden="true"
                                                            style={{
                                                                fontSize:
                                                                    '12px',
                                                                color: 'rgba(255,255,255,.34)',
                                                            }}
                                                        >
                                                            ·
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '13.5px',
                                                                fontWeight:
                                                                    '700',
                                                                letterSpacing:
                                                                    '-.01em',
                                                                color: '#fff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            5 months
                                                        </span>
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '1px',
                                                            fontSize: '11px',
                                                            fontWeight: '500',
                                                            color: 'rgba(255,255,255,.6)',
                                                            whiteSpace:
                                                                'nowrap',
                                                        }}
                                                    >
                                                        average raise and term
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/4f918ed8.png"
                                                    alt="Professional services"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '34px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.9), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Professional services
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '6px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            RWF 6.5M
                                                        </span>
                                                        <span
                                                            aria-hidden="true"
                                                            style={{
                                                                fontSize:
                                                                    '12px',
                                                                color: 'rgba(255,255,255,.34)',
                                                            }}
                                                        >
                                                            ·
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '13.5px',
                                                                fontWeight:
                                                                    '700',
                                                                letterSpacing:
                                                                    '-.01em',
                                                                color: '#fff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            3 months
                                                        </span>
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '1px',
                                                            fontSize: '11px',
                                                            fontWeight: '500',
                                                            color: 'rgba(255,255,255,.6)',
                                                            whiteSpace:
                                                                'nowrap',
                                                        }}
                                                    >
                                                        average raise and term
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/9eb44d0e.png"
                                                    alt="Retail"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '34px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.9), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Retail
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '6px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            RWF 8.4M
                                                        </span>
                                                        <span
                                                            aria-hidden="true"
                                                            style={{
                                                                fontSize:
                                                                    '12px',
                                                                color: 'rgba(255,255,255,.34)',
                                                            }}
                                                        >
                                                            ·
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '13.5px',
                                                                fontWeight:
                                                                    '700',
                                                                letterSpacing:
                                                                    '-.01em',
                                                                color: '#fff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            4 months
                                                        </span>
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '1px',
                                                            fontSize: '11px',
                                                            fontWeight: '500',
                                                            color: 'rgba(255,255,255,.6)',
                                                            whiteSpace:
                                                                'nowrap',
                                                        }}
                                                    >
                                                        average raise and term
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/52d04172.png"
                                                    alt="Transport and logistics"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '34px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.9), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Transport &amp;
                                                        logistics
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '6px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            RWF 14.2M
                                                        </span>
                                                        <span
                                                            aria-hidden="true"
                                                            style={{
                                                                fontSize:
                                                                    '12px',
                                                                color: 'rgba(255,255,255,.34)',
                                                            }}
                                                        >
                                                            ·
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '13.5px',
                                                                fontWeight:
                                                                    '700',
                                                                letterSpacing:
                                                                    '-.01em',
                                                                color: '#fff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            5 months
                                                        </span>
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '1px',
                                                            fontSize: '11px',
                                                            fontWeight: '500',
                                                            color: 'rgba(255,255,255,.6)',
                                                            whiteSpace:
                                                                'nowrap',
                                                        }}
                                                    >
                                                        average raise and term
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/3aa9a334.png"
                                                    alt="Hospitality"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '34px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.9), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Hospitality
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '6px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            RWF 22.5M
                                                        </span>
                                                        <span
                                                            aria-hidden="true"
                                                            style={{
                                                                fontSize:
                                                                    '12px',
                                                                color: 'rgba(255,255,255,.34)',
                                                            }}
                                                        >
                                                            ·
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '13.5px',
                                                                fontWeight:
                                                                    '700',
                                                                letterSpacing:
                                                                    '-.01em',
                                                                color: '#fff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            6 months
                                                        </span>
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '1px',
                                                            fontSize: '11px',
                                                            fontWeight: '500',
                                                            color: 'rgba(255,255,255,.6)',
                                                            whiteSpace:
                                                                'nowrap',
                                                        }}
                                                    >
                                                        average raise and term
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/9d882722.png"
                                                    alt="Construction and property"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '34px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.9), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Construction &amp;
                                                        property
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '6px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            RWF 31.0M
                                                        </span>
                                                        <span
                                                            aria-hidden="true"
                                                            style={{
                                                                fontSize:
                                                                    '12px',
                                                                color: 'rgba(255,255,255,.34)',
                                                            }}
                                                        >
                                                            ·
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '13.5px',
                                                                fontWeight:
                                                                    '700',
                                                                letterSpacing:
                                                                    '-.01em',
                                                                color: '#fff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            6 months
                                                        </span>
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '1px',
                                                            fontSize: '11px',
                                                            fontWeight: '500',
                                                            color: 'rgba(255,255,255,.6)',
                                                            whiteSpace:
                                                                'nowrap',
                                                        }}
                                                    >
                                                        average raise and term
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/bcba6e76.png"
                                                    alt="Restaurant and bar"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '34px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.9), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Restaurant &amp; bar
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '6px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            RWF 9.6M
                                                        </span>
                                                        <span
                                                            aria-hidden="true"
                                                            style={{
                                                                fontSize:
                                                                    '12px',
                                                                color: 'rgba(255,255,255,.34)',
                                                            }}
                                                        >
                                                            ·
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '13.5px',
                                                                fontWeight:
                                                                    '700',
                                                                letterSpacing:
                                                                    '-.01em',
                                                                color: '#fff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            4 months
                                                        </span>
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '1px',
                                                            fontSize: '11px',
                                                            fontWeight: '500',
                                                            color: 'rgba(255,255,255,.6)',
                                                            whiteSpace:
                                                                'nowrap',
                                                        }}
                                                    >
                                                        average raise and term
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/f30acdf7.png"
                                                    alt="Agriculture"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '34px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.9), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Agriculture
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '6px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            RWF 12.8M
                                                        </span>
                                                        <span
                                                            aria-hidden="true"
                                                            style={{
                                                                fontSize:
                                                                    '12px',
                                                                color: 'rgba(255,255,255,.34)',
                                                            }}
                                                        >
                                                            ·
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '13.5px',
                                                                fontWeight:
                                                                    '700',
                                                                letterSpacing:
                                                                    '-.01em',
                                                                color: '#fff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            5 months
                                                        </span>
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '1px',
                                                            fontSize: '11px',
                                                            fontWeight: '500',
                                                            color: 'rgba(255,255,255,.6)',
                                                            whiteSpace:
                                                                'nowrap',
                                                        }}
                                                    >
                                                        average raise and term
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/3a42ae59.png"
                                                    alt="Food production"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '34px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.9), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Food production
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '6px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            RWF 17.4M
                                                        </span>
                                                        <span
                                                            aria-hidden="true"
                                                            style={{
                                                                fontSize:
                                                                    '12px',
                                                                color: 'rgba(255,255,255,.34)',
                                                            }}
                                                        >
                                                            ·
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '13.5px',
                                                                fontWeight:
                                                                    '700',
                                                                letterSpacing:
                                                                    '-.01em',
                                                                color: '#fff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            5 months
                                                        </span>
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '1px',
                                                            fontSize: '11px',
                                                            fontWeight: '500',
                                                            color: 'rgba(255,255,255,.6)',
                                                            whiteSpace:
                                                                'nowrap',
                                                        }}
                                                    >
                                                        average raise and term
                                                    </div>
                                                </figcaption>
                                            </figure>
                                            <figure
                                                className="rz-tile"
                                                style={{
                                                    position: 'relative',
                                                    flex: '0 0 auto',
                                                    width: 'clamp(220px,24vw,300px)',
                                                    height: 'clamp(150px,16vw,200px)',
                                                    margin: '0 12px 0 0',
                                                    borderRadius: '18px',
                                                    overflow: 'hidden',
                                                    background: '#e2e8f2',
                                                }}
                                            >
                                                <img
                                                    src="/site/img/4f918ed8.png"
                                                    alt="Professional services"
                                                    loading="lazy"
                                                    decoding="async"
                                                    style={{
                                                        width: '100%',
                                                        height: '100%',
                                                        objectFit: 'cover',
                                                        display: 'block',
                                                    }}
                                                />
                                                <figcaption
                                                    style={{
                                                        position: 'absolute',
                                                        inset: 'auto 0 0 0',
                                                        padding:
                                                            '34px 16px 13px',
                                                        background:
                                                            'linear-gradient(to top, rgba(12,24,48,.9), rgba(12,24,48,0))',
                                                        color: '#fff',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            letterSpacing:
                                                                '-.015em',
                                                        }}
                                                    >
                                                        Professional services
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '3px',
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '6px',
                                                        }}
                                                    >
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '15px',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.02em',
                                                                color: '#a8b8ff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            RWF 6.5M
                                                        </span>
                                                        <span
                                                            aria-hidden="true"
                                                            style={{
                                                                fontSize:
                                                                    '12px',
                                                                color: 'rgba(255,255,255,.34)',
                                                            }}
                                                        >
                                                            ·
                                                        </span>
                                                        <span
                                                            style={{
                                                                fontSize:
                                                                    '13.5px',
                                                                fontWeight:
                                                                    '700',
                                                                letterSpacing:
                                                                    '-.01em',
                                                                color: '#fff',
                                                                whiteSpace:
                                                                    'nowrap',
                                                            }}
                                                        >
                                                            3 months
                                                        </span>
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '1px',
                                                            fontSize: '11px',
                                                            fontWeight: '500',
                                                            color: 'rgba(255,255,255,.6)',
                                                            whiteSpace:
                                                                'nowrap',
                                                        }}
                                                    >
                                                        average raise and term
                                                    </div>
                                                </figcaption>
                                            </figure>
                                        </div>
                                    </div>
                                </section>

                                <section
                                    style={{
                                        maxWidth: '1200px',
                                        margin: '0 auto',
                                        padding:
                                            'clamp(56px,8vw,100px) clamp(18px,4vw,44px) 0',
                                    }}
                                >
                                    <div
                                        className="rz-rise"
                                        style={{ maxWidth: '640px' }}
                                    >
                                        <div
                                            className="rz-kick"
                                            style={{
                                                fontSize: '12.5px',
                                                fontWeight: '700',
                                                letterSpacing: '.1em',
                                                color: '#1e3aff',
                                            }}
                                        >
                                            01 · WHO QUALIFIES
                                        </div>
                                        <h2
                                            style={{
                                                margin: '13px 0 0',
                                                fontSize:
                                                    'clamp(30px,4vw,48px)',
                                                lineHeight: '1.02',
                                                fontWeight: '800',
                                                letterSpacing: '-.045em',
                                            }}
                                        >
                                            Can your business borrow?
                                        </h2>
                                        <p
                                            style={{
                                                margin: '14px 0 0',
                                                fontSize:
                                                    'clamp(15.5px,1.3vw,17.5px)',
                                                lineHeight: '1.65',
                                                color: '#69748a',
                                            }}
                                        >
                                            We only lend investors' money to
                                            businesses that can clearly pay it
                                            back. You qualify if:
                                        </p>
                                    </div>
                                    <div
                                        className="rz-rise"
                                        style={{
                                            marginTop: 'clamp(28px,4vw,44px)',
                                            display: 'grid',
                                            gridTemplateColumns:
                                                'repeat(auto-fit,minmax(260px,1fr))',
                                            gap: 'clamp(16px,2vw,24px)',
                                        }}
                                    >
                                        <div
                                            className="rz-glass"
                                            style={{
                                                padding:
                                                    'clamp(22px,2.6vw,30px)',
                                                borderRadius: '20px',
                                                background:
                                                    'rgba(255,255,255,.58)',
                                                backdropFilter:
                                                    'blur(18px) saturate(1.45)',
                                                WebkitBackdropFilter:
                                                    'blur(18px) saturate(1.45)',
                                                border: '1px solid rgba(12,24,48,.09)',
                                                boxShadow:
                                                    '0 1px 2px rgba(12,24,48,.03), 0 20px 40px -24px rgba(12,24,48,.16)',
                                            }}
                                        >
                                            <svg
                                                width="26"
                                                height="26"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="#1e3aff"
                                                strokeWidth="2.2"
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                            >
                                                <path d="M4 12.5l5 5L20 6.5" />
                                            </svg>
                                            <h3
                                                style={{
                                                    margin: '14px 0 0',
                                                    fontSize: '18px',
                                                    fontWeight: '700',
                                                    letterSpacing: '-.02em',
                                                }}
                                            >
                                                Registered in Rwanda
                                            </h3>
                                            <p
                                                style={{
                                                    margin: '8px 0 0',
                                                    fontSize: '14.5px',
                                                    lineHeight: '1.65',
                                                    color: '#69748a',
                                                }}
                                            >
                                                Your business is officially
                                                registered and has been trading
                                                for at least 5 years.
                                            </p>
                                        </div>
                                        <div
                                            className="rz-glass"
                                            style={{
                                                padding:
                                                    'clamp(22px,2.6vw,30px)',
                                                borderRadius: '20px',
                                                background:
                                                    'rgba(255,255,255,.58)',
                                                backdropFilter:
                                                    'blur(18px) saturate(1.45)',
                                                WebkitBackdropFilter:
                                                    'blur(18px) saturate(1.45)',
                                                border: '1px solid rgba(12,24,48,.09)',
                                                boxShadow:
                                                    '0 1px 2px rgba(12,24,48,.03), 0 20px 40px -24px rgba(12,24,48,.16)',
                                            }}
                                        >
                                            <svg
                                                width="26"
                                                height="26"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="#1e3aff"
                                                strokeWidth="2.2"
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                            >
                                                <path d="M4 12.5l5 5L20 6.5" />
                                            </svg>
                                            <h3
                                                style={{
                                                    margin: '14px 0 0',
                                                    fontSize: '18px',
                                                    fontWeight: '700',
                                                    letterSpacing: '-.02em',
                                                }}
                                            >
                                                Making real profit
                                            </h3>
                                            <p
                                                style={{
                                                    margin: '8px 0 0',
                                                    fontSize: '14.5px',
                                                    lineHeight: '1.65',
                                                    color: '#69748a',
                                                }}
                                            >
                                                You've made solid profit in each
                                                of those 5 years, enough that
                                                the monthly repayment fits
                                                comfortably.
                                            </p>
                                        </div>
                                        <div
                                            className="rz-glass"
                                            style={{
                                                padding:
                                                    'clamp(22px,2.6vw,30px)',
                                                borderRadius: '20px',
                                                background:
                                                    'rgba(255,255,255,.58)',
                                                backdropFilter:
                                                    'blur(18px) saturate(1.45)',
                                                WebkitBackdropFilter:
                                                    'blur(18px) saturate(1.45)',
                                                border: '1px solid rgba(12,24,48,.09)',
                                                boxShadow:
                                                    '0 1px 2px rgba(12,24,48,.03), 0 20px 40px -24px rgba(12,24,48,.16)',
                                            }}
                                        >
                                            <svg
                                                width="26"
                                                height="26"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="#1e3aff"
                                                strokeWidth="2.2"
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                            >
                                                <path d="M4 12.5l5 5L20 6.5" />
                                            </svg>
                                            <h3
                                                style={{
                                                    margin: '14px 0 0',
                                                    fontSize: '18px',
                                                    fontWeight: '700',
                                                    letterSpacing: '-.02em',
                                                }}
                                            >
                                                Records we can check
                                            </h3>
                                            <p
                                                style={{
                                                    margin: '8px 0 0',
                                                    fontSize: '14.5px',
                                                    lineHeight: '1.65',
                                                    color: '#69748a',
                                                }}
                                            >
                                                5 years of financial statements,
                                                plus bank or mobile money
                                                records an accountant can look
                                                at. They don't need to be fancy,
                                                just real.
                                            </p>
                                        </div>
                                    </div>
                                </section>

                                <section
                                    style={{
                                        maxWidth: '1200px',
                                        margin: '0 auto',
                                        padding:
                                            'clamp(56px,8vw,100px) clamp(18px,4vw,44px) 0',
                                    }}
                                >
                                    <div
                                        className="rz-rise"
                                        style={{
                                            borderRadius: '24px',
                                            overflow: 'hidden',
                                            background: 'rgba(255,255,255,.8)',
                                            backdropFilter:
                                                'blur(24px) saturate(1.6)',
                                            WebkitBackdropFilter:
                                                'blur(24px) saturate(1.6)',
                                            border: '1px solid rgba(12,24,48,.1)',
                                            boxShadow:
                                                '0 1px 2px rgba(12,24,48,.05), 0 32px 64px -28px rgba(12,24,48,.28)',
                                            display: 'flex',
                                            flexWrap: 'wrap',
                                        }}
                                    >
                                        <div
                                            style={{
                                                flex: '1 1 340px',
                                                minWidth: '0',
                                                padding: 'clamp(26px,3vw,40px)',
                                                background: '#fff',
                                            }}
                                        >
                                            <div
                                                className="rz-kick"
                                                style={{
                                                    fontSize: '12.5px',
                                                    fontWeight: '700',
                                                    letterSpacing: '.1em',
                                                    color: '#1e3aff',
                                                }}
                                            >
                                                02 · WHAT IT COSTS
                                            </div>
                                            <h2
                                                style={{
                                                    margin: '13px 0 0',
                                                    fontSize:
                                                        'clamp(24px,3vw,36px)',
                                                    fontWeight: '800',
                                                    letterSpacing: '-.035em',
                                                    lineHeight: '1.1',
                                                }}
                                            >
                                                What it costs, with a real
                                                example.
                                            </h2>
                                            <p
                                                style={{
                                                    margin: '14px 0 0',
                                                    fontSize: '15.5px',
                                                    lineHeight: '1.7',
                                                    color: '#69748a',
                                                }}
                                            >
                                                One flat charge for the whole
                                                loan, 10% to 20% of the amount,
                                                agreed in writing before you
                                                sign. It never grows.
                                            </p>
                                            <p
                                                style={{
                                                    margin: '12px 0 0',
                                                    fontSize: '15.5px',
                                                    lineHeight: '1.7',
                                                    color: '#69748a',
                                                }}
                                            >
                                                Equal monthly payments. Pay on
                                                time and borrowing again is
                                                faster and cheaper.
                                            </p>
                                        </div>
                                        <div
                                            style={{
                                                flex: '1 1 320px',
                                                minWidth: '0',
                                                padding: 'clamp(26px,3vw,40px)',
                                                background: '#1428a4',
                                                display: 'flex',
                                                flexDirection: 'column',
                                                justifyContent: 'center',
                                            }}
                                        >
                                            <div
                                                style={{
                                                    fontSize: '13px',
                                                    fontWeight: '600',
                                                    color: 'rgba(255,255,255,.55)',
                                                }}
                                            >
                                                Example
                                            </div>
                                            <div
                                                style={{
                                                    marginTop: '8px',
                                                    fontSize:
                                                        'clamp(22px,2.4vw,28px)',
                                                    fontWeight: '800',
                                                    letterSpacing: '-.03em',
                                                    color: '#fff',
                                                    lineHeight: '1.25',
                                                }}
                                            >
                                                Borrow RWF 5,000,000
                                                <br />
                                                for 6 months
                                            </div>
                                            <div
                                                style={{
                                                    marginTop: '20px',
                                                    display: 'flex',
                                                    flexDirection: 'column',
                                                    gap: '1px',
                                                    background:
                                                        'rgba(255,255,255,.14)',
                                                    borderRadius: '13px',
                                                    overflow: 'hidden',
                                                }}
                                            >
                                                <div
                                                    style={{
                                                        padding: '13px 16px',
                                                        background:
                                                            'rgba(255,255,255,.05)',
                                                        display: 'flex',
                                                        justifyContent:
                                                            'space-between',
                                                        gap: '10px',
                                                    }}
                                                >
                                                    <span
                                                        style={{
                                                            fontSize: '14px',
                                                            color: 'rgba(255,255,255,.65)',
                                                        }}
                                                    >
                                                        One flat charge (18%)
                                                    </span>
                                                    <span
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            color: '#fff',
                                                        }}
                                                    >
                                                        RWF 900,000
                                                    </span>
                                                </div>
                                                <div
                                                    style={{
                                                        padding: '13px 16px',
                                                        background:
                                                            'rgba(255,255,255,.05)',
                                                        display: 'flex',
                                                        justifyContent:
                                                            'space-between',
                                                        gap: '10px',
                                                    }}
                                                >
                                                    <span
                                                        style={{
                                                            fontSize: '14px',
                                                            color: 'rgba(255,255,255,.65)',
                                                        }}
                                                    >
                                                        You repay in total
                                                    </span>
                                                    <span
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            color: '#fff',
                                                        }}
                                                    >
                                                        RWF 5,900,000
                                                    </span>
                                                </div>
                                                <div
                                                    style={{
                                                        padding: '13px 16px',
                                                        background:
                                                            'rgba(255,255,255,.05)',
                                                        display: 'flex',
                                                        justifyContent:
                                                            'space-between',
                                                        gap: '10px',
                                                    }}
                                                >
                                                    <span
                                                        style={{
                                                            fontSize: '14px',
                                                            color: 'rgba(255,255,255,.65)',
                                                        }}
                                                    >
                                                        Each month, for 6 months
                                                    </span>
                                                    <span
                                                        style={{
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            color: '#4ecb9d',
                                                        }}
                                                    >
                                                        RWF 983,333
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section
                                    data-rz-blue=""
                                    style={{
                                        position: 'relative',
                                        overflow: 'clip',
                                        marginTop: 'clamp(56px,8vw,100px)',
                                        background:
                                            'radial-gradient(130% 110% at 78% 0%, #1e3aff 0%, #1428a4 32%, #0a1440 72%, #070f30 100%)',
                                    }}
                                >
                                    <span
                                        aria-hidden="true"
                                        style={{
                                            position: 'absolute',
                                            inset: '0',
                                            opacity: '.45',
                                            backgroundImage:
                                                'linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px)',
                                            backgroundSize: '100% 48px',
                                            pointerEvents: 'none',
                                        }}
                                    ></span>
                                    <div
                                        style={{
                                            position: 'relative',
                                            maxWidth: '1200px',
                                            margin: '0 auto',
                                            padding:
                                                'clamp(56px,7.5vw,96px) clamp(18px,4vw,44px)',
                                        }}
                                    >
                                        <div
                                            className="rz-rise"
                                            style={{ maxWidth: '640px' }}
                                        >
                                            <div
                                                className="rz-kick"
                                                style={{
                                                    fontSize: '12.5px',
                                                    fontWeight: '700',
                                                    letterSpacing: '.1em',
                                                    color: '#7f92ff',
                                                }}
                                            >
                                                03 · THE CHECK
                                            </div>
                                            <h2
                                                style={{
                                                    margin: '13px 0 0',
                                                    fontSize:
                                                        'clamp(30px,4vw,48px)',
                                                    lineHeight: '1.02',
                                                    fontWeight: '800',
                                                    letterSpacing: '-.045em',
                                                    color: '#fff',
                                                }}
                                            >
                                                The check, honestly.
                                            </h2>
                                            <p
                                                style={{
                                                    margin: '14px 0 0',
                                                    fontSize:
                                                        'clamp(15.5px,1.3vw,17.5px)',
                                                    lineHeight: '1.65',
                                                    color: 'rgba(255,255,255,.62)',
                                                }}
                                            >
                                                The money you borrow belongs to
                                                real people, in Rwanda and
                                                abroad. So before you get it,
                                                and while you have it, a
                                                licensed accountant audits your
                                                business every month. Here's
                                                what that means for you:
                                            </p>
                                        </div>
                                        <div
                                            className="rz-rise"
                                            style={{
                                                marginTop:
                                                    'clamp(28px,4vw,44px)',
                                                maxWidth: '780px',
                                                display: 'flex',
                                                flexDirection: 'column',
                                                gap: '12px',
                                            }}
                                        >
                                            <div
                                                style={{
                                                    display: 'flex',
                                                    gap: '14px',
                                                    padding: '18px 20px',
                                                    borderRadius: '16px',
                                                    background:
                                                        'rgba(255,255,255,.08)',
                                                    backdropFilter:
                                                        'blur(16px)',
                                                    WebkitBackdropFilter:
                                                        'blur(16px)',
                                                    border: '1px solid rgba(255,255,255,.16)',
                                                }}
                                            >
                                                <span
                                                    style={{
                                                        flex: '0 0 auto',
                                                        width: '30px',
                                                        height: '30px',
                                                        borderRadius: '9px',
                                                        background: '#fff',
                                                        color: '#0a1440',
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent:
                                                            'center',
                                                        fontWeight: '800',
                                                        fontSize: '14px',
                                                    }}
                                                >
                                                    1
                                                </span>
                                                <p
                                                    style={{
                                                        margin: '0',
                                                        fontSize: '14.5px',
                                                        lineHeight: '1.65',
                                                        color: 'rgba(255,255,255,.66)',
                                                    }}
                                                >
                                                    <strong
                                                        style={{
                                                            color: 'rgb(255, 255, 255)',
                                                        }}
                                                    >
                                                        One visit before the
                                                        loan.
                                                    </strong>{' '}
                                                    An accountant comes to your
                                                    business, looks at your
                                                    records, and confirms you
                                                    can repay. Usually done
                                                    within 2 days.
                                                </p>
                                            </div>
                                            <div
                                                style={{
                                                    display: 'flex',
                                                    gap: '14px',
                                                    padding: '18px 20px',
                                                    borderRadius: '16px',
                                                    background:
                                                        'rgba(255,255,255,.08)',
                                                    backdropFilter:
                                                        'blur(16px)',
                                                    WebkitBackdropFilter:
                                                        'blur(16px)',
                                                    border: '1px solid rgba(255,255,255,.16)',
                                                }}
                                            >
                                                <span
                                                    style={{
                                                        flex: '0 0 auto',
                                                        width: '30px',
                                                        height: '30px',
                                                        borderRadius: '9px',
                                                        background: '#fff',
                                                        color: '#0a1440',
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent:
                                                            'center',
                                                        fontWeight: '800',
                                                        fontSize: '14px',
                                                    }}
                                                >
                                                    2
                                                </span>
                                                <p
                                                    style={{
                                                        margin: '0',
                                                        fontSize: '14.5px',
                                                        lineHeight: '1.65',
                                                        color: 'rgba(255,255,255,.66)',
                                                    }}
                                                >
                                                    <strong
                                                        style={{
                                                            color: 'rgb(255, 255, 255)',
                                                        }}
                                                    >
                                                        A short audit every
                                                        month.
                                                    </strong>{' '}
                                                    While the loan runs, the
                                                    accountant looks at your
                                                    numbers monthly. If your
                                                    business hits a rough patch,
                                                    we find about it early and
                                                    plan a restructuring.
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                {sheetOpen ? (
                                    <>
                                        <div
                                            onClick={closeSheet}
                                            style={{
                                                position: 'fixed',
                                                inset: '0',
                                                zIndex: '200',
                                                background: 'rgba(12,24,48,.5)',
                                                backdropFilter: 'blur(3px)',
                                                display: 'flex',
                                                alignItems: 'center',
                                                justifyContent: 'center',
                                                padding: '18px',
                                                overflowY: 'auto',
                                            }}
                                        >
                                            <div
                                                onClick={stop}
                                                style={{
                                                    width: '100%',
                                                    maxWidth: '420px',
                                                    margin: 'auto',
                                                    borderRadius: '20px',
                                                    background: '#fff',
                                                    boxShadow:
                                                        '0 40px 90px -30px rgba(12,24,48,.5)',
                                                    overflow: 'hidden',
                                                    animation:
                                                        'rz-pop .28s ease both',
                                                }}
                                            >
                                                <div
                                                    style={{
                                                        padding:
                                                            '20px 22px 18px',
                                                        background: '#1428a4',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            display: 'flex',
                                                            alignItems:
                                                                'flex-start',
                                                            justifyContent:
                                                                'space-between',
                                                            gap: '14px',
                                                        }}
                                                    >
                                                        <div>
                                                            <div
                                                                style={{
                                                                    fontSize:
                                                                        '11px',
                                                                    fontWeight:
                                                                        '700',
                                                                    letterSpacing:
                                                                        '.12em',
                                                                    color: 'rgba(255,255,255,.5)',
                                                                }}
                                                            >
                                                                YOU ARE ASKING
                                                                TO BORROW
                                                            </div>
                                                            <div
                                                                style={{
                                                                    marginTop:
                                                                        '6px',
                                                                    fontSize:
                                                                        '26px',
                                                                    fontWeight:
                                                                        '800',
                                                                    letterSpacing:
                                                                        '-.03em',
                                                                    color: '#fff',
                                                                    lineHeight:
                                                                        '1',
                                                                }}
                                                            >
                                                                {qualStr}
                                                            </div>
                                                        </div>
                                                        <button
                                                            onClick={closeSheet}
                                                            style={{
                                                                flex: '0 0 auto',
                                                                width: '30px',
                                                                height: '30px',
                                                                display: 'flex',
                                                                alignItems:
                                                                    'center',
                                                                justifyContent:
                                                                    'center',
                                                                border: 'none',
                                                                borderRadius:
                                                                    '9px',
                                                                background:
                                                                    'rgba(255,255,255,.12)',
                                                                color: '#fff',
                                                                fontSize:
                                                                    '15px',
                                                                cursor: 'pointer',
                                                                fontFamily:
                                                                    'inherit',
                                                            }}
                                                        >
                                                            ✕
                                                        </button>
                                                    </div>
                                                    <div
                                                        style={{
                                                            marginTop: '14px',
                                                            display: 'flex',
                                                            flexDirection:
                                                                'column',
                                                            gap: '1px',
                                                            background:
                                                                'rgba(255,255,255,.14)',
                                                            borderRadius:
                                                                '11px',
                                                            overflow: 'hidden',
                                                        }}
                                                    >
                                                        <div
                                                            style={{
                                                                padding:
                                                                    '9px 13px',
                                                                background:
                                                                    'rgba(255,255,255,.06)',
                                                                display: 'flex',
                                                                justifyContent:
                                                                    'space-between',
                                                                gap: '10px',
                                                            }}
                                                        >
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '12.5px',
                                                                    color: 'rgba(255,255,255,.6)',
                                                                }}
                                                            >
                                                                Repayment term
                                                            </span>
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '13px',
                                                                    fontWeight:
                                                                        '700',
                                                                    color: '#fff',
                                                                }}
                                                            >
                                                                {termStr}
                                                            </span>
                                                        </div>
                                                        <div
                                                            style={{
                                                                padding:
                                                                    '9px 13px',
                                                                background:
                                                                    'rgba(255,255,255,.06)',
                                                                display: 'flex',
                                                                justifyContent:
                                                                    'space-between',
                                                                gap: '10px',
                                                            }}
                                                        >
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '12.5px',
                                                                    color: 'rgba(255,255,255,.6)',
                                                                }}
                                                            >
                                                                One flat charge
                                                            </span>
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '13px',
                                                                    fontWeight:
                                                                        '700',
                                                                    color: '#fff',
                                                                }}
                                                            >
                                                                {chargePctStr}
                                                            </span>
                                                        </div>
                                                        <div
                                                            style={{
                                                                padding:
                                                                    '9px 13px',
                                                                background:
                                                                    'rgba(255,255,255,.06)',
                                                                display: 'flex',
                                                                justifyContent:
                                                                    'space-between',
                                                                gap: '10px',
                                                            }}
                                                        >
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '12.5px',
                                                                    color: 'rgba(255,255,255,.6)',
                                                                }}
                                                            >
                                                                You repay
                                                                monthly
                                                            </span>
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '13px',
                                                                    fontWeight:
                                                                        '700',
                                                                    color: '#fff',
                                                                }}
                                                            >
                                                                {monStr}
                                                            </span>
                                                        </div>
                                                        <div
                                                            style={{
                                                                padding:
                                                                    '9px 13px',
                                                                background:
                                                                    'rgba(255,255,255,.06)',
                                                                display: 'flex',
                                                                justifyContent:
                                                                    'space-between',
                                                                gap: '10px',
                                                            }}
                                                        >
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '12.5px',
                                                                    color: 'rgba(255,255,255,.6)',
                                                                }}
                                                            >
                                                                Total you repay
                                                            </span>
                                                            <span
                                                                style={{
                                                                    fontSize:
                                                                        '13px',
                                                                    fontWeight:
                                                                        '700',
                                                                    color: '#4ecb9d',
                                                                }}
                                                            >
                                                                {totalStr}
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                {notSent ? (
                                                    <>
                                                        <div
                                                            style={{
                                                                padding:
                                                                    '20px 22px 22px',
                                                            }}
                                                        >
                                                            <p
                                                                style={{
                                                                    margin: '0 0 15px',
                                                                    fontSize:
                                                                        '13.5px',
                                                                    lineHeight:
                                                                        '1.6',
                                                                    color: '#69748a',
                                                                }}
                                                            >
                                                                Nothing is
                                                                committed yet.
                                                                Leave your
                                                                details and we
                                                                will call you to
                                                                confirm the
                                                                amount and book
                                                                the accountant's
                                                                visit.
                                                            </p>
                                                            <div
                                                                style={{
                                                                    display:
                                                                        'flex',
                                                                    flexDirection:
                                                                        'column',
                                                                    gap: '11px',
                                                                }}
                                                            >
                                                                <div>
                                                                    <div
                                                                        style={{
                                                                            fontSize:
                                                                                '12px',
                                                                            fontWeight:
                                                                                '600',
                                                                            color: '#69748a',
                                                                        }}
                                                                    >
                                                                        Business
                                                                        name
                                                                    </div>
                                                                    <input
                                                                        value={
                                                                            fBiz
                                                                        }
                                                                        onChange={
                                                                            setBiz
                                                                        }
                                                                        placeholder="As registered"
                                                                        style={{
                                                                            marginTop:
                                                                                '6px',
                                                                            width: '100%',
                                                                            height: '44px',
                                                                            padding:
                                                                                '0 14px',
                                                                            boxSizing:
                                                                                'border-box',
                                                                            borderRadius:
                                                                                '11px',
                                                                            border: '1.5px solid #e2e8f2',
                                                                            background:
                                                                                '#f8fafd',
                                                                            outline:
                                                                                'none',
                                                                            fontSize:
                                                                                '14.5px',
                                                                            fontFamily:
                                                                                'inherit',
                                                                            color: '#0c1830',
                                                                        }}
                                                                    />
                                                                </div>
                                                                <div>
                                                                    <div
                                                                        style={{
                                                                            display:
                                                                                'flex',
                                                                            alignItems:
                                                                                'center',
                                                                            justifyContent:
                                                                                'space-between',
                                                                            gap: '10px',
                                                                        }}
                                                                    >
                                                                        <div
                                                                            style={{
                                                                                fontSize:
                                                                                    '12px',
                                                                                fontWeight:
                                                                                    '600',
                                                                                color: '#69748a',
                                                                            }}
                                                                        >
                                                                            How
                                                                            should
                                                                            we
                                                                            reach
                                                                            you?
                                                                        </div>
                                                                        <div
                                                                            style={{
                                                                                display:
                                                                                    'flex',
                                                                                gap: '5px',
                                                                                padding:
                                                                                    '3px',
                                                                                borderRadius:
                                                                                    '9px',
                                                                                background:
                                                                                    '#f0f3f9',
                                                                            }}
                                                                        >
                                                                            {(
                                                                                modes ??
                                                                                []
                                                                            ).map(
                                                                                (
                                                                                    md: any,
                                                                                    mdI: number,
                                                                                ) => (
                                                                                    <Fragment
                                                                                        key={
                                                                                            mdI
                                                                                        }
                                                                                    >
                                                                                        <button
                                                                                            onClick={
                                                                                                md.set
                                                                                            }
                                                                                            style={{
                                                                                                padding:
                                                                                                    '5px 12px',
                                                                                                border: 'none',
                                                                                                borderRadius:
                                                                                                    '7px',
                                                                                                background:
                                                                                                    md.bg,
                                                                                                color: md.fg,
                                                                                                fontSize:
                                                                                                    '11.5px',
                                                                                                fontWeight:
                                                                                                    '700',
                                                                                                cursor: 'pointer',
                                                                                                fontFamily:
                                                                                                    'inherit',
                                                                                                boxShadow:
                                                                                                    md.sh,
                                                                                            }}
                                                                                        >
                                                                                            {
                                                                                                md.label
                                                                                            }
                                                                                        </button>
                                                                                    </Fragment>
                                                                                ),
                                                                            )}
                                                                        </div>
                                                                    </div>
                                                                    <div
                                                                        style={{
                                                                            marginTop:
                                                                                '6px',
                                                                            display:
                                                                                'flex',
                                                                            gap: '8px',
                                                                        }}
                                                                    >
                                                                        <select
                                                                            value={
                                                                                fCode
                                                                            }
                                                                            onChange={
                                                                                setCode
                                                                            }
                                                                            style={{
                                                                                display:
                                                                                    codeDisp,
                                                                                flex: '0 0 auto',
                                                                                width: '102px',
                                                                                height: '44px',
                                                                                padding:
                                                                                    '0 12px',
                                                                                boxSizing:
                                                                                    'border-box',
                                                                                borderRadius:
                                                                                    '11px',
                                                                                border: '1.5px solid #e2e8f2',
                                                                                background:
                                                                                    '#f8fafd',
                                                                                outline:
                                                                                    'none',
                                                                                fontSize:
                                                                                    '14.5px',
                                                                                fontFamily:
                                                                                    'inherit',
                                                                                color: '#0c1830',
                                                                                cursor: 'pointer',
                                                                            }}
                                                                        >
                                                                            {(
                                                                                codes ??
                                                                                []
                                                                            ).map(
                                                                                (
                                                                                    c: any,
                                                                                    cI: number,
                                                                                ) => (
                                                                                    <Fragment
                                                                                        key={
                                                                                            cI
                                                                                        }
                                                                                    >
                                                                                        <option
                                                                                            value={
                                                                                                c.v
                                                                                            }
                                                                                        >
                                                                                            {
                                                                                                c.l
                                                                                            }
                                                                                        </option>
                                                                                    </Fragment>
                                                                                ),
                                                                            )}
                                                                        </select>
                                                                        <input
                                                                            value={
                                                                                fContact
                                                                            }
                                                                            onChange={
                                                                                setContact
                                                                            }
                                                                            inputMode={
                                                                                contactMode
                                                                            }
                                                                            placeholder={
                                                                                contactPh
                                                                            }
                                                                            style={{
                                                                                flex: '1 1 auto',
                                                                                minWidth:
                                                                                    '0',
                                                                                height: '44px',
                                                                                padding:
                                                                                    '0 12px',
                                                                                boxSizing:
                                                                                    'border-box',
                                                                                borderRadius:
                                                                                    '11px',
                                                                                border: '1.5px solid #e2e8f2',
                                                                                background:
                                                                                    '#f8fafd',
                                                                                outline:
                                                                                    'none',
                                                                                fontSize:
                                                                                    '14.5px',
                                                                                fontFamily:
                                                                                    'inherit',
                                                                                color: '#0c1830',
                                                                            }}
                                                                        />
                                                                    </div>
                                                                </div>
                                                                <div>
                                                                    <div
                                                                        style={{
                                                                            fontSize:
                                                                                '12px',
                                                                            fontWeight:
                                                                                '600',
                                                                            color: '#69748a',
                                                                        }}
                                                                    >
                                                                        Where is
                                                                        the
                                                                        business?
                                                                    </div>
                                                                    <div
                                                                        style={{
                                                                            marginTop:
                                                                                '6px',
                                                                            display:
                                                                                'flex',
                                                                            gap: '8px',
                                                                        }}
                                                                    >
                                                                        <select
                                                                            value={
                                                                                fProv
                                                                            }
                                                                            onChange={
                                                                                setProv
                                                                            }
                                                                            style={{
                                                                                flex: '1 1 0',
                                                                                minWidth:
                                                                                    '0',
                                                                                height: '44px',
                                                                                padding:
                                                                                    '0 12px',
                                                                                boxSizing:
                                                                                    'border-box',
                                                                                borderRadius:
                                                                                    '11px',
                                                                                border: '1.5px solid #e2e8f2',
                                                                                background:
                                                                                    '#f8fafd',
                                                                                outline:
                                                                                    'none',
                                                                                fontSize:
                                                                                    '14.5px',
                                                                                fontFamily:
                                                                                    'inherit',
                                                                                color: '#0c1830',
                                                                                cursor: 'pointer',
                                                                            }}
                                                                        >
                                                                            <option value="">
                                                                                Province
                                                                            </option>
                                                                            {(
                                                                                provinces ??
                                                                                []
                                                                            ).map(
                                                                                (
                                                                                    p: any,
                                                                                    pI: number,
                                                                                ) => (
                                                                                    <Fragment
                                                                                        key={
                                                                                            pI
                                                                                        }
                                                                                    >
                                                                                        <option
                                                                                            value={
                                                                                                p
                                                                                            }
                                                                                        >
                                                                                            {
                                                                                                p
                                                                                            }
                                                                                        </option>
                                                                                    </Fragment>
                                                                                ),
                                                                            )}
                                                                        </select>
                                                                        <select
                                                                            value={
                                                                                fDist
                                                                            }
                                                                            onChange={
                                                                                setDist
                                                                            }
                                                                            disabled={
                                                                                distDisabled
                                                                            }
                                                                            style={{
                                                                                flex: '1 1 0',
                                                                                minWidth:
                                                                                    '0',
                                                                                height: '44px',
                                                                                padding:
                                                                                    '0 12px',
                                                                                boxSizing:
                                                                                    'border-box',
                                                                                borderRadius:
                                                                                    '11px',
                                                                                border: '1.5px solid #e2e8f2',
                                                                                background:
                                                                                    '#f8fafd',
                                                                                outline:
                                                                                    'none',
                                                                                fontSize:
                                                                                    '14.5px',
                                                                                fontFamily:
                                                                                    'inherit',
                                                                                color: '#0c1830',
                                                                                cursor: distCur,
                                                                            }}
                                                                        >
                                                                            <option value="">
                                                                                District
                                                                            </option>
                                                                            {(
                                                                                districts ??
                                                                                []
                                                                            ).map(
                                                                                (
                                                                                    d: any,
                                                                                    dI: number,
                                                                                ) => (
                                                                                    <Fragment
                                                                                        key={
                                                                                            dI
                                                                                        }
                                                                                    >
                                                                                        <option
                                                                                            value={
                                                                                                d
                                                                                            }
                                                                                        >
                                                                                            {
                                                                                                d
                                                                                            }
                                                                                        </option>
                                                                                    </Fragment>
                                                                                ),
                                                                            )}
                                                                        </select>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <button
                                                                onClick={submit}
                                                                disabled={
                                                                    subDisabled
                                                                }
                                                                style={{
                                                                    marginTop:
                                                                        '16px',
                                                                    width: '100%',
                                                                    height: '48px',
                                                                    border: 'none',
                                                                    borderRadius:
                                                                        '12px',
                                                                    background:
                                                                        subBg,
                                                                    color: subFg,
                                                                    fontSize:
                                                                        '15px',
                                                                    fontWeight:
                                                                        '600',
                                                                    cursor: subCur,
                                                                    fontFamily:
                                                                        'inherit',
                                                                    transition:
                                                                        'background .2s',
                                                                }}
                                                            >
                                                                Send my details
                                                            </button>
                                                            {submitErr ? (
                                                                <div
                                                                    style={{
                                                                        marginTop:
                                                                            '9px',
                                                                        fontSize:
                                                                            '11.5px',
                                                                        fontWeight:
                                                                            '600',
                                                                        color: '#c0392b',
                                                                        textAlign:
                                                                            'center',
                                                                    }}
                                                                >
                                                                    {submitErr}
                                                                </div>
                                                            ) : null}
                                                            <p
                                                                style={{
                                                                    margin: '11px 0 0',
                                                                    fontSize:
                                                                        '11.5px',
                                                                    lineHeight:
                                                                        '1.6',
                                                                    color: '#8894a8',
                                                                }}
                                                            >
                                                                We use your
                                                                details only to
                                                                contact you
                                                                about borrowing
                                                                on Rozine.
                                                            </p>
                                                        </div>
                                                    </>
                                                ) : null}

                                                {sent ? (
                                                    <>
                                                        <div
                                                            style={{
                                                                padding:
                                                                    '26px 22px 24px',
                                                                textAlign:
                                                                    'center',
                                                            }}
                                                        >
                                                            <span
                                                                style={{
                                                                    display:
                                                                        'inline-flex',
                                                                    alignItems:
                                                                        'center',
                                                                    justifyContent:
                                                                        'center',
                                                                    width: '46px',
                                                                    height: '46px',
                                                                    borderRadius:
                                                                        '14px',
                                                                    background:
                                                                        'rgba(29,158,117,.12)',
                                                                }}
                                                            >
                                                                <svg
                                                                    width="24"
                                                                    height="24"
                                                                    viewBox="0 0 24 24"
                                                                    fill="none"
                                                                    stroke="#1d9e75"
                                                                    strokeWidth="2.6"
                                                                    strokeLinecap="round"
                                                                    strokeLinejoin="round"
                                                                >
                                                                    <path d="M4 12.5l5 5L20 6.5" />
                                                                </svg>
                                                            </span>
                                                            <div
                                                                style={{
                                                                    marginTop:
                                                                        '14px',
                                                                    fontSize:
                                                                        '19px',
                                                                    fontWeight:
                                                                        '800',
                                                                    letterSpacing:
                                                                        '-.025em',
                                                                    color: '#0c1830',
                                                                }}
                                                            >
                                                                Got it.
                                                            </div>
                                                            <p
                                                                style={{
                                                                    margin: '9px auto 0',
                                                                    maxWidth:
                                                                        '320px',
                                                                    fontSize:
                                                                        '14px',
                                                                    lineHeight:
                                                                        '1.65',
                                                                    color: '#69748a',
                                                                }}
                                                            >
                                                                We will call you
                                                                within a day and
                                                                tell you whether{' '}
                                                                {bizName} is
                                                                likely to
                                                                qualify, before
                                                                any accountant
                                                                visit is booked.
                                                            </p>
                                                            <div
                                                                style={{
                                                                    margin: '0 auto',
                                                                    width: '100%',
                                                                    maxWidth:
                                                                        '360px',
                                                                    containerType:
                                                                        'inline-size',
                                                                }}
                                                            >
                                                                <div
                                                                    style={{
                                                                        position:
                                                                            'relative',
                                                                        width: '100%',
                                                                        aspectRatio:
                                                                            '1.585',
                                                                        borderRadius:
                                                                            '4.222cqw',
                                                                        overflow:
                                                                            'hidden',
                                                                        background:
                                                                            'radial-gradient(150% 130% at 14% 0%, #22b585 0%, #17795a 40%, #052a1c 100%)',
                                                                        boxShadow:
                                                                            '0 5.556cqw 11.111cqw -3.333cqw rgba(29,158,117,.5), inset 0 0.222cqw 0 rgba(255,255,255,.3)',
                                                                        textAlign:
                                                                            'left',
                                                                    }}
                                                                >
                                                                    <div
                                                                        style={{
                                                                            position:
                                                                                'absolute',
                                                                            left: '-30%',
                                                                            top: '-30%',
                                                                            width: '160%',
                                                                            height: '160%',
                                                                            opacity:
                                                                                '.11',
                                                                            transform:
                                                                                'rotate(-30deg)',
                                                                            backgroundImage:
                                                                                'url("/site/img/ed8e027e.png")',
                                                                            backgroundRepeat:
                                                                                'repeat',
                                                                            backgroundSize:
                                                                                '4cqw 2.22cqw',
                                                                        }}
                                                                    ></div>
                                                                    <div
                                                                        style={{
                                                                            position:
                                                                                'absolute',
                                                                            right: '-15.556cqw',
                                                                            top: '-15.556cqw',
                                                                            width: '51.111cqw',
                                                                            height: '51.111cqw',
                                                                            borderRadius:
                                                                                '50%',
                                                                            background:
                                                                                'radial-gradient(closest-side, rgba(255,255,255,.2), transparent)',
                                                                        }}
                                                                    ></div>
                                                                    <div
                                                                        style={{
                                                                            position:
                                                                                'relative',
                                                                            height: '100%',
                                                                            padding:
                                                                                '5.778cqw 6.222cqw',
                                                                            display:
                                                                                'flex',
                                                                            flexDirection:
                                                                                'column',
                                                                        }}
                                                                    >
                                                                        <div
                                                                            style={{
                                                                                display:
                                                                                    'flex',
                                                                                alignItems:
                                                                                    'flex-start',
                                                                                justifyContent:
                                                                                    'space-between',
                                                                                gap: '2cqw',
                                                                            }}
                                                                        >
                                                                            <img
                                                                                src="/site/img/25d454a5.png"
                                                                                alt="rozine"
                                                                                style={{
                                                                                    height: '7cqw',
                                                                                    width: 'auto',
                                                                                    display:
                                                                                        'block',
                                                                                }}
                                                                            />
                                                                            <span
                                                                                style={{
                                                                                    fontSize:
                                                                                        '2.533cqw',
                                                                                    fontWeight:
                                                                                        '700',
                                                                                    letterSpacing:
                                                                                        '.14em',
                                                                                    color: 'rgba(255,255,255,.72)',
                                                                                    whiteSpace:
                                                                                        'nowrap',
                                                                                }}
                                                                            >
                                                                                BORROWING
                                                                                REQUEST
                                                                            </span>
                                                                        </div>
                                                                        <div
                                                                            style={{
                                                                                marginTop:
                                                                                    'auto',
                                                                            }}
                                                                        >
                                                                            <div
                                                                                style={{
                                                                                    fontSize:
                                                                                        '3cqw',
                                                                                    fontWeight:
                                                                                        '600',
                                                                                    letterSpacing:
                                                                                        '.1em',
                                                                                    color: 'rgba(255,255,255,.82)',
                                                                                    textTransform:
                                                                                        'uppercase',
                                                                                }}
                                                                            >
                                                                                {
                                                                                    cardBiz
                                                                                }{' '}
                                                                                <span
                                                                                    style={{
                                                                                        color: 'rgba(255,255,255,.9)',
                                                                                    }}
                                                                                >
                                                                                    ·
                                                                                </span>{' '}
                                                                                <span
                                                                                    style={{
                                                                                        fontWeight:
                                                                                            '400',
                                                                                    }}
                                                                                >
                                                                                    {
                                                                                        cardLoc
                                                                                    }
                                                                                </span>
                                                                            </div>
                                                                            <div
                                                                                style={{
                                                                                    marginTop:
                                                                                        '1.667cqw',
                                                                                    fontSize:
                                                                                        '2.333cqw',
                                                                                    fontWeight:
                                                                                        '700',
                                                                                    letterSpacing:
                                                                                        '.13em',
                                                                                    color: 'rgba(255,255,255,.62)',
                                                                                }}
                                                                            >
                                                                                ASKING
                                                                                TO
                                                                                BORROW
                                                                            </div>
                                                                            <div
                                                                                style={{
                                                                                    marginTop:
                                                                                        '0.667cqw',
                                                                                    fontSize:
                                                                                        '10cqw',
                                                                                    fontWeight:
                                                                                        '800',
                                                                                    letterSpacing:
                                                                                        '-.03em',
                                                                                    color: '#fff',
                                                                                    lineHeight:
                                                                                        '1',
                                                                                }}
                                                                            >
                                                                                {
                                                                                    qualStr
                                                                                }
                                                                            </div>
                                                                            <div
                                                                                style={{
                                                                                    marginTop:
                                                                                        '2.667cqw',
                                                                                    display:
                                                                                        'flex',
                                                                                    alignItems:
                                                                                        'flex-end',
                                                                                    justifyContent:
                                                                                        'space-between',
                                                                                    gap: '2cqw',
                                                                                }}
                                                                            >
                                                                                <div
                                                                                    style={{
                                                                                        display:
                                                                                            'flex',
                                                                                        gap: '4.333cqw',
                                                                                    }}
                                                                                >
                                                                                    <div>
                                                                                        <div
                                                                                            style={{
                                                                                                fontSize:
                                                                                                    '2.333cqw',
                                                                                                letterSpacing:
                                                                                                    '.1em',
                                                                                                whiteSpace:
                                                                                                    'nowrap',
                                                                                                color: 'rgba(255,255,255,.6)',
                                                                                            }}
                                                                                        >
                                                                                            TERM
                                                                                        </div>
                                                                                        <div
                                                                                            style={{
                                                                                                marginTop:
                                                                                                    '0.444cqw',
                                                                                                fontSize:
                                                                                                    '3.833cqw',
                                                                                                fontWeight:
                                                                                                    '700',
                                                                                                whiteSpace:
                                                                                                    'nowrap',
                                                                                                color: '#fff',
                                                                                            }}
                                                                                        >
                                                                                            {
                                                                                                termStr
                                                                                            }
                                                                                        </div>
                                                                                    </div>
                                                                                    <div>
                                                                                        <div
                                                                                            style={{
                                                                                                fontSize:
                                                                                                    '2.333cqw',
                                                                                                letterSpacing:
                                                                                                    '.1em',
                                                                                                whiteSpace:
                                                                                                    'nowrap',
                                                                                                color: 'rgba(255,255,255,.6)',
                                                                                            }}
                                                                                        >
                                                                                            FLAT
                                                                                            CHARGE
                                                                                        </div>
                                                                                        <div
                                                                                            style={{
                                                                                                marginTop:
                                                                                                    '0.444cqw',
                                                                                                fontSize:
                                                                                                    '3.833cqw',
                                                                                                fontWeight:
                                                                                                    '700',
                                                                                                whiteSpace:
                                                                                                    'nowrap',
                                                                                                color: '#fff',
                                                                                            }}
                                                                                        >
                                                                                            {
                                                                                                chargePctStr
                                                                                            }
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <div
                                                                                    style={{
                                                                                        display:
                                                                                            'flex',
                                                                                        alignItems:
                                                                                            'center',
                                                                                        gap: '1.333cqw',
                                                                                        fontSize:
                                                                                            '3.5cqw',
                                                                                        fontWeight:
                                                                                            '700',
                                                                                        letterSpacing:
                                                                                            '.03em',
                                                                                        whiteSpace:
                                                                                            'nowrap',
                                                                                        color: '#fff',
                                                                                    }}
                                                                                >
                                                                                    <svg
                                                                                        viewBox="0 0 24 24"
                                                                                        fill="none"
                                                                                        style={{
                                                                                            width: '4.667cqw',
                                                                                            height: '4.667cqw',
                                                                                            flexShrink:
                                                                                                '0',
                                                                                        }}
                                                                                    >
                                                                                        <circle
                                                                                            cx="12"
                                                                                            cy="12"
                                                                                            r="9"
                                                                                            stroke="#fff"
                                                                                            strokeWidth="1.7"
                                                                                        />
                                                                                        <path
                                                                                            d="M3.2 12h17.6M12 3c2.7 2.6 2.7 15.4 0 18M12 3c-2.7 2.6-2.7 15.4 0 18"
                                                                                            stroke="#fff"
                                                                                            strokeWidth="1.5"
                                                                                        />
                                                                                    </svg>
                                                                                    rozine.rw
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div
                                                                    style={{
                                                                        marginTop:
                                                                            '11px',
                                                                        display:
                                                                            'flex',
                                                                        gap: '7px',
                                                                    }}
                                                                >
                                                                    {(
                                                                        shareBtns ??
                                                                        []
                                                                    ).map(
                                                                        (
                                                                            sb: any,
                                                                            sbI: number,
                                                                        ) => (
                                                                            <Fragment
                                                                                key={
                                                                                    sbI
                                                                                }
                                                                            >
                                                                                <button
                                                                                    onClick={
                                                                                        sb.on
                                                                                    }
                                                                                    title={
                                                                                        sb.label
                                                                                    }
                                                                                    style={{
                                                                                        flex: '1',
                                                                                        height: '38px',
                                                                                        borderRadius:
                                                                                            '10px',
                                                                                        border: '1px solid #e2e8f2',
                                                                                        background:
                                                                                            sb.bg,
                                                                                        color: sb.fg,
                                                                                        cursor: 'pointer',
                                                                                        display:
                                                                                            'flex',
                                                                                        alignItems:
                                                                                            'center',
                                                                                        justifyContent:
                                                                                            'center',
                                                                                        gap: '6px',
                                                                                        fontSize:
                                                                                            '11.5px',
                                                                                        fontWeight:
                                                                                            '600',
                                                                                        fontFamily:
                                                                                            'inherit',
                                                                                    }}
                                                                                >
                                                                                    {
                                                                                        sb.icon
                                                                                    }
                                                                                    {
                                                                                        sb.text
                                                                                    }
                                                                                </button>
                                                                            </Fragment>
                                                                        ),
                                                                    )}
                                                                </div>
                                                                <div
                                                                    style={{
                                                                        marginTop:
                                                                            '7px',
                                                                        minHeight:
                                                                            '15px',
                                                                        fontSize:
                                                                            '11.5px',
                                                                        fontWeight:
                                                                            '600',
                                                                        color: '#1d9e75',
                                                                    }}
                                                                >
                                                                    {shareMsg}
                                                                </div>
                                                            </div>
                                                            <div
                                                                style={{
                                                                    marginTop:
                                                                        '16px',
                                                                }}
                                                            >
                                                                <button
                                                                    onClick={
                                                                        closeSheet
                                                                    }
                                                                    style={{
                                                                        padding:
                                                                            '12px 24px',
                                                                        borderRadius:
                                                                            '999px',
                                                                        border: '1.5px solid #e2e8f2',
                                                                        background:
                                                                            'none',
                                                                        color: '#0c1830',
                                                                        fontSize:
                                                                            '14px',
                                                                        fontWeight:
                                                                            '600',
                                                                        cursor: 'pointer',
                                                                        fontFamily:
                                                                            'inherit',
                                                                    }}
                                                                >
                                                                    Close
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </>
                                                ) : null}
                                            </div>
                                        </div>
                                    </>
                                ) : null}

                                <section
                                    style={{
                                        maxWidth: '1200px',
                                        margin: '0 auto',
                                        padding:
                                            'clamp(56px,8vw,100px) clamp(18px,4vw,44px) clamp(56px,8vw,100px)',
                                    }}
                                >
                                    <div
                                        className="rz-rise"
                                        style={{
                                            display: 'flex',
                                            flexWrap: 'wrap',
                                            gap: 'clamp(28px,4.5vw,64px)',
                                            alignItems: 'flex-start',
                                        }}
                                    >
                                        <div
                                            style={{
                                                flex: '1 1 280px',
                                                minWidth: '0',
                                            }}
                                        >
                                            <div
                                                className="rz-kick"
                                                style={{
                                                    fontSize: '12.5px',
                                                    fontWeight: '700',
                                                    letterSpacing: '.1em',
                                                    color: '#1e3aff',
                                                }}
                                            >
                                                04 · QUESTIONS
                                            </div>
                                            <h2
                                                style={{
                                                    margin: '13px 0 0',
                                                    fontSize:
                                                        'clamp(28px,3.4vw,42px)',
                                                    lineHeight: '1.05',
                                                    fontWeight: '800',
                                                    letterSpacing: '-.04em',
                                                }}
                                            >
                                                Questions businesses ask.
                                            </h2>
                                        </div>
                                        <div
                                            style={{
                                                flex: '1 1 460px',
                                                minWidth: '0',
                                            }}
                                        >
                                            <div
                                                className="rz-glass"
                                                style={{
                                                    marginBottom: '9px',
                                                    borderRadius: '16px',
                                                    overflow: 'hidden',
                                                    background:
                                                        'rgba(255,255,255,.58)',
                                                    backdropFilter:
                                                        'blur(18px) saturate(1.45)',
                                                    WebkitBackdropFilter:
                                                        'blur(18px) saturate(1.45)',
                                                    border: '1px solid rgba(12,24,48,.09)',
                                                    boxShadow:
                                                        '0 1px 2px rgba(12,24,48,.03), 0 20px 40px -24px rgba(12,24,48,.16)',
                                                }}
                                            >
                                                <button
                                                    onClick={bfaq0}
                                                    style={{
                                                        width: '100%',
                                                        padding: '18px 20px',
                                                        display: 'flex',
                                                        alignItems: 'baseline',
                                                        gap: '16px',
                                                        background: 'none',
                                                        border: 'none',
                                                        cursor: 'pointer',
                                                        textAlign: 'left',
                                                        fontFamily: 'inherit',
                                                    }}
                                                >
                                                    <span
                                                        style={{
                                                            flex: '1 1 auto',
                                                            fontSize:
                                                                'clamp(16px,1.5vw,18px)',
                                                            fontWeight: '600',
                                                            letterSpacing:
                                                                '-.02em',
                                                            color: '#0c1830',
                                                        }}
                                                    >
                                                        How long does it take to
                                                        get the money?
                                                    </span>
                                                    <span
                                                        style={{
                                                            flex: '0 0 auto',
                                                            width: '22px',
                                                            height: '22px',
                                                            borderRadius: '7px',
                                                            background:
                                                                'rgba(30,58,255,.09)',
                                                            color: '#1e3aff',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            lineHeight: '1',
                                                        }}
                                                    >
                                                        {bf0m}
                                                    </span>
                                                </button>
                                                {bf0 ? (
                                                    <>
                                                        <p
                                                            style={{
                                                                margin: '0',
                                                                padding:
                                                                    '0 20px 19px',
                                                                maxWidth:
                                                                    '560px',
                                                                fontSize:
                                                                    '15px',
                                                                lineHeight:
                                                                    '1.7',
                                                                color: '#69748a',
                                                                animation:
                                                                    'rz-pop .3s ease both',
                                                            }}
                                                        >
                                                            {bfa0}
                                                        </p>
                                                    </>
                                                ) : null}
                                            </div>
                                            <div
                                                className="rz-glass"
                                                style={{
                                                    marginBottom: '9px',
                                                    borderRadius: '16px',
                                                    overflow: 'hidden',
                                                    background:
                                                        'rgba(255,255,255,.58)',
                                                    backdropFilter:
                                                        'blur(18px) saturate(1.45)',
                                                    WebkitBackdropFilter:
                                                        'blur(18px) saturate(1.45)',
                                                    border: '1px solid rgba(12,24,48,.09)',
                                                    boxShadow:
                                                        '0 1px 2px rgba(12,24,48,.03), 0 20px 40px -24px rgba(12,24,48,.16)',
                                                }}
                                            >
                                                <button
                                                    onClick={bfaq1}
                                                    style={{
                                                        width: '100%',
                                                        padding: '18px 20px',
                                                        display: 'flex',
                                                        alignItems: 'baseline',
                                                        gap: '16px',
                                                        background: 'none',
                                                        border: 'none',
                                                        cursor: 'pointer',
                                                        textAlign: 'left',
                                                        fontFamily: 'inherit',
                                                    }}
                                                >
                                                    <span
                                                        style={{
                                                            flex: '1 1 auto',
                                                            fontSize:
                                                                'clamp(16px,1.5vw,18px)',
                                                            fontWeight: '600',
                                                            letterSpacing:
                                                                '-.02em',
                                                            color: '#0c1830',
                                                        }}
                                                    >
                                                        What if I have no
                                                        collateral?
                                                    </span>
                                                    <span
                                                        style={{
                                                            flex: '0 0 auto',
                                                            width: '22px',
                                                            height: '22px',
                                                            borderRadius: '7px',
                                                            background:
                                                                'rgba(30,58,255,.09)',
                                                            color: '#1e3aff',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            lineHeight: '1',
                                                        }}
                                                    >
                                                        {bf1m}
                                                    </span>
                                                </button>
                                                {bf1 ? (
                                                    <>
                                                        <div
                                                            style={{
                                                                padding:
                                                                    '0 20px 19px',
                                                                maxWidth:
                                                                    '560px',
                                                                fontSize:
                                                                    '15px',
                                                                lineHeight:
                                                                    '1.7',
                                                                color: '#69748a',
                                                                animation:
                                                                    'rz-pop .3s ease both',
                                                            }}
                                                        >
                                                            <p
                                                                style={{
                                                                    margin: '0',
                                                                }}
                                                            >
                                                                You don’t need
                                                                any. We lend
                                                                against five
                                                                years of profit
                                                                and your ability
                                                                to repay, not
                                                                against
                                                                property. That
                                                                is the point of
                                                                the accountant
                                                                check.
                                                            </p>
                                                            <p
                                                                style={{
                                                                    margin: '14px 0 0',
                                                                    fontWeight:
                                                                        '600',
                                                                    color: '#0c1830',
                                                                }}
                                                            >
                                                                How we size the
                                                                loan
                                                            </p>
                                                            <div
                                                                style={{
                                                                    marginTop:
                                                                        '8px',
                                                                    display:
                                                                        'flex',
                                                                    flexDirection:
                                                                        'column',
                                                                    gap: '5px',
                                                                    fontSize:
                                                                        '14px',
                                                                }}
                                                            >
                                                                <div
                                                                    style={{
                                                                        display:
                                                                            'flex',
                                                                        alignItems:
                                                                            'baseline',
                                                                        gap: '9px',
                                                                    }}
                                                                >
                                                                    <span
                                                                        aria-hidden="true"
                                                                        style={{
                                                                            flex: '0 0 auto',
                                                                            width: '5px',
                                                                            height: '5px',
                                                                            borderRadius:
                                                                                '50%',
                                                                            background:
                                                                                '#1e3aff',
                                                                            position:
                                                                                'relative',
                                                                            top: '-3px',
                                                                        }}
                                                                    ></span>
                                                                    <span>
                                                                        Monthly
                                                                        surplus
                                                                        =
                                                                        (revenue
                                                                        − costs)
                                                                        ÷ 12
                                                                    </span>
                                                                </div>
                                                                <div
                                                                    style={{
                                                                        display:
                                                                            'flex',
                                                                        alignItems:
                                                                            'baseline',
                                                                        gap: '9px',
                                                                    }}
                                                                >
                                                                    <span
                                                                        aria-hidden="true"
                                                                        style={{
                                                                            flex: '0 0 auto',
                                                                            width: '5px',
                                                                            height: '5px',
                                                                            borderRadius:
                                                                                '50%',
                                                                            background:
                                                                                '#1e3aff',
                                                                            position:
                                                                                'relative',
                                                                            top: '-3px',
                                                                        }}
                                                                    ></span>
                                                                    <span>
                                                                        Affordable
                                                                        payment
                                                                        =
                                                                        monthly
                                                                        surplus
                                                                        ÷ 1.25,
                                                                        so every
                                                                        payment
                                                                        is
                                                                        covered
                                                                        1.25
                                                                        times
                                                                        over
                                                                    </span>
                                                                </div>
                                                                <div
                                                                    style={{
                                                                        display:
                                                                            'flex',
                                                                        alignItems:
                                                                            'baseline',
                                                                        gap: '9px',
                                                                    }}
                                                                >
                                                                    <span
                                                                        aria-hidden="true"
                                                                        style={{
                                                                            flex: '0 0 auto',
                                                                            width: '5px',
                                                                            height: '5px',
                                                                            borderRadius:
                                                                                '50%',
                                                                            background:
                                                                                '#1e3aff',
                                                                            position:
                                                                                'relative',
                                                                            top: '-3px',
                                                                        }}
                                                                    ></span>
                                                                    <span>
                                                                        Loan =
                                                                        affordable
                                                                        payment
                                                                        × term ÷
                                                                        (1 +
                                                                        charge),
                                                                        capped
                                                                        at 35%
                                                                        of
                                                                        annual
                                                                        revenue
                                                                    </span>
                                                                </div>
                                                            </div>
                                                            <div
                                                                style={{
                                                                    marginTop:
                                                                        '14px',
                                                                    padding:
                                                                        '15px 17px',
                                                                    borderRadius:
                                                                        '14px',
                                                                    background:
                                                                        'rgba(30,58,255,.05)',
                                                                    border: '1px solid rgba(30,58,255,.12)',
                                                                    fontSize:
                                                                        '14px',
                                                                }}
                                                            >
                                                                <div
                                                                    style={{
                                                                        fontWeight:
                                                                            '700',
                                                                        color: '#0c1830',
                                                                    }}
                                                                >
                                                                    A real
                                                                    example
                                                                </div>
                                                                <div
                                                                    style={{
                                                                        marginTop:
                                                                            '3px',
                                                                        fontSize:
                                                                            '13px',
                                                                    }}
                                                                >
                                                                    Revenue RWF
                                                                    80,000,000,
                                                                    costs RWF
                                                                    62,000,000,
                                                                    6 month
                                                                    term, 17.5%
                                                                    charge.
                                                                </div>
                                                                <div
                                                                    style={{
                                                                        marginTop:
                                                                            '10px',
                                                                        display:
                                                                            'flex',
                                                                        flexDirection:
                                                                            'column',
                                                                    }}
                                                                >
                                                                    <div
                                                                        style={{
                                                                            display:
                                                                                'flex',
                                                                            justifyContent:
                                                                                'space-between',
                                                                            alignItems:
                                                                                'baseline',
                                                                            gap: '12px',
                                                                            padding:
                                                                                '9px 0',
                                                                            borderTop:
                                                                                '1px solid rgba(12,24,48,.09)',
                                                                        }}
                                                                    >
                                                                        <span>
                                                                            Monthly
                                                                            surplus
                                                                        </span>
                                                                        <span
                                                                            style={{
                                                                                flex: '0 0 auto',
                                                                                whiteSpace:
                                                                                    'nowrap',
                                                                                fontWeight:
                                                                                    '600',
                                                                                color: '#0c1830',
                                                                                fontVariantNumeric:
                                                                                    'tabular-nums',
                                                                            }}
                                                                        >
                                                                            RWF
                                                                            1,500,000
                                                                        </span>
                                                                    </div>
                                                                    <div
                                                                        style={{
                                                                            display:
                                                                                'flex',
                                                                            justifyContent:
                                                                                'space-between',
                                                                            alignItems:
                                                                                'baseline',
                                                                            gap: '12px',
                                                                            padding:
                                                                                '9px 0',
                                                                            borderTop:
                                                                                '1px solid rgba(12,24,48,.09)',
                                                                        }}
                                                                    >
                                                                        <span>
                                                                            Affordable
                                                                            payment
                                                                        </span>
                                                                        <span
                                                                            style={{
                                                                                flex: '0 0 auto',
                                                                                whiteSpace:
                                                                                    'nowrap',
                                                                                fontWeight:
                                                                                    '600',
                                                                                color: '#0c1830',
                                                                                fontVariantNumeric:
                                                                                    'tabular-nums',
                                                                            }}
                                                                        >
                                                                            RWF
                                                                            1,200,000
                                                                        </span>
                                                                    </div>
                                                                    <div
                                                                        style={{
                                                                            display:
                                                                                'flex',
                                                                            justifyContent:
                                                                                'space-between',
                                                                            alignItems:
                                                                                'baseline',
                                                                            gap: '12px',
                                                                            padding:
                                                                                '9px 0',
                                                                            borderTop:
                                                                                '1px solid rgba(12,24,48,.09)',
                                                                        }}
                                                                    >
                                                                        <span>
                                                                            You
                                                                            could
                                                                            borrow
                                                                        </span>
                                                                        <span
                                                                            style={{
                                                                                flex: '0 0 auto',
                                                                                whiteSpace:
                                                                                    'nowrap',
                                                                                fontWeight:
                                                                                    '600',
                                                                                color: '#0c1830',
                                                                                fontVariantNumeric:
                                                                                    'tabular-nums',
                                                                            }}
                                                                        >
                                                                            RWF
                                                                            6,100,000
                                                                        </span>
                                                                    </div>
                                                                    <div
                                                                        style={{
                                                                            display:
                                                                                'flex',
                                                                            justifyContent:
                                                                                'space-between',
                                                                            alignItems:
                                                                                'baseline',
                                                                            gap: '12px',
                                                                            padding:
                                                                                '9px 0',
                                                                            borderTop:
                                                                                '1px solid rgba(12,24,48,.09)',
                                                                        }}
                                                                    >
                                                                        <span>
                                                                            You
                                                                            repay
                                                                            in
                                                                            total
                                                                        </span>
                                                                        <span
                                                                            style={{
                                                                                flex: '0 0 auto',
                                                                                whiteSpace:
                                                                                    'nowrap',
                                                                                fontWeight:
                                                                                    '600',
                                                                                color: '#0c1830',
                                                                                fontVariantNumeric:
                                                                                    'tabular-nums',
                                                                            }}
                                                                        >
                                                                            RWF
                                                                            7,167,500
                                                                        </span>
                                                                    </div>
                                                                    <div
                                                                        style={{
                                                                            display:
                                                                                'flex',
                                                                            justifyContent:
                                                                                'space-between',
                                                                            alignItems:
                                                                                'baseline',
                                                                            gap: '12px',
                                                                            padding:
                                                                                '9px 0',
                                                                            borderTop:
                                                                                '1px solid rgba(12,24,48,.09)',
                                                                        }}
                                                                    >
                                                                        <span>
                                                                            Each
                                                                            month,
                                                                            for
                                                                            6
                                                                            months
                                                                        </span>
                                                                        <span
                                                                            style={{
                                                                                flex: '0 0 auto',
                                                                                whiteSpace:
                                                                                    'nowrap',
                                                                                fontWeight:
                                                                                    '600',
                                                                                color: '#0c1830',
                                                                                fontVariantNumeric:
                                                                                    'tabular-nums',
                                                                            }}
                                                                        >
                                                                            RWF
                                                                            1,194,583
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                                <div
                                                                    style={{
                                                                        marginTop:
                                                                            '10px',
                                                                        fontSize:
                                                                            '13px',
                                                                    }}
                                                                >
                                                                    The 35% cap
                                                                    would have
                                                                    allowed RWF
                                                                    28,000,000
                                                                    here, so the
                                                                    payment
                                                                    cover is
                                                                    what sets
                                                                    the amount.
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </>
                                                ) : null}
                                            </div>
                                            <div
                                                className="rz-glass"
                                                style={{
                                                    marginBottom: '9px',
                                                    borderRadius: '16px',
                                                    overflow: 'hidden',
                                                    background:
                                                        'rgba(255,255,255,.58)',
                                                    backdropFilter:
                                                        'blur(18px) saturate(1.45)',
                                                    WebkitBackdropFilter:
                                                        'blur(18px) saturate(1.45)',
                                                    border: '1px solid rgba(12,24,48,.09)',
                                                    boxShadow:
                                                        '0 1px 2px rgba(12,24,48,.03), 0 20px 40px -24px rgba(12,24,48,.16)',
                                                }}
                                            >
                                                <button
                                                    onClick={bfaq2}
                                                    style={{
                                                        width: '100%',
                                                        padding: '18px 20px',
                                                        display: 'flex',
                                                        alignItems: 'baseline',
                                                        gap: '16px',
                                                        background: 'none',
                                                        border: 'none',
                                                        cursor: 'pointer',
                                                        textAlign: 'left',
                                                        fontFamily: 'inherit',
                                                    }}
                                                >
                                                    <span
                                                        style={{
                                                            flex: '1 1 auto',
                                                            fontSize:
                                                                'clamp(16px,1.5vw,18px)',
                                                            fontWeight: '600',
                                                            letterSpacing:
                                                                '-.02em',
                                                            color: '#0c1830',
                                                        }}
                                                    >
                                                        Can I repay early?
                                                    </span>
                                                    <span
                                                        style={{
                                                            flex: '0 0 auto',
                                                            width: '22px',
                                                            height: '22px',
                                                            borderRadius: '7px',
                                                            background:
                                                                'rgba(30,58,255,.09)',
                                                            color: '#1e3aff',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            lineHeight: '1',
                                                        }}
                                                    >
                                                        {bf2m}
                                                    </span>
                                                </button>
                                                {bf2 ? (
                                                    <>
                                                        <p
                                                            style={{
                                                                margin: '0',
                                                                padding:
                                                                    '0 20px 19px',
                                                                maxWidth:
                                                                    '560px',
                                                                fontSize:
                                                                    '15px',
                                                                lineHeight:
                                                                    '1.7',
                                                                color: '#69748a',
                                                                animation:
                                                                    'rz-pop .3s ease both',
                                                            }}
                                                        >
                                                            {bfa2}
                                                        </p>
                                                    </>
                                                ) : null}
                                            </div>
                                            <div
                                                className="rz-glass"
                                                style={{
                                                    marginBottom: '9px',
                                                    borderRadius: '16px',
                                                    overflow: 'hidden',
                                                    background:
                                                        'rgba(255,255,255,.58)',
                                                    backdropFilter:
                                                        'blur(18px) saturate(1.45)',
                                                    WebkitBackdropFilter:
                                                        'blur(18px) saturate(1.45)',
                                                    border: '1px solid rgba(12,24,48,.09)',
                                                    boxShadow:
                                                        '0 1px 2px rgba(12,24,48,.03), 0 20px 40px -24px rgba(12,24,48,.16)',
                                                }}
                                            >
                                                <button
                                                    onClick={bfaq3}
                                                    style={{
                                                        width: '100%',
                                                        padding: '18px 20px',
                                                        display: 'flex',
                                                        alignItems: 'baseline',
                                                        gap: '16px',
                                                        background: 'none',
                                                        border: 'none',
                                                        cursor: 'pointer',
                                                        textAlign: 'left',
                                                        fontFamily: 'inherit',
                                                    }}
                                                >
                                                    <span
                                                        style={{
                                                            flex: '1 1 auto',
                                                            fontSize:
                                                                'clamp(16px,1.5vw,18px)',
                                                            fontWeight: '600',
                                                            letterSpacing:
                                                                '-.02em',
                                                            color: '#0c1830',
                                                        }}
                                                    >
                                                        What if I miss a
                                                        payment?
                                                    </span>
                                                    <span
                                                        style={{
                                                            flex: '0 0 auto',
                                                            width: '22px',
                                                            height: '22px',
                                                            borderRadius: '7px',
                                                            background:
                                                                'rgba(30,58,255,.09)',
                                                            color: '#1e3aff',
                                                            display: 'flex',
                                                            alignItems:
                                                                'center',
                                                            justifyContent:
                                                                'center',
                                                            fontSize: '14px',
                                                            fontWeight: '700',
                                                            lineHeight: '1',
                                                        }}
                                                    >
                                                        {bf3m}
                                                    </span>
                                                </button>
                                                {bf3 ? (
                                                    <>
                                                        <p
                                                            style={{
                                                                margin: '0',
                                                                padding:
                                                                    '0 20px 19px',
                                                                maxWidth:
                                                                    '560px',
                                                                fontSize:
                                                                    '15px',
                                                                lineHeight:
                                                                    '1.7',
                                                                color: '#69748a',
                                                                animation:
                                                                    'rz-pop .3s ease both',
                                                            }}
                                                        >
                                                            {bfa3}
                                                        </p>
                                                    </>
                                                ) : null}
                                            </div>
                                        </div>
                                    </div>
                                </section>
                            </>
                        ) : null}

                        {pgHelp ? (
                            <>
                                <section
                                    style={{
                                        background:
                                            'linear-gradient(180deg, rgba(248,250,253,.6) 0%, rgba(248,250,253,0) 100%)',
                                    }}
                                >
                                    <div
                                        style={{
                                            maxWidth: '1200px',
                                            margin: '0 auto',
                                            padding:
                                                'clamp(44px,6vw,72px) clamp(18px,4vw,44px)',
                                        }}
                                    >
                                        <div style={{ maxWidth: '720px' }}>
                                            <div
                                                className="rz-kick"
                                                style={{
                                                    fontSize: '12.5px',
                                                    fontWeight: '700',
                                                    letterSpacing: '.1em',
                                                    color: '#1e3aff',
                                                    animation:
                                                        'rz-pop .6s ease both',
                                                }}
                                            >
                                                HELP
                                            </div>
                                            <h1
                                                style={{
                                                    margin: '14px 0 0',
                                                    fontSize:
                                                        'clamp(32px,4.6vw,56px)',
                                                    lineHeight: '1.02',
                                                    fontWeight: '800',
                                                    letterSpacing: '-.04em',
                                                    animation:
                                                        'rz-pop .6s ease both .08s',
                                                }}
                                            >
                                                Every question, answered
                                                plainly.
                                            </h1>
                                            <p
                                                style={{
                                                    margin: '16px 0 0',
                                                    fontSize:
                                                        'clamp(15.5px,1.3vw,18px)',
                                                    lineHeight: '1.65',
                                                    color: '#69748a',
                                                    animation:
                                                        'rz-pop .6s ease both .16s',
                                                }}
                                            >
                                                If your question isn't here,
                                                email us. A person answers, not
                                                a robot.
                                            </p>
                                        </div>
                                    </div>
                                </section>

                                <section
                                    style={{
                                        maxWidth: '860px',
                                        margin: '0 auto',
                                        padding:
                                            'clamp(44px,6vw,72px) clamp(18px,4vw,44px) clamp(56px,8vw,100px)',
                                    }}
                                >
                                    {(groups ?? []).map(
                                        (g: any, gI: number) => (
                                            <Fragment key={gI}>
                                                <div
                                                    style={{
                                                        marginBottom:
                                                            'clamp(36px,5vw,52px)',
                                                    }}
                                                >
                                                    <div
                                                        style={{
                                                            display: 'flex',
                                                            alignItems:
                                                                'baseline',
                                                            gap: '13px',
                                                            margin: '0 0 12px',
                                                        }}
                                                    >
                                                        <span
                                                            aria-hidden="true"
                                                            style={{
                                                                fontSize:
                                                                    'clamp(30px,3.4vw,44px)',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.05em',
                                                                lineHeight:
                                                                    '.9',
                                                                color: 'transparent',
                                                                WebkitTextStroke:
                                                                    '1.3px rgba(12,24,48,.16)',
                                                            }}
                                                        >
                                                            {g.n}
                                                        </span>
                                                        <h2
                                                            style={{
                                                                margin: '0',
                                                                fontSize:
                                                                    'clamp(22px,2.6vw,30px)',
                                                                fontWeight:
                                                                    '800',
                                                                letterSpacing:
                                                                    '-.03em',
                                                            }}
                                                        >
                                                            {g.title}
                                                        </h2>
                                                    </div>
                                                    <div
                                                        style={{
                                                            display: 'flex',
                                                            flexDirection:
                                                                'column',
                                                            gap: '9px',
                                                        }}
                                                    >
                                                        {(g.items ?? []).map(
                                                            (
                                                                q: any,
                                                                qI: number,
                                                            ) => (
                                                                <Fragment
                                                                    key={qI}
                                                                >
                                                                    <div
                                                                        className="rz-glass"
                                                                        style={{
                                                                            borderRadius:
                                                                                '16px',
                                                                            overflow:
                                                                                'hidden',
                                                                            background:
                                                                                'rgba(255,255,255,.58)',
                                                                            backdropFilter:
                                                                                'blur(18px) saturate(1.45)',
                                                                            WebkitBackdropFilter:
                                                                                'blur(18px) saturate(1.45)',
                                                                            border: '1px solid rgba(12,24,48,.09)',
                                                                            boxShadow:
                                                                                '0 1px 2px rgba(12,24,48,.03), 0 20px 40px -24px rgba(12,24,48,.16)',
                                                                        }}
                                                                    >
                                                                        <button
                                                                            onClick={
                                                                                q.toggle
                                                                            }
                                                                            style={{
                                                                                width: '100%',
                                                                                padding:
                                                                                    '17px 20px',
                                                                                display:
                                                                                    'flex',
                                                                                alignItems:
                                                                                    'baseline',
                                                                                gap: '16px',
                                                                                background:
                                                                                    'none',
                                                                                border: 'none',
                                                                                cursor: 'pointer',
                                                                                textAlign:
                                                                                    'left',
                                                                                fontFamily:
                                                                                    'inherit',
                                                                            }}
                                                                        >
                                                                            <span
                                                                                style={{
                                                                                    flex: '1 1 auto',
                                                                                    fontSize:
                                                                                        'clamp(15.5px,1.4vw,17.5px)',
                                                                                    fontWeight:
                                                                                        '600',
                                                                                    letterSpacing:
                                                                                        '-.015em',
                                                                                    color: '#0c1830',
                                                                                }}
                                                                            >
                                                                                {
                                                                                    q.q
                                                                                }
                                                                            </span>
                                                                            <span
                                                                                style={{
                                                                                    flex: '0 0 auto',
                                                                                    width: '22px',
                                                                                    height: '22px',
                                                                                    borderRadius:
                                                                                        '7px',
                                                                                    background:
                                                                                        'rgba(30,58,255,.09)',
                                                                                    color: '#1e3aff',
                                                                                    display:
                                                                                        'flex',
                                                                                    alignItems:
                                                                                        'center',
                                                                                    justifyContent:
                                                                                        'center',
                                                                                    fontSize:
                                                                                        '14px',
                                                                                    fontWeight:
                                                                                        '700',
                                                                                    lineHeight:
                                                                                        '1',
                                                                                }}
                                                                            >
                                                                                {
                                                                                    q.mark
                                                                                }
                                                                            </span>
                                                                        </button>
                                                                        {q.open ? (
                                                                            <>
                                                                                <p
                                                                                    style={{
                                                                                        margin: '0',
                                                                                        padding:
                                                                                            '0 20px 19px',
                                                                                        maxWidth:
                                                                                            '640px',
                                                                                        fontSize:
                                                                                            '15px',
                                                                                        lineHeight:
                                                                                            '1.7',
                                                                                        color: '#69748a',
                                                                                        animation:
                                                                                            'rz-pop .3s ease both',
                                                                                    }}
                                                                                >
                                                                                    {
                                                                                        q.a
                                                                                    }
                                                                                </p>
                                                                            </>
                                                                        ) : null}
                                                                    </div>
                                                                </Fragment>
                                                            ),
                                                        )}
                                                    </div>
                                                </div>
                                            </Fragment>
                                        ),
                                    )}

                                    <div
                                        style={{
                                            borderRadius: '20px',
                                            background: '#1428a4',
                                            padding: 'clamp(24px,3vw,36px)',
                                            display: 'flex',
                                            flexWrap: 'wrap',
                                            gap: '20px',
                                            alignItems: 'center',
                                            justifyContent: 'space-between',
                                        }}
                                    >
                                        <div style={{ minWidth: '0' }}>
                                            <h3
                                                style={{
                                                    margin: '0',
                                                    fontSize:
                                                        'clamp(19px,2vw,24px)',
                                                    fontWeight: '800',
                                                    letterSpacing: '-.025em',
                                                    color: '#fff',
                                                }}
                                            >
                                                Still not sure?
                                            </h3>
                                            <p
                                                style={{
                                                    margin: '8px 0 0',
                                                    fontSize: '14.5px',
                                                    lineHeight: '1.6',
                                                    color: 'rgba(255,255,255,.65)',
                                                }}
                                            >
                                                Email us at{' '}
                                                <strong
                                                    style={{
                                                        color: 'rgb(255, 255, 255)',
                                                    }}
                                                >
                                                    hello@rozine.rw
                                                </strong>{' '}
                                                for more information.
                                            </p>
                                        </div>
                                        <a
                                            href="#calc"
                                            onClick={goInv}
                                            style={{
                                                flex: '0 0 auto',
                                                display: 'inline-flex',
                                                alignItems: 'center',
                                                gap: '9px',
                                                padding: '14px 26px',
                                                borderRadius: '999px',
                                                background: '#1e3aff',
                                                color: '#fff',
                                                fontSize: '15px',
                                                fontWeight: '600',
                                            }}
                                            style-hover="background:#4361ff; color:#fff"
                                        >
                                            Start investing{' '}
                                            <span style={{ opacity: '.7' }}>
                                                →
                                            </span>
                                        </a>
                                    </div>
                                </section>
                            </>
                        ) : null}

                        <footer
                            id="start"
                            data-rz-blue=""
                            style={{
                                position: 'relative',
                                marginTop: 'clamp(60px,9vw,120px)',
                                background: '#1428a4',
                            }}
                        >
                            <div
                                style={{
                                    position: 'relative',
                                    maxWidth: '1200px',
                                    margin: '0 auto',
                                    padding:
                                        '0 clamp(18px,4vw,44px) clamp(32px,4vw,48px)',
                                }}
                            >
                                <div
                                    style={{
                                        paddingTop: 'clamp(22px,3vw,32px)',
                                        borderTop:
                                            '1px solid rgba(255,255,255,.15)',
                                    }}
                                >
                                    <p
                                        style={{
                                            margin: '0',
                                            maxWidth: '820px',
                                            fontSize: '12px',
                                            lineHeight: '1.7',
                                            color: 'rgba(255,255,255,.45)',
                                        }}
                                    >
                                        Rozine connects investors and
                                        businesses; it is not a bank and does
                                        not hold your money; a licensed bank
                                        does. Lending involves risk: returns are
                                        not guaranteed and you can lose money.
                                        The safety fund is limited.
                                    </p>
                                    <div
                                        style={{
                                            marginTop: 'clamp(30px,4vw,52px)',
                                        }}
                                    >
                                        <div
                                            style={{
                                                display: 'flex',
                                                flexWrap: 'wrap',
                                                alignItems: 'center',
                                                gap: 'clamp(12px,2vw,26px)',
                                            }}
                                        >
                                            <div
                                                className="rz-sig"
                                                style={{
                                                    display: 'flex',
                                                    flexWrap: 'nowrap',
                                                    alignItems: 'center',
                                                    gap: 'clamp(6px,.8vw,12px)',
                                                    fontSize:
                                                        'clamp(15px,2vw,27px)',
                                                    lineHeight: '1.05',
                                                    fontWeight: '800',
                                                    letterSpacing: '-.045em',
                                                    color: '#fff',
                                                    flex: '0 1 auto',
                                                    minWidth: 'max-content',
                                                    whiteSpace: 'nowrap',
                                                }}
                                            >
                                                <span>From</span>
                                                <img
                                                    src="/site/img/65162fc2.png"
                                                    alt="rozine"
                                                    style={{
                                                        height: '.66em',
                                                        width: 'auto',
                                                        display: 'block',
                                                        position: 'relative',
                                                        top: '.055em',
                                                    }}
                                                />
                                                <span>Technologies</span>
                                                <span
                                                    aria-hidden="true"
                                                    className="rz-sig-dash"
                                                    style={{
                                                        width: 'clamp(14px,2.4vw,44px)',
                                                        minWidth: '14px',
                                                        height: '2px',
                                                        borderRadius: '2px',
                                                        background:
                                                            'linear-gradient(90deg, rgba(255,255,255,.55), rgba(255,255,255,.12))',
                                                        flex: '0 0 auto',
                                                    }}
                                                ></span>
                                                <span
                                                    style={{
                                                        color: 'rgba(255,255,255,.38)',
                                                    }}
                                                >
                                                    Kigali, Rwanda
                                                </span>
                                            </div>
                                            <div
                                                className="rz-social"
                                                style={{
                                                    display: 'flex',
                                                    flexWrap: 'nowrap',
                                                    gap: '8px',
                                                    flex: '0 0 auto',
                                                    marginLeft: 'auto',
                                                }}
                                            >
                                                <a
                                                    href="https://instagram.com/rozine.rw"
                                                    aria-label="Instagram"
                                                    title="Instagram"
                                                    style={{
                                                        width: '34px',
                                                        height: '34px',
                                                        borderRadius: '10px',
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent:
                                                            'center',
                                                        background:
                                                            'rgba(255,255,255,.1)',
                                                        border: '1px solid rgba(255,255,255,.18)',
                                                        color: 'rgba(255,255,255,.78)',
                                                        transition:
                                                            'background .22s ease, color .22s ease, transform .22s ease',
                                                    }}
                                                    style-hover="background:#ffffff; color:#1428a4; transform:translateY(-2px)"
                                                >
                                                    <svg
                                                        width="16"
                                                        height="16"
                                                        viewBox="0 0 24 24"
                                                        aria-hidden="true"
                                                    >
                                                        <path
                                                            d="M12 2.2c-2.7 0-3 0-4.1.06-1 .05-1.7.2-2.3.44a4.6 4.6 0 0 0-1.7 1.1 4.6 4.6 0 0 0-1.1 1.7c-.24.6-.4 1.3-.44 2.3C2.2 9 2.2 9.3 2.2 12s0 3 .06 4.1c.05 1 .2 1.7.44 2.3a4.6 4.6 0 0 0 1.1 1.7 4.6 4.6 0 0 0 1.7 1.1c.6.24 1.3.4 2.3.44 1.1.06 1.4.06 4.1.06s3 0 4.1-.06c1-.05 1.7-.2 2.3-.44a4.9 4.9 0 0 0 2.8-2.8c.24-.6.4-1.3.44-2.3.06-1.1.06-1.4.06-4.1s0-3-.06-4.1c-.05-1-.2-1.7-.44-2.3a4.6 4.6 0 0 0-1.1-1.7 4.6 4.6 0 0 0-1.7-1.1c-.6-.24-1.3-.4-2.3-.44C15 2.2 14.7 2.2 12 2.2Zm0 1.8c2.7 0 2.9 0 4 .05.8.04 1.2.17 1.5.29.4.15.7.33 1 .63.3.3.48.6.63 1 .12.3.25.7.29 1.5.05 1.1.05 1.3.05 4s0 2.9-.05 4c-.4.8-.17 1.2-.29 1.5a2.9 2.9 0 0 1-1.63 1.63c-.3.12-.7.25-1.5.29-1.1.05-1.3.05-4 .05s-2.9 0-4-.05c-.8-.04-1.2-.17-1.5-.29a2.9 2.9 0 0 1-1.63-1.63c-.12-.3-.25-.7-.29-1.5C4.02 14.9 4 14.7 4 12s.02-2.9.07-4c.04-.8.17-1.2.29-1.5.15-.4.33-.7.63-1 .3-.3.6-.48 1-.63.3-.12.7-.25 1.5-.29C8.6 4.02 8.8 4 12 4Zm0 3.1a4.9 4.9 0 1 0 0 9.8 4.9 4.9 0 0 0 0-9.8Zm0 8a3.1 3.1 0 1 1 0-6.2 3.1 3.1 0 0 1 0 6.2Zm6.2-8.2a1.15 1.15 0 1 1-2.3 0 1.15 1.15 0 0 1 2.3 0Z"
                                                            fill="currentColor"
                                                        />
                                                    </svg>
                                                </a>
                                                <a
                                                    href="https://x.com/rozine.rw"
                                                    aria-label="X"
                                                    title="X"
                                                    style={{
                                                        width: '34px',
                                                        height: '34px',
                                                        borderRadius: '10px',
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent:
                                                            'center',
                                                        background:
                                                            'rgba(255,255,255,.1)',
                                                        border: '1px solid rgba(255,255,255,.18)',
                                                        color: 'rgba(255,255,255,.78)',
                                                        transition:
                                                            'background .22s ease, color .22s ease, transform .22s ease',
                                                    }}
                                                    style-hover="background:#ffffff; color:#1428a4; transform:translateY(-2px)"
                                                >
                                                    <svg
                                                        width="16"
                                                        height="16"
                                                        viewBox="0 0 24 24"
                                                        aria-hidden="true"
                                                    >
                                                        <path
                                                            d="M17.5 3h3.3l-7.2 8.2L22 21h-6.3l-4.4-5.7L6.1 21H2.8l7.6-8.7L2.5 3h6.4l4.1 5.4L17.5 3Zm-1.2 16h1.8L7.6 4.9H5.7L16.3 19Z"
                                                            fill="currentColor"
                                                        />
                                                    </svg>
                                                </a>
                                                <a
                                                    href="https://www.linkedin.com/company/rozine"
                                                    aria-label="LinkedIn"
                                                    title="LinkedIn"
                                                    style={{
                                                        width: '34px',
                                                        height: '34px',
                                                        borderRadius: '10px',
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent:
                                                            'center',
                                                        background:
                                                            'rgba(255,255,255,.1)',
                                                        border: '1px solid rgba(255,255,255,.18)',
                                                        color: 'rgba(255,255,255,.78)',
                                                        transition:
                                                            'background .22s ease, color .22s ease, transform .22s ease',
                                                    }}
                                                    style-hover="background:#ffffff; color:#1428a4; transform:translateY(-2px)"
                                                >
                                                    <svg
                                                        width="16"
                                                        height="16"
                                                        viewBox="0 0 24 24"
                                                        aria-hidden="true"
                                                    >
                                                        <path
                                                            d="M4.98 3.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5ZM3 9.5h4v11H3v-11Zm6.5 0h3.8v1.5h.05a4.2 4.2 0 0 1 3.75-2c4 0 4.7 2.5 4.7 5.9v5.6h-4v-5c0-1.2 0-2.8-1.8-2.8-1.75 0-2 1.35-2 2.7v5.1h-4v-11Z"
                                                            fill="currentColor"
                                                        />
                                                    </svg>
                                                </a>
                                                <a
                                                    href="mailto:hello@rozine.rw"
                                                    aria-label="Email"
                                                    title="Email"
                                                    style={{
                                                        width: '34px',
                                                        height: '34px',
                                                        borderRadius: '10px',
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent:
                                                            'center',
                                                        background:
                                                            'rgba(255,255,255,.1)',
                                                        border: '1px solid rgba(255,255,255,.18)',
                                                        color: 'rgba(255,255,255,.78)',
                                                        transition:
                                                            'background .22s ease, color .22s ease, transform .22s ease',
                                                    }}
                                                    style-hover="background:#ffffff; color:#1428a4; transform:translateY(-2px)"
                                                >
                                                    <svg
                                                        width="16"
                                                        height="16"
                                                        viewBox="0 0 24 24"
                                                        aria-hidden="true"
                                                    >
                                                        <path
                                                            d="M3 5.5h18a1 1 0 0 1 1 1v11a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1v-11a1 1 0 0 1 1-1Zm.9 1.8L12 13l8.1-5.7H3.9ZM4 9.2v7.3h16V9.2l-8 5.6-8-5.6Z"
                                                            fill="currentColor"
                                                        />
                                                    </svg>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </footer>
                    </div>
                </div>
            </>
        );
    }
}
