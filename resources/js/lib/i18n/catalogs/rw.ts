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

    'business.auth.wordmark': 'rozine',
    'business.auth.for_business': 'Ku bucuruzi',
    'business.auth.rdb_verified': 'Byemejwe na RDB',
    'business.auth.secure': 'Bifite umutekano',
    'business.auth.please_wait': 'Tegereza gato…',
    'business.auth.login.head_title': "Kwinjira k'ubucuruzi",
    'business.auth.login.title': 'Murakaza neza nanone',
    'business.auth.login.subtitle':
        "Injira mu kibaho cyawe cy'utanga impapuro.",
    'business.auth.login.email_label': "Imeyili y'akazi",
    'business.auth.login.email_placeholder': 'wowe@ikigo.rw',
    'business.auth.login.password_label': 'Ijambobanga',
    'business.auth.login.password_placeholder': '••••••••',
    'business.auth.login.submit': 'Injira',
    'business.auth.login.switch_prompt': 'Uri mushya kuri Rozine?',
    'business.auth.login.switch_action': 'Andikisha ubucuruzi',
    'business.auth.register.head_title': 'Andikisha ubucuruzi bwawe',
    'business.auth.register.title': 'Andikisha ubucuruzi bwawe',
    'business.auth.register.subtitle':
        'Emeza ikigo cyawe binyuze ku Rwanda Development Board.',
    'business.auth.register.code_label': "Kode y'ikigo ya RDB",
    'business.auth.register.code_placeholder': '102938475',
    'business.auth.register.rdb_note':
        'Twemeza ikigo cyawe mu buryo butaziguye kuri Rwanda Development Board — nta kwinjira kwawe bwite bisaba.',
    'business.auth.register.matched': '✓ Ikigo cyabonetse muri RDB',
    'business.auth.register.name_label': "Izina ry'ubucuruzi ryanditswe",
    'business.auth.register.name_placeholder': 'urugero: Karongi Freight Ltd',
    'business.auth.register.code_sent':
        "Andika kode y'imibare 6 yoherejwe kuri telefone na imeyili byanditswe kuri ubu bucuruzi muri RDB.",
    'business.auth.register.otp_label': 'Kode ikoreshwa rimwe',
    'business.auth.register.otp_placeholder': "Kode y'imibare 6",
    'business.auth.register.resend': 'Ohereza kode nanone',
    'business.auth.register.verify_company': "Emeza kode y'ikigo",
    'business.auth.register.verifying_company': 'Birimo kwemezwa na RDB…',
    'business.auth.register.verify_code': 'Emeza ukomeze',
    'business.auth.register.verifying_code': 'Kode irimo kwemezwa…',
    'business.auth.register.switch_prompt': 'Wamaze kwiyandikisha?',
    'business.auth.register.switch_action': 'Injira',

    'common.currency.rwf': 'RWF',
    'app.nav.label': 'Kugenda muri porogaramu',
    'app.nav.launcher': 'Aho porogaramu ziri',
    'business.nav.home': 'Ahabanza',
    'business.nav.reports': 'Raporo',
    'business.nav.profile': 'Umwirondoro',
    'business.home.head_title': 'Ahabanza',
    'business.home.wallet_balance': 'Amafaranga ari mu gikapu',
    'business.home.deposit': 'Shyiramo',
    'business.home.withdraw': 'Bikuza',
    'business.home.notifications': 'Ubutumwa',
    'business.home.notifications_unread': 'Ubutumwa, {count} butarasomwa',
    'business.home.company_code': 'RDB {code}',
    'business.home.rozine_rating': 'Amanota ya Rozine',
    'business.rating.pending': 'Igenzura ritegerejwe',
    'business.rating.band.strong': 'Ikomeye',
    'business.rating.band.stable': 'Ihamye',
    'business.rating.band.weak': 'Idakomeye',
    'business.rating.band.distressed': 'Iri mu bibazo',
    'business.home.raising_now': 'Irimo gushakirwa igishoro',
    'business.home.funded_progress': 'Aho ishoramari rya {title} rigeze',
    'business.home.raised_of': '{raised} kuri {target}',
    'business.home.investors_link': 'Abashoramari {count} ›',
    'business.today.title': 'Uyu munsi',
    'business.today.subtitle': 'Ibikeneye ko ubyitaho ubu.',
    'business.today.approved.kicker': 'Ubusabe bwemejwe',
    'business.today.approved.sub':
        "Ishyura amafaranga y'ubusabe {fee} kugira ngo bugezwe ku bashoramari.",
    'business.today.approved.cta': 'Ishyura utangaze',
    'business.today.declined.kicker': 'Ubusabe bwanzwe',
    'business.today.declined.sub':
        'Birenze ubushobozi bwawe bwemejwe — tuvugishe mbere yo kongera gusaba.',
    'business.today.declined.cta': 'Reba impamvu',
    'business.today.disbursement.kicker': 'Igishoro cyuzuye',
    'business.today.disbursement.cta': 'Emeza kohereza amafaranga',
    'business.today.audit.kicker': "Igihe cy'igenzura",
    'business.today.audit.title': 'Igenzura rya {month}',
    'business.today.audit.sub_open':
        'CPA wawe azaza ukwezi kurangiye · rizashyirwaho kashe bitarenze {sealed}',
    'business.today.audit.sub_closing':
        'Ukwezi kurarangira mu minsi {days} · rizashyirwaho kashe bitarenze {sealed}',
    'business.today.audit.cta': 'Itegure',
    'business.today.repayment.kicker': 'Kwishyura gutaha',
    'business.today.repayment.sub': '{note} · Bigomba kwishyurwa ku wa {date}',
    'business.today.repayment.cta': 'Ishyura ubu',
    'business.today.caught_up.title': 'Byose biri ku gihe',
    'business.today.caught_up.body':
        'Nta kwishyura cyangwa igikorwa gitegerejwe. Tuzakumenyesha ikintu nigikenera kwitabwaho.',
    'business.capital.title': 'Igishoro cyawe',
    'business.capital.subtitle': 'Amateka yawe yose, ku mpapuro zawe zose.',
    'business.capital.active_notes': '{count} zikora',
    'business.capital.about': 'Ibyerekeye {label}',
    'business.capital.close': 'Funga',
    'business.capital.raised.label': 'Byakusanyijwe',
    'business.capital.raised.about':
        'Igishoro cyose wakuye ku bashoramari ku mpapuro zose watanze kuri Rozine.',
    'business.capital.investors.label': 'Abashoramari',
    'business.capital.investors.about':
        'Abashoramari batandukanye bashyigikiye impapuro zawe. Uwashoye muri nyinshi abarwa rimwe.',
    'business.capital.repaid.label': 'Byishyuwe',
    'business.capital.repaid.about':
        'Inyungu zose wishyuye abashoramari ku mpapuro zawe zose kugeza ubu.',
    'business.capital.on_time.label': 'Ku gihe',
    'business.capital.on_time.about':
        "Igipimo cy'ubwishyu bwakozwe ku gihe cyangwa mbere yacyo, kuva watangira.",
    'business.note.title': 'Impapuro zawe',
    'business.note.filter_label': 'Tondeka impapuro ukurikije uko zihagaze',
    'business.note.filter.draft': 'Umushinga',
    'business.note.filter.active': 'Irakora',
    'business.note.filter.repaying': 'Irishyurwa',
    'business.note.filter.failed': 'Yananiranye',
    'business.note.filter.archived': 'Yabitswe',
    'business.note.status.draft': 'Umushinga',
    'business.note.status.active': 'Irakora',
    'business.note.status.funded': 'Yuzuye',
    'business.note.status.repaying': 'Irishyurwa',
    'business.note.status.completed': 'Yarangiye',
    'business.note.status.failed': 'Yananiranye',
    'business.note.funded': 'byakusanyijwe',
    'business.note.investors': 'Abashoramari {count}',
    'business.note.continue_application': 'Komeza ubusabe',
    'business.note.empty.title': 'Nta rupapuro ruri muri iki cyiciro',
    'business.note.empty.body': "Tangira gushaka igishoro cy'ubucuruzi bwawe.",
    'business.grow.title': 'Kwaguka',
    'business.grow.subtitle': 'Shaka igishoro kinini igihe witeguye.',
    'business.grow.headroom': 'Ubushobozi busigaye',
    'business.grow.headroom_body':
        'Ushobora gushaka aya mafaranga yiyongera ubu.',
    'business.grow.apply': 'Saba igishoro',

    'auth.two_factor.recovery_codes_remaining': {
        one: 'Hasigaye kode {count} yo kugarura konti',
        other: 'Hasigaye kode {count} zo kugarura konti',
    },
};

export default rw;
