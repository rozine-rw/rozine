/**
 * Kinyarwanda catalog.
 *
 * REVIEW REQUIRED. Structurally complete against `en`, but this copy is engineering-supplied and has
 * NOT been verified by a native Kinyarwanda speaker. It exists so the catalog shape, the missing-key
 * lint, and the plural machinery are exercised against a third locale. No locale may be activated
 * under D-07 until a named reviewer signs this file off.
 */
import type { Catalog } from '@/lib/i18n/types';

const rw: Catalog = {
    'identity.home.investor': 'Ahagenewe umushoramari',
    'identity.home.business': 'Ahagenewe ubucuruzi',
    'identity.home.auditor': 'Ahagenewe umugenzuzi',
    'identity.home.admin': 'Ahagenewe abakozi',
    'identity.home.overview': 'Incamake',
    'identity.home.access': 'Uburenganzira bwa konti',
    'identity.home.ready': 'Konti yawe yemerewe kwinjira hano.',
    'identity.home.verified': 'Umwirondoro wawe waremejwe.',
    'identity.home.staff_ready': 'Konti yawe y’umukozi yemerewe kwinjira hano.',
    'identity.home.back': 'Hitamo porogaramu',
    'identity.home.settings': 'Igenamiterere rya konti',
    'identity.home.saving': 'Kubika aho ugeze…',
    'identity.home.failed':
        'Ntitwashoboye kubika aho ugeze. Subira aho uhitamo porogaramu urebe uburenganzira bwawe.',
    'identity.denied.title': 'Uburenganzira bugomba kugenzurwa',
    'identity.denied.body':
        'Konti yawe nticyemerewe gufungura uru rupapuro mu nshingano wahisemo. Hitamo porogaramu wongere kugenzura uburenganzira bwawe.',
    'environment.demo': 'Demo — si urubuga nyarwo',
    'environment.uat': 'UAT — si urubuga nyarwo',
    'environment.synthetic_only':
        'Koresha amakuru y’impimbano gusa. Nta guhererekanya amafaranga nyayo.',
    'common.brand.name': 'Rozine',
    'common.action.log_in': 'Injira',
    'common.action.sign_up': 'Iyandikishe',

    'auth.login.head_title': 'Injira',
    'auth.login.layout_title': 'Injira muri konti yawe',
    'auth.login.layout_description':
        'Andika imeyili n’ijambobanga ryawe kugira ngo winjire',
    'auth.login.email_label': 'Aderesi imeyili',
    'auth.login.email_placeholder': 'imeyili@urugero.com',
    'auth.login.password_label': 'Ijambobanga',
    'auth.login.password_placeholder': 'Ijambobanga',
    'auth.login.forgot_password': 'Wibagiwe ijambobanga?',
    'auth.login.remember_me': 'Unyibuke',
    'auth.login.no_account': 'Nta konti ufite?',

    'auth.forgot_password.head_title': 'Wibagiwe ijambobanga',
    'auth.forgot_password.layout_title': 'Wibagiwe ijambobanga',
    'auth.forgot_password.layout_description':
        'Andika imeyili yawe kugira ngo woherezwe umurongo wo guhindura ijambobanga',
    'auth.forgot_password.email_label': 'Aderesi imeyili',
    'auth.forgot_password.email_placeholder': 'imeyili@urugero.com',
    'auth.forgot_password.submit': 'Ohereza umurongo wo guhindura ijambobanga',
    'auth.forgot_password.return_prefix': 'Cyangwa subira kuri',
    'auth.forgot_password.return_link': 'kwinjira',

    'auth.two_factor.recovery_codes_remaining': {
        one: 'Hasigaye kode {count} yo kugarura konti',
        other: 'Hasigaye kode {count} zo kugarura konti',
    },
};

export default rw;
