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

    'suite.head_title': 'Porogaramu zawe',
    'suite.tagline':
        "Urwego rw'amasoko y'imari y'abantu bose ku bukungu buri kuzamuka",
    'suite.motto': 'Intangiriro imwe · ahantu hose',
    'suite.section.apps': 'Porogaramu zawe',
    'suite.app.open': 'Fungura porogaramu →',
    'suite.app.investor.title': 'Umushoramari',
    'suite.app.investor.description':
        'Menya ubucuruzi bwagenzuwe, shora imari, ukurikirane inyungu.',
    'suite.app.business.title': 'Ubucuruzi',
    'suite.app.business.description':
        'Shaka igishoro, uhe abashoramari raporo buri kwezi.',
    'suite.app.auditor.title': 'Umugenzuzi',
    'suite.app.auditor.description':
        'Genzura aho ubucuruzi bukorera, genzura raporo, ubone inyungu.',
    'suite.blocker.action.verify_email': 'Emeza imeyili yawe →',
    'suite.blocker.action.contact': "Vugana n'ubufasha bwa Rozine →",
    'suite.blocker.EMAIL_VERIFICATION_REQUIRED.title':
        'Banza wemeze imeyili yawe',
    'suite.blocker.EMAIL_VERIFICATION_REQUIRED.body':
        'Kanda ku murongo twakoherereje, hanyuma porogaramu zawe zigaragare hano.',
    'suite.blocker.IDENTITY_NOT_LINKED.title':
        'Umwirondoro wawe ntabwo urategurwa',
    'suite.blocker.IDENTITY_NOT_LINKED.body':
        "Konti yawe ntirahuzwa n'umwirondoro wagenzuwe, bityo nta porogaramu irafunguka.",
    'suite.blocker.IDENTITY_VERIFICATION_REQUIRED.title':
        'Umwirondoro wawe uri kugenzurwa',
    'suite.blocker.IDENTITY_VERIFICATION_REQUIRED.body':
        'Porogaramu zawe zizafunguka igenzura ry’umwirondoro rirangiye.',
    'suite.blocker.PARTY_AUTHORITY_REQUIRED.title':
        'Hakenewe ububasha bwo gusinya',
    'suite.blocker.PARTY_AUTHORITY_REQUIRED.body':
        "Iyi konti y'ikigo ikeneye umusinyi wagenzuwe mbere y'uko porogaramu ifunguka.",
    'suite.blocker.ROLE_MEMBERSHIP_INVALID.title':
        'Konti yawe ikeneye kwitabwaho',
    'suite.blocker.ROLE_MEMBERSHIP_INVALID.body':
        'Kimwe mu byo wiyandikishijemo nticyasomwe. Ubufasha bushobora kubikosora.',
    'suite.blocker.ROLE_MEMBERSHIP_CONFLICT.title':
        'Izi porogaramu ntizishobora guhurizwa hamwe',
    'suite.blocker.ROLE_MEMBERSHIP_CONFLICT.body':
        'Umufatanyabikorwa mu igenzura ntashobora no gushora cyangwa gushaka igishoro kuri Rozine. Ubufasha buzagufasha guhitamo.',
    'suite.blocker.ROLE_MEMBERSHIP_REQUIRED.title': 'Nta porogaramu uragira',
    'suite.blocker.ROLE_MEMBERSHIP_REQUIRED.body':
        'Ntabwo urinjira muri porogaramu n’imwe. Ubufasha bushobora kugutegurira iyo ukeneye.',

    'auth.two_factor.recovery_codes_remaining': {
        one: 'Hasigaye kode {count} yo kugarura konti',
        other: 'Hasigaye kode {count} zo kugarura konti',
    },
};

export default rw;
