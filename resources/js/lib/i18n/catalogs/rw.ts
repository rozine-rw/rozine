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
    'suite.sign_out': 'Sohoka',
    'site.app_entry.label': 'Porogaramu za Rozine',
    'site.app_entry.sign_in': 'Injira',
    'site.app_entry.create_account': 'Fungura konti',
    'site.app_entry.open_app': 'Fungura porogaramu',
    'suite.preview': 'Igerageza rikoresha amakuru y’icyitegererezo',
    'suite.mfa_required':
        'Shyiraho kwemeza kwinjira mu buryo bubiri kugira ngo ufungure porogaramu y’umugenzuzi.',
    'suite.mfa_setup': 'Shyiraho kwemeza kwinjira mu buryo bubiri',
    'suite.network_error':
        'Ntitwashoboye kwemeza guhindura inshingano. Reba umurongo wa interineti wongere ugerageze.',
    'suite.command_conflict':
        'Ubu busabe ntibushobora kongera gukoreshwa. Ongera ugenzure uburenganzira bwawe mbere yo guhitamo porogaramu.',
    'suite.access_changed':
        'Uburenganzira bwawe cyangwa porogaramu ukoresha byahindutse. Ongera ubigenzure, hanyuma uhitemo porogaramu.',
    'suite.retry': 'Ongera uhindure inshingano',
    'suite.refresh_access': 'Ongera ugenzure uburenganzira',
    'suite.admin': 'Fungura ahagenewe abakozi',
    'suite.opening': 'Gufungura porogaramu yawe…',
    'identity.home.back': 'Hitamo porogaramu',
    'identity.denied.title': 'Uburenganzira bugomba kugenzurwa',
    'identity.denied.body':
        'Konti yawe nticyemerewe gufungura uru rupapuro mu nshingano wahisemo. Hitamo porogaramu wongere kugenzura uburenganzira bwawe.',
    'identity.denied.verification_required.title':
        'Igenzura ry’umwirondoro rirakenewe',
    'identity.denied.verification_required.body':
        'Umwirondoro wawe ugomba kugenzurwa mbere yo gufungura uru rupapuro. Hitamo porogaramu kugira ngo ukomeze.',
    'identity.denied.verification_required.action': 'Emeza umwirondoro wawe',
    'identity.denied.expired_offer.title': 'Iki cyifuzo cyarafunzwe',
    'identity.denied.expired_offer.body':
        'Igihe cyo kwemera uyu murimo cyarangiye, ntukiwufunguriwe. Hitamo porogaramu urebe imirimo yawe iriho.',
    'errors.not_found.head_title': 'Ntibibonetse',
    'errors.not_found.title': 'Ntitwabonye uru rupapuro',
    'errors.not_found.body':
        'Birashoboka ko ihuza ryanditswe nabi, cyangwa icyo ryerekezagaho kitakiboneka. Reba ihuza, cyangwa wongere utangirire kuri Rozine.',
    'errors.not_found.home': 'Jya ku rupapuro rw’ibanze rwa Rozine',
    'errors.unavailable.head_title': 'Ntibiboneka by’agateganyo',
    'errors.unavailable.title': 'Rozine ntiyashoboye gufungura iyi paji',
    'errors.unavailable.body':
        'Habaye ikibazo ku ruhande rwacu. Ibyo wamaze kohereza ntibyagizweho ingaruka. Tegereza gato, hanyuma wongere ugerageze.',
    'errors.unavailable.retry': 'Ongera ugerageze',
    'environment.demo': 'Demo — si urubuga nyarwo',
    'environment.uat': 'UAT — si urubuga nyarwo',
    'environment.synthetic_only':
        'Koresha amakuru y’impimbano gusa. Nta guhererekanya amafaranga nyayo.',
    'common.brand.name': 'Rozine',
    'common.action.log_in': 'Injira',
    'common.action.sign_up': 'Iyandikishe',

    'auth.login.head_title': 'Injira',
    'auth.login.layout_title': 'Murakaza neza nanone',
    'auth.login.layout_description': 'Injira muri konti yawe ya Rozine.',
    'auth.login.email_label': 'Aderesi imeyili',
    'auth.login.email_placeholder': 'imeyili@urugero.com',
    'auth.login.password_label': 'Ijambobanga',
    'auth.login.password_placeholder': 'Ijambobanga',
    'auth.login.forgot_password': 'Wibagiwe ijambobanga?',
    'auth.login.remember_me': 'Unyibuke',
    'auth.login.no_account': 'Uri mushya kuri Rozine?',

    'auth.forgot_password.head_title': 'Wibagiwe ijambobanga',
    'auth.forgot_password.layout_title': 'Wibagiwe ijambobanga',
    'auth.forgot_password.layout_description':
        'Andika imeyili yawe kugira ngo woherezwe umurongo wo guhindura ijambobanga',
    'auth.forgot_password.email_label': 'Aderesi imeyili',
    'auth.forgot_password.email_placeholder': 'imeyili@urugero.com',
    'auth.forgot_password.submit': 'Ohereza umurongo wo guhindura ijambobanga',
    'auth.forgot_password.return_prefix': 'Cyangwa subira kuri',
    'auth.forgot_password.return_link': 'kwinjira',
    'auth.login.create_account': 'Fungura konti',
    'auth.register.head_title': 'Iyandikishe',
    'auth.register.layout_title': 'Fungura konti yawe',
    'auth.register.layout_description':
        'Andika amakuru yawe hepfo kugira ngo ufungure konti.',
    'auth.register.name_label': 'Amazina',
    'auth.register.name_placeholder': 'Amazina yose',
    'auth.register.email_label': 'Aderesi imeyili',
    'auth.register.email_placeholder': 'wowe@urugero.rw',
    'auth.register.password_label': 'Ijambobanga',
    'auth.register.password_placeholder': 'Ijambobanga',
    'auth.register.confirm_label': 'Emeza ijambobanga',
    'auth.register.confirm_placeholder': 'Emeza ijambobanga',
    'auth.register.submit': 'Fungura konti',
    'auth.register.have_account': 'Usanzwe ufite konti?',
    'auth.register.log_in': 'Injira',
    'auth.brand.wordmark': 'rozine',
    'auth.brand.secure': 'Bitekanye',
    'auth.brand.business.have_account': 'Waramaze kwiyandikisha?',
    'auth.brand.business.eyebrow': 'Ku bigo by’ubucuruzi',
    'auth.brand.business.login_description':
        'Injira mu mbonerahamwe y’ikigo cyawe.',
    'auth.brand.business.email_label': 'Imeyili y’akazi',
    'auth.brand.business.email_placeholder': 'wowe@ikigo.rw',
    'auth.brand.business.register_cta': 'Andikisha ikigo',
    'auth.brand.business.register_title': 'Andikisha ikigo cyawe',
    'auth.brand.business.register_description':
        'Fungura konti ikigo cyawe kizakoresha gusaba no gukusanya amafaranga.',
    'auth.brand.investor.login_description': 'Injira ucunge ishoramari ryawe.',
    'auth.brand.investor.register_description':
        'Tangira gushora imari mu bigo byagenzuwe mu Rwanda hose.',
    'auth.brand.auditor.eyebrow': 'Ku bafatanyabikorwa b’igenzura',
    'auth.brand.auditor.login_description': 'Injira mu biro byawe by’igenzura.',
    'auth.brand.auditor.register_description':
        'Fungura konti uzakoresha wakira imirimo y’igenzura.',
    'auth.reset_password.head_title': 'Hindura ijambobanga',
    'auth.reset_password.layout_title': 'Hindura ijambobanga',
    'auth.reset_password.layout_description':
        'Hitamo ijambobanga rishya rya konti yawe.',
    'auth.reset_password.email_label': 'Imeyili',
    'auth.reset_password.password_label': 'Ijambobanga',
    'auth.reset_password.password_placeholder': 'Ijambobanga',
    'auth.reset_password.confirm_label': 'Emeza ijambobanga',
    'auth.reset_password.confirm_placeholder': 'Emeza ijambobanga',
    'auth.reset_password.submit': 'Hindura ijambobanga',
    'auth.verify_email.head_title': 'Kwemeza imeyili',
    'auth.verify_email.layout_title': 'Emeza imeyili yawe',
    'auth.verify_email.layout_description':
        'Andika kode y’imibare 6 twakoherereje kuri imeyili. Irangira mu minota 10.',
    'auth.verify_email.resent':
        'Kode nshya yoherejwe kuri imeyili wiyandikishijeho. Kode za mbere ntizigikora.',
    'auth.verify_email.submit': 'Emeza imeyili',
    'auth.verify_email.resend': 'Ohereza kode nshya',
    'auth.verify_email.log_out': 'Sohoka',
    'auth.confirm_password.head_title': 'Emeza ijambobanga',
    'auth.confirm_password.layout_title': 'Emeza ijambobanga',
    'auth.confirm_password.layout_description':
        'Aha ni ahantu harinzwe. Emeza ijambobanga ryawe mbere yo gukomeza.',
    'auth.confirm_password.passkey': 'Emeza ukoresheje passkey',
    'auth.confirm_password.passkey_loading': 'Biremezwa…',
    'auth.confirm_password.separator': 'Cyangwa wemeze ukoresheje ijambobanga',
    'auth.confirm_password.password_label': 'Ijambobanga',
    'auth.confirm_password.password_placeholder': 'Ijambobanga',
    'auth.confirm_password.submit': 'Emeza ijambobanga',
    'auth.two_factor_challenge.head_title': 'Kwemeza mu byiciro bibiri',
    'auth.two_factor_challenge.code_title': 'Kode yo kwemeza',
    'auth.two_factor_challenge.code_description':
        'Andika kode yatanzwe na porogaramu yawe yo kwemeza.',
    'auth.two_factor_challenge.recovery_title': 'Kode yo kugarura',
    'auth.two_factor_challenge.recovery_description':
        'Emeza ko ufite uburenganzira kuri konti yawe wandika imwe muri kode zawe zo kugarura.',
    'auth.two_factor_challenge.recovery_placeholder': 'Andika kode yo kugarura',
    'auth.two_factor_challenge.submit': 'Komeza',
    'auth.two_factor_challenge.toggle_prefix': 'cyangwa ushobora',
    'auth.two_factor_challenge.use_recovery':
        'kwinjira ukoresheje kode yo kugarura',
    'auth.two_factor_challenge.use_code': 'kwinjira ukoresheje kode yo kwemeza',

    'suite.head_title': 'Porogaramu zawe',
    'suite.tagline':
        "Urwego rw'amasoko y'imari y'abantu bose ku bukungu buri kuzamuka",
    'suite.motto': 'Intangiriro imwe · ahantu hose',
    'suite.section.apps': 'Porogaramu zawe',
    'suite.app.open': 'Fungura porogaramu →',
    'suite.app.browse': 'Reba amahirwe →',
    'suite.app.request_access': 'Saba uburenganzira →',
    'suite.app.investor.preview':
        'Reba amahirwe afunguye ubu. Uzashobora gushora umwirondoro wawe umaze kwemezwa.',
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
    'suite.blocker.action.verify_identity': 'Emeza umwirondoro wawe →',
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
        'Witeguye gushora imari?',
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
    'app.read_failure.server':
        'Rozine ntiyashoboye gufungura iyi paji ubu. Ibyo wamaze kohereza ntibyagizweho ingaruka.',
    'app.read_failure.network':
        'Ntibyashobotse kugera kuri Rozine. Reba umurandasi wawe, hanyuma wongere ugerageze.',
    'app.read_failure.retry': 'Ongera ugerageze',
    'app.connectivity.offline':
        'Nta murandasi ufite. Ibyo ubona bishobora kuba bitagezweho, kandi nta kintu gishobora koherezwa kugeza ongeye kubona umurandasi.',
    'business.nav.home': 'Ahabanza',
    'business.nav.reports': 'Raporo',
    'business.nav.market': 'Isoko',
    'business.nav.profile': 'Umwirondoro',
    'business.market.title': "Incamake y'isoko",
    'business.market.subtitle': 'Uko noti zawe zihagaze ku isoko rya kabiri.',
    'business.market.tile.demand': 'UBUSHAKE',
    'business.market.tile.price': "IGICIRO CY'IMPUZANDENGO",
    'business.market.tile.volume': 'INGANO (IMINSI 7)',
    'business.market.tile.liquidity': "AMANOTA Y'UBWINSHI BW'AMAFARANGA",
    'business.market.chart': 'Igiciro ku isoko rya kabiri',
    'business.market.range': "Igihe cy'igiciro",
    'business.market.range_7d': 'IM 7',
    'business.market.range_14d': 'IM 14',
    'business.market.range_30d': 'IM 30',
    'business.market.chart_empty':
        'Nta bucuruzi bwo ku isoko rya kabiri burabaho.',
    'business.market.high': 'HEJURU CYANE',
    'business.market.low': 'HASI CYANE',
    'business.market.notes': 'Noti zawe ku isoko',
    'business.market.notes_empty_title': 'Nta noti yawe iracuruzwa',
    'business.market.notes_empty_body':
        "Noti abashoramari bagurisha mbere y'igihe zizagaragara hano n'igiciro cyazo.",
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
    'business.today.approved.sub_free':
        'Yitangaze ku bashoramari. Nta mafaranga yo gutangaza asabwa.',
    'business.today.approved.cta_free': 'Tangaza',
    'business.today.cosign.kicker': 'Raporo y’igenzura yiteguye',
    'business.today.cosign.sub':
        'CPA wawe yayifunze. Soma ibyabonetse mbere yo gushyiraho umukono.',
    'business.today.cosign.cta': 'Yisuzume kandi uyishyireho umukono',
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

    'business.apply.head_title': 'Saba igishoro',
    'business.apply.label': "Ubusabe bw'igishoro",
    'business.apply.close': 'Funga',
    'business.apply.back': 'Subira inyuma',
    'business.apply.progress': 'Aho ubusabe bugeze',
    'business.apply.step_of': 'Intambwe ya {step} kuri {total}',
    'business.apply.continue': 'Komeza',
    'business.apply.saving': 'Birabikwa…',
    'business.apply.submit': 'Sinya ubusabe',
    'business.apply.submitting': 'Birimo gusinywa…',
    'business.apply.incomplete': 'Banza urangize iyi ntambwe',
    'business.apply.business.title': "Ubucuruzi n'imari",
    'business.apply.business.subtitle':
        "Bivuye ku cyemezo cyawe cya RDB n'inyandiko za banki na mobile money zagenzuwe. Bisuzume — niba ari byo, komeza.",
    'business.apply.business.rdb_verified': '✓ Byemejwe na RDB',
    'business.apply.business.statements_verified': '✓ Inyandiko zemejwe',
    'business.apply.business.active': '● Irakora',
    'business.apply.business.established': 'Yashinzwe {year}',
    'business.apply.business.standing': 'Uko imari ihagaze',
    'business.apply.business.statements_badge': 'Inyandiko zemejwe',
    'business.apply.business.period': {
        one: '{from} – {through} · ukwezi {count}',
        other: '{from} – {through} · amezi {count}',
    },
    'business.apply.business.partial_year': {
        one: '{year} · ukwezi {count}',
        other: '{year} · amezi {count}',
    },
    'business.apply.business.revenue': 'Amafaranga yinjiye',
    'business.apply.business.costs': 'Ibyakoreshejwe',
    'business.apply.business.net_profit': 'Amafaranga asigara mu bikorwa',
    'business.apply.business.existing_debt': 'Umwenda usanzwe',
    'business.apply.business.crb_verified': '✓ Byemejwe na CRB',
    'business.apply.business.year_by_year': 'Umwaka ku wundi',
    'business.apply.business.capacity': 'Ubushobozi bwemejwe',
    'business.apply.business.capacity_basis': '· amafaranga × ububiko',
    'business.apply.business.capacity_pending': 'Igenzura ritegerejwe',
    'business.apply.business.capacity_body':
        'Igishoro kinini amafaranga ubucuruzi bwawe bwinjiza ashobora kwishyura hakurikijwe ububiko bwagenzuwe. Buri cyifuzo gihabwa agaciro kari muri iyi mbibi.',
    'business.apply.raise.title': 'Igishoro cyawe',
    'business.apply.raise.subtitle':
        'Shyiraho amasezerano yawe kandi wibwire abashoramari.',
    'business.apply.raise.fundraise': '1 · Gushaka igishoro',
    'business.apply.raise.note_title':
        "Umutwe w'urupapuro · icyo igishoro kigenewe",
    'business.apply.raise.note_title_placeholder':
        'urugero: Ububiko bukonje, Kongera imodoka…',
    'business.apply.raise.note_title_help':
        "Vuga umushinga nyir'izina abashoramari bateramo inkunga.",
    'business.apply.raise.target': "Intego y'igishoro (RWF)",
    'business.apply.raise.term': 'Igihe',
    'business.apply.raise.term_months': 'Amezi {months}',
    'business.apply.raise.rate': 'Igipimo',
    'business.apply.raise.rate_info': 'Uko iyi nyungu ibarwa',
    'business.apply.raise.rate_how': 'Uko igipimo cyawe gishyirwaho',
    'business.apply.raise.rate_one_charge':
        "Ikiguzi kimwe ku byo uguza. Igikurwamo cyonyine ni amafaranga ya platifomu ya 3%, akurwa ku mafaranga wakusanyije igihe akoherejwe — nta yandi mafaranga y'inyongera.",
    'business.apply.raise.rate_yours': 'Amanota yawe',
    'business.apply.raise.rate_rated': 'Ku rwego rwa {band}',
    'business.apply.raise.rate_best': 'Amanota yo hejuru, amezi 3',
    'business.apply.raise.rate_best_detail': 'Igipimo cyiza kuri Rozine',
    'business.apply.raise.rate_term_detail': "+{ratio} y'inyongera y'igihe",
    'business.apply.raise.rate_cap': 'Ntirenga {cap}%',
    'business.apply.raise.quote_pending':
        "Andika intego n'igihe kugira ngo ubone ibiciro.",
    'business.apply.raise.notes': 'Impapuro',
    'business.apply.raise.notes_info': 'Ibyerekeye impapuro za Rozine',
    'business.apply.raise.note_unit': 'Urupapuro rumwe rwa Rozine ni {price}.',
    'business.apply.raise.term_label': 'Igihe · amezi yo kwishyura',
    'business.apply.raise.term_any': '· hagati ya 3 na 6',
    'business.apply.raise.you_receive': 'Uhabwa',
    'business.apply.raise.interest': '+ Inyungu',
    'business.apply.raise.interest_basis': '({rate}% · amezi {months})',
    'business.apply.raise.you_repay': 'Wishyura',
    'business.apply.raise.per_month': '{amount} / ukwezi',
    'business.apply.raise.first_payment':
        'Bitangira ukwezi kumwe nyuma yo kubona amafaranga',
    'business.apply.raise.reserve': 'Ikigega cyo kurinda abashoramari',
    'business.apply.raise.reserve_detail':
        'Ikurwa mu kiguzi · nta kindi wishyura',
    'business.apply.raise.use_of_funds': 'Icyo amafaranga azakoreshwa',
    'business.apply.raise.use.inventory': 'Ibicuruzwa',
    'business.apply.raise.use.expansion': 'Kwaguka',
    'business.apply.raise.use.equipment': 'Ibikoresho',
    'business.apply.raise.use.hiring': 'Gutanga akazi',
    'business.apply.raise.use.working_capital': 'Igishoro cyo gukora',
    'business.apply.raise.use.other': 'Ibindi',
    'business.apply.raise.show_investors': '2 · Wereke abashoramari uwo uri we',
    'business.apply.raise.logo': 'Ikirango',
    'business.apply.raise.photos_help':
        "Shyiraho ikirango cyawe n'amafoto agera kuri 7 — ibicuruzwa, aho ukorera, abakozi n'imikorere.",
    'business.apply.raise.slot.products': 'Ibicuruzwa',
    'business.apply.raise.slot.facilities': 'Aho ukorera',
    'business.apply.raise.slot.team': 'Abakozi',
    'business.apply.raise.slot.operations': 'Imikorere',
    'business.apply.raise.slot.customers': 'Abakiriya',
    'business.apply.raise.slot.impact': 'Ingaruka nziza',
    'business.apply.raise.slot.brand': 'Ikirango',
    'business.apply.raise.story': '3 · Inkuru yawe',
    'business.apply.raise.story_prompt':
        'Kuki abashoramari bakwizera ubucuruzi bwawe?',
    'business.apply.raise.story_placeholder':
        "Sebeya Logistics imaze imyaka 8 itwara ibicuruzwa muri Afurika y'Iburasirazuba...",
    'business.apply.raise.story_count': 'Amagambo {count} / 100',
    'business.apply.review.title': 'Suzuma usinye',
    'business.apply.review.subtitle':
        'Emera ingaruka, wemere amasezerano, usinye kugira ngo wohereze.',
    'business.apply.review.risk_disclosures': "Itangazwa ry'ingaruka",
    'business.apply.review.agreements': 'Amasezerano',
    'business.apply.review.agree_terms_prefix': 'Nemeye',
    'business.apply.review.agree_privacy_prefix': 'Nasomye',
    'business.apply.review.document.terms': "Amabwiriza n'amasezerano",
    'business.apply.review.document.privacy': "Itangazo ry'ibanga",
    'business.apply.review.read': 'Soma',
    'business.apply.review.document_intro':
        "Rozine · Repubulika y'u Rwanda · verisiyo {version}. Incamake y'ingingo z'ingenzi, hanyuma inyandiko yuzuye wemera.",
    'business.apply.review.got_it': 'Nabyumvise',
    'business.apply.review.sign_submit': 'Sinya wohereze',
    'business.apply.review.full_name': 'Amazina yawe yose',
    'business.apply.review.full_name_placeholder': 'Robert Mugisha',
    'business.apply.review.signature': "Umukono w'ikoranabuhanga",
    'business.apply.review.sign_here': 'Sinya hano',
    'business.apply.review.due_on_approval': 'Bishyurwa nibyemezwa',
    'business.apply.review.application_fee': "Amafaranga y'ubusabe",
    'business.apply.review.application_fee_when':
        'Rimwe gusa, yishyurwa nibyemezwa.',
    'business.apply.review.fee_note':
        'Yishyurwa urupapuro rwemejwe, mbere yo gutangazwa. Igihe amafaranga wakusanyije akoherejwe, havanwamo amafaranga ya platifomu ya 3%; wishyura amafaranga yose wakusanyije.',
    'business.apply.review.binding':
        "Umukono wawe utegeka ubucuruzi mu mategeko kubahiriza inshingano zatangajwe. Nimero y'ubusabe itangwa iyo bwoherejwe.",
    'business.apply.approved.title': 'Ubusabe bwawe bwemejwe',
    'business.apply.approved.body':
        "Bushyire ku rutonde rw'abashoramari kugira ngo utangire gukusanya. Nta mafaranga yo gushyira ku rutonde.",
    'business.apply.approved.publish': "Shyira ku rutonde rw'abashoramari",
    'business.apply.published.title': 'Ikusanya ryawe ryashyizwe ahagaragara',
    'business.apply.published.body':
        'Abashoramari barayibona ku rutonde kandi bashobora gushora ubu.',
    'business.apply.submitted.title': 'Ubusabe bwawe bwoherejwe',
    'business.apply.submitted.body':
        'Ubusabe bwawe burimo gusuzumwa. Uzamenyeshwa buri ntambwe.',
    'business.apply.submitted.note_id': "Nimero y'urupapuro · {id}",
    'business.apply.submitted.funded_title': 'Nirimara kuzura',
    'business.apply.submitted.funded_body':
        "Amafaranga agera kuri konti ya banki y'ubucuruzi mu masaha 24 nyuma yo gufunga. Uwo munsi uba itariki yo kwishyura buri kwezi kugeza byose byishyuwe.",
    'business.apply.submitted.timeline': 'Aho ubusabe bugeze',
    'business.apply.submitted.stage.submitted': 'Yoherejwe',
    'business.apply.submitted.stage.under_review': 'Irasuzumwa',
    'business.apply.submitted.stage.approved': 'Yemejwe',
    'business.apply.submitted.stage.published': 'Yatangajwe',
    'business.apply.submitted.back_home': 'Subira Ahabanza',

    'app.skip_to_content': 'Jya ku bikubiyemo',
    'app.sheet.close': 'Funga',
    'business.publish.title': "Tangaza ku rubuga rw'abashoramari",
    'business.publish.body_fee':
        "{title} yatsinze isuzuma. Ishyura amafaranga y'ubusabe rimwe kugira ngo igaragare ku bashoramari.",
    'business.publish.body_free':
        "{title} yatsinze isuzuma. Nta mafaranga y'ubusabe yo kwishyura — yitangaze kugira ngo igaragare ku bashoramari.",
    'business.publish.target': "Intego y'igishoro",
    'business.publish.fee': "Amafaranga y'ubusabe",
    'business.publish.pay_with': 'Ishyura ukoresheje',
    'business.publish.source.wallet': 'Igikapu',
    'business.publish.source.mtn': 'MTN MoMo',
    'business.publish.source.airtel': 'Airtel',
    'business.publish.source.card': 'Ikarita',
    'business.publish.pay_and_publish': 'Ishyura utangaze',
    'business.publish.publish': 'Tangaza',
    'business.publish.publishing': 'Biratangazwa…',
    'business.publish.not_yet': 'Si ubu',

    'business.onboarding.back': 'Subira inyuma',
    'business.onboarding.progress': 'Aho kwiyandikisha bigeze',
    'business.onboarding.step_label.confirm': 'INTAMBWE YA 1 KURI 4 · RDB',
    'business.onboarding.step_label.documents': 'INTAMBWE YA 2 KURI 4',
    'business.onboarding.step_label.bank': 'INTAMBWE YA 3 KURI 4',
    'business.onboarding.step_label.finish': 'INTAMBWE YA 4 KURI 4',
    'business.onboarding.cta.confirm': 'Emeza ukomeze',
    'business.onboarding.cta.documents': 'Komeza',
    'business.onboarding.cta.bank': 'Komeza',
    'business.onboarding.cta.finish': 'Soza kwiyandikisha',
    'business.onboarding.error.certificate':
        'Ohereza kandi wemeze icyemezo cya RDB kugira ngo ukomeze — nta kigo gishyirwa ku rutonde kitagifite',
    'business.onboarding.error.bank':
        "Huza konti ya banki y'ikigo yemejwe kugira ngo ukomeze",
    'business.onboarding.confirm.title': 'Emeza ikigo cyawe',
    'business.onboarding.confirm.subtitle':
        "Byavanywe mu Rwego rw'Igihugu rw'Iterambere (RDB). Bisuzume wemeze ko ari byo.",
    'business.onboarding.confirm.name': 'Izina ryanditswe',
    'business.onboarding.confirm.company_code': "Nomero y'ikigo",
    'business.onboarding.confirm.legal_form': "Ubwoko bw'ikigo",
    'business.onboarding.confirm.registered': 'Cyanditswe',
    'business.onboarding.confirm.status': 'Uko gihagaze',
    'business.onboarding.confirm.status_active': 'Gikora',
    'business.onboarding.confirm.status_dormant': 'Cyasinziriye',
    'business.onboarding.confirm.status_deregistered': 'Cyavanywe ku rutonde',
    'business.onboarding.confirm.staff': 'Abakozi',
    'business.onboarding.confirm.staff_count': 'Abakozi {count}',
    'business.onboarding.confirm.address': 'Aderesi yanditswe',
    'business.onboarding.confirm.industry': "Urwego rw'ubucuruzi",
    'business.onboarding.confirm.auto_detected': '✓ Byabonetse muri RDB',
    'business.onboarding.confirm.industry_help':
        'Twabibonye mu iyandikwa ryawe muri RDB ({category}). Bihindure niba bidahuye.',
    'business.onboarding.confirm.management': 'Ubuyobozi',
    'business.onboarding.confirm.shareholders': 'Abanyamigabane',
    'business.onboarding.documents.title':
        "Icyemezo cya RDB, ikirango n'amafoto",
    'business.onboarding.documents.subtitle':
        'Icyemezo cyo kwandikwa ni itegeko — nta kintu gishyirwa ku rutonde kitagifite. Hanyuma werekane abashoramari uwo uri we.',
    'business.onboarding.documents.certificate':
        'Icyemezo cyo kwandikwa muri RDB',
    'business.onboarding.documents.certificate_required': 'Birakenewe',
    'business.onboarding.documents.certificate_uploaded': 'Cyoherejwe',
    'business.onboarding.documents.certificate_verified': 'Cyemejwe',
    'business.onboarding.documents.certificate_drop':
        'Shyiraho icyemezo cya RDB (PDF cyangwa ifoto)',
    'business.onboarding.documents.certificate_number': "Nomero y'icyemezo",
    'business.onboarding.documents.certificate_placeholder': 'RDB/2019/123456',
    'business.onboarding.documents.verify_certificate': 'Emeza icyemezo',
    'business.onboarding.documents.verifying': 'Biri kwemezwa…',
    'business.onboarding.documents.certificate_on_file': 'Icyemezo cyabitswe',
    'business.onboarding.documents.logo': 'Ikirango',
    'business.onboarding.documents.logo_set': 'Ikirango cyashyizweho',
    'business.onboarding.documents.logo_label': 'Ohereza ikirango cyawe',
    'business.onboarding.documents.logo_help':
        'Ikirango gisobanutse gituma abashoramari bakugirira icyizere ako kanya.',
    'business.onboarding.documents.photos': "Amafoto y'ibimenyetso",
    'business.onboarding.documents.uploaded': 'Yoherejwe',
    'business.onboarding.bank.title': "Konti ya banki y'ikigo",
    'business.onboarding.bank.subtitle':
        "Aho uzakira amafaranga ukusanyije. Igomba kuba konti y'ikigo yo mu Rwanda iri ku izina ry'ikigo cyawe, ifite nibura abashyira umukono {count}.",
    'business.onboarding.bank.bank': 'Banki',
    'business.onboarding.bank.select': 'Hitamo banki yawe…',
    'business.onboarding.bank.business_account_prefix': 'Iyi ni',
    'business.onboarding.bank.business_account': "konti y'ikigo",
    'business.onboarding.bank.business_account_suffix':
        ' yanditswe, si konti bwite.',
    'business.onboarding.bank.account_name': 'Izina rya konti',
    'business.onboarding.bank.use_company_name': "Koresha izina ry'ikigo",
    'business.onboarding.bank.account_number': 'Nomero ya konti',
    'business.onboarding.bank.account_number_placeholder': '00012345678',
    'business.onboarding.bank.signatories': 'Abashyira umukono',
    'business.onboarding.bank.signatories_count':
        '{selected} batoranyijwe · hakenewe {required}+',
    'business.onboarding.bank.link': "Huza konti y'ikigo",
    'business.onboarding.bank.linking': 'Birahuzwa…',
    'business.onboarding.bank.linked': "✓ Konti ya banki y'ikigo yahujwe",
    'business.onboarding.bank.locked':
        "Ushobora guhuza konti imwe gusa yo kwakiriraho amafaranga. Kugira ngo uyihindure nyuma, vugana n'ubufasha bwa Rozine — ibi birinda amafaranga yawe koherezwa ahandi nta burenganzira.",
    'business.onboarding.finish.title': 'Byose biteguye',
    'business.onboarding.finish.subtitle':
        'Aho ukorera harateguye. Bisuzume mbere yo gusoza.',
    'business.onboarding.finish.company': 'Ikigo',
    'business.onboarding.finish.rdb': '✓ RDB',
    'business.onboarding.finish.contact': 'Aho uboneka',
    'business.onboarding.finish.payout': 'Konti yakiriraho',
    'business.onboarding.finish.setting_up': 'Turimo gutegura aho ukorera…',

    'business.note.close': 'Subira ku Ahabanza',
    'business.note.back': 'Subira inyuma',
    'business.note.tile.outstanding': 'Ibisigaye kwishyurwa',
    'business.note.tile.payments_made': 'Ubwishyu bwakozwe',
    'business.note.tile.investors': 'Abashoramari',
    'business.note.tile.health': 'Uko kwishyura bihagaze',
    'business.note.tile.raised': 'Byakusanyijwe',
    'business.note.tile.funded': 'Byatewe inkunga',
    'business.note.tile.closes_in': 'Birangira mu',
    'business.note.payments_made': '{made} / {total}',
    'business.note.health.on_time': 'Ku gihe',
    'business.note.health.late': 'Byatinze',
    'business.note.pct': '{pct}%',
    'business.note.day': 'Umunsi {count}',
    'business.note.days': 'Iminsi {count}',
    'business.note.tracker.repayment': "Ikurikirana ry'ubwishyu",
    'business.note.tracker.funding': "Ikurikirana ry'inkunga",
    'business.note.tracker.repaid_pct': '{pct}% byishyuwe',
    'business.note.tracker.funded_pct': '{pct}% byatewe inkunga',
    'business.note.tracker.repaid': 'Byishyuwe',
    'business.note.tracker.left_to_pay': 'Ibisigaye kwishyura',
    'business.note.tracker.over_month': 'mu kwezi {count}',
    'business.note.tracker.over_months': 'mu mezi {count}',
    'business.note.tracker.fully_repaid': 'Byishyuwe byose',
    'business.note.tracker.raised': 'Byakusanyijwe',
    'business.note.tracker.left_to_raise': 'Ibisigaye gukusanywa',
    'business.note.tracker.closes': 'birangira ku wa {date}',
    'business.note.upcoming': 'Ubwishyu butaha',
    'business.note.due_in_day':
        'Bigomba kwishyurwa ku wa {date} · mu munsi {count}',
    'business.note.due_in_days':
        'Bigomba kwishyurwa ku wa {date} · mu minsi {count}',
    'business.note.pay': 'Ishyura',
    'business.note.funded_notice':
        "Byatewe inkunga yose ku wa {date}. Rozine irimo gutegura kohereza amafaranga — kwishyura buri kwezi bitangira iminsi 30 nyuma y'uko amafaranga agera kuri banki yawe.",
    'business.note.expired_notice':
        'Iki gikorwa cyo gukusanya cyarangiye ku wa {date} kitageze kuri {target}. Buri mushoramari yasubijwe amafaranga ye yose, nta kiguzi.',
    'business.note.photos': 'Amafoto',
    'business.note.reported_by_business': "Byatanzwe n'ikigo",
    'business.note.close_photo': 'Funga ifoto',
    'business.note.previous_photo': 'Ifoto ibanza',
    'business.note.next_photo': 'Ifoto ikurikira',
    'business.note.trend.title': 'Uko ibikorwa byagenze',
    'business.note.trend.caption':
        'Amafaranga yinjiye buri kwezi · uko kwishyura bihagaze',
    'business.note.trend.range': 'Igihe',
    'business.note.trend.six_months': 'Amezi 6',
    'business.note.trend.twelve_months': 'Amezi 12',
    'business.note.trend.hint': 'Kanda ku murongo urebe uko kwezi',
    'business.note.trend.month_on_time':
        '{month} · yinjije {revenue} · yishyuye ku gihe',
    'business.note.trend.month_late':
        '{month} · yinjije {revenue} · yishyuye atinze',
    'business.note.trend.high': 'Ahari hejuru',
    'business.note.trend.low': 'Ahari hasi',
    'business.note.investors.title': 'Abashoramari baheruka',
    'business.note.investors.view_all': 'Reba bose {count} ›',
    'business.note.investors.kind.individual': 'Umuntu ku giti cye',
    'business.note.investors.kind.institution': 'Ikigo',
    'business.note.investors.kind.sacco': 'SACCO',

    'common.ordinal.zero': '{n}',
    'common.ordinal.one': '{n}',
    'common.ordinal.two': '{n}',
    'common.ordinal.few': '{n}',
    'common.ordinal.many': '{n}',
    'common.ordinal.other': '{n}',
    'business.reports.title': 'Raporo',
    'business.reports.subtitle':
        "Bigenzurwa buri kwezi n'umugenzuzi wawe uza aho ukorera.",
    'business.reports.guide.title': 'Uko igenzura rya buri kwezi rikorwa',
    'business.reports.guide.opens.title': 'Kusanya inyandiko zawe',
    'business.reports.guide.opens.body':
        "Hagati ya tariki ya 20 n'impera z'ukwezi, kusanya inyandiko zose z'imari, izo ku mpapuro n'iz'ikoranabuhanga, witegura igenzura CPA wawe azakorera aho ukorera.",
    'business.reports.guide.visit.title': "Itegure uruzinduko rw'umugenzuzi",
    'business.reports.guide.visit.body_before':
        "CPA wagenewe asura aho ukorera agasuzuma inyandiko, agahuza amafaranga yinjira n'asohoka, hanyuma agafunga raporo mbere ya tariki ya",
    'business.reports.guide.visit.body_after': '.',
    'business.reports.guide.cosign.title': 'Shyiraho umukono cyangwa ujurire',
    'business.reports.guide.cosign.body':
        'Iyo igenzura rifunzwe, ufite amasaha 24 yo kuyisuzuma no gushyiraho umukono, cyangwa kujurira ufite ibimenyetso. Nutagira icyo ukora mu masaha 24, raporo ifatwa nk’iyemejwe. Kubera ko utigera ukora ku mibare, raporo iba yigenga — ari cyo abashoramari bishyurira.',
    'business.reports.guide.visit.body_undated':
        "CPA wagenewe asura aho ukorera agasuzuma inyandiko, agahuza amafaranga yinjira n'asohoka, hanyuma agafunga raporo.",
    'business.reports.tabs': 'Uko raporo zihagaze',
    'business.reports.tab.verified': 'Zatangajwe',
    'business.reports.tab.in_audit': 'Birimo kugenzurwa',
    'business.reports.tab.archived': 'Zabitswe',
    'business.reports.status.verified': 'Byemejwe',
    'business.reports.status.in_audit': 'Birimo kugenzurwa',
    'business.reports.status.archived': 'Byabitswe',
    'business.reports.health.healthy': 'Bimeze neza',
    'business.reports.health.watch': 'Bikurikiranwe',
    'business.reports.health.at_risk': 'Biri mu kaga',
    'business.reports.row.filed':
        'Ayinjiye {inflow} · {health} · yagenzuwe na {auditor}',
    'business.reports.row.in_audit':
        'Biri kwa {auditor} · bifungwa bitarenze {date}',
    'business.reports.row.annual': "Incamake y'umwaka wose",
    'business.reports.annual': "Raporo y'umwaka {year}",
    'business.reports.empty': 'Nta raporo irahari.',
    'business.reports.empty_body':
        'Raporo ya buri kwezi igaragara hano iyo umugenzuzi wawe amaze kuyifunga.',
    'business.reports.percent': '{value}%',
    'business.reports.count_of': '{count} kuri {of}',
    'business.reports.sheet.live': 'Abashoramari bawe barayibona',
    'business.reports.sheet.archived': 'Raporo yabitswe',
    'business.reports.sheet.published':
        "Yatangajwe ku wa {date} · yabonywe n'abashoramari {count}",
    'business.reports.sheet.filed': 'Yatanzwe ku wa {date} · yarabitswe',
    'business.reports.sheet.inflow': 'Ayinjiye',
    'business.reports.sheet.outflow': 'Ayasohotse',
    'business.reports.sheet.health': 'Uko bimeze',
    'business.reports.sheet.recap': 'Ibyo abashoramari basoma',
    'business.reports.sheet.figures': 'Imibare yatanzwe',
    'business.reports.sheet.audited_by': 'Yagenzuwe na {name}',
    'business.reports.sheet.disclosure_live':
        'Ibi ni byo byose abashoramari bawe babona kuri uku kwezi. Nta kindi ku kigo cyawe gitangazwa.',
    'business.reports.sheet.disclosure_archived':
        "Raporo zabitswe ziguma mu mateka yawe ariko ntizikigaragara ku rupapuro rw'abashoramari.",
    'business.reports.figure.cash_inflow': 'Amafaranga yinjiye',
    'business.reports.figure.cash_outflow': 'Amafaranga yasohotse',
    'business.reports.figure.net_position': 'Igisigaye',
    'business.reports.figure.net_margin': 'Inyungu nyayo',
    'business.reports.figure.days_cash_on_hand':
        'Iminsi amafaranga ahari yamara',
    'business.reports.figure.quarters_above_floor':
        'Ibihembwe byarenze igipimo cya {floor}%',

    'business.profile.title': 'Umwirondoro',
    'business.profile.verified': '✓ Cyemejwe',
    'business.profile.score': 'Amanota {score}',
    'business.profile.menu': 'Ibikubiye mu mwirondoro',
    'business.profile.section.company': "Amakuru y'ikigo",
    'business.profile.section.linked': 'Konti zihujwe',
    'business.profile.section.terms': "Amategeko n'amabwiriza",
    'business.profile.section.privacy': "Itangazo ku ibanga ry'amakuru",
    'business.profile.section.security': 'Umutekano wa konti',
    'business.profile.section.permissions': "Uburenganzira n'inshingano",
    'business.profile.section.support': 'Ubufasha',
    'business.profile.security.two_factor': 'Kwinjira mu byiciro bibiri',
    'business.profile.security.two_factor_on':
        'Birakora · kode ya porogaramu yemeza iyo winjira',
    'business.profile.security.login_alerts': 'Imenyesha ryo kwinjira',
    'business.profile.security.login_alerts_hint':
        'Imeri igihe igikoresho gishya cyinjiye',
    'business.profile.security.two_factor_off':
        'Ntibikora · ongeraho kode ya porogaramu yemeza iyo winjira',
    'business.profile.security.password': 'Hindura ijambobanga',
    'business.profile.permissions.none': 'Nta burenganzira kuri iki kigo',
    'business.profile.permissions.business.view': 'Kureba ikigo',
    'business.profile.permissions.application.create':
        'Gutangiza ubusabe bwo gukusanya',
    'business.profile.permissions.application.save': 'Guhindura ubusabe',
    'business.profile.permissions.application.evaluate':
        'Kugenzura niba byemewe',
    'business.profile.permissions.application.sign': 'Gusinya ubusabe',
    'business.profile.permissions.report.cosign': 'Gusinyana raporo',
    'business.profile.permissions.business.wallet.deposit':
        'Kongera amafaranga mu ikofi',
    'business.profile.permissions.business.wallet.withdraw':
        'Kubikuza mu gikapu',
    'business.profile.permissions.repayment.pay': 'Kwishyura',
    'business.profile.permissions.business.profile.update':
        "Guhindura amakuru y'ikigo",
    'business.profile.pending.linked_title':
        'Nta konti yo kwakiriraho iracyahari',
    'business.profile.pending.linked_body':
        'Konti zo kwakiriraho zihujwe n’iki kigo zizagaragara hano.',
    'business.help.intro':
        "Ibisubizo by'ibibazo ubucuruzi bubaza kenshi. Kanda ku ikarita usome igisubizo cyose.",
    'business.help.group.start': 'Gutangira',
    'business.help.group.capacity': "Ubushobozi n'ibiciro",
    'business.help.group.raise': "Gukusanya inyandiko y'umwenda",
    'business.help.group.repay': 'Kwishyura',
    'business.help.group.reports': "Raporo n'amanota yawe",
    'business.help.group.fees': "Amafaranga ya serivisi n'igishoro",
    'business.help.group.investors': "Abashoramari n'isoko rya kabiri",
    'business.help.group.account': "Konti n'ubufasha",
    'business.help.start.what.q':
        'Rozine ni iki kandi ifasha ubucuruzi bwanjye gute?',
    'business.help.start.what.a':
        "Rozine ifasha ubucuruzi bwo mu Rwanda bwagenzuwe kuguza abashoramari benshi binyuze mu nyandiko y'umwenda. Ukusanya RWF 3,000,000 kugeza kuri 100,000,000 mu mezi 3 kugeza kuri 6 kandi ukishyura mu byiciro bingana buri kwezi by'igishoro n'inyungu idahinduka.\n\nNta mugabane w'ubucuruzi bwawe utanga. Buri mushoramari yishyurwa hakurikijwe imigabane afite.",
    'business.help.start.eligible.q': 'Ni ubuhe bucuruzi bushobora gukusanya?',
    'business.help.start.eligible.a':
        "Ubucuruzi bwanditse mu Rwanda bufite amafaranga yinjiye ku mwaka yagenzuwe angana nibura na RWF 30,000,000 n'inyandiko za banki na mobile money z'amezi 36. Ubucuruzi bwongera gukusanya bwishyuye ku gihe inyandiko yabwo iheruka bukenera amezi 12.\n\nAmanota yawe agomba kuba nibura 2.0, kandi Umufatanyabikorwa w'igenzura agomba gusura aho ukorera agashyira umukono kuri raporo y'ibanze mbere y'uko ikusanya ryawe rishyirwa ku isoko.",
    'business.help.start.documents.q': 'Nkeneye iki kugira ngo nsabe?',
    'business.help.start.documents.a':
        "Amakuru y'iyandikwa ry'ubucuruzi bwawe n'inyandiko za banki na mobile money, wohereza muri porogaramu. Porogaramu ikumenyesha niba hari ikindi gikenewe.\n\nNta mafaranga yo gusaba yishyurwa muri iki gihe cy'igerageza.",
    'business.help.capacity.how.q': "Ingano y'inyandiko yanjye igenwa gute?",
    'business.help.capacity.how.a':
        "Rozine ibara amafaranga ubucuruzi bwawe busigarana buri kwezi nyuma y'ibikorwa, ibyo nyirabwo afata n'indi myenda yishyurwa, ikagena ingano y'inyandiko ku buryo ayo mafaranga angana n'inshuro 1.5 z'icyiciro cya buri kwezi. Ubucuruzi bwaba munsi y'inshuro 1.25 ntibuhabwa inyandiko.\n\nInyandiko ntirenga 35% by'amafaranga yinjiye ku mwaka yagenzuwe, kandi iri hagati ya RWF 3,000,000 na 100,000,000.",
    'business.help.capacity.rate.q': 'Nzishyura inyungu ingana iki?',
    'business.help.capacity.rate.a':
        "Inyungu yose iri hagati ya 10.0% na 15.0% by'igishoro mu gihe cyose, igenwa n'amanota yawe, hakiyongeraho gato ku bihe birebire. Igenwa igihe ikusanya rifunguwe.\n\nIhinduka ry'amanota yawe nyuma ntirihindura amasezerano y'inyandiko yamaze gutangwa.",
    'business.help.capacity.more.q':
        'Nashobora gukusanya ibirenze ibyo nahawe?',
    'business.help.capacity.more.a':
        'Oya. Ikusanya ntirishobora kurenga ingano Rozine ibara ihereye ku mibare yawe yagenzuwe.\n\nKwishyura ku gihe no gukomeza igenzura rya buri kwezi ni byo binoza ubutaha.',
    'business.help.raise.time.q':
        'Ikusanya rimara igihe kingana iki rifunguye?',
    'business.help.raise.time.a':
        "Ikusanya rifungurirwa abashoramari iminsi 30, cyangwa kugeza ryuzuye. Abashoramari bagura imigabane ya RWF 5,000, kandi nta mushoramari ushobora gutunga ibirenze kimwe cya kabiri cy'ikusanya ryawe.\n\nUshobora gukurikirana amafaranga yiyemejwe muri porogaramu igihe icyo ari cyo cyose.",
    'business.help.raise.unfunded.q':
        'Bigenda bite ikusanya ryanjye ritujujwe?',
    'business.help.raise.unfunded.a':
        'Iyo ikusanya ritagera ku 100% mu minsi 30, rirarangira kandi buri mushoramari agasubizwa amafaranga ye yose. Nta kintu cyishyurwa kandi nta mwenda ubaho.\n\nUshobora kongera gusaba nyuma.',
    'business.help.raise.schedule.q':
        "Menya ryari ingengabihe yanjye y'ubwishyu?",
    'business.help.raise.schedule.a':
        "Mbere y'uko hagira uwiyemeza. Icyiciro ni igishoro wongeyeho inyungu yose, ugabanyije umubare w'amezi. Icyiciro cya mbere kigomba kwishyurwa ukwezi kumwe nyuma yo guhabwa amafaranga, ibindi bikishyurwa ku munsi nk'uwo w'ukwezi gukurikiyeho.\n\nUrugero, RWF 12,000,000 kuri 11.1% mu mezi 6 ni 13,332,000 yose, cyangwa 2,222,000 buri kwezi.",
    'business.help.repay.how.q': 'Nishyura nte?',
    'business.help.repay.how.a':
        "Shyira amafaranga y'icyiciro mu gasanduku k'ubucuruzi bwawe kuri Rozine. Saa 23:59 ku itariki y'ubwishyu arafatwa akagabanywa abafite inyandiko mu buryo bwikora.\n\nShyira amafaranga mu gasanduku uyakuye kuri konti ya banki cyangwa mobile money iri mu izina ry'ubucuruzi bwawe.",
    'business.help.repay.early.q': "Nashobora kwishyura mbere y'igihe?",
    'business.help.repay.early.a':
        'Yego. Ushobora kwishyura umwenda wose usigaye igihe icyo ari cyo cyose, maze inyandiko ikarangira.\n\nInyungu mwumvikanyeho ntigabanuka: abashoramari bahabwa neza amafaranga yari asigaye ateganyijwe.',
    'business.help.repay.late.q': 'Bigenda bite nishyuye ntinze?',
    'business.help.repay.late.a':
        "Ibiri mu gasanduku kawe ku itariki y'ubwishyu birafatwa, kandi ibigeramo nyuma bifatwa ako kanya. Igihano cy'ubukererwe cya 5% by'amafaranga atishyuwe cyongerwaho ku itariki y'ubwishyu, ku munsi wa 7 no ku munsi wa 30, ntikirenge 15% by'icyiciro; cyose gihabwa abashoramari bawe.\n\nIyo ibirarane bitishyuwe ku munsi wa 31, inyandiko ifatwa nk'iyananiwe kwishyurwa, kugaruza bigatangira. Tuvugishe hakiri kare niba ubona ikibazo kizaza.",
    'business.help.reports.monthly.q': 'Igenzura rya buri kwezi rikora gute?',
    'business.help.reports.monthly.a':
        "Buri kwezi igihe inyandiko yawe ikiriho, utanga inyandiko z'imari n'iz'ibikorwa by'ukwezi, Umufatanyabikorwa wawe w'igenzura akazigenzura aje aho ukorera, agashyira umukono kuri raporo itangazwa ku bashoramari bawe mbere y'umunsi w'ubwishyu.\n\nUmenyeshwa igihe raporo ifite umukono igutegereje.",
    'business.help.reports.rating.q': 'Amanota yanjye ya Rozine agenwa gute?',
    'business.help.reports.rating.a':
        "Amanota yawe ni umubare umwe kuri 5.0, ugaragazwa nka Ikomeye (4.0 kugeza 5.0), Ihamye (3.0 kugeza 3.9), Idakomeye (2.0 kugeza 2.9) cyangwa Iri mu bibazo (munsi ya 2.0). Yongera kubarwa kuri buri raporo y'ukwezi ifite umukono kandi agatangazwa ku bafite inyandiko yawe.\n\nKwishyura ku gihe no gutegura inyandiko zawe kuri buri gusurwa ni byo biyafasha cyane.",
    'business.help.reports.dispute.q': 'Bigenda bite ntemeranya na raporo?',
    'business.help.reports.dispute.a':
        "Ufite amasaha 24 uhereye igihe wayihawe kugira ngo ujurire raporo ifite umukono, utanga ibisobanuro cyangwa inyandiko z'ibimenyetso zigera kuri eshanu. Igihe ubujurire bugisuzumwa, inyandiko yawe ihagarikwa ku isoko.\n\nNiba utajuriye ku gihe, raporo iratangazwa.",
    'business.help.fees.fees.q':
        'Ni ayahe mafaranga ya serivisi Rozine yishyuza?',
    'business.help.fees.fees.a':
        "Buri giciro cya serivisi kigaragazwa mu RWF mbere yo kwemeza, hamwe n'amafaranga uzahabwa n'ayo uzishyura.\n\nInyungu wishyura ibarwa ku gishoro cyose. Kubikuza nta mafaranga ya Rozine bisaba; ikiguzi cy'utanga serivisi kigaragazwa mbere.",
    'business.help.fees.funds.q': 'Mbona ryari amafaranga yakusanyijwe?',
    'business.help.fees.funds.a':
        "Iyo ikusanya ryawe rigeze ku 100%, amafaranga yoherezwa ku bucuruzi bwawe kandi ingengabihe yawe y'ubwishyu igatangira.\n\nUmenyeshwa igihe amafaranga yoherejwe, kandi ushobora kuyabikuza kuri konti yagenzuwe iri mu izina ry'ubucuruzi bwawe.",
    'business.help.investors.who.q': 'Ni nde utera inkunga ikusanya ryanjye?',
    'business.help.investors.who.a':
        'Abashoramari bagenzuwe kuri Rozine, buri wese afite imigabane ya RWF 5,000 mu nyandiko yawe.\n\nNtukorana na buri umwe ukwe: Rozine igabanya buri bwishyu hagati yabo kandi ikabatangariza raporo zawe zifite umukono.',
    'business.help.investors.secondary.q': 'Isoko rya kabiri rindeba?',
    'business.help.investors.secondary.a':
        "Isoko rya kabiri nirifungura, abashoramari bazashobora kugurishanya imigabane y'inyandiko yawe. Ibyo ntibihindura na rimwe ibyo urimo cyangwa igihe ubyishyura.\n\nIgihe inyandiko yawe yatinze, yananiwe kwishyurwa cyangwa iri mu mpaka, imigabane yayo ntishobora gucuruzwa.",
    'business.help.account.team.q':
        "Ni nde ushobora gukora mu izina ry'ubucuruzi bwanjye?",
    'business.help.account.team.a':
        "Umwirondoro > Uburenganzira n'inshingano bugaragaza buri muntu uri kuri konti yawe n'ibyo yemerewe gukora.\n\nOngeraho porogaramu yo kwemeza muri Umutekano wa konti kugira ngo buri kwinjira gusabe kode.",
    'business.help.account.complain.q': 'Natanga ikirego nte?',
    'business.help.account.complain.a':
        "Twandikire uhereye muri iki gice cy'ubufasha. Ikirego cyawe gihabwa nimero yacyo; twemeza ko twakiriye mu masaha 4 kandi tugaharanira kugikemura mu masaha 48.\n\nUshobora kugeza ikirego ku Kigo gishinzwe isoko ry'imari n'imigabane igihe icyo ari cyo cyose.",
    'business.profile.pending.terms_title': 'Biraza vuba muri porogaramu',
    'business.profile.pending.terms_body':
        "Amategeko n'amabwiriza bizasomerwa hano nibimara gutangazwa.",
    'business.profile.pending.privacy_title': 'Biraza vuba muri porogaramu',
    'business.profile.pending.privacy_body':
        "Itangazo ku ibanga ry'amakuru rizasomerwa hano nirimara gutangazwa.",
    'business.profile.sign_out': 'Sohoka',
    'business.profile.back': 'Subira ku mwirondoro',
    'business.profile.company.name': "Izina ry'ikigo",
    'business.profile.company.email': "Imeli y'ikigo",
    'business.profile.company.phone': "Telefoni y'ikigo",
    'business.profile.company.phone_placeholder': '0788 123 456',
    'business.profile.company.address': 'Aderesi yanditswe',
    'business.profile.company.province': 'Intara',
    'business.profile.company.district': 'Akarere',
    'business.profile.company.sector': 'Umurenge',
    'business.profile.company.cell': 'Akagari',
    'business.profile.company.street': 'Umuhanda',
    'business.profile.company.choose': 'Hitamo…',
    'business.profile.company.sector_placeholder': "Izina ry'umurenge",
    'business.profile.company.cell_placeholder': "Izina ry'akagari",
    'business.profile.company.save': 'Bika impinduka',
    'business.profile.company.saving': 'Birabikwa…',
    'business.profile.company.saved': "Amakuru y'ikigo yabitswe",
    'business.profile.records.certificate': 'Icyemezo cya RDB',
    'business.profile.records.expired_notice':
        'Icyemezo cya RDB cyarangiye. Ohereza igishya kugira ngo ukomeze gukusanya.',
    'business.profile.records.number': "Nomero y'icyemezo",
    'business.profile.records.status': 'Uko gihagaze',
    'business.profile.records.status_verified': 'Cyemejwe',
    'business.profile.records.status_expired': 'Cyarangiye',
    'business.profile.records.expires': 'Kizarangira',
    'business.profile.records.no_expiry': 'Nta gihe kirangirira',
    'business.profile.records.signatories': 'Abashyira umukono',
    'business.profile.records.mandate':
        '{count} ku bubasha · hakenewe {required}+',
    'business.profile.registration.title': 'Ibyanditswe',
    'business.profile.registration.established': 'Cyashinzwe',
    'business.profile.registration.note':
        "Bivuye ku iyandikwa n'ububasha byawe byemejwe. Bihinduka gusa iyo Rozine yemeje inyandiko nshya.",
    'business.profile.registration.people': 'Abari ku bubasha',
    'business.profile.registration.required': 'Agomba gushyiraho umukono',
    'business.profile.registration.role.owner': "Nyir'ikigo",
    'business.profile.registration.role.beneficial_owner': "Nyir'inyungu",
    'business.profile.registration.role.controller': 'Ugenzura',
    'business.profile.registration.role.director': 'Umuyobozi',
    'business.profile.registration.role.signatory': 'Ushyiraho umukono',
    'business.profile.registration.role.representative': 'Uhagarariye',
    'business.profile.linked.unlink': 'Kuraho',
    'business.profile.linked.unlink_named': 'Kuraho {name}',
    'business.profile.linked.add': '+ Huza konti yakiriraho',
    'business.profile.linked.none':
        'Nta konti irahuzwa. Huza imwe kugira ngo ushyireho kandi ubikuze amafaranga.',
    'business.profile.linked.empty': 'Nta konti irahuzwa.',
    'business.profile.linked.footnote':
        "Hahuzwa gusa konti iri mu izina ryanditswe ry'ubucuruzi. Amafaranga abikujwe ajya gusa kuri konti ihujwe.",
    'business.profile.legal.updated':
        'Byavuguruwe ku wa {date} · Verisiyo {version}',
    'business.profile.legal.terms_intro':
        'Soma aya Mategeko witonze. Iyo wanditse ikigo cyawe, utangiye gukusanya cyangwa ukoresha Rozine mu bundi buryo, uba wemeye kubahiriza aya Mategeko yose. Niba utayemera, ntugomba gukoresha uru rubuga.',
    'business.profile.legal.privacy_intro':
        "Iri tangazo risobanura amakuru Rozine ikusanya ku kigo cyawe, uko tuyakoresha, n'amahitamo ufite. Iyo ukoresheje Rozine uba wemeye ibivugwa hano.",
    'business.profile.legal.terms_footer':
        "Aya Mategeko agengwa n'amategeko ya Repubulika y'u Rwanda. Ku kibazo cyose, andikira",
    'business.profile.legal.terms_contact': 'legal@rozine.rw',
    'business.profile.legal.privacy_footer':
        "Kugira ngo ukoreshe uburenganzira ku makuru yawe cyangwa uvugane n'ushinzwe kurinda amakuru, andikira",
    'business.profile.legal.privacy_contact': 'privacy@rozine.rw',
    'business.profile.legal.footer_end': '.',

    'admin.auth.head_title': 'Injira',
    'admin.auth.title': 'Injira',
    'admin.auth.subtitle':
        "Ni iby'abakozi gusa. Buri gikorwa cyandikwa mu bubiko bw'igenzura.",
    'admin.auth.email_label': "Imeli y'akazi",
    'admin.auth.email_placeholder': 'wowe@rozine.com',
    'admin.auth.password_label': "Ijambo ry'ibanga",
    'admin.auth.password_placeholder': '••••••••',
    'admin.auth.submit': 'Injira muri konsole',
    'admin.auth.submitting': 'Birimo kwinjira…',
    'admin.auth.back': '← Subira kuri porogaramu zose',
    'admin.nav.label': 'Kugenda muri konsole',
    'admin.nav.open_menu': 'Fungura urutonde',
    'admin.nav.close_menu': 'Funga urutonde',
    'admin.nav.all_apps': '← Porogaramu zose',
    'admin.nav.group.accounts': 'Konti',
    'admin.nav.group.capital': 'Imari',
    'admin.nav.group.treasury': "Ububiko bw'imari",
    'admin.nav.group.console': 'Konsole',
    'admin.nav.notes': 'Inoti',
    'admin.section.notes.title': 'Inoti',
    'admin.section.notes.subtitle': 'Inoti zose zatanzwe kuri Rozine',
    'admin.section.notes.search':
        'Shakisha inoti ukoresheje ikigo cyangwa nimero yayo…',
    'admin.nav.primary_market': "Isoko ry'ibanze",
    'admin.section.primary_market.title': "Igenzura ry'isoko ry'ibanze",
    'admin.section.primary_market.subtitle':
        "Kugenzura ikusanya ry'amafaranga rikomeje",
    'admin.section.primary_market.search':
        'Shakisha ishoramari ukoresheje ikigo cyangwa inoti…',
    'admin.nav.secondary_market': 'Isoko rya kabiri',
    'admin.section.secondary_market.title': "Igenzura ry'isoko rya kabiri",
    'admin.section.secondary_market.subtitle': 'Kugenzura ubucuruzi',
    'admin.section.secondary_market.search':
        'Shakisha itegeko ukoresheje inoti cyangwa umushoramari…',
    'admin.nav.risk': "Ikigo cy'ingaruka",
    'admin.section.risk.title': "Ikigo cy'ingaruka",
    'admin.section.risk.subtitle': 'Gucunga ingaruka ku rubuga rwose',
    'admin.section.risk.search':
        'Shakisha ikibazo ukoresheje ikigo cyangwa inoti…',
    'admin.nav.compliance': 'Kubahiriza amategeko',
    'admin.section.compliance.title': 'Ikibaho cyo kubahiriza amategeko',
    'admin.section.compliance.subtitle': "Igenzura rya KYC, AML n'amabwiriza",
    'admin.section.compliance.search':
        'Shakisha dosiye ukoresheje izina cyangwa nimero…',
    'admin.nav.payments': 'Ubwishyu',
    'admin.section.payments.title': "Ikigo cy'ubwishyu",
    'admin.section.payments.subtitle': 'Uko amafaranga agenda ku rubuga',
    'admin.section.payments.search':
        'Shakisha ubwishyu ukoresheje uruhande cyangwa nimero…',
    'admin.nav.ratings': 'Amanota',
    'admin.section.ratings.title': 'Amanota',
    'admin.section.ratings.subtitle':
        "Ibyiciro biriho, ibyabihinduye n'uburemere bubiri inyuma",
    'admin.section.ratings.search': 'Shakisha amanota ukoresheje ikigo…',
    'admin.nav.deferrals': 'Gusubika',
    'admin.section.deferrals.title': 'Gusubika',
    'admin.section.deferrals.subtitle':
        'Ubusabe bwo kwimura itariki yo kwishyura',
    'admin.section.deferrals.search':
        'Shakisha ubusabe ukoresheje ikigo cyangwa inoti…',
    'admin.nav.plus': 'Rozine Plus',
    'admin.section.plus.title': 'Rozine Plus',
    'admin.section.plus.subtitle':
        "Kwiyandikisha n'amabwiriza ahoraho bikoresha",
    'admin.section.plus.search': 'Shakisha umunyamuryango ukoresheje izina…',
    'admin.nav.finance': 'Imari',
    'admin.section.finance.title': "Ikibaho cy'imari",
    'admin.section.finance.subtitle':
        "Buri nkomoko y'amafaranga n'igitabo cy'imari",
    'admin.section.finance.search': 'Shakisha ibyanditswe…',
    'admin.nav.messaging': 'Ubutumwa',
    'admin.section.messaging.title': "Ubutumwa n'ibikora ubwabyo",
    'admin.section.messaging.subtitle':
        "Amategeko agena igihe, impamvu n'uburyo buri butumwa bwoherezwa",
    'admin.section.messaging.search': 'Shakisha ubutumwa…',
    'admin.nav.academies': 'Amasomo',
    'admin.section.academies.title': 'Amasomo',
    'admin.section.academies.subtitle':
        "Amasomo n'amanota by'amashuri y'abashoramari, ibigo n'abagenzuzi",
    'admin.section.academies.search': 'Shakisha isomo…',
    'admin.nav.app_control': 'Igenzura rya porogaramu',
    'admin.section.app_control.title': 'Igenzura rya porogaramu',
    'admin.section.app_control.subtitle':
        "Ibiri muri porogaramu z'abashoramari, ibigo n'abagenzuzi byose",
    'admin.section.app_control.search': 'Shakisha igenamiterere…',
    'admin.nav.policies': 'Politiki',
    'admin.section.policies.title': "Politiki n'amategeko",
    'admin.section.policies.subtitle': 'Amategeko yose agenga urusobe',
    'admin.section.policies.search': 'Shakisha politiki…',
    'admin.nav.system_health': 'Ubuzima bwa sisitemu',
    'admin.section.system_health.title': 'Ubuzima bwa sisitemu',
    'admin.section.system_health.subtitle':
        "Kugenzura ibimenyetso by'urusobe, hamwe n'inama",
    'admin.section.system_health.search':
        'Shakisha ikimenyetso ukoresheje izina cyangwa uko gihagaze…',
    'admin.nav.engines': 'Moteri',
    'admin.section.engines.title': 'Moteri',
    'admin.section.engines.subtitle':
        "Uko urubuga rufata ibyemezo: ingano, kongera gutera inkunga, umurongo, imbibi z'abafatanyabikorwa, igabana rya CPA n'imirongo y'igihombo",
    'admin.section.engines.search': 'Shakisha ibyinjizwa…',
    'admin.design.tabs': 'Ibice bya {section}',
    'admin.design.col.note': 'Inoti',
    'admin.design.col.issuer': 'Uwayitanze',
    'admin.design.col.state': 'Imiterere',
    'admin.design.col.yield': 'Inyungu',
    'admin.design.col.term': 'Igihe',
    'admin.design.col.funded': 'Ayabonetse',
    'admin.design.col.raised': 'Ayakusanyijwe',
    'admin.design.col.raise': 'Ikusanya',
    'admin.design.col.progress': 'Aho bigeze',
    'admin.design.col.closes': 'Irangira',
    'admin.design.col.orders': 'Ibisabwa',
    'admin.design.col.avg_ask': 'Igiciro gisabwa ugereranyije',
    'admin.design.col.spread': 'Ikinyuranyo',
    'admin.design.col.volume_30d': "Ingano y'iminsi 30",
    'admin.design.col.trades': 'Ubucuruzi',
    'admin.design.col.business': 'Ikigo',
    'admin.design.col.outstanding': 'Ibisigaye kwishyurwa',
    'admin.design.col.days_late': "Iminsi y'ubukererwe",
    'admin.design.col.risk': 'Ingaruka',
    'admin.design.col.default_probability': 'Amahirwe yo kunanirwa',
    'admin.design.col.applicant': 'Usaba',
    'admin.design.col.document': 'Inyandiko',
    'admin.design.col.checks': 'Igenzura ryikora',
    'admin.design.col.decision': 'Icyemezo',
    'admin.design.col.reference': 'Indango',
    'admin.design.col.user': 'Ukoresha',
    'admin.design.col.type': 'Ubwoko',
    'admin.design.col.amount': 'Amafaranga',
    'admin.design.col.compliance': 'Kubahiriza amategeko',
    'admin.design.col.sector': 'Urwego',
    'admin.design.col.band': 'Icyiciro',
    'admin.design.col.change': 'Impinduka',
    'admin.design.col.reason': 'Impamvu',
    'admin.design.col.why': 'Impamvu',
    'admin.design.col.ask': 'Icyo basaba',
    'admin.design.col.deferred': 'Ayasubitswe',
    'admin.design.col.opened': 'Byafunguwe',
    'admin.design.col.plan': 'Gahunda',
    'admin.design.col.due': 'Itariki yo kwishyura',
    'admin.design.col.decided': 'Byafashweho icyemezo',
    'admin.design.col.investor': 'Umushoramari',
    'admin.design.col.tier': "Urwego rw'abanyamuryango",
    'admin.design.col.monthly': 'Buri kwezi',
    'admin.design.col.min_interest': 'Inyungu ntoya',
    'admin.design.col.bands': 'Ibyiciro',
    'admin.design.col.filled_mtd': 'Ibyuzuye uku kwezi',
    'admin.design.col.stream': "Inkomoko y'amafaranga",
    'admin.design.col.limit': 'Imbibi',
    'admin.design.col.used': 'Byakoreshejwe',
    'admin.design.col.cap': 'Igisenge',
    'admin.design.col.share': 'Igice cyakoreshejwe',
    'admin.design.col.rate': 'Igipimo',
    'admin.design.col.paid_by': 'Byishyurwa na',
    'admin.design.col.collected': 'Ayakiriwe',
    'admin.design.col.position': 'Umwanya',
    'admin.design.col.updated': 'Byavuguruwe',
    'admin.design.col.entry': "Inyandiko y'ibaruramari",
    'admin.design.col.account': 'Konti',
    'admin.design.col.debit': 'Ayasohotse',
    'admin.design.col.credit': 'Ayinjiye',
    'admin.design.col.posted': 'Byanditswe',
    'admin.design.col.rule': 'Itegeko',
    'admin.design.col.fires_when': 'Rikora iyo',
    'admin.design.col.timing': "Igihe n'inzira",
    'admin.design.col.template': 'Icyitegererezo',
    'admin.design.col.channel': 'Inzira',
    'admin.design.col.campaign': 'Ubukangurambaga',
    'admin.design.col.audience': 'Abagenewe',
    'admin.design.col.sent': 'Byoherejwe',
    'admin.design.col.message': 'Ubutumwa',
    'admin.design.col.recipient': 'Uwakiriye',
    'admin.design.col.lesson': 'Isomo',
    'admin.design.col.app': 'Porogaramu',
    'admin.design.col.status': 'Uko bihagaze',
    'admin.design.col.assigned_cpa': 'CPA wahawe',
    'admin.design.col.variance': 'V_total / D',
    'admin.design.col.signed': 'Umukono',
    'admin.design.col.period': 'Igihe',
    'admin.design.col.audit_partner': 'Umufatanyabikorwa mu igenzura',
    'admin.design.col.features': 'Ibiranga',
    'admin.design.col.entity': 'Ikintu',
    'admin.design.col.input': 'Icyinjizwa',
    'admin.design.col.value': 'Agaciro',
    'admin.design.col.source': 'Aho biva',
    'admin.design.col.signal': 'Ikimenyetso',
    'admin.design.col.reading': 'Igipimo',
    'admin.design.col.recommendation': 'Inama',
    'admin.design.notes.kpi.total': 'Inoti zose',
    'admin.design.notes.kpi.live': 'Ikusanya rikomeje',
    'admin.design.notes.kpi.repaying': 'Iziri kwishyurwa',
    'admin.design.notes.kpi.raised': 'Igishoro cyakusanyijwe',
    'admin.design.notes.tab.all': 'Zose',
    'admin.design.notes.tab.live': 'Zikomeje',
    'admin.design.notes.tab.repaying': 'Ziri kwishyurwa',
    'admin.design.notes.tab.matured': 'Zarangiye',
    'admin.design.notes.tab.failed': 'Zananiwe',
    'admin.design.notes.notes.title': 'Inoti',
    'admin.design.notes.notes.empty': 'Nta noti zo kwerekana ubu.',
    'admin.design.primary_market.kpi.active': 'Ikusanya rikora',
    'admin.design.primary_market.kpi.active.sub': 'Ubu riri gukora',
    'admin.design.primary_market.kpi.in_flight': 'Igishoro kiri mu nzira',
    'admin.design.primary_market.kpi.in_flight.sub': 'Ayakusanyijwe kugeza ubu',
    'admin.design.primary_market.kpi.fill': 'Ikigereranyo cyo kuzura',
    'admin.design.primary_market.kpi.fill.sub': "By'intego",
    'admin.design.primary_market.kpi.completion': 'Igipimo cyo kurangiza',
    'admin.design.primary_market.kpi.completion.sub': 'Bigera ku ntego',
    'admin.design.primary_market.trend.title': 'Uko inkunga yiyongera',
    'admin.design.primary_market.trend.empty': 'Nta nkunga yo kugaragaza ubu.',
    'admin.design.primary_market.raises.title': 'Ikusanya rikomeje',
    'admin.design.primary_market.raises.empty':
        'Nta kusanya rikomeje ryo kwerekana ubu.',
    'admin.design.secondary_market.kpi.volume': "Ingano y'uyu munsi",
    'admin.design.secondary_market.kpi.orders': 'Ibisabwa bifunguye',
    'admin.design.secondary_market.kpi.orders.sub': 'Biri ku isoko',
    'admin.design.secondary_market.kpi.trades': 'Ubucuruzi bwose',
    'admin.design.secondary_market.kpi.trades.sub': 'Kuva mu ntangiriro',
    'admin.design.secondary_market.kpi.tradable': 'Inoti zicuruzwa',
    'admin.design.secondary_market.kpi.tradable.sub':
        'Ziri kwishyurwa · zuzuye',
    'admin.design.secondary_market.activity.title': "Ibikorwa by'ubucuruzi",
    'admin.design.secondary_market.activity.empty':
        'Nta bucuruzi bwo kugaragaza ubu.',
    'admin.design.secondary_market.market.title': 'Isoko kuri buri noti',
    'admin.design.secondary_market.market.sub':
        "Ibisabwa byo kugurisha bifunguye, igiciro gisabwa ugereranyije n'agaciro fatizo, n'ingano y'iminsi 30.",
    'admin.design.secondary_market.market.empty':
        'Nta bisabwa bifunguye byo kwerekana ubu.',
    'admin.design.risk.kpi.exposure': "Ingano y'ingaruka",
    'admin.design.risk.kpi.late': 'Ubwishyu bwakerewe',
    'admin.design.risk.kpi.distressed': 'Inoti ziri mu kaga',
    'admin.design.risk.kpi.default_probability':
        "Ikigereranyo cy'amahirwe yo kunanirwa",
    'admin.design.risk.distressed.title':
        "Inoti ziri mu kaga · imburira y'ibanze",
    'admin.design.risk.distressed.empty':
        'Nta noti ziri mu kaga zo kwerekana ubu.',
    'admin.design.reports.kpi.published': 'Byagenzuwe kandi byatangajwe',
    'admin.design.reports.kpi.awaiting': 'Bitegereje igenzura',
    'admin.design.reports.kpi.breached': 'SLA yarenze',
    'admin.design.reports.kpi.on_time': "Igipimo cy'igenzura ku gihe",
    'admin.design.reports.field.title': 'Igenzura ryihuse ku butaka',
    'admin.design.reports.field.sub':
        "Buri gusura ku butaka n'umugenzuzi wabyo (CPA), ikinyuranyo, igihe byemejwe n'ibyagaragaye byasinywe.",
    'admin.design.reports.field.empty':
        'Nta gikorwa cya ISRS 4400 kiranditswe.',
    'admin.design.reports.monthly.title':
        "Ikurikirana ry'igenzura rya buri kwezi",
    'admin.design.reports.monthly.sub':
        "Raporo z'imari buri kwezi, zigenzurirwa aho ubucuruzi bukorera bitarenze ku itariki ya 7.",
    'admin.design.reports.monthly.empty': 'Nta kiri muri iki gice.',
    'admin.design.compliance.kpi.kyc_completion': 'KYC yarangiye',
    'admin.design.compliance.kpi.aml_alerts': 'Imburira za AML',
    'admin.design.compliance.kpi.sanctions': "Abahuye n'ibihano",
    'admin.design.compliance.kpi.pending': 'Isuzuma ritegerejwe',
    'admin.design.compliance.queue.title': "Umurongo w'isuzuma rya KYC",
    'admin.design.compliance.queue.empty':
        'Nta suzuma rya KYC ryo kwerekana ubu.',
    'admin.design.payments.kpi.deposits': 'Amafaranga yashyizwe',
    'admin.design.payments.kpi.withdrawals': 'Amafaranga yabikujwe',
    'admin.design.payments.kpi.disbursed': 'Ayatanzwe',
    'admin.design.payments.kpi.disbursed.sub': 'Ku bigo',
    'admin.design.payments.kpi.awaiting': 'Bitegereje kwemezwa',
    'admin.design.payments.kpi.awaiting.sub': 'Biri ku murongo',
    'admin.design.payments.movement.title': 'Uko amafaranga agenda',
    'admin.design.payments.movement.empty':
        'Nta mafaranga yagiye yo kugaragaza ubu.',
    'admin.design.payments.approvals.title': 'Umurongo wo kwemeza',
    'admin.design.payments.approvals.empty':
        'Nta kintu gitegereje kwemezwa cyo kwerekana ubu.',
    'admin.design.ratings.kpi.rated': 'Ibigo byahawe amanota',
    'admin.design.ratings.kpi.changed': 'Icyiciro cyahindutse',
    'admin.design.ratings.kpi.trending_down': 'Biri kumanuka',
    'admin.design.ratings.kpi.pinned': "Byashyizweho n'abakozi",
    'admin.design.ratings.tab.changed': 'Icyiciro cyahindutse',
    'admin.design.ratings.tab.trending_down': 'Biri kumanuka',
    'admin.design.ratings.tab.all': 'Ibigo byose',
    'admin.design.ratings.book.title': "Igitabo cy'amanota",
    'admin.design.ratings.book.empty': 'Nta manota yo kwerekana ubu.',
    'admin.design.deferrals.kpi.this_month': 'Gusubika uku kwezi',
    'admin.design.deferrals.kpi.live': 'Birakomeje',
    'admin.design.deferrals.kpi.deferred': 'Ayasubitswe',
    'admin.design.deferrals.kpi.extra_paid': 'Inyongera yahawe abashoramari',
    'admin.design.deferrals.awaiting.title': 'Bitegereje icyemezo cyawe',
    'admin.design.deferrals.awaiting.empty':
        'Nta gahunda itegereje icyemezo yo kwerekana ubu.',
    'admin.design.deferrals.live_plans.title': 'Gahunda zikomeje',
    'admin.design.deferrals.live_plans.sub': 'Zemejwe kandi zirakomeje.',
    'admin.design.deferrals.live_plans.empty':
        'Nta gahunda zikomeje zo kwerekana ubu.',
    'admin.design.deferrals.decided.title': 'Byafashweho icyemezo',
    'admin.design.deferrals.decided.empty': 'Nta byemezo byo kwerekana ubu.',
    'admin.design.plus.tab.tiers': 'Inzego za Plus',
    'admin.design.plus.tab.institutions': 'Ibigo',
    'admin.design.plus.tab.all': 'Abashoramari bose',
    'admin.design.plus.subscribers.title': 'Abiyandikishije kuri Rozine Plus',
    'admin.design.plus.subscribers.empty':
        'Nta biyandikishije bo kwerekana ubu.',
    'admin.design.plus.orders.title': 'Amabwiriza ahoraho',
    'admin.design.plus.orders.sub': 'Kugura kwikora kwa konti za Plus.',
    'admin.design.plus.orders.empty': 'Nta mabwiriza ahoraho yo kwerekana ubu.',
    'admin.design.finance.kpi.revenue': 'Amafaranga yinjiye yose',
    'admin.design.finance.kpi.primary': "Isoko ry'ibanze",
    'admin.design.finance.kpi.investor_fees': "Amafaranga y'abashoramari",
    'admin.design.finance.kpi.secondary': 'Isoko rya kabiri',
    'admin.design.finance.kpi.revenue.sub': 'Inzira zose, kuva mu ntangiriro',
    'admin.design.finance.kpi.primary.sub':
        "Amafaranga y'urubuga, umugabane wa Rozine",
    'admin.design.finance.kpi.investor_fees.sub':
        "Ibyiciro by'amafaranga ku nyungu",
    'admin.design.finance.kpi.secondary.sub': 'Amafaranga yo kongera kugurisha',
    'admin.design.finance.tab.streams': "Inkomoko z'amafaranga",
    'admin.design.finance.tab.ledger': "Igitabo cy'imari n'ubwishyu",
    'admin.design.finance.raise.title': "Isoko ry'ibanze",
    'admin.design.finance.raise.sub':
        'Icyo ibigo byishyura kugira ngo bishake imari',
    'admin.design.finance.raise.empty':
        'Nta mafaranga yo gushaka imari yo kwerekana ubu.',
    'admin.design.finance.earnings.title': "Amafaranga y'abashoramari",
    'admin.design.finance.earnings.sub':
        'Icyo abashoramari bishyura ku nyungu zabo',
    'admin.design.finance.earnings.empty':
        'Nta mafaranga ku nyungu yo kwerekana ubu.',
    'admin.design.finance.trades.title': 'Isoko rya kabiri',
    'admin.design.finance.trades.sub': "Icyo kuva kare n'ubucuruzi byishyura",
    'admin.design.finance.trades.empty':
        "Nta mafaranga y'ubucuruzi yo kwerekana ubu.",
    'admin.design.finance.residual.title': "Kugaruza ikiguzi n'ibisigaye",
    'admin.design.finance.residual.sub':
        'Imirongo mito. Ntugire icyo uteganya kuri yo.',
    'admin.design.finance.residual.empty':
        'Nta mirongo isigaye yo kwerekana ubu.',
    'admin.design.finance.ledger.title': "Igitabo cy'imari n'ubwishyu",
    'admin.design.finance.ledger.empty':
        "Nta nyandiko z'ibaruramari zo kwerekana ubu.",
    'admin.design.messaging.tab.automations': 'Ibikora ubwabyo',
    'admin.design.messaging.tab.templates': 'Ibyitegererezo',
    'admin.design.messaging.tab.campaigns': 'Ubukangurambaga',
    'admin.design.messaging.tab.history': 'Amateka',
    'admin.design.messaging.automations.title': 'Ibikora ubwabyo',
    'admin.design.messaging.automations.empty':
        'Nta bikora ubwabyo byo kwerekana ubu.',
    'admin.design.messaging.templates.title': 'Ibyitegererezo',
    'admin.design.messaging.templates.empty':
        'Nta byitegererezo byo kwerekana ubu.',
    'admin.design.messaging.campaigns.title': 'Ubukangurambaga',
    'admin.design.messaging.campaigns.empty':
        'Nta bukangurambaga bwo kwerekana ubu.',
    'admin.design.messaging.history.title': 'Amateka',
    'admin.design.messaging.history.empty': 'Nta butumwa bwo kwerekana ubu.',
    'admin.messaging.col.recipient': 'Uwakiriye',
    'admin.messaging.col.message': 'Ubutumwa',
    'admin.messaging.col.category': 'Icyiciro',
    'admin.messaging.col.why': 'Impamvu bwoherejwe',
    'admin.messaging.col.via': 'Binyuze',
    'admin.messaging.col.when': 'Igihe',
    'admin.messaging.source.automation': 'Byikora',
    'admin.messaging.status.delivered': 'Mu butumwa bwa porogaramu',
    'admin.messaging.status.sent': 'Imeri yoherejwe',
    'admin.messaging.status.failed': 'Imeri yanze',
    'admin.messaging.channel.in_app': 'Muri porogaramu',
    'admin.messaging.channel.mail': 'Imeri',
    'admin.messaging.category.onboarding': 'Kwiyandikisha',
    'admin.messaging.category.wallet': 'Ikofi',
    'admin.messaging.category.fundraising': 'Gukusanya imari',
    'admin.messaging.category.portfolio': 'Ishoramari',
    'admin.messaging.category.reporting': 'Raporo',
    'admin.messaging.category.security': 'Umutekano',
    'admin.messaging.category.underwriting': 'Isesengura',
    'admin.messaging.category.payments': 'Kwishyura',
    'admin.messaging.category.audits': 'Igenzura',
    'admin.messaging.category.support': 'Ubufasha',
    'admin.messaging.category.general': 'Rusange',
    'admin.messaging.audience.investor': 'Abashoramari',
    'admin.messaging.audience.business': 'Ibigo',
    'admin.messaging.audience.auditor': 'Abafatanyabikorwa mu igenzura',
    'admin.messaging.audience.everyone': 'Abafite konti bose',
    'admin.messaging.trigger.verification.approved':
        'Ishami ry’iyubahirizwa ryemeje igenzura ry’umwirondoro',
    'admin.messaging.trigger.verification.rejected':
        'Ishami ry’iyubahirizwa risubije igenzura ry’umwirondoro',
    'admin.messaging.trigger.wallet.deposit_received':
        'Utanga serivisi yemeje kubitsa',
    'admin.messaging.trigger.wallet.withdrawal_sent':
        'Amafaranga yabikujwe yoherejwe',
    'admin.messaging.trigger.wallet.withdrawal_returned':
        'Amafaranga yabikujwe agarutse mu gikapu',
    'admin.messaging.trigger.primary.refunded':
        'Ikusanya rirangiye ritageze ku ntego',
    'admin.messaging.trigger.primary.report_published':
        'Raporo y’ukwezi y’ikigo ufitemo imigabane ifunzwe',
    'admin.messaging.trigger.primary.raise_funded':
        'Ikusanya umushoramari yiyemeje ryuzuye',
    'admin.messaging.trigger.primary.holding_issued':
        'Impapuro z’umwenda zihawe umushoramari',
    'admin.messaging.trigger.security.password_changed':
        'Ijambo ry’ibanga rihinduwe',
    'admin.messaging.trigger.security.payout_account_linked':
        'Konti yo kwakiriraho ihujwe',
    'admin.messaging.trigger.security.payout_account_unlinked':
        'Konti yo kwakiriraho ikuweho',
    'admin.messaging.trigger.security.new_device': 'Igikoresho gishya cyinjiye',
    'admin.messaging.trigger.business.deposit_received':
        'Utanga serivisi yemeje kubitsa kw’ikigo',
    'admin.messaging.trigger.business.application_approved':
        'Ubusabe bw’inkunga bwemejwe',
    'admin.messaging.trigger.business.raise_expired':
        'Ikusanya rirangiye ritageze ku ntego',
    'admin.messaging.trigger.business.raise_funded': 'Ikusanya ryuzuye',
    'admin.messaging.trigger.business.disbursement_sent':
        'Amafaranga yakusanyijwe yoherejwe',
    'admin.messaging.trigger.business.withdrawal_sent':
        'Amafaranga ikigo cyabikuje yoherejwe',
    'admin.messaging.trigger.business.withdrawal_returned':
        'Amafaranga ikigo cyabikuje agarutse',
    'admin.messaging.trigger.business.report_sealed':
        'Umugenzuzi afunze raporo y’ukwezi',
    'admin.messaging.trigger.business.report_review_closing':
        'Igihe cyo gusinya cyangwa kujuririra raporo kirarangiye',
    'admin.messaging.trigger.business.reporting_window_open':
        'Igenzura ry’ukwezi ritangiye ku kigo gifite inoti ikora',
    'admin.messaging.trigger.auditor.accreditation_approved':
        'Uruhushya rwemejwe',
    'admin.messaging.trigger.auditor.accreditation_rejected':
        'Uruhushya ruhakanywe',
    'admin.messaging.trigger.auditor.job_offered': 'Akazi k’igenzura gatanzwe',
    'admin.messaging.trigger.auditor.report_reminder':
        'Abakozi bibutsa umugenzuzi raporo',
    'admin.messaging.trigger.auditor.accreditation_renewal_due':
        'Igihe cyo kuvugurura uruhushya kirageze',
    'admin.messaging.trigger.support.complaint_answered':
        'Ubufasha busubije ikirego',
    'admin.messaging.trigger.support.complaint_resolved': 'Ikirego gikemutse',
    'admin.messaging.automations.live': 'Automatisation {count} zikora',
    'admin.messaging.automations.fire':
        'Buri imwe yikora iyo icyayitera kibaye',
    'admin.messaging.automations.group': 'Automatisation · {audience}',
    'admin.design.col.on': 'Irakora',
    'admin.messaging.timing': 'Ako kanya · {channels}',
    'admin.messaging.state.live': 'Irakora',
    'admin.messaging.automations.sub':
        'Buri butumwa bwoherezwa ubwabwo iyo igikorwa cyabwo cyanditswe. Ntibirahindurwa muri konsole.',
    'admin.messaging.templates.sub':
        'Imeyili Rozine yohereza. Ntizirahindurwa muri konsole.',
    'admin.messaging.rule.funds_disbursed': 'Amafaranga yatanzwe',
    'admin.messaging.rule.verification_approved': 'Kwemezwa byemejwe',
    'admin.messaging.rule.raise_fully_funded': 'Ubukusanyamari bwuzuye',
    'admin.messaging.template.account.new_device_sign_in':
        'Kwinjira hakoreshejwe igikoresho gishya',
    'admin.messaging.template.account.one_time_code': 'Kode y’igihe kimwe',
    'admin.messaging.template.account.reset_password':
        'Guhindura ijambo ry’ibanga',
    'admin.messaging.template.account.verify_email':
        'Kwemeza aderesi ya imeyili',
    'admin.messaging.template.auditor.accreditation_decided':
        'Icyemezo ku kwemererwa',
    'admin.messaging.template.auditor.job_offered': 'Akazi k’igenzura katanzwe',
    'admin.messaging.template.business.application_decided':
        'Icyemezo ku busabe',
    'admin.messaging.template.business.disbursement_sent':
        'Amafaranga yatanzwe',
    'admin.messaging.template.business.raise_expired':
        'Igihe cyo gukusanya cyarangiye',
    'admin.messaging.template.business.repayment_reminder':
        'Kwibutsa kwishyura',
    'admin.messaging.template.business.reporting_window_open':
        'Kwibutsa raporo y’ukwezi',
    'admin.messaging.template.investor.investment_confirmed':
        'Ishoramari ryemejwe',
    'admin.messaging.template.investor.payment_delayed': 'Kwishyura byatinze',
    'admin.messaging.template.investor.payout_received': 'Amafaranga yakiriwe',
    'admin.messaging.template.investor.raise_expired':
        'Igihe cyo gukusanya cyarangiye',
    'admin.messaging.template.investor.report_published': 'Raporo yasohotse',
    'admin.messaging.template.investor.verification_decided':
        'Icyemezo ku igenzura ry’umwirondoro',
    'admin.messaging.template.investor.wallet_transfer_completed':
        'Kohereza mu ikofi byarangiye',
    'admin.messaging.no_match': 'Nta butumwa buhuye n’ibyo washakishije.',
    'admin.messaging.older': 'Ubutumwa bwa kera',
    'admin.messaging.newest': 'Ubutumwa bushya',
    'admin.design.academies.kpi.completed': 'Amasomo yarangiye',
    'admin.design.academies.kpi.live': 'Amasomo ari ku murongo',
    'admin.design.col.track': 'Icyiciro',
    'admin.design.col.topics': 'Ingingo',
    'admin.design.academies.kpi.tracks': 'Ibyiciro',
    'admin.design.academies.kpi.checks': 'Ibibazo byihuse',
    'admin.design.academies.kpi.completed.sub': 'Bibikwa ku gikoresho cy’uwiga',
    'admin.design.academies.lessons.sub':
        'Buri ngingo irangizwa n’ikibazo kimwe cyihuse. Aho umuntu ageze bibikwa ku gikoresho cye, bityo amasomo yarangiye n’amanota ntibigaragara hano.',
    'admin.design.academies.tab.investor': "Ishuri ry'abashoramari",
    'admin.design.academies.tab.business': "Ishuri ry'ibigo",
    'admin.design.academies.tab.auditor': "Ishuri ry'abagenzuzi",
    'admin.design.academies.tab.all': 'Byose',
    'admin.design.academies.lessons.live': '{count} zikora',
    'admin.design.academies.lessons.live_in': '{count} zikora muri {app}',
    'admin.design.academies.app.investor': 'porogaramu y’Abashoramari',
    'admin.design.academies.app.business': 'porogaramu y’Ibigo',
    'admin.design.academies.app.auditor': 'porogaramu y’Abagenzuzi',
    'admin.design.academies.state.live': 'Irakora',
    'admin.design.academies.lessons.title': 'Amasomo',
    'admin.design.academies.lessons.empty': 'Nta masomo yo kwerekana ubu.',
    'admin.design.app_control.apps.title': 'Porogaramu',
    'admin.design.app_control.apps.sub':
        "Porogaramu z'abashoramari, ibigo n'abagenzuzi n'ibiziranga.",
    'admin.design.app_control.apps.empty':
        'Nta igenamiterere rya porogaramu ryo kwerekana ubu.',
    'admin.design.app_control.app.investor': "Porogaramu y'Abashoramari",
    'admin.design.app_control.app.business': "Porogaramu y'Ubucuruzi",
    'admin.design.app_control.app.auditor': "Porogaramu y'Abagenzuzi",
    'admin.design.app_control.online': 'Irakora',
    'admin.design.app_control.feature.sign_ups': 'Kwiyandikisha gushya',
    'admin.design.app_control.feature.secondary_market': 'Isoko rya kabiri',
    'admin.design.app_control.feature.academy': "Ishuri ry'Abashoramari",
    'admin.design.app_control.feature.note_creation': 'Gukora RNP',
    'admin.design.app_control.feature.monthly_reports': 'Raporo za buri kwezi',
    'admin.design.app_control.feature.auditor_sign_ups':
        "Kwiyandikisha kw'abagenzuzi",
    'admin.design.app_control.feature.auto_dispatch':
        'Kohereza mu buryo bwikora muri km 30',
    'admin.design.app_control.feature.flash_audits':
        'Igenzura ryihuse (amasaha 24)',
    'admin.design.app_control.state.live': 'Irakora',
    'admin.design.app_control.state.not_built': 'Ntirarakorwa',
    'admin.design.app_control.sandbox.title': 'Imbibi za sandbox',
    'admin.design.app_control.sandbox.sub':
        'Igice cyakoreshejwe cya buri mbibi za sandbox ya CMA. Nta kirenze imbibi cyemerwa.',
    'admin.design.app_control.sandbox.empty': 'Nta mikoreshereze irerekanwa.',
    'admin.design.app_control.sandbox.businesses': 'Ibigo bifite manda ikora',
    'admin.design.app_control.sandbox.investors': 'Abashoramari bemejwe',
    'admin.design.app_control.sandbox.principal': 'Igishoro gisigaye',
    'admin.design.app_control.content.title': 'Ibikubiyemo',
    'admin.design.app_control.content.sub':
        "Ibiri muri porogaramu z'abashoramari, ibigo n'abagenzuzi.",
    'admin.design.app_control.content.empty':
        'Nta bikubiyemo byo kwerekana ubu.',
    'admin.design.app_control.type.business': 'Ubucuruzi',
    'admin.design.app_control.unrated': 'Ntirahabwa amanota',
    'admin.design.app_control.content_state.active': 'Irakora',
    'admin.design.app_control.content_state.pending': 'Bitegereje kwemezwa',
    'admin.design.engines.tab.sizing': 'Ingano',
    'admin.design.engines.tab.refinance': 'Kongera gutera inkunga',
    'admin.design.engines.tab.queue': 'Umurongo',
    'admin.design.engines.tab.floors': "Imbibi z'abafatanyabikorwa",
    'admin.design.engines.tab.cpa': 'Igabana rya CPA',
    'admin.design.engines.tab.loss': "Imirongo y'igihombo",
    'admin.design.engines.sizing.title': 'Ingano',
    'admin.design.engines.sizing.empty':
        "Nta byinjizwa by'ingano byo kwerekana ubu.",
    'admin.design.engines.refinance.title': 'Kongera gutera inkunga',
    'admin.design.engines.refinance.empty':
        'Nta byinjizwa byo kongera gutera inkunga byo kwerekana ubu.',
    'admin.design.engines.queue.title': 'Umurongo',
    'admin.design.engines.queue.empty':
        "Nta byinjizwa by'umurongo byo kwerekana ubu.",
    'admin.design.engines.floors.title': "Imbibi z'abafatanyabikorwa",
    'admin.design.engines.floors.empty':
        "Nta mbibi z'abafatanyabikorwa zo kwerekana ubu.",
    'admin.design.engines.cpa.title': 'Igabana rya CPA',
    'admin.design.engines.cpa.empty':
        "Nta byinjizwa by'igabana rya CPA byo kwerekana ubu.",
    'admin.design.engines.loss.title': "Imirongo y'igihombo",
    'admin.design.engines.loss.empty':
        "Nta mirongo y'igihombo yo kwerekana ubu.",
    'admin.design.policies.tab.fees': 'Amafaranga',
    'admin.design.policies.tab.underwriting': "Isuzuma ry'inguzanyo",
    'admin.design.policies.tab.business': 'Ibisabwa ku bigo',
    'admin.design.policies.tab.investor': 'Ibisabwa ku bashoramari',
    'admin.design.policies.tab.kyc': 'Ibisabwa bya KYC',
    'admin.design.policies.tab.penalties': "Kubahiriza n'ibihano",
    'admin.design.policies.tab.limits': 'Imbibi',
    'admin.design.policies.fees.sub':
        "Icyo buri ruhande yishyura Rozine, n'umugabane ujya ku mugenzuzi w'ikigo.",
    'admin.design.policies.underwriting.sub':
        "Amategeko agena niba ishakishwa ry'imari ryemewe n'ingingo zaryo.",
    'admin.design.policies.business.sub':
        'Abemerewe gushaka imari kuri Rozine.',
    'admin.design.policies.investor.sub':
        'Abemerewe gushora imari kuri Rozine.',
    'admin.design.policies.kyc.sub':
        'Icyo buri ruhande rugomba gutanga. Igisabwa kibuza ibikorwa kugeza gitanzwe; igitakusanywa ntikigeze gisabwa.',
    'admin.design.policies.penalties.sub':
        'Uko Rozine ifata ubwishyu bukererewe, kunanirwa kwishyura no kongera kugenzura.',
    'admin.design.policies.limits.sub':
        "Imbibi z'ibikorwa muri porogaramu zombi.",
    'admin.design.policies.rules.empty': 'Nta mategeko yo kwerekana ubu.',
    'admin.design.system_health.signals.title': 'Ibimenyetso',
    'admin.design.system_health.signals.empty':
        'Nta bimenyetso byo kwerekana ubu.',
    'admin.nav.group.oversight': 'Igenzura',
    'admin.nav.group.engagement': 'Imikoranire',
    'admin.pending.title': 'Nta kintu kirahari',
    'admin.pending.body':
        "Iki gice cya konsole ntikirahuzwa. Kizerekana amakuru akimara kwandikwa n'urubuga.",
    'admin.nav.today': 'Imbonerahamwe',
    'admin.nav.businesses': 'Ibigo',
    'admin.nav.investors': 'Abashoramari',
    'admin.nav.auditors': 'Abagenzuzi',
    'admin.nav.applications': 'Ubusabe',
    'admin.nav.disbursements': 'Kwishyura',
    'admin.nav.ledger': "Igitabo cy'imari",
    'admin.nav.staff': "Abakozi n'inshingano",
    'admin.nav.events': "Ibikorwa n'igenzura",
    'admin.section.about': 'Ibyerekeye {title}',
    'admin.section.today.title': "Ikigo cy'ibikorwa bya Rozine",
    'admin.section.today.subtitle': 'Uko urubuga rumeze mu ncamake',
    'admin.section.today.search': 'Shakisha ibigo, inyandiko, abashoramari…',
    'admin.section.applications.title': "Umurongo w'ubusabe",
    'admin.section.applications.subtitle':
        'Suzuma kandi wemeze ubusabe bushya bwa RNP',
    'admin.section.applications.search':
        "Shakisha ukoresheje umutwe w'urupapuro cyangwa nimero y'ubusabe",
    'admin.section.disbursements.title': 'Kwishyura',
    'admin.section.disbursements.subtitle':
        "Amafaranga yemejwe ategereje kwishyurwa. Hejuru y'urugero, abantu babiri batandukanye bagomba kwemeza buri kwishyura.",
    'admin.section.disbursements.search':
        'Shakisha ukoresheje ikigo, inyandiko cyangwa indango…',
    'admin.section.businesses.title': "Urutonde rw'ibigo",
    'admin.section.businesses.subtitle': 'Ibigo byose byemejwe biri ku rubuga',
    'admin.section.businesses.search':
        'Shakisha ibigo ukoresheje izina, urwego, imiterere…',
    'admin.section.investors.title': "Urutonde rw'abashoramari",
    'admin.section.investors.subtitle': "Gucunga konti z'abashoramari na KYC",
    'admin.section.investors.search':
        'Shakisha abashoramari ukoresheje izina cyangwa KYC…',
    'admin.section.auditors.title': "Urusobe rw'abagenzuzi",
    'admin.section.auditors.subtitle':
        "Abafatanyabikorwa ba ICPAR, impushya zabo n'uko bahagaze",
    'admin.section.auditors.search':
        'Shakisha ukoresheje izina, ikigo cyangwa akarere…',
    'admin.section.staff.title': "Abakozi n'inshingano",
    'admin.section.staff.subtitle':
        "Konti z'abakozi, inshingano zabo n'uko konti zimeze",
    'admin.section.staff.search':
        'Shakisha abakozi ukoresheje izina cyangwa imeli…',
    'admin.section.ledger.title': "Igitabo cy'imari",
    'admin.section.ledger.subtitle':
        "Buri rujya n'uruza rw'amafaranga, uhereye ku rushya. Fungura kimwe urebe ibyanditswe.",
    'admin.section.ledger.search':
        'Shakisha mu gitabo ukoresheje ubwoko, uruhande, indango…',
    'admin.section.events.title': "Ibikorwa n'igenzura",
    'admin.section.events.subtitle':
        'Inyandiko idahinduka ya buri gikorwa — tondeka ukoresheje uwakoze, igikorwa cyangwa ikintu',
    'admin.section.events.search':
        'Shakisha ukoresheje ukoresha, igikorwa, ikintu…',
    'admin.search.label': 'Shakisha kuri uru rupapuro',
    'admin.search.empty_title': 'Nta bihuye kuri uru rupapuro',
    'admin.search.empty_body':
        'Nta kintu muri {section} gihuye na “{term}”. Iri shakisha rireba kuri uru rupapuro gusa.',
    'admin.account.open': 'Konti, {name}',
    'admin.account.close': 'Funga urutonde rwa konti',
    'admin.account.sign_out': 'Sohoka',
    'admin.role.analyst': 'Umusesenguzi',
    'admin.role.approver': 'Uwemeza',
    'admin.role.superadmin': 'Umuyobozi mukuru',
    'admin.role.access.analyst': 'Uburenganzira bwo gutondeka',
    'admin.role.access.approver': 'Uburenganzira bwo kwemeza',
    'admin.role.access.superadmin': 'Uburenganzira bwose',
    'admin.role.compliance': 'Kubahiriza amategeko',
    'admin.role.access.compliance': 'Uburenganzira bwo kubahiriza amategeko',
    'admin.role.treasury': 'Imari',
    'admin.role.access.treasury': "Uburenganzira bw'imari",
    'admin.kyc.intro':
        "Ibisabwa byo kugenzura umwirondoro by'abantu bashaka gushora imari. Fungura kimwe urebe inyandiko zacyo, hanyuma ucyemeze cyangwa ucyange ugaragaza impamvu.",
    'admin.kyc.tabs': "Imiterere y'igenzura",
    'admin.kyc.tab.submitted': 'Bitegereje gusuzumwa',
    'admin.kyc.tab.decided': 'Byafatiwe icyemezo',
    'admin.kyc.table': 'Ibisabwa byo kugenzura umwirondoro',
    'admin.kyc.queue': "Urutonde rw'isuzuma rya KYC",
    'admin.kyc.col.document': 'Inyandiko',
    'admin.kyc.col.applicant': 'Usaba',
    'admin.kyc.col.checks': 'Igenzura ryikora',
    'admin.kyc.submitted_ago': 'Byoherejwe {ago}',
    'admin.kyc.checks.passed': 'Igenzura rya KYC ryikora ryose ryatsinze',
    'admin.kyc.checks.screening':
        "Imyaka, nimero y'indangamuntu, inyandiko, ifoto · nta isuzuma ry'urutonde",
    'admin.kyc.check.age': 'Imyaka 18 cyangwa irenga',
    'admin.kyc.check.id_number': "Imiterere ya nimero y'indangamuntu",
    'admin.kyc.check.documents': "Amafoto y'inyandiko yoherejwe",
    'admin.kyc.check.selfie': 'Ifoto yafashwe',
    'admin.kyc.stats.aml': "Imenyesha ry'iyezandonke",
    'admin.kyc.stats.sanctions': "Abahuye n'ibihano",
    'admin.kyc.stats.not_connected': 'Ntibihujwe',
    'admin.kyc.stats.screening_sub': 'Isuzuma (KYC-R04) ntirirubakwa',
    'admin.kyc.col.status': 'Uko bihagaze',
    'admin.kyc.col.decision': 'Icyemezo',
    'admin.kyc.review': 'Gusuzuma',
    'admin.kyc.review_named': 'Gusuzuma {name}',
    'admin.kyc.stats.completion': 'KYC yarangiye',
    'admin.kyc.stats.completion_sub': '{verified} kuri {total} byemejwe',
    'admin.kyc.stats.percent': '{value}%',
    'admin.kyc.stats.pending': 'Ibitegereje isuzuma',
    'admin.kyc.stats.turnaround': 'Igihe kigereranyo amasaha {hours}',
    'admin.kyc.stats.turnaround_none': 'Nta cyemezo mu minsi 30',
    'admin.kyc.id_type.national_id': 'Indangamuntu',
    'admin.kyc.id_type.passport': 'Pasiporo',
    'admin.kyc.id_type.drivers_license': 'Uruhushya rwo gutwara',
    'admin.kyc.status.draft': 'Byongeye gufungurwa',
    'admin.kyc.status.submitted': 'Bitegereje',
    'admin.kyc.status.approved': 'Byemejwe',
    'admin.kyc.status.rejected': 'Byanzwe',
    'admin.kyc.empty.submitted': 'Nta bisabwa bitegereje gusuzumwa.',
    'admin.kyc.empty.decided': 'Nta cyemezo kirafatwa.',
    'admin.kyc.more': 'Erekana ibindi',
    'admin.kyc.drawer': 'Igisabwa cyo kugenzura umwirondoro',
    'admin.kyc.facts': 'Amakuru yoherejwe',
    'admin.kyc.fact.date_of_birth': "Itariki y'amavuko",
    'admin.kyc.fact.id_type': 'Inyandiko',
    'admin.kyc.fact.id_number': "Nimero y'inyandiko",
    'admin.kyc.fact.submitted_at': 'Byoherejwe',
    'admin.kyc.documents': 'Inyandiko',
    'admin.kyc.slot.front': "Imbere h'indangamuntu",
    'admin.kyc.slot.back': "Inyuma h'indangamuntu",
    'admin.kyc.slot.selfie': 'Ifoto yawe',
    'admin.kyc.document.replaced': 'Yasimbuwe',
    'admin.kyc.document.meta': '{name} · {size} KB',
    'admin.kyc.document.open': 'Kuramo {name}',
    'admin.kyc.document.download': 'Kuramo',
    'admin.kyc.document.current': "Inyandiko z'ubu",
    'admin.kyc.document.earlier': 'Izoherejwe mbere',
    'admin.kyc.document.view': 'Reba {slot}',
    'admin.kyc.document.show': 'Reba',
    'admin.kyc.document.view_file': 'Reba {name}',
    'admin.kyc.document.pdf': 'PDF',
    'admin.kyc.viewer.position': '{index} kuri {total}',
    'admin.kyc.viewer.new_tab': 'Fungura mu idirishya rishya',
    'admin.kyc.viewer.close': 'Funga aho kureba',
    'admin.kyc.viewer.previous': 'Inyandiko ibanza',
    'admin.kyc.viewer.next': 'Inyandiko ikurikira',
    'admin.kyc.decision': 'Icyemezo',
    'admin.kyc.history': 'Amateka',
    'admin.kyc.command.verification.save': 'Intambwe yabitswe',
    'admin.kyc.command.verification.upload': 'Inyandiko yashyizweho',
    'admin.kyc.command.verification.submit': 'Byoherejwe gusuzumwa',
    'admin.kyc.command.verification.approve':
        "Byemejwe n'ushinzwe kubahiriza amategeko",
    'admin.kyc.command.verification.reject':
        "Byanzwe n'ushinzwe kubahiriza amategeko",
    'admin.kyc.approve': 'Emeza',
    'admin.kyc.reject': 'Anga',
    'admin.kyc.stage.approve.title': 'Emeza uyu mwirondoro',
    'admin.kyc.stage.approve.body':
        'Ibi byemeza uyu muntu kandi bimufungurira kuba Umushoramari. Vuga ibyo wagenzuye.',
    'admin.kyc.stage.approve.cta': 'Emeza umwirondoro',
    'admin.kyc.stage.approve.placeholder':
        "urugero: Ifoto, nimero n'ifoto ye bihuye na nyir'konti",
    'admin.kyc.stage.reject.title': 'Anga iki gisabwa',
    'admin.kyc.stage.reject.body':
        'Uyu muntu abona impamvu yawe kandi ashobora gukosora amakuru ye.',
    'admin.kyc.stage.reject.cta': 'Anga igisabwa',
    'admin.kyc.stage.reject.placeholder':
        "urugero: Ifoto y'indangamuntu ntigaragara neza. Ohereza indi isobanutse.",
    'admin.nav.mail_testers': 'Abagerageza imeyili z’igerageza',
    'admin.section.mail_testers.title': 'Abagerageza imeyili z’igerageza',
    'admin.section.mail_testers.subtitle':
        'Abo urubuga rw’igerageza rushobora koherereza imeyili nyazo. Ku rubuga rw’igerageza gusa.',
    'admin.section.mail_testers.search':
        'Shakisha ugerageza ukoresheje imeyili…',
    'admin.mail_testers.intro':
        'Urubuga rw’igerageza rwohereza imeyili nyazo ku bagerageza bemewe gusa; izindi zirahagarikwa. Ongeraho hano aderesi nyayo y’ugerageza, ugaragaze impamvu. Buri gihinduka kibikwa mu izina ryawe.',
    'admin.mail_testers.server.title': 'Bemewe buri gihe kuri iyi seriveri',
    'admin.mail_testers.server.body':
        'Byashyizwe kuri seriveri y’igerageza. Bihindurirwe aho, si hano.',
    'admin.mail_testers.add.label': 'Ongeraho ugerageza',
    'admin.mail_testers.add.email': 'Aderesi ya imeyili y’ugerageza',
    'admin.mail_testers.add.placeholder': 'izina@urugero.com',
    'admin.mail_testers.add.cta': 'Ongeraho ugerageza',
    'admin.mail_testers.table': 'Abagerageza bavuzwe mu mazina',
    'admin.mail_testers.col.email': 'Imeyili',
    'admin.mail_testers.col.added_by': 'Yongeweho na',
    'admin.mail_testers.col.added_at': 'Yongeweho',
    'admin.mail_testers.col.actions': 'Ibikorwa',
    'admin.mail_testers.remove': 'Kuraho',
    'admin.mail_testers.remove_label': 'Kuraho {email}',
    'admin.mail_testers.empty': 'Nta bagerageza bavuzwe mu mazina barahari.',
    'admin.mail_testers.stage.add.title': 'Ongeraho {email} nk’ugerageza',
    'admin.mail_testers.stage.add.body':
        'Urubuga rw’igerageza ruzashobora koherereza iyi aderesi imeyili nyazo, harimo ubutumwa bwo kwiyandikisha n’ubwo guhindura ijambobanga.',
    'admin.mail_testers.stage.add.cta': 'Ongeraho ugerageza',
    'admin.mail_testers.stage.add.placeholder':
        'Kuki uyu muntu akeneye imeyili z’igerageza?',
    'admin.mail_testers.stage.remove.title': 'Kuraho {email}',
    'admin.mail_testers.stage.remove.body':
        'Urubuga rw’igerageza ruzahita ruhagarika kohereza imeyili kuri iyi aderesi.',
    'admin.mail_testers.stage.remove.cta': 'Kuraho ugerageza',
    'admin.mail_testers.stage.remove.placeholder':
        'Kuki uyu ugerageza akuweho?',
    'admin.drawer.close': 'Funga',
    'admin.stage.reason_label': 'Impamvu yandikwa',
    'admin.stage.logged_as':
        "Byandikwa mu bubiko bw'igenzura nka {name} · {role}",
    'admin.stage.cancel': 'Reka',
    'admin.rating.pending': 'Igenzura ritegerejwe',
    'admin.rating.of_five': '{band} {score} / 5',
    'admin.rating.band.strong': 'Ikomeye',
    'admin.rating.band.stable': 'Ihamye',
    'admin.rating.band.weak': 'Intege nke',
    'admin.rating.band.distressed': 'Iri mu bibazo',
    'admin.design.ratings.weights.title': 'Uko amanota abarwa',
    'admin.design.ratings.weights.sub':
        "Buri gipimo gishyirwa hagati ya 0 na 100, kigahabwa uburemere, bigateranywa; amanota agabanyijwe na 20 ni amanota kuri 5. Imipaka y'imyitwarire ikurikizwa nyuma.",
    'admin.design.ratings.weights.empty':
        "Abasesenguzi b'inguzanyo bonyine ni bo babona uru rutonde.",
    'admin.design.ratings.bands.title': 'Ibyiciro',
    'admin.design.ratings.bands.empty':
        "Abasesenguzi b'inguzanyo bonyine ni bo babona ibyiciro.",
    'admin.engine.target_dscr': 'DSCR igamijwe',
    'admin.engine.minimum_dscr': 'DSCR ntoya',
    'admin.engine.repeat_monthly_dscr': 'DSCR ya buri kwezi · indi nshuro',
    'admin.engine.min_principal': 'Ubusabe buto',
    'admin.engine.max_principal': 'Ubusabe bunini',
    'admin.engine.issuance_unit': 'Ubusabe buzengurutswa kuri',
    'admin.engine.first_issue_months': 'Amezi agenzuwe · bwa mbere',
    'admin.engine.repeat_issue_months': 'Amezi agenzuwe · indi nshuro',
    'admin.engine.tenors': 'Igihe · amezi',
    'admin.engine.rate_band': "Urwego rw'inyungu · ihamye",
    'admin.engine.platform_fee': "Amafaranga y'urubuga · igihe cyo gutanga",
    'admin.engine.cpa_share': "Umugabane w'umugenzuzi",
    'admin.engine.coverage': 'Ubushobozi bwo kwishyura umwenda',
    'admin.engine.cfads_margin': 'Amafaranga aboneka / amafaranga yinjiye',
    'admin.engine.nocf_stability': "Ugutekana kw'amafaranga y'ibikorwa",
    'admin.engine.positive_months': 'Amezi afite amafaranga asigaye',
    'admin.engine.history_depth': "Amezi y'amateka",
    'admin.engine.conduct': 'Imyitwarire yo kwishyura no gutanga raporo',
    'admin.engine.adverse_conduct_cap':
        'Umupaka · raporo zakererewe cyangwa zabuze',
    'admin.engine.default_cap': 'Umupaka · kunanirwa kwishyura',
    'admin.engine.strong': 'Bikomeye',
    'admin.engine.stable': 'Bihamye',
    'admin.engine.weak': 'Bidakomeye',
    'admin.engine.distressed': 'Mu bibazo',
    'admin.design.engines.sizing.sub':
        "Imipaka ine yose irakurikizwa, iya hasi ni yo ikurikizwa: ubushobozi kuri DSCR igamijwe, amafaranga yasabwe, ubusabe bunini n'umwanya usigaye. Ubusabe buri munsi y'ubuto bwemewe buranga. Nyuma amafaranga aragabanywa kugeza DSCR igamijwe yubahirijwe, akazengurutswa ku rugero rwo gutanga.",
    'admin.design.ratings.movers.title': 'Ibihindura amanota',
    'admin.design.ratings.movers.sub':
        "Buri raporo y'imari yemejwe isubiramo ibipimo by'imari, bityo kuzamuka cyangwa kugabanuka kw'amafaranga yinjiye bihindura amanota. Kwishyura na raporo za buri kwezi bihindura imyitwarire, kandi imipaka igumisha amanota hasi igihe hari ibirarane, kutubahiriza raporo cyangwa kunanirwa kwishyura. Nta mukozi ushobora kubihindura.",
    'admin.design.ratings.movers.empty':
        "Abasesenguzi b'inguzanyo bonyine ni bo babona ibihindura amanota.",
    'admin.engine.new_issuer_conduct':
        'Imyitwarire mbere yo kwishyura · kuri 100',
    'admin.engine.instalment_conduct':
        "Ibyishyuwe ku gihe · umugabane w'imyitwarire",
    'admin.engine.report_conduct':
        "Raporo za buri kwezi ku gihe · umugabane w'imyitwarire",
    'admin.policy.eyebrow': 'Byashyizweho mu mabwiriza',
    'admin.policy.open': 'Politiki',
    'admin.policy.target_dscr': 'DSCR igamijwe',
    'admin.policy.listing_audit': 'Igenzura ryo gushyirwa ku isoko',
    'admin.policy.audit_routing': 'Urugero rwo kohereza mu igenzura',
    'admin.policy.rate_band': "Urugero rw'inyungu · rimwe, byose hamwe",
    'admin.policy.min_revenue': 'Amafaranga yinjira ku mwaka make',
    'admin.policy.min_statement_years': "Imyaka mike y'inyandiko z'imari",
    'admin.policy.rdb_certificate': 'Icyemezo cya RDB',
    'admin.policy.max_notes': 'Inyandiko nyinshi / ikigo',
    'admin.policy.platform_fee': 'Amafaranga ya platform · igihe cyo kwishyura',
    'admin.policy.cpa_share': 'Umugabane wa CPA — Kigali',
    'admin.policy.listing_fee': 'Amafaranga yo gushyira ku isoko',
    'admin.policy.repayment_fee': 'Amafaranga yo kwishyura',
    'admin.payments.policy_title': 'Ibiciro bikurikizwa',
    'admin.policy.minimum_age': "Imyaka mike y'umushoramari",
    'admin.policy.identity_evidence': "Inyandiko z'umwirondoro kuri dosiye",
    'admin.policy.complaint_acknowledge': 'Ikirego cyakirwa mu',
    'admin.policy.complaint_resolve': 'Ikirego gikemurwa mu',
    'admin.kyc.policy_title': 'Amategeko yo kubahiriza akurikizwa',
    'admin.policy.min_principal': 'Ubusabe buto',
    'admin.policy.max_principal': 'Ubusabe bunini',
    'admin.policy.first_issue_months': 'Amezi yagenzuwe · bwa mbere',
    'admin.policy.repeat_issue_months': 'Amezi yagenzuwe · gusubiramo',
    'admin.policy.withdrawal_min': 'Kubikuza guto',
    'admin.policy.withdrawal_max': 'Kubikuza kunini',
    'admin.policy.cpa_share_upcountry': 'Umugabane wa CPA — mu ntara',
    'admin.policy.resale_fee': 'Amafaranga yo kugurisha · ugurisha',
    'admin.policy.earnings_fee': "Ibyiciro by'amafaranga ku nyungu",
    'admin.policy.cpa_earnings_share':
        'Umugabane wa CPA ku mafaranga ku nyungu',
    'admin.policy.application_fee': 'Amafaranga yo gusaba · sandbox',
    'admin.policy.withdrawal_fee': 'Amafaranga yo kubikuza',
    'admin.policy.loss_reserve': "Ikigega cy'igihombo",
    'admin.policy.min_rating': 'Amanota make yo gukusanya',
    'admin.policy.revenue_cap': 'Ubukusanyamari bunini · ku byinjira',
    'admin.policy.kyc_reverify': 'Kongera kugenzura KYC',
    'admin.policy.late_charge': "Amande y'ubukererwe",
    'admin.policy.miss_grace': "Igihe cy'imbabazi",
    'admin.policy.default_at': 'Kunanirwa kwishyura',
    'admin.policy.auto_freeze': 'Guhagarika byikora iyo hananiwe',
    'admin.policy.min_investment': 'Ishoramari rito · igice kimwe',
    'admin.policy.concentration':
        "Igice kinini cy'ubukusanyamari ku mushoramari",
    'admin.policy.raise_days': 'Igihe cyo gukusanya · iminsi',
    'admin.policy.resale_listing_days':
        'Igihe itangazo ryo kugurisha rimara · iminsi',
    'admin.policy.resale_reservation_minutes': 'Gufatira igicuruzwa · iminota',
    'admin.policy.cpa_radius': 'Intera yo kohereza CPA',
    'admin.policy.sandbox_businesses': 'Imbibi za sandbox · ibigo',
    'admin.policy.sandbox_investors': 'Imbibi za sandbox · abashoramari',
    'admin.policy.sandbox_principal': 'Imbibi za sandbox · igishoro gisigaye',
    'admin.policy.residency': 'Aho atuye',
    'admin.policy.risk_statements':
        "Amatangazo y'ingaruka mbere y'ishoramari rya mbere",
    'admin.policy.hint.platform_fee':
        'Bikurwa ku mafaranga yakusanyijwe igihe ahabwa ikigo.',
    'admin.policy.hint.cpa_share':
        "Umugabane w'amafaranga y'urubuga uhabwa umugenzuzi ku masezerano y'i Kigali.",
    'admin.policy.hint.cpa_share_upcountry':
        'Umugabane umwe ku masezerano yo hanze ya Kigali.',
    'admin.policy.hint.earnings_fee':
        "Bikurwa ku nyungu z'umushoramari. Urwego rukurikira icyiciro cye.",
    'admin.policy.hint.cpa_earnings_share':
        "Umugabane w'amafaranga ku nyungu uhabwa umugenzuzi.",
    'admin.policy.hint.resale_fee':
        "Yishyurwa n'ugurisha iyo inyandiko igurishijwe mbere y'igihe cyayo.",
    'admin.policy.hint.listing_fee':
        "Yishyurwa n'ikigo kugira ngo gishyire ahagaragara ishakishwa ry'imari.",
    'admin.policy.hint.repayment_fee':
        'Yishyurwa kuri buri bwishyu bwa buri kwezi.',
    'admin.policy.hint.application_fee':
        'Yishyurwa ku busabe igihe Rozine iri mu igerageza.',
    'admin.policy.hint.withdrawal_fee': 'Ashyirwa ku kubikuza mu gikapu.',
    'admin.policy.hint.loss_reserve':
        "Umugabane w'amafaranga ubikwa mu gihe cyo kunanirwa kwishyura.",
    'admin.policy.hint.target_dscr':
        'Ishakishwa rinini rigarukira aho amafaranga yinjira yishyura ku kigero iki.',
    'admin.policy.hint.listing_audit':
        "Umugenzuzi agenzura buri shakishwa ry'imari mbere y'uko rijya ahagaragara.",
    'admin.policy.hint.audit_routing':
        'DSCR ntoya yemewe. Munsi yayo, ubusabe buranswe.',
    'admin.policy.hint.min_rate':
        'Inyungu ihamye ntoya inyandiko ishobora kugira, byose hamwe.',
    'admin.policy.hint.max_rate':
        'Inyungu ihamye nini inyandiko ishobora kugira, byose hamwe.',
    'admin.policy.hint.note_unit_price': "Igiciro cy'inyandiko imwe ya Rozine.",
    'admin.policy.hint.min_term': "Igihe gito cyemewe cy'inyandiko.",
    'admin.policy.hint.max_term': "Igihe kinini cyemewe cy'inyandiko.",
    'admin.policy.hint.min_principal':
        'Ishakishwa rito ikigo gishobora gusaba.',
    'admin.policy.hint.max_principal':
        'Ishakishwa rinini ikigo gishobora gusaba.',
    'admin.policy.hint.min_rating':
        'Amanota ya Rozine make yemerera gushaka imari.',
    'admin.policy.hint.revenue_cap':
        "Ishakishwa rinini, nk'umugabane w'amafaranga ikigo cyinjiza.",
    'admin.policy.hint.registration_countries':
        'Aho ikigo kigomba kuba cyanditswe.',
    'admin.policy.hint.min_statement_years':
        "Imyaka y'inyandiko za banki cyangwa MoMo isabwa.",
    'admin.policy.hint.min_revenue':
        'Amafaranga make yinjiye mu mezi 12 ashize kugira ngo cyemererwe.',
    'admin.policy.hint.first_issue_months':
        "Amezi y'inyandiko agenzurwa mbere y'ishakishwa rya mbere.",
    'admin.policy.hint.repeat_issue_months':
        'Amezi agenzurwa mbere ya buri shakishwa rikurikiraho.',
    'admin.policy.hint.rdb_certificate':
        'Ikigo kigomba gushyiraho icyemezo cya RDB gifite agaciro.',
    'admin.policy.hint.max_notes':
        'Inyandiko zikora cyangwa zishyurwa ikigo gishobora kugira icyarimwe.',
    'admin.policy.hint.residency': 'Aho umushoramari agomba gutura.',
    'admin.policy.hint.minimum_age':
        'Imyaka mike umushoramari ashobora kugira.',
    'admin.policy.hint.min_investment': 'Ishoramari rito ryemewe.',
    'admin.policy.hint.concentration':
        'Umugabane munini umushoramari umwe ashobora kugira mu ishakishwa rimwe.',
    'admin.policy.hint.risk_statements':
        "Amatangazo umushoramari yemera mbere y'ishoramari rya mbere.",
    'admin.policy.hint.kyc_investor_id': "Imbere n'inyuma by'inyandiko.",
    'admin.policy.hint.kyc_investor_selfie':
        "Ifoto ifashwe ako kanya igereranywa n'inyandiko.",
    'admin.policy.hint.kyc_investor_dob':
        'Bigaragaza ko umushoramari afite imyaka isabwa.',
    'admin.policy.hint.kyc_investor_address': 'Kwiyandikisha ntibibisaba.',
    'admin.policy.hint.kyc_business_rdb':
        "Bigenzurwa mbere y'uko ikigo gisaba.",
    'admin.policy.hint.identity_evidence':
        'Amafoto muri buri dosiye: imbere, inyuma na selfie.',
    'admin.policy.hint.kyc_reverify':
        'Inshuro umukoresha agomba kongera kugenzura umwirondoro.',
    'admin.policy.hint.late_charge': 'Yongerwa ku bwishyu bwakererewe.',
    'admin.policy.hint.miss_grace':
        "Igihe ubwishyu butatanzwe bufatwa nk'ubukererewe.",
    'admin.policy.hint.default_at':
        'Igihe ubwishyu butatanzwe buhinduka kunanirwa kwishyura.',
    'admin.policy.hint.auto_freeze':
        'Guhagarika ikigo mu buryo bwikora iyo kinaniwe kwishyura.',
    'admin.policy.hint.complaint_acknowledge':
        'Igihe cyo kwemeza ko ikirego cyakiriwe.',
    'admin.policy.hint.complaint_resolve': 'Igihe cyo gukemura ikirego.',
    'admin.policy.hint.raise_days':
        "Igihe ishakishwa ry'imari rimara rifunguye.",
    'admin.policy.hint.withdrawal_min': 'Kubikuza guto mu gikapu.',
    'admin.policy.hint.withdrawal_max': 'Kubikuza kunini kwemewe.',
    'admin.policy.hint.resale_listing_days':
        'Igihe itangazo ryo kongera kugurisha rimara.',
    'admin.policy.hint.resale_reservation_minutes':
        'Igihe umuguzi afata igice mbere yo kwishyura.',
    'admin.policy.hint.cpa_radius': 'Intera nini umugenzuzi yoherezwa.',
    'admin.policy.hint.sandbox_businesses':
        "Umubare ntarengwa w'ibigo bikora mu igerageza rya CMA.",
    'admin.policy.hint.sandbox_investors':
        "Umubare ntarengwa w'abashoramari bemejwe mu igerageza.",
    'admin.policy.hint.sandbox_principal':
        "Igiteranyo ntarengwa cy'imari yakusanyijwe mu igerageza.",
    'admin.policy.hint.rate_band':
        "Urugero rw'inyungu ihamye y'inyandiko, byose hamwe.",
    'admin.policy.hint.dual_control_threshold':
        'Guhera kuri aya mafaranga, hakenewe undi wemeza.',
    'admin.policy.min_rate': 'Inyungu nto · ihamye',
    'admin.policy.max_rate': 'Inyungu nini · ihamye',
    'admin.policy.note_unit_price': "Igiciro cy'igice cy'inyandiko",
    'admin.policy.min_term': 'Igihe gito · amezi',
    'admin.policy.max_term': 'Igihe kinini · amezi',
    'admin.policy.registration_countries': 'Ibihugu byemewe byo kwandikwamo',
    'admin.policy.kyc_investor_id': 'Umushoramari · Indangamuntu / pasiporo',
    'admin.policy.kyc_investor_selfie':
        'Umushoramari · Ifoto yo kwemeza ko ari muzima',
    'admin.policy.kyc_investor_dob': "Umushoramari · Itariki y'amavuko",
    'admin.policy.kyc_investor_address': "Umushoramari · Icyemezo cy'aho atuye",
    'admin.policy.kyc_business_rdb': 'Ikigo · Icyemezo cya RDB',
    'admin.policy.term.rwanda': 'u Rwanda',
    'admin.policy.term.not_collected': 'Ntibikusanywa',
    'admin.policy.term.resident_rwanda': 'Utuye mu Rwanda',
    'admin.policy.term.every_deal': 'Bisabwa kuri buri bukusanyamari',
    'admin.policy.term.required': 'Birasabwa',
    'admin.policy.term.not_set': 'Ntibirashyirwaho',
    'admin.policy.term.none': 'Nta na kimwe',
    'admin.policy.term.late_from_due':
        'Nta na kimwe · bikererwa guhera ku itariki',
    'admin.policy.term.day_31': 'Umunsi wa 31 utishyuwe',
    'admin.policy.term.late_schedule':
        "5% kuri D, umunsi wa 7 n'uwa 30 · ntirenga 15%",
    'admin.finance.stream.platform_fee':
        'Amafaranga ya platform · umugabane wa Rozine',
    'admin.finance.stream.audit_partner_share':
        "Amafaranga ya platform · umugabane w'Audit Partner (ugomba kwishyurwa)",
    'admin.finance.stream.deposit_fees': 'Amafaranga yo kubitsa',
    'admin.finance.paid_by.business': 'Ibigo',
    'admin.finance.paid_by.investor': 'Abashoramari',
    'admin.finance.paid_by.seller': 'Ugurisha',
    'admin.finance.stream.earnings_fee': 'Amafaranga ku nyungu',
    'admin.finance.stream.resale_fee': 'Amafaranga yo kugurisha',
    'admin.finance.stream.withdrawal_fee': 'Amafaranga yo kubikuza',
    'admin.finance.stream.loss_reserve': "Ikigega cy'igihombo",
    'admin.policy.dual_control_threshold': 'Abemeza babiri guhera kuri',
    'admin.time.now': 'ubu nyine',
    'admin.time.minutes': 'hashize iminota {count}',
    'admin.time.hours': 'hashize amasaha {count}',
    'admin.time.days': 'hashize iminsi {count}',
    'admin.ledger.kind.investment': 'Ishoramari',
    'admin.ledger.kind.disbursement': 'Kwishyura ikigo',
    'admin.ledger.kind.repayment': 'Kwishyura inguzanyo',
    'admin.ledger.kind.platform_fee': 'Amafaranga ya platifomu',
    'admin.ledger.kind.repayment_fee': 'Amafaranga yo kwishyura',
    'admin.ledger.kind.auditor_share': "Umugabane w'umugenzuzi",
    'admin.ledger.kind.distribution': 'Kwishyura abashoramari',
    'admin.ledger.kind.recovery': 'Kwishyura ibyagarujwe',
    'admin.ledger.kind.deposit': 'Kubitsa mu gikapu',
    'admin.ledger.kind.withdrawal': 'Kubikuza',
    'admin.ledger.kind.secondary': 'Igurisha rya kabiri',
    'admin.ledger.kind.secondary_fee': "Amafaranga y'igurisha rya kabiri",
    'admin.ledger.kind.contra': 'Ikosora rinyuranye',
    'admin.today.commands': 'Amabwiriza yihuse',
    'admin.today.command.review_queue': 'Umurongo wo gusuzuma',
    'admin.today.command.audit_desk': "Ibiro by'igenzura",
    'admin.today.command.policies': 'Amabwiriza',
    'admin.today.kpi.capital_raised': 'Imari yose yakusanyijwe',
    'admin.today.kpi.capital_raised_trend': 'Mu nyandiko zose',
    'admin.today.kpi.active_businesses': 'Ibigo bikora',
    'admin.today.kpi.active_businesses_trend': 'Ku rubuga',
    'admin.today.kpi.verified_investors': 'Abashoramari bemejwe',
    'admin.today.kpi.verified_investors_trend': 'KYC yemejwe',
    'admin.today.kpi.outstanding_notes': 'Inyandiko zitararangira',
    'admin.today.kpi.outstanding_notes_trend':
        'Biri ku isoko · gukusanya · kwishyura',
    'admin.today.kpi.treasury_position': "Uko ububiko bw'imari buhagaze",
    'admin.today.kpi.treasury_positive':
        'Amafaranga yinjiye, ukuyemo ayishyuwe',
    'admin.today.kpi.treasury_negative': 'Hishyuwe menshi kuruta ayinjiye',
    'admin.today.kpi.default_rate': 'Igipimo cyo kunanirwa kwishyura',
    'admin.today.kpi.default_rate_trend': "{failed} muri {total} z'inyandiko",
    'admin.today.kpi.secondary_volume':
        "Ingano y'igurisha rya kabiri uyu munsi",
    'admin.today.kpi.secondary_volume_trend': "Hagati y'abashoramari",
    'admin.today.kpi.not_tracked': 'Ntibirakurikiranwa',
    'admin.today.attention.applications_pending': 'Ubusabe butegereje',
    'admin.today.attention.kyc_awaiting': 'KYC itegereje gusuzumwa',
    'admin.today.attention.notes_late': 'Inyandiko zatinze kwishyurwa',
    'admin.today.attention.notes_default_risk': 'Inyandiko zishobora kunanirwa',
    'admin.today.attention.frozen_accounts': 'Konti zahagaritswe',
    'admin.today.breaks.title': 'Ibitandukanye mu guhuza konti',
    'admin.today.breaks.caption':
        'Buri kinyuranyo kiguma hano, kibarwa iminsi, kugeza gisobanuwe. Gihe ugikurikirana cyangwa ukizamure.',
    'admin.today.breaks.ledger': "Fungura igitabo cy'imari",
    'admin.today.breaks.empty_title': "Igitabo cy'imari gihuye neza",
    'admin.today.breaks.empty_body': 'Nta kinyuranyo gifunguye.',
    'admin.today.breaks.untracked_title': 'Ihuza rya konti ntirirakurikiranwa',
    'admin.today.breaks.untracked_body':
        "Ibinyuranyo bizagaragara hano ihuza ry'inyandiko za banki nirimara guhuzwa.",
    'admin.today.breaks.age': 'Kimaze iminsi {days}',
    'admin.today.breaks.unassigned': 'Nta ugikurikirana',
    'admin.today.breaks.owner': 'Ugikurikirana: {name}',
    'admin.today.breaks.assign': 'Mpa jye',
    'admin.today.breaks.escalate': 'Zamura',
    'admin.today.breaks.assign_title': 'Fata inshingano za {reference}',
    'admin.today.breaks.assign_body':
        'Uba ukurikirana iki kinyuranyo kugeza gisobanuwe. Vuga uko uzagikurikirana.',
    'admin.today.breaks.assign_cta': 'Fata inshingano',
    'admin.today.breaks.assign_placeholder':
        "urugero: Guhuza n'inyandiko yo kwishyura ya MoMo yo ku wa 22 Nzeri.",
    'admin.today.breaks.escalate_title': 'Zamura {reference}',
    'admin.today.breaks.escalate_body':
        "Ikinyuranyo kijya ku buyobozi bw'imari. Vuga icyo wabonye n'impamvu bakenewe.",
    'admin.today.breaks.escalate_cta': 'Zamura',
    'admin.today.breaks.escalate_placeholder':
        "urugero: Inyandiko y'utanga serivisi ntihura n'iyacu; birakenera banki.",
    'admin.today.capital.title': 'Imari yakusanyijwe',
    'admin.today.capital.from': 'Kuva',
    'admin.today.capital.to': 'Kugeza',
    'admin.today.capital.from_date': 'Itariki yo gutangira',
    'admin.today.capital.from_time': 'Isaha yo gutangira',
    'admin.today.capital.to_date': 'Itariki yo kurangiza',
    'admin.today.capital.to_time': 'Isaha yo kurangiza',
    'admin.today.capital.clear': 'Siba',
    'admin.trend.refused':
        "Hitamo itariki yo kurangiza ingana cyangwa iri nyuma y'itariki yo gutangira.",
    'admin.today.capital.all_time': 'Ibihe byose',
    'admin.today.capital.range': '{from} → {to}',
    'admin.today.capital.caption': '{range} · bishyizwe hamwe ku {grain}',
    'admin.today.capital.grain.year': 'mwaka',
    'admin.today.capital.grain.month': 'kwezi',
    'admin.today.capital.grain.day': 'munsi',
    'admin.today.capital.grain.hour': 'saha',
    'admin.today.health.title': 'Uko inyandiko zimeze',
    'admin.today.health.caption':
        "Inyandiko {count} zasohotse, hakurikijwe raporo y'ukwezi iheruka",
    'admin.today.health.donut': "{pct}% by'inyandiko bimeze neza",
    'admin.today.health.healthy_caption': 'bimeze neza',
    'admin.today.health.healthy': 'Bimeze neza',
    'admin.today.health.watch': 'Bikurikiranwa',
    'admin.today.health.distressed': 'Biri mu bibazo',
    'admin.today.health.untracked': 'Ntibirakurikiranwa',
    'admin.today.activity.title': 'Ibikorwa biri kuba',
    'admin.today.activity.caption': 'Mu rusobe rwose',
    'admin.today.activity.empty': 'Nta mafaranga arahinduranya.',
    'admin.today.untracked': 'Ntibirakurikiranwa muri konsole.',
    'admin.today.capital.empty': 'Nta gishoro cyakusanyijwe muri iki gihe.',
    'admin.today.activity.see_all': "Reba ibikorwa byose mu gitabo cy'imari",
    'admin.today.lifecycle.title': "Urugendo rw'inyandiko",
    'admin.today.lifecycle.caption': 'Aho buri nyandiko iri ubu',
    'admin.today.lifecycle.submitted': 'Byoherejwe',
    'admin.today.lifecycle.live': 'Biri ku isoko',
    'admin.today.lifecycle.funded': 'Byakusanyijwe',
    'admin.today.lifecycle.repaying': 'Birishyurwa',
    'admin.today.lifecycle.matured': 'Byarangiye',
    'admin.today.lifecycle.failed': 'Byananiranye',
    'admin.today.pending.title': 'Ubusabe butegereje',
    'admin.today.pending.empty': 'Nta busabe butegereje.',
    'admin.today.pending.review': 'Suzuma',
    'admin.today.pending.review_named': 'Suzuma {name}',
    'admin.today.sectors.title': 'Imari muri buri rwego',
    'admin.today.sectors.about': 'Ibyerekeye imari muri buri rwego',
    'admin.today.sectors.tip':
        'Imari itararangira muri buri rwego. Umutuku bivuze ko hari nibura inyandiko imwe ikurikiranwa cyangwa iri mu bibazo.',
    'admin.today.sectors.notes': 'Inyandiko {count}',
    'admin.today.sectors.empty': 'Nta nyandiko irasohoka.',
    'admin.today.treasury.title': "Incamake y'ububiko bw'imari",
    'admin.today.treasury.invested': 'Imari yashowe (isoko rya mbere)',
    'admin.today.treasury.disbursed': 'Yishyuwe ibigo',
    'admin.today.treasury.platform_net': 'Uko urubuga ruhagaze',
    'admin.today.treasury.paid_to_investors': 'Yishyuwe abashoramari',
    'admin.today.collections.title': 'Ibyishyurwa',
    'admin.today.collections.caption': 'Inyandiko {count} ziri kwishyurwa',
    'admin.today.collections.outstanding': 'Bitararangira',
    'admin.today.collections.next_due': 'Ibyishyurwa bikurikira',
    'admin.today.collections.on_track': 'Biri ku gihe',
    'admin.today.collections.late': 'Byatinze',
    'admin.today.collections.default_risk': 'Bishobora kunanirwa',
    'admin.trail.by': 'na {actor}',
    'admin.trail.reason': 'Impamvu: {reason}',
    'admin.applications.policy_title': 'Amabwiriza yo kwemeza akurikizwa',
    'admin.applications.tabs': "Imiterere y'ubusabe",
    'admin.applications.tab.pending': 'Bitegereje',
    'admin.applications.tab.under_review': 'Birasuzumwa',
    'admin.applications.tab.escalated': 'Byazamuwe',
    'admin.applications.tab.approved': 'Byemejwe',
    'admin.applications.tab.rejected': 'Byanzwe',
    'admin.applications.table': 'Ubusabe',
    'admin.applications.col.business': 'Ikigo · Inyandiko',
    'admin.applications.col.sector': 'Urwego',
    'admin.applications.col.requested': 'Ayasabwe',
    'admin.applications.col.capacity': 'Ubushobozi bwakoreshejwe',
    'admin.applications.col.rating': 'Amanota',
    'admin.applications.col.submitted': 'Byoherejwe',
    'admin.applications.col.decision': 'Icyemezo · Ibikorwa',
    'admin.applications.terms': 'Amezi {term} · {rate}% rimwe',
    'admin.applications.capacity_used': 'Ubushobozi bwakoreshejwe',
    'admin.applications.capacity_unavailable': 'Ntibiboneka',
    'admin.applications.older': 'Ubusabe bwa kera',
    'admin.decision.reason_code.CURRENT_RELEASE_REVIEW_REQUIRED':
        'Hakenewe isuzuma ryo kurekura riri gukorwa: igiciro cyabitswe si icyemezo kiriho. Reba amagenzura yo kurekura ari hepfo.',
    'admin.decision.reason_code.DSCR_BELOW_CUTOFF':
        'Ubwishingizi buri munsi ya 1,25: amafaranga yinjira ntashobora kwishyura igice.',
    'admin.decision.reason_code.RESTRICTION_ACTIVE':
        "Inzitizi y'inguzanyo iriho ibuza kongera kuguza.",
    'admin.applications.term_only': 'Amezi {term}',
    'admin.applications.decision.approve': 'Kwemeza byikora',
    'admin.applications.decision.reject': 'Ikimenyetso: kwanga',
    'admin.applications.decision.audit': 'Ohereza mu igenzura',
    'admin.applications.decision.review': 'Bikeneye isuzuma',
    'admin.applications.review': 'Suzuma',
    'admin.applications.review_named': 'Suzuma {name}',
    'admin.applications.approve': 'Emeza',
    'admin.applications.approve_named': 'Emeza {name}',
    'admin.applications.empty': 'Nta busabe buri muri uyu murongo.',
    'admin.applications.state.submitted': 'Bitegereje',
    'admin.applications.state.under_review': 'Birasuzumwa',
    'admin.applications.state.info_requested': 'Amakuru yasabwe',
    'admin.applications.state.escalated': 'Byazamuwe',
    'admin.applications.state.approved': 'Byemejwe',
    'admin.applications.state.rejected': 'Byanzwe',
    'admin.review.label': 'Isuzuma ryo kwemeza, {business}',
    'admin.review.eyebrow': 'Isuzuma ryo kwemeza',
    'admin.review.recommend.approve': 'Inama: Emeza',
    'admin.review.recommend.reject': 'Inama: Anga',
    'admin.review.recommend.audit': 'Inama: Ohereza mu igenzura',
    'admin.review.recommend.review': "Inama: Isuzuma ry'intoki",
    'admin.review.audit.missing_title':
        'Igenzura ryo gushyirwa ku isoko ntiriraboneka',
    'admin.review.audit.missing_body':
        "Kwemeza birahagaritswe kugeza umugenzuzi yemeje igenzura ry'iki kigo. Risabe ikigo, cyangwa uzamure.",
    'admin.review.audit.pending_title':
        'Igenzura ryo gushyirwa ku isoko rirakorwa',
    'admin.review.audit.pending_body':
        "Kwemeza bizashoboka umugenzuzi namara kwemeza igenzura. Nta kintu gishyirwa ku isoko mbere y'aho.",
    'admin.review.requested': 'Ayasabwe',
    'admin.review.rating': 'Amanota ya Rozine',
    'admin.review.rating_score': '· {score} / 5',
    'admin.review.term': 'Igihe',
    'admin.review.term_months': 'Amezi {count}',
    'admin.review.yield': "Inyungu y'umushoramari",
    'admin.review.yield_value': '{rate}% rimwe',
    'admin.review.capacity_title': "Ubushobozi bukoreshwa n'uku gukusanya",
    'admin.review.capacity_approved': 'Ubushobozi bwemejwe {amount}',
    'admin.review.capacity_existing':
        'Inyandiko {count} zikora · {amount} bitararangira',
    'admin.review.factors_title': 'Ibigize amanota',
    'admin.review.factors_caption': 'Biva kuri moteri yo kwemeza · gusoma gusa',
    'admin.review.factor.repayment_history': 'Amateka yo kwishyura',
    'admin.review.factor.revenue_consistency': "Guhoraho kw'amafaranga yinjira",
    'admin.review.factor.statement_record': "Amateka y'inyandiko z'imari",
    'admin.review.factor.capacity_headroom': 'Ubushobozi busigaye',
    'admin.review.factor.sector_risk': "Ibyago by'urwego",
    'admin.review.use_of_funds': 'Icyo amafaranga azakoreshwa',
    'admin.review.submitted': 'Byoherejwe {date}',
    'admin.review.reviewer': 'Usuzuma: {name}',
    'admin.review.unassigned': 'Nta wahawe',
    'admin.review.business_profile': "Reba umwirondoro wose w'ikigo →",
    'admin.review.trail': "Amateka y'ibyemezo",
    'admin.review.trail_empty': 'Nta cyemezo kiranditswe.',
    'admin.review.approve': 'Emeza ukore inyandiko',
    'admin.review.reject': 'Anga',
    'admin.review.take': 'Fata usuzume',
    'admin.review.info': 'Saba amakuru',
    'admin.review.escalate': 'Zamura',
    'admin.review.stage.approve.title': 'Emeza ukore inyandiko',
    'admin.review.stage.approve.body':
        'Ibi bikora inyandiko ya {amount} ku {rate}% rimwe mu mezi {term} kandi iyitangaza ku bashoramari. Ikigo kizamenyeshwa.',
    'admin.review.stage.approve.cta': 'Emeza kwemeza',
    'admin.review.stage.approve.placeholder':
        "urugero: Ibimenyetso byuzuye; DSCR n'ubushobozi biri mu mabwiriza.",
    'admin.review.stage.reject.title': 'Anga ubusabe',
    'admin.review.stage.reject.body':
        'Ikigo kizamenyeshwa. Shyiramo impamvu isobanutse kugira ngo cyumve kandi gishobore kongera gusaba.',
    'admin.review.stage.reject.cta': 'Emeza kwanga',
    'admin.review.stage.reject.placeholder':
        'urugero: Amafaranga yasabwe arenze ubushobozi bwemejwe; gabanya wongere wohereze.',
    'admin.review.stage.info.title': 'Saba andi makuru',
    'admin.review.stage.info.body':
        'Bwira ikigo neza icyo gitanga. Kizabona ubutumwa burimo icyo usaba.',
    'admin.review.stage.info.cta': 'Ohereza icyifuzo',
    'admin.review.stage.info.placeholder':
        "urugero: Ohereza inyandiko za banki na mobile money z'amezi 6 ashize.",
    'admin.review.stage.escalate.title': "Zamurira komite y'inguzanyo",
    'admin.review.stage.escalate.body':
        "Ibi byohereza ubusabe mu isuzuma ry'abayobozi. Ongeraho ibisobanuro kuri komite.",
    'admin.review.stage.escalate.cta': 'Zamura',
    'admin.review.stage.escalate.placeholder':
        'urugero: Ibimenyetso biri ku mupaka ariko amateka yo kwishyura ni meza — bikeneye komite.',
    'admin.review.stage.take.title': 'Fata usuzume',
    'admin.review.stage.take.body':
        'Ubusabe bujya mu isuzuma, wowe ukaba ari wowe ubusuzuma.',
    'admin.review.stage.take.cta': 'Fata usuzume',
    'admin.review.stage.take.placeholder':
        "urugero: Ndabufata mu nama y'inguzanyo y'uyu munsi.",
    'admin.maker_checker.by': '{actor} · {at}',
    'admin.disbursements.policy_title': 'Amabwiriza yo kwishyura akurikizwa',
    'admin.disbursements.title': 'Kwishyura bitegereje',
    'admin.disbursements.table': 'Kwishyura bitegereje',
    'admin.disbursements.col.reference': 'Indango yo kwishyura',
    'admin.disbursements.col.note': 'Inyandiko',
    'admin.disbursements.col.recipient': 'Uwishyurwa',
    'admin.disbursements.col.amount': 'Amafaranga',
    'admin.disbursements.col.due': 'Igihe',
    'admin.disbursements.col.actions': 'Imiterere · Ibikorwa',
    'admin.disbursements.state.ready': 'Bitegereje kwishyurwa byikora',
    'admin.disbursements.state.awaiting_second_approver':
        'Bitegereje uwemeza wa kabiri',
    'admin.disbursements.state.on_hold': 'Byahagaritswe',
    'admin.disbursements.state.dispatched': 'Byoherejwe ku utanga serivisi',
    'admin.disbursements.state.paid': 'Byishyuwe',
    'admin.disbursements.state.failed': 'Kwishyura byananiranye',
    'admin.disbursements.action.release': 'Ishyura',
    'admin.disbursements.action.check': 'Genzura',
    'admin.disbursements.action.open': 'Fungura',
    'admin.disbursements.action.inspect': 'Suzuma',
    'admin.disbursements.open_named': 'Fungura {reference}',
    'admin.disbursements.today': 'Uyu munsi',
    'admin.disbursements.tomorrow': 'Ejo',
    'admin.disbursements.in_days': 'Mu minsi {count}',
    'admin.disbursements.overdue': 'Byarengeje iminsi {count}',
    'admin.disbursements.empty_title': 'Kwishyura kose kwarangiye',
    'admin.disbursements.empty_body': 'Nta kwishyura gutegerejwe ubu.',
    'admin.disbursements.drawer_label': 'Kwishyura {reference}',
    'admin.disbursements.amount': 'Amafaranga',
    'admin.disbursements.platform_fee':
        'Amafaranga ya platifomu ya 3%: {fee}, muri yo {share} ajya ku mugenzuzi. Ikigo cyakira {payout}.',
    'admin.disbursements.destination': 'Aho yoherezwa',
    'admin.disbursements.due': 'Igihe',
    'admin.disbursements.rule_dual':
        'Guhera kuri {threshold}, kwishyura bikenera abantu babiri batandukanye: umwe aremera, undi akemeza. Nta wemeza ibyo yemereye.',
    'admin.disbursements.rule_single':
        'Munsi ya {threshold}, uwemeza umwe ubifitiye uburenganzira arishyura.',
    'admin.disbursements.failure_title': 'Kwishyura byananiranye',
    'admin.disbursements.failure_code': 'Kode',
    'admin.disbursements.failure_provider': "Indango y'utanga serivisi",
    'admin.disbursements.failure_at': 'Byananiranye ku',
    'admin.disbursements.failure_attempts': 'Inshuro zagerageje',
    'admin.disbursements.failure_inspect':
        'Suzuma mbere yo kongera kugerageza: emeza ku utanga serivisi ko nta mafaranga yasohotse, kugira ngo kwishyura kumwe kutoherezwa kabiri.',
    'admin.disbursements.trail': 'Amateka yo kwishyura',
    'admin.disbursements.trail_empty': 'Nta kiranditswe.',
    'admin.disbursements.command.hold': 'Hagarika',
    'admin.disbursements.command.retry': 'Ongera ugerageze kwishyura',
    'admin.disbursements.stage.hold.title': 'Hagarika uku kwishyura',
    'admin.disbursements.stage.hold.body':
        'Nta kwishyura mu gihe bihagaritswe. Vuga impamvu, kugira ngo ukurikira amenye icyo akuraho.',
    'admin.disbursements.stage.hold.cta': 'Hagarika',
    'admin.disbursements.stage.hold.placeholder':
        'urugero: Dutegereje ko ikigo cyemeza nimero nshya ya MoMo.',
    'admin.disbursements.stage.retry.title': 'Ongera ugerageze uku kwishyura',
    'admin.disbursements.stage.retry.body':
        'Ongera ugerageze gusa utanga serivisi amaze kwemeza ko igerageza ryananiranye ritimuye amafaranga. {amount} izongera yoherezwe kuri {business}.',
    'admin.disbursements.stage.retry.cta': 'Ongera ugerageze',
    'admin.disbursements.stage.retry.placeholder':
        'urugero: MTN yemeje ko MM-88213 yasubijwe yose; aho yoherezwa hongeye kugenzurwa.',
    'admin.parties.policy_title': "Amabwiriza yo kwemererwa kw'ibigo",
    'admin.parties.filter': 'Tondeka ukurikije imiterere',
    'admin.audit_reports.title': "Ikurikirana ry'igenzura rya buri kwezi",
    'admin.audit_reports.caption':
        "Igenzura ryose n'uko raporo yaryo ihagaze. Igenzura rya buri kwezi rikorerwa aho ubucuruzi buri bitarenze ku itariki ya 7; nyuma ubucuruzi bugira amasaha 24 yo gusinya cyangwa kujuririra.",
    'admin.audit_reports.field.title': "Ibikorwa by'igenzura ryihuse ku butaka",
    'admin.audit_reports.field.caption':
        'Buri genzura ryo ku kibanza ryerekana CPA n’uruhushya rwe, uko raporo ihagaze n’ikinyuranyo kigereranyijwe hakurikijwe amafaranga, hamwe n’ikimenyetso cy’ifungwa ryabitswe na raporo ya PDF iyo bihari.',
    'admin.audit_reports.field.table': 'Igenzura ryihuse ku butaka',
    'admin.audit_reports.field.empty': 'Nta gikorwa cya ISRS 4400 kiranditswe.',
    'admin.audit_reports.feed.title': 'Ibyagenzuwe vuba bigatangazwa',
    'admin.audit_reports.feed.audited':
        '✓ Byagenzuwe aho bikorera na {auditor} · {licence}',
    'admin.audit_reports.feed.empty': 'Nta raporo ya buri kwezi iragenzurwa.',
    'admin.audit_reports.filter': "Uko raporo z'igenzura zigaragara",
    'admin.audit_reports.table': "Raporo z'igenzura",
    'admin.audit_reports.empty_title': 'Nta kintu kiri hano.',
    'admin.audit_reports.empty_body':
        'Igenzura rigaragara hano iyo umugenzuzi aryemeye.',
    'admin.audit_reports.monthly': 'Igenzura rya buri kwezi',
    'admin.audit_reports.flash': 'Igenzura ryihuse',
    'admin.audit_reports.due': 'Bigomba kurangira {date}',
    'admin.audit_reports.cosign_by': 'Gusinya bitarenze {date}',
    'admin.audit_reports.remind': 'Ibutsa',
    'admin.audit_reports.remind_auditor': 'Ibutsa umugenzuzi ibya {business}',
    'admin.audit_reports.remind_business':
        'Ibutsa {business} gusinya cyangwa kujuririra',
    'admin.audit_reports.reminded': 'Yibukijwe {date}',
    'admin.audit_reports.pdf': 'Kora PDF',
    'admin.audit_reports.pdf_named':
        'Kuramo raporo yashyizweho kashe ya {business} (PDF)',
    'admin.audit_reports.stats.published': 'Byagenzuwe kandi byatangajwe',
    'admin.audit_reports.stats.awaiting_cosign': 'Bitegereje gusinywa',
    'admin.audit_reports.stats.overdue': 'Igihe cyarenze',
    'admin.audit_reports.stats.on_time': 'Igipimo cyo kurangiza ku gihe',
    'admin.audit_reports.chip.all': 'Byose',
    'admin.audit_reports.chip.owed': 'Bitegereje umugenzuzi',
    'admin.audit_reports.chip.awaiting_cosign': 'Bitegereje gusinywa',
    'admin.audit_reports.chip.published': 'Byatangajwe',
    'admin.audit_reports.chip.disputed': 'Byajuririwe',
    'admin.audit_reports.col.business': 'Ubucuruzi',
    'admin.audit_reports.col.period': 'Igihe',
    'admin.audit_reports.col.partner': 'Umugenzuzi',
    'admin.audit_reports.col.status': 'Uko bihagaze',
    'admin.audit_reports.col.variance': 'Ikinyuranyo',
    'admin.audit_reports.col.action': 'Igikorwa',
    'admin.audit_reports.status.not_started': 'Igenzura ntirirafungurwa',
    'admin.audit_reports.status.in_progress': 'Birakomeje',
    'admin.audit_reports.status.overdue': 'Byatinze',
    'admin.audit_reports.status.returned': 'Byagaruwe',
    'admin.audit_reports.status.sealed': 'Byashyizweho kashe',
    'admin.audit_reports.status.awaiting_cosign': 'Bitegereje gusinywa',
    'admin.audit_reports.status.published': 'Byatangajwe',
    'admin.audit_reports.status.disputed': 'Byajuririwe',
    'admin.audit_reports.status.escalated': 'Byazamuwe',
    'admin.audit_reports.status.amended': 'Byahinduwe',
    'admin.audit_reports.status.withdrawn': 'Byakuweho',
    'admin.parties.coverage_link': 'Aho abafatanyabikorwa bagera',
    'admin.parties.registry.title': 'Urutonde rwa CPA',
    'admin.parties.registry.caption':
        "Buri CPA ufite uruhushya, n'uruhushya rwe, uko ahagaze n'igenzura arimo gukora. Fungura umurongo ubone umwirondoro wose.",
    'admin.parties.queue.requests.title': "Ibisabwa n'abafatanyabikorwa",
    'admin.parties.queue.requests.caption':
        'Kongerera igihe impushya bitegereje gusuzumwa. Umufatanyabikorwa abona ko bigitegereje kugeza ufashe icyemezo.',
    'admin.parties.queue.requests.empty':
        'Nta busabe bw’abafatanyabikorwa butegereje. Kongerera igihe impushya biva muri porogaramu y’Abagenzuzi bigera hano.',
    'admin.parties.queue.requests.chip': 'KONGERERA IGIHE',
    'admin.parties.queue.desk.title': 'Ibiro byo kugenzura ICPAR',
    'admin.parties.queue.desk.caption':
        'Emeza uruhushya mu gitabo cya ICPAR, hanyuma wemeze kugira ngo umufatanyabikorwa ashobore gufata igenzura.',
    'admin.parties.queue.desk.empty':
        "Nta busabe bw'abagenzuzi butegereje kugenzurwa.",
    'admin.parties.queue.desk.chip': 'BITEGEREJE KUGENZURWA',
    'admin.parties.queue.approve': 'Emeza',
    'admin.parties.queue.reject': 'Wanga',
    'admin.parties.queue.review': 'Suzuma',
    'admin.parties.queue.field.licence': 'Uruhushya',
    'admin.parties.queue.field.expires_on': 'Uruhushya rurangira',
    'admin.parties.queue.field.submitted_at': 'Byoherejwe',
    'admin.parties.chip.all': 'Byose',
    'admin.parties.chip.healthy': 'Bimeze neza',
    'admin.parties.chip.watch': 'Bikurikiranwa',
    'admin.parties.chip.distressed': 'Biri mu bibazo',
    'admin.parties.chip.frozen': 'Byahagaritswe',
    'admin.parties.chip.verified': 'Byemejwe',
    'admin.parties.chip.pending': 'Bitegereje',
    'admin.parties.chip.kyc_overdue': 'KYC yarengeje igihe',
    'admin.parties.chip.restricted': 'Bifite imbogamizi',
    'admin.parties.chip.active': 'Bikora',
    'admin.parties.chip.licence_expired': 'Uruhushya rwarangiye',
    'admin.parties.chip.suspended': "Byahagaritswe by'agateganyo",
    'admin.parties.select.sector': 'Urwego',
    'admin.parties.select.country': 'Igihugu',
    'admin.parties.select.sort': 'Tondeka',
    'admin.parties.stats.total_businesses': 'Ibigo byose',
    'admin.parties.stats.avg_rating': 'Amanota ugereranyije',
    'admin.parties.stats.active_notes': 'Inyandiko zikora',
    'admin.parties.stats.capital_raised': 'Imari yatewe inkunga',
    'admin.parties.stats.total_investors': 'Abashoramari bose',
    'admin.parties.stats.kyc_verified': 'KYC yemejwe',
    'admin.parties.stats.awaiting_kyc': 'Bategereje KYC',
    'admin.parties.stats.total_aum': 'Imari yose icungwa',
    'admin.parties.stats.active_audits': 'Igenzura riri gukorwa',
    'admin.parties.stats.active_partners': 'Abafatanyabikorwa bakora',
    'admin.parties.stats.pending_partners': 'ICPAR itegerejwe',
    'admin.parties.stats.licences_expiring': 'Impushya zirangira ≤ iminsi 30',
    'admin.parties.stats.operators': 'Abakozi',
    'admin.parties.stats.approvers': 'Abemeza',
    'admin.parties.stats.frozen_accounts': 'Byahagaritswe',
    'admin.parties.count.business': 'Ibigo {count}',
    'admin.parties.count.investor': 'Abashoramari {count}',
    'admin.parties.count.auditor': 'Abagenzuzi {count}',
    'admin.parties.count.staff': 'Abakozi {count}',
    'admin.parties.showing.business':
        "Herekanwa {shown} bya mbere muri {total} by'ibigo",
    'admin.parties.showing.investor':
        "Herekanwa {shown} ba mbere muri {total} b'abashoramari",
    'admin.parties.showing.auditor':
        "Herekanwa {shown} ba mbere muri {total} b'abagenzuzi",
    'admin.parties.showing.staff':
        "Herekanwa {shown} ba mbere muri {total} b'abakozi",
    'admin.parties.table.business': 'Ibigo',
    'admin.parties.table.investor': 'Abashoramari',
    'admin.parties.table.auditor': 'Abagenzuzi',
    'admin.parties.table.staff': 'Abakozi',
    'admin.parties.empty.business': "Nta kigo gihuye n'ibi byatoranyijwe.",
    'admin.parties.empty.investor':
        "Nta mushoramari uhuye n'ibi byatoranyijwe.",
    'admin.parties.empty.auditor': "Nta mugenzuzi uhuye n'ibi byatoranyijwe.",
    'admin.parties.empty.staff': "Nta mukozi uhuye n'ibi byatoranyijwe.",
    'admin.parties.col.business': 'Ikigo',
    'admin.parties.col.rating': 'Amanota',
    'admin.parties.name_pending': 'Izina ritaraboneka',
    'admin.parties.col.active_notes': 'Inyandiko zikora',
    'admin.parties.col.investors': 'Abateye inkunga',
    'admin.parties.col.raised': 'Byatewe inkunga',
    'admin.parties.col.capacity': 'Ubushobozi bwakoreshejwe',
    'admin.parties.col.status': 'Imiterere',
    'admin.parties.col.investor': 'Umushoramari',
    'admin.parties.col.kyc': 'KYC',
    'admin.parties.col.portfolio': 'Byashowe + byemewe',
    'admin.parties.col.wallet': 'Igikapu',
    'admin.parties.col.holdings': 'Imigabane',
    'admin.parties.col.businesses': 'Ibigo',
    'admin.parties.col.partner': 'Umufatanyabikorwa',
    'admin.parties.col.district': 'Akarere',
    'admin.parties.col.engagements': 'Imirimo',
    'admin.parties.col.on_time': 'Ku gihe',
    'admin.parties.col.share_mtd': "Umugabane w'uku kwezi",
    'admin.parties.col.operator': 'Umukozi',
    'admin.parties.col.role': 'Inshingano',
    'admin.parties.badge.frozen': 'Yahagaritswe',
    'admin.parties.badge.kyc_overdue': 'KYC yarengeje igihe',
    'admin.parties.badge.restricted': 'Ifite imbogamizi',
    'admin.parties.health.healthy': 'Bimeze neza',
    'admin.parties.health.watch': 'Bikurikiranwa',
    'admin.parties.health.distressed': 'Biri mu bibazo',
    'admin.parties.health.active': 'Irakora',
    'admin.parties.health.kyc_pending': 'KYC itegerejwe',
    'admin.parties.health.frozen': 'Byahagaritswe',
    'admin.parties.health.not_tracked': 'Ntibikurikiranwa',
    'admin.parties.kyc.verified': 'Byemejwe',
    'admin.parties.kyc.pending': 'Bitegereje',
    'admin.parties.kyc.overdue': 'Byarengeje igihe',
    'admin.parties.kyc.rejected': 'Byanzwe',
    'admin.parties.status.frozen': 'Yahagaritswe',
    'admin.parties.status.suspended': 'Yahagaritswe',
    'admin.parties.status.restricted': 'Ifite imbogamizi',
    'admin.parties.status.verified': 'Yemejwe',
    'admin.parties.status.pending': 'Itegereje',
    'admin.parties.status.active': 'Irakora',
    'admin.parties.standing.active': 'Arakora',
    'admin.parties.standing.pending': 'Ategereje kwemezwa',
    'admin.parties.standing.licence_expired': 'Uruhushya rwarangiye',
    'admin.parties.standing.suspended': "Yahagaritswe by'agateganyo",
    'admin.matrix.title': "Uburenganzira bw'inshingano",
    'admin.matrix.info':
        'Icyo buri rwego rwemerewe gukora. Inzego zo hasi zibuzwa ibikorwa byangiza, ntizihishwa gusa.',
    'admin.matrix.col.capability': 'Ubushobozi',
    'admin.matrix.col.detail': 'Icyo bwemerera',
    'admin.matrix.granted': 'Byemewe',
    'admin.matrix.withheld': 'Ntibyemewe',
    'admin.permission.admin.open': 'Gufungura konsole',
    'admin.permission_detail.admin.open':
        "Kwinjira muri konsole y'abakozi, imbonerahamwe yayo n'Abakozi n'inshingano.",
    'admin.permission.businesses.view': 'Kureba ibigo',
    'admin.permission_detail.businesses.view':
        "Gusoma urutonde rw'ibigo n'amakuru yose ya buri kigo.",
    'admin.permission.businesses.verify': 'Kugenzura ibigo',
    'admin.permission_detail.businesses.verify':
        "Gufata icyemezo ku igenzura ry'ibigo, handitswe impamvu.",
    'admin.permission.investors.verify': 'Kugenzura abashoramari',
    'admin.permission_detail.investors.verify':
        "Gufata icyemezo ku igenzura ry'umwirondoro w'abashoramari no gusoma Ibikorwa n'igenzura.",
    'admin.permission.applications.review': 'Gusuzuma ubusabe',
    'admin.permission_detail.applications.review':
        'Gusuzuma ubusabe bwo gukusanya imari no kubwemerera gutangazwa.',
    'admin.permission.audit.partners.verify':
        'Kugenzura abafatanyabikorwa mu igenzura',
    'admin.permission_detail.audit.partners.verify':
        'Kwemeza ibigo bya CPA no kureba aho abafatanyabikorwa bakorera.',
    'admin.permission.audit.assignments.manage': 'Gutanga igenzura',
    'admin.permission_detail.audit.assignments.manage':
        'Kohereza no kwimurira igenzura ku bafatanyabikorwa mu igenzura.',
    'admin.permission.audit.reports.view': "Gusoma raporo z'igenzura",
    'admin.permission_detail.audit.reports.view':
        "Gufungura raporo z'igenzura zafunzwe n'ibimenyetso byazo.",
    'admin.permission.consent.documents.record':
        "Kwandika inyandiko z'amategeko",
    'admin.permission_detail.consent.documents.record':
        "Kwandika verisiyo nshya z'Amabwiriza n'Itangazo ry'ibanga.",
    'admin.permission.ledger.view': "Kureba igitabo cy'imari",
    'admin.permission_detail.ledger.view':
        "Gusoma ibyanditswe mu gitabo cy'imari n'amafaranga asigaye.",
    'admin.permission.disbursements.view': 'Kureba kwishyura',
    'admin.permission_detail.disbursements.view':
        "Kureba urutonde rw'ibyishyurwa na buri kurekurwa.",
    'admin.permission.disbursements.hold': 'Guhagarika kwishyura',
    'admin.permission_detail.disbursements.hold':
        'Guhagarika ubwishyu mbere y’uko burekurwa.',
    'admin.permission.disbursements.requery': 'Kongera kubaza ku bwishyu',
    'admin.permission_detail.disbursements.requery':
        "Kubaza utanga serivisi z'ubwishyu uko ubwishyu buhagaze ubu.",
    'admin.permission.staging.mail.testers.manage': 'Gucunga abagerageza imeri',
    'admin.permission_detail.staging.mail.testers.manage':
        'Guhitamo abakira imeri kuri staging.',
    'admin.permission.staff.manage': 'Gucunga abakozi',
    'admin.permission_detail.staff.manage':
        "Kongeramo umukozi, cyangwa guhagarika cyangwa gusubizaho uburenganzira bw'undi mukozi, hatanzwe impamvu.",
    'admin.permission.accounts.freeze': 'Guhagarika konti',
    'admin.permission_detail.accounts.freeze':
        "Guhagarika by'agateganyo uburenganzira bw'umushoramari cyangwa ubucuruzi kuri porogaramu yabo.",
    'admin.permission.capacity.adjust': 'Guhindura ubushobozi',
    'admin.permission_detail.capacity.adjust':
        'Guhindura umubare ubucuruzi bushobora gukusanya.',
    'admin.permission.records.edit': 'Guhindura amakuru',
    'admin.permission_detail.records.edit':
        'Guhindura amakuru yabitswe ku nyandiko iyo ari yo yose.',
    'admin.permission.ledger.correct': 'Gukosora ibyakozwe',
    'admin.permission_detail.ledger.correct':
        'Gukosora igikorwa cyarangiye hakoreshejwe igikorwa gishya gihujwe nacyo, hatanzwe impamvu.',
    'admin.permission.policies.change': 'Amategeko',
    'admin.permission_detail.policies.change':
        "Guhindura amategeko y'ibiciro, ibisabwa n'ibikorwa byikora.",
    'admin.permission.records.delete': 'Gusiba',
    'admin.permission_detail.records.delete':
        'Gukura burundu inyandiko kuri platform.',
    'admin.matrix.withheld.not_built': 'Nta ruhare kugeza ubu: ntikirubakwa',
    'admin.matrix.withheld.by_rule':
        "Nta ruhare, ku itegeko: ibyanditswe mu gitabo cy'imari n'igenzura ntibisibwa",
    'admin.action_log.title': "Igitabo cy'ibikorwa by'ubuyobozi",
    'admin.staff.approvals.title': 'Gutegereza kwemezwa n’undi muyobozi mukuru',
    'admin.staff.approvals.body':
        'Uburenganzira bw’umuyobozi mukuru ntibuhinduka kugeza bwemejwe n’undi muyobozi mukuru ubufitiye uburenganzira. Uwasabye n’uwasabiriwe ntibashobora kwemeza.',
    'admin.staff.approvals.grant':
        '{requester} yasabye uburenganzira bw’umuyobozi mukuru.',
    'admin.staff.approvals.reactivate':
        '{requester} yasabye kongera gufungura uburenganzira bw’umuyobozi mukuru.',
    'admin.staff.approvals.form': 'Emeza uburenganzira bwa {recipient}',
    'admin.staff.approvals.submit': 'Emeza uburenganzira bw’umuyobozi mukuru',
    'admin.staff.approvals.waiting':
        'Gutegereza ko undi muyobozi mukuru abyemeza.',
    'admin.staff.invite.open': '+ Tumira umukozi',
    'admin.staff.invite.title': 'Tumira umukozi',
    'admin.staff.invite.body':
        "Ha konti isanzweho uburenganzira bwo kwinjira muri console n'uruhare rumwe. Umukozi abanza kwiyandikisha kuri konti ye yihariye no gufungura kwinjira mu ntambwe ebyiri. Impinduka yandikwa n'izina ryawe n'impamvu.",
    'admin.staff.invite.email': 'Imeli ya konti',
    'admin.staff.invite.role': 'Uruhare',
    'admin.staff.invite.cancel': 'Hagarika',
    'admin.staff.invite.submit': 'Tanga uburenganzira',
    'admin.staff.signed_in_as': 'Winjiye nka',
    'admin.staff.access_full': 'Uburenganzira bwose',
    'admin.staff.access_count': 'Ubushobozi {granted} kuri {total}',
    'admin.action_log.info':
        "Impinduka ziheruka ku burenganzira bw'abakozi, izigezweho mbere: uwazikoze, uwo zahinduriye uburenganzira n'impamvu.",
    'admin.action_log.empty': "Nta gikorwa cy'ubuyobozi kiranditswe.",
    'admin.action_log.col.action': 'Igikorwa',
    'admin.action_log.col.by': 'Na',
    'admin.action_log.col.target': 'Uwo bireba',
    'admin.action_log.col.reason': 'Impamvu',
    'admin.action_log.col.when': 'Ryari',
    'admin.action_log.action.staff_suspended': 'Uburenganzira bwahagaritswe',
    'admin.action_log.action.staff_enabled': 'Uburenganzira bwemejwe',
    'admin.action_log.action.staff_granted': 'Yongewemo nka {role}',
    'admin.permission.reconciliation.view': 'Kureba guhuza konti',
    'admin.permission_detail.reconciliation.view':
        "Kugereranya igitabo cy'imari n'imbonerahamwe z'abatanga serivisi.",
    'admin.permission.exceptions.view': 'Kureba ibidasanzwe',
    'admin.permission_detail.exceptions.view':
        "Kureba ibidasanzwe bifunguye mu Kigo cy'ingaruka.",
    'admin.permission.messaging.view': 'Kureba ubutumwa',
    'admin.permission_detail.messaging.view': "Gusoma amateka y'ubutumwa.",
    'admin.permission.primary.view': "Kureba isoko ry'ibanze",
    'admin.permission_detail.primary.view':
        "Gukurikirana ikusanyamari riri gukorwa n'urutonde rw'inoti.",
    'admin.permission.payments.view': 'Kureba ubwishyu',
    'admin.permission_detail.payments.view': "Gufungura Ikigo cy'ubwishyu.",
    'admin.permission.complaints.answer': 'Gusubiza ibirego',
    'admin.permission_detail.complaints.answer':
        "Gusubiza no gufunga ibirego by'abitabiriye.",
    'admin.permission.health.view': 'Kureba ubuzima bwa sisitemu',
    'admin.permission_detail.health.view':
        "Kureba imirongo y'ibitegereje, imirimo n'uko serivisi zihagaze.",
    'admin.parties.you': 'Wowe',
    'admin.parties.drawer_label': "{name}, amakuru y'uruhande",
    'admin.parties.type.business': 'Ikigo',
    'admin.parties.type.investor': 'Umushoramari',
    'admin.parties.type.auditor': 'Umugenzuzi',
    'admin.parties.type.staff': 'Umukozi',
    'admin.parties.app.business': 'Ikigo',
    'admin.parties.app.investor': 'Umushoramari',
    'admin.parties.app.auditor': 'Umugenzuzi',
    'admin.parties.app.staff': 'Ubuyobozi',
    'admin.parties.tabs': "Amakuru y'uruhande",
    'admin.parties.tab.overview': 'Incamake',
    'admin.parties.tab.activity': 'Ibikorwa',
    'admin.parties.tab.controls': 'Igenzura',
    'admin.parties.frozen_note':
        'Yahagaritswe na {actor} ku {at}. Reba Igenzura urebe impamvu no kuyifungura.',
    'admin.parties.kyc_overdue_note':
        'Kongera kwemeza KYC byarengeje igihe kuva {date}.',
    'admin.parties.stat.active_notes': 'Inyandiko zikora',
    'admin.parties.stat.investors': 'Abateye inkunga',
    'admin.parties.stat.raised': 'Byatewe inkunga',
    'admin.parties.stat.rating': 'Amanota ya Rozine',
    'admin.parties.stat.capacity': 'Ubushobozi',
    'admin.parties.stat.capacity_used': 'Ubushobozi bwakoreshejwe',
    'admin.parties.stat.portfolio': 'Byashowe + byemewe',
    'admin.parties.stat.wallet': 'Igikapu',
    'admin.parties.stat.holdings': 'Imigabane',
    'admin.parties.stat.businesses': 'Ibigo',
    'admin.parties.stat.kyc_due': 'Igihe cya KYC',
    'admin.parties.stat.engagements': 'Imirimo',
    'admin.parties.stat.on_time': 'Ku gihe',
    'admin.parties.stat.share_mtd': "Umugabane w'uku kwezi",
    'admin.parties.stat.role': 'Inshingano',
    'admin.parties.stat.last_sign_in': 'Aheruka kwinjira',
    'admin.parties.list.active_notes': 'Inyandiko zikora',
    'admin.parties.list.holdings': 'Imigabane',
    'admin.parties.list.engagements': 'Imirimo',
    'admin.parties.list_empty': 'Nta kintu kirimo.',
    'admin.parties.holding_detail': {
        one: '{amount} · inyandiko {count}',
        other: '{amount} · inyandiko {count}',
    },
    'admin.parties.note_status.draft': 'Umushinga',
    'admin.parties.note_status.active': 'Iri ku isoko',
    'admin.parties.note_status.funded': 'Yakusanyijwe',
    'admin.parties.note_status.repaying': 'Irishyurwa',
    'admin.parties.note_status.completed': 'Yarangiye',
    'admin.parties.note_status.failed': 'Yananiranye',
    'admin.parties.history': 'Ibikorwa',
    'admin.parties.history_empty': 'Nta gikorwa kiranditswe.',
    'admin.parties.kyc_title': 'KYC no kwemeza',
    'admin.parties.kyc_current': 'Uko bimeze ubu',
    'admin.parties.kyc_due': 'Kongera kwemeza bigomba kuba {date}',
    'admin.parties.verify_kyc': '✓ Emeza KYC',
    'admin.parties.verify_licence': '✓ Emeza uruhushya',
    'admin.parties.reject': 'Anga',
    'admin.parties.licence_title': 'Uruhushya rwa ICPAR no kwemeza',
    'admin.parties.licence_status': 'Uko kwemeza bimeze',
    'admin.parties.licence.verified': 'Rwemejwe',
    'admin.parties.licence.pending': 'Rutegereje',
    'admin.parties.licence.expired': 'Rwarangiye',
    'admin.parties.licence_field.member_id': 'Nimero ya ICPAR',
    'admin.parties.licence_field.licence': 'Uruhushya rwa PPC',
    'admin.parties.licence_field.expires_on': 'Rurangira',
    'admin.parties.licence_field.district': 'Akarere',
    'admin.parties.account_state': 'Uko konti imeze',
    'admin.parties.frozen_by': 'Yahagaritswe na {actor} · {at}',
    'admin.parties.legal_hold':
        "Icyemezo cy'amategeko gituma iyi konti ikomeza guhagarikwa. Ubwubahirize cyangwa Amategeko gusa ni bo bashobora kuyifungura.",
    'admin.parties.freeze': 'Hagarika konti',
    'admin.parties.release': 'Ongera ukoreshe konti',
    'admin.parties.freeze_caption':
        "Guhagarika bikuraho ako kanya uburenganzira bwa konti muri porogaramu ya {app}. Bishobora gusubizwa igihe icyo ari cyo cyose; guhagarika no gufungura byombi bigira impamvu n'izina.",
    'admin.parties.restrictions': 'Amateka yo guhagarika',
    'admin.parties.restrictions_empty': 'Iyi konti ntiyigeze ihagarikwa.',
    'admin.parties.stage.freeze.title': 'Hagarika {name}',
    'admin.parties.stage.freeze.body':
        'Kwinjira muri porogaramu ya {app} bihita bihagarara. Vuga impamvu, kugira ngo gufungura bizasuzumwe hashingiwe kuri yo.',
    'admin.parties.stage.freeze.cta': 'Hagarika konti',
    'admin.parties.stage.freeze.placeholder':
        "urugero: Ubutumwa bw'uburiganya FA-2291 ku aho amafaranga yoherezwa.",
    'admin.parties.stage.release.title': 'Ongera ukoreshe {name}',
    'admin.parties.stage.release.body':
        'Kwinjira muri porogaramu ya {app} bihita bigaruka. Vuga icyakuyeho impamvu yo guhagarika.',
    'admin.parties.stage.release.cta': 'Ongera ukoreshe konti',
    'admin.parties.stage.release.placeholder':
        "urugero: Utanga serivisi yafunze FA-2291 nk'ikosa; aho yoherezwa hongeye kugenzurwa.",
    'admin.parties.stage.verify_kyc.title': 'Emeza KYC ya {name}',
    'admin.parties.stage.verify_kyc.body':
        "Iyo byemejwe, konti ishobora gukora muri porogaramu ya {app} mu mbibi z'amabwiriza.",
    'admin.parties.stage.verify_kyc.cta': 'Emeza KYC',
    'admin.parties.stage.verify_kyc.placeholder':
        "urugero: Indangamuntu n'ifoto bihuye; aderesi yemejwe n'inyemezabwishyu.",
    'admin.parties.stage.reject_kyc.title': 'Anga KYC ya {name}',
    'admin.parties.stage.reject_kyc.body':
        'Konti ikomeza kutabasha gukora muri porogaramu ya {app}. Vuga icyananiranye kugira ngo bagikosore.',
    'admin.parties.stage.reject_kyc.cta': 'Anga KYC',
    'admin.parties.stage.reject_kyc.placeholder':
        "urugero: Ifoto y'indangamuntu ntisomeka; saba indi.",
    'admin.parties.stage.verify_licence.title': 'Emeza uruhushya rwa {name}',
    'admin.parties.stage.verify_licence.body':
        "Umufatanyabikorwa yinjira mu itsinda ry'akarere ke muri porogaramu ya {app}.",
    'admin.parties.stage.verify_licence.cta': 'Emeza uruhushya',
    'admin.parties.stage.verify_licence.placeholder':
        'urugero: Urutonde rwa ICPAR rwagenzuwe uyu munsi; icyemezo gifite agaciro kugeza 2027.',
    'admin.parties.stage.reject_licence.title': 'Anga uruhushya rwa {name}',
    'admin.parties.stage.reject_licence.body':
        'Umufatanyabikorwa ntahabwa akazi muri porogaramu ya {app} kugeza uruhushya rwemewe rwemejwe.',
    'admin.parties.stage.reject_licence.cta': 'Anga uruhushya',
    'admin.parties.stage.reject_licence.placeholder':
        "urugero: Nimero y'umunyamuryango ntiri ku rutonde rwa ICPAR.",
    'admin.ledger.title': "Igitabo cy'imari",
    'admin.ledger.caption':
        "Buri rujya n'uruza rw'amafaranga, uhereye ku rushya.",
    'admin.ledger.count': 'Ibyanditswe {count}',
    'admin.ledger.table': "Igitabo cy'imari",
    'admin.ledger.search_label': "Shakisha mu gitabo cy'imari",
    'admin.ledger.search_placeholder':
        'Shakisha ubwoko, uruhande cyangwa indango…',
    'admin.ledger.col.time': 'Igihe',
    'admin.ledger.col.type': 'Ubwoko',
    'admin.ledger.col.from_to': 'Kuva → Kuri',
    'admin.ledger.col.reference': 'Indango',
    'admin.ledger.col.amount': 'Amafaranga',
    'admin.ledger.col.account': 'Konti',
    'admin.ledger.col.debit': 'Ibisohoka',
    'admin.ledger.col.credit': 'Ibyinjira',
    'admin.ledger.open_named': 'Fungura icyanditswe {id}',
    'admin.ledger.see_all': 'Reba ibyanditswe byose {count}',
    'admin.ledger.older': 'Ibyanditswe bya kera',
    'admin.ledger.newest': 'Subira ku bishya',
    'admin.ledger.empty': 'Nta cyanditswe muri iki gihe.',
    'admin.ledger.drawer_label': 'Icyanditswe {id}',
    'admin.ledger.fact.from': 'Kuva',
    'admin.ledger.fact.to': 'Kuri',
    'admin.ledger.fact.reference': 'Indango',
    'admin.ledger.fact.operation': 'Igikorwa',
    'admin.ledger.posted_by': 'Byanditswe na {actor} · {at}',
    'admin.ledger.origin':
        'Byatangijwe na {actor} · {at}. Ibikurikiyeho muri uru rugendo bigumana iyi nkomoko.',
    'admin.ledger.origin_none':
        'Nta gikorwa cyatangije iki cyanditswe kibitswe.',
    'admin.ledger.postings': 'Ibyanditswe',
    'admin.ledger.total': 'Igiteranyo',
    'admin.ledger.balanced': 'Biringaniye',
    'admin.ledger.unbalanced': 'Ntibiringaniye',
    'admin.ledger.contra_of': 'Iri kosora rinyuranye rikuraho',
    'admin.ledger.contra_by': "Yakuweho n'ikosora rinyuranye",
    'admin.ledger.immutable':
        'Ibyanditswe mu gitabo ntibihindurwa. Ikosora ni icyanditswe cyaryo kinyuranye, gihuzwa hano.',
    'admin.ledger.open_events': "Reba iki cyanditswe mu bubiko bw'ibikorwa →",
    'admin.events.immutable':
        "Inyandiko z'igenzura ntizihinduka. Nta kintu kuri iyi paji gishobora guhindurwa cyangwa gusibwa.",
    'admin.events.title': "Ububiko bw'igenzura",
    'admin.events.table': "Ububiko bw'igenzura",
    'admin.events.count': 'Ibyanditswe {count}',
    'admin.events.search_label': "Shakisha mu bubiko bw'igenzura",
    'admin.events.search_placeholder':
        'Shakisha ukoresha, igikorwa cyangwa ikintu…',
    'admin.events.preset.today': 'Uyu munsi',
    'admin.events.preset.7d': 'Iminsi 7',
    'admin.events.preset.30d': 'Iminsi 30',
    'admin.events.from': 'Itariki yo gutangira',
    'admin.events.to': 'Itariki yo kurangiza',
    'admin.events.to_word': 'kugeza',
    'admin.events.clear': 'Siba',
    'admin.events.clear_filters': 'Siba ibyatoranyijwe',
    'admin.events.filtered_empty':
        "Nta kintu mu bubiko gihuye n'ibi byatoranyijwe.",
    'admin.events.empty': 'Nta gikorwa kiranditswe.',
    'admin.events.col.when': 'Ryari',
    'admin.events.col.who': 'Nde',
    'admin.events.col.action': 'Igikorwa',
    'admin.events.col.object': 'Ikintu',
    'admin.events.col.source': 'Inkomoko',
    'admin.events.no_reason': 'Nta mpamvu yanditswe kuri iki gikorwa.',
    'admin.events.field': 'Umwanya',
    'admin.events.before': 'Mbere',
    'admin.events.after': 'Nyuma',
    'admin.events.no_changes': 'Nta gaciro kahindutse.',
    'admin.events.see_all': 'Reba ibyanditswe byose {count}',
    'admin.events.export': 'Kohereza hanze',
    'admin.events.export_running': 'Kohereza hanze birakorwa · {actor} · {at}',
    'admin.events.export_download': 'Kuramo ibyoherejwe',
    'admin.events.export_title': 'Ohereza ubu bubiko hanze',
    'admin.events.export_body':
        'Ibyoherezwa bikurikiza ibyatoranyijwe bigaragara kandi nabyo byandikwa mu bubiko. Vuga uwo bigenewe.',
    'admin.events.export_cta': 'Tangira kohereza',
    'admin.events.export_placeholder':
        'urugero: Icyifuzo cya RCMA 2026-114, ibikorwa bya Nzeri.',

    'business.wallet.title': 'Ikofi',
    'business.wallet.back': 'Subira ku Ahabanza',
    'business.wallet.available': 'Amafaranga ahari',
    'business.wallet.brand': 'Ikofi ya Rozine',
    'business.wallet.range.label': 'Igihe',
    'business.wallet.range.all': 'Igihe cyose',
    'business.wallet.range.start': 'Intangiriro',
    'business.wallet.range.now': 'Ubu',
    'business.wallet.range.from': 'Kuva',
    'business.wallet.range.to': 'Kugeza',
    'business.wallet.range.apply': 'Emeza',
    'business.wallet.range.clear': 'Igihe cyose',
    'business.wallet.export.label': 'Kuramo raporo y’ikofi',
    'business.wallet.export.pdf': 'Kuramo PDF',
    'business.wallet.export.csv': 'Kuramo Excel',
    'business.wallet.caption':
        'Amafaranga ya serivisi, ayishyurwa n’ayatanzwe anyura hano',
    'business.wallet.deposit.button': 'Shyiramo',
    'business.wallet.withdraw.button': 'Bikuza',
    'business.wallet.processing': 'Birimo gukorwa…',
    'business.wallet.history': "Amateka y'ibikorwa",
    'business.wallet.detail.when': "Itariki n'isaha",
    'business.wallet.detail.reference': 'Indanganturo',
    'business.wallet.detail.done': 'Birarangiye',

    'business.servicing.wallet.status.active': 'Birakora',
    'business.servicing.wallet.status.restricted': 'Byabujijwe',
    'business.servicing.wallet.restricted':
        'Byabujijwe kuva ku wa {date}. Kubitsa no kwishyura biracyakunda.',
    'business.servicing.wallet.pending': '{amount} bitegereje kwemezwa',
    'business.servicing.wallet.to_repayments': 'Jya ku kwishyura',
    'business.servicing.wallet.movement': "Ubwoko bw'igikorwa",
    'business.servicing.wallet.filter.external': 'Ibyabikijwe',
    'business.servicing.wallet.filter.internal': 'Ibyishyuwe',
    'business.servicing.wallet.entry.deposit': 'Kubitsa · {counterparty}',
    'business.servicing.wallet.entry.repayment': 'Kwishyura · {note}',
    'business.servicing.wallet.instalments': 'Ibice {list}',
    'business.servicing.wallet.empty': 'Nta kirimo kugeza ubu.',
    'business.servicing.wallet.older': 'Erekana ibya kera',
    'business.servicing.wallet.receipt.label': "Inyemezabwishyu y'igikorwa",
    'business.servicing.wallet.receipt.psp_fee': "Amafaranga y'utanga serivisi",
    'business.servicing.wallet.receipt.policy': "Verisiyo y'amabwiriza",
    'auditor.nav.home': 'Ahabanza',
    'auditor.nav.jobs': 'Imirimo',
    'auditor.nav.portfolio': 'Ibyo ushinzwe',
    'auditor.nav.profile': 'Umwirondoro',
    'auditor.nav.conflicts': 'Inyungu wagaragaje',
    'auditor.nav.jobs_badge': 'Imirimo {count} ifunguye',
    'auditor.clock.label': 'Igihe gisigaye kuri uyu murimo',
    'auditor.clock.time_left': 'Igihe gisigaye',
    'auditor.time.minutes_ago': 'hashize iminota {count}',
    'auditor.time.hours_ago': 'hashize amasaha {count}',
    'auditor.time.days_ago': 'hashize iminsi {count}',
    'auditor.home.head_title': 'Ahabanza',
    'auditor.home.wallet_owed': 'Ayo ugomba guhabwa',
    'auditor.home.withdraw': 'Bikuza',
    'auditor.home.statement': "Inyandiko y'ibyakozwe",
    'auditor.home.notifications': 'Ubutumwa',
    'auditor.home.notifications_unread': 'Ubutumwa, {count} butarasomwa',
    'auditor.home.greeting.morning': 'Mwaramutse,',
    'auditor.home.greeting.afternoon': 'Mwiriwe,',
    'auditor.home.greeting.evening': 'Muraho,',
    'auditor.home.tile.yield': 'Inyungu · {month}',
    'auditor.home.tile.deals': 'Amasezerano',
    'auditor.home.tile.licence': 'Uruhushya',
    'auditor.rating.word': 'Amanota',
    'auditor.rating.label': "Amanota y'umufatanyabikorwa {score} kuri 100",
    'auditor.availability.accepting': 'Ndakira amagenzura',
    'auditor.availability.paused': 'Byahagaritswe',
    'auditor.availability.home_sub':
        'Mu birometero {radius} · imirimo {max} icyarimwe',
    'auditor.availability.home_paused':
        'Kanda wongere wakire amagenzura yihuse',
    'auditor.availability.toggle': 'Kwakira amagenzura',
    'auditor.home.nearby_one': 'Igenzura ryihuse 1 hafi yawe',
    'auditor.home.nearby_other': 'Amagenzura yihuse {count} hafi yawe',
    'auditor.home.nearby_sub':
        'Irya hafi ni kuri km {distance} · uwemeye mbere afata dosiye',
    'auditor.home.in_progress': 'Birakorwa',
    'auditor.job.step_of': 'Intambwe {step} kuri {steps}',
    'auditor.job.status.overdue': 'Yarenze igihe',
    'auditor.job.status.awaiting_cosign': 'Bitegereje gusinyirwa',
    'auditor.job.reassigned': 'Wahawe uyu murimo',
    'auditor.standing.title': 'Uko uhagaze',
    'auditor.standing.on_time': 'Gusoza ku gihe',
    'auditor.standing.avg_variance': 'Ikinyuranyo mpuzandengo',
    'auditor.standing.jobs_done': 'Imirimo yakozwe',
    'auditor.standing.clock_expiries': 'Igihe cyarenze',
    'auditor.activity.title': 'Ibiheruka kuba',
    'auditor.activity.empty': 'Nta kirakorwa kuri dosiye zawe.',
    'auditor.activity.filed': 'Byatanzwe · {business}',
    'auditor.activity.filed_sub': '{when} · ikinyuranyo cya {variance}%',
    'auditor.activity.published': 'Raporo ya {month} yemejwe',
    'auditor.activity.repayment': 'Ubwishyu bwakiriwe',
    'auditor.activity.missed': 'Ubwishyu butatanzwe · {business}',
    'auditor.activity.missed_sub': '{when} · umugabane wawe urahagarara',
    'auditor.activity.deferred': 'Ubwishyu bwimuwe · {business}',
    'auditor.activity.payout': "Kwishyurwa umugabane w'inyungu",
    'auditor.sector.agriculture': 'Ubuhinzi',
    'auditor.sector.logistics': 'Ubwikorezi',
    'auditor.sector.manufacturing': 'Inganda',
    'auditor.sector.retail': 'Ubucuruzi buciriritse',
    'auditor.sector.energy': 'Ingufu',
    'auditor.sector.technology': 'Ikoranabuhanga',
    'auditor.sector.services': 'Serivisi',
    'auditor.jobs.head_title': 'Imirimo',
    'auditor.jobs.title': "Imirimo y'igenzura",
    'auditor.jobs.lead':
        'Igenzura ryo ku kibanza riri mu birometero {radius}. Uwemeye mbere afata dosiye. Buri igenzura ryihuse rigomba kurangira mu masaha {hours} nyuma yo koherezwa.',
    'auditor.jobs.map_label':
        "Ikarita y'akarere ka km {radius} ufite imirimo {count} ifunguye, aho iri hagereranyijwe",
    'auditor.jobs.map_badge': 'km {radius} · {count} ifunguye',
    'auditor.jobs.map_label_page':
        "Ikarita y'akarere ka km {radius} ifite imirimo {count} kuri uru rupapuro, aho iri hagereranyijwe",
    'auditor.jobs.map_badge_page': 'km {radius} · {count} kuri uru rupapuro',
    'auditor.jobs.assigned': 'Ushinzwe',
    'auditor.jobs.distance': 'Intera',
    'auditor.jobs.km': 'km {distance}',
    'auditor.jobs.sector_unavailable': 'Urwego ntirubonetse',
    'auditor.jobs.kind_monthly': 'Isura rya buri kwezi',
    'auditor.jobs.kind_flash': 'Igenzura ryihuse',
    'auditor.jobs.show_more': 'Erekana ibindi',
    'auditor.jobs.conflicts_link': 'Inyungu wagaragaje →',
    'auditor.jobs.page_empty':
        'Nta kigaragara kuri uru rupapuro. Imirimo ya mbere ishobora gukurikiraho.',
    'auditor.jobs.assigned_empty': 'Nta murimo wemeye uri ku isaha ubu.',
    'auditor.jobs.assigned_page_empty':
        'Nta murimo ushinzwe kuri uru rupapuro.',
    'auditor.jobs.requested': 'Ayasabwe',
    'auditor.jobs.dscr': 'DSCR',
    'auditor.jobs.term': 'Igihe',
    'auditor.jobs.term_months': 'amezi {months}',
    'auditor.jobs.view_file': 'Reba ubusabe bwose →',
    'auditor.jobs.decline': 'Anga',
    'auditor.jobs.declare_conflict': 'Tangaza inyungu bwite',
    'auditor.jobs.empty':
        'Nta murimo ufunguye mu karere kawe. Tuzakumenyesha igihe dosiye yo ku rwego rwa 2 ikeneye igenzura mu birometero {radius}.',
    'auditor.monthly.title': 'Raporo za buri kwezi',
    'auditor.monthly.lead':
        "Buri kwezi ufungura dosiye y'igenzura y'ibigo ushinzwe. Genzura ku kibanza ushyireho kashe mbere y'itariki ya 7 — ikigo gisinya nyuma.",
    'auditor.monthly.to_open': 'Amagenzura yo gufungura',
    'auditor.monthly.waiting': '{count} bitegereje',
    'auditor.monthly.not_opened': "{month} · dosiye y'igenzura ntirafungurwa",
    'auditor.monthly.not_opened_undated': "Dosiye y'igenzura ntirafungurwa",
    'auditor.desk_review.eyebrow': 'Isuzuma ry’impapuro · {month}',
    'auditor.desk_review.eyebrow_undated': 'Isuzuma ry’impapuro',
    'auditor.desk_review.status.disputed': 'Riri gusuzumwa',
    'auditor.desk_review.status.escalated': 'Ryoherejwe hejuru',
    'auditor.desk_review.note':
        "Ntibishobora guhindurwa kugeza ubuyobozi bwa Rozine bukemuye ikibazo. Kuvugana mu buryo butaziguye n'uyu mucuruzi byahagaritswe.",
    'auditor.desk_review.proofs': 'Ibimenyetso byatanzwe',
    'auditor.desk_review.proofs_value': {
        one: 'Ikimenyetso {count}',
        other: 'Ibimenyetso {count}',
    },
    'auditor.desk_review.variance': 'Ikinyuranyo cyawe kiremereye',
    'auditor.desk_review.view': 'Reba raporo →',
    'auditor.monthly.open': 'Fungura igenzura',
    'auditor.monthly.show_all': 'Erekana amadirishya {count} yose afunguye',
    'auditor.monthly.show_fewer': 'Erekana bike · {shown} kuri {count}',
    'auditor.monthly.due_line': '{note} · Bigomba {date}',
    'auditor.monthly.status.in_progress': 'Birakorwa',
    'auditor.monthly.status.overdue': 'Byarenze igihe',
    'auditor.monthly.status.changes_requested': 'Byasubijwe',
    'auditor.monthly.status.awaiting_cosign': 'Bitegereje gusinywa',
    'auditor.monthly.inflow': 'Ayinjiye',
    'auditor.monthly.outflow': 'Ayasohotse',
    'auditor.monthly.cover': 'Ubwishingizi',
    'auditor.monthly.review': 'Suzuma wemeze ku kibanza →',
    'auditor.monthly.empty':
        'Nta raporo ya buri kwezi itegereje igenzura ryawe ubu.',
    'auditor.sheet.close': 'Funga',
    'auditor.sheet.cancel': 'Reka',
    'auditor.decline.title': 'Anga {business}',
    'auditor.decline.lead':
        "Umurimo usubizwa mu kugabanywa. Hitamo impamvu; izandikwa hamwe n'uko wanze.",
    'auditor.decline.placeholder': 'Ikikubuza gufata uyu murimo',
    'auditor.decline.submit': 'Anga umurimo',
    'auditor.conflict.sheet_title': 'Tangaza inyungu ufite muri {business}',
    'auditor.conflict.body':
        "Niba hari inyungu ufite mu kigo ushinzwe kugenzura, bivuge. Itangazo ryandikwa, kandi inyungu ibuza akazi ihita ihagarika akazi kawe kuri dosiye mu gihe Ishami ry'Igenzura ritegura kuyiha undi. Ntuzigera usabwa kugenzura dosiye wazanye — Rozine irabibuza burundu.",
    'auditor.conflict.kind_label': 'Ni iyihe nyungu',
    'auditor.conflict.kind.financial_interest': "Inyungu y'amafaranga",
    'auditor.conflict.kind.role_tie':
        'Nyirayo, umuyobozi, umukozi cyangwa umujyanama',
    'auditor.conflict.kind.family_or_business':
        "Isano ry'umuryango cyangwa ubucuruzi",
    'auditor.conflict.kind.other': 'Ibindi',
    'auditor.conflict.note_label':
        'Ibisobanuro bishingiye ku byabaye (ni ngombwa)',
    'auditor.conflict.note_placeholder': 'Iyo nyungu ni iyihe kandi kuva ryari',
    'auditor.conflict.submit': 'Tangaza inyungu',
    'auditor.outcome.done': 'Birangiye',
    'auditor.outcome.conflict.title': 'Inyungu yatangajwe',
    'auditor.outcome.conflict.recorded':
        'Itangazo ryawe kuri {business} ryanditswe. Ntirihagarika akazi kawe kuri uyu murimo.',
    'auditor.outcome.declined.title': 'Umurimo wanzwe',
    'auditor.outcome.declined.body':
        '{business} yasubijwe mu kugabanywa kandi impamvu yawe yanditswe.',
    'auditor.outcome.sealed.title': 'Igenzura ryashyizweho kashe riratangwa',
    'auditor.outcome.sealed.flash':
        'Raporo yo ku kibanza ya {business} yashyizweho kashe. {business} isinya bitarenze {date}; moteri itanga amanota ishingiye ku byo wabonye.',
    'auditor.outcome.sealed.monthly':
        "Raporo ya {month} ya {business} — inyandiko z'imari zoherejwe n'ibyo wabonye — yashyizweho kashe. {business} isinya bitarenze {date}.",
    'auditor.outcome.suggested.title': 'Impinduka zasabwe',
    'auditor.outcome.suggested.body':
        "Ikigo cyabonye impamvu yawe n'ibisobanuro, kandi gishobora kongera kohereza inyandiko ngo uyigenzure.",
    'auditor.outcome.rejected.title': 'Inyandiko yanzwe',
    'auditor.outcome.rejected.body':
        "Iyi verisiyo y'inyandiko ntishobora kugenzurwa kandi yafunzwe impamvu yawe yanditswe. Si icyemezo ku nguzanyo, kandi ibimenyetso n'amateka ya raporo birabikwa.",
    'auditor.sheet.back': 'Subira inyuma',
    'auditor.file.head_title': '{business} · dosiye',
    'auditor.file.label': "Dosiye y'ikigo {business}",
    'auditor.file.eyebrow_preview': "Incamake y'ubusabe",
    'auditor.file.eyebrow_file': "Dosiye y'ikigo",
    'auditor.file.place': '{district} · km {distance}',
    'auditor.file.continue': 'Komeza igenzura',
    'auditor.file.title': 'Suzuma ubusabe',
    'auditor.file.lead':
        'Ibyo {business} yatanze byose, byagenzuwe hakurikijwe imbibi za Rozine. Igenzura ryawe ku kibanza rikemura ibyo moteri idashobora kwemeza iri kure.',
    'auditor.file.lead_provisional': 'Ubusabe bwa {business} uko buhagaze ubu.',
    'auditor.file.lead_no_prescreen':
        'Nta isuzuma ryikora ry’ibanze ryanditswe.',
    'auditor.file.lead_field_check':
        'Igenzura ryawe ku kibanza ryemeza ibidashobora kugenzurwa uri kure.',
    'auditor.file.reassigned_title': 'Wahawe iyi dosiye',
    'auditor.file.reassigned_body':
        'Igihe cya mbere kiracyakurikizwa — kwimurwa ntibisubiramo isaha. Ibimenyetso byari kuri dosiye bigumaho.',
    'auditor.file.raise': 'Igishoro gisabwa',
    'auditor.file.term_months': 'Amezi {months}',
    'auditor.file.return': 'Inyungu yose',
    'auditor.file.return_value': '{pct}% yose hamwe',
    'auditor.file.use_of_funds': 'Icyo amafaranga azakoreshwa',
    'auditor.file.documents': 'Inyandiko zatanzwe',
    'auditor.file.no_documents':
        'Nta nyandiko zatanzwe ziranditswe kuri iyi dosiye.',
    'auditor.file.doc_status.parsed': '✓ OCR',
    'auditor.file.doc_status.present': '✓ Birahari',
    'auditor.file.prescreen': "Isuzuma ry'ibanze ryikora",
    'auditor.file.no_prescreen':
        "Nta gisubizo cy'isuzuma ry'ibanze ryikora kiratangazwa kuri iyi dosiye.",
    'auditor.file.check.met': 'Byujujwe',
    'auditor.file.check.flag': 'Ikimenyetso',
    'auditor.file.document.pdf': 'Raporo ya konti (PDF)',
    'auditor.file.document.csv': 'Raporo ya konti (CSV)',
    'auditor.file.document.received': 'Yakiriwe {date}',
    'auditor.file.document.received_records': {
        one: 'Yakiriwe {date} · inyandiko {count}',
        other: 'Yakiriwe {date} · inyandiko {count}',
    },
    'auditor.file.brief.flash.reason':
        "Nta noti ishobora gushyirwa ku isoko mbere y'uko Umugenzuzi asura ubucuruzi agafunga raporo y'ibanze. Rozine yagenzuye kure raporo za konti zatanzwe; uruzinduko rwawe rwemeza ku kibanza ko ubucuruzi, ububiko bwabwo n'ibitabo byabwo ari ukuri mbere y'uko amafaranga atangwa.",
    'auditor.file.brief.flash.step_1':
        "Iyandikishe ugeze aho ubucuruzi bukorera; aho uri ako kanya handikwa hamwe n'igenzura.",
    'auditor.file.brief.flash.step_2':
        "Fata amafoto asabwa ukoresheje kamera ya porogaramu uri imbere y'imbibi z'aho ubucuruzi bukorera. Gushyiraho amafoto ari muri telefoni ntibyemewe.",
    'auditor.file.brief.flash.step_3':
        "Bara ububiko ku kibanza wandike agaciro kabwo. Ikinyuranyo cyose n'umubare watangajwe kigomba gusobanurwa mbere yo gufunga.",
    'auditor.file.brief.flash.step_4':
        "Huza ibitabo by'impapuro n'inyemezabwishyu na raporo za konti zagenzuwe, ukurikije uburyo bwemeranyijweho bwa ISRS 4400.",
    'auditor.file.brief.monthly.reason':
        'Buri kwezi igihe noti itararangira, Umugenzuzi agenzura ku kibanza inyandiko z’ubucuruzi. Muri porogaramu ikoreshwa ubu, funga raporo y’ukwezi kwagenzuwe bitarenze impera z’umunsi wa 7 w’ukwezi gukurikira (ku isaha ya Kigali). Ubucuruzi buhabwa amasaha 24 yo kwemeza cyangwa kujuririra.',
    'auditor.file.brief.monthly.step_1':
        "Suzuma raporo za konti z'ukwezi ubucuruzi bwashyizeho.",
    'auditor.file.brief.monthly.step_2':
        "Bara amafaranga n'ibicuruzwa biri mu bubiko ku kibanza. Ikinyuranyo cyose kigomba gusobanurwa mbere yo gufunga.",
    'auditor.file.brief.monthly.step_3':
        "Fata amafoto asabwa ukoresheje kamera ya porogaramu uri imbere y'imbibi z'aho ubucuruzi bukorera.",
    'auditor.file.brief.monthly.step_4':
        'Funga bitarenze itariki ntarengwa iri kuri raporo: impera z’umunsi wa 7 w’ukwezi gukurikira (ku isaha ya Kigali) muri porogaramu ikoreshwa ubu.',
    'auditor.file.why': 'Impamvu igenzura ryo ku kibanza rikenewe',
    'auditor.file.mandate': 'Ibyo ugomba kugenzura ku kibanza',
    'auditor.file.history': 'Amateka ya dosiye',
    'auditor.file.first_visit': 'Uruzinduko rwa mbere',
    'auditor.file.first_visit_body':
        'Nta genzura ryabanje ririho kuri iki kigo.',
    'auditor.file.last_audit': 'Igenzura riheruka {date} · {kind} · {by}',
    'auditor.file.kind.flash': 'Igenzura ryihuse',
    'auditor.file.kind.monthly': 'Raporo ya buri kwezi',
    'auditor.file.flags': 'Ibimenyetso kuri dosiye',
    'auditor.file.no_flags': 'Nta kimenyetso kiri kuri dosiye.',
    'auditor.audit.head_title': '{business} · igenzura',
    'auditor.audit.label': 'Igenzura rya {business}',
    'auditor.audit.eyebrow_flash': 'Igenzura ryihuse ku kibanza',
    'auditor.audit.eyebrow_monthly': 'Raporo ya buri kwezi · igenzura',
    'auditor.audit.steps': "Intambwe z'igenzura",
    'auditor.audit.step.review': 'Isuzuma',
    'auditor.audit.step.check_in': 'Kwiyandikisha',
    'auditor.audit.step.photos': 'Amafoto',
    'auditor.audit.step.ledger': 'Igitabo',
    'auditor.audit.step.seal': 'Kashe',
    'auditor.audit.step.statements': 'Inyandiko za konti',
    'auditor.audit.step.count': 'Kubara',
    'auditor.audit.continue': 'Komeza',
    'auditor.audit.to_seal': 'Suzuma ushyireho kashe',
    'auditor.audit.start_count': 'Tangira kubara',
    'auditor.audit.to_photos': 'Komeza ku mafoto',
    'auditor.audit.back': 'Inyuma',
    'auditor.audit.back_to_jobs': 'Subira ku mirimo',
    'auditor.audit.offline':
        'Nta murandasi ufite. Nta kibura: ibyo wanditse biguma kuri iyi paji, kandi porogaramu yo gufata ibimenyetso ibibika mu ibanga ikabyohereza wongeye kubona umurandasi.',
    'auditor.audit.variance': 'Ikinyuranyo',
    'auditor.audit.within_tolerance': 'Biri mu rugero rwemewe',
    'auditor.audit.outside_tolerance': 'Ikinyuranyo kirenze urugero',
    'auditor.capture.title':
        'Bifatirwa muri porogaramu ya Rozine yo gufata ibimenyetso',
    'auditor.capture.body':
        "Amafoto no kwiyandikisha ku kibanza bifatwa muri porogaramu yo gufata amakuru, ntibifatirwa ku rubuga. Isaha, aho byafatiwe n'igenzura rya telefoni bya buri kimwe bigaragara uko seriveri ibyanditse.",
    'auditor.capture.open': 'Fungura porogaramu yo gufata ibimenyetso',
    'auditor.capture.status.not_started':
        'Porogaramu ntirafungurwa kuri uyu murimo',
    'auditor.capture.status.capturing':
        'Birafatwa kuri telefoni yawe · {received} kuri {expected} byakiriwe',
    'auditor.capture.status.syncing':
        'Birohererezwa · {received} kuri {expected} byakiriwe',
    'auditor.capture.status.complete': 'Ibintu {expected} byose byakiriwe',
    'auditor.capture.attention.no_signal':
        'Nta murandasi — porogaramu ibika byose mu ibanga ikabyohereza wongeye kubona umurandasi',
    'auditor.capture.attention.storage_full':
        'Ububiko bwa telefoni bwuzuye — shaka umwanya ngo porogaramu ikomeze gufata',
    'auditor.capture.attention.upload_failed':
        'Kohereza byanze — fungura porogaramu wongere ugerageze',
    'auditor.capture.last_sync': 'Iheruka koherezwa {when}',
    'auditor.checkin.title': 'Iyandikishe ku kibanza',
    'auditor.checkin.lead':
        "Emeza ko uri ku kigo koko. Aho uri handikwa hamwe n'igenzura.",
    'auditor.checkin.waiting': 'Dutegereje ko wiyandikisha',
    'auditor.checkin.position': '{position} · ±{accuracy} m',
    'auditor.checkin.done': 'Wiyandikishije ku kibanza · {time}',
    'auditor.checkin.open': 'Iyandikishe ukoresheje porogaramu',
    'auditor.checkin.review_title': 'Uku kwiyandikisha kuzasuzumwa',
    'auditor.checkin.review_body':
        "Ukuri kw'aho uri cyangwa intera n'aho ikigo cyanditse birenze amabwiriza. Komeza; ibikorwa by'igenzura bizabisuzuma.",
    'auditor.photos.title': "Amafoto y'ikibanza afite aho yafatiwe",
    'auditor.photos.lead':
        'Bifatwa ako kanya muri porogaramu — gukura amafoto mu bubiko byahagaritswe kugira ngo hatabaho uburiganya. {captured} kuri {required} bikenewe byafashwe.',
    'auditor.photos.extras': {
        one: 'Hiyongereyeho ifoto {count} y’inyongera.',
        other: 'Hiyongereyeho amafoto {count} y’inyongera.',
    },
    'auditor.photos.grid': "Amafoto y'ikibanza",
    'auditor.photos.pending': 'Ntirafatwa',
    'auditor.photos.captured': 'Yafashwe',
    'auditor.photos.untitled': 'Nta mutwe',
    'auditor.photos.add': 'Ongeraho ifoto',
    'auditor.photos.titles': "Ha imitwe amafoto y'inyongera",
    'auditor.photos.extra': 'Inyongera {n}',
    'auditor.photos.left': '{count} bisigaye',
    'auditor.photos.placeholder':
        'Icyo yerekana — urugero: icyumba gikonje cya 2 cyuzuye',
    'auditor.ledger.title': "Ububiko n'ibitabo by'ibaruramari",
    'auditor.ledger.lead':
        "Andika agaciro k'ububiko wabaze. Tukagereranya n'icyatangajwe — ikinyuranyo kirenze {tolerance} kigaragazwa.",
    'auditor.ledger.reported': 'Ububiko bwatangajwe',
    'auditor.ledger.observed':
        "Byabonetse ku kibanza · agaciro k'ububiko (RWF)",
    'auditor.ledger.observed_placeholder': 'urugero: 42,000,000',
    'auditor.ledger.attachments': "Inyandiko z'ibitabo by'ibaruramari",
    'auditor.ledger.none': 'Nta nyandiko yometseho',
    'auditor.ledger.accepted': '{parsed} kuri {count} byemewe',
    'auditor.ledger.rules':
        "Ohereza igitabo cy'umwimerere nka PDF cyangwa CSV (kugeza kuri MB 10). Igitabo cyaskaninwe gishobora kuba PDF; scan idafite inyandiko ishyirwa ku ruhande ngo isuzumwe n'umuntu.",
    'auditor.ledger.doc.scanning': 'Biragenzurwa … {detail}',
    'auditor.ledger.doc.parsed': 'Byemewe · {detail}',
    'auditor.ledger.doc.failed': 'Byanzwe · {detail}',
    'auditor.ledger.reading': 'Inyandiko irasomwa',
    'auditor.ledger.rescan': 'Ongera usikane iyi nyandiko',
    'auditor.ledger.file_input': "Dosiye y'igitabo",
    'auditor.ledger.attach': 'Ometseho igitabo (PDF cyangwa CSV)',
    'auditor.ledger.attach_another': 'Ongeraho ikindi gitabo',
    'auditor.ledger.reconciles':
        "Ibitabo byo ku mpapuro n'inyemezabwishyu bihura n'inyandiko za konti z'ikoranabuhanga.",
    'auditor.ledger.reconciles_blocked':
        'Banza wometseho nibura igitabo kimwe cyemewe.',
    'auditor.statements.title': 'Soma ukwezi',
    'auditor.statements.lead':
        'Ibyo inyandiko za banki, MoMo na POS ziri kuri dosiye zerekana kuri iki gihe. Nta cyo kuzuza hano.',
    'auditor.statements.unavailable':
        "Inyandiko z'iki gihe ntiziraboneka kuri dosiye",
    'auditor.statements.parsed': 'Byavanywe mu nyandiko za banki na MoMo',
    'auditor.statements.inflow': 'Ayinjiye yose',
    'auditor.statements.outflow': 'Ayasohotse yose',
    'auditor.statements.net': 'Amafaranga asigaye',
    'auditor.statements.cover': 'Ubushobozi bwo kwishyura',
    'auditor.statements.view': 'Reba',
    'auditor.count.title': "Kubara n'amafaranga",
    'auditor.count.lead':
        "Kanda ku bimenyetso wabonye koko, hanyuma wandike ibyo wabonye. Tugereranya buri mubare n'inyandiko ziri kuri dosiye — nta mubare hano uturuka ku kigo.",
    'auditor.count.financial': "Ibimenyetso by'imari",
    'auditor.count.vault': "Ububiko bw'ibimenyetso",
    'auditor.count.cash': 'Byabonetse ku kibanza · amafaranga (RWF)',
    'auditor.count.cash_hint':
        'Inyandiko zerekana {amount} · urugero {tolerance}',
    'auditor.count.cash_no_statement':
        'Nta mafaranga ari ku nyandiko za konti kuri iki gihe.',
    'auditor.count.period': "Igihe cy'inyandiko",
    'auditor.count.account': 'Konti / nomero ya MoMo',
    'auditor.count.inventory': "Ibimenyetso by'ububiko",
    'auditor.count.stock': 'Byabonetse ku kibanza · ububiko (ibice)',
    'auditor.count.units': 'ibice',
    'auditor.count.stock_hint': 'Ububiko bwatangajwe: ibice {units}',
    'auditor.count.stock_no_baseline': 'Nta bubiko bwatangajwe kuri iki gihe.',
    'auditor.count.stock_tolerance': 'Urugero rw’ububiko: ibice {units}',
    'auditor.count.operational': 'Uko ikigo gikora',
    'auditor.count.status.active': 'Kirakora',
    'auditor.count.status.suspended': 'Cyahagaze',
    'auditor.count.status.restricted': 'Gifite imbogamizi',
    'auditor.seal.title': 'Emeza ushyireho kashe',
    'auditor.seal.lead':
        "Ibyo wabonye byitirirwa uruhushya rwawe rwa ICPAR kandi bigezwa ku bashoramari nyuma y'uko ikigo gisinye.",
    'auditor.seal.note': "Inyandiko y'isuzuma",
    'auditor.seal.note_optional': 'Si ngombwa',
    'auditor.seal.note_required': 'Ni ngombwa',
    'auditor.seal.note_done': 'Ni ngombwa · byakozwe',
    'auditor.seal.note_placeholder': "Vuga ibyo wabonye n'impamvu",
    'auditor.seal.note_count': '{count} / {max}',
    'auditor.seal.preview': 'Reba ibyo wabonye',
    'auditor.seal.suggest': 'Saba impinduka',
    'auditor.seal.suggest_lead':
        'Inyandiko ya {business} isubizwa ikigo ngo ikosorwe yongere yoherezwe. Hitamo impamvu kandi uvuge ibyabaye.',
    'auditor.seal.suggest_placeholder':
        'urugero: urupapuro rwo kubara rwa Nzeri ntirusinye',
    'auditor.seal.suggest_submit': 'Saba impinduka',
    'auditor.seal.reject': 'Anga inyandiko',
    'auditor.seal.reject_lead':
        "Iyi verisiyo y'inyandiko ya {business} ntishobora kugenzurwa. Hitamo impamvu kandi uvuge ibyabaye — bireba inyandiko, si inguzanyo y'ikigo, kandi ibimenyetso n'amateka yayo birabikwa.",
    'auditor.seal.reject_placeholder':
        "Ibyo wagenzuye n'ibitashoboye kugenzurwa",
    'auditor.seal.reject_submit': 'Anga inyandiko',
    'auditor.seal.findings_eyebrow': 'Ibyabonetse · {version}',
    'auditor.seal.digest': 'Ikimenyetso gitegereje',
    'auditor.seal.digest_note':
        "Birangira iyo kashe yawe ya ICPAR ishyizweho. PDF isinywe ikorwa na Rozine igezwa ku bashoramari nyuma y'uko ikigo cyemeje.",
    'auditor.seal.apply': 'Ongera wiyemeze kugira ngo ushyireho kashe',
    'auditor.seal.submit': "Shyiraho kashe y'uruhushya rwa ICPAR",
    'auditor.sealed.title': 'Byashyizweho kashe biratangwa',
    'auditor.sealed.body':
        'Raporo yashyizweho kashe ntishobora guhindurwa. {party} isinya bitarenze {date}; nyuma igezwa ku bashoramari.',
    'auditor.sealed.timeline': 'Aho gutanga bigeze',
    'auditor.sealed.stage.sealed': 'Washyizeho kashe',
    'auditor.sealed.stage.cosigned': "Isinya ry'ikigo",
    'auditor.sealed.stage.published': 'Byagejejwe ku bashoramari',
    'auditor.sealed.cosign.pending': 'Birategerejwe',
    'auditor.sealed.cosign.signed': 'Byasinywe',
    'auditor.sealed.cosign.declined': 'Byajuririwe',
    'auditor.sealed.cosign.overdue': 'Byarenze igihe',
    'auditor.sealed.seal': 'Ikimenyetso cya kashe',
    'auditor.sealed.licence':
        "Byashyizweho kashe n'uruhushya {licence}. Iy'umwimerere igumaho; ikosora ryose riba umugereka uhuzwa nayo.",
    'auditor.sealed.amend': 'Tangira umugereka uhujwe',
    'auditor.portfolio.head_title': 'Ibyo ushinzwe',
    'auditor.portfolio.title': 'Ibyo ushinzwe',
    'auditor.portfolio.lead':
        "Winjiza mu buryo bubiri: kugenzura no gukurikirana dosiye usuzuma, n'ikomisiyo ku mishinga uzana.",
    'auditor.portfolio.cpa_card': 'Ikarita yawe ya CPA',
    'auditor.portfolio.cpa_card_sub': 'Igihamya ko uri uwacu',
    'auditor.portfolio.sourced.title': 'Imishinga wazanye',
    'auditor.portfolio.sourced.tag': 'Kuzana',
    'auditor.portfolio.sourced.empty':
        "Imishinga uzana iguhesha ikomisiyo ivuye mu kwishyura. Undi CPA ni we uyigenzura, bityo ikomisiyo yawe ntiterwa n'amanota wari gutanga.",
    'auditor.portfolio.earnings.title': 'Ibyo winjije mu igenzura · uku kwezi',
    'auditor.portfolio.earnings.managed': 'Imishinga ucunga',
    'auditor.portfolio.earnings.next_payout': 'Ubwishyu bukurikira',
    'auditor.portfolio.yield.title': 'Umugabane ku nyungu',
    'auditor.portfolio.yield.empty':
        "Ubwishyu bw'umugabane ku nyungu bugaragara hano bumaze kwishyurwa.",
    'auditor.portfolio.managed.title': 'Imishinga ucunga',
    'auditor.portfolio.managed.groups': "Amatsinda y'amasezerano ucunga",
    'auditor.portfolio.managed.group.active': 'Ishoramari rikora',
    'auditor.portfolio.managed.group.raising': 'Ikusanya',
    'auditor.portfolio.managed.filter': 'Uko ikusanya rihagaze',
    'auditor.portfolio.managed.chip.all': 'Byose',
    'auditor.portfolio.managed.chip.live': 'Birakomeje',
    'auditor.portfolio.managed.chip.failed': 'Byanze',
    'auditor.portfolio.managed.raising_empty':
        "Nta ikusanya ry'ubucuruzi wagenzuye riri muri iki gice.",
    'auditor.portfolio.managed.raised': 'Ayakusanyijwe',
    'auditor.portfolio.managed.target': 'Intego',
    'auditor.portfolio.managed.investors': 'Abashoramari',
    'auditor.portfolio.managed.count': '{count} zose',
    'auditor.portfolio.managed.sub': '{district} · yishyuwe {date}',
    'auditor.portfolio.managed.funded': 'Byatewe inkunga',
    'auditor.portfolio.managed.raise': 'Amafaranga yashakwaga',
    'auditor.portfolio.managed.fee': "Amafaranga y'urubuga",
    'auditor.portfolio.managed.share': '40% yawe',
    'auditor.portfolio.managed.empty':
        "Nta mishinga uracunga. Tsinda Igenzura ryihuse kugira ngo ube ushinzwe konti y'ubucuruzi.",
    'auditor.calendar.title': "Ingengabihe y'igenzura",
    'auditor.calendar.lead':
        "Igenzura ryose rya buri kwezi ugomba gukora, n'igihe.",
    'auditor.calendar.previous': 'Ukwezi gushize',
    'auditor.calendar.next': 'Ukwezi gutaha',
    'auditor.calendar.legend.soon': 'Mu minsi 5',
    'auditor.calendar.legend.scheduled': 'Biteganyijwe',
    'auditor.calendar.legend.passed': 'Byarenze',
    'auditor.calendar.day_due_one': 'Igenzura 1 rigomba gutangwa',
    'auditor.calendar.day_due_other': 'Igenzura {count} rigomba gutangwa',
    'auditor.calendar.close': 'Funga',
    'auditor.calendar.soon_one': 'Igenzura 1 rigomba gutangwa mu minsi 5',
    'auditor.calendar.soon_other':
        'Igenzura {count} rigomba gutangwa mu minsi 5',
    'auditor.calendar.soon_body_one':
        "Ritange mbere y'igihe ntarengwa: {business}, ku wa {date}.",
    'auditor.calendar.soon_body_other':
        "Tanga buri rimwe mbere y'igihe cyaryo. Irya mbere ni {business}, ku wa {date}.",
    'auditor.calendar.clear': 'Nta kigomba gutangwa mu minsi itanu iri imbere',
    'auditor.calendar.clear_next':
        'Nta kibazo. Igitangwa gikurikira ni {business} ku wa {date}.',
    'auditor.calendar.clear_none':
        'Nta kibazo. Igitangwa gikurikira ntikiragenwa.',
    'auditor.calendar.due_in': 'Bigomba gutangwa muri {month}',
    'auditor.calendar.count_one': 'Umushinga 1',
    'auditor.calendar.count_other': 'Imishinga {count}',
    'auditor.calendar.empty': 'Nta kigomba gutangwa uku kwezi.',
    'auditor.calendar.partial': 'Imwe mu mirimo wahawe ntigaragara hano',
    'auditor.calendar.partial_body':
        'Iyi kalendari yerekana imirimo uheruka guhabwa gusa. Fungura Imirimo urebe igenzura ryose ugomba gutanga.',
    'auditor.calendar.partial_empty':
        'Nta kigomba gutangwa uku kwezi mu mirimo uheruka guhabwa.',
    'auditor.calendar.reports_partial':
        'Hano hagaragara raporo uheruka gutanga gusa, bityo ukwezi gushobora kubura zimwe.',
    'auditor.calendar.open_jobs': 'Fungura Imirimo',
    'auditor.calendar.share': 'Umugabane wawe',
    'auditor.calendar.on_time_by': 'Bitarenze',
    'auditor.calendar.due_today': 'Uyu munsi',
    'auditor.calendar.in_days_one': 'Mu munsi 1',
    'auditor.calendar.in_days_other': 'Mu minsi {count}',
    'auditor.calendar.overdue_one': 'Byarenzeho umunsi 1',
    'auditor.calendar.overdue_other': 'Byarenzeho iminsi {count}',
    'auditor.calendar.monthly': 'Igenzura rya buri kwezi',
    'auditor.calendar.filed_on': '{title} · yatanzwe ku wa {date}',
    'auditor.conflict.title': 'Tangaza inyungu',
    'auditor.conflict.none_assigned': 'Nta dosiye ushinzwe kugenzura ubu.',
    'auditor.conflict.options_one': 'Tangaza kuri dosiye 1 ushinzwe',
    'auditor.conflict.options_other':
        'Tangaza kuri imwe muri dosiye {count} ushinzwe',
    'auditor.conflict.on_record': 'Byanditswe',
    'auditor.conflict.business_on_record': 'Ubucuruzi bwanditswe',
    'auditor.conflict.assignment_ref': 'Nomero {reference}',
    'auditor.conflicts.head_title': 'Inyungu wagaragaje',
    'auditor.conflicts.title': 'Inyungu wagaragaje',
    'auditor.conflicts.lead':
        'Inyungu zose wagaragaje, uko zanditswe. Buri imwe yahagaritse akazi kawe kuri uwo murimo, kandi hasigaye gusa inyemezo yawe.',
    'auditor.conflicts.empty': 'Nta nyungu wagaragaje.',
    'auditor.conflicts.page_empty':
        'Nta kigaragara kuri uru rupapuro. Izo wagaragaje mbere zishobora gukurikiraho.',
    'auditor.reports.status.awaiting_cosign': 'Itegereje gusinywa',
    'auditor.reports.status.published': 'Yatangajwe',
    'auditor.reports.status.late': 'Yatinze',
    'auditor.reports.status.rejected': 'Yanzwe',
    'auditor.reports.flash': 'Igenzura ryihuse',
    'auditor.reports.monthly': 'Raporo ya {month}',
    'auditor.reports.late_by': '{title} · yatanzwe itinze iminsi {days}',
    'auditor.reports.district': 'Akarere',
    'auditor.reports.amend': 'Tangira umugereka uhujwe →',
    'auditor.profile.head_title': 'Umwirondoro',
    'auditor.profile.title': 'Umwirondoro',
    'auditor.profile.menu': "Ibice by'umwirondoro",
    'auditor.profile.section.accreditation': "Uruhushya rw'umwuga",
    'auditor.profile.section.availability': "Igihe uboneka n'aho ukorera",
    'auditor.profile.section.earnings': 'Ibyo winjiza',
    'auditor.profile.section.contact': "Umwirondoro n'aho ubarizwa",
    'auditor.profile.section.payout': 'Konti ya banki yo kwishyurirwaho',
    'auditor.profile.section.telemetry': "Igenzura ry'umugenzuzi",
    'auditor.profile.section.security': "Umutekano n'ibikoresho",
    'auditor.profile.section.learn': "Ishuri ry'abagenzuzi",
    'auditor.profile.section.legal': "Amategeko n'amabwiriza",
    'auditor.profile.contact.title': "Umwirondoro n'aho ubarizwa",
    'auditor.profile.contact.phone': 'Telefoni',
    'auditor.profile.contact.email': 'Imeyili',
    'auditor.profile.contact.address': 'Aderesi',
    'auditor.profile.contact.district': 'Akarere ukoreramo',
    'auditor.profile.contact.not_provided': 'Ntibyatanzwe',
    'auditor.profile.telemetry.title': "Igenzura ry'umugenzuzi",
    'auditor.profile.telemetry.clock_expiries': 'Igihe cyarangiye',
    'auditor.profile.telemetry.clock_expiries_sub':
        'Igenzura ryemewe ryageze kuri 0:00',
    'auditor.profile.telemetry.variance': "Ubunyangamugayo bw'itandukaniro",
    'auditor.profile.telemetry.variance_sub':
        "Itandukaniro risanzwe n'isesengura ry'imbere",
    'auditor.profile.telemetry.on_time': 'Kurangiriza ku gihe',
    'auditor.profile.telemetry.jobs_done': {
        one: 'Akazi {count} karangiye',
        other: 'Imirimo {count} yarangiye',
    },
    'auditor.profile.telemetry.strikes': "Amakosa y'ubuziranenge",
    'auditor.profile.telemetry.strikes_sub': 'Impaka zemejwe zikurega',
    'auditor.profile.telemetry.defended': 'Impaka watsinze',
    'auditor.profile.telemetry.defended_sub': 'Isuzuma ryemeje akazi kawe',
    'auditor.profile.telemetry.first_note': 'Kunanirwa kwishyura noti ya mbere',
    'auditor.profile.telemetry.first_note_sub':
        'Amahirwe yananiwe kwishyura noti ya 1',
    'auditor.profile.telemetry.note':
        "Rozine ikurikirana imikorere y'abafatanyabikorwa kugira ngo irinde imari y'abashoramari. Igipimo kigaragaza akarongo kugeza gipimwe.",
    'auditor.profile.security.title': "Umutekano n'ibikoresho",
    'auditor.profile.security.two_factor': 'Kwinjira mu byiciro bibiri',
    'auditor.profile.security.two_factor_on':
        'Birakora · kode ya porogaramu yemeza iyo winjira',
    'auditor.profile.security.two_factor_off':
        'Ntibikora · ongeraho kode ya porogaramu yemeza iyo winjira',
    'auditor.profile.security.password': 'Hindura ijambobanga',
    'auditor.profile.legal.title': "Amategeko n'amabwiriza",
    'auditor.profile.legal.engagement': "Amasezerano y'akazi",
    'auditor.profile.legal.current':
        "Yemejwe · amasezerano rusange n'uburyo bwumvikanyweho",
    'auditor.profile.legal.required': 'Bisaba ko ubyemeza',
    'auditor.profile.legal.unavailable': 'Ntibishoboka gusomwa ubu',
    'auditor.profile.earnings.title': 'Ibyo winjije',
    'auditor.profile.earnings.lead':
        "Ibyo winjije byose uku kwezi, umurongo ku murongo, n'uko buri mubare wabazwe.",
    'auditor.profile.earnings.this_month': 'Ibyo winjije muri {month}',
    'auditor.profile.earnings.total': 'Ibyo winjije byose hamwe',
    'auditor.profile.earnings.sources': 'Aho byaturutse',
    'auditor.profile.earnings.platform_fee':
        "Umugabane w'amafaranga ya platform",
    'auditor.profile.earnings.platform_fee_rule':
        "40% by'amafaranga ya platform ya 3% kuri buri nkunga wagenzuye, ashyirwa kuri konti iyo yishyuwe.",
    'auditor.profile.earnings.earnings_fee': "Umugabane w'amafaranga ku nyungu",
    'auditor.profile.earnings.earnings_fee_rule':
        "25% by'amafaranga ku nyungu kuri buri bwishyu bwa buri kwezi bw'abashoramari. Bitangirana n'ubwishyu bwa mbere.",
    'auditor.profile.earnings.lines': "Imirongo yose y'uku kwezi",
    'auditor.profile.earnings.line_count': {
        one: 'Umurongo {count}',
        other: 'Imirongo {count}',
    },
    'auditor.profile.earnings.empty':
        'Nta cyo urinjiza uku kwezi. Umurongo ugaragara ako kanya inkunga wagenzuye yishyuwe.',
    'auditor.profile.earnings.basis':
        'Inkunga {raise} · amafaranga ya platform ya 3% {fee} · 40% byawe {amount}',
    'auditor.profile.pending.payout_title':
        'Nta konti yo kwishyurirwaho iracyahari',
    'auditor.profile.pending.payout_body':
        'Konti ya banki wishyurirwaho izagaragara hano.',
    'auditor.profile.pending.legal_title': 'Biraza vuba muri porogaramu',
    'auditor.profile.pending.legal_body':
        "Amasezerano yawe y'akazi azasomerwa hano.",
    'auditor.profile.on_time': 'Ku gihe',
    'auditor.profile.jobs': 'Imirimo',
    'auditor.profile.since': 'Kuva',
    'auditor.accreditation.title': "Uruhushya rw'umwuga",
    'auditor.accreditation.licence_line':
        'Uruhushya {licence} · rurangira {date}',
    'auditor.accreditation.badge.active': '✓ Rurakora',
    'auditor.accreditation.badge.expired': 'Rwarangiye',
    'auditor.accreditation.badge.suspended': 'Rwahagaritswe',
    'auditor.accreditation.badge.pending': 'Kongererwa birategerejwe',
    'auditor.accreditation.standing': 'Iminsi isigaye',
    'auditor.accreditation.days_left': 'Iminsi {count} mbere yo kongeresha',
    'auditor.accreditation.expired_ago': 'Rwarangiye hashize iminsi {count}',
    'auditor.accreditation.pending_title': 'Kongererwa birasuzumwa',
    'auditor.accreditation.pending_line':
        '{id} · uruhushya {licence} kugeza {date}, rwatanzwe {submitted}. Abakozi ba Rozine barugenzura mu gitabo cya ICPAR.',
    'auditor.accreditation.withdraw': 'Kuraho',
    'auditor.accreditation.rejected_title': 'Kongererwa byanzwe',
    'auditor.accreditation.rejected_line':
        '{id} yanzwe — {reason}. Kosora amakuru wongere wohereze.',
    'auditor.accreditation.renew': 'Ongeresha uruhushya',
    'auditor.accreditation.licence_label': "Nomero y'uruhushya",
    'auditor.accreditation.licence_placeholder': 'ICPAR/CPA/0000',
    'auditor.accreditation.expiry_label': 'Itariki nshya ruzarangiriraho',
    'auditor.accreditation.certificate_label': "Icyemezo cy'uruhushya",
    'auditor.accreditation.certificate_drop': 'Shyiramo icyemezo cya ICPAR',
    'auditor.accreditation.submit': 'Ohereza ngo bisuzumwe',
    'auditor.availability.title': "Igihe uboneka n'aho ukorera",
    'auditor.availability.accepting_sub':
        'Uri mu bashobora guhabwa imirimo kandi ushobora guhabwa amagenzura yihuse.',
    'auditor.availability.paused_sub':
        'Nta murimo mushya uhabwa. Imirimo wemeye ikomeza igihe cyayo.',
    'auditor.availability.max_title': 'Imirimo icyarimwe',
    'auditor.availability.max_sub': "Nyuma y'aha nta murimo uzahabwa.",
    'auditor.availability.radius_title': 'Aho ukorera',
    'auditor.availability.radius_sub':
        'Bipimwa uhereye ku biro byawe byanditswe kugera ku kigo.',

    'business.rating.page_title': "Ubuzima bw'imari",
    'business.rating.page_subtitle':
        "Amanota yawe, ubushobozi n'imibare yemejwe.",
    'business.rating.title': 'Amanota ya Rozine',
    'business.rating.out_of': 'kuri 5',
    'business.rating.explainer':
        "Buri raporo y'ukwezi yagenzuwe irabivugurura. Amanota menshi afungura ubushobozi bwisumbuye n'inyungu nke ku gukusanya gukurikira.",
    'business.rating.drift': 'Yahawe {audited} mu igenzura — ubu {now}',
    'business.rating.refused':
        'Uburyo bwo gutanga amanota ntiburabasha guha amanota iki kigo',
    'business.rating.factors': 'Uko amanota ateye',
    'business.rating.factor.financial_health': "Ubuzima bw'imari",
    'business.rating.factor.repayment_history': 'Amateka yo kwishyura',
    'business.rating.factor.statement_consistency':
        'Guhuza kwa raporo za banki',
    'business.rating.factor.growth_outlook': "Iterambere n'icyerekezo",
    'business.rating.sizing.title': 'Uko ubushobozi bwawe bubarwa',
    'business.rating.sizing.cash': 'Amafaranga ku kwezi',
    'business.rating.sizing.cash_note':
        "inyungu nyayo {margin}% + {depreciation}% by'iyangirika byongeweho",
    'business.rating.sizing.multiplier': '× Ikigwizo cyawe',
    'business.rating.sizing.tier.none': 'Nta bicuruzwa byemejwe biraboneka',
    'business.rating.sizing.tier.cover1x':
        "CPA wawe yemeje ibicuruzwa bingana nibura n'amafaranga asabwa inshuro 1",
    'business.rating.sizing.tier.cover2x':
        "CPA wawe yemeje ibicuruzwa bingana nibura n'amafaranga asabwa inshuro 2",
    'business.rating.sizing.carry':
        'Ubwishyu bwa buri kwezi ushobora kwishyura',
    'business.rating.sizing.explainer':
        "Ubushobozi bwawe ni amafaranga ikigo cyawe kinjiza buri kwezi, agwizwa n'ibyo ibicuruzwa byemejwe byemerera, hanyuma bigarukira ku gipimo gito muri bitanu biri hejuru. Nta muntu muri Rozine ushyiraho iyi mibare n'intoki. Ubusabe burenze ubushobozi bwangwa ako kanya.",
    'business.rating.stock.title': 'Ibicuruzwa byanditswe',
    'business.rating.stock.verified':
        '{value} byabaruwe na CPA wawe aho ukorera',
    'business.rating.stock.indicative':
        '{value} ni ibisanzwe mu rwego rwawe — ariko ibarura rya CPA ni ryo ryonyine ritanga ikigwizo kinini',
    'business.rating.stock.none': 'Nta bicuruzwa byanditswe',
    'business.rating.stock.why':
        'Inguzanyo igura ibicuruzwa yishyurwa hagurishijwe ibyo bicuruzwa, bityo ibicuruzwa byemejwe byongera ubushobozi bwawe.',
    'business.rating.limits': 'Ibipimo bitanu · igito ni cyo gikoreshwa',
    'business.rating.limit.capacity': "Ubushobozi bw'amafaranga",
    'business.rating.limit.capacity_detail':
        'EBITDA {ebitda}/ukwezi × M {multiplier}',
    'business.rating.limit.capacity_lift':
        'Ongera EBITDA, cyangwa usabe CPA wawe kwemeza ibicuruzwa',
    'business.rating.limit.revenue_share': "Igice cy'ayinjiye",
    'business.rating.limit.revenue_share_detail':
        '{percent}% bya {revenue} byinjiye mu mwaka byagenzuwe',
    'business.rating.limit.revenue_share_lift':
        'Ongera ayinjiye mu mwaka yagenzuwe',
    'business.rating.limit.book_share': "Igice cy'inguzanyo zose",
    'business.rating.limit.book_share_detail':
        "{percent}% bya {book} by'inguzanyo zitarishyurwa",
    'business.rating.limit.book_share_lift':
        "Kizamuka ubwacyo uko inguzanyo z'urubuga ziyongera",
    'business.rating.limit.phase_cap': "Igipimo cy'icyiciro {phase}",
    'business.rating.limit.phase_cap_detail':
        "Igipimo cy'urubuga igihe inguzanyo ziri munsi ya {book}",
    'business.rating.limit.phase_cap_lift': 'Kizamuka mu cyiciro gikurikira',
    'business.rating.limit.policy_max': 'Igipimo ntarengwa',
    'business.rating.limit.policy_max_detail':
        'Igipimo ntarengwa ku gukusanya kumwe',
    'business.rating.limit.policy_max_lift': 'Nta na kimwe — iki ni ntarengwa',
    'business.rating.limit.yours': 'Iki ni cyo gipimo cyawe',
    'business.rating.approved': 'Ubushobozi bwemejwe',
    'business.rating.bound': 'Igikubuza: {limit}',
    'business.rating.lift': 'Kugira ngo kizamuke — {how}',
    'business.rating.headroom': 'Umwanya usigaye',
    'business.rating.headroom_note': 'Ushobora gukusanya aya yiyongera ubu.',
    'business.rating.raise': 'Kusanya',
    'business.rating.tiers.title': 'Icyo ikigwizo kinini cyakwemerera',
    'business.rating.tiers.applied': 'Cyakoreshejwe',
    'business.rating.tiers.not_yet': 'Ntibirashoboka',
    'business.rating.tiers.no_cover': 'Nta bicuruzwa bikenewe kuri uru rwego',
    'business.rating.tiers.needs':
        'Hakenewe {need}× · ibicuruzwa byawe bitanga {yours}×',
    'business.rating.financials': 'Imibare yemejwe',
    'business.rating.sources': 'Banki · MoMo',
    'business.rating.financial.revenue': 'Ayinjira buri kwezi',
    'business.rating.financial.ebitda': 'Amafaranga asigaye / ukwezi',
    'business.rating.financial.margin': 'Inyungu nyayo',
    'business.rating.financial.outstanding': 'Ibitarishyurwa',

    'business.repayments.title': 'Kwishyura',
    'business.repayments.progress': 'Aho kwishyura bigeze',
    'business.repayments.payments': 'Ubwishyu {made} / {total}',
    'business.repayments.remaining': 'Ibisigaye',
    'business.repayments.total': 'Igiteranyo',
    'business.repayments.schedule': 'Gahunda yo kwishyura',
    'business.repayments.late.title': 'Iyo ubwishyu butinze',
    'business.repayments.issued.lede': '{title} · uko byatanzwe',
    'business.repayments.issued.principal': 'Igishoro',
    'business.repayments.issued.return': 'Inyungu',
    'business.repayments.issued.total': 'Igiteranyo cyo kwishyura',
    'business.repayments.issued.split':
        'Igishoro {principal} · inyungu {return}',
    'business.repayments.issued.note':
        "Buri gice kigizwe n'igishoro n'inyungu, kigomba kwishyurwa ku itariki igaragara. Ubwishyu, amande y'ubukererwe n'inyemezabwishyu bizagaragara hano kwishyura nibimara gufungurwa.",
    'business.repayments.none.title': 'Nta kwishyura birahari',
    'business.repayments.none.body':
        "Kwishyura bitangira iyo amafaranga wakusanyije amaze kuzura no kukugeraho. Ingengabihe, ibigomba kwishyurwa n'ubwishyu bwose bizagaragara hano.",
    'business.servicing.repay.state.current': 'Biri ku gihe',
    'business.servicing.repay.state.due_today': 'Bigomba kwishyurwa uyu munsi',
    'business.servicing.repay.state.overdue': 'Byarengeje igihe',
    'business.servicing.repay.state.repaid': 'Byishyuwe',
    'business.servicing.repay.state.defaulted': 'Byananiranye kwishyurwa',
    'business.servicing.repay.instalments_left': {
        one: 'Hasigaye igice {count}',
        other: 'Hasigaye ibice {count}',
    },
    'business.servicing.repay.repaid': 'Iyi nyandiko yishyuwe yose. Murakoze.',
    'business.servicing.repay.due_title': 'Ibyo kwishyura ubu',
    'business.servicing.repay.due_today': 'Bigomba kwishyurwa uyu munsi',
    'business.servicing.repay.dpd': {
        one: "Umunsi {count} w'ubukererwe",
        other: "Iminsi {count} y'ubukererwe",
    },
    'business.servicing.repay.next_title': 'Igice gikurikira · #{index}',
    'business.servicing.repay.due_on': 'Bigomba kwishyurwa ku wa {date}',
    'business.servicing.repay.next_late_fee':
        "Nibiba bitarishyurwa ku wa {date}, hazongerwaho amafaranga y'ubukererwe agera kuri {amount}. Ni igereranya.",
    'business.servicing.repay.autocollect':
        'Tuzabikura mu gikapu cyawe cya Rozine ku wa {date}.',
    'business.servicing.repay.autocollect_off':
        'Gukura amafaranga mu gikapu mu buryo bwikora birahagaritswe.',
    'business.servicing.repay.restriction.ARREARS':
        'Hari ibirarane kuva ku wa {date}. Inyandiko zawe ntizicuruzwa kugeza byishyuwe.',
    'business.servicing.repay.restriction.RESTRICTION_ACTIVE':
        'Byabujijwe kuva ku wa {date}. Uracyashobora kwishyura.',
    'business.servicing.repay.part.principal': 'Igishoro',
    'business.servicing.repay.part.return': 'Inyungu',
    'business.servicing.repay.part.late_fees': "Amafaranga y'ubukererwe",
    'business.servicing.repay.part.total': 'Igiteranyo',
    'business.servicing.repay.pay_title': 'Ishyura uvuye mu gikapu',
    'business.servicing.repay.option.due_now': 'Ibigomba kwishyurwa ubu',
    'business.servicing.repay.option.next_instalment':
        "Igice gikurikira mbere y'igihe",
    'business.servicing.repay.option_note.due_now':
        "Byishyura igice {list}, hamwe n'amafaranga y'ubukererwe niba ahari.",
    'business.servicing.repay.option_note.next_instalment':
        "Byishyura igice {list} mbere y'igihe, ku mafaranga yateganyijwe. Nta gabanywa ribaho ku kwishyura kare.",
    'business.servicing.repay.wallet_has':
        'Igikapu cyawe gifite {amount} ushobora gukoresha.',
    'business.servicing.repay.pay_cta': 'Ishyura {amount}',
    'business.servicing.repay.short':
        'Ibiri mu gikapu cyawe ntibihagije kuri iki gikorwa.',
    'business.servicing.repay.top_up': 'Shyira amafaranga mu gikapu',
    'business.servicing.repay.receipt_title': 'Ubwishyu bwanditswe',
    'business.servicing.repay.allocating': 'Biri kugabanywa abashoramari bawe',
    'business.servicing.repay.allocated': {
        one: 'Byagabanyijwe umushoramari {count}',
        other: 'Byagabanyijwe abashoramari {count}',
    },
    'business.servicing.repay.unapplied':
        '{amount} ntiyari ikenewe kandi iguma mu gikapu cyawe.',
    'business.servicing.repay.receipt_done': 'Byarangiye',
    'business.servicing.repay.recent': 'Ibyishyuwe vuba',
    'business.servicing.repay.row.due':
        '#{index} · bigomba kwishyurwa ku wa {date}',
    'business.servicing.repay.row.paid': '#{index} · byishyuwe ku wa {date}',
    'business.servicing.repay.status.paid': 'Byishyuwe',
    'business.servicing.repay.status.processing': 'Biri gutunganywa',
    'business.servicing.repay.status.due': 'Bigomba kwishyurwa',
    'business.servicing.repay.status.partially_paid': 'Byishyuwe igice',
    'business.servicing.repay.status.overdue': 'Byarengeje igihe',
    'business.servicing.repay.status.upcoming': 'Biraza',
    'business.servicing.repay.late_lines':
        "Amafaranga y'ubukererwe kuri iki gice",
    'business.servicing.repay.late_line': '{step} · {date} · {status}',
    'business.servicing.repay.step.due_date': 'Itariki yo kwishyura yarenze',
    'business.servicing.repay.step.day_7': 'Umunsi wa 7 bitarishyurwa',
    'business.servicing.repay.step.day_30': 'Umunsi wa 30 bitarishyurwa',
    'business.servicing.repay.fee_status.projected': 'giteganyijwe',
    'business.servicing.repay.fee_status.assessed': 'byongewemo',
    'business.servicing.repay.fee_status.partially_collected':
        'byishyuwe igice',
    'business.servicing.repay.fee_status.collected': 'byishyuwe',
    'business.servicing.repay.fee_status.waived': 'byakuweho',
    'business.servicing.repay.step_rate': '+{percent}%',
    'business.servicing.repay.ladder_unavailable':
        "Amabwiriza y'amafaranga y'ubukererwe ntaboneka ubu, bityo nta mafaranga y'ubukererwe yerekanwa.",
    'business.servicing.repay.ladder_version':
        "Amabwiriza y'amafaranga y'ubukererwe {version}.",
    'business.audit_prep.title': 'Itegure igenzura',
    'business.audit_prep.window_open': "Igihe cy'igenzura cyatangiye",
    'business.audit_prep.next': 'Igenzura rikurikira',
    'business.audit_prep.first': 'Igenzura ryawe rya mbere',
    'business.audit_prep.month': 'Igenzura rya {month}',
    'business.audit_prep.day_left': 'Umunsi usigaye',
    'business.audit_prep.days_left': 'Iminsi isigaye',
    'business.audit_prep.intro':
        "Wakiriye ubutumwa: Tegura raporo zose za banki, amateka ya Mobile Money n'ibitabo by'inyemezabwishyu byo ku mpapuro, witegura uruzinduko rwa CPA wawe.",
    'business.audit_prep.reassigned':
        'Dosiye yawe yavuye kwa {from} ijya kwa {to}, kandi amateka yawe yajyanye nayo.',
    'business.audit_prep.ready': 'Tegura ibi',
    'business.audit_prep.item.statements.title':
        "Raporo za banki n'amateka ya Mobile Money",
    'business.audit_prep.item.statements.body':
        'Ukwezi kose, kugeza ku munsi wa nyuma, byiteguye ko CPA wawe abisuzumira aho ukorera.',
    'business.audit_prep.item.stock.title':
        "Ibicuruzwa byabaruwe n'ibitabo bigezweho",
    'business.audit_prep.item.stock.body':
        "Umugenzuzi abara ibicuruzwa imbonankubone. Igitabo kitavuguruwe kigaragara nk'ikinyuranyo kandi kigabanya ubushobozi bwo kuguza.",
    'business.audit_prep.item.access.title':
        'Kugera ku bubiko bwose, iduka na kesi',
    'business.audit_prep.item.access.body':
        "Ikintu cyose gifunze cyangwa kitagerwaho uwo munsi kibarwa nk'icyabuze.",
    'business.audit_prep.item.papers.title':
        "Inyemezabwishyu za kesi n'ibikorwa bihuye",
    'business.audit_prep.item.papers.body':
        "Reba neza ko inyemezabwishyu zose za kesi zo ku mpapuro n'ibikorwa byose by'ikoranabuhanga bihuye, mbere y'isuzuma CPA azakorera aho ukorera.",
    'business.audit_prep.item.person.title': 'Umuntu ufite ububasha uhari',
    'business.audit_prep.item.person.body':
        'Hakenewe umuntu ushobora gufungura inzugi no gusobanura imibare — si abakozi bari ku kazi gusa.',
    'business.audit_prep.how': 'Uko igenzura rikorwa',
    'business.audit_prep.flow.closes.title': 'Ukwezi kurarangira',
    'business.audit_prep.flow.closes.body':
        "CPA wawe afungura dosiye y'igenzura y'icyo gihe. Nta kintu usabwa kugira ngo bitangire.",
    'business.audit_prep.flow.visit.title': 'Uruzinduko aho ukorera',
    'business.audit_prep.flow.visit.body':
        "CPA wagenewe azasura aho ukorera asuzume inyandiko, ahuze amafaranga yinjira n'asohoka, kandi akore raporo y'igenzura ya buri kwezi.",
    'business.audit_prep.flow.sealed.title': 'Birafunzwe',
    'business.audit_prep.flow.sealed.body':
        "Ibyabonetse n'ibinyuranyo bifungwa hakoreshejwe uruhushya rwe rwa ICPAR.",
    'business.audit_prep.flow.cosign.title': 'Ushyiraho umukono',
    'business.audit_prep.flow.cosign.body':
        'Wongeraho incamake ugashyiraho umukono bitarenze {date}, cyangwa ukajurira ufite ibimenyetso.',
    'business.audit_prep.closing':
        "Ntujya utanga raporo y'ukwezi ubwawe. CPA wawe arakusura, agasuzuma inyandiko zawe aho ukorera kandi agafunga raporo — uruhare rwawe ni ukwitegura, hanyuma ugashyiraho umukono cyangwa ukajurira ibyo yabonye.",

    'investor.nav.deals': 'Amahirwe',
    'investor.nav.portfolio': 'Ishoramari ryanjye',
    'investor.nav.profile': 'Umwirondoro',
    'investor.market.order_detail.title': "Ibisobanuro by'ibwiriza",
    'investor.market.order_detail.empty_title': 'Nta bwiriza ryatoranyijwe',
    'investor.market.order_detail.empty_body':
        'Hitamo ibwiriza ibumoso urebe impamvu ryakozwe cyangwa ritakozwe.',
    'investor.nav.market': 'Isoko',
    'investor.nav.cart': 'Igitebo',
    'investor.market.title': 'Isoko',
    'investor.market.subtitle':
        "Gura cyangwa ugurishe Rozine Notes zigikora mbere y'uko zigera ku gihe cyazo.",
    'investor.market.view.browse': 'Reba',
    'investor.market.view.orders': 'Amabwiriza',
    'investor.market.view.saved': 'Izabitswe',
    'investor.market.filter.performance': 'Imikorere',
    'investor.market.filter.status': 'Uko bihagaze',
    'investor.market.filter.industry': 'Urwego',
    'investor.market.performance.all': 'Byose',
    'investor.market.performance.most_active': 'Izikora cyane',
    'investor.market.performance.highest_yield': 'Inyungu nini',
    'investor.market.performance.lowest_risk': 'Ibyago bike',
    'investor.market.performance.most_liquid': 'Izigurishwa vuba',
    'investor.market.status.all': 'Byose',
    'investor.market.status.active': 'Izikora',
    'investor.market.status.sold': 'Izagurishijwe',
    'investor.market.status.complete': 'Izarangiye',
    'investor.market.industry.all': 'Byose',
    'investor.market.industry.agriculture': 'Ubuhinzi',
    'investor.market.industry.manufacturing': 'Inganda',
    'investor.market.industry.energy': 'Ingufu',
    'investor.market.industry.technology': 'Ikoranabuhanga',
    'investor.market.industry.logistics': 'Ubwikorezi',
    'investor.market.industry.finance': 'Imari',
    'investor.market.orders.label': 'Amabwiriza',
    'investor.market.orders.buy': 'Amabwiriza yo kugura',
    'investor.market.orders.sell': 'Amabwiriza yo kugurisha',
    'investor.market.orders.completed': 'Ayarangiye',
    'investor.market.orders.cancelled': 'Ayahagaritswe',
    'investor.market.orders.empty': 'Nta mabwiriza ari muri iki gice.',
    'investor.market.browse.empty_title': "Nta noti ihuye n'ibi byatoranyijwe",
    'investor.market.browse.empty_body':
        'Gerageza guhindura ibyatoranyijwe hejuru.',
    'investor.market.saved.empty_title': 'Nta noti irabikwa',
    'investor.market.saved.empty_body':
        'Kurura noti hejuru kugira ngo uyibike hano.',
    'investor.cart.title': 'Igitebo',
    'investor.cart.empty_title': 'Igitebo cyawe kirimo ubusa',
    'investor.cart.empty_body':
        'Ongeramo noti uvuye mu Mahirwe, uhitemo amafaranga ushaka kuri buri imwe, maze uzishyure zose icyarimwe.',
    'investor.cart.browse_deals': 'Reba amahirwe',
    'investor.cart.add': 'Shyira mu gitebo',
    'investor.cart.in_cart_remove': 'Iri mu gitebo · Kuramo',
    'investor.cart.update_failed':
        'Ntitwashoboye kuvugurura igitebo cyawe. Ongera ufungure maze wongere ugerageze.',
    'investor.cart.count': { one: 'Noti {count}', other: 'Noti {count}' },
    'investor.cart.total': 'IGITERANYO CY’IGITEBO',
    'investor.cart.remove': 'Kura {name} mu gitebo',
    'investor.cart.filled_remove': 'Yujujwe · Kuramo',
    'investor.cart.filled_note': {
        one: 'Noti {count} yujujwe kandi ntizishyurwa.',
        other: 'Noti {count} zujujwe kandi ntizishyurwa.',
    },
    'investor.cart.target_of': 'kuri {amount}',
    'investor.cart.units': { one: 'noti', other: 'noti' },
    'investor.cart.quantity': 'Umubare wa noti muri {name}',
    'investor.cart.checkout': 'Ishyura',
    'investor.cart.checkout_note':
        'Kuri ubu buri noti yishyurwa ukwayo: fungura aho kuyishyura kugira ngo uyifate kandi uyishyure.',
    'investor.cart.before_charge':
        'Amafaranga usubizwa ni mbere yo gukuraho igiciro ku nyungu. Buri kwishyura kwerekana amafaranga nyayo.',
    'investor.common.back': 'Subira inyuma',
    'investor.money.rwf': 'RWF',
    'investor.rating.strong': 'Ikomeye',
    'investor.rating.stable': 'Ihamye',
    'investor.rating.weak': 'Intege nke',
    'investor.rating.distressed': 'Mu bibazo',
    'investor.deals.head_title': 'Amahirwe',
    'investor.deals.wallet_balance': 'Amafaranga ari ku ikofi',
    'investor.deals.deposit': 'Shyiraho amafaranga',
    'investor.deals.withdraw': 'Bikuza',
    'investor.deals.notifications': 'Imenyesha',
    'investor.deals.notifications_unread': 'Imenyesha, {count} ritarasomwa',
    'investor.deals.sort_label': 'Tondeka amahirwe',
    'investor.deals.sort.all': 'Byose',
    'investor.deals.sort.top_interest': 'Inyungu nyinshi',
    'investor.deals.sort.top_rated': 'Izifite amanota menshi',
    'investor.deals.sort.top_picks': 'Izatoranyijwe',
    'investor.deals.sort.in_cart': 'Biri mu gitebo',
    'investor.deals.industry_label': 'Shungura ukurikije urwego',
    'investor.deals.industry_all': 'Byose',
    'investor.deals.audited': 'YAGENZUWE',
    'investor.deals.just_listed': 'INSHYA',
    'investor.deals.photo_previous': 'Ifoto ibanza ya {name}',
    'investor.deals.photo_next': 'Ifoto ikurikira ya {name}',
    'investor.deals.open_deal': 'Fungura {name}',
    'investor.deals.raised': 'BYAKUSANYIJWE',
    'investor.deals.avg_revenue_short': "IMPUZANDENGO Y'UKWEZI",
    'investor.deals.avg_revenue': "IMPUZANDENGO Y'INJIZA Y'UKWEZI",
    'investor.deals.funded_label': 'Aho ishoramari rya {name} rigeze',
    'investor.deals.investors_one': 'umushoramari',
    'investor.deals.investors': 'abashoramari',
    'investor.deals.left_to_fill': 'ASIGAYE',
    'investor.deals.notes': 'IMPAPURO',
    'investor.deals.taken': 'zafashwe',
    'investor.deals.fully_funded': 'Byuzuye',
    'investor.deals.days_left': 'Iminsi {count}',
    'investor.deals.closing': 'Birafunga',
    'investor.deals.deal_previous': 'Amahirwe abanza',
    'investor.deals.deal_next': 'Amahirwe akurikira',
    'investor.deals.empty_title': 'Nta mahirwe afunguye ubu',
    'investor.deals.empty_body':
        'Ishoramari rishya rigaragara hano rimaze kugenzurwa no gushyirwa ku rutonde.',
    'investor.deals.all_closed':
        'Ishoramari ryose ryafashwe ryose cyangwa ryafunzwe muri iki gihe. Ishoramari rishya rigaragara hano iyo ritangiye.',
    'investor.deals.invest_bar': 'Shora muri {name}',
    'investor.deals.notes_quantity': "Umubare w'impapuro",
    'investor.deals.notes_suffix': 'IMPAPURO',
    'investor.deals.of_left': 'kuri {count} zisigaye',
    'investor.deals.term': 'IGIHE',
    'investor.deals.months_short': 'Amezi {count}',
    'investor.deals.bar_invest': 'SHORA',
    'investor.deals.bar_interest': 'INYUNGU',
    'investor.deals.bar_get_back': 'UZABONA',
    'investor.deals.invest': 'Shora',
    'investor.deals.verify_to_invest': 'Emeza umwirondoro ushore',
    'investor.deals.gate.verification_required':
        'Emeza umwirondoro wawe kugira ngo ushore. Kureba birafunguye kuri bose.',
    'investor.deals.gate.verification_pending':
        'Turimo kugenzura umwirondoro wawe. Uzashobora gushora umaze kwemezwa.',
    'investor.deals.gate.restricted':
        "Gushora byahagaritswe kuri iyi konti. Vugana n'ubufasha.",
    'investor.deal.status.open': 'BIRAKOMEJE',
    'investor.deal.status.sold_out': 'BYASHIZE',
    'investor.deal.status.frozen': 'BYAHAGARITSWE',
    'investor.deal.status.withdrawn': 'BYAKUWEHO',
    'investor.deal.notice.sold_out.title': 'Iri shoramari ryuzuye',
    'investor.deal.notice.sold_out.body':
        'Impapuro zose zafashwe, nta cyasigaye cyo kugura hano.',
    'investor.deal.notice.frozen.title': 'Iri shoramari ryahagaritswe',
    'investor.deal.notice.frozen.body':
        'Rozine yahagaritse ishoramari rishya mu gihe hakorwa isuzuma. Ibyo ufite ntibigirwaho ingaruka.',
    'investor.deal.notice.withdrawn.title': 'Iri shoramari ryakuweho',
    'investor.deal.notice.withdrawn.body':
        'Ikigo cyarikuyeho mbere yo kuzura. Amafaranga yari yiyemejwe asubizwa yose nta kiguzi.',
    'investor.deal.funding_progress': 'Aho ishoramari rigeze',
    'investor.deal.loading': "Birimo gufungura ibisobanuro by'umushinga",
    'investor.deal.raised': 'BYAKUSANYIJWE',
    'investor.deal.target': 'INTEGO',
    'investor.deal.time_left': 'IGIHE GISIGAYE',
    'investor.deal.notes_sold_of':
        "{sold} kuri {total} z'impapuro zagurishijwe",
    'investor.deal.notes_of': "{sold} kuri {total} z'impapuro",
    'investor.deal.details': 'IBISOBANURO',
    'investor.deal.photos': 'Amafoto',
    'investor.deal.reported_by_business': "Byatanzwe n'ikigo",
    'investor.deal.use_of_funds': 'Icyo amafaranga azakoreshwa',
    'investor.deal.use.inventory': 'Ibicuruzwa',
    'investor.deal.use.equipment': 'Ibikoresho',
    'investor.deal.use.expansion': 'Kwagura',
    'investor.deal.use.hiring': 'Gutanga akazi',
    'investor.deal.use.working_capital': 'Igishoro cyo gukora',
    'investor.deal.use.other': 'Ibindi',
    'investor.deal.financials': 'Imari',
    'investor.deal.verified_audited': 'Byemejwe · byagenzuwe',
    'investor.deal.avg_monthly_revenue': "IMPUZANDENGO Y'INJIZA",
    'investor.deal.ebitda': 'EBITDA',
    'investor.deal.existing_debt': 'IDENI RISANZWE',
    'investor.deal.capacity': 'UBUSHOBOZI BWEMEJWE',
    'investor.deal.rozine_tag': 'Rozine',
    'investor.deal.assessment': 'Isuzuma rya Rozine',
    'investor.deal.rozine_analysis': 'Isesengura rya Rozine',
    'investor.deal.assessed_by_rozine': 'Byasuzumwe na Rozine',
    'investor.deal.rozine_rating': 'Amanota ya Rozine',
    'investor.deal.out_of_five': '{score} / 5',
    'investor.deal.total_return': 'Inyungu yose',
    'investor.deal.repayment': 'Kwishyura',
    'investor.deal.monthly': 'Buri kwezi',
    'investor.deal.term': 'Igihe',
    'investor.deal.months_long': 'Amezi {count}',
    'investor.deal.track_record': 'Amateka kuri Rozine',
    'investor.deal.history': 'AMATEKA',
    'investor.deal.raises': 'ISHORAMARI',
    'investor.deal.raises_done': 'ISHORAMARI RYAKOZWE',
    'investor.deal.on_time': 'KU GIHE',
    'investor.deal.repaid': 'BYISHYUWE',
    'investor.deal.verified': 'Byemejwe',
    'investor.deal.about': 'Ibyerekeye ikigo',
    'investor.deal.registration': "Nimero y'ikigo muri RDB",
    'investor.deal.registered': 'Cyanditswe',
    'investor.deal.registered_value': '{year} · imyaka {years}',
    'investor.deal.team_size': "Umubare w'abakozi",
    'investor.deal.industry': 'Urwego',
    'investor.deal.address': 'Aderesi',
    'investor.deal.monthly_updates': 'Raporo za buri kwezi',
    'investor.deal.audited': 'Byagenzuwe',
    'investor.deal.rating': 'AMANOTA',
    'investor.deal.interest': 'INYUNGU',
    'investor.deal.return': 'INYUNGU',
    'investor.deal.verified_pill': '✓ Byemejwe',
    'investor.deal.overdue_title':
        'Raporo ya {month} yaratinze · irakurikiranwa',
    'investor.deal.overdue_body':
        "Raporo yemejwe ntiyagenzuwe bitarenze ku itariki ya 7. Itsinda rya Rozine rishinzwe kubahiriza amategeko rirakurikirana n'umugenzuzi w'ikigo.",
    'investor.deal.your_investment': 'ISHORAMARI RYAWE',
    'investor.deal.repaid_monthly': 'Byishyurwa buri kwezi · amezi {count}',
    'investor.deal.unit_times_one': '{price} × urupapuro {count}',
    'investor.deal.unit_times_other': '{price} × impapuro {count}',
    'investor.deal.expected_return': 'Inyungu iteganyijwe',
    'investor.deal.total_at_maturity': 'Byose byishyurwa ku iherezo',
    'investor.deal.fewer_notes': 'Urupapuro rumwe ruke',
    'investor.deal.more_notes': 'Urupapuro rumwe rwiyongereye',
    'investor.checkout.title': 'Kwishyura',
    'investor.checkout.done': 'Byarangiye',
    'investor.checkout.deal_line': 'Inyungu {rate}% · amezi {count}',
    'investor.checkout.closes_in': 'BIRAFUNGA MURI',
    'investor.checkout.amount': 'AMAFARANGA USHORA',
    'investor.checkout.units_each_one':
        'Urupapuro {count} · {price} buri rumwe',
    'investor.checkout.units_each_other':
        'Impapuro {count} · {price} buri rumwe',
    'investor.checkout.expected_return': 'Inyungu iteganyijwe ({rate}%)',
    'investor.plus.fee': 'Amafaranga ku nyungu',
    'investor.plus.fee_label': 'Amafaranga ku nyungu · {rate}',
    'investor.plus.fee_rate': '{rate}% ({tier})',
    'investor.plus.fee_note':
        'Akatwa ku nyungu zawe gusa, ntabwo akatwa ku gishoro cyawe. Iki gipimo ntigihinduka kuri uru rupapuro.',
    'investor.plus.tier.standard': 'Isanzwe',
    'investor.plus.tier.bronze': 'Umuringa',
    'investor.plus.tier.silver': 'Ifeza',
    'investor.plus.tier.gold': 'Zahabu',
    'investor.plus.tier.platinum': 'Platine',
    'investor.plus.tier.diamond': 'Diyama',
    'investor.checkout.maturity_value': 'Agaciro ku iherezo · {date}',
    'investor.checkout.wallet': 'Ikofi',
    'investor.checkout.funding_source': 'Aho amafaranga ava',
    'investor.checkout.wallet_available': 'Ahari: {amount}',
    'investor.checkout.pay_with': 'Ishyura ukoresheje',
    'investor.checkout.wallet_line': '{wallet} · {amount} bihari',
    'investor.checkout.insufficient':
        'Amafaranga arenze ayo ufite ku ikofi. Shyira amafaranga ku ikofi cyangwa ugabanye umubare.',
    'investor.checkout.deposit': 'Shyiraho',
    'investor.checkout.disclosure': "IKIGUZI N'INGARUKA",
    'investor.checkout.acknowledge':
        "Nasomye ibisobanuro ku kiguzi n'ingaruka ({version}).",
    'investor.checkout.refusal.TRADE_BELOW_MINIMUM':
        'Shora nibura urupapuro rumwe.',
    'investor.checkout.refusal.INSUFFICIENT_AVAILABLE_FUNDS':
        'Amafaranga arenze ayo ufite ku ikofi. Shyira amafaranga ku ikofi cyangwa ugabanye umubare.',
    'investor.checkout.refusal.EXPOSURE_LIMIT':
        'Ibyo birenze urugero rwawe rwo gushora. Gabanya umubare.',
    'investor.checkout.refusal.NOTE_INELIGIBLE':
        'Iri shoramari ntirigishobora kugurwa.',
    'investor.checkout.refusal.RESERVATION_EXPIRED':
        'Impapuro wari wabitse zarekuwe. Reba umubare wongere wemeze.',
    'investor.checkout.refusal.RESTRICTION_ACTIVE':
        "Gushora byahagaritswe kuri iyi konti. Vugana n'ubufasha.",
    'investor.checkout.refusal.VERSION_CONFLICT':
        'Amabwiriza yahindutse urimo gusoma. Yongere uyasome wemeze.',
    'investor.checkout.processing': 'Biremezwa…',
    'investor.checkout.confirm': 'Emeza ishoramari · {amount}',
    'investor.checkout.fine_print':
        'Amafaranga ahita yiyemezwa · Inyungu iteganyijwe, ntisezeranyijwe',
    'investor.checkout.confirmed': 'Ishoramari ryemejwe',
    'investor.checkout.confirmed_body_before': 'Washoye',
    'investor.checkout.confirmed_body_after':
        'muri {name}. Ubu biri mu ishoramari ryawe.',
    'investor.checkout.expected_return_plain': 'Inyungu iteganyijwe',
    'investor.checkout.maturity_value_plain': 'Agaciro ku iherezo',
    'investor.checkout.maturity_date': "Itariki y'iherezo",
    'investor.checkout.transaction_id': "Nimero y'igikorwa",
    'investor.checkout.reference': 'Indango',
    'investor.checkout.view_receipt': 'Reba inyemezabwishyu',
    'investor.checkout.view_portfolio': 'Reba mu ishoramari ryawe',
    'investor.checkout.explore': 'Reba andi mahirwe',
    'investor.updates.status.healthy': 'Bimeze neza',
    'investor.updates.status.watch': 'Birakurikiranwa',
    'investor.updates.net': 'isigaye',
    'investor.updates.none': 'Nta raporo ya buri kwezi iratangazwa.',
    'investor.updates.parsed_audited': 'Inyandiko yasesenguwe · yagenzuwe',
    'investor.updates.show_more': 'Reba ibindi',
    'investor.updates.show_less': 'Reba bike',
    'investor.updates.sheet_label': 'Raporo ya {month}',
    'investor.updates.verified_report': "Raporo y'ukwezi yemejwe",
    'investor.updates.audited_by': 'Yagenzuwe aho ikigo gikorera na {name}',
    'investor.updates.licence_verified': '{licence} · byemejwe ku wa {date}',
    'investor.updates.financials': "IMARI Y'UKU KWEZI",
    'investor.updates.inflow': 'IBYINJIYE',
    'investor.updates.outflow': 'IBYASOHOTSE',
    'investor.updates.verified': 'BYEMEJWE',
    'investor.updates.from_statements': 'biva ku nyandiko za banki na MoMo',
    'investor.updates.net_month': "Isigaye y'ukwezi",
    'investor.updates.from_business': 'BIVUYE KU KIGO',
    'investor.updates.auditor_note': 'ICYO UMUGENZUZI AVUGA',
    'investor.updates.proof_photos': "AMAFOTO Y'IBIMENYETSO",
    'investor.updates.open_photo': 'Fungura ifoto: {caption}',
    'investor.updates.required_shot': 'Ifoto isabwa',
    'investor.updates.added_by_auditor': "Yongewemo n'umugenzuzi",
    'investor.updates.gps': 'AHO BYAFATIWE (GPS)',
    'investor.updates.captured': 'YAFASHWE',
    'investor.updates.camera_only':
        "Yafashwe ako kanya na kamera y'umugenzuzi wa Rozine · kohereza biva mu bubiko byahagaritswe",
    'investor.updates.photo_previous': 'Ifoto ibanza',
    'investor.updates.photo_next': 'Ifoto ikurikira',
    'investor.audit.kicker': 'Byagenzuwe mu bwigenge · {standard}',
    'investor.audit.kicker_plain': 'Byagenzuwe mu bwigenge',
    'investor.audit.verified_line': '{licence} · byemejwe ku wa {date}',
    'investor.audit.view': 'Reba',
    'investor.audit.hide': 'Hisha',
    'investor.audit.standard': 'Igipimo',
    'investor.audit.partner': 'Umugenzuzi',
    'investor.audit.licence': 'Kwiyandikisha muri ICPAR',
    'investor.audit.cash': 'Amafaranga / MoMo yabonetse',
    'investor.audit.inventory': "Ibarura ry'icyitegererezo cy'ibicuruzwa",
    'investor.audit.units': 'Ibintu {count}',
    'investor.audit.variance': "Itandukaniro n'ibipimo",
    'investor.audit.digest': 'Ikimenyetso cya raporo',
    'investor.audit.photos': "AMAFOTO Y'IGENZURA AFITE AHO YAFATIWE",
    'investor.audit.download': "Kuramo raporo y'igenzura yasinywe (PDF)",
    'investor.audit.disclaimer':
        "Ibyagaragaye ni ukuri kwabonywe hakurikijwe uburyo bwemeranyijweho — si igitekerezo cy'igenzura. Ikimenyetso gihuza uruhushya rwa CPA, GPS n'igihe kuri iyi raporo.",
    'investor.auth.tagline': 'Igishoro cyo gukura ku bigo byunguka',
    'investor.auth.have_account': 'Usanzwe ufite konti?',
    'investor.auth.log_in': 'Injira',
    'investor.auth.new_to_rozine': 'Uri mushya kuri Rozine?',
    'investor.auth.create_account': 'Fungura konti',
    'investor.auth.new_here': 'Uri mushya hano?',
    'investor.auth.create_an_account': 'Fungura konti',
    'investor.auth.create_title': 'Fungura konti yawe',
    'investor.auth.continue': 'Komeza',
    'investor.auth.please_wait': 'Tegereza gato…',
    'investor.auth.email': 'IMEYILI',
    'investor.auth.email_placeholder': 'wowe@urugero.rw',
    'investor.auth.first_name': "IZINA RY'IDINI",
    'investor.auth.last_name': "IZINA RY'UMURYANGO",
    'investor.auth.first_placeholder': 'Robert',
    'investor.auth.last_placeholder': 'Mugisha',
    'investor.auth.entity_name': "IZINA RY'IKIGO",
    'investor.auth.entity_placeholder': 'Horizon Capital Partners',
    'investor.auth.representative': 'UHAGARARIYE',
    'investor.auth.representative_placeholder': 'Aline Uwase',
    'investor.auth.password_placeholder': 'Inyuguti 6 cyangwa zirenga',
    'investor.auth.brand_headline':
        'Bona inyungu igera kuri {max}% ushyigikira ibigo byo mu Rwanda byunguka.',
    'investor.auth.brand_point_1':
        'Buri kigo kigenzurwa na CPA wemewe na ICPAR',
    'investor.auth.brand_point_2':
        'Inyungu idahinduka ya {min}–{max}% ku gihe cyose, yishyurwa buri kwezi',
    'investor.auth.brand_point_3':
        'Gurisha imigabane yawe ku bandi bashoramari ku isoko',
    'investor.auth.type.individual': 'Umuntu ku giti cye',
    'investor.auth.type.institution': 'Ikigo',
    'investor.auth.role.individual': 'Ndi umuntu ku giti cyanjye',
    'investor.auth.role.institution': 'Ndi ikigo',
    'investor.auth.role.individual_body':
        'Shora mu izina ryawe, uhereye kuri {price} ku rupapuro.',
    'investor.auth.role.institution_body':
        "Ikigega, SACCO, ikigo cy'ubwishingizi cyangwa isanduku y'ikigo gishora imari yacyo.",
    'investor.auth.register_head_title': 'Tangira',
    'investor.auth.login.head_title': 'Kwinjira',
    'investor.auth.login.title': 'Murakaza neza',
    'investor.auth.login.subtitle': 'Injira ucunge ishoramari ryawe.',
    'investor.auth.login.method': 'Uburyo bwo kwinjira',
    'investor.auth.login.tab_password': "Ijambo ry'ibanga",
    'investor.auth.login.tab_pin': 'PIN',
    'investor.auth.login.id': 'IMEYILI CYANGWA TELEFONI',
    'investor.auth.login.password': "IJAMBO RY'IBANGA",
    'investor.auth.login.pin': "PIN Y'IMIBARE 4",
    'investor.auth.login.use_pin': 'Koresha PIN aho',
    'investor.auth.login.use_password': "Koresha ijambo ry'ibanga aho",
    'investor.auth.login.use_pin_short': 'Koresha PIN',
    'investor.auth.login.use_password_short': "Koresha ijambo ry'ibanga",
    'investor.auth.login.pin_note':
        'Nta OTP isabwa iyo winjiye ukoresheje PIN.',
    'investor.intro.head_title': 'Murakaza neza',
    'investor.intro.skip': 'Simbuka',
    'investor.intro.back': 'Subira inyuma',
    'investor.intro.next': 'Komeza',
    'investor.intro.start': 'Tangira',
    'investor.intro.slide': 'igice',
    'investor.intro.slide_of': '{index} kuri {count}',
    'investor.intro.audit.title': "Buri kigo kigenzurwa mbere y'uko ukibona.",
    'investor.intro.audit.body':
        "Rozine ishyira ku rutonde gusa ibigo byatsinze igenzura ryigenga — si amanota y'inguzanyo, ni igenzura nyaryo.",
    'investor.intro.audit.point_1':
        "CPA wemewe na ICPAR agenzura ibitabo by'imari kandi agasura aho ikigo gikorera.",
    'investor.intro.audit.point_2':
        "Imyaka itanu y'ubucuruzi bwunguka, n'inyungu ya 10% idahagarara.",
    'investor.intro.audit.point_3':
        'Raporo za buri kwezi zikomeza kuza igihe amafaranga yawe ashowe.',
    'investor.intro.rate.title':
        'Inyungu idahinduka ya {min}–{max}%, yishyurwa buri kwezi.',
    'investor.intro.rate.body':
        "Umenya inyungu yose mbere yo kwiyemeza. Nta nyungu y'inyungu, nta gipimo gihinduka, nta kiguzi gikurwaho mbere.",
    'investor.intro.rate.point_1':
        "Igihe kiri hagati y'amezi {term_min} na {term_max}, byishyurwa buri kwezi.",
    'investor.intro.rate.point_2':
        "Igipimo gishyirwaho hakurikijwe amanota y'ikigo n'igihe, kandi ntigihinduka.",
    'investor.intro.rate.point_3':
        'Uhereye kuri {price} ku rupapuro, mu bigo byinshi uko ushaka.',
    'investor.intro.exit.title': 'Ukeneye amafaranga yawe kare? Yagurishe.',
    'investor.intro.exit.body':
        'Gurisha impapuro zawe ku bandi bashoramari ku isoko — bihuzwa mu masegonda ku giciro gikwiye, cyangwa uzishyire ku isoko ushyireho igiciro cyawe.',
    'investor.intro.exit.point_1':
        "Bihuzwa n'ibiciro bisanzweho kandi byishyurwa ku ikofi yawe.",
    'investor.intro.exit.point_2':
        'Shyiraho igiciro fatizo kugira ngo bitagurishwa munsi yacyo.',
    'investor.intro.exit.point_3':
        'Gurisha igice kimwe, ibisigaye bikomeze kunguka.',
    'investor.signup.progress': 'Aho kwiyandikisha bigeze',
    'investor.signup.step_label': 'Intambwe {step} kuri {count} · {type}',
    'investor.signup.create': 'Fungura konti',
    'investor.signup.identity.head_title': 'Umwirondoro',
    'investor.signup.identity.title': 'Umwirondoro',
    'investor.signup.identity.title_institution': "Amakuru y'ikigo",
    'investor.signup.identity.account_type': 'UBWOKO BWA KONTI',
    'investor.signup.identity.type_individual': 'Umuntu ku giti cye',
    'investor.signup.identity.type_institution': 'Ikigo · Plus',
    'investor.signup.identity.institution_name': "IZINA RY'IKIGO",
    'investor.signup.identity.contact_person': 'UWO KUVUGANA NA WE',
    'investor.signup.identity.address': 'ADERESI / AHO GIHEREREYE',
    'investor.signup.identity.address_placeholder':
        'KN 4 Ave, Nyarugenge, Kigali',
    'investor.signup.identity.company_code': "NIMERO Y'IKIGO MURI RDB",
    'investor.signup.identity.company_code_hint':
        "Nimero y'imibare 9 iri ku cyemezo cya RDB",
    'investor.signup.identity.id_type': "UBWOKO BW'INDANGAMUNTU",
    'investor.signup.identity.id_number':
        "NIMERO Y'INDANGAMUNTU / PASIPORO / URUHUSHYA",
    'investor.signup.identity.id_hint':
        "Nk'uko yanditse ku cyangombwa wahisemo",
    'investor.signup.id.national_id': 'Indangamuntu',
    'investor.signup.id.passport': 'Pasiporo',
    'investor.signup.id.drivers_license': 'Uruhushya rwo gutwara',
    'investor.signup.id_placeholder.national_id': '1 1990 8 0012345 6 78',
    'investor.signup.id_placeholder.passport': 'PC1234567',
    'investor.signup.id_placeholder.drivers_license': 'RA-0123456',
    'investor.signup.address.head_title': 'Aderesi',
    'investor.signup.address.title': 'Aderesi',
    'investor.signup.address.country': 'IGIHUGU',
    'investor.signup.address.province': 'INTARA',
    'investor.signup.address.district': 'AKARERE',
    'investor.signup.address.sector': 'UMURENGE',
    'investor.signup.address.cell': 'AKAGARI',
    'investor.signup.address.province_placeholder': 'Umujyi wa Kigali',
    'investor.signup.address.district_placeholder': 'Gasabo',
    'investor.signup.address.sector_placeholder': 'Kimironko',
    'investor.signup.address.cell_placeholder': 'Bibare',
    'investor.signup.contact.head_title': 'Uko wabonwa',
    'investor.signup.contact.title': 'Uko wabonwa',
    'investor.signup.contact.email': 'IMEYILI',
    'investor.signup.contact.phone': 'NIMERO YA TELEFONI',
    'investor.signup.contact.country_code': "Kode y'igihugu",
    'investor.signup.security.head_title': 'Rinda konti yawe',
    'investor.signup.security.title': 'Rinda konti yawe',
    'investor.signup.security.subtitle':
        "Kora PIN y'imibare 4, cyangwa ijambo ry'ibanga ry'inyuguti 6 cyangwa zirenga.",
    'investor.signup.security.pin': 'PIN',
    'investor.signup.security.password': "Ijambo ry'ibanga",
    'investor.signup.security.password_placeholder': 'Nibura inyuguti 6',
    'investor.signup.payment.head_title': 'Uburyo bwo kwishyura',
    'investor.signup.payment.title': 'Uburyo bwo kwishyura',
    'investor.signup.payment.subtitle':
        'Huza uburyo bwo gushyira amafaranga ku ikofi. Tubyemeza dukoresheje OTP.',
    'investor.signup.payment.mtn': 'MTN Mobile Money',
    'investor.signup.payment.airtel': 'Airtel Money',
    'investor.signup.payment.bank': 'Konti ya banki',
    'investor.signup.payment.otp': "Kode y'ikoreshwa rimwe",
    'investor.signup.payment.otp_placeholder': 'Andika OTP',
    'investor.signup.payment.send_otp': 'Ohereza OTP',
    'investor.signup.payment.otp_sent':
        "✓ OTP yoherejwe · andika kode y'imibare 6 wemeze",
    'investor.signup.agree.head_title': 'Suzuma wemere',
    'investor.signup.agree.title': 'Suzuma wemere',
    'investor.signup.agree.terms_before': 'Nemeye',
    'investor.signup.agree.terms': "Amabwiriza n'Amategeko ya Rozine",
    'investor.signup.agree.terms_after': '.',
    'investor.signup.agree.privacy_before': 'Nasomye',
    'investor.signup.agree.privacy': 'Itangazo ku Ibanga',
    'investor.signup.agree.privacy_after': '.',
    'investor.kyc.progress': 'Aho igenzura rigeze',
    'investor.kyc.kicker_identity': "IGENZURA RY'UMWIRONDORO",
    'investor.kyc.kicker_entity': "IGENZURA RY'IKIGO",
    'investor.kyc.personal.title': 'Amakuru bwite',
    'investor.kyc.personal.subtitle':
        'Tugenzura buri mushoramari kugira ngo urubuga rugire umutekano kandi rwubahirize amategeko.',
    'investor.kyc.document.title': 'Emeza indangamuntu yawe',
    'investor.kyc.document.subtitle':
        "Hitamo ubwoko bw'icyangombwa wohereze amafoto agaragara neza.",
    'investor.kyc.liveness.title': 'Kwemeza ko uri muzima',
    'investor.kyc.liveness.subtitle':
        'Ifoto yawe yihuse yemeza ko ari wowe koko.',
    'investor.kyc.entity.title': "Igenzura ry'ikigo",
    'investor.kyc.entity.subtitle':
        "Tugenzura ikigo mu mategeko mbere y'uko hagira igishoro cyiyemezwa.",
    'investor.kyc.representative.title': 'Uhagarariye wemewe',
    'investor.kyc.representative.subtitle':
        "Umuntu uzakora ishoramari mu izina ry'ikigo.",
    'investor.kyc.declarations.title': "Inkomoko y'amafaranga n'amatangazo",
    'investor.kyc.declarations.subtitle': 'Birasabwa mbere yo gutangiza manda.',
    'investor.kyc.country': 'IGIHUGU UTUYEMO',
    'investor.kyc.dob': "ITARIKI Y'AMAVUKO",
    'investor.kyc.dob_placeholder': 'UU / UK / UMWAKA',
    'investor.kyc.id_number': "NIMERO Y'INDANGAMUNTU",
    'investor.kyc.upload_front': 'Ohereza imbere',
    'investor.kyc.upload_back': 'Ohereza inyuma',
    'investor.kyc.uploaded_front': 'Imbere hoherejwe',
    'investor.kyc.uploaded_back': 'Inyuma hoherejwe',
    'investor.kyc.selfie': 'Kanda ufate ifoto yawe',
    'investor.kyc.selfie_done': 'Ifoto yafashwe',
    'investor.kyc.entity_type': "UBWOKO BW'IKIGO",
    'investor.kyc.entity.fund': "Ikigega cy'ishoramari",
    'investor.kyc.entity.sacco': 'SACCO',
    'investor.kyc.entity.treasury': "Isanduku y'ikigo",
    'investor.kyc.entity.insurer': "Ikigo cy'ubwishingizi",
    'investor.kyc.entity.pension': "Ikigega cy'izabukuru",
    'investor.kyc.entity.other': 'Ibindi',
    'investor.kyc.company_code': "NIMERO Y'IKIGO MURI RDB",
    'investor.kyc.incorporated': 'IGIHE CYASHINZWE',
    'investor.kyc.incorporated_placeholder': 'UK / UMWAKA',
    'investor.kyc.certificate': 'Ohereza icyemezo',
    'investor.kyc.certificate_done': 'Icyemezo cyoherejwe',
    'investor.kyc.certificate_hint': "Icyemezo cy'ishingwa · PDF cyangwa ifoto",
    'investor.kyc.rep_name': 'AMAZINA YOSE',
    'investor.kyc.rep_role': 'INSHINGANO',
    'investor.kyc.rep_role_placeholder': 'Ushinzwe isanduku',
    'investor.kyc.rep_id': "NIMERO Y'INDANGAMUNTU CYANGWA PASIPORO",
    'investor.kyc.resolution': "Ohereza icyemezo cy'inama y'ubutegetsi",
    'investor.kyc.resolution_done': "Icyemezo cy'inama y'ubutegetsi cyoherejwe",
    'investor.kyc.resolution_hint':
        'Icyemezo cyemerera uyu muntu kwiyemeza igishoro',
    'investor.kyc.rep_liveness': 'Kwemeza ko uhagarariye ari muzima',
    'investor.kyc.source': "INKOMOKO NYAMUKURU Y'AMAFARANGA",
    'investor.kyc.funds.operations': "Imari y'ikigo ubwacyo",
    'investor.kyc.funds.member_savings': "Ubwizigame bw'abanyamuryango",
    'investor.kyc.funds.investment_returns': "Igishoro gicungwa cy'abakiriya",
    'investor.kyc.funds.premiums': "Impano y'igishoro",
    'investor.kyc.funds.contributions': "Imisanzu y'izabukuru",
    'investor.kyc.funds.other': 'Ibindi',
    'investor.kyc.commitment': 'ISHORAMARI RITEGANYIJWE MU MWAKA',
    'investor.kyc.commitment_hint':
        'Ni ikigereranyo gusa — ishoramari nyaryo urishyiraho muri porogaramu.',
    'investor.kyc.aml':
        "Nemeza ko ba nyir'ikigo nyakuri bagaragajwe kandi ko amafaranga atavuye mu byaha.",
    'investor.kyc.target':
        "Numva ko inyungu ya Rozine Plus ari intego, atari isezerano, ko impapuro zibikwa mu izina ry'ikigo, kandi ko Rozine idafata igishoro mu mari yayo.",
    'investor.kyc.submit': 'Ohereza kugira ngo bigenzurwe',
    'investor.kyc.previous': 'Intambwe ibanza',
    'investor.kyc.submitted_notice':
        'Amakuru yawe ari mu itsinda ryacu rishinzwe kubahiriza amategeko. Tuzakumenyesha nibamara kuyasuzuma.',
    'investor.kyc.rejected_notice':
        'Itsinda rishinzwe kubahiriza amategeko ntiryashoboye kwemeza aya makuru: {reason}. Yakosore wongere uyohereze.',
    'investor.kyc.verifying': 'Turimo kugenzura umwirondoro wawe…',
    'investor.kyc.verifying_entity': 'Turimo kugenzura ikigo…',
    'investor.verified.title': 'Wemejwe',
    'investor.verified.body':
        'Murakaza neza kuri Rozine. Ikofi yawe yiteguye kandi amahirwe {count} yemejwe aragutegereje.',
    'investor.verified.kyc': 'IMITERERE YA KYC',
    'investor.verified.verified': 'Byemejwe',
    'investor.verified.wallet': 'IKOFI',
    'investor.verified.cta': 'Tangira kureba',
    'investor.health.healthy': 'Bimeze neza',
    'investor.health.watch': 'Birakurikiranwa',
    'investor.health.arrears': 'Birarimo ibirarane',
    'investor.health.frozen': 'Byahagaritswe',
    'investor.health.defaulted': 'Ntibyishyuwe',
    'investor.health.matured': 'Byarangiye',
    'investor.portfolio.title': 'Ishoramari ryanjye',
    'investor.portfolio.tabs': 'Ibyo ufite',
    'investor.portfolio.tab.active': 'Bikora',
    'investor.portfolio.tab.matured': 'Byarangiye',
    'investor.portfolio.tab.secondary': 'Isoko rya kabiri',
    'investor.portfolio.tab.saved': 'Ibyo wabitse',
    'investor.portfolio.invested_line': 'Washoye RWF {amount}',
    'investor.portfolio.matures_line':
        'Birangira {date} · {made}/{total} byishyuwe',
    'investor.portfolio.details': 'Ibisobanuro',
    'investor.portfolio.see_all': 'Reba izindi {count} ›',
    'investor.portfolio.empty.active.title': 'Nta cyo ufite ubu',
    'investor.portfolio.empty.active.body':
        "Impapuro uguze zigaragara hano hamwe n'ubwishyu na raporo zazo.",
    'investor.portfolio.empty.matured.title': 'Nta kintu kiri hano',
    'investor.portfolio.empty.matured.body':
        'Ibyo ufite muri iki cyiciro bizagaragara hano.',
    'investor.portfolio.empty.secondary.title': 'Nta kintu kiri hano',
    'investor.portfolio.empty.secondary.body':
        'Ibyo ufite muri iki cyiciro bizagaragara hano.',
    'investor.portfolio.empty.saved.title': 'Nta mahirwe urabika',
    'investor.portfolio.browse_deals': 'Reba amahirwe',
    'investor.portfolio.total_value': 'AGACIRO KOSE',
    'investor.portfolio.businesses_one': 'Ikigo 1',
    'investor.portfolio.businesses': 'Ibigo {count}',
    'investor.portfolio.invested': 'BYASHOWE',
    'investor.portfolio.total_gain': 'INYUNGU YOSE',
    'investor.portfolio.this_month': 'UKU KWEZI',
    'investor.portfolio.projected': 'BITEGANYIJWE · AMEZI 3 ARI IMBERE',
    'investor.portfolio.next_payout': 'Gikurikira: {amount} · {month}',
    'investor.portfolio.avg_month': 'IMPUZANDENGO / UKWEZI',
    'investor.portfolio.upcoming': 'UBWISHYU BUTEGANYIJWE · RWF',
    'investor.portfolio.pays_one': 'Ikigo {count} kiriha uku kwezi',
    'investor.portfolio.pays_other': 'Ibigo {count} biriha uku kwezi',
    'investor.portfolio.upcoming_note':
        'Kanda ukwezi urebe ibigo bikwishyura. Igiteranyo kigabanuka uko impapuro zirangira.',
    'investor.portfolio.diversification': 'UKWIRAGIZA MU NZEGO',
    'investor.portfolio.risk_balance': "UBURINGANIRE BW'INGARUKA",
    'investor.portfolio.concentrated': 'Ishoramari ryibanze hamwe',
    'investor.portfolio.concentrated_body':
        "{name} ingana na {pct}% by'igishoro washoye. Gukwirakwiza mu bigo byinshi bigabanya ingaruka niba kimwe kitagenze neza.",
    'investor.portfolio.browse': 'Reba andi mahirwe',
    'investor.portfolio.balanced': 'Byakwirakwijwe neza',
    'investor.portfolio.balanced_body':
        'Igishoro cyawe kiri mu bigo byinshi nta na kimwe kiganje. Komeza utyo.',
    'investor.portfolio.balanced_count':
        'Igishoro cyawe kiri mu bigo {count} nta na kimwe kiganje. Komeza utyo.',
    'investor.portfolio.idle': 'BIDAKORA KU IKOFI',
    'investor.portfolio.idle_body': 'Bishore mu rupapuro rushya.',
    'investor.portfolio.reinvest': 'Ongera ushore ›',
    'investor.holding.frozen_title': 'Uru rupapuro rwahagaritswe',
    'investor.holding.frozen_body':
        'Rozine yahagaritse uru rupapuro mu gihe hakorwa isuzuma. Ubwishyu buzasubukurwa rurekuwe; uburenganzira bwawe ntibuhinduka.',
    'investor.holding.latest_report': 'Raporo iheruka',
    'investor.holding.latest_report_month': 'Raporo ya {month}',
    'investor.holding.on_watch': 'Biri gukurikiranwa · birimo gusuzumwa',
    'investor.holding.on_plan': 'Biri ku murongo',
    'investor.holding.invested': 'BYASHOWE',
    'investor.holding.expected_profit': 'INYUNGU ITEGANYIJWE',
    'investor.holding.yield': 'Inyungu ya {rate}%',
    'investor.holding.total_at_maturity': 'BYOSE KU IHEREZO',
    'investor.holding.principal_yield': 'Igishoro + inyungu',
    'investor.holding.received': 'BYAKIRIWE KUGEZA UBU',
    'investor.holding.payments': '{made}/{total} byishyuwe',
    'investor.holding.due.today': 'Uyu munsi',
    'investor.holding.due.tomorrow': 'Ejo',
    'investor.holding.due.in_days': 'mu minsi {count}',
    'investor.holding.next_payment': 'UBWISHYU BUKURIKIRA',
    'investor.holding.no_next.matured': 'Byishyuwe byose',
    'investor.holding.no_next.paused': 'Byahagaritswe',
    'investor.holding.on_time': 'KWISHYURA KU GIHE',
    'investor.holding.all_on_time': 'Byose ku gihe',
    'investor.holding.late_one': 'Ubwishyu {count} bwatinze',
    'investor.holding.late_other': 'Ubwishyu {count} bwatinze',
    'investor.holding.rating': 'AMANOTA',
    'investor.holding.out_of_five': 'kuri 5',
    'investor.holding.repaid': 'BYISHYUWE',
    'investor.holding.month_left_one': 'Ukwezi {count} gusigaye',
    'investor.holding.month_left_other': 'Amezi {count} asigaye',
    'investor.holding.rating_changed': 'Amanota yahindutse',
    'investor.holding.rating_changed_body':
        'Wawushyizemo ifite {from} · ubu ni {to}',
    'investor.holding.rating_changed_note':
        "Amanota ahinduka buri raporo y'ukwezi yagenzuwe.",
    'investor.holding.plan_title': 'Ubwishyu bumwe bwimuriwe',
    'investor.holding.plan_state.on_track': 'Biri ku murongo',
    'investor.holding.plan_state.off_track': 'Byataye umurongo',
    'investor.holding.plan_body':
        "{name} yatubwiye mbere y'itariki. Byagenzuwe ku mibare yagenzuwe, byemerwa, kandi amanota yacyo yamanutse.",
    'investor.holding.plan_reason': 'Impamvu yatanzwe',
    'investor.holding.plan_arrives': 'Amafaranga azagera',
    'investor.holding.plan_deferred': 'Byimuriwe',
    'investor.holding.delayed': 'Ubwishyu bwatinze',
    'investor.holding.defaulted': 'Ubwishyu ntibwakozwe',
    'investor.holding.days_overdue': "Iminsi {count} y'ubukererwe",
    'investor.holding.in_recovery': 'MU KWISHYUZA',
    'investor.holding.arrears_body':
        'Ubwishyu buheruka bwa {name} bwatinze. Amanota yayo muri Rozine yahinduwe kandi itsinda ryacu ryo kwishyuza riri mu kazi.',
    'investor.holding.step.missed.title': 'Ubwishyu ntibwakozwe',
    'investor.holding.step.missed.body':
        'Sisitemu zikoresha zagaragaje ubwishyu bwatinze.',
    'investor.holding.step.contacted.title': 'Ikigo cyavuganywe',
    'investor.holding.step.contacted.body':
        'Rozine yavuganye na cyo yemeza impamvu.',
    'investor.holding.step.plan.title': 'Gahunda yo kwishyura yumvikanyweho',
    'investor.holding.step.plan.body':
        'Ingengabihe yo kwishyura ibirarane irimo gutunganywa.',
    'investor.holding.step.resume.title': 'Kwishyura birasubukurwa',
    'investor.holding.step.resume.body':
        'Ubwishyu busanzwe bwa buri kwezi burakomeza.',
    'investor.holding.claim_note':
        'Igishoro cyawe gikomeza kuba umwenda ikigo kigufitiye. Rozine ikwishyuriza.',
    'investor.holding.photos': 'AMAFOTO',
    'investor.holding.progress': 'AHO KWISHYURA BIGEZE',
    'investor.holding.payments_made': '{made} kuri {total} byishyuwe',
    'investor.holding.investors': 'Abashoramari {count}',
    'investor.holding.updates': 'RAPORO ZA BURI KWEZI',
    'investor.wallet.title': 'Ikofi',
    'investor.wallet.available': 'AMAFARANGA AHARI',
    'investor.wallet.status.active': 'Irakora',
    'investor.wallet.status.restricted': 'Irabujijwe',
    'investor.wallet.pending_withdrawal': 'Kubikuza {amount} bitegereje',
    'investor.wallet.instant': 'Ahita ashobora gushorwa',
    'investor.wallet.rozine_wallet': 'Ikofi ya Rozine',
    'investor.wallet.deposit': 'Shyiraho',
    'investor.wallet.withdraw': 'Bikuza',
    'investor.wallet.panel.deposit': 'Ongeraho amafaranga',
    'investor.wallet.panel.withdraw': 'Bikuza',
    'investor.wallet.amount': 'AMAFARANGA',
    'investor.wallet.method': 'UBURYO BWO KWISHYURA',
    'investor.wallet.method_to': 'UBURYO BWO KWISHYURA',
    'investor.wallet.fee': 'Ikiguzi cyo gukora',
    'investor.wallet.result.deposit': 'Amafaranga mashya ahari',
    'investor.wallet.result.withdraw': 'Uzakira',
    'investor.wallet.confirm.deposit': 'Emeza gushyiraho',
    'investor.wallet.confirm.withdraw': 'Emeza kubikuza',
    'investor.wallet.processing': 'Birimo koherezwa…',
    'investor.wallet.refusal.TRADE_BELOW_MINIMUM':
        'Andika nibura amafaranga make yemewe',
    'investor.wallet.refusal.INSUFFICIENT_AVAILABLE_FUNDS':
        'Amafaranga arenze ayo ufite',
    'investor.wallet.refusal.DAILY_LIMIT':
        'Birenze urugero rwo kubikuza ku munsi',
    'investor.wallet.refusal.RESTRICTION_ACTIVE':
        "Kubikuza byahagaritswe kuri iyi konti. Vugana n'ubufasha.",
    'investor.wallet.no_methods': 'Nta konti yo kwishyurirwaho irahuzwa.',
    'investor.wallet.link_account': 'Huza konti',
    'investor.wallet.earn.title': "Amateka y'inyungu",
    'investor.wallet.earn.subtitle': "Inyungu yabonetse n'igishoro cyagarutse.",
    'investor.wallet.earn.from': 'Itariki itangira',
    'investor.wallet.earn.to': 'kugeza',
    'investor.wallet.earn.to_date': 'Itariki irangira',
    'investor.wallet.earn.ranges': "Igihe cy'inyungu",
    'investor.wallet.earn.range.3m': 'Amezi 3',
    'investor.wallet.earn.range.6m': 'Amezi 6',
    'investor.wallet.earn.range.12m': 'Amezi 12',
    'investor.wallet.earn.range.ytd': 'Uyu mwaka',
    'investor.wallet.earn.interest': 'INYUNGU',
    'investor.wallet.earn.received': 'BYAKIRIWE',
    'investor.wallet.earn.payouts': 'UBWISHYU',
    'investor.wallet.earn.avg': 'IMPUZANDENGO / UKWEZI',
    'investor.wallet.earn.month': 'UKWEZI',
    'investor.wallet.earn.paid': 'BYISHYUWE',
    'investor.wallet.earn.total': 'BYOSE BYINJIYE',
    'investor.wallet.earn.empty': "Nta bwishyu bwageze hagati y'izo tariki.",
    'investor.wallet.tx.title': "Amateka y'ibikorwa",
    'investor.wallet.tx.title_short': 'Ibikorwa',
    'investor.wallet.tx.export': 'Kuramo',
    'investor.wallet.tx.pdf': 'Kuramo PDF',
    'investor.wallet.tx.csv': 'Kuramo Excel',
    'investor.wallet.tx.ranges': "Igihe cy'ibikorwa",
    'investor.wallet.tx.range.today': 'Uyu munsi',
    'investor.wallet.tx.range.7d': 'Iminsi 7',
    'investor.wallet.tx.range.30d': 'Iminsi 30',
    'investor.wallet.tx.range.all': 'Byose',
    'investor.wallet.tx.kind.deposit': 'Gushyiraho · {counterparty}',
    'investor.wallet.tx.kind.withdrawal': 'Kubikuza · {counterparty}',
    'investor.wallet.tx.kind.investment': 'Ishoramari · {counterparty}',
    'investor.wallet.tx.kind.payout': 'Ubwishyu · {counterparty}',
    'investor.wallet.tx.kind.refund': 'Gusubizwa · {counterparty}',
    'investor.wallet.tx.kind.fee': 'Ikiguzi · {counterparty}',
    'investor.wallet.tx.status.completed': 'Byarangiye',
    'investor.wallet.tx.status.pending': 'Bitegereje',
    'investor.wallet.tx.status.failed': 'Byanze',
    'investor.wallet.tx.empty': 'Nta bikorwa muri iki gihe.',
    'investor.wallet.receipt.label': 'Inyemezabwishyu',
    'investor.wallet.receipt.kind.deposit': 'Gushyiraho',
    'investor.wallet.receipt.kind.withdrawal': 'Kubikuza',
    'investor.wallet.receipt.kind.investment': 'Ishoramari',
    'investor.wallet.receipt.kind.payout': 'Ubwishyu bwa buri kwezi',
    'investor.wallet.receipt.kind.refund': 'Gusubizwa',
    'investor.wallet.receipt.kind.fee': 'Ikiguzi',
    'investor.wallet.receipt.amount': 'AMAFARANGA',
    'investor.wallet.receipt.status': 'Imiterere',
    'investor.wallet.receipt.type': 'Ubwoko',
    'investor.wallet.receipt.credit': 'Byinjiye',
    'investor.wallet.receipt.debit': 'Byasohotse',
    'investor.wallet.receipt.when': "Itariki n'isaha",
    'investor.wallet.receipt.transaction_id': "Nimero y'igikorwa",
    'investor.wallet.receipt.reference': 'Indango',
    'investor.wallet.receipt.before': 'Amafaranga mbere',
    'investor.wallet.receipt.after': 'Amafaranga nyuma',
    'investor.wallet.receipt.gross': 'Amafaranga yose',
    'investor.wallet.receipt.fee': 'Ikiguzi cyo gukora',
    'investor.wallet.receipt.net': 'Ayakiriwe',
    'investor.wallet.receipt.principal': 'Igishoro cyagarutse',
    'investor.wallet.receipt.return': 'Inyungu yishyuwe',
    'investor.wallet.receipt.payout_fee': "Ikiguzi cy'umushoramari (1%)",
    'investor.wallet.receipt.done': 'Byarangiye',
    'investor.profile.title': 'Umwirondoro',
    'investor.profile.menu': "Urutonde rw'umwirondoro",
    'investor.profile.kyc.verified': '✓ KYC yemejwe',
    'investor.profile.kyc.pending': 'Igenzura rirakomeje',
    'investor.profile.kyc.unverified': 'Ntibyemejwe',
    'investor.profile.kyc.expired': 'Icyangombwa cyarangiye',
    'investor.profile.type.individual': 'Umuntu ku giti cye',
    'investor.profile.type.institution': 'Ikigo',
    'investor.profile.support.label': 'Ubufasha',
    'investor.profile.support.title': 'Vugana n’ubufasha bwa Rozine',
    'investor.profile.support.sub':
        'Baza ikibazo cyangwa utange ikirego. Itsinda rigusubiza mu gice cy’ubufasha.',
    'investor.profile.support.message': 'Andika',
    'investor.profile.member_since': 'Umunyamuryango kuva {date}',
    'investor.profile.item.linked': 'Konti zahujwe',
    'investor.profile.item.statements': "Inyandiko z'imari n'imisoro",
    'investor.profile.item.automation': 'Auto-Deploy',
    'investor.profile.item.verification': "Igenzura ry'umwirondoro",
    'investor.profile.item.terms': "Amabwiriza n'Amategeko",
    'investor.profile.item.privacy': 'Itangazo ku Ibanga',
    'investor.profile.item.personal': 'Umwirondoro wawe',
    'investor.profile.item.plan': 'Rozine Plus · uko uyibona',
    'investor.profile.item.security': 'Umutekano wa konti',
    'investor.profile.item.help': 'Ubufasha',
    'investor.profile.plan_title': 'Rozine Plus',
    'investor.profile.personal.name': 'AMAZINA YOSE',
    'investor.profile.personal.email': 'IMEYILI',
    'investor.profile.personal.phone': 'TELEFONI',
    'investor.profile.personal.id_type': "UBWOKO BW'INDANGAMUNTU",
    'investor.profile.personal.id_number': "NIMERO Y'INDANGAMUNTU",
    'investor.profile.personal.passport_number': 'NIMERO YA PASIPORO',
    'investor.profile.personal.address': 'ADERESI',
    'investor.profile.personal.country': 'IGIHUGU',
    'investor.profile.personal.country_unknown': 'Ntibizwi',
    'investor.profile.personal.province': 'INTARA',
    'investor.profile.personal.district': 'AKARERE',
    'investor.profile.personal.sector': 'UMURENGE',
    'investor.profile.personal.cell': 'AKAGARI',
    'investor.profile.personal.not_provided': 'Ntibyatanzwe',
    'investor.profile.security.two_factor': 'Kwinjira mu byiciro bibiri',
    'investor.profile.security.two_factor_on':
        'Birakora · kode ya porogaramu yemeza iyo winjira',
    'investor.profile.security.login_alerts': 'Imenyesha ryo kwinjira',
    'investor.profile.security.login_alerts_hint':
        'Nyoherereza imeri igikoresho gishya cyinjiye',
    'investor.profile.security.two_factor_off':
        'Ntibikora · ongeraho kode ya porogaramu yemeza iyo winjira',
    'investor.profile.security.password': 'Hindura ijambobanga',
    'investor.profile.pending.plan_title': 'Rozine Plus ntirafungurwa',
    'investor.profile.pending.plan_body':
        "Ibyiciro byayo n'amafaranga yishyurwa biracyategurwa. Kugeza icyo gihe, ushora ku mahirwe umwe umwe, ukurikije amabwiriza ya buri mahirwe.",
    'investor.help.intro':
        "Ibisubizo by'ibibazo abashoramari babaza kenshi. Kanda ku ikarita usome igisubizo cyose.",
    'support.help.updated': 'Byavuguruwe ku wa {date}',
    'support.help.updated_long': 'Byavuguruwe bwa nyuma ku wa {date}',
    'support.help.count': {
        one: 'Igisubizo {count}',
        other: 'Ibisubizo {count}',
    },
    'support.help.still_title': 'Uracyakeneye ubufasha?',
    'support.help.still_body':
        'Twandikire. Twemeza ko twakiriye buri butumwa mu masaha 4.',
    'support.help.still_action': 'Andikira ubufasha',
    'investor.deals.nudge.tag': 'Komeza kwiga',
    'investor.deals.nudge.title': 'Komeza Ishuri ry’Umushoramari',
    'investor.deals.nudge.body':
        'Wasoje amasomo {done} kuri {total}. Komereza aho wari ugeze.',
    'investor.deals.nudge.later': 'Nyuma',
    'investor.deals.nudge.continue': 'Komeza',
    'investor.academy.title': 'Ishuri ry’Umushoramari',
    'investor.academy.learning': 'Kwiga',
    'investor.academy.summary_start':
        'Menya uko inyandiko z’umwenda za Rozine zikora',
    'investor.academy.summary_resume': 'Komereza aho wari ugeze',
    'investor.academy.summary_done': 'Amasomo yose yarangiye',
    'investor.academy.done_count': 'Amasomo {done} kuri {total} yarangiye',
    'investor.academy.tracks': 'Ibyiciro {count}',
    'investor.academy.intro':
        'Amasomo magufi ku buryo inyandiko z’umwenda, amanota n’ubwishyu bikora. Aho ugeze ubikwa kuri iki gikoresho gusa.',
    'investor.academy.group.basics': 'Ibanze kuri Rozine',
    'investor.academy.group.mission': 'Intego yacu',
    'investor.academy.group.investing': 'Ishoramari: ibanze',
    'investor.academy.group.rozine': 'Gushora kuri Rozine',
    'investor.academy.group_progress': '{done}/{total} birangiye',
    'investor.academy.status.start': 'Tangira',
    'investor.academy.status.progress': '{done}/{total}',
    'investor.academy.status.done': 'Byarangiye ✓',
    'investor.academy.close': 'Funga isomo',
    'investor.academy.progress': 'Aho isomo rigeze',
    'investor.academy.step': '{step} kuri {total}',
    'investor.academy.got_it': 'Ndabyumvise',
    'investor.academy.quick_check': 'Isuzuma rito',
    'investor.academy.correct': 'Ni byo',
    'investor.academy.correct_body': 'Ni byo rwose. Komeza ku gikurikira.',
    'investor.academy.wrong': 'Si byo neza',
    'investor.academy.wrong_body':
        'Ongera usome iyi ngingo. Izagaruka rimwe mbere y’uko isomo rirangira.',
    'investor.academy.next': 'Komeza',
    'investor.academy.finish': 'Soza',
    'investor.academy.done_title': 'Isomo ryarangiye',
    'investor.academy.done_body': 'Urangije isomo “{lesson}”.',
    'investor.academy.partial_title': 'Ntibiruzura neza',
    'investor.academy.partial_body':
        'Watsinze {passed} mu bibazo {total} byihuse bya {lesson}. Ongera usubire mu isomo kugira ngo urirangize.',
    'investor.academy.partial_retry': 'Ongera utangire isomo',
    'investor.academy.done_cta': 'Subira ku Ishuri',
    'investor.academy.lesson.notes.title': 'Inyandiko z’umwenda za Rozine',
    'investor.academy.lesson.notes.sub': 'Icyo ugura n’uko cyishyura',
    'investor.academy.lesson.notes.topic1.title': 'Inyandiko y’umwenda ni iki?',
    'investor.academy.lesson.notes.topic1.body':
        'Inyandiko y’umwenda ni inguzanyo y’igihe gito ihabwa ubucuruzi bwo mu Rwanda bwagenzuwe, igurishwa mu migabane ya RWF 5,000. Ubucuruzi bukwishyura igishoro cyawe n’inyungu idahinduka mu mezi 3 kugeza kuri 6.',
    'investor.academy.lesson.notes.topic1.q':
        'Kugura inyandiko y’umwenda bivuga ko…',
    'investor.academy.lesson.notes.topic1.option1':
        'Uguriza ubucuruzi bwagenzuwe ku nyungu idahinduka',
    'investor.academy.lesson.notes.topic1.option2':
        'Ugura imigabane muri Rozine',
    'investor.academy.lesson.notes.topic1.option3':
        'Uhabwa agahimbazamusyi uko byagenda kose',
    'investor.academy.lesson.notes.topic2.title': 'Inyungu n’ubwishyu',
    'investor.academy.lesson.notes.topic2.body':
        'Buri shoramari ryerekana inyungu yaryo yose n’igihe cyaryo. Ubucuruzi bwishyura mu byiciro bingana buri kwezi, kandi umugabane wawe kuri buri cyiciro ushyirwa mu gasanduku kawe, havuyemo amafaranga ya Rozine ku gice cy’inyungu.',
    'investor.academy.lesson.notes.topic2.q': 'Inyungu yawe yishyurwa…',
    'investor.academy.lesson.notes.topic2.option1': 'Icyarimwe nyuma y’umwaka',
    'investor.academy.lesson.notes.topic2.option2':
        'Buri kwezi, mu gihe cyose cy’inyandiko',
    'investor.academy.lesson.notes.topic2.option3': 'Ari uko ubisabye gusa',
    'investor.academy.lesson.wallet.title': 'Agasanduku kawe ka Rozine',
    'investor.academy.lesson.wallet.sub': 'Kubitsa, ubwishyu no kubikuza',
    'investor.academy.lesson.wallet.topic1.title': 'Ahari, afashwe n’ayemejwe',
    'investor.academy.lesson.wallet.topic1.body':
        'Amafaranga ahari ashobora gushorwa cyangwa gukurwamo. Amafaranga afashwe kubera kwishyura, cyangwa yemejwe mu ikusanya winjiyemo, abikwa ku ruhande kugeza ikusanya ryuzuye cyangwa usubijwe amafaranga.',
    'investor.academy.lesson.wallet.topic1.q':
        'Amafaranga yemejwe mu ikusanya…',
    'investor.academy.lesson.wallet.topic1.option1':
        'Ashobora gukurwamo igihe cyose',
    'investor.academy.lesson.wallet.topic1.option2': 'Aba yatakaye burundu',
    'investor.academy.lesson.wallet.topic1.option3':
        'Abikwa ku ruhande kugeza ikusanya ryuzuye cyangwa asubijwe',
    'investor.academy.lesson.wallet.topic2.title': 'Kwishyurwa',
    'investor.academy.lesson.wallet.topic2.body':
        'Ubwishyu bugera mu gasanduku kawe kuri buri munsi wo kwishyura. Ushobora kubushora mu rindi kusanya rifunguye, cyangwa kubukura ukabwohereza kuri konti yagenzuwe iri mu izina ryawe.',
    'investor.academy.lesson.wallet.topic2.q':
        'Iyo ubucuruzi bwishyuye, amafaranga…',
    'investor.academy.lesson.wallet.topic2.option1':
        'Agera mu gasanduku kawe mu buryo bwikora',
    'investor.academy.lesson.wallet.topic2.option2':
        'Agomba gusabwa mu munsi umwe',
    'investor.academy.lesson.wallet.topic2.option3':
        'Ahita ajya mu yindi nyandiko',
    'investor.academy.lesson.rating.title': 'Gusoma amanota ya Rozine',
    'investor.academy.lesson.rating.sub': 'Amanota amwe, ijambo rimwe',
    'investor.academy.lesson.rating.topic1.title': 'Icyo amanota asobanura',
    'investor.academy.lesson.rating.topic1.body':
        'Buri bucuruzi buhabwa amanota kuri 5.0 aherekejwe n’ijambo: Ikomeye (4.0 kugeza 5.0), Ihamye (3.0 kugeza 3.9), Intege nke (2.0 kugeza 2.9) cyangwa Mu bibazo (munsi ya 2.0). Ubucuruzi bugomba kugira nibura 2.0 kugira ngo bukusanye.',
    'investor.academy.lesson.rating.topic1.q':
        'Ubucuruzi bufite amanota 4.4 ni…',
    'investor.academy.lesson.rating.topic1.option1': 'Mu bibazo',
    'investor.academy.lesson.rating.topic1.option2': 'Ikomeye',
    'investor.academy.lesson.rating.topic1.option3': 'Ntibwemerewe gukusanya',
    'investor.academy.lesson.rating.topic2.title': 'Kuyakoresha neza',
    'investor.academy.lesson.rating.topic2.body':
        'Amanota yongera kubarwa kuri buri raporo y’ukwezi ifite umukono. Amanota menshi asobanura ingaruka nke, si ukubura ingaruka, bityo gabanya amafaranga yawe mu nyandiko nyinshi.',
    'investor.academy.lesson.rating.topic2.q': 'Amanota akoreshwa neza nk’…',
    'investor.academy.lesson.rating.topic2.option1': 'Isezerano ry’inyungu',
    'investor.academy.lesson.rating.topic2.option2':
        'Impamvu yo gushyira byose mu nyandiko imwe',
    'investor.academy.lesson.rating.topic2.option3':
        'Umuyobozi, hamwe no kugabanya amafaranga yawe',
    'investor.academy.lesson.mission.title': 'Impamvu Rozine ibaho',
    'investor.academy.lesson.mission.sub': 'Intego yacu n’indangagaciro zacu',
    'investor.academy.lesson.mission.topic1.title': 'Intego',
    'investor.academy.lesson.mission.topic1.body':
        'Rozine ihuza abashoramari basanzwe n’ubucuruzi bwo mu Rwanda bwagenzuwe bukeneye igishoro cyo gukora, kugira ngo ibigo byo mu gihugu bikure kandi abashoramari babone inyungu idahinduka.',
    'investor.academy.lesson.mission.topic1.q': 'Rozine ibaho kugira ngo…',
    'investor.academy.lesson.mission.topic1.option1':
        'Ihuze abashoramari n’ubucuruzi bwo mu gihugu bwagenzuwe',
    'investor.academy.lesson.mission.topic1.option2':
        'Icuruze imigabane yo hanze',
    'investor.academy.lesson.mission.topic1.option3': 'Igurishe ubwishingizi',
    'investor.academy.lesson.mission.topic2.title': 'Gukorera mu mucyo mbere',
    'investor.academy.lesson.mission.topic2.body':
        'Buri shoramari rishingiye ku nyandiko za banki na mobile money zagenzuwe, isura ryigenga aho bukorera na raporo z’ukwezi zifite umukono, kugira ngo ubone icyo ushoramo.',
    'investor.academy.lesson.mission.topic2.q':
        'Rozine ishingira buri shoramari kuri…',
    'investor.academy.lesson.mission.topic2.option1':
        'Amasezerano y’ubucuruzi ubwabwo',
    'investor.academy.lesson.mission.topic2.option2':
        'Inyandiko zagenzuwe na raporo z’ukwezi zifite umukono',
    'investor.academy.lesson.mission.topic2.option3':
        'Isezerano rya Rozine ryo kwishyura',
    'investor.academy.lesson.fundamentals.title': 'Ibanze by’ishoramari',
    'investor.academy.lesson.fundamentals.sub': 'Ingaruka n’inyungu',
    'investor.academy.lesson.fundamentals.topic1.title': 'Ingaruka n’inyungu',
    'investor.academy.lesson.fundamentals.topic1.body':
        'Inyungu ishobora kuba nini akenshi iherekezwa n’ingaruka nyinshi. Ushobora gutakaza igice cyangwa amafaranga yose washoye iyo ubucuruzi butishyuye.',
    'investor.academy.lesson.fundamentals.topic1.q':
        'Inyungu ishobora kuba nini akenshi iherekezwa na…',
    'investor.academy.lesson.fundamentals.topic1.option1': 'Nta ngaruka na nke',
    'investor.academy.lesson.fundamentals.topic1.option2':
        'Isezerano ry’inyungu',
    'investor.academy.lesson.fundamentals.topic1.option3': 'Ingaruka nyinshi',
    'investor.academy.lesson.fundamentals.topic2.title': 'Shora ufite gahunda',
    'investor.academy.lesson.fundamentals.topic2.body':
        'Inyandiko ntishobora kuvunjwamo amafaranga mbere y’igihe cyayo, bityo shora gusa amafaranga utazakenera muri icyo gihe, kandi wiyemeze mbere amafaranga ushaka gushora.',
    'investor.academy.lesson.fundamentals.topic2.q':
        'Mbere yo gushora, ukwiye…',
    'investor.academy.lesson.fundamentals.topic2.option1':
        'Gukoresha gusa amafaranga utazakenera muri icyo gihe',
    'investor.academy.lesson.fundamentals.topic2.option2':
        'Kuguza kugira ngo ushore byinshi',
    'investor.academy.lesson.fundamentals.topic2.option3':
        'Gutegura kuvunja mbere y’igihe',
    'investor.academy.lesson.diversify.title': 'Kugabanya ishoramari',
    'investor.academy.lesson.diversify.sub': 'Gabanya amafaranga yawe',
    'investor.academy.lesson.diversify.topic1.title': 'Kugabanya',
    'investor.academy.lesson.diversify.topic1.body':
        'Kugabanya amafaranga yawe mu bucuruzi bwinshi bituma ubutinze kwishyura bugira ingaruka nto ku mafaranga yawe yose.',
    'investor.academy.lesson.diversify.topic1.q':
        'Kugabanya ishoramari bifasha cyane cyane…',
    'investor.academy.lesson.diversify.topic1.option1':
        'Gutuma buri nyandiko yishyura byinshi',
    'investor.academy.lesson.diversify.topic1.option2':
        'Kugabanya ingaruka z’ubucuruzi bumwe',
    'investor.academy.lesson.diversify.topic1.option3':
        'Gukuraho ingaruka zose',
    'investor.academy.lesson.diversify.topic2.title': 'Vanga inzego n’ibihe',
    'investor.academy.lesson.diversify.topic2.body':
        'Kuvanga inzego, amanota n’ibihe bikurinda guterwa n’ikibazo kimwe. Ntushobora gutunga ibirenze kimwe cya kabiri cy’ikusanya rimwe.',
    'investor.academy.lesson.diversify.topic2.q':
        'Kuvanga inzego n’ibihe bigufasha…',
    'investor.academy.lesson.diversify.topic2.option1':
        'Kudaterwa n’ikibazo kimwe',
    'investor.academy.lesson.diversify.topic2.option2':
        'Kwishyura amafaranga make',
    'investor.academy.lesson.diversify.topic2.option3':
        'Gutunga ibirenze kimwe cya kabiri cy’ikusanya',
    'investor.academy.lesson.deals.title': 'Guhitamo ishoramari',
    'investor.academy.lesson.deals.sub': 'Gusoma urupapuro rw’ishoramari',
    'investor.academy.lesson.deals.topic1.title': 'Ibyo ugomba kureba',
    'investor.academy.lesson.deals.topic1.body':
        'Ku rupapuro rwa buri shoramari, reba amanota, inyungu, igihe, igisigaye kuzuzwa, imibare yagenzuwe na raporo z’ukwezi zifite umukono mbere yo kwiyemeza.',
    'investor.academy.lesson.deals.topic1.q': 'Mbere yo gushora ukwiye kureba…',
    'investor.academy.lesson.deals.topic1.option1': 'Izina ry’ubucuruzi gusa',
    'investor.academy.lesson.deals.topic1.option2':
        'Umubare w’abantu babukunze',
    'investor.academy.lesson.deals.topic1.option3':
        'Amanota, inyungu, igihe n’imibare yagenzuwe',
    'investor.academy.lesson.deals.topic2.title': 'Ayo ushobora gushora',
    'investor.academy.lesson.deals.topic2.body':
        'Inyandiko z’umwenda zigurishwa mu migabane ya RWF 5,000, bityo ushobora gutangirira ku mugabane umwe. Ishoramari ntirishobora kurenga igisigaye kuzuzwa.',
    'investor.academy.lesson.deals.topic2.q':
        'Ishoramari rito kurusha ayandi ni…',
    'investor.academy.lesson.deals.topic2.option1': 'RWF 1,000,000',
    'investor.academy.lesson.deals.topic2.option2':
        'Umugabane umwe wa RWF 5,000',
    'investor.academy.lesson.deals.topic2.option3':
        'Kimwe cya kabiri cy’ikusanya',
    'investor.academy.lesson.repayments.title': 'Ubwishyu n’amafaranga uhabwa',
    'investor.academy.lesson.repayments.sub': 'Uko wishyurwa',
    'investor.academy.lesson.repayments.topic1.title':
        'Wishyurwa mu gihe cyose',
    'investor.academy.lesson.repayments.topic1.body':
        'Mbere yo kwiyemeza, ubona amafaranga uzishyurwa n’igihe ubwishyu buzamara. Icyiciro cya mbere cyishyurwa ukwezi kumwe kuzuye nyuma y’uko amafaranga ahawe ikigo koko, hanyuma buri kwezi ku munsi umwe (cyangwa ku munsi wa nyuma w’ukwezi iyo bibaye ngombwa). Amatariki nyayo y’ubwishyu agenwa kandi akerekanwa iyo inyandiko z’umwenda zitanzwe.',
    'investor.academy.lesson.repayments.topic1.q': 'Ubwishyu bwa Rozine…',
    'investor.academy.lesson.repayments.topic1.option1':
        'Buteganyijwe buri kwezi mu gihe cyose',
    'investor.academy.lesson.repayments.topic1.option2':
        'Bwishyurwa rimwe ku iherezo',
    'investor.academy.lesson.repayments.topic1.option3':
        'Igihe ubucuruzi bubishakiye',
    'investor.academy.lesson.repayments.topic2.title':
        'Ongera ushore cyangwa ubikuze',
    'investor.academy.lesson.repayments.topic2.body':
        'Buri bwishyu ni ubwawe: ushobora kubushora mu rindi kusanya rifunguye cyangwa kububikuza. Nta kintu cyongera gushorwa mu buryo bwikora.',
    'investor.academy.lesson.repayments.topic2.q':
        'Iyo uhawe ubwishyu ushobora…',
    'investor.academy.lesson.repayments.topic2.option1':
        'Kubusiga mu nyandiko gusa',
    'investor.academy.lesson.repayments.topic2.option2':
        'Kubukoresha ku mafaranga y’serivisi gusa',
    'investor.academy.lesson.repayments.topic2.option3':
        'Kongera kubushora cyangwa kububikuza',

    'investor.help.group.start': 'Gutangira',
    'investor.help.group.returns': "Inyungu n'ubwishyu",
    'investor.help.group.risk': "Ingaruka n'uko ubucuruzi bugenzurwa",
    'investor.help.group.portfolio': 'Kubaka ishoramari ryawe',
    'investor.help.group.secondary': 'Isoko rya kabiri',
    'investor.help.group.wallet': "Agasanduku, kubitsa n'amafaranga y'serivisi",
    'investor.help.group.safety': "Umutekano w'amafaranga yawe",
    'investor.help.group.account': "Konti n'ubufasha",
    'investor.help.start.what.q':
        'Rozine ni iki kandi gushora imari bikora gute?',
    'investor.help.start.what.a':
        "Rozine igufasha kuguriza ubucuruzi bwo mu Rwanda bwagenzuwe binyuze mu nyandiko z'umwenda (notes). Ugura imigabane mu ikusanya ry'ubucuruzi; iyo ikusanya ryuzuye, ubucuruzi bwishyura mu byiciro bingana buri kwezi, kandi umugabane wawe w'igishoro n'inyungu ushyirwa mu gasanduku kawe ka Rozine.\n\nRozine ikorera mu igerageza ry'amabwiriza (sandbox) ry'Ikigo gishinzwe isoko ry'imari n'imigabane, bityo hari imipaka ikurikizwa igihe cyose rimara.",
    'investor.help.start.first.q': 'Nashora imari bwa mbere nte?',
    'investor.help.start.first.a':
        "Banza wemeze umwirondoro wawe, hanyuma ushyire amafaranga mu gasanduku kawe uyakuye kuri konti ya mobile money cyangwa ya banki yanditse mu izina ryawe. Fungura ishoramari kuri Isoko urebe amanota yaryo, inyungu, igihe, ingengabihe y'ubwishyu n'imibare y'ubucuruzi yagenzuwe, uhitemo umubare w'imigabane, hanyuma wemeze.\n\nMbere yo kwemeza, ubona amafaranga, buri giciro cya serivisi mu RWF n'ibyo uzabona neza.",
    'investor.help.start.minimum.q':
        'Hari amafaranga make ntarengwa yo gushora?',
    'investor.help.start.minimum.a':
        "Inyandiko z'umwenda zigurishwa mu migabane ya RWF 5,000, bityo ushobora gutangirira ku mugabane umwe.\n\nNtushobora gutunga ibirenze kimwe cya kabiri cy'ikusanya rimwe, kandi ishoramari ntirishobora kurenga igisigaye kuzuzwa.",
    'investor.help.returns.when.q':
        'Inyungu zanjye zishyurwa gute kandi ryari?',
    'investor.help.returns.when.a':
        "Icyiciro cya mbere kigomba kwishyurwa ukwezi kumwe nyuma yo gutanga amafaranga, ibindi bikishyurwa ku munsi nk'uwo w'ukwezi gukurikiyeho. Kuri buri tariki y'ubwishyu, ubwishyu bw'ubucuruzi bugabanywa abafite imigabane hakurikijwe umubare w'imigabane bafite, bugashyirwa mu dusanduku twabo.\n\nIngengabihe yose igaragazwa mbere y'uko wiyemeza, kandi ntihinduka nyuma y'uko inyandiko itangwa.",
    'investor.help.returns.yield.q': 'Nakwitega inyungu ingana iki?',
    'investor.help.returns.yield.a':
        "Buri nyandiko ifite inyungu yose idahinduka iri hagati ya 10% na 15% by'igishoro mu gihe cyayo cyose cy'amezi 3 kugeza kuri 6, igenwa igihe ikusanya rifunguwe.\n\nAmafaranga ya Rozine ku nyungu akurwa ku gice cy'inyungu cya buri bwishyu, ntakurwa ku gishoro cyawe. Igipimo cyayo giterwa n'igishoro cyawe gikora kandi kigumaho kuri buri shoramari.",
    'investor.help.returns.guaranteed.q':
        'Inyungu zanjye zirizewe ijana ku ijana?',
    'investor.help.returns.guaranteed.a':
        "Oya. Inyungu igaragazwa ni iyo ubucuruzi bwiyemeje kwishyura, si icyizere ntakuka. Ushobora gutakaza igice cyangwa amafaranga yose washoye niba ubucuruzi butishyuye.\n\nGukwirakwiza ishoramari ku nyandiko nyinshi bigabanya ingaruka z'ubucuruzi bumwe.",
    'investor.help.risk.protected.q': 'Igishoro cyanjye kirarinzwe?',
    'investor.help.risk.protected.a':
        "Oya. Igishoro cyawe gishobora guhura n'ingaruka. Buri bucuruzi bugenzurwa hakoreshejwe inyandiko z'imari zabwo kandi busurwa n'umucungamari wigenga ufite uruhushya mbere yo gukusanya, na buri kwezi igihe inyandiko yabwo ikiriho. Ibyo bigabanya ingaruka ariko ntibizikuraho.\n\nInyandiko ntishobora kuvunjwamo amafaranga mbere y'igihe cyayo, bityo shora gusa amafaranga utazakenera muri icyo gihe.",
    'investor.help.risk.vetting.q':
        'Ubucuruzi bugenzurwa bute mbere yo gukusanya?',
    'investor.help.risk.vetting.a':
        "Ubucuruzi bugomba kugira amafaranga yinjiye ku mwaka yagenzuwe angana nibura na RWF 30,000,000 n'inyandiko za banki na mobile money z'amezi 36 (12 ku bucuruzi bwongera gukusanya bwishyuye ku gihe). Rozine igena ingano ya buri nyandiko ku buryo ubucuruzi bwinjiza nibura inshuro 1.5 z'icyiciro cyabwo cya buri kwezi.\n\nNta nyandiko ishyirwa ku isoko mbere y'uko Umufatanyabikorwa w'igenzura asura aho ubucuruzi bukorera agashyira umukono kuri raporo y'ibanze.",
    'investor.help.risk.rating.q': 'Amanota ya Rozine ni iki?',
    'investor.help.risk.rating.a':
        "Buri bucuruzi buhabwa amanota kuri 5.0, afite umubare umwe nyuma y'akadomo, aherekejwe n'ijambo: Ikomeye (4.0 kugeza 5.0), Ihamye (3.0 kugeza 3.9), Intege nke (2.0 kugeza 2.9) cyangwa Mu bibazo (munsi ya 2.0). Ubucuruzi bugomba kugira nibura 2.0 kugira ngo bukusanye.\n\nAmanota yongera kubarwa kuri buri raporo y'ukwezi ifite umukono. Amanota mashya ntahindura amasezerano y'inyandiko yamaze gutangwa.",
    'investor.help.risk.late.q':
        'Bigenda bite iyo ubucuruzi butinze kwishyura cyangwa bunaniwe kwishyura?',
    'investor.help.risk.late.a':
        "Ku itariki y'ubwishyu, ibiri mu gasanduku k'ubucuruzi byose birafatwa bikishyurwa, kandi ibigeramo nyuma bifatwa ako kanya. Ibihano by'ubukererwe bya 5% by'amafaranga atishyuwe ku itariki y'ubwishyu, ku munsi wa 7 no ku munsi wa 30 byose bihabwa abafite imigabane. Rozine nta na kimwe ifataho.\n\nIyo ibirarane bitishyuwe ku munsi wa 31, inyandiko ifatwa nk'iyananiwe kwishyurwa. Kugaruza birakorwa ariko bishobora kutagarura amafaranga yose.",
    'investor.help.portfolio.diversify.q': 'Nakwirakwiza ishoramari nte?',
    'investor.help.portfolio.diversify.a':
        "Gabanya amafaranga yawe mu bucuruzi butandukanye, mu nzego zitandukanye n'ibihe bitandukanye aho kuyashyira mu nyandiko imwe. Iyo ubucuruzi bumwe butinze kwishyura, ubundi bugabanya ingaruka.\n\nAbashoramari benshi bashyiraho umupaka kuri buri nyandiko kandi bakongera ubwoko butandukanye uko igihe kigenda.",
    'investor.help.portfolio.track.q': 'Nakurikirana ishoramari ryanjye nte?',
    'investor.help.portfolio.track.a':
        "Ishoramari ryawe rigaragaza buri mugabane ufite n'igishoro cyawo, ibyamaze kwishyurwa n'ibigisigaye. Raporo y'ukwezi ifite umukono ya buri bucuruzi igaragara kuri uwo mugabane.\n\nBuri bwishyu bugaragara mu mateka y'agasanduku kawe, kandi Umwirondoro > Inyandiko z'imari n'imisoro birimo inyandiko zawe.",
    'investor.help.portfolio.reinvest.q':
        'Nashobora kongera gushora ubwishyu nabonye?',
    'investor.help.portfolio.reinvest.a':
        'Yego. Ubwishyu bugera mu gasanduku kawe, kandi ushobora gushora ayo mafaranga mu yandi makusanya afunguye igihe ubishakiye.\n\nNta kintu cyongera gushorwa mu buryo bwikora.',
    'investor.help.secondary.exit.q':
        "Nashobora kuva mu ishoramari mbere y'igihe?",
    'investor.help.secondary.exit.a':
        "Isoko rya kabiri ntirirafungura, bityo ubu ugumana buri nyandiko kugeza ku cyiciro cyayo cya nyuma.\n\nNiritangira, uzashobora gushyira ku isoko imigabane y'inyandiko iri ku gihe kugira ngo undi mushoramari ayigure. Inyandiko zatinze, zananiwe kwishyurwa cyangwa ziri mu mpaka ntizishobora gucuruzwa.",
    'investor.help.secondary.price.q':
        'Igiciro cyo kongera kugurisha kizagenwa gute?',
    'investor.help.secondary.price.a':
        "Uzahitamo umubare w'imigabane n'igiciro cya buri mugabane mu mafaranga yuzuye. Igiciro ntigishobora kurenga ubwishyu iyo migabane igisigaje kubona, ariko gishobora kuba gito kugira ngo igurishwe vuba.\n\nItangazo rimara iminsi 7 kandi rishobora kongera gushyirwaho nta kiguzi. Ugurisha yishyura 2% by'igiciro cyo kugurisha; ugura nta cyo yishyura.",
    'investor.help.secondary.guaranteed.q': 'Kugurisha bizaba byizewe?',
    'investor.help.secondary.guaranteed.a':
        'Oya. Kugurisha bisaba ko undi mushoramari agura ku giciro cyawe, bityo itangazo rishobora kugurishwa igice gusa, cyangwa ntirigurishwe na gato.\n\nImigabane itagurishijwe igumana nawe kandi ikomeza kubona ubwishyu.',
    'investor.help.wallet.move.q': 'Nshyira cyangwa nkura amafaranga nte?',
    'investor.help.wallet.move.a':
        'Amafaranga abitswa anyuze kuri mobile money cyangwa banki aturutse kuri konti yagenzuwe iri mu izina ryawe. Amafaranga akurwamo yoherezwa gusa kuri konti yawe yagenzuwe, hagati ya RWF 1,000 na RWF 2,000,000 buri gihe.\n\nCunga konti zawe muri Umwirondoro > Konti zahujwe.',
    'investor.help.wallet.fees.q':
        'Ni ayahe mafaranga ya serivisi Rozine yishyuza abashoramari?',
    'investor.help.wallet.fees.a':
        "Amafaranga ya Rozine ku nyungu akurwa ku gice cy'inyungu cya buri bwishyu: 15% munsi ya RWF 1M y'igishoro gikora, 12% kuva kuri 1M kugeza kuri 5M, 10% kuva kuri 5M kugeza kuri 25M, 9% kuva kuri 25M kugeza kuri 100M na 8% hejuru yaho. Ntakurwa na rimwe ku gishoro.\n\nKubikuza nta mafaranga ya Rozine bisaba; ikiguzi cy'utanga serivisi kigaragazwa mbere yo kwemeza.",
    'investor.help.wallet.speed.q': 'Kubikuza bifata igihe kingana iki?',
    'investor.help.wallet.speed.a':
        "Iyo umaze kwemeza, amafaranga yoherezwa kuri konti yawe binyuze ku utanga serivisi yo kwishyura. Ubusabe budasanzwe bushobora gufatwa bugasuzumwa mu gihe kigera ku masaha 24.\n\nUshobora gukurikirana buri bubikuza mu mateka y'agasanduku kawe. Amafaranga wiyemeje mu ikusanya rifunguye ntashobora kubikuzwa.",
    'investor.help.safety.held.q': 'Amafaranga yanjye abikwa he?',
    'investor.help.safety.held.a':
        "Amafaranga yose y'abakiriya abikwa kuri konti y'abakiriya yihariye muri banki y'u Rwanda ifite uruhushya, atandukanye n'amafaranga ya Rozine. Igiteranyo cy'udusanduku twose gihuzwa n'iyo konti buri munsi.\n\nAmafaranga ya serivisi yinjijwe kandi yatangajwe gusa ni yo ashobora kujya kuri konti ya Rozine.",
    'investor.help.safety.stop.q': 'Bigenda bite Rozine iramutse ihagaze?',
    'investor.help.safety.stop.a':
        'Ibikorwa bishya bihagarara uwo munsi, buri mafaranga yiyemejwe mu ikusanya ritujujwe asubizwa yose, kandi buri nyandiko yatanzwe ikomeza kwishyurwa kugeza ku gihe cyayo hifashishijwe ikigega cyabigenewe.\n\nKubikuza amafaranga ari mu dusanduku ni ubuntu muri icyo gihe.',
    'investor.help.account.secure.q': 'Konti yanjye irindwa gute?',
    'investor.help.account.secure.a':
        "Ongeraho porogaramu yo kwemeza muri Umwirondoro > Umutekano wa konti kugira ngo usabwe kode buri gihe winjira, kandi ufungure imenyesha ryo kwinjira kugira ngo umenyeshwe igikoresho gishya.\n\nRozine ntizigera igusaba ijambo ry'ibanga cyangwa kode kuri telefoni, SMS cyangwa imeri.",
    'investor.help.account.complain.q': 'Natanga ikirego nte?',
    'investor.help.account.complain.a':
        "Twandikire uhereye muri iki gice cy'ubufasha. Ikirego cyawe gihabwa nimero yacyo; twemeza ko twakiriye mu masaha 4 kandi tugaharanira kugikemura mu masaha 48.\n\nUshobora kugeza ikirego ku Kigo gishinzwe isoko ry'imari n'imigabane igihe icyo ari cyo cyose.",
    'investor.profile.pending.terms_title': 'Biraza vuba muri porogaramu',
    'investor.profile.pending.terms_body':
        "Amabwiriza n'Amategeko bizasomerwa hano nibimara gutangazwa.",
    'investor.profile.pending.privacy_title': 'Biraza vuba muri porogaramu',
    'investor.profile.pending.privacy_body':
        'Itangazo ku Ibanga rizasomerwa hano nirimara gutangazwa.',
    'investor.profile.sign_out': 'Sohoka',
    'investor.profile.linked.unlink': 'Kuraho',
    'investor.profile.linked.unverified': 'Biragenzurwa',
    'investor.profile.linked.none':
        'Nta konti yo kwishyurirwaho. Huza imwe ushyireho amafaranga kandi wakire ubwishyu.',
    'investor.profile.linked.empty': 'Nta konti yo kwishyurirwaho irahuzwa.',
    'investor.profile.linked.new': 'Huza konti nshya',
    'investor.profile.linked.add': '+ Huza konti nshya',
    'investor.profile.linked.type': 'UBWOKO',
    'investor.profile.linked.type_mobile': 'Mobile money',
    'investor.profile.linked.type_bank': 'Konti ya banki',
    'investor.profile.linked.network': 'UMUYOBORO',
    'investor.profile.linked.mtn': 'MTN MoMo',
    'investor.profile.linked.airtel': 'Airtel Money',
    'investor.profile.linked.bank': 'BANKI',
    'investor.profile.linked.mobile_number': 'NIMERO YA TELEFONI',
    'investor.profile.linked.account_number': 'NIMERO YA KONTI',
    'investor.profile.linked.mobile_placeholder': '07X XXX XXXX',
    'investor.profile.linked.account_placeholder': 'Nimero ya konti',
    'investor.profile.linked.submit': 'Huza konti',
    'investor.profile.linked.footnote':
        'Rozine ikora igikorwa kimwe cyo kugenzura. Ubwishyu bugera gusa kuri konti iri mu izina ryawe.',
    'investor.profile.automation.gated_title': 'Auto-Deploy ntiraboneka',
    'investor.profile.automation.gated_body':
        "Ikeneye kwemezwa ku bijyanye n'igicuruzwa, amafaranga n'amategeko mbere y'uko Rozine ishora imari mu izina ryawe.",
    'investor.profile.automation.intro':
        'Auto-Deploy yakwemerera Rozine kugufatira impapuro mu bikorwa bishya byo gukusanya imari, ukurikije amategeko ushyizeho. Kugeza yemejwe, buri shoramari urihitamo ukaryemeza ubwawe.',
    'investor.profile.automation.nothing_runs':
        'Nta kintu cya Auto-Deploy cyashyizweho, gikora cyangwa cyishyuzwa kuri konti yawe.',
    'investor.profile.automation.next': 'Hitamo umushinga ubwawe',
    'investor.profile.statements.intro':
        "Kuramo inyandiko z'ishoramari n'incamake z'imisoro. Buri imwe ikorwa hashingiwe ku mateka yemejwe y'ibikorwa byawe.",
    'investor.profile.statements.year': "UMWAKA W'IMISORO {year}",
    'investor.profile.statements.annual': "Incamake y'inyungu y'umwaka",
    'investor.profile.statements.annual_meta':
        'Inyungu yose yabonetse · ibiguzi byishyuwe',
    'investor.profile.statements.incomplete':
        'Umwaka ntirurangira · imibare iracyahinduka',
    'investor.profile.statements.download_annual': "Kuramo incamake y'umwaka",
    'investor.profile.statements.monthly': 'INYANDIKO ZA BURI KWEZI',
    'investor.profile.statements.pdf': 'Inyandiko ya PDF',
    'investor.profile.statements.month_open':
        '{month} ntirurangira · imibare ishobora guhinduka',
    'investor.profile.statements.none':
        "Nta nyandiko iraboneka. Iya mbere izaza nyuma y'ukwezi kwa mbere kwuzuye.",
    'investor.profile.statements.disclaimer':
        'Inyandiko zitangwa kugira ngo uzibike. Rozine ntitanga inama ku misoro — baza umujyanama wabigize umwuga.',

    'business.apply.business.unavailable': 'Ntibiboneka',
    'business.apply.business.ineligible': 'Ntiremerewe gusaba igishoro',
    'business.apply.raise.resized':
        'Wasabye {requested} · iki ni cyo cyifuzo wemera iyo usinye',
    'business.apply.raise.schedule': 'Gahunda yo kwishyura',
    'business.apply.raise.instalment': 'Icyiciro cya {n}',
    'business.apply.raise.instalment_final': 'Icyiciro cya {n} · cya nyuma',
    'business.apply.review.your_offer': 'Icyifuzo cyawe',
    'business.apply.review.flat_rate': '{rate}% ntihinduka',
    'business.apply.review.accept_offer':
        'Nemeye iki cyifuzo: {principal} mu mezi {months}, byishyurwa hakurikijwe gahunda iri haruguru.',
    'business.apply.review.no_offer':
        'Nta cyifuzo cyo kwemera kiraboneka. Subira ku gishoro cyawe ubone igiciro.',
    'business.apply.review.signatories': 'Abasinya',
    'business.apply.review.signatures_required':
        "Imikono isabwa n'ububasha bw'ikigo: {count}",
    'business.apply.review.signed_on': 'Yasinye · {date}',
    'business.apply.review.signer.signed': 'Yasinye',
    'business.apply.review.signer.pending': 'Birategerejwe',
    'business.apply.review.attestation':
        'Kwandika izina ryawe byemeza ko ari wowe usinya. Konti yawe yagenzuwe, si iri zina, ni yo isinya.',
    'business.apply.review.waiting':
        'Hategerejwe ko {names} asinya. Ubusabe bwoherezwa iyo imikono yose isabwa yabonetse.',
    'business.apply.review.cannot_sign':
        "Umusinyi uri ku bubasha bw'ikigo bwagenzuwe ni we wenyine ushobora gusinya ubu busabe.",
    'business.apply.review.agreement_unavailable': 'Amasezerano ntararaboneka.',
    'business.apply.review.agreement_unavailable_body':
        "Rozine ntiratangaza amategeko n'imenyesha ry'ingaruka byemejwe kuri ubu busabe, bityo nta kintu cyo gusinya kiraboneka. Umushinga wawe n'icyifuzo cyawe biguma bibitswe.",
    'business.apply.view_only':
        'Ushobora kureba ubu busabe, ariko ntushobora kubuhindura.',
    'business.apply.submitted.application_id': "Nimero y'ubusabe · {id}",
    'business.apply.outcome.checking.title': 'Turimo kureba ibyabaye',
    'business.apply.outcome.checking.body':
        "Umuyoboro wacitse mbere y'uko seriveri isubiza. Turimo kubaza niba icyifuzo cyawe cyakiriwe.",
    'business.apply.outcome.unconfirmed.title': 'Ntiturabasha kubyemeza',
    'business.apply.outcome.unconfirmed.body':
        'Nta kintu kizongera koherezwa kugeza seriveri yemeje uko icyifuzo cyawe cyagenze.',
    'business.apply.outcome.pending.title': 'Biracyakorwaho',
    'business.apply.outcome.pending.body':
        'Seriveri yakiriye icyifuzo cyawe kandi iracyagikoraho.',
    'business.apply.outcome.not_recorded.title': 'Nta gisubizo cyanditswe',
    'business.apply.outcome.not_recorded.body':
        'Seriveri nta gisubizo iragira ku cyifuzo cyawe. Ushobora kongera kohereza icyifuzo kimwe neza.',
    'business.apply.outcome.check_again': 'Ongera urebe',
    'business.apply.outcome.try_again': 'Ongera ugerageze',
    'business.apply.outcome.refused.VERSION_CONFLICT':
        'Ubu busabe bwahindutse kuva wabufungura. Twazanye verisiyo iheruka — yisuzume wongere ugerageze.',
    'business.apply.outcome.refused.IDEMPOTENCY_CONFLICT':
        "Iki cyifuzo cyakoreshejwe mbere n'andi makuru, bityo ntikongeye koherezwa. Twazanye verisiyo iheruka.",
    'business.apply.outcome.refused.QUOTE_STALE':
        "Igiciro cyawe cyahindutse mbere y'uko usinya. Suzuma icyifuzo gishya wongere ucyemere.",
    'business.apply.outcome.refused.DOCUMENT_VERSION_STALE':
        "Inyandiko cyangwa itangazo byahindutse mbere y'uko usinya. Soma verisiyo nshya wongere uyemere.",
    'business.apply.outcome.refused.ACTION_FORBIDDEN':
        'Ntushobora gukora iki gikorwa kuri ubu bucuruzi.',
    'business.apply.outcome.refused.MANDATE_REQUIRED':
        'Ububasha bwawe bwagenzuwe ntibukwemerera gusinyira ubu bucuruzi.',
    'business.apply.outcome.refused.NOT_FOUND': 'Ubu busabe ntibukikugeraho.',
    'business.apply.outcome.refused.denied':
        'Uburenganzira bwawe bwahindutse. Subira kuri porogaramu zawe wongere ugerageze.',
    'business.apply.outcome.refused.failed':
        'Seriveri ntiyabashije kubirangiza. Twazanye verisiyo iheruka.',
    'business.publish.unavailable':
        'Gutangaza bizafunguka ubusabe bwawe bumaze kwemezwa no gusinywa byuzuye, kandi uburyo bwo gushyira ku isoko bwiteguye.',

    'business.apply.outcome.refused.MANDATE_STALE':
        "Ububasha bwo gusinya bw'ikigo bwahindutse mbere y'uko usinya. Reba abagomba gusinya ubu, hanyuma wongere usinye.",

    'business.apply.review.document_summary': 'Incamake',
    'business.apply.outcome.refused.APPLICATION_PENDING_REVIEW':
        'Ubucuruzi bwawe bufite ubundi busabe burimo gusuzumwa. Uzashobora kongera gusaba bumaze gufatirwa icyemezo.',
    'business.apply.outcome.refused.APPLICATION_STEP_INVALID':
        'Subira kuri «Suzuma usinye» kugira ngo wohereze ubu busabe.',
    'business.apply.pending_review.link': 'Reba ubusabe burimo gusuzumwa',
    'business.apply.review.document_full_text': 'Inyandiko yuzuye',
    'business.apply.review.reduce.open': 'Fata amafaranga make',
    'business.apply.review.reduce.label': 'Amafaranga ushaka (RWF)',
    'business.apply.review.reduce.help':
        'Kugeza ku yo wahawe, mu mpapuro zuzuye za {unit}. Turongera kubara icyifuzo kuri aya mafaranga, ukongera kucyemera.',
    'business.apply.review.reduce.submit': 'Ongera ubare',
    'business.apply.review.reduce.cancel': 'Guma ku cyifuzo',
    'business.apply.review.reduced':
        'Wahisemo {principal} kuri {offered} wahawe.',
    'business.apply.review.use_full': 'Fata ayo wahawe yose',

    'business.apply.recalculating': 'Turimo kubara…',
    'auditor.capture.unavailable':
        'Porogaramu yo gufata amakuru ntiraboneka kuri uyu murimo, bityo amafoto no kwiyandikisha ku kibanza ntibishobora gufatwa. Nta buryo bwo kubifatira ku rubuga.',
    'auditor.photos.add_unavailable': 'Gufata ntibiboneka',
    'auditor.checkin.position_unavailable': 'Aho uri ntibiboneka',
    'auditor.ledger.ingested': 'Byakiriwe · ntibirasuzumwa',
    'auditor.jobs.map_approximate': 'Aho biri hagereranyijwe',
    'auditor.reason.label': 'Impamvu',
    'auditor.reason.explanation_required':
        'Ibisobanuro bishingiye ku byabaye (ni ngombwa)',
    'auditor.reason.explanation_optional': 'Ibisobanuro (si ngombwa)',
    'auditor.outcome.conflict.blocking.reassignment_pending':
        "Inyungu wagaragaje yanditswe. Akazi kuri uyu murimo karahagaze mu gihe Ishami ry'Igenzura ritegura kuwuha undi.",
    'auditor.outcome.conflict.blocking.reassigned':
        'Inyungu wagaragaje yanditswe kandi {business} yahawe undi. Akazi kawe kuri uyu murimo karahagaze.',
    'auditor.outcome.conflict.blocking.recorded':
        'Inyungu wagaragaje yanditswe. Akazi kuri uyu murimo karahagaze.',
    'auditor.outcome.conflict.blocking.closed':
        "Inyungu wagaragaje yanditswe. Ishami ry'Igenzura ryafunze uyu murimo, kandi akazi kawe kuri wo karahagaze.",
    'auditor.seal.cites': 'Ibimenyetso: {ids}',
    'auditor.seal.evidence': "Ibimenyetso bishyirwaho kashe hamwe n'iyi raporo",
    'auditor.seal.versions':
        'Uburyo {procedure} · ibyabonetse {findings} · ibimenyetso {evidence}',
    'auditor.seal.code_title': 'Emeza ko ari wowe',
    'auditor.seal.code_lead':
        "Andika kode y'imibare itandatu iri muri porogaramu yawe y'umutekano kugira ngo ushyireho kashe ku ruhushya {licence}.",
    'auditor.seal.code_label': "Kode y'umutekano y'imibare itandatu",
    'auditor.seal.code_scope':
        'Iyi kode yemeza gusa ko ari wowe ushyiraho kashe. Nta cyo ivuga kuri telefoni cyangwa ibikoresho byakoreshejwe mu gufata ibimenyetso.',
    'auditor.seal.code_wrong':
        "Iyo kode ntihuye. Andika kode iriho ubu muri porogaramu yawe y'umutekano.",
    'auditor.seal.code_expired':
        "Iyemeza ryawe ryarangiye mbere y'uko kashe ishyirwaho. Andika kode nshya.",
    'auditor.seal.code_throttled':
        'Wagerageje inshuro nyinshi. Uzashobora kwandika kode nshya mu {wait}.',
    'auditor.seal.code_throttled_later':
        'Wagerageje inshuro nyinshi. Tegereza gato, hanyuma wandike kode nshya.',
    'auditor.seal.code_unreachable':
        'Ntibyashobotse kugera kuri Rozine ngo igenzure kode yawe. Nta cyashyizweho kashe — andika kode nshya wongere ugerageze.',
    'auditor.seal.sealing': 'Birashyirwaho kashe…',
    'auditor.seal.mfa_title':
        'Fungura uburyo bwo kwemeza kabiri kugira ngo ushyireho kashe',
    'auditor.seal.mfa_body':
        "Gushyiraho kashe bisaba kode ya porogaramu y'umutekano wemeje kuri konti yawe. Yishyireho mu igenamiterere ry'umutekano, hanyuma ugaruke ushyireho kashe.",
    'auditor.seal.mfa_settings': "Fungura igenamiterere ry'umutekano",
    'auditor.sealed.report_id': 'Raporo',
    'auditor.sealed.signature_ref': 'Umukono',
    'auditor.sealed.key_id': 'Urufunguzo',
    'auditor.sealed.amended_by':
        'Raporo {report} ikosora iyi; iyi raporo iguma uko yashyizweho kashe.',
    'auditor.sealed.open_amendment': 'Fungura ikosora',
    'auditor.sealed.verify': 'Genzura kashe',
    'auditor.audit.amends':
        'Iyi ni ikosora rifitanye isano na raporo {report}. Iyo raporo iguma idahindutse.',
    'auditor.audit.open_original': "Fungura iy'umwimerere",
    'auditor.receipt.title': 'Inyungu yanditswe',
    'auditor.receipt.body.reassignment_pending':
        "Inyungu wagaragaje yanditswe. Akazi kuri uyu murimo karahagaze mu gihe Ishami ry'Igenzura ritegura kuwuha undi.",
    'auditor.receipt.body.reassigned':
        'Inyungu wagaragaje yanditswe kandi umurimo wahawe undi. Ntukibasha kugera kuri dosiye yawo.',
    'auditor.receipt.body.recorded':
        'Inyungu wagaragaje yanditswe. Akazi kuri uyu murimo karahagaze, kandi ntukibasha kugera kuri dosiye yawo.',
    'auditor.receipt.body.closed':
        "Inyungu wagaragaje yanditswe. Ishami ry'Igenzura ryafunze uyu murimo, kandi ntukibasha kugera kuri dosiye yawo.",
    'auditor.receipt.status': 'Umurimo',
    'auditor.receipt.state.reassignment_pending': 'Gutegereza guhabwa undi',
    'auditor.receipt.state.reassigned': 'Wahawe undi',
    'auditor.receipt.state.recorded': 'Byanditswe',
    'auditor.receipt.state.closed': "Byafunzwe n'Ishami ry'Igenzura",
    'auditor.receipt.kind': "Ubwoko bw'inyungu",
    'auditor.receipt.declared': 'Byatangajwe',
    'auditor.receipt.note': 'Ibisobanuro byawe',
    'auditor.evidence.title': 'Ibimenyetso',
    'auditor.evidence.captured': 'Byafashwe',
    'auditor.evidence.source': 'Inkomoko',
    'auditor.evidence.attestation': 'Icyemezo cya telefoni',
    'auditor.evidence.position': 'Aho byafatiwe',
    'auditor.evidence.accuracy': "Ubunyangamugayo bw'aho",
    'auditor.evidence.metres': '±{metres} m',
    'auditor.evidence.unavailable': 'Ntibiboneka',
    'auditor.evidence.digest': 'SHA-256 {digest}…',
    'auditor.evidence.source_companion_device': 'Porogaramu yo gufata',
    'auditor.evidence.source_web_upload': 'Byoherejwe ku rubuga',
    'auditor.evidence.attestation_verified': 'Byemejwe',
    'auditor.evidence.attestation_unverified': 'Ntibyemejwe',
    'auditor.evidence.attestation_unavailable': 'Ntibiboneka',
    'auditor.evidence.kind.photo': "Ifoto y'ikibanza",
    'auditor.evidence.kind.check_in': 'Kwiyandikisha ku kibanza',
    'auditor.evidence.kind.ledger': "Igitabo cy'ibaruramari",
    'auditor.evidence.kind.statement': "Inyandiko y'imari",
    'auditor.evidence.kind.licence_certificate': "Icyemezo cy'uruhushya",
    'auditor.command.checking.title': 'Turareba uko byagenze',
    'auditor.command.checking.body':
        "Igisubizo cy'igikorwa cyawe giheruka cyabuze, bityo Rozine irareba niba cyanditswe. Nta kintu cyongera koherezwa muri icyo gihe.",
    'auditor.command.unconfirmed.title':
        'Ntitwashoboye kwemeza igikorwa cyawe giheruka',
    'auditor.command.unconfirmed.body':
        'Ntibyashobotse kugera kuri Rozine ngo irebe niba cyanditswe. Ongera urebe mbere yo gukora ikindi — ntikizoherezwa kabiri.',
    'auditor.command.pending.title': 'Byanditswe — biracyakorwa',
    'auditor.command.pending.body':
        'Rozine yakiriye igikorwa cyawe giheruka kandi iracyagikoraho. Ongera urebe mu kanya.',
    'auditor.command.not_recorded.title': 'Ntibyanditswe',
    'auditor.command.not_recorded.body':
        "Rozine nta nyandiko ifite y'igikorwa cyawe giheruka. Ushobora kongera kohereza ubusabe bumwe; ntibushobora gukorwa kabiri.",
    'auditor.command.check_again': 'Ongera urebe',
    'auditor.command.try_again': 'Ongera ugerageze',
    'auditor.command.refused.VERSION_CONFLICT':
        'Iyi nyandiko yahindutse kuva uyifunguye. Paji yavuguruwe — yirebe wongere ugerageze.',
    'auditor.command.refused.IDEMPOTENCY_CONFLICT':
        'Ubu busabe bwakoreshejwe ku kindi gikorwa. Ongera utangire uhereye kuri paji yavuguruwe.',
    'auditor.command.refused.DIGEST_STALE':
        'Ibimenyetso byahindutse nyuma yo kureba incamake. Reba incamake nshya wemeze ukoresheje kode nshya.',
    'auditor.command.refused.EVIDENCE_VERSION_STALE':
        'Ibimenyetso byahindutse nyuma yo kureba incamake. Reba incamake nshya wongere wemeze.',
    'auditor.command.refused.FINDINGS_VERSION_STALE':
        'Ibyabonetse byahindutse nyuma yo kureba incamake. Reba incamake nshya wongere wemeze.',
    'auditor.command.refused.PROCEDURE_VERSION_STALE':
        "Verisiyo y'uburyo yahindutse. Reba incamake nshya wongere wemeze.",
    'auditor.command.refused.MANDATE_STALE':
        "Amabwiriza y'umurimo wawe yahindutse. Reba paji yavuguruwe wongere wemeze.",
    'auditor.command.refused.ACTION_FORBIDDEN':
        'Ntukibasha gukora ibi kuri uyu murimo. Uburenganzira bwawe bwahindutse.',
    'auditor.command.refused.NOT_FOUND': 'Iyi nyandiko ntuyemerewe.',
    'auditor.command.refused.STEP_UP_INVALID':
        "Iryo yemeza ntiryakunze. Andika kode nshya yo muri porogaramu yawe y'umutekano.",
    'auditor.command.refused.STEP_UP_EXPIRED':
        "Iryo yemeza ryarangiye mbere yo gushyiraho kashe. Andika kode nshya yo muri porogaramu yawe y'umutekano.",
    'auditor.command.refused.denied':
        'Uburenganzira bwawe bwahindutse, bityo ntibyakozwe.',
    'auditor.command.refused.failed':
        'Ntibyashobotse. Vugurura paji wongere ugerageze.',
    'auditor.accreditation.badge.none': 'Nta cyemezo',
    'auditor.accreditation.badge.first_pending': 'Birasuzumwa',
    'auditor.accreditation.none_line': 'Nta ruhushya rwanditswe',
    'auditor.accreditation.none_body':
        "Ohereza uruhushya rwawe rwa ICPAR ngo rusuzumwe. Kuruhereza ntibiguha uburenganzira: uzahabwa akazi ari uko umukozi wa Rozine ubifitiye uburenganzira yanditse igenzura rya ICPAR n'amatariki yaryo.",
    'auditor.accreditation.first_pending_title':
        'Icyemezo cya mbere kirasuzumwa',
    'auditor.accreditation.evidence_line': 'Icyemezo {id} · SHA-256 {digest}…',
    'auditor.accreditation.expiry_label_first':
        'Itariki uruhushya rurangiriraho',
    'auditor.accreditation.first_submit': 'Ohereza icyemezo cyawe',

    'auditor.availability.locked':
        'Ntushobora guhindura uko uboneka hano muri iki gihe.',
    'auditor.home.unavailable': 'Ntibiboneka',

    'auditor.availability.home_paused_locked':
        'Ntuhabwa imirimo yihuse muri iki gihe',

    'business.grow.starting': 'Biratangira…',

    'auditor.conflict.not_allowed':
        'Ntukibasha gutanga itangazo kuri iyi dosiye, bityo itangazo ryawe ntiryoherejwe.',

    'auditor.jobs.accept_due': 'Emera · birangire {time}',
    'auditor.jobs.accept_plain': 'Emera',
    'auditor.jobs.offer_open':
        'Icyifuzo gifunguye kugeza {time} · hasigaye {left}',
    'auditor.jobs.offer_label': 'Igihe gisigaye cyo kwemera iki cyifuzo',
    'auditor.jobs.offer_closed': 'Iki cyifuzo cyafunzwe',
    'auditor.command.refused.ASSIGNMENT_ACCEPTANCE_EXPIRED':
        'Iki cyifuzo cyafunzwe mbere y’uko kwemera kwawe kugera kuri Rozine, bityo ntikwakiriwe. Paji yavuguruwe.',

    'auditor.standing.reason.ACCREDITATION_REQUIRED':
        'Nta cyemezo cyemejwe urabona.',
    'auditor.command.refused.ACCREDITATION_REQUIRED':
        'Nta cyemezo cyemejwe urabona. Ntushobora guhabwa akazi mbere y’icyo gihe, bityo nta cyahinduwe.',
    'auditor.standing.reason.ACCREDITATION_EXPIRED':
        'Uruhushya rwawe rwarangiye. Ruvugurure kugira ngo wongere uhabwe akazi.',
    'auditor.command.refused.ACCREDITATION_EXPIRED':
        'Uruhushya rwawe rwarangiye. Ruvugurure kugira ngo wongere uhabwe akazi. Ntushobora guhabwa akazi mbere y’icyo gihe, bityo nta cyahinduwe.',
    'auditor.standing.reason.ACCREDITATION_SUSPENDED':
        "Icyemezo cyawe cyahagaritswe n'Ishami ry'Igenzura.",
    'auditor.command.refused.ACCREDITATION_SUSPENDED':
        "Icyemezo cyawe cyahagaritswe n'Ishami ry'Igenzura. Ntushobora guhabwa akazi mbere y’icyo gihe, bityo nta cyahinduwe.",
    'auditor.standing.reason.STANDING_CHECK_REQUIRED':
        "Igenzura ry'uburenganzira bwawe rikorwa n'Ishami ry'Igenzura rirategerejwe.",
    'auditor.command.refused.STANDING_CHECK_REQUIRED':
        "Igenzura ry'uburenganzira bwawe rikorwa n'Ishami ry'Igenzura rirategerejwe. Ntushobora guhabwa akazi mbere y’icyo gihe, bityo nta cyahinduwe.",
    'auditor.standing.paused_until_restored':
        'Kugabanya akazi byahagaze kugeza uburenganzira bwawe bugaruwe. Icyemezo cyawe cyo kwakira igenzura kiragumaho.',
    'auditor.standing.turning_on':
        'Kubifungura ntibizakuzanira ibyifuzo kugeza uburenganzira bwawe bugaruwe.',

    'auditor.accreditation.licence_title': 'Uruhushya rwo gukora umwuga',
    'auditor.accreditation.view_certificate': 'Kuramo icyemezo cyanditswe',
    'auditor.accreditation.view_submitted': 'Kuramo icyemezo cyoherejwe',

    'auditor.engagement.head_title': 'Amasezerano y’akazi',
    'auditor.engagement.title': 'Amasezerano y’akazi',
    'auditor.engagement.lead':
        'Soma inyandiko zombi zose mbere yo kwemera. Uhabwa akazi gashya gusa hakurikijwe amasezerano wemeye.',
    'auditor.engagement.synthetic_title': 'Amasezerano y’igerageza',
    'auditor.engagement.synthetic_body':
        'Aya ni amasezerano y’igerageza, ntakoreshwa ku kazi nyako. Kuyemera ntibigaragaza akazi k’umwuga nyako.',
    'auditor.engagement.original_language':
        'Amasezerano agaragazwa mu rurimi yanditswemo.',
    'auditor.engagement.document.master_services':
        'Amasezerano Rusange ya Serivisi',
    'auditor.engagement.document.agreed_procedures': 'Uburyo Bwumvikanyweho',
    'auditor.engagement.document_meta': 'Verisiyo {version} · SHA-256 {hash}…',
    'auditor.engagement.acceptance_title': 'Kwemera kwawe',
    'auditor.engagement.release_meta':
        'Verisiyo {version} · uburyo {procedure}',
    'auditor.engagement.release_hash': 'SHA-256 y’itangazwa {hash}…',
    'auditor.engagement.accept_label':
        'Nasomye kandi nemeye Amasezerano Rusange ya Serivisi n’Uburyo Bwumvikanyweho',
    'auditor.engagement.accept': 'Emera amasezerano',
    'auditor.engagement.accepting': 'Biremezwa…',
    'auditor.engagement.acceptance_required':
        'Kanda ku kazu kugira ngo wemeze ko wasomye kandi wemeye inyandiko zombi.',
    'auditor.engagement.accepted': 'Wemeye verisiyo {version} ku wa {date}',
    'auditor.engagement.accepted_receipt': 'SHA-256 y’icyemezo {hash}…',
    'auditor.engagement.no_accept':
        'Kwemera aya masezerano ntibishoboka kuri wowe ubu.',
    'auditor.engagement.unavailable': 'Nta masezerano y’akazi ahari ubu',
    'auditor.engagement.unavailable_body':
        'Akazi gashya karahagaze kugeza Rozine itangaje amasezerano. Nta cyo usabwa muri icyo gihe.',
    'auditor.engagement.refused.AUDIT_ENGAGEMENT_VERSION_CONFLICT':
        'Amasezerano yahindutse mbere y’uko kwemera kwawe kugera kuri Rozine, bityo nta cyemewe. Soma verisiyo iriho yose mbere yo kwemera.',
    'auditor.engagement.refused.AUDIT_ENGAGEMENT_TERMS_REQUIRED':
        'Aya masezerano yakuweho mbere y’uko kwemera kwawe kugera kuri Rozine, bityo nta cyemewe.',
    'auditor.engagement.banner.required':
        'Soma kandi wemere amasezerano y’akazi kugira ngo uhabwe akazi gashya',
    'auditor.engagement.banner.unavailable':
        'Amasezerano y’akazi ntaboneka; akazi gashya karahagaze',
    'auditor.command.refused.AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED':
        'Emera amasezerano y’akazi ariho kugira ngo ukomeze.',
    'auditor.command.review_terms': 'Soma amasezerano',
    'auditor.seal.save_note': 'Bika inyandiko',
    'auditor.seal.saving_note': 'Inyandiko irabikwa…',
    'auditor.seal.note_unsaved':
        'Bika inyandiko yawe mbere yo kureba ibizashyirwaho kashe. Igenzura, kode yawe na kashe byose bishingira ku nyandiko yabitswe.',
    'auditor.ledger.reported_undeclared': 'Ntibyatangajwe',
    'auditor.ledger.reported_undeclared_note':
        "Ikigo nticyatangaje agaciro k'ububiko, bityo nta mubare watangajwe wo kugereranya n'ibyo wabaze. Andika ibyo wabaze.",
    'auditor.ledger.reconciles_undeclared':
        'Guhuza bizakomeza guhagarara kugeza ikigo gitangaje ububiko bwacyo.',

    'auditor.statements.cover_unavailable': 'Ntibiboneka',
    'auditor.statements.documents': "Inyandiko z'umwimerere",
    'auditor.statements.no_documents':
        "Nta nyandiko z'umwimerere zibitswe z'uku kwezi.",
    'auditor.count.period_unavailable': 'Ntibiboneka',

    'auditor.file.start': 'Tangira igenzura',
    'auditor.file.starting': 'Biratangira…',
    'auditor.file.application_unavailable':
        'Nta busabe bwoherejwe buraboneka bwo kugenzura.',
    'auditor.command.refused.APPLICATION_VERSION_CONFLICT':
        "Ubusabe bw'ikigo bwahindutse kuva ufunguye iyi dosiye. Paji yavuguruwe — yirebe wongere utangire.",
    'auditor.command.refused.APPLICATION_NOT_SUBMITTED':
        'Ubu busabe ntiburoherezwa, bityo nta kintu kiragenzurwa. Paji yavuguruwe.',
    'auditor.command.refused.APPLICATION_NOT_FOUND':
        'Ubu busabe ntibukikugeraho, bityo nta kintu cyatangiye.',
    'auditor.command.refused.AUDIT_APPLICATION_BOUND':
        "Hari raporo isanzwe ihujwe n'ubu busabe, bityo nta nshya yatangiye. Paji yavuguruwe — komereza aho.",
    'auditor.command.refused.AUDIT_REPORT_REASSIGNMENT_REQUIRED':
        "Iyi raporo igomba guhabwa undi mbere y'uko akazi gakomeza, bityo nta kintu cyatangiye. Paji yavuguruwe.",

    'auditor.evidence.source_isolated_synthetic':
        "Igihamya cy'igerageza cy'ikigereranyo (cyitaruye)",

    'auditor.ledger.file_type': 'Hitamo igitabo kiri muri PDF cyangwa CSV.',
    'auditor.ledger.file_size':
        'Iyi dosiye irengeje MB 10. Ohereza PDF cyangwa CSV itarenze MB 10.',

    'auditor.seal.note_unsaved_unsealable':
        'Inyandiko yawe ntirabikwa. Yibike ubu; gushyiraho kashe bizafunguka raporo imaze kwitegura.',
    'auditor.capture.synthetic':
        "Igihamya cy'igerageza cy'ikigereranyo (cyitaruye) — ntabwo ari ifoto yafashwe koko.",

    'auditor.returned.title.changes_requested': 'Impinduka zasabwe',
    'auditor.returned.title.rejected': 'Inyandiko yanzwe',
    'auditor.returned.lead.changes_requested':
        'Iyi nyandiko yasubijwe ikigo kubera impamvu iri hepfo. Raporo ibikwa uko yasubijwe.',
    'auditor.returned.lead.rejected':
        "Iyi nyandiko ntiyashoboye kugenzurwa, kubera impamvu iri hepfo. Ibi bireba inyandiko, si inguzanyo y'ikigo. Raporo ibikwa uko yanzwe.",
    'auditor.returned.reason': 'Impamvu',
    'auditor.returned.recorded': 'Byanditswe',
    'auditor.returned.amend': 'Tangira ivugurura rifitanye isano',
    'auditor.returned.amended_by':
        'Ivugurura {report} ryatangijwe kuri iyi raporo.',
    'auditor.returned.view_amendment': 'Reba ivugurura',

    'auditor.reason.count': '{count} / {max}',
    'auditor.command.refused.AUDIT_REPORT_DECISION_NOT_ALLOWED':
        'Iyi raporo ntigishobora gusubizwa cyangwa kwangwa, bityo nta kintu cyanditswe. Paji yavuguruwe.',
    'auditor.command.refused.AUDIT_REPORT_NOT_AMENDABLE':
        'Iyi raporo ntishobora kuvugururwa ubu, bityo nta vugurura ryatangiye. Paji yavuguruwe.',
    'auditor.ledger.download': 'Kuramo umwimerere',

    'auditor.sealed.body_undated':
        'Raporo yashyizweho kashe ntishobora guhindurwa. {party} iracyakeneye gusinya; nyuma igezwa ku bashoramari.',
    'auditor.sealed.unavailable':
        "Iyi kashe ntishobora kugenzurwa ubu — urufunguzo rwayisinyishije ntirukiri urukoreshwa. Inyandiko yashyizweho kashe n'amateka yayo ntibyahindutse.",

    'auditor.sealed.body_published':
        'Byashyizweho kashe biranasinywa; byagejejwe ku bashoramari ku wa {date}.',
    'auditor.sealed.body_signed':
        'Raporo yashyizweho kashe kandi {party} yarayisinye. Ikurikiraho ni ukugezwa ku bashoramari.',
    'auditor.sealed.body_declined':
        'Raporo yashyizweho kashe. {party} yayihakanye aho kuyisinya, bityo ntiyagejejwe ku bashoramari.',
    'auditor.sealed.body_overdue':
        'Raporo yashyizweho kashe, ariko igihe {party} yari ifite cyo kuyisinya cyarangiye. Ntishobora gusinywa ukundi kandi ntiyagejejwe ku bashoramari; nta kintu cyemezwa ubwacyo.',

    'auditor.command.refused.AUDIT_PROCEDURE_SOURCE_CHANGED':
        'Isoko ryahindutse nyuma yo kureba ibizashyirwaho kashe. Subira inyuma urisuzume, hanyuma wongere urebe mbere yo gushyiraho kashe.',

    'auditor.sealed.body_amended':
        'Wavuguruye iyi raporo, bityo ntizasinywa kandi ntizagezwa ku bashoramari. Ivugurura ni ryo riyisimbura.',

    'settlement.notice.checking.title': 'Turagenzura ibyabaye',
    'settlement.notice.checking.body':
        'Igisubizo nticyatugezeho, turareba niba byanditswe. Nta kintu cyongera koherezwa muri icyo gihe.',
    'settlement.notice.unconfirmed.title': 'Ntibiremezwa',
    'settlement.notice.unconfirmed.body':
        'Ntitwabashije kugera kuri seriveri ngo tubyemeze. Nta kintu cyongeye koherezwa. Ongera urebe umaze kubona interineti.',
    'settlement.notice.pending.title': 'Byanditswe, ntibiremezwa',
    'settlement.notice.pending.body':
        'Seriveri yanditse iki cyifuzo kandi iracyagikoraho. Uru rupapuro ruzerekana igisubizo nikimara kwemezwa.',
    'settlement.notice.not_recorded.title': 'Nta cyanditswe',
    'settlement.notice.not_recorded.body':
        "Seriveri nta nyandiko ifite y'iki cyifuzo. Twavuguruye amakuru, ushobora kongera kohereza icyo cyifuzo nyine.",
    'settlement.notice.check_again': 'Ongera urebe',
    'settlement.notice.try_again': 'Ongera wohereze icyo cyifuzo',
    'settlement.notice.no_longer_allowed':
        'Nta cyanditswe, kandi iki gikorwa ntikigishobotse hakurikijwe amakuru ariho ubu.',
    'settlement.poll.stopped':
        'Ntibiremezwa. Twahagaritse kugenzura twikoresheje — vugurura urebe amakuru mashya.',
    'settlement.poll.refresh': 'Vugurura',
    'settlement.refusal.other':
        'Iki cyifuzo cyanzwe. Vugurura wongere ugerageze.',
    'settlement.refusal.VALIDATION_FAILED':
        'Hari amakuru agomba gukosorwa mbere yo gukomeza.',
    'settlement.refusal.EXPOSURE_LIMIT':
        "Ibi byarenza kimwe mu mipaka yawe y'ishoramari.",
    'settlement.refusal.INSUFFICIENT_AVAILABLE_FUNDS':
        'Amafaranga ufite aboneka ntahagije kuri aya mafaranga.',
    'settlement.refusal.VERSION_CONFLICT':
        'Hari ibyahindutse kuva uru rupapuro rufunguka. Twavuguruye amakuru — yasuzume wongere ugerageze.',
    'settlement.refusal.IDEMPOTENCY_CONFLICT':
        'Iki cyifuzo cyakoreshejwe ku kindi kintu. Ongera utangire uhereye ku makuru ariho ubu.',
    'settlement.refusal.RESERVATION_EXPIRED':
        "Igihe cy'iminota 5 wari wafatiwe cyarangiye, impapuro zirarekurwa. Ongera ufate kugira ngo ukomeze.",
    'settlement.refusal.CAMPAIGN_CLOSED':
        'Iki gikorwa cyo gushaka imari ntikigifunguye.',
    'settlement.refusal.UNITS_UNAVAILABLE':
        'Izo mpapuro ntizikiboneka. Hitamo nke cyangwa wongere ugerageze nyuma.',
    'settlement.refusal.COMMITMENT_LOCKED':
        'Imari yose yabonetse, ibi ntibigishobora guhagarikwa.',
    'settlement.refusal.CAMPAIGN_SETTLEMENT_REQUIRED':
        'Abashoramari bamaze kwiyemeza muri iki gikorwa cyo gukusanya, bityo ntigishobora guhagarikwa hano. Ibyo biyemeje bigomba kubanza gukemurwa.',
    'settlement.refusal.CAMPAIGN_FUNDED':
        'Iki gikorwa cyabonye amafaranga yose kandi gitegereje koherezwa, bityo ntigishobora guhagarikwa.',
    'settlement.refusal.NOTE_INELIGIBLE': 'Uru rupapuro ntirwemerewe ubu.',
    'settlement.refusal.DISCLOSURE_STALE':
        'Amakuru yo kumenyesha yahindutse. Soma ayariho ubu wongere uyemeze.',
    'settlement.refusal.POLICY_INPUT_REQUIRED':
        'Ibi ntibiraboneka: hari amabwiriza akenewe atarashyirwaho.',
    'settlement.refusal.DEPOSIT_METHOD_UNVERIFIED':
        'Iyo konti ntiragenzurwa ngo ikoreshwe mu kubitsa.',
    'settlement.refusal.WITHDRAWAL_METHOD_UNVERIFIED':
        'Hitamo konti yemejwe yo kubikurizaho.',
    'settlement.refusal.EXPOSURE_RESERVATION_REQUIRED':
        "Nta bubiko bw'inguzanyo bwanditswe kuri iyi dosiye, bityo ntishobora gushyirwa ku rutonde.",
    'settlement.refusal.SIGNATURES_REQUIRED':
        'Abasinya bose basabwa ntibarasinya ku Isuzuma.',
    'settlement.refusal.STAFF_RELEASE_REQUIRED':
        'Abakozi ba Rozine ntibararekura iyi dosiye.',
    'settlement.refusal.TERMS_CHANGED': 'Amabwiriza yarahindutse kuva wasinya.',
    'settlement.refusal.FEE_DISCLOSURE_CHANGED':
        'Ibisobanuro ku mafaranga yo gutangaza byarahindutse. Soma ibiriho mbere yo gutangaza.',
    'settlement.refusal.QUOTE_STALE': 'Igiciro wahawe ntikigikurikizwa.',
    'settlement.refusal.APPLICATION_ALREADY_RELEASED':
        'Iyi dosiye yamaze kurekurwa.',
    'settlement.refusal.LISTING_ALREADY_PUBLISHED':
        'Iki gitangazwa cyamaze gushyirwa ahagaragara.',
    'settlement.refusal.NOTHING_DUE':
        'Nta kigomba kwishyurwa kuri iyi nyandiko ubu. Urupapuro rwavuguruwe.',
    'settlement.refusal.NOTE_NOT_SERVICING':
        'Iyi nyandiko ntiri kwishyurwa, bityo nta kintu cyo kwishyura.',
    'settlement.refusal.APPLICATION_NOT_RELEASED':
        'Iyi dosiye ntiremererwa gushyirwa ku rutonde.',
    'settlement.refusal.DISBURSEMENT_IN_FLIGHT':
        'Icyemezo cyo kwishyura cyamaze kwandikwa, ntigishobora guhindurwa gutya.',
    'settlement.refusal.PROVIDER_OUTCOME_UNRESOLVED':
        "Igisubizo cy'utanga serivisi ntikirasobanuka, iki gikorwa kirahagaritswe.",
    'settlement.refusal.ACTION_FORBIDDEN':
        'Ntushobora gukora ibi ukurikije uburenganzira ufite ubu.',
    'settlement.refusal.IDENTITY_VERIFICATION_REQUIRED':
        'Genzura umwirondoro wawe kugira ngo ushore imari.',
    'settlement.refusal.RESTRICTION_ACTIVE':
        'Hari ikumira riri gukurikizwa, ibi ntibiboneka ubu.',
    'settlement.refusal.CONNECTED_PARTY':
        "Ufitanye isano n'iki kigo, ntushobora kubigiramo uruhare.",
    'settlement.refusal.SELF_APPROVAL_FORBIDDEN':
        'Undi mukozi agomba kubikora: ntushobora kwemeza igikorwa cyawe bwite.',
    'settlement.refusal.STEP_UP_REQUIRED':
        'Hakenewe kubanza kwemeza bundi bushya mu buryo bukomeye.',
    'settlement.refusal.STEP_UP_INVALID':
        "Iryo yemeza ntiryakunze. Andika kode nshya yo muri porogaramu yawe y'umutekano.",
    'settlement.refusal.STEP_UP_EXPIRED':
        "Iryo yemeza ryarangiye. Andika kode nshya yo muri porogaramu yawe y'umutekano.",
    'settlement.refusal.MANDATE_REQUIRED':
        "Ibi bikeneye umuntu wemerewe n'ububasha bw'ikigo.",
    'settlement.refusal.STAFF_ACCESS_REQUIRED':
        "Hakenewe uburenganzira bw'abakozi.",
    'settlement.refusal.STAFF_PERMISSION_REQUIRED':
        "Uburenganzira bwawe nk'umukozi ntibukubiyemo iki gikorwa.",
    'settlement.refusal.STAFF_VERIFIED_EMAIL_AND_MFA_REQUIRED':
        'Emeza imeyili yawe kandi ukoreshe kwemeza mu ntambwe ebyiri kugira ngo ukomeze.',
    'settlement.refusal.MFA_REQUIRED': 'Kwemeza mu ntambwe ebyiri birakenewe.',
    'settlement.refusal.NOT_FOUND': 'Ntushobora kongera kureba iyi nyandiko.',
    'investor.wallet.c3.available_balance': 'Amafaranga aboneka',
    'investor.wallet.c3.total': "Igiteranyo cy'ikofi",
    'investor.wallet.c3.bucket.available': 'Aboneka',
    'investor.wallet.c3.bucket.held': 'Afashwe',
    'investor.wallet.c3.bucket.committed': 'Yiyemejwe',
    'investor.wallet.c3.spendable':
        'Aboneka ni yo yonyine ishobora gukoreshwa. Afashwe ari mu kwishyura gukomeje; ayiyemejwe ategereje gutangwa.',
    'investor.wallet.c3.restricted':
        'Hari ikumira rikurikizwa kuva ku wa {date}. Kubitsa biracyashoboka.',
    'investor.wallet.c3.restricted_unavailable':
        'Hari ikumira rikurikizwa kuva ku wa {date}. Kubitsa ntibiboneka muri iki gihe.',
    'investor.wallet.c3.no_pending': 'Bihita bishobora gushorwa',
    'investor.wallet.c3.pending_deposits':
        '{amount} ntibiremezwa — ntibiri mu giteranyo',
    'investor.wallet.c3.no_policy':
        'Kubitsa ntibiraboneka: nta mabwiriza yo kubitsa arashyirwaho.',
    'investor.wallet.c3.credited_on_success':
        'Azashyirwa kuri konti nibimara kwemezwa',
    'investor.wallet.c3.policy_synthetic':
        "Amabwiriza y'igerageza yo kubitsa {version} — imibare y'igerageza, si amabwiriza akurikizwa.",
    'investor.wallet.c3.policy': 'Amabwiriza yo kubitsa {version}.',
    'investor.wallet.c3.policy_minimum': 'Nibura {amount}.',
    'investor.wallet.c3.policy_maximum': 'Ntarengwa {amount}.',
    'investor.wallet.c3.deposit_unavailable': 'Kubitsa ntibikunda ubu.',
    'investor.wallet.c3.intent_only':
        "Ibi byandika icyifuzo cyawe cyo kubitsa. Nta mafaranga ashyirwa kuri konti mbere y'uko ubwishyu bwemezwa.",
    'investor.wallet.c3.holds': 'Afashwe mu kwishyura',
    'investor.wallet.c3.hold_line': {
        one: '{name} · urupapuro {count}',
        other: '{name} · impapuro {count}',
    },
    'investor.wallet.c3.hold_expires': 'Birekurwa mu {time} keretse wemeje',
    'investor.wallet.c3.deposits': 'Ibyabikijwe',
    'investor.wallet.c3.deposit_from': 'Kubitsa · {method}',
    'investor.wallet.c3.intent.pending': 'Ntibiremezwa',
    'investor.wallet.c3.intent.unknown':
        "Ntibiremezwa — turi kugenzura n'utanga serivisi",
    'investor.wallet.c3.intent.succeeded': 'Byashyizwe kuri konti',
    'investor.wallet.c3.intent.failed':
        'Ntibyakunze — nta cyashyizwe kuri konti',
    'investor.wallet.c3.entry.deposit_in': 'Kubitsa · {counterparty}',
    'investor.wallet.c3.entry.deposit_out': 'Kohereza kuri {counterparty}',
    'investor.wallet.c3.entry.hold': 'Afashwe kuri {name}',
    'investor.wallet.c3.entry.hold_release': 'Ifatwa ryarekuwe · {name}',
    'investor.wallet.c3.entry.commitment': 'Ishoramari · {name}',
    'investor.wallet.c3.entry.commitment_refund': 'Gusubizwa · {name}',
    'investor.wallet.c3.movement': "Ubwoko bw'imyimukire",
    'investor.wallet.c3.period': 'Igihe',
    'investor.wallet.c3.range.today': 'Uyu munsi',
    'investor.wallet.c3.range.7d': 'Iminsi 7',
    'investor.wallet.c3.range.30d': 'Iminsi 30',
    'investor.wallet.c3.range.all': 'Igihe cyose',
    'investor.wallet.c3.range.all_short': 'Byose',
    'investor.wallet.c3.filter.external': "Ayinjiye n'ayasohotse",
    'investor.wallet.c3.filter.internal': "Ayafashwe n'ayiyemejwe",
    'investor.wallet.c3.transfer': '{from} → {to}',
    'investor.wallet.c3.older': 'Erekana ibya kera',
    'investor.wallet.c3.receipt_policy': "Verisiyo y'amabwiriza",
    'investor.wallet.c3.not_credited':
        'Byanditswe, ntibiremezwa. Nta cyashyizwe kuri konti; bizakorwa ari uko ubwishyu bwemejwe.',
    'investor.wallet.c3.not_credited_failed':
        'Byemejwe ko ubwishyu butarangiye. Nta cyashyizwe kuri konti.',
    'investor.wallet.c3.credit_receipt':
        'Inyemezabwishyu yo gushyira kuri konti',
    'investor.wallet.c3.funding': 'Kwimura amafaranga',
    'investor.wallet.c3.withdraw_unavailable':
        'Kubikuza ntibishoboka kuri konti yawe ubu.',
    'investor.wallet.c3.withdraw_available': 'Ayo ushobora kubikuza: {amount}',
    'investor.wallet.c3.provider_charge': "Amafaranga y'utanga serivisi",
    'investor.wallet.c3.withdraw_terms':
        'Bikuza hagati ya {minimum} na {maximum} icyarimwe. Amabwiriza {version}.',
    'investor.wallet.c3.withdraw_only_on_success':
        'Amafaranga ava ku yo ushobora gukoresha ubu, akoherezwa iyo utanga serivisi abyemeje. Iyo kohereza binaniranye, agaruka mu gikapu cyawe.',
    'investor.wallet.c3.withdrawals': 'Ibyabikujwe',
    'investor.wallet.c3.withdrawal_to': 'Kubikuza · {method}',
    'investor.wallet.c3.withdrawal.pending': 'Biroherezwa',
    'investor.wallet.c3.withdrawal.unknown':
        'Biroherezwa — turi kubaza utanga serivisi',
    'investor.wallet.c3.withdrawal.succeeded': 'Byoherejwe',
    'investor.wallet.c3.withdrawal.failed':
        'Ntibyakunze — byagarutse mu gikapu cyawe',
    'investor.wallet.c3.withdrawal_waiting':
        'Byabikiwe uku kubikuza kugeza utanga serivisi abyemeje. Hagati aho ntibiba mu yo ushobora gukoresha.',
    'investor.wallet.c3.settled_receipt.WITHDRAWAL_PAID':
        'Inyemezabwishyu yo kohereza',
    'investor.wallet.c3.settled_receipt.WITHDRAWAL_RETURNED':
        'Inyemezabwishyu yo kugarura',
    'investor.wallet.c3.entry.withdrawal_in':
        'Byagarutse bivuye kuri {counterparty}',
    'investor.wallet.c3.entry.withdrawal_out': 'Kubikuza · {counterparty}',
    'business.publish.intro':
        '{title} izagaragara ku bashoramari buri ntambwe iri hepfo imaze kuzuzwa. Gutangaza bikoresha imikono watanze mu isuzuma, nta kindi cyo gusinya hano.',
    'business.publish.release.awaiting':
        "Hategerejwe isuzuma ry'abakozi ba Rozine. Abakozi barekura ubusabe ari uko gusa moteri y'amanota, ububasha bwo gusinya na raporo y'igenzura byose byujuje ibisabwa.",
    'business.publish.release.released':
        'Abakozi ba Rozine bayirekuye ngo ishyirwe ku isoko.',
    'business.publish.release.refused': 'Ntiyarekuwe ngo ishyirwe ku isoko',
    'business.publish.cause.ENGINE_GATE_FAILED':
        "Ibisabwa by'inguzanyo bya moteri y'amanota ntibyujujwe.",
    'business.publish.cause.AUTHORITY_CHANGED':
        "Ububasha bwo gusinya bw'ikigo bwahindutse kuva wasinya.",
    'business.publish.cause.REPORT_NOT_CURRENT':
        "Raporo y'igenzura ntikiri iy'igihe.",
    'business.publish.cause.EXPOSURE_RESERVATION_REQUIRED':
        "Nta bubiko bw'inguzanyo bwanditswe kuri iyi dosiye, bityo ntishobora gushyirwa ku rutonde.",
    'business.publish.cause.SIGNATURES_REQUIRED':
        'Abasinya bose basabwa ntibarasinya ku Isuzuma.',
    'business.publish.cause.STAFF_RELEASE_REQUIRED':
        'Abakozi ba Rozine ntibararekura iyi dosiye.',
    'business.publish.cause.TERMS_CHANGED':
        'Amabwiriza yarahindutse kuva wasinya.',
    'business.publish.cause.FEE_DISCLOSURE_CHANGED':
        'Ibisobanuro ku mafaranga yo gutangaza byarahindutse. Soma ibiriho mbere yo gutangaza.',
    'business.publish.cause.QUOTE_STALE': 'Igiciro wahawe ntikigikurikizwa.',
    'business.publish.cause.RESTRICTION_ACTIVE':
        'Hari ikumira rireba iki kigo.',
    'business.publish.cause.RELEASE_CHECK_NOT_COMPLETED':
        'Igenzura rimwe ryo kurekura ntiryarangiye, bityo dosiye ntirashobora kurekurwa.',
    'business.publish.cause.other': 'Igenzura rimwe ryo kurekura ntiryatsinze.',
    'business.publish.prerequisites': 'Mbere yo gutangaza',
    'business.publish.prerequisite.staff_release':
        "Yarekuwe n'abakozi ba Rozine nyuma y'isuzuma",
    'business.publish.prerequisite.signatures_retained':
        'Imikono yawe yo mu isuzuma irabitswe',
    'business.publish.prerequisite.quote_current':
        'Igiciro ntikyahindutse kuva wasinya',
    'business.publish.prerequisite.terms_current':
        'Amabwiriza ntiyahindutse kuva wasinya',
    'business.publish.met': 'Byakozwe',
    'business.publish.not_met': 'Ntibirakorwa',
    'business.publish.fee_label': 'Amafaranga yo gushyira ku isoko',
    'business.publish.fee_waived': 'Yakuweho muri MVP',
    'business.publish.disclosure_title': 'Itangazo ku mafaranga',
    'business.publish.disclosure_version': 'Itangazo {version}',
    'business.publish.changed':
        'Igiciro cyangwa amabwiriza byahindutse kuva wasinya. Bisuzume wongere usinye mbere yo gutangaza.',
    'business.publish.review_again': 'Suzuma wongere usinye',
    'business.publish.blocked':
        'Gutangaza bizafunguka buri ntambwe iri hejuru imaze kuzuzwa.',
    'business.publish.published.title': 'Yashyizwe ku bashoramari',
    'business.publish.published.body':
        "{title} iragaragara ku rubuga rw'abashoramari. Nta mafaranga yakuweho.",
    'business.publish.published.receipt':
        'Inyemezabwishyu yo gushyira ku isoko',
    'business.publish.published.reference': "Nomero y'ikiranga",
    'business.publish.published.recorded': 'Byanditswe',
    'business.publish.published.disclosure': 'Itangazo ku mafaranga',
    'business.publish.published.campaign': 'Reba igikorwa cyo gukusanya',
    'business.publish.published.home': 'Subira ku Ahabanza',
    'business.campaign.state.live': 'Irakora',
    'business.campaign.state.fully_reserved': 'Yose yafashwe',
    'business.campaign.state.funded': 'Yabonye amafaranga yose',
    'business.campaign.state.funded_pending_disbursement':
        'Yabonye amafaranga yose · itegereje koherezwa',
    'business.campaign.state.disbursing': 'Amafaranga arimo koherezwa',
    'business.campaign.state.issued': 'Impapuro zatanzwe',
    'business.campaign.state.expired': 'Ntiyuzuye',
    'business.campaign.state.cancelled': 'Yahagaritswe',
    'business.campaign.state.failed_closing': 'Yafunzwe, amafaranga asubizwa',
    'business.campaign.state.sold_out_pending_settlement': 'Yose yiyemejwe',
    'business.campaign.state.inventory_unavailable': 'Nta nyandiko ziboneka',
    'business.campaign.state.closing_pending_settlement': 'Birarangira',
    'business.campaign.state.unavailable': 'Imiterere ntiboneka',
    'business.campaign.lifecycle.live': 'Irakusanya · irakora',
    'business.campaign.lifecycle.fully_reserved': 'Irakusanya · yose yafashwe',
    'business.campaign.lifecycle.sold_out_pending_settlement':
        'Yose yiyemejwe · itegereje kurangizwa',
    'business.campaign.lifecycle.inventory_unavailable':
        'Irakusanya · nta nyandiko ziboneka',
    'business.campaign.lifecycle.closing_pending_settlement':
        'Igihe cyarangiye · birarangira',
    'business.campaign.restriction.RESTRICTION_ACTIVE':
        'Yashyizweho imbogamizi kuva ku wa {date}. Ibyiyemezo bishya birahagaritswe igihe imbogamizi imara; ibyamaze kwiyemezwa birahaguma.',
    'business.campaign.restriction.NOTE_INELIGIBLE':
        'Ntiyemerewe ibyiyemezo bishya kuva ku wa {date}; ibyamaze kwiyemezwa birahaguma.',
    'business.campaign.tile.committed': 'Byiyemejwe',
    'business.campaign.tile.of_target': "By'intego",
    'business.campaign.tile.refunded': 'Byasubijwe',
    'business.campaign.tracker.title': "Ikurikirana ry'ibyiyemejwe",
    'business.campaign.tracker.committed_pct': '{pct}% byiyemejwe',
    'business.campaign.not_yet_committed': 'Ibitariyemezwa kandi bitarafatwa',
    'business.campaign.closing_now': 'Birarangira',
    'business.campaign.committed': 'Byiyemejwe',
    'business.campaign.reserved': 'Byafashwe',
    'business.campaign.reserved_note':
        "Inyandiko zafashwe mu kwishyura kw'umushoramari. Ntiziriyemezwa.",
    'business.campaign.units':
        'Inyandiko {committed} kuri {total} ziyemejwe · {reserved} zafashwe · {available} ziraboneka · {unavailable} ntiziboneka',
    'business.campaign.closes': 'Birangira ku wa {date}',
    'business.campaign.fully_reserved':
        'Inyandiko zose zihari zafashwe mu kwishyura. Abashoramari bashya ntibashobora gufata ubu.',
    'business.campaign.unavailable_note':
        'Inyandiko zitaboneka zari mu kwishyura cyangwa mu cyiyemezo cyarangiye. Ntizigurishwa.',
    'business.campaign.sold_out':
        'Inyandiko zose ziyemejwe, bityo abashoramari bashya ntibashobora kwinjira. Ubukusanye ntiburabona amafaranga kugeza Rozine irangije kubirangiza.',
    'business.campaign.inventory_unavailable':
        'Nta nyandiko ziboneka zo gufata kandi nta zafashwe mu kwishyura, bityo abashoramari bashya ntibashobora kwiyemeza ubu.',
    'business.campaign.closing':
        'Igihe cyarangiye, bityo abashoramari bashya ntibashobora kwiyemeza. Rozine irimo gufunga ubukusanye kandi izerekana ibyavuyemo hano.',
    'business.campaign.progress_unavailable':
        'Aho ubukusanye bugeze ntibushobora kwerekanwa ubu. Ongera ufungure urupapuro ugerageze.',
    'business.campaign.tile.deadline': 'Itariki ntarengwa',
    'business.campaign.deadline_passed': 'Igihe cyarangiye ku wa {date}',
    'business.campaign.funded':
        'Byatewe inkunga yose ku wa {date}. Iki gikorwa ntikigishobora guhagarikwa.',
    'business.campaign.closing_title': 'Kohereza amafaranga',
    'business.campaign.closing.awaiting.title':
        'Hategerejwe kohereza amafaranga',
    'business.campaign.closing.awaiting.body':
        'Rozine irimo gutegura kohereza amafaranga kuri konti yawe. Tuzabigaragaza hano nibimara koherezwa no kwemezwa.',
    'business.campaign.closing.in_flight.title': 'Ntibiremezwa',
    'business.campaign.closing.in_flight.pending':
        'Kohereza amafaranga kuri konti yawe biracyakorwa. Tuzabigaragaza hano nibimara kwemezwa.',
    'business.campaign.closing.in_flight.unknown':
        "Uko kohereza amafaranga byagenze ntibiremezwa. Nta kirarangira mbere y'uko byemezwa; tuzabigaragaza hano nibimara kwemezwa.",
    'business.campaign.disbursed': '{amount} byoherejwe kuri {destination}.',
    'business.campaign.disbursed_amount': 'Byoherejwe',
    'business.campaign.destination': 'Kuri',
    'business.campaign.effective_at': 'Byakurikijwe',
    'business.campaign.effective_date': "Itariki y'ingengabihe (Kigali)",
    'business.campaign.receipt.title': 'Inyemezabwishyu yo kohereza amafaranga',
    'business.campaign.receipt.amount': 'Amafaranga',
    'business.campaign.receipt.reference': "Nomero y'ikiranga",
    'business.campaign.receipt.recorded': 'Byanditswe',
    'business.campaign.receipt.view': 'Reba inyemezabwishyu',
    'business.campaign.closed.expired':
        'Iki gikorwa cyo gukusanya cyarangiye ku wa {date} kitaratewe inkunga yose. {amount} byasubijwe abashoramari byose, nta kiguzi.',
    'business.campaign.closed.cancelled':
        'Iki gikorwa cyo gukusanya cyahagaritswe ku wa {date}. {amount} byasubijwe abashoramari byose, nta kiguzi.',
    'business.campaign.closed.failed_closing':
        'Iki gikorwa cyo gukusanya cyarangiye ku wa {date} nta mafaranga yoherejwe. {amount} byasubijwe abashoramari byose, nta kiguzi.',
    'business.campaign.closed.expired_none':
        'Iki gikorwa cyo gukusanya cyafunzwe ku wa {date} nta mushoramari urashyiramo amafaranga, bityo nta cyo gusubizwa cyari gihari.',
    'business.campaign.closed.cancelled_none':
        'Iki gikorwa cyo gukusanya cyahagaritswe ku wa {date} nta mushoramari urashyiramo amafaranga, bityo nta cyo gusubizwa cyari gihari.',
    'business.campaign.closed.failed_closing_none':
        'Iki gikorwa cyo gukusanya cyarangiye ku wa {date} nta mafaranga yoherejwe. Nta mushoramari wari washyizemo amafaranga, bityo nta cyo gusubizwa cyari gihari.',
    'business.campaign.cancel.open': 'Hagarika iki gikorwa cyo gukusanya',
    'business.campaign.cancel.cancelling': 'Birahagarikwa…',
    'business.campaign.cancel.title': 'Uhagarike iki gikorwa cyo gukusanya?',
    'business.campaign.cancel.body':
        'Igikorwa cyo gukusanya kirafungwa burundu kandi ntikizongera kwakira abashoramari. Ntibishobora gusubizwa inyuma.',
    'business.campaign.cancel.reason': 'Impamvu (si ngombwa)',
    'business.campaign.cancel.confirm': 'Hagarika gukusanya',
    'business.campaign.cancel.keep': 'Komeza gukusanya',
    'admin.disbursements.col.provider': 'Utanga serivisi',
    'admin.disbursements.state.queued': 'Icyifuzo cyanditswe · gitegereje',
    'admin.disbursements.state.succeeded': 'Byishyuwe · byahujwe',
    'admin.disbursements.state.failed_closing':
        'Byafunzwe binaniranye · byasubijwe',
    'admin.disbursements.provider.none': 'Ntibyoherejwe',
    'admin.disbursements.provider.pending': 'Bitegereje · ntibiremezwa',
    'admin.disbursements.provider.unknown': 'Ntibizwi · ntibiremezwa',
    'admin.disbursements.provider.succeeded': 'Byagenze neza · byagenzuwe',
    'admin.disbursements.provider.failed': 'Byananiranye · byagenzuwe',
    'admin.disbursements.older': 'Kwishyura kwa kera',
    'admin.disbursements.deadline': 'Itariki ntarengwa',
    'admin.disbursements.deadline_unavailable': 'Nta tariki ntarengwa yemejwe',
    'admin.disbursements.causes': 'Impamvu',
    'admin.disbursements.receipt.code': 'Inyemezabwishyu',
    'admin.disbursements.receipt.reference': 'Indango',
    'admin.disbursements.receipt.amount': 'Amafaranga',
    'admin.disbursements.receipt.recorded_at': 'Byanditswe ku',
    'admin.disbursements.receipt.revision': 'Ivugurura',
    'admin.disbursements.check.not_run': 'Ntibirakorwa',
    'admin.disbursements.check.passed': 'Byatsinze',
    'admin.disbursements.check.failed': 'Byananiranye',
    'admin.disbursements.precheck.title': 'Igenzura ribanza',
    'admin.disbursements.checked_at': 'Byagenzuwe ku',
    'admin.disbursements.policy_version': "Verisiyo y'amabwiriza",
    'admin.disbursements.intent.title': 'Icyifuzo cyo kwishyura',
    'admin.disbursements.intent.not_payment':
        'Icyifuzo cyanditswe — si ukwishyura. Serivisi yishyura iracyohereza gusa nyuma yo kongera kugenzura.',
    'admin.disbursements.intent.not_sent':
        'Ntibiroherezwa: serivisi ntiraryohereza uku kwishyura.',
    'admin.disbursements.operation': 'Igikorwa',
    'admin.disbursements.dispatch.title': 'Kohereza',
    'admin.disbursements.dispatch.sent_at': 'Byoherejwe ku',
    'admin.disbursements.dispatch.recheck': 'Kongera kugenzura kwa serivisi',
    'admin.disbursements.outcome.title': "Igisubizo cy'utanga serivisi",
    'admin.disbursements.outcome.pending':
        'Bitegereje: utanga serivisi ntaremeza igisubizo. Ntibyishyuwe kandi ntibyananiranye.',
    'admin.disbursements.outcome.unknown':
        "Ntibizwi: igisubizo cy'utanga serivisi ntikiremezwa. Ntibyishyuwe kandi ntibyananiranye.",
    'admin.disbursements.outcome.succeeded':
        'Byagenze neza: utanga serivisi yagenzuye ukwishyura.',
    'admin.disbursements.outcome.failed':
        'Byananiranye: utanga serivisi yagenzuye ko byananiranye burundu.',
    'admin.disbursements.outcome.failed_unreconciled':
        'Ntibirahuzwa: nta kintu gifungwa cyangwa gisubizwa kugeza kunanirwa guhujwe.',
    'admin.disbursements.outcome.exception':
        "Ikibazo mu guhuza: igisubizo cy'utanga serivisi kivuguruzanya cyangwa ntigishobora gukemurwa. Uku kwishyura gukomeza guhagarikwa kandi ntiguhujwe.",
    'admin.disbursements.outcome.reference': "Indango y'utanga serivisi",
    'admin.disbursements.outcome.error_code': "Kode y'ikosa",
    'admin.disbursements.outcome.observed_at': 'Byabonetse ku',
    'admin.disbursements.outcome.effective_at': 'Byagize agaciro ku',
    'admin.disbursements.outcome.reconciliation': 'Guhuza',
    'admin.disbursements.outcome.reconciled_at': 'Byahujwe ku',
    'admin.disbursements.outcome.requery_note':
        'Kongera kubaza utanga serivisi bibaza kuri iki gikorwa kimwe. Ntibyongera kohereza ukwishyura. Igenzura riteganyijwe na ryo rirabaza.',
    'admin.disbursements.reconciliation.unreconciled': 'Ntibirahuzwa',
    'admin.disbursements.reconciliation.matched': 'Byahujwe',
    'admin.disbursements.reconciliation.exception':
        'Ikibazo · byahagaritswe, ntibyahujwe',
    'admin.disbursements.hold.title': 'Ihagarikwa',
    'admin.disbursements.hold.release_note':
        "Gukuraho ihagarikwa bituma kwishyura byikora byongera gukora. Umukozi wese ubifitiye uburenganzira ashobora kurikuraho, harimo n'uwarishyizeho.",
    'admin.disbursements.issue.title': 'Isohora',
    'admin.disbursements.issue.holdings': 'Imigabane yasohowe',
    'admin.disbursements.issue.issued_at': 'Byasohowe ku',
    'admin.disbursements.issue.effective_date': 'Itariki bitangira (Kigali)',
    'admin.disbursements.refund.title': 'Gusubiza amafaranga',
    'admin.disbursements.refund.commitments': 'Ibyiyemejwe byasubijwe',
    'admin.disbursements.refund.total': 'Igiteranyo cyasubijwe',
    'admin.disbursements.ledger': "Fungura mu gitabo cy'ibaruramari",
    'admin.disbursements.attention_count': '{count} bikeneye kurebwa',
    'admin.disbursements.automation.title': 'Kwishyura byikora',
    'admin.disbursements.automation.payout': 'Ikigo kirahabwa',
    'admin.disbursements.automation.rule': 'Itegeko',
    'admin.disbursements.automation.waiting.verified_payout_destination':
        'Bitegereje aho kwishyurira hemejwe. Kwishyura bitangira byikora iyo aho ikigo cyishyurirwa hamaze kwemezwa.',
    'admin.disbursements.automation.waiting.next_run':
        'Kwishyura bitangira byikora ubutaha bikora.',
    'admin.disbursements.command.release_hold': 'Kuraho ihagarikwa',
    'admin.disbursements.command.requery': 'Ongera ubaze utanga serivisi',
    'admin.disbursements.stage.release_hold.title': 'Kuraho iri hagarikwa',
    'admin.disbursements.stage.release_hold.body':
        "Kwishyura byikora byongera gukora iyo ihagarikwa rikuweho. Bisubira aho byari biri mbere y'ihagarikwa.",
    'admin.disbursements.stage.release_hold.cta': 'Kuraho ihagarikwa',
    'admin.disbursements.stage.release_hold.placeholder':
        'urugero: Ikigo cyemeje nimero yacyo nshya ya MoMo.',
    'admin.disbursements.stage.requery.title': 'Ongera ubaze utanga serivisi',
    'admin.disbursements.stage.requery.body':
        'Ibi bibaza utanga serivisi ku gikorwa kimwe. Ntibyongera kohereza ukwishyura, kandi nta cyo byemeza ko cyahujwe: igisubizo kinyura mu guhuza nk’ikindi cyose.',
    'admin.disbursements.stage.requery.cta': 'Baza utanga serivisi',
    'admin.disbursements.stage.requery.placeholder':
        "urugero: Urupapuro rw'utanga serivisi rugaragaza ko ikibazo cyarangiye.",
    'admin.applications.release.title': 'Kurekurira gushyirwa ku isoko',
    'admin.applications.release.state.awaiting_staff_review':
        "Bitegereje isuzuma ry'abakozi",
    'admin.applications.release.state.released': 'Byarekuwe',
    'admin.applications.release.state.refused': 'Byanzwe',
    'admin.applications.release.explain':
        "Kurekura bituma ikigo gishyira ahagaragara ubu bukusanye. Bisaba ko igenzura rya moteri, iry'ububasha n'irya raporo byatsinze, kandi ntibishobora kurenga igenzura ryananiranye.",
    'admin.applications.release.gates': 'Igenzura ryo kurekura',
    'admin.applications.release.gate.engine': 'Moteri isuzuma inguzanyo',
    'admin.applications.release.gate.authority': "Ububasha bw'ikigo",
    'admin.applications.release.gate.report':
        "Raporo y'igenzura ryo gushyira ku isoko",
    'admin.applications.release.gate_state.passed': 'Byatsinze',
    'admin.applications.release.gate_state.failed': 'Byahagaritswe',
    'admin.applications.release.cause.ENGINE_GATE_FAILED':
        "Ibisabwa by'inguzanyo bya moteri y'amanota ntibyujujwe.",
    'admin.applications.release.cause.AUTHORITY_CHANGED':
        "Ububasha bwo gusinya bw'ikigo bwahindutse nyuma yo gusinya.",
    'admin.applications.release.cause.MANDATE_UNVERIFIED':
        'Uburenganzira bwo gusinyira ikigo ntiburemezwa.',
    'admin.applications.release.cause.REPORT_NOT_CURRENT':
        "Raporo y'igenzura ntikiri iy'ubu.",
    'admin.applications.release.cause.EXPOSURE_RESERVATION_REQUIRED':
        "Nta bubiko bw'inguzanyo bwanditswe kuri iyi dosiye.",
    'admin.applications.release.cause.SIGNATURES_REQUIRED':
        'Ntabwo abasinya bose basabwa basinye ku Isuzuma.',
    'admin.applications.release.cause.STAFF_RELEASE_REQUIRED':
        "Iyemezwa ryanditswe ntirigihuye n'ibyo ikigo cyasinye.",
    'admin.applications.release.cause.TERMS_CHANGED':
        "Amasezerano yahindutse nyuma y'uko ikigo gisinye.",
    'admin.applications.release.cause.FEE_DISCLOSURE_CHANGED':
        "Itangazo ry'amafaranga yo gushyira ku isoko ryahindutse.",
    'admin.applications.release.cause.QUOTE_STALE':
        "Igiciro cyatanzwe ntikiri icy'ubu.",
    'admin.applications.release.cause.RESTRICTION_ACTIVE':
        'Iki kigo gifite imbogamizi.',
    'admin.applications.release.cause.RELEASE_CHECK_NOT_COMPLETED':
        'Iri genzura ntiryashoboye kurangira.',
    'admin.applications.release.cause.other': 'Iri genzura ntiryatsinze.',
    'admin.applications.release.blocked':
        'Igenzura rimwe ryananiranye, bityo iyi dosiye ntishobora kurekurwa. Kurekura ntibirenga igenzura ryananiranye.',
    'admin.applications.release.receipt': 'Inyemezabwishyu yo kurekura',
    'admin.applications.release.command': 'Rekurira gushyirwa ku isoko',
    'admin.applications.release.stage.title': 'Rekura iyi dosiye',
    'admin.applications.release.stage.body':
        'Ikigo kizashobora gushyira ahagaragara ubukusanye bwacyo. Nta kintu gishyirwa ku isoko cyangwa ngo giterwe inkunga kitarabikora.',
    'admin.applications.release.stage.cta': 'Rekura',
    'admin.applications.release.stage.placeholder':
        'urugero: Moteri, ububasha na raporo yashyizweho kashe byose byemejwe.',
    'investor.audit.tolerance': "Ikinyuranyo cyemewe n'amabwiriza",
    'investor.audit.reconciliation': "ITANGAZO RY'IGENZURA RY'IMIBARE",
    'investor.deal.ebitda_unavailable': 'Ntiboneka',
    'investor.deal.ebitda_not_sourced': 'Ntiyakuwe nka EBITDA',
    'investor.deal.photos_none': 'Ikigo nta mafoto cyashyize ahagaragara.',
    'investor.deal.photo_unavailable': 'Ifoto ntiboneka',
    'investor.deal.restriction.NOTE_INELIGIBLE':
        'Gushora byahagaritswe: uru rupapuro ntirwemerewe ubu',
    'investor.deal.restriction.RESTRICTION_ACTIVE':
        'Gushora byahagaritswe: hari ikumira rikurikizwa',
    'investor.deal.restriction.body':
        'Kuva ku wa {date}. Igikorwa cyo gushaka imari gikomeza uko kiri; gufata bishya bitegereza ko ikumira rivanwaho.',
    'investor.deal.lifecycle.live': 'Birakomeje',
    'investor.deal.lifecycle.fully_reserved': 'Byafashwe byose',
    'investor.deal.lifecycle.sold_out_pending_settlement': 'Yose yiyemejwe',
    'investor.deal.lifecycle.inventory_unavailable': 'Nta nyandiko ziboneka',
    'investor.deal.lifecycle.closing_pending_settlement': 'Birarangira',
    'investor.deal.lifecycle.funded': 'Imari yose yabonetse',
    'investor.deal.lifecycle.disbursing': 'Birimo kwishyurwa',
    'investor.deal.lifecycle.issued': 'Impapuro zatanzwe',
    'investor.deal.lifecycle.expired': 'Ntibyuzuye',
    'investor.deal.lifecycle.cancelled': 'Byahagaritswe',
    'investor.deal.lifecycle.failed_closing': 'Byafunzwe, amafaranga asubizwa',
    'investor.deal.notice.fully_reserved.title': 'Impapuro zose zafashwe ubu',
    'investor.deal.notice.fully_reserved.body':
        "Impapuro zose zihari ubu zafashwe mu kwishyura kw'abandi bashoramari. Uzongere urebe nyuma.",
    'investor.deal.notice.sold_out_pending_settlement.title':
        'Impapuro zose ziyemejwe',
    'investor.deal.notice.sold_out_pending_settlement.body':
        'Abashoramari biyemeje impapuro zose. Kurangiza ntikuranditswe; impapuro zitangwa ari uko amafaranga amaze koherezwa ku kigo.',
    'investor.deal.notice.inventory_unavailable.title':
        'Nta nyandiko ziboneka ubu',
    'investor.deal.notice.inventory_unavailable.body':
        'Nta mpapuro zishobora gufatwa ubu. Uzongere urebe nyuma.',
    'investor.deal.notice.closing_pending_settlement.title':
        'Iki gikorwa cyarafunzwe',
    'investor.deal.notice.closing_pending_settlement.body':
        'Igihe cyarangiye kandi igikorwa kirimo kurangizwa. Amafaranga ugomba gusubizwa agaragara mu gikapu cyawe akimara kwandikwa.',
    'investor.deal.notice.funded.title': 'Imari yose yabonetse',
    'investor.deal.notice.funded.body':
        'Ibyiyemejwe birafunze mu gihe amafaranga yoherezwa ku kigo. Impapuro zitangwa ubwo bwishyu bumaze kwemezwa.',
    'investor.deal.notice.disbursing.title': 'Kwishyura ikigo birakomeje',
    'investor.deal.notice.disbursing.body':
        'Ubwishyu ku kigo ntiburemezwa. Impapuro zitangwa ari uko bwemejwe.',
    'investor.deal.notice.issued.title':
        'Iki gikorwa cyo gushaka imari cyarangiye',
    'investor.deal.notice.issued.body':
        'Ikigo cyarishyuwe kandi impapuro zahawe abashoramari bacyo.',
    'investor.deal.notice.expired.title': 'Iki gikorwa ntikyuzuye mu gihe',
    'investor.deal.notice.expired.body':
        'Buri cyiyemezo cyasubijwe cyose, nta kiguzi.',
    'investor.deal.notice.cancelled.title': 'Ikigo cyahagaritse iki gikorwa',
    'investor.deal.notice.cancelled.body':
        'Buri cyiyemezo cyasubijwe cyose, nta kiguzi.',
    'investor.deal.notice.failed_closing.title':
        'Iki gikorwa cyarangiye nta bwishyu bubaye',
    'investor.deal.notice.failed_closing.body':
        'Amafaranga ntiyoherejwe, bityo buri cyiyemezo cyasubijwe cyose.',
    'investor.deals.paused': 'Byahagaritswe',
    'investor.deal.cap.max': {
        one: 'Kugeza ku rupapuro {count}: {reason}.',
        other: 'Kugeza ku mpapuro {count}: {reason}.',
    },
    'investor.deal.cap.none':
        'Ntushobora gufata izindi mpapuro hano: {reason}.',
    'investor.deal.cap.reason.raise_cap':
        "umupaka w'umushoramari umwe kuri iki gikorwa cyo gukusanya",
    'investor.deal.cap.reason.availability': 'izo ni zo mpapuro zose zisigaye',
    'investor.deal.cap.reason.restriction': 'hari ikumira rikurikizwa',
    'investor.deal.cap.reason.connected_party': "ufitanye isano n'iki kigo",
    'investor.updates.published_photos': 'AMAFOTO YASHYIZWE AHAGARAGARA',
    'investor.checkout.c3.processing': 'Birimo koherezwa…',
    'investor.checkout.c3.hold_ended':
        'Igihe wafatiwe cyarangiye — izi mpapuro zishobora kuba zarekuwe',
    'investor.checkout.c3.hold_left': 'Wafatiwe · hasigaye {time} ngo wemeze',
    'investor.checkout.c3.committed': 'Wiyemeje',
    'investor.checkout.c3.committed_body':
        'Wiyemeje — impapuro zawe zitangwa ikigo kimaze kwishyurwa. Kugeza icyo gihe ni icyiyemezo, si umutungo ufite.',
    'investor.checkout.c3.view_awaiting': 'Reba mu « Bitegereje gutangwa »',
    'investor.checkout.c3.reserved_title': 'Emeza impapuro zawe',
    'investor.checkout.c3.held_amount': 'Afashwe ku aboneka',
    'investor.checkout.c3.release': 'Rekura izi mpapuro',
    'investor.checkout.c3.confirm_fine_print':
        'Kwemeza bishyira amafaranga yafashwe mu cyiyemezo. Ushobora guhagarika nta kiguzi kugeza imari yose ibonetse.',
    'investor.checkout.c3.at_maturity': {
        one: 'Bigaruka mu kwezi {count}',
        other: 'Bigaruka mu mezi {count}',
    },
    'investor.checkout.c3.indicative':
        "Ni ikigereranyo kugeza ufashe: uburenganzira nyabwo bw'impapuro zawe bushyirwaho igihe zifashwe.",
    'investor.checkout.c3.reserve': 'Fata · {amount}',
    'investor.notices.title': 'Imenyesha',
    'investor.notices.unread_count': '{count} bitarasomwa',
    'investor.notices.ago.now': 'Ubu nyine',
    'investor.notices.ago.minutes': 'hashize iminota {count}',
    'investor.notices.ago.hour': 'hashize isaha 1',
    'investor.notices.ago.hours': 'hashize amasaha {count}',
    'investor.notices.ago.day': 'hashize umunsi 1',
    'investor.notices.ago.days': 'hashize iminsi {count}',
    'investor.notices.unread': 'Bitarasomwa',
    'investor.notices.mark_all': 'Byose byasomwe',
    'investor.notices.close': 'Funga imenyesha',
    'investor.notices.loading': 'Imenyesha riri gufunguka',
    'investor.notices.failed': 'Imenyesha ryawe ntiryashoboye gufunguka.',
    'investor.notices.retry': 'Ongera ugerageze',
    'investor.notices.empty': 'Nta menyesha rirahari.',
    'investor.notices.business.deposit_received.title':
        'Amafaranga yashyizwe kuri konti',
    'investor.notices.business.deposit_received.body':
        "{amount} ubu ari mu gikapu cy'ikigo.",
    'investor.notices.business.application_approved.title': 'Ubusabe bwemejwe',
    'investor.notices.business.application_approved.body':
        '{title} yemerewe {amount} mu mezi {months}. Suzuma kandi usinye kugira ngo ikusanyamari ritangazwe.',
    'investor.notices.business.withdrawal_sent.title': 'Ibyabikujwe byoherejwe',
    'investor.notices.business.withdrawal_sent.body':
        '{amount} byoherejwe kuri {account}.',
    'investor.notices.business.withdrawal_returned.title':
        'Ibyabikujwe byagarutse',
    'investor.notices.business.withdrawal_returned.body':
        "Kohereza {amount} kuri {account} ntibyakunze. Byagarutse mu gikapu cy'ikigo.",
    'investor.notices.auditor.accreditation_approved.title':
        'Uruhushya rwemejwe',
    'investor.notices.auditor.accreditation_approved.body':
        "Uruhushya {licence} rwemejwe kugeza ku wa {date}. Ubu ushobora kwakira imirimo y'igenzura.",
    'investor.notices.auditor.accreditation_rejected.title':
        'Uruhushya rwawe rugomba kongera gusuzumwa',
    'investor.notices.auditor.accreditation_rejected.body':
        '{reason} Ohereza icyemezo gikosoye uhereye kuri porofayili yawe.',
    'investor.notices.auditor.accreditation_renewal_due.title':
        'Igihe cyo kuvugurura uruhushya kirageze',
    'investor.notices.auditor.accreditation_renewal_due.body':
        "Uruhushya {licence} ruzarangira ku wa {date}. Ohereza uruhushya ruvuguruye uhereye kuri porofayili yawe kugira ngo ukomeze kwakira imirimo y'igenzura.",
    'investor.notices.auditor.report_reminder.title':
        "Kwibutsa raporo y'igenzura",
    'investor.notices.auditor.report_reminder.body_monthly':
        'Rozine irakwibutsa ko igenzura rya {month} rya {business} rigomba kurangira bitarenze {date}.',
    'investor.notices.auditor.report_reminder.body_flash':
        'Rozine irakwibutsa ko igenzura ryihuse rya {business} rigomba kurangira bitarenze {date}.',
    'investor.notices.support.complaint_answered.title':
        'Rozine yashubije ikirego cyawe',
    'investor.notices.support.complaint_answered.body':
        'Hari igisubizo gishya ku kirego {reference}.',
    'investor.notices.support.complaint_resolved.title': 'Ikirego cyakemutse',
    'investor.notices.support.complaint_resolved.body':
        'Ikirego {reference} cyakemutse. Gifungure usome uko cyakemuwe.',
    'support.chat.title': 'Ubufasha bwa Rozine',
    'support.chat.open': 'Vugana n’ubufasha bwa Rozine',
    'support.chat.clocks':
        'Twemeza ko twakiriye mu masaha 4, tugakemura mu masaha 48',
    'support.chat.reference': 'Nimero {reference}',
    'support.chat.greeting':
        'Tubwire ikitagenze neza. Ubutumwa bwawe butangiza ikirego gifite nimero yacyo. Twemeza ko twacyakiriye mu masaha 4 tukagikemura mu masaha 48, cyangwa tukakubwira impamvu n’igihe.',
    'support.chat.cma':
        'Ushobora kugeza ikirego ku Kigo gishinzwe Isoko ry’Imari n’Imigabane igihe icyo ari cyo cyose.',
    'support.chat.closed': 'Iki kirego cyakemutse.',
    'support.chat.new': 'Ubutumwa bushya',
    'support.chat.message': 'Ubutumwa bwawe',
    'support.chat.placeholder': 'Andika ubutumwa bwawe…',
    'support.chat.send': 'Ohereza',
    'support.chat.list': 'Ibirego byawe',
    'support.chat.opened': 'Cyatanzwe {at}',
    'support.chat.resolution': 'Uko cyakemuwe · {at}',
    'support.chat.status.awaiting': 'Gitegereje igisubizo',
    'support.chat.status.answered': 'Cyashubijwe',
    'support.chat.status.resolved': 'Cyakemutse',
    'investor.notices.auditor.job_offered.title': "Umurimo mushya w'igenzura",
    'investor.notices.auditor.job_offered.body_flash':
        'Igenzura ryihuse kuri {business}, {location}. Emera mbere ya {time}.',
    'investor.notices.auditor.job_offered.body_routine':
        'Igenzura risanzwe kuri {business}, {location}. Emera mbere ya {time}.',
    'investor.notices.business.report_sealed.title':
        "Raporo y'igenzura itegereje kwemezwa",
    'investor.notices.business.report_sealed.body':
        "{auditor} yashyizeho kashe kuri raporo y'igenzura yawe. Yisuzume, hanyuma uyisinyeho cyangwa uyijurire.",
    'investor.notices.business.report_sealed.body_due':
        "{auditor} yashyizeho kashe kuri raporo y'igenzura yawe. Yisinyeho cyangwa uyijurire mbere ya {time}.",
    'investor.notices.business.report_review_closing.title':
        'Igihe cyo gusuzuma kirarangiye vuba',
    'investor.notices.business.report_review_closing.body':
        "Igihe cyo gusinya cyangwa kujurira raporo y'igenzura kirangira saa {time}. Nutagira icyo ukora, izatangazwa uko yashyizweho kashe.",
    'investor.notices.business.reporting_window_open.title':
        "Igenzura ry'ukwezi ryatangiye",
    'investor.notices.business.reporting_window_open.body':
        "Igenzura rya {month} ryatangiye. Tegura inyandiko za konti n'ibitabo by'ukwezi kugira ngo umugenzuzi wawe abisuzume kandi ashyire kashe kuri raporo bitarenze {date}. Hanyuma uyisinye cyangwa uyijurire.",
    'investor.notices.business.raise_expired.title':
        'Ubukangurambaga bwarangiye budashyitse',
    'investor.notices.business.raise_expired.body':
        '{title} yarangiye igihe cyayo kigeze hamaze kwemezwa {committed} kuri {target}. Abashoramari basubijwe amafaranga yabo, nta kiguzi cyakwa.',
    'investor.notices.wallet.deposit_received.title':
        'Amafaranga yashyizwe kuri konti',
    'investor.notices.wallet.deposit_received.body':
        '{amount} ubu ari mu gikapu cyawe.',
    'investor.notices.wallet.withdrawal_sent.title': 'Ibyabikujwe byoherejwe',
    'investor.notices.wallet.withdrawal_sent.body':
        '{amount} byoherejwe kuri {account}.',
    'investor.notices.wallet.withdrawal_returned.title':
        'Ibyabikujwe byagarutse',
    'investor.notices.wallet.withdrawal_returned.body':
        'Kohereza {amount} kuri {account} ntibyakunze. Byagarutse mu gikapu cyawe.',
    'investor.notices.primary.refunded.title': 'Amafaranga yawe yagarutse',
    'investor.notices.primary.refunded.body':
        'Ikusanyamari rya {business} ryarangiye ritageze ku ntego. {amount} yagarutse mu gikapu cyawe, nta mafaranga yakuweho.',
    'investor.notices.primary.raise_funded.title': 'Ikusanyamari ryuzuye',
    'investor.notices.primary.raise_funded.body':
        "Ikusanyamari rya {business} ryageze ku ntego. {amount} yawe y'impapuro {units} ibitswe kugeza amafaranga yoherejwe.",
    'investor.notices.primary.holding_issued.title': 'Ishoramari ryemejwe',
    'investor.notices.primary.holding_issued.body':
        'Impapuro zawe {units} muri {business} zatanzwe: washoye {amount}, witeze {maturity} ku itariki {date}.',
    'investor.notices.business.raise_funded.title': 'Ikusanyamari ryuzuye',
    'investor.notices.business.raise_funded.body':
        '{title} yageze ku ntego yayo ya {principal}. Rozine iri gutegura kohereza amafaranga kuri konti yawe.',
    'investor.notices.business.disbursement_sent.title': 'Amafaranga yoherejwe',
    'investor.notices.business.disbursement_sent.body':
        '{amount} ya {title} yoherejwe kuri {account}.',
    'investor.notices.business.disbursement_sent.body_fee':
        '{amount} ya {title} yoherejwe kuri {account}: ni {principal} zakusanyijwe havuyemo amafaranga ya serivisi ya 3% angana na {fee}.',
    'investor.notices.primary.report_published.title':
        'Raporo y’ukwezi yasohotse',
    'investor.notices.primary.report_published.body':
        '{business} yasohoye raporo yayo ya {month}, yemejwe na {partner}.',
    'investor.notices.security.password_changed.title':
        'Ijambo ry’ibanga ryawe ryahinduwe',
    'investor.notices.security.password_changed.body':
        'Niba atari wowe, hindura ijambo ry’ibanga ubu kandi uvugane n’ubufasha bwa Rozine.',
    'investor.notices.security.new_device.title': 'Igikoresho gishya cyinjiye',
    'investor.notices.security.new_device.body':
        '{device} cyinjiye kuri konti yawe. Niba atari wowe, hindura ijambobanga ubu kandi uhamagare ubufasha bwa Rozine.',
    'investor.notices.security.payout_account_linked.title':
        'Konti yo kwishyurirwaho yahujwe',
    'investor.notices.security.payout_account_linked.body':
        '{account} ubu yahujwe ku kubitsa no kubikuza. Niba atari wowe, hamagara ubufasha bwa Rozine ubu.',
    'investor.notices.security.payout_account_unlinked.title':
        'Konti yo kwishyurirwaho yakuweho',
    'investor.notices.security.payout_account_unlinked.body':
        '{account} ntikigihujwe. Niba atari wowe, hamagara ubufasha bwa Rozine ubu.',
    'investor.notices.verification.approved.title': 'Wemejwe',
    'investor.notices.verification.approved.body':
        'Umwirondoro wawe wemejwe kandi igikapu cyawe cyiteguye gushora imari.',
    'investor.notices.verification.rejected.title':
        'Amakuru y’umwirondoro wawe akeneye kongera kurebwa',
    'investor.notices.verification.rejected.body':
        'Ishami rishinzwe kubahiriza amategeko ntiryashoboye kwemeza amakuru yawe: {reason}',
    'investor.checkout.risks.title': "Mbere y'ishoramari ryawe rya mbere",
    'investor.checkout.risks.lead':
        'Shyira akamenyetso kuri buri nteruro kugira ngo wemeze ko uyumva.',
    'investor.checkout.risks.accept': 'Ndumva izi ngaruka',
    'investor.checkout.risks.failed':
        'Ntitwashoboye kubika igisubizo cyawe. Ongera ugerageze.',
    'investor.checkout.c3.reserve_after_risks':
        "Emeza amagambo y'ingaruka ari hejuru kugira ngo ubike.",
    'investor.checkout.c3.reserve_unavailable': 'Gufata ntibishoboka ubu.',
    'investor.checkout.c3.reserve_fine_print':
        "Gufata bibika impapuro n'amafaranga iminota 5 mu gihe wemeza.",
    'investor.primary.status.confirmed':
        'Wiyemeje — bitangwa nyuma yo kwishyura',
    'investor.primary.status.awaiting_disbursement':
        'Imari yose yabonetse — gutegereza kwishyura',
    'investor.primary.status.in_flight_pending': 'Kwishyura ntikuremezwa',
    'investor.primary.status.in_flight_unknown':
        'Kwishyura ntikuremezwa — turagenzura',
    'investor.primary.status.issued': 'Byatanzwe',
    'investor.primary.status.cancelled': 'Byahagaritswe — byasubijwe',
    'investor.primary.status.expired': 'Ntibyuzuye — byasubijwe',
    'investor.primary.status.failed_closing': 'Byafunzwe — byasubijwe',
    'investor.primary.status_body.confirmed':
        'Igikorwa kiracyafunguye. Ushobora guhagarika nta kiguzi kugeza imari yose ibonetse.',
    'investor.primary.status_body.awaiting_disbursement':
        'Imari yose yabonetse, guhagarika byafunzwe. Impapuro zitangwa kwishyura ikigo bimaze kwemezwa.',
    'investor.primary.status_body.in_flight_pending':
        "Ubwishyu bw'ikigo bwoherejwe ariko ntiburemezwa. Nta kintu gitangwa mbere y'uko bwemezwa.",
    'investor.primary.status_body.in_flight_unknown':
        "Ntiturabasha kumenya niba ubwishyu bw'ikigo bwageze, turagenzura. Ntibwishyuwe, ntibwanze kandi ntibwasubijwe; nta kintu gitangwa mbere yo kwemezwa.",
    'investor.primary.status_body.issued':
        'Ikigo cyarishyuwe kandi impapuro zawe zaratanzwe.',
    'investor.primary.status_body.cancelled':
        'Byahagaritswe na {by}. Igishoro cyawe cyasubijwe cyose, nta kiguzi.',
    'investor.primary.status_body.expired':
        'Igikorwa ntikyuzuye mu gihe. Igishoro cyawe cyasubijwe cyose, nta kiguzi.',
    'investor.primary.status_body.failed_closing':
        'Amafaranga ntiyoherejwe. Igishoro cyawe cyasubijwe cyose, nta kiguzi.',
    'investor.primary.cancelled_by.investor': 'wowe',
    'investor.primary.cancelled_by.business': 'ikigo',
    'investor.primary.cancelled_by.none': 'Rozine',
    'investor.primary.receipt.amount': 'Amafaranga',
    'investor.primary.receipt.recorded': 'Byanditswe',
    'investor.primary.receipt.reference': 'Indango',
    'investor.primary.receipt.confirmation': 'INYEMEZABWISHYU YO KWEMEZA',
    'investor.primary.receipt.refund': 'INYEMEZABWISHYU YO GUSUBIZWA',
    'investor.primary.units': 'Impapuro',
    'investor.primary.units_value': {
        one: 'Urupapuro {count} · {ordinals}',
        other: 'Impapuro {count} · {ordinals}',
    },
    'investor.primary.units_short': {
        one: 'Urupapuro {count}',
        other: 'Impapuro {count}',
    },
    'investor.primary.principal': 'Igishoro',
    'investor.primary.terms': 'Amasezerano',
    'investor.primary.maturity': 'Itariki yo kurangiza',
    'investor.primary.maturity_at_issue': 'Ishyirwaho impapuro zitangwa',
    'investor.primary.versions': 'Amabwiriza · itangazo',
    'investor.primary.view_holding': 'Reba umutungo',
    'investor.primary.awaiting_issue': 'Bitegereje gutangwa',
    'investor.primary.awaiting_issue_note': 'Ibyiyemejwe, si imitungo ubu',
    'investor.primary.rights.title': "Uburenganzira bw'impapuro zawe",
    'investor.primary.rights.instalment': 'Igice',
    'investor.primary.rights.principal': 'Igishoro',
    'investor.primary.rights.return': 'Inyungu',
    'investor.primary.rights.nth': 'Nomero {n}',
    'investor.primary.rights.total_return': 'Inyungu yose',
    'investor.primary.rights.undated':
        "Amatariki yo kwishyura ashyirwaho impapuro zitangwa: irya mbere riba ukwezi kumwe nyuma y'itariki ubwishyu bwakoreweho.",
    'investor.primary.commitment_title': 'Icyiyemezo',
    'investor.primary.back_to_portfolio': 'Garuka ku mutungo',
    'investor.primary.cancel': 'Hagarika icyiyemezo',
    'investor.primary.cancel_title': 'Uhagarika iki cyiyemezo?',
    'investor.primary.cancel_body':
        'Igishoro cyawe gisubira muri aboneka cyose, nta kiguzi. Ntuzongera gutunga izi mpapuro.',
    'investor.primary.cancel_confirm': 'Yego, hagarika usubize',
    'investor.primary.cancel_keep': 'Bigumeho',
    'investor.holding.issue.title': 'Inyandiko yo gutanga',
    'investor.holding.issue.issued_at': 'Byatanzwe',
    'investor.holding.issue.effective_at': 'Ubwishyu bwakozwe',
    'investor.holding.issue.effective_date': 'Itariki ikurikizwa (Kigali)',
    'investor.holding.issue.schedule': 'Ingengabihe',
    'investor.holding.issue.due': 'Itariki',
    'auth.two_factor.recovery_codes_remaining': {
        one: 'Hasigaye kode {count} yo kugarura konti',
        other: 'Hasigaye kode {count} zo kugarura konti',
    },
    'business.audit_cosign.head_title': "Shyira umukono kuri raporo y'igenzura",
    'business.audit_cosign.title': "Shyira umukono kuri raporo y'igenzura",
    'business.audit_cosign.back': 'Subira inyuma',
    'business.audit_cosign.lead':
        "CPA wawe yafunze iyi raporo nyuma y'igenzura ryakorewe aho ukorera. Soma ibyabonetse mbere yo gushyiraho umukono.",
    'business.audit_cosign.kind.monthly': "Raporo y'igenzura ya buri kwezi",
    'business.audit_cosign.kind.flash': "Raporo y'igenzura yihuse",
    'business.audit_cosign.period': 'Igihe',
    'business.audit_cosign.auditor': 'Umugenzuzi',
    'business.audit_cosign.auditor_value': '{name} · uruhushya {licence}',
    'business.audit_cosign.procedure': 'Uburyo bukurikizwa',
    'business.audit_cosign.digest': 'Ikimenyetso cya raporo',
    'business.audit_cosign.digest_short': '{digest}…',
    'business.audit_cosign.seal.title': 'Kashe',
    'business.audit_cosign.seal.valid': 'Kashe ifite agaciro',
    'business.audit_cosign.seal.valid_at': 'Yafunzwe {date}',
    'business.audit_cosign.seal.verify': 'Genzura kashe',
    'business.audit_cosign.seal.unavailable':
        'Kashe ntishobora kugenzurwa ubu.',
    'business.audit_cosign.note_title': 'Icyo umugenzuzi yanditse',
    'business.audit_cosign.findings': 'Ibyabonetse',
    'business.audit_cosign.findings_empty': 'Nta byabonetse byanditswe.',
    'business.audit_cosign.evidence': {
        one: 'Ikimenyetso {count}',
        other: 'Ibimenyetso {count}',
    },
    'business.audit_cosign.sealed_note':
        'Iyi raporo irafunzwe. Nta kintu kuri uru rupapuro kiyihindura.',
    'business.audit_cosign.status.title': 'Imikono',
    'business.audit_cosign.status.count': 'Imikono {signed} kuri {required}',
    'business.audit_cosign.status.signers': 'Abasinya',
    'business.audit_cosign.status.you': 'Wowe',
    'business.audit_cosign.status.signed_on': 'Yasinye · {date}',
    'business.audit_cosign.status.signed': 'Yasinye',
    'business.audit_cosign.status.pending': 'Birategerejwe',
    'business.audit_cosign.due': 'Shyiraho umukono bitarenze {date}',
    'business.audit_cosign.overdue':
        'Byarenze igihe — umukono wagombaga gushyirwaho bitarenze {date}',
    'business.audit_cosign.published': 'Yatangajwe {date}',
    'business.audit_cosign.yours.title': 'Umukono wawe',
    'business.audit_cosign.yours.accept':
        'Nasuzumye ibyabonetse mu igenzura kandi nshyize umukono kuri iyi raporo.',
    'business.audit_cosign.yours.note': 'Incamake yawe (si ngombwa)',
    'business.audit_cosign.yours.note_help':
        "Ibikwa hamwe n'umukono wawe. Ntihindura raporo yafunzwe.",
    'business.audit_cosign.yours.identity':
        'Konti yawe yagenzuwe ni yo isinya. Buri musinyi usabwa ashyiraho umukono we ukwe.',
    'business.audit_cosign.yours.submit': 'Shyiraho umukono',
    'business.audit_cosign.yours.submitting': 'Birimo gusinywa…',
    'business.audit_cosign.yours.signed': 'Washyize umukono kuri iyi raporo.',
    'business.audit_cosign.yours.signed_on': 'Washyizeho umukono {date}.',
    'business.audit_cosign.yours.waiting':
        'Hategerejwe ko {names} ashyiraho umukono. Raporo itangazwa iyo imikono yose isabwa yabonetse.',
    'business.audit_cosign.yours.all_in':
        'Imikono yose isabwa yabonetse. Raporo itangazwa iyo igenzura ryo kuyitangaza rirangiye.',
    'business.audit_cosign.yours.published':
        'Imikono yose isabwa yabonetse kandi raporo yatangajwe.',
    'business.audit_cosign.yours.unavailable':
        'Iyi raporo ntishobora gushyirwaho umukono muri iki gihe.',
    'business.audit_cosign.yours.cannot':
        'Ntushobora gushyira umukono kuri iyi raporo.',
    'business.audit_cosign.refused.with_code': '{reason} ({code})',
    'business.audit_cosign.refused.VERSION_CONFLICT':
        'Imikono kuri iyi raporo yahindutse igihe wayikoragaho. Twazanye verisiyo iheruka — yisuzume wongere ugerageze.',
    'business.audit_cosign.refused.IDEMPOTENCY_CONFLICT':
        "Iki cyifuzo cyakoreshejwe mbere n'andi makuru, bityo ntikongeye koherezwa. Twazanye verisiyo iheruka.",
    'business.audit_cosign.refused.DIGEST_STALE':
        'Raporo wasomye si yo ikiriho ubu. Twayizanye — yisome wongere ugerageze.',
    'business.audit_cosign.refused.MANDATE_STALE':
        "Ububasha bwo gusinya bw'ikigo bwahindutse. Reba abashobora gusinya ubu, hanyuma wongere ugerageze.",
    'business.audit_cosign.refused.ACTION_FORBIDDEN':
        'Ntushobora gukora iki gikorwa kuri ubu bucuruzi.',
    'business.audit_cosign.refused.MANDATE_REQUIRED':
        'Ububasha bwawe bwagenzuwe ntibukwemerera gusinyira ubu bucuruzi.',
    'business.audit_cosign.refused.NOT_FOUND': 'Iyi raporo ntikikugeraho.',
    'business.audit_cosign.refused.denied':
        'Uburenganzira bwawe bwahindutse. Subira kuri porogaramu zawe wongere ugerageze.',
    'business.audit_cosign.refused.failed':
        'Ibi ntibyanditswe. Reba raporo uko imeze ubu wongere ugerageze.',
    'business.audit_prep.seal_by':
        'CPA wawe afunga raporo ya {month} bitarenze {seal}. Ntushobora kuyitangiza cyangwa kuyihindura.',
    'business.audit_cosign.count': '{count}/{limit}',
    'business.audit_cosign.published_auto':
        "Yatangajwe mu buryo bwikora nyuma y'amasaha 24",
    'business.audit_cosign.dispute.open': 'Tanga ubujurire',
    'business.audit_cosign.dispute.submit': 'Ohereza ubujurire',
    'business.audit_cosign.dispute.submitting': 'Biroherezwa…',
    'business.audit_cosign.dispute.cancel': 'Hagarika',
    'business.audit_cosign.dispute.files_add': 'Ongeraho amadosiye',
    'business.audit_cosign.dispute.file_remove': 'Kuramo {name}',
    'audit.verify_seal.head_title': "Genzura kashe y'igenzura",
    'audit.verify_seal.title': "Igenzura rya kashe y'igenzura",
    'audit.verify_seal.lead':
        "Reba niba raporo y'igenzura ya Rozine ifite kashe ifite agaciro.",
    'audit.verify_seal.valid': 'Kashe yagenzuwe',
    'audit.verify_seal.valid_body': 'Iki kimenyetso gihuye na raporo yafunzwe.',
    'audit.verify_seal.unavailable': 'Iyi kashe ntishobora kugenzurwa ubu',
    'audit.verify_seal.unavailable_body': 'Ongera ugerageze nyuma.',
    'audit.verify_seal.report_id': 'Nimero ya raporo',
    'audit.verify_seal.digest': 'Ikimenyetso cya raporo',
    'audit.verify_seal.amends': 'Ikosora raporo {id}',
    'audit.verify_seal.amended_by': 'Yakosowe na raporo {id}',
    'audit.verify_seal.scope':
        "Hano herekanwa gusa nimero ya raporo, ikimenyetso cyayo n'igisubizo cy'igenzura rya kashe.",
    'business.audit_cosign.refused.AUDIT_REPORT_AMENDED':
        'Umugenzuzi yakosoye iyi raporo, bityo ntishobora gushyirwaho umukono. Raporo ikosowe izakugezwaho ngo uyishyireho umukono imaze gufungwa.',
    'common.file_size.kb': '{size} KB',
    'common.file_size.mb': '{size} MB',
    'common.file_size.kind': '{kind} · {size}',
    'common.proof_file.download': 'Kuramo {name}',
    'common.proof_file.download_short': 'Kuramo',
    'business.audit_cosign.published_staff':
        "Byatangajwe n'abakozi ba Rozine {date}",
    'business.audit_cosign.yours.published_auto':
        "Byatangajwe mu buryo bwikora nyuma y'amasaha 24: nta wabyemeje cyangwa ngo abijurire ku gihe. Nta mukono wanditswe.",
    'business.audit_cosign.yours.published_staff':
        'Abakozi ba Rozine bakemuye ubujurire kandi batangaza raporo. Nta mukono wanditswe.',
    'business.audit_cosign.window.title': "Igihe cyo gusuzuma cy'amasaha 24",
    'business.audit_cosign.window.left': 'Hasigaye {time}',
    'business.audit_cosign.window.body':
        "Iyo raporo y'igenzura imaze gushyirwaho kashe muri porogaramu yawe, ufite amasaha 24 yo kuyemeza cyangwa gutanga ubujurire buherekejwe n'ibimenyetso.",
    'business.audit_cosign.window.delivered':
        'Yageze muri porogaramu yawe {date}',
    'business.audit_cosign.window.due':
        'Emeza cyangwa ujurire bitarenze {date}',
    'business.audit_cosign.window.ended': "Igihe cy'amasaha 24 cyarangiye.",
    'business.audit_cosign.window.auto':
        'Raporo zitasinywe zemezwa mu buryo bwikora iyo igihe kirangiye.',
    'business.audit_cosign.dispute.intro':
        "Garagaza ibyo ujuririra mu byagaragajwe n'ibimenyetso byawe. Ubujurire bwawe ntibuhindura raporo ifite kashe, kandi buhagarika igihe cy'amasaha 24 mu gihe CPA ibusuzuma.",
    'business.audit_cosign.dispute.proof_rule':
        'Ongeraho inyandiko isobanura, nibura dosiye imwe, cyangwa byombi.',
    'business.audit_cosign.dispute.supporting': 'Inyandiko isobanura',
    'business.audit_cosign.dispute.supporting_help':
        'Inyandiko isanzwe, inyuguti zitarenze 1,000.',
    'business.audit_cosign.dispute.files': "Dosiye z'ibimenyetso",
    'business.audit_cosign.dispute.files_help':
        'Dosiye zitarenze {limit}: PDF, JPEG cyangwa PNG, buri imwe itarenze 10 MB.',
    'business.audit_cosign.dispute.file_type':
        '{name} ntiyongeweho: hemerwa gusa dosiye za PDF, JPEG cyangwa PNG.',
    'business.audit_cosign.dispute.file_size':
        '{name} ntiyongeweho: buri dosiye ntigomba kurenza 10 MB.',
    'business.audit_cosign.dispute.file_limit':
        '{name} ntiyongeweho: ubujurire bushobora kugira dosiye zitarenze {limit}.',
    'business.audit_cosign.disputed.under_review.title':
        'Ubujurire burimo gusuzumwa',
    'business.audit_cosign.disputed.under_review.body':
        'Igihe cyahagaritswe. CPA irimo gusuzuma ibimenyetso byawe.',
    'business.audit_cosign.disputed.escalated.title':
        'Abakozi ba Rozine barimo gusuzuma',
    'business.audit_cosign.disputed.escalated.body':
        'Abakozi ba Rozine barimo gusuzuma ubujurire bwawe. Igihe gikomeza guhagarara kandi raporo ntitangazwa muri icyo gihe.',
    'business.audit_cosign.disputed.amendment_required.title':
        'Hakenewe ivugurura',
    'business.audit_cosign.disputed.amendment_required.body':
        "Umugenzuzi agomba kuvugurura raporo; uzahabwa igihe gishya cy'amasaha 24. Igihe gikomeza guhagarara kugeza icyo gihe.",
    'business.audit_cosign.disputed.amended.title': 'Raporo yavuguruwe',
    'business.audit_cosign.disputed.amended.body':
        "Umugenzuzi yavuguruye raporo. Raporo ivuguruye ifite igihe cyayo cy'amasaha 24.",
    'business.audit_cosign.disputed.upheld.title':
        "Byatangajwe n'abakozi ba Rozine",
    'business.audit_cosign.disputed.upheld.body':
        'Abakozi ba Rozine basuzumye ubujurire bwawe, bagumishaho ibyagaragajwe kandi batangaza raporo. Nta mukono wanditswe.',
    'business.audit_cosign.disputed.resolved.title': 'Ubujurire bwarangiye',
    'business.audit_cosign.disputed.resolved.body': 'Ubu bujurire bwarangiye.',
    'business.audit_cosign.disputed.open_amendment':
        'Fungura raporo ivuguruye ({report})',
    'business.audit_cosign.disputed.record_title': 'Ubujurire bwawe',
    'business.audit_cosign.disputed.submitted': 'Bwatanzwe {date}',
    'business.audit_cosign.disputed.supporting_text':
        'Inyandiko yawe isobanura',
    'business.audit_cosign.disputed.files': "Dosiye zawe z'ibimenyetso",
    'auditor.sealed.body_disputed':
        'Raporo ifite kashe. {party} yarayijuririye, bityo igihe cyo kuyisuzuma cyahagaritswe kandi nta kintu gitangazwa mu gihe ubujurire bugikomeza.',
    'auditor.sealed.dispute.title': "Ubujurire bw'ikigo",
    'auditor.sealed.dispute.status.under_review': 'Burimo gusuzumwa',
    'auditor.sealed.dispute.status.escalated': 'Kuri abakozi ba Rozine',
    'auditor.sealed.dispute.status.resolved': 'Bwakemutse',
    'auditor.sealed.dispute.submitted': 'Bwatanzwe {date} · {time}',
    'auditor.sealed.dispute.guide.under_review':
        'Banza usuzume ibimenyetso. Hanyuma utangire ivugurura rifitanye isano, cyangwa ugumishaho ibyo wagaragaje.',
    'auditor.sealed.dispute.guide.escalated':
        'Abakozi ba Rozine barimo gusuzuma ubu bujurire. Gushyira kashe ku ivugurura ntibizabukemura keretse abakozi banditse ko ivugurura rikenewe.',
    'auditor.sealed.dispute.guide.amendment_required':
        "Abakozi ba Rozine basabye ivugurura. Numara gushyira kashe ku ivugurura rifitanye isano, ikigo kizahabwa igihe gishya cy'amasaha 24.",
    'auditor.sealed.dispute.supporting_text': "Inyandiko isobanura y'ikigo",
    'auditor.sealed.dispute.files': "Dosiye z'ibimenyetso",
    'auditor.sealed.dispute.outcome.amendment_required':
        'Umwanzuro: hakenewe ivugurura',
    'auditor.sealed.dispute.outcome.amended': 'Umwanzuro: yavuguruwe',
    'auditor.sealed.dispute.outcome.upheld':
        'Umwanzuro: ibyagaragajwe byagumishijweho',
    'auditor.sealed.dispute.uphold': 'Gumishaho ibyagaragajwe',
    'auditor.dispute_uphold.lead':
        'Impamvu yawe yoherezwa ku bakozi ba Rozine, basuzuma ubujurire bwa {business}. Kugumishaho ibyagaragajwe ntibitangaza raporo.',
    'auditor.dispute_uphold.label': 'Impamvu yawe (irakenewe)',
    'auditor.dispute_uphold.placeholder':
        "Vuga, mu buryo bw'ukuri, impamvu ibyagaragajwe bigumaho nyuma yo gusuzuma ibimenyetso.",
    'auditor.dispute_uphold.submit': 'Ohereza ku bakozi ba Rozine',
    'business.audit_cosign.refused.REPORT_WINDOW_CLOSED':
        "Igihe cyo gusuzuma cy'amasaha 24 cyarangiye, bityo iyi raporo ntigishobora kwemezwa cyangwa kujuririrwa. Twayifunguye uko imeze ubu.",
    'business.audit_cosign.refused.REPORT_REVIEW_CLOSED':
        'Iyi raporo yamaze kujuririrwa, iri kwa bakozi ba Rozine cyangwa yaratangajwe, bityo ntigishobora kwemezwa cyangwa kujuririrwa. Twayifunguye uko imeze ubu.',
    'auditor.sealed.body_published_auto':
        "Byatangajwe mu buryo bwikora nyuma y'amasaha 24, ku wa {date}. Ikigo nticyayisinyeho.",
    'auditor.sealed.body_published_staff':
        "Byatangajwe n'abakozi ba Rozine ku wa {date}. Ikigo nticyayisinyeho.",
    'auditor.sealed.cosign.not_signed': 'Ntiyasinywe',
    'business.audit_cosign.head_title_read': "Raporo y'igenzura",
    'business.audit_cosign.lead_read':
        "CPA wawe yafunze iyi raporo nyuma y'igenzura ryakorewe aho ukorera. Ibi ni ibyo yabonye.",
    'business.audit_cosign.heading.signed':
        "Raporo y'igenzura, yasinywe kandi iratangazwa",
    'business.audit_cosign.heading.auto_approved':
        "Raporo y'igenzura, yatangajwe mu buryo bwikora nyuma y'amasaha 24",
    'business.audit_cosign.heading.staff_resolved':
        "Raporo y'igenzura, yatangajwe n'abakozi ba Rozine",
    'business.audit_cosign.heading.disputed':
        "Raporo y'igenzura, ubujurire burimo gusuzumwa",
    'business.audit_cosign.heading.amended':
        "Raporo y'igenzura, yavuguruwe nyuma y'ubujurire bwawe",
    'business.audit_cosign.heading.dispute_closed':
        "Raporo y'igenzura, ubujurire bwarangiye",
    'business.audit_cosign.heading.unavailable': "Raporo y'igenzura",
    'business.audit_cosign.disputed.note.cpa': 'Impamvu ya CPA',
    'business.audit_cosign.disputed.note.staff':
        "Icyitonderwa cy'abakozi ba Rozine",
    'business.audit_cosign.disputed.note.unknown': "Icyitonderwa cy'isuzuma",
    'auditor.sealed.dispute.note.cpa': 'Impamvu yawe yo kugumishaho',
    'auditor.sealed.dispute.note.staff': "Icyitonderwa cy'abakozi ba Rozine",
    'auditor.sealed.dispute.note.unknown': "Icyitonderwa cy'isuzuma",
    'business.campaign.servicing.state.current': 'Kwishyura · ku gihe',
    'business.campaign.servicing.state.due_today':
        'Kwishyura bigomba uyu munsi',
    'business.campaign.servicing.state.overdue': 'Kwishyura byarengeje igihe',
    'business.campaign.servicing.state.repaid': 'Byishyuwe byose',
    'business.campaign.servicing.state.defaulted': 'Byananiranye kwishyurwa',
    'business.campaign.servicing.dpd': {
        one: "Umunsi {count} w'ubukererwe",
        other: "Iminsi {count} y'ubukererwe",
    },
    'business.campaign.servicing.restriction.ARREARS':
        'Hari ibirarane kuva ku wa {date}. Kugurisha iyi nyandiko byahagaritswe kugeza ubwishyu bwakererewe bwakiriwe kandi bugahuzwa.',
    'business.campaign.servicing.restriction.RESTRICTION_ACTIVE':
        'Hari ikumira kuva ku wa {date}. Kwishyura biracyakirwa.',
    'business.campaign.servicing.payments': '{made} kuri {total}',
    'business.campaign.servicing.total': 'Igiteranyo cyo kwishyura',
    'business.campaign.servicing.instalments_left': {
        one: 'Hasigaye igice {count}',
        other: 'Hasigaye ibice {count}',
    },
    'business.campaign.servicing.next_title': 'Igice gikurikiraho',
    'business.campaign.servicing.next_due':
        'Igice {index} · kigomba kwishyurwa ku wa {date}',
    'business.campaign.servicing.principal': 'Igishoro',
    'business.campaign.servicing.return': 'Inyungu',
    'business.campaign.servicing.next_total': 'Igiteranyo kigomba kwishyurwa',
    'business.campaign.servicing.open': 'Fungura ibyo kwishyura',
    'business.campaign.servicing.repaid':
        'Ibice byose byishyuwe: {amount} byishyuwe bitarenze ku wa {date}.',
    'investor.servicing.title': 'Kwishyura',
    'investor.servicing.state.current': 'Ku gihe',
    'investor.servicing.state.due_today': 'Kwishyura bigomba uyu munsi',
    'investor.servicing.state.overdue': 'Kwishyura byarengeje igihe',
    'investor.servicing.state.repaid': 'Byishyuwe byose',
    'investor.servicing.state.defaulted': 'Byananiranye kwishyurwa',
    'investor.servicing.dpd': {
        one: "Umunsi {count} w'ubukererwe",
        other: "Iminsi {count} y'ubukererwe",
    },
    'investor.servicing.restriction.ARREARS':
        'Hari ibirarane kuva ku wa {date}',
    'investor.servicing.restriction.DEFAULT':
        'Byananiranye kwishyurwa kuva ku wa {date}',
    'investor.servicing.restriction.DISPUTED': 'Bijuririrwa kuva ku wa {date}',
    'investor.servicing.restriction.RESTRICTION_ACTIVE':
        'Bikumiriwe kuva ku wa {date}',
    'investor.servicing.restriction.NOTE_INELIGIBLE':
        'Inyandiko itemewe kuva ku wa {date}',
    'investor.servicing.received.principal': 'Igishoro cyakiriwe',
    'investor.servicing.received.return': 'Inyungu yakiriwe',
    'investor.servicing.received.late_fees': "Amande z'ubukererwe zakiriwe",
    'investor.servicing.received.fees': 'Amafaranga ya Rozine',
    'investor.servicing.received.net': "Ayakiriwe nyuma y'amafaranga",
    'investor.servicing.outstanding_principal': 'Igishoro gisigaye',
    'investor.servicing.remaining_projected': 'Ibiteganyijwe bisigaye',
    'investor.servicing.projection': 'Biteganyijwe, ntibyemejwe',
    'investor.servicing.next_payment': 'Ubwishyu bukurikiraho',
    'investor.servicing.next_payment_value': '{amount} · {date}',
    'investor.servicing.basis': 'Ishingiro',
    'investor.servicing.basis_for': 'Ishingiro rya {figure}',
    'investor.servicing.instalments': 'Ibice byo kwishyura',
    'investor.servicing.instalment':
        'Igice {index} · kigomba kwishyurwa ku wa {date}',
    'investor.servicing.instalment_status.upcoming': 'Kizaza',
    'investor.servicing.instalment_status.due': 'Kigomba kwishyurwa',
    'investor.servicing.instalment_status.overdue': 'Cyarengeje igihe',
    'investor.servicing.instalment_status.processing':
        'Kiri gutunganywa · ntikirakugeraho',
    'investor.servicing.instalment_status.partially_paid': 'Cyishyuwe igice',
    'investor.servicing.instalment_status.paid': 'Cyishyuwe',
    'investor.servicing.entitled':
        'Umugabane wawe: igishoro {principal} · inyungu {return}',
    'investor.servicing.paid':
        'Wishyuwe: igishoro {principal} · inyungu {return}',
    'investor.servicing.payout_net': "Ubwishyu {amount} nyuma y'amafaranga",
    'investor.servicing.late_fees.title':
        "Amande z'ubukererwe kuri iri shoramari",
    'investor.servicing.late_fees.line': 'Igice {index} · {step} · {rate}%',
    'investor.servicing.late_fees.step.due_date': 'Itariki yo kwishyura',
    'investor.servicing.late_fees.step.day_7': 'Umunsi wa 7',
    'investor.servicing.late_fees.step.day_30': 'Umunsi wa 30',
    'investor.servicing.late_fees.status.projected': 'Biteganyijwe',
    'investor.servicing.late_fees.status.assessed': 'Yaciwe · ntiyakusanyijwe',
    'investor.servicing.late_fees.status.partially_collected':
        'Yakusanyijwe igice',
    'investor.servicing.late_fees.status.collected': 'Yakusanyijwe',
    'investor.servicing.late_fees.status.waived': 'Yakuweho',
    'investor.servicing.late_fees.applies': 'Itangira ku wa {date}',
    'investor.servicing.late_fees.assessed': 'Umugabane wawe',
    'investor.servicing.late_fees.collected': 'Byakusanyijwe',
    'investor.servicing.late_fees.outstanding': 'Bitarakusanywa',
    'investor.servicing.late_fees.terms': 'Amabwiriza',
    'investor.servicing.late_fees.collection_only':
        'Yishyurwa gusa iyo yakusanyijwe',
    'investor.servicing.late_fees.not_guaranteed': 'Ntiyishingiwe na Rozine',
    'investor.servicing.late_fees.payout': 'Reba ubwishyu',
    'investor.servicing.late_fees.total.assessed': 'Umugabane wawe wose',
    'investor.servicing.late_fees.total.collected': 'Byakusanyijwe',
    'investor.servicing.late_fees.total.outstanding': 'Bitarakusanywa',
    'investor.servicing.late_fees.disclosure':
        "Amande z'ubukererwe uzishyurwa gusa iyo n'igihe ikigo kizishyuye. Rozine ntiyazishingira. Itangazo {version}.",
    'investor.servicing.payouts.title': 'Ubwishyu wahawe',
    'investor.servicing.payouts.empty': 'Nta bwishyu burahabwa.',
    'investor.servicing.payouts.row':
        'Igice {index} · cyashyizwe kuri konti ku wa {date}',
    'investor.servicing.payouts.gross': 'Mbumbe',
    'investor.servicing.payouts.fee': '{name} ({rate}%)',
    'investor.servicing.payouts.fee_code.INVESTOR_REPAYMENT_FEE':
        'Amafaranga yo kwishyura',
    'investor.servicing.payouts.fee_code.PLUS_EARNINGS_FEE':
        'Amafaranga ku nyungu',
    'investor.servicing.payouts.net': 'Ayageze ku gikapu cyawe',
    'investor.servicing.payouts.receipt': 'Inyemezabwishyu {reference}',
    'investor.servicing.exit.title': 'Uburyo bwo gusohoka',
    'investor.servicing.exit.unavailable':
        'Kugurisha iri shoramari ntibishoboka.',
    'investor.servicing.exit.cause.FEATURE_DISABLED':
        'Kugurisha ntibiratangira',
    'investor.servicing.exit.cause.MARKET_HALTED': 'Isoko ryahagaritswe',
    'investor.servicing.exit.cause.NOTE_HALTED': 'Iyi nyandiko yahagaritswe',
    'investor.servicing.exit.cause.ARREARS': 'Ikigo gifite ibirarane',
    'investor.servicing.exit.cause.DEFAULT': 'Inyandiko yananiranye kwishyurwa',
    'investor.servicing.exit.cause.DISPUTED': 'Inyandiko irajuririrwa',
    'investor.servicing.exit.cause.RESTRICTION_ACTIVE': 'Hari ikumira',
    'investor.servicing.exit.cause.REPORT_STALE': 'Raporo iheruka ishaje',
    'investor.servicing.exit.cause.NO_AVAILABLE_UNITS':
        'Nta bice bihari byo kugurisha',
    'investor.servicing.exit.cause.NOTE_INELIGIBLE': 'Inyandiko ntiyemewe',
    'investor.servicing.exit.record_date': "Itariki y'ibarura ikurikira {date}",
    'investor.earnings.title': 'Inyungu',
    'investor.earnings.invested': 'Byashowe',
    'investor.earnings.outstanding_principal': 'Igishoro gisigaye',
    'investor.earnings.realised': 'Byabonetse · byageze ku gikapu cyawe',
    'investor.earnings.principal_back': 'Igishoro cyagarutse',
    'investor.earnings.return': 'Inyungu',
    'investor.earnings.late_fees': "Amande z'ubukererwe",
    'investor.earnings.fees': 'Amafaranga ya Rozine',
    'investor.earnings.net_return': "Inyungu nyuma y'amafaranga",
    'investor.earnings.this_month': "Uku kwezi, nyuma y'amafaranga",
    'investor.earnings.avg_monthly':
        "Impuzandengo y'ukwezi, nyuma y'amafaranga",
    'investor.earnings.projected': 'Biteganyijwe',
    'investor.earnings.remaining_return': 'Inyungu iteganyijwe isigaye',
    'investor.earnings.next_3m': 'Amezi 3 ari imbere',
    'investor.earnings.next_payout': 'Ubwishyu bukurikiraho {amount} · {date}',
    'admin.nav.repayments': 'Ibyishyuwe',
    'admin.section.repayments.title': 'Ibyishyuwe',
    'admin.section.repayments.subtitle':
        'Ubwishyu bwakiriwe ku nyandiko, buhujwe hakurikijwe urugero rwemewe na politiki kandi bugabanywa na sisitemu nkuru. Ibidasanzwe bikomeza guhagarikwa.',
    'admin.section.repayments.search':
        'Shakisha ukoresheje ikigo, inyandiko cyangwa indango…',
    'admin.repayments.title': 'Ibyishyuwe',
    'admin.repayments.table': 'Ibyishyuwe',
    'admin.repayments.older': 'Ibyishyuwe bya kera',
    'admin.repayments.empty_title': 'Nta byishyuwe biraboneka',
    'admin.repayments.empty_body':
        'Ubwishyu bugaragara hano iyo ikigo cyishyuye.',
    'admin.repayments.count.due_today': {
        one: '{count} bigomba uyu munsi',
        other: '{count} bigomba uyu munsi',
    },
    'admin.repayments.count.overdue': {
        one: '{count} byarengeje igihe',
        other: '{count} byarengeje igihe',
    },
    'admin.repayments.count.exceptions': {
        one: 'Ikidasanzwe {count}',
        other: 'Ibidasanzwe {count}',
    },
    'admin.repayments.col.reference': "Indango y'ubwishyu",
    'admin.repayments.col.source': 'Inkomoko',
    'admin.repayments.dpd': "Iminsi y'ubukererwe",
    'admin.repayments.received_at': 'Byakiriwe',
    'admin.repayments.state.received': 'Byakiriwe · biri kugabanywa',
    'admin.repayments.state.allocated': 'Byagabanyijwe · byahujwe',
    'admin.repayments.state.exception': 'Ikidasanzwe · byahagaritswe',
    'admin.repayments.source.wallet': "Igikapu cy'ikigo",
    'admin.repayments.source.inbound_receipt': 'Ubwishyu bwinjiye',
    'admin.repayments.drawer_label': 'Ubwishyu {reference}',
    'admin.repayments.amount': 'Amafaranga',
    'admin.repayments.dpd_at_receipt': "Iminsi y'ubukererwe igihe cyakirwa",
    'admin.repayments.source.title': 'Inkomoko',
    'admin.repayments.source.kind': 'Biturutse',
    'admin.repayments.source.wallet_entry': 'Icyanditswe mu gikapu',
    'admin.repayments.source.wallet_note':
        "Ni igikorwa cy'imbere mu gitabo cy'ibaruramari: nta mutanga serivisi ubirimo kandi nta ntambwe y'abakozi ikenewe.",
    'admin.repayments.source.receipt': 'Ubwishyu bwinjiye',
    'admin.repayments.provider.state': 'Umutanga serivisi',
    'admin.repayments.provider.requery_note':
        'Kongera kubaza umutanga serivisi bireba icyo gikorwa kimwe. Ntibyongera gukusanya amafaranga. Isuzuma riteganyijwe na ryo rirabaza.',
    'admin.repayments.reconciliation.title': 'Ihuza',
    'admin.repayments.reconciliation.unreconciled': 'Ntibirahuzwa',
    'admin.repayments.reconciliation.matched': 'Birahura · byahujwe',
    'admin.repayments.reconciliation.exception':
        'Ikidasanzwe · byahagaritswe, ntibyahujwe',
    'admin.repayments.reconciliation.expected': 'Byari byitezwe',
    'admin.repayments.reconciliation.observed': 'Byabonetse',
    'admin.repayments.reconciliation.difference': 'Ikinyuranyo',
    'admin.repayments.reconciliation.tolerance': 'Urugero rwemewe',
    'admin.repayments.reconciliation.checked_at': 'Byagenzuwe ku wa',
    'admin.repayments.reconciliation.policy': 'Politiki',
    'admin.repayments.reconciliation.blocked':
        "Ubu bwishyu bwahagaritswe: ihuza ryabonye ikidasanzwe. Nta cyanditswe cyangwa ngo kigaragazwe nk'icyahujwe, kandi kugikemura ntibirashoboka.",
    'admin.repayments.servicing.title': 'Imiterere yo kwishyura inyandiko',
    'admin.repayments.servicing.before': "Mbere y'ubu bwishyu",
    'admin.repayments.servicing.after': 'Nyuma yo kugabanya',
    'admin.repayments.servicing.not_posted': 'Ntibirandikwa.',
    'admin.repayments.servicing.state': 'Imiterere',
    'admin.repayments.servicing.status.current': 'Ku gihe',
    'admin.repayments.servicing.status.due_today': 'Bigomba uyu munsi',
    'admin.repayments.servicing.status.overdue': 'Byarengeje igihe',
    'admin.repayments.servicing.status.repaid': 'Byishyuwe',
    'admin.repayments.servicing.status.defaulted': 'Byananiranye',
    'admin.repayments.component.principal': 'Igishoro',
    'admin.repayments.component.return': 'Inyungu',
    'admin.repayments.component.late_fees': "Amande z'ubukererwe",
    'admin.repayments.component.total': 'Ibisigaye',
    'admin.repayments.allocation.title': 'Igabanya',
    'admin.repayments.allocation.pending':
        'Ntibiragabanywa: ubwishyu bwanditswe ariko igabanya ntirandikwa.',
    'admin.repayments.allocation.balanced': 'Biringaniye',
    'admin.repayments.allocation.unbalanced': 'Ntibiringaniye',
    'admin.repayments.allocation.receipt': 'Ubwishyu {amount}',
    'admin.repayments.allocation.paid': 'Ibyo ubwishyu bwishyuye',
    'admin.repayments.allocation.distributed': 'Aho bwagiye',
    'admin.repayments.allocation.col.kind': 'Umurongo',
    'admin.repayments.allocation.col.account': 'Konti',
    'admin.repayments.allocation.col.amount': 'Amafaranga',
    'admin.repayments.allocation.kind.principal': 'Igishoro',
    'admin.repayments.allocation.kind.return': 'Inyungu',
    'admin.repayments.allocation.kind.late_fee': "Amande y'ubukererwe",
    'admin.repayments.allocation.kind.investor_gross': 'Abashoramari, mbumbe',
    'admin.repayments.allocation.kind.investor_fee':
        "Amafaranga y'abashoramari",
    'admin.repayments.allocation.kind.investor_net':
        "Abashoramari, nyuma y'amafaranga",
    'admin.repayments.allocation.kind.investor_late_fee':
        'Abashoramari, amande',
    'admin.repayments.allocation.kind.psp_fee': 'Amafaranga ya PSP',
    'admin.repayments.allocation.kind.unapplied': 'Bitakoreshejwe',
    'admin.repayments.allocation.instalment': '· igice {index}',
    'admin.repayments.allocation.holders': {
        one: '· ishoramari {count}',
        other: '· amashoramari {count}',
    },
    'admin.repayments.allocation.entitlements': {
        one: "Byagabanyijwe ishoramari {count} hakurikijwe igisigara kinini, itariki y'ibarura {date}.",
        other: "Byagabanyijwe amashoramari {count} hakurikijwe igisigara kinini, itariki y'ibarura {date}.",
    },
    'admin.repayments.allocation.posted_at': 'Byanditswe ku wa {at}',
    'admin.repayments.allocation.ledger': 'Fungura ibyanditswe',
    'admin.repayments.receipts': 'Inyemezabwishyu',
    'admin.repayments.business': 'Fungura ikigo',
    'admin.repayments.trail': "Amateka y'ubwishyu",
    'admin.repayments.requery_body':
        'Ibi bibaza umutanga serivisi ku bwishyu bumwe bwinjiye. Ntibyongera gukusanya amafaranga.',
    'admin.nav.book': 'Inyandiko zikora',
    'admin.nav.exceptions': 'Ibidasanzwe',
    'admin.section.book.title': 'Inyandiko zikora',
    'admin.section.book.subtitle':
        'Buri nyandiko ikora n’uko ihagaze, nk’uko sisitemu nkuru ibyandika. Gusoma gusa.',
    'admin.section.book.search':
        'Shakisha ukoresheje ubucuruzi, inyandiko cyangwa nimero…',
    'admin.section.exceptions.title': 'Ibidasanzwe',
    'admin.section.exceptions.subtitle':
        'Ibirarane, ibyahagaritswe n’ibinyuranyo, buri kimwe gifite igihe kimaze n’ugikurikirana kugeza gikemutse. Gusoma gusa.',
    'admin.section.exceptions.search':
        'Shakisha ukoresheje ubucuruzi cyangwa indango…',
    'admin.book.title': 'Inyandiko zikora',
    'admin.book.caption':
        'Uko kwishyura guhagaze n’iminsi y’ubukererwe nk’uko sisitemu nkuru ibyandika. Nta kintu hano gihindura inyandiko.',
    'admin.book.table': 'Inyandiko zikora',
    'admin.book.filter': 'Tandukanya ukurikije uko kwishyura guhagaze',
    'admin.book.stats.live_notes': 'Inyandiko zikora',
    'admin.book.stats.principal_outstanding': 'Igishoro gisigaye',
    'admin.book.stats.due_today': 'Bigomba uyu munsi',
    'admin.book.stats.overdue': 'Byarengeje igihe',
    'admin.book.chip.all': 'Byose',
    'admin.book.col.note_id': 'Nimero y’inyandiko',
    'admin.book.col.note': 'Ubucuruzi · inyandiko',
    'admin.book.col.principal': 'Igishoro gisigaye',
    'admin.book.col.next_due': 'Igihe gikurikira',
    'admin.book.col.dpd': 'Iminsi y’ubukererwe',
    'admin.book.col.health': 'Uko ihagaze',
    'admin.book.nothing_due': 'Nta cyateganyijwe',
    'admin.book.open_named': 'Fungura {note}',
    'admin.book.more': 'Izindi nyandiko',
    'admin.book.empty_title': 'Nta nyandiko ikora',
    'admin.book.empty_body':
        'Inyandiko igaragara hano iyo amafaranga yayo amaze gutangwa no kwishyura bigatangira.',
    'admin.book.filtered_title': 'Nta nyandiko ihuye n’iri tandukanya',
    'admin.book.filtered_body': 'Hitamo Byose urebe inyandiko zose.',
    'admin.exceptions.title': 'Ibidasanzwe bifunguye',
    'admin.exceptions.caption':
        'Buri kidasanzwe kiguma hano, gifite igihe kimaze, kugeza gikemutse. Kugena ugikurikirana, kukizamura no kugikemura ntibiraboneka.',
    'admin.exceptions.table': 'Ibidasanzwe bifunguye',
    'admin.exceptions.filter': 'Tandukanya ukurikije ubwoko',
    'admin.exceptions.count.open': {
        one: '{count} gifunguye',
        other: '{count} bifunguye',
    },
    'admin.exceptions.count.unassigned': {
        one: '{count} nta ugikurikirana',
        other: '{count} nta ubikurikirana',
    },
    'admin.exceptions.chip.all': 'Byose',
    'admin.exceptions.kind.arrears': 'Ibirarane',
    'admin.exceptions.kind.halt': 'Byahagaritswe',
    'admin.exceptions.kind.variance': 'Ikinyuranyo',
    'admin.exceptions.kind.audit': 'Igenzura ryatinze',
    'admin.exceptions.kind.complaint': 'Ikirego',
    'admin.exceptions.col.reference': 'Indango',
    'admin.exceptions.col.kind': 'Ubwoko',
    'admin.exceptions.col.subject': 'Ikidasanzwe',
    'admin.exceptions.col.amount': 'Amafaranga',
    'admin.exceptions.col.owner': 'Igihe · ugikurikirana',
    'admin.exceptions.col.open': 'Dosiye',
    'admin.exceptions.age': {
        one: 'Kimaze umunsi {count}',
        other: 'Kimaze iminsi {count}',
    },
    'admin.exceptions.dpd': 'Iminsi {count} y’ubukererwe',
    'admin.exceptions.open_named': 'Fungura {reference}',
    'admin.exceptions.more': 'Ibidasanzwe bya kera',
    'admin.exceptions.empty_title': 'Nta kidasanzwe gifunguye',
    'admin.exceptions.empty_body':
        'Ibirarane, ibyahagaritswe n’ibinyuranyo bigaragara hano iyo sisitemu nkuru ifunguye kimwe.',
    'admin.exceptions.filtered_title': 'Nta kidasanzwe cy’ubu bwoko',
    'admin.exceptions.filtered_body':
        'Hitamo Byose urebe ibidasanzwe byose bifunguye.',
    'admin.notes.tabs': 'Inyandiko z’imyenda ukurikije aho zigeze',
    'admin.notes.sort_label': 'Tondeka inyandiko',
    'admin.notes.sort.newest': 'Tondeka: izigezweho',
    'admin.notes.sort.raised': 'Tondeka: imari yakusanyijwe',
    'admin.notes.sort.yield': 'Tondeka: inyungu',
    'admin.notes.sort.funded': 'Tondeka: % yatewe inkunga',
    'admin.notes.sort.investors': 'Tondeka: abashoramari',
    'admin.notes.sort.term': 'Tondeka: igihe',
    'admin.notes.state.live': 'Irakusanya',
    'admin.notes.state.funded': 'Yuzuye',
    'admin.notes.state.repaying': 'Irishyurwa',
    'admin.notes.state.failed': 'Ntiyagezweho',
    'admin.notes.more': 'Izindi nyandiko',
    'admin.primary.title': 'Ubukusanyamari buri gukorwa',
    'admin.primary.caption':
        'Ubukusanyamari bwose butaruzura, ubushya mbere. Ubukusanyamari buzura cyangwa bukananirwa ku gihe cyabwo, bityo nta kugenzura kuri hano.',
    'admin.primary.col.raise': 'Ubukusanyamari',
    'admin.primary.col.funded': 'Ayabonetse',
    'admin.primary.col.progress': 'Aho bigeze',
    'admin.primary.col.closes': 'Birangira',
    'admin.primary.yield': 'Inyungu {rate}%',
    'admin.primary.term': {
        one: 'Ukwezi {count}',
        other: 'Amezi {count}',
    },
    'admin.primary.funded': '{pct}%',
    'admin.primary.raised_of': '{raised} kuri {target}',
    'admin.payments.title': 'Ubwishyu buri mu nzira',
    'admin.payments.caption':
        'Kubikuza amafaranga yabo afashwe mu gihe utanga serivisi yemeza ubwishyu. Buri kimwe cyishyurwa, cyangwa kigasubira mu gikapu, hakurikijwe igisubizo cyemejwe.',
    'admin.payments.kpi.count': {
        one: 'Igikorwa {count} · iminsi 14 ishize',
        other: 'Ibikorwa {count} · iminsi 14 ishize',
    },
    'admin.payments.kpi.disbursed_count': {
        one: 'Ubwishyu {count} ku bigo · iminsi 14 ishize',
        other: 'Ubwishyu {count} ku bigo · iminsi 14 ishize',
    },
    'admin.payments.kpi.in_flight': 'Ubwishyu buri mu nzira',
    'admin.payments.kpi.in_flight.sub': 'Bitegereje utanga serivisi',
    'admin.payments.col.reference': 'Indango',
    'admin.payments.col.who': 'Ukoresha',
    'admin.payments.col.type': 'Ubwoko',
    'admin.payments.col.amount': 'Amafaranga',
    'admin.payments.col.account': 'Kuri konti',
    'admin.payments.col.state': 'Uko bihagaze',
    'admin.payments.owner.investor': 'Kubikuza k’umushoramari',
    'admin.payments.owner.business': 'Kubikuza k’ikigo',
    'admin.payments.state.pending': 'Bitegereje utanga serivisi',
    'admin.payments.state.unknown': 'Ntibiremezwa, birongera kubazwa',
    'admin.payments.unnamed': 'Konti itagira izina',
    'admin.payments.empty_title': 'Nta bwishyu buri mu nzira',
    'admin.payments.empty_body':
        'Kubikuza kose kwarishyuwe cyangwa kwasubijwe.',
    'admin.payments.more': 'Erekana ubwishyu bwose',
    'admin.primary.investors': {
        one: 'Umushoramari {count}',
        other: 'Abashoramari {count}',
    },
    'admin.primary.days_left': {
        one: 'Hasigaye umunsi {count}',
        other: 'Hasigaye iminsi {count}',
    },
    'admin.primary.closes_today': 'Birarangira',
    'admin.primary.lifecycle.fully_reserved': 'Byose byafashwe',
    'admin.primary.lifecycle.inventory_unavailable': 'Ntibiboneka',
    'admin.primary.lifecycle.sold_out_pending_settlement': 'Byashize',
    'admin.primary.lifecycle.closing_pending_settlement': 'Birarangira',
    'admin.primary.trend.last_14': 'Iminsi 14 ishize',
    'admin.primary.more': 'Ubundi bukusanyamari buri gukorwa',
    'admin.primary.empty_title': 'Nta bukusanyamari buri gukorwa',
    'admin.primary.empty_body':
        'Ubukusanyamari bugaragara hano iyo itangazwa ryabwo rimaze guhabwa amanota no gufungurwa.',
    'admin.nav.reconciliation': 'Guhuza konti',
    'admin.nav.coverage': 'Aho abafatanyabikorwa bakorera',
    'admin.nav.reports': 'Raporo',
    'admin.section.reconciliation.title': 'Guhuza konti',
    'admin.section.reconciliation.subtitle':
        'Igitabo cy’imari, raporo ya konti n’ikinyuranyo cya buri konti biri iruhande rumwe, n’icyuho cyose kugeza gisobanuwe. Gusoma gusa.',
    'admin.section.reconciliation.search':
        'Shakisha ukoresheje konti cyangwa indango…',
    'admin.section.coverage.title': 'Aho abafatanyabikorwa bakorera',
    'admin.section.coverage.subtitle':
        'Abafatanyabikorwa b’igenzura n’igenzura rifunguye muri buri karere, n’uko akarere gafite ugakorera. Gusoma gusa.',
    'admin.section.coverage.search':
        'Shakisha ukoresheje akarere cyangwa intara…',
    'admin.section.reports.title': 'Raporo za buri kwezi',
    'admin.section.reports.subtitle':
        "Raporo z'imikorere y'ubucuruzi zitangarizwa abashoramari",
    'admin.section.reports.search': 'Shakisha…',
    'admin.reconciliation.day_close.label': 'Gufunga umunsi',
    'admin.reconciliation.day_close.title': 'Gufunga umunsi · {date}',
    'admin.reconciliation.day_close.state.reconciled': 'Byahujwe',
    'admin.reconciliation.day_close.state.open_break': 'Icyuho gifunguye',
    'admin.reconciliation.day_close.state.not_reconciled': 'Ntibirahuzwa',
    'admin.reconciliation.day_close.body.reconciled':
        'Konti zose zahujwe; umunsi wafunzwe {time}.',
    'admin.reconciliation.day_close.body.open_break':
        'Umunsi ntushobora gufungwa hari icyuho kidasobanuwe. Buri cyuho kiguma hepfo, gifite igihe kimaze, kugeza gisobanuwe.',
    'admin.reconciliation.day_close.body.not_reconciled':
        'Sisitemu nkuru ntirahuza uyu munsi, bityo ntushobora gufungwa.',
    'admin.reconciliation.accounts.title': 'Amafaranga ari kuri konti',
    'admin.reconciliation.accounts.caption':
        'Igitabo cy’imari, raporo ya konti n’ikinyuranyo cya buri konti, nk’uko sisitemu nkuru ibigaragaza.',
    'admin.reconciliation.accounts.table': 'Amafaranga ari kuri konti',
    'admin.reconciliation.accounts.empty_title': 'Nta konti yatanzwe',
    'admin.reconciliation.accounts.empty_body':
        'Konti igaragara hano iyo sisitemu nkuru imaze kuyihuza.',
    'admin.reconciliation.col.account': 'Konti',
    'admin.reconciliation.col.ledger': 'Igitabo cy’imari',
    'admin.reconciliation.col.statement': 'Raporo ya konti',
    'admin.reconciliation.col.difference': 'Ikinyuranyo',
    'admin.reconciliation.col.feed': 'Iyakirwa rya raporo',
    'admin.reconciliation.col.reference': 'Indango',
    'admin.reconciliation.col.break': 'Icyuho',
    'admin.reconciliation.col.gap': 'Ingano',
    'admin.reconciliation.col.owner': 'Igihe · ugikurikirana',
    'admin.reconciliation.col.record': 'Dosiye',
    'admin.reconciliation.as_of': 'Kugeza {time}',
    'admin.reconciliation.no_statement': 'Nta raporo',
    'admin.reconciliation.feed.available': 'Iraboneka',
    'admin.reconciliation.feed.unavailable': 'Ntiboneka',
    'admin.reconciliation.feed.since': 'Kuva {time}',
    'admin.reconciliation.breaks.title': 'Ibyuho bifunguye',
    'admin.reconciliation.breaks.caption':
        'Buri cyuho kiguma hano, gifite igihe kimaze, kugeza gisobanuwe. Kugena ugikurikirana no kukizamura ntibiraboneka.',
    'admin.reconciliation.breaks.table': 'Ibyuho bifunguye',
    'admin.reconciliation.breaks.empty_title': 'Nta cyuho gifunguye',
    'admin.reconciliation.breaks.empty_body':
        'Icyuho kigaragara hano iyo raporo ya konti n’igitabo cy’imari bidahuye.',
    'admin.reconciliation.open_named': 'Fungura {reference}',
    'admin.reports.title': 'Raporo',
    'admin.reports.caption':
        'Buri raporo ku gihe cyayo, n’uko imibare yayo irangiye. Nta kintu hano gihindura umubare.',
    'admin.reports.table': 'Raporo',
    'admin.reports.col.pack': 'Raporo',
    'admin.reports.col.period': 'Igihe',
    'admin.reports.col.status': 'Uko ihagaze',
    'admin.reports.col.as_of': 'Kugeza',
    'admin.reports.col.download': 'Gukuramo',
    'admin.reports.kind.regulator': 'Ugenzura',
    'admin.reports.kind.board': 'Inama y’ubuyobozi',
    'admin.reports.kind.export': 'Icyoherezwa',
    'admin.reports.status.complete': 'Irarangiye',
    'admin.reports.status.incomplete': 'Igihe ntikirafungwa',
    'admin.reports.status.moving': 'Imibare iracyahinduka',
    'admin.reports.period': '{start} – {end}',
    'admin.reports.snapshot': 'Ifoto y’igihe',
    'admin.reports.pending': 'Bitegerejwe:',
    'admin.reports.not_ready': 'Igihe nikimara gufungwa',
    'admin.reports.unavailable': 'Ntibishoboka kumanura ubu',
    'admin.reports.download': 'Kuramo',
    'admin.reports.download_snapshot': 'Kuramo ifoto y’igihe',
    'admin.reports.download_named': 'Kuramo {label}',
    'admin.reports.empty_title': 'Nta raporo iraboneka',
    'admin.reports.empty_body':
        'Raporo igaragara hano iyo igihe cyayo gitangiye.',
    'admin.coverage.title': 'Uturere',
    'admin.coverage.caption':
        'Abafatanyabikorwa b’igenzura bakora n’igenzura rifunguye muri buri karere, n’icyemezo cya sisitemu nkuru ku kuba gafite ugakorera.',
    'admin.coverage.table': 'Aho abafatanyabikorwa bakorera mu turere',
    'admin.coverage.stats.districts': 'Uturere',
    'admin.coverage.stats.uncovered': 'Tudafite ugakorera',
    'admin.coverage.stats.audits_open': 'Igenzura rifunguye',
    'admin.coverage.col.district': 'Akarere',
    'admin.coverage.col.partners': 'Abafatanyabikorwa b’igenzura bakora',
    'admin.coverage.col.audits': 'Igenzura rifunguye',
    'admin.coverage.col.capacity': 'Uko gakorerwa',
    'admin.coverage.col.open': 'Abafatanyabikorwa',
    'admin.coverage.capacity.covered': 'Gafite ugakorera',
    'admin.coverage.capacity.uncovered': 'Nta ugakorera',
    'admin.coverage.open': 'Reba',
    'admin.coverage.open_named': 'Abafatanyabikorwa b’igenzura muri {district}',
    'admin.coverage.empty_title': 'Nta karere katanzwe',
    'admin.coverage.empty_body':
        'Uturere tugaragara hano iyo sisitemu nkuru itangiye gukurikirana aho abafatanyabikorwa b’igenzura bakorera.',
    'admin.nav.complaints': 'Ibirego',
    'admin.section.complaints.title': 'Ibirego',
    'admin.section.complaints.subtitle':
        'Buri kirego n’inimero yacyo, igihe cy’amasaha 4 cyo kwemeza ko cyakiriwe n’icy’amasaha 48 cyo kugikemura, n’ibiganiro byacyo.',
    'admin.section.complaints.search':
        'Shakisha ukoresheje nimero cyangwa umukiriya…',
    'admin.kyc.complaints_link': 'Ibirego',
    'admin.health.status.critical': 'Bikomeye',
    'admin.health.status.watch': 'Gukurikiranwa',
    'admin.health.status.healthy': 'Ni byiza',
    'admin.health.status.untracked': 'Ntibikurikiranwa',
    'admin.health.overall.critical': 'Hakenewe ubutabazi',
    'admin.health.overall.watch': 'Birakora ariko hari ibyo kwitondera',
    'admin.health.overall.healthy': 'Sisitemu zose ni nzima',
    'admin.health.overall.critical.sub':
        '{critical} bikomeye, {watch} byo gukurikiranwa',
    'admin.health.overall.watch.sub': {
        one: 'Ikimenyetso {count} gikeneye kurebwa',
        other: 'Ibimenyetso {count} bikeneye kurebwa',
    },
    'admin.health.overall.healthy.sub':
        'Buri kimenyetso gikurikiranwa kiri mu rugero',
    'admin.health.not_tracked': 'Ntibirakurikiranwa',
    'admin.health.ledger.label': 'Ubuziranenge bw’igitabo cy’imari',
    'admin.health.ledger.value.healthy': 'Biringaniye',
    'admin.health.ledger.value.critical': 'Ntibiringaniye',
    'admin.health.ledger.value.watch': {
        one: 'Ikinyuranyo {count} gifunguye',
        other: 'Ibinyuranyo {count} bifunguye',
    },
    'admin.health.ledger.detail': {
        one: 'Konti {count} y’utanga serivisi yahujwe',
        other: 'Konti {count} z’utanga serivisi zahujwe',
    },
    'admin.health.ledger.rec.healthy':
        'Ihererekanya ryose rirahuye. Nta gukosora intoki bikenewe.',
    'admin.health.ledger.rec.watch':
        'Hari igisubizo cy’utanga serivisi igitabo kitabashije gukurikira. Kirebe muri Guhuza konti.',
    'admin.health.ledger.rec.critical':
        'Konti y’ihererekanya ntigihura n’utanga serivisi. Yirebe muri Guhuza konti.',
    'admin.health.ledger.action': 'Fungura Guhuza konti',
    'admin.health.capital.label': 'Imari iri mu nzira',
    'admin.health.capital.detail': {
        one: 'Ukusanya {count} kuri gukorwa cyangwa gutegereje kwishyurwa',
        other: 'Ukusanya {count} kuri gukorwa cyangwa gutegereje kwishyurwa',
    },
    'admin.health.capital.rec':
        'Amafaranga arimo aragenda. Komeza umurongo wo kwishyura ube usukuye.',
    'admin.health.queue.label': 'Umurongo w’isuzuma',
    'admin.health.queue.detail': 'ubusabe butegereje gusuzumwa',
    'admin.health.queue.rec.watch':
        'Ubusabe buri kwiyongera. Shyiraho umusesenguzi.',
    'admin.health.queue.rec.healthy': 'Umurongo nta busabe burimo.',
    'admin.health.queue.rec.within':
        'Biri mu rugero. Nta busabe burimo kwirundanya.',
    'admin.health.queue.action': 'Fungura umurongo',
    'admin.health.events.label': 'Ibikorwa byanditswe',
    'admin.health.events.value': '{count}/isaha',
    'admin.health.events.detail': {
        one: 'Igikorwa {count} cyanditswe muri rusange',
        other: 'Ibikorwa {count} byanditswe muri rusange',
    },
    'admin.health.events.rec.healthy':
        'Ibikorwa birimo kwandikwa no gukwirakwizwa.',
    'admin.health.events.rec.watch': 'Nta gikorwa cyanditswe mu isaha ishize.',
    'admin.health.default_rate.label': 'Igipimo cyo kunanirwa kwishyura',
    'admin.health.default_rate.detail': 'Bizatangira kwishyura nibitangira',
    'admin.health.arrears.label': 'Igipimo cy’ibirarane',
    'admin.health.arrears.detail': 'Bizatangira kwishyura nibitangira',
    'admin.health.outstanding.label': 'Ibisigaye kwishyurwa',
    'admin.health.outstanding.detail': 'Bizatangira kwishyura nibitangira',
    'admin.health.treasury.label': 'Uko isanduku ihagaze',
    'admin.health.treasury.detail': 'Nta mafaranga y’isanduku arandikwa',
    'admin.health.frozen.label': 'Konti zahagaritswe',
    'admin.health.frozen.detail': 'konti zahagaritswe kwinjira',
    'admin.health.frozen.rec.healthy': {
        one: 'Nta konti y’umukozi yahagaritswe.',
        other: 'Nta konti y’umukozi yahagaritswe.',
    },
    'admin.health.frozen.rec.watch': {
        one: "Konti {count} y'umukozi yahagaritswe. Yisuzume muri Abakozi n'inshingano.",
        other: "Konti {count} z'abakozi zahagaritswe. Zisuzume muri Abakozi n'inshingano.",
    },
    'admin.health.frozen.action': "Fungura Abakozi n'inshingano",
    'admin.complaints.title': 'Ibirego',
    'admin.complaints.caption':
        'Emeza ko cyakiriwe mu masaha 4 ugikemure mu masaha 48, cyangwa ubwire umukiriya impamvu n’igihe (BRS CMP-R03).',
    'admin.complaints.table': 'Ibirego',
    'admin.complaints.stats.unresolved': 'Bitarakemuka',
    'admin.complaints.stats.awaiting': 'Bitegereje kwemezwa',
    'admin.complaints.stats.late': 'Byarengeje igihe',
    'admin.complaints.col.reference': 'Nimero',
    'admin.complaints.col.status': 'Uko bihagaze',
    'admin.complaints.col.clock': 'Igihe gikurikira',
    'admin.complaints.col.open': 'Ibiganiro',
    'admin.complaints.status.awaiting': 'Gitegereje',
    'admin.complaints.status.answered': 'Cyashubijwe',
    'admin.complaints.status.resolved': 'Cyakemutse',
    'admin.complaints.late.acknowledge':
        'Ntiharemezwa ko cyakiriwe mu masaha 4',
    'admin.complaints.late.resolve': 'Ntikirakemuka mu masaha 48',
    'admin.complaints.role.investor': 'Umushoramari',
    'admin.complaints.role.business': 'Ikigo',
    'admin.complaints.acknowledge_by': 'Emeza bitarenze {time}',
    'admin.complaints.resolve_by': 'Kemura bitarenze {time}',
    'admin.complaints.done': 'Cyafunzwe',
    'admin.complaints.open': 'Fungura',
    'admin.complaints.open_named': 'Fungura ikirego {reference}',
    'admin.complaints.empty_title': 'Nta birego',
    'admin.complaints.empty_body':
        'Ikirego kigaragara hano umukiriya akimara kucyohereza muri porogaramu.',
    'admin.complaints.drawer': 'Ikirego {reference}',
    'admin.complaints.from.customer': 'Umukiriya · {time}',
    'admin.complaints.from.staff': 'Rozine · {time}',
    'admin.complaints.from.resolution': 'Uko cyakemuwe · {time}',
    'admin.complaints.answer.label': 'Igisubizo cyawe',
    'admin.complaints.answer.placeholder': 'Andikira umukiriya…',
    'admin.complaints.answer.resolves': 'Iki gisubizo gikemura ikirego',
    'admin.complaints.answer.send': 'Ohereza igisubizo',
    'admin.complaints.answer.hint':
        'Umukiriya agisoma muri porogaramu kandi akabona ubutumwa. Igisubizo gikemura gifunga ikirego.',
    'admin.complaints.closed':
        'Iki kirego cyakemutse. Nta kindi gishobora kongerwaho.',
    'auditor.academy.title': 'Ishuri ry’Umugenzuzi',
    'auditor.academy.intro':
        'Amasomo magufi ku igenzura, raporo zifunze n’uko uhembwa kuri Rozine, akubiyemo amategeko akurikizwa ubu n’amabwiriza yanditse y’ubwishyu bwo mu gihe kizaza. Ayo mabwiriza y’ahazaza agaragazwa mu masomo. Aho ugeze ubikwa kuri iki gikoresho gusa.',
    'auditor.academy.summary_start': 'Menya uko igenzura rikorwa kuri Rozine',
    'auditor.academy.resume': 'Komeza: {lesson}',
    'auditor.academy.review': 'Subiramo Ishuri',
    'auditor.academy.browse': 'Reba amasomo yose',
    'auditor.academy.group.role': 'Uruhare rwawe',
    'auditor.academy.group.assignments': 'Uko akazi kakugeraho',
    'auditor.academy.group.verification': 'Kugenzura ubucuruzi',
    'auditor.academy.group.monthly': 'Buri kwezi',
    'auditor.academy.group.visits':
        'Igenzura ritunguranye n’isura ry’ibirarane',
    'auditor.academy.group.pay': 'Uko uhembwa',
    'auditor.academy.lesson.role.title': 'Uri nde',
    'auditor.academy.lesson.role.sub': 'Umugenzuzi wemewe kandi wigenga',
    'auditor.academy.lesson.role.topic1.title': 'Umucungamari wigenga',
    'auditor.academy.lesson.role.topic1.body':
        'Uri Umufatanyabikorwa mu igenzura: umucungamari w’umwuga wigenga, wemewe na ICPAR kandi ufitanye amasezerano na Rozine. Ugenzura imbonankubone ibyo inyandiko z’imari zonyine zidashobora kwemeza, mbere y’uko ubucuruzi bukusanya amafaranga na buri kwezi igihe inyandiko y’umwenda yabwo itarishyurwa.',
    'auditor.academy.lesson.role.topic1.q':
        'Ni nde ushobora kugenzura ubucuruzi kuri Rozine?',
    'auditor.academy.lesson.role.topic1.option1':
        'Abacungamari bemewe kandi bigenga gusa',
    'auditor.academy.lesson.role.topic1.option2':
        'Umukozi uwo ari we wese wa Rozine',
    'auditor.academy.lesson.role.topic1.option3':
        'Umucungamari w’ubwo bucuruzi',
    'auditor.academy.lesson.role.topic2.title': 'Uruhushya rwawe',
    'auditor.academy.lesson.role.topic2.body':
        'Uruhushya rwawe rwa ICPAR rusuzumwa igihe wemerewe, kandi rukongera gusuzumwa mbere ya buri kazi uhawe. Uruhushya rwarangiye ruhagarika akazi gashya uwo munsi nyine.',
    'auditor.academy.lesson.role.topic2.q':
        'Uruhushya rwawe rurangiye uyu munsi. Bigenda bite?',
    'auditor.academy.lesson.role.topic2.option1': 'Uhabwa ukwezi k’inyongera',
    'auditor.academy.lesson.role.topic2.option2':
        'Nta kazi gashya uhabwa guhera uyu munsi',
    'auditor.academy.lesson.role.topic2.option3':
        'Nta kintu kugeza igenzura ritaha rirangiye',
    'auditor.academy.lesson.independence.title': 'Kwigenga',
    'auditor.academy.lesson.independence.sub':
        'Inyungu zivuguruzanya n’amakuru ufite',
    'auditor.academy.lesson.independence.topic1.title':
        'Garagaza buri nyungu ivuguruzanya',
    'auditor.academy.lesson.independence.topic1.body':
        'Ugaragaza inyungu zose zivuguruzanya. Ntuzigera uhabwa ubucuruzi wowe cyangwa ikigo cyawe mufitanye isano.',
    'auditor.academy.lesson.independence.topic1.q':
        'Ikigo cyawe gikora ibaruramari ry’ubucuruzi runaka. Ushobora kubugenzura?',
    'auditor.academy.lesson.independence.topic1.option1':
        'Yego, niba ubivuze muri raporo',
    'auditor.academy.lesson.independence.topic1.option2':
        'Mu igenzura ritunguranye gusa',
    'auditor.academy.lesson.independence.topic1.option3':
        'Oya, ntushobora kubuhabwa',
    'auditor.academy.lesson.independence.topic2.title':
        'Ntugacuruze ushingiye ku byo uzi',
    'auditor.academy.lesson.independence.topic2.body':
        'Raporo, amanota cyangwa ibirarane ntibiba ibya rubanda kugeza Rozine ibitangaje. Kugura inyandiko y’umwenda ugenzura hagati yo gufunga raporo yayo no kuyitangaza bifungura dosiye y’igenzura.',
    'auditor.academy.lesson.independence.topic2.q':
        'Wafunze raporo nziza uyu munsi, izatangazwa ejo. Ushobora kugura iyo nyandiko uyu munsi?',
    'auditor.academy.lesson.independence.topic2.option1':
        'Oya, bifungura dosiye y’igenzura',
    'auditor.academy.lesson.independence.topic2.option2':
        'Yego, raporo yamaze gufungwa',
    'auditor.academy.lesson.independence.topic2.option3':
        'Yego, niba amafaranga ari make',
    'auditor.academy.lesson.dispatch.title': 'Akazi uhabwa',
    'auditor.academy.lesson.dispatch.sub':
        'Umugenzuzi wujuje ibisabwa uri hafi, muri km 30',
    'auditor.academy.lesson.dispatch.topic1.title': 'Muri km 30',
    'auditor.academy.lesson.dispatch.topic1.body':
        'Akazi gahabwa umucungamari wujuje ibisabwa uri hafi kurusha abandi, ufite ibiro biri muri km 30 uvuye aho ubucuruzi bukorera. Kujuja ibisabwa bivuze kugira uruhushya rugifite agaciro, nta nyungu zivuguruzanya wagaragaje, kandi uburyo bwo gusimburana bubyemera.',
    'auditor.academy.lesson.dispatch.topic1.q':
        'Ubucuruzi buri kuri km 31 uvuye ku biro byawe. Bigenda bite?',
    'auditor.academy.lesson.dispatch.topic1.option1':
        'Burahabwa wowe niba nta wundi uri hafi',
    'auditor.academy.lesson.dispatch.topic1.option2': 'Ntuzigera ubuhabwa',
    'auditor.academy.lesson.dispatch.topic1.option3':
        'Ushobora kubusaba ku rutonde',
    'auditor.academy.lesson.dispatch.topic2.title': 'Gusimburana',
    'auditor.academy.lesson.dispatch.topic2.body':
        'Abacungamari basimburana ku bucuruzi, ku buryo nta n’umwe ugenzura ubucuruzi bumwe ubuziraherezo. Rozine ishyiraho umubare w’amezi akurikirana umucungamari umwe ashobora kugenzura ubucuruzi.',
    'auditor.academy.lesson.dispatch.topic2.q':
        'Kuki abacungamari basimburana?',
    'auditor.academy.lesson.dispatch.topic2.option1':
        'Kugira ngo igihembo kigabanywe kimwe',
    'auditor.academy.lesson.dispatch.topic2.option2':
        'Kubera ko impushya zirangira buri kwezi',
    'auditor.academy.lesson.dispatch.topic2.option3':
        'Kugira ngo nta mucungamari ugenzura ubucuruzi bumwe ubuziraherezo',
    'auditor.academy.lesson.baseline.title': 'Raporo y’ibanze',
    'auditor.academy.lesson.baseline.sub':
        'Nta nyandiko y’umwenda isohoka itariho',
    'auditor.academy.lesson.baseline.topic1.title':
        'Mbere y’uko isoko rifungurwa',
    'auditor.academy.lesson.baseline.topic1.body':
        'Nta nyandiko y’umwenda ishyirwa ku isoko utarasura ubucuruzi ngo ufunge raporo y’ibanze. Ukurikiza aho hantu uburyo bwumvikanyweho bwa ISRS 4400, kandi raporo ifunze itangazwa hamwe n’isoko.',
    'auditor.academy.lesson.baseline.topic1.q':
        'Ikusanya ry’amafaranga rishobora gufungurwa raporo y’ibanze itarafungwa?',
    'auditor.academy.lesson.baseline.topic1.option1': 'Oya, na rimwe',
    'auditor.academy.lesson.baseline.topic1.option2':
        'Yego, abashoramari baburiwe',
    'auditor.academy.lesson.baseline.topic1.option3': 'Yego, ku makusanya mato',
    'auditor.academy.lesson.baseline.topic2.title': 'Amategeko y’ibimenyetso',
    'auditor.academy.lesson.baseline.topic2.body':
        'Amafoto aturuka gusa kuri kamera iri muri porogaramu, ifite aho uri ako kanya. Kohereza amafoto ari muri telefoni birabujijwe, kandi ifoto yafatiwe hanze y’imbibi z’aho ubucuruzi bukorera irangwa.',
    'auditor.academy.lesson.baseline.topic2.q':
        'Ejo wafashe ifoto ukoresheje kamera isanzwe ya telefoni yawe. Ushobora kuyohereza?',
    'auditor.academy.lesson.baseline.topic2.option1':
        'Yego, uyikuye mu mafoto yawe',
    'auditor.academy.lesson.baseline.topic2.option2':
        'Oya, amafoto yafatiwe muri porogaramu ako kanya ni yo yemewe',
    'auditor.academy.lesson.baseline.topic2.option3':
        'Yego, niba wongeyeho ibisobanuro',
    'auditor.academy.lesson.differences.title':
        'Itandukaniro no gufunga raporo',
    'auditor.academy.lesson.differences.sub':
        'Ibibuza gufunga raporo n’ibyo ihuza',
    'auditor.academy.lesson.differences.topic1.title':
        'Sobanura buri tandukaniro',
    'auditor.academy.lesson.differences.topic1.body':
        'Itandukaniro ryose ridasobanuwe hagati y’inyandiko z’ubucuruzi n’ibyo usanze aho bukorera ribuza gufunga raporo. Sobanura buri tandukaniro muri raporo, bitabaye ibyo ntishobora gufungwa.',
    'auditor.academy.lesson.differences.topic1.q':
        'Ubaze ibicuruzwa bike ugereranyije n’ibiri mu nyandiko, kandi ntubashe gusobanura impamvu. Ushobora gufunga raporo?',
    'auditor.academy.lesson.differences.topic1.option1':
        'Yego, niba itandukaniro ari rito',
    'auditor.academy.lesson.differences.topic1.option2':
        'Yego, wongeyeho igitekerezo',
    'auditor.academy.lesson.differences.topic1.option3':
        'Oya, gufunga birabujijwe',
    'auditor.academy.lesson.differences.topic2.title':
        'Ibyo gufunga raporo bihuza',
    'auditor.academy.lesson.differences.topic2.body':
        'Raporo ifunze ifungishwa ikimenyetso cya SHA-256 gihuza umwirondoro wawe, uruhushya rwawe, ibiri muri raporo n’igihe. Iyo imaze gufungwa, nta kintu na kimwe kiyihinduraho.',
    'auditor.academy.lesson.differences.topic2.q':
        'Ikimenyetso cyo gufunga gihuza iki?',
    'auditor.academy.lesson.differences.topic2.option1':
        'Umwirondoro wawe, uruhushya, ibiri muri raporo n’igihe',
    'auditor.academy.lesson.differences.topic2.option2':
        'Izina ry’ubucuruzi gusa',
    'auditor.academy.lesson.differences.topic2.option3':
        'Abashoramari bafite iyo nyandiko',
    'auditor.academy.lesson.numbers.title': 'Imibare ishingirwaho itangwa',
    'auditor.academy.lesson.numbers.sub': 'Ibyo igenzura ryawe ryemeza',
    'auditor.academy.lesson.numbers.topic1.title': 'CFADS',
    'auditor.academy.lesson.numbers.topic1.body':
        'Amafaranga aboneka yo kwishyura imyenda ni amafaranga yinjira buri kwezi yagenzuwe, ukuyemo ibyakoreshejwe mu mikorere, ayo nyirubucuruzi afata n’ayishyurwa ku myenda isanzwe. RWF 4,200,000 nyuma y’ibyakoreshejwe, ukuyemo 600,000 nyirubucuruzi yafashe n’inguzanyo ya 400,000, bisigaza 3,200,000.',
    'auditor.academy.lesson.numbers.topic1.q':
        'Amafaranga yinjiye nyuma y’ibyakoreshejwe ni RWF 5,000,000, ayo nyirubucuruzi yafashe ni 1,000,000, inguzanyo isanzwe yishyurwa ni 500,000. CFADS ni angahe?',
    'auditor.academy.lesson.numbers.topic1.option1': 'RWF 3,500,000',
    'auditor.academy.lesson.numbers.topic1.option2': 'RWF 4,000,000',
    'auditor.academy.lesson.numbers.topic1.option3': 'RWF 5,500,000',
    'auditor.academy.lesson.numbers.topic2.title':
        'Ubushobozi bwo kwishyura n’itangwa',
    'auditor.academy.lesson.numbers.topic2.body':
        'Ubushobozi bwo kwishyura ni CFADS igabanyijwe n’umubare w’ubwishyu bwa buri kwezi busabwa. Itangwa ni rito kurusha ibindi muri ibi: ayasabwe, ayo ubushobozi bwa 1.50 bwemera, 35% by’amafaranga yinjiye ku mwaka yagenzuwe, na RWF 100,000,000. Igenzura ukora aho bukorera ryemeza inyandiko iyo mibare ishingiyeho.',
    'auditor.academy.lesson.numbers.topic2.q':
        'Rozine igena itangwa ishingiye ku bushobozi bungana iki?',
    'auditor.academy.lesson.numbers.topic2.option1': '1.00',
    'auditor.academy.lesson.numbers.topic2.option2': '1.50',
    'auditor.academy.lesson.numbers.topic2.option3': '1.25',
    'auditor.academy.lesson.cycle.title': 'Uruziga rwa buri kwezi',
    'auditor.academy.lesson.cycle.sub': 'Igihe porogaramu ikoresha ubu',
    'auditor.academy.lesson.cycle.topic1.title':
        'Bitarenze ku ya 7, ku isaha ya Kigali',
    'auditor.academy.lesson.cycle.topic1.body':
        'Muri porogaramu ikoreshwa ubu, funga raporo ya buri kwezi kwagenzuwe bitarenze impera z’umunsi wa 7 w’ukwezi gukurikira (ku isaha ya Kigali). Kurikiza itariki ntarengwa iri kuri raporo; ntihindukana n’umunsi wo kwishyura inyandiko y’umwenda.',
    'auditor.academy.lesson.cycle.topic1.q':
        'Muri porogaramu ikoreshwa ubu, raporo y’igenzura rya Nzeri igomba gufungwa bitarenze iyihe tariki?',
    'auditor.academy.lesson.cycle.topic1.option1': 'Ku ya 7 Ukwakira',
    'auditor.academy.lesson.cycle.topic1.option2': 'Ku ya 15 Ukwakira',
    'auditor.academy.lesson.cycle.topic1.option3': 'Ku ya 18 Ukwakira',
    'auditor.academy.lesson.cycle.topic2.title': 'Igihe igenzura ryatinze',
    'auditor.academy.lesson.cycle.topic2.body':
        'Porogaramu ikoreshwa ubu igaragaza ko igenzura rya buri kwezi ritarafungwa ryatinze iyo igihe cyagenwe kirangiye. Iyo tariki ni na yo ikoreshwa mu kubara uko urangiza ku gihe. Raporo ibuze yonyine ntituma inyandiko y’umwenda ifatwa nk’iyananiwe kwishyurwa.',
    'auditor.academy.lesson.cycle.topic2.q':
        'Igihe ntarengwa cyo gufunga raporo y’ukwezi kirangiye utarayifunga. Ni iki gihinduka?',
    'auditor.academy.lesson.cycle.topic2.option1':
        'Inyandiko ihita ifatwa nk’iyananiwe kwishyurwa',
    'auditor.academy.lesson.cycle.topic2.option2':
        'Igenzura riratinze kandi gufunga nyuma yaho bibarwa nk’ubukererwe',
    'auditor.academy.lesson.cycle.topic2.option3':
        'Itariki ntarengwa ikurikira umunsi wo kwishyura',
    'auditor.academy.lesson.disputes.title': 'Kwemeza no kujurira',
    'auditor.academy.lesson.disputes.sub': 'Ibikurikira nyuma yo gufunga',
    'auditor.academy.lesson.disputes.topic1.title': 'Amasaha 24 yo gusubiza',
    'auditor.academy.lesson.disputes.topic1.body':
        'Nyuma yo gufunga, uhagarariye ubucuruzi afite amasaha 24 yo kwemeza cyangwa kujuririra raporo yawe.',
    'auditor.academy.lesson.disputes.topic1.q':
        'Ubucuruzi bufite igihe kingana iki cyo kujuririra raporo ifunze?',
    'auditor.academy.lesson.disputes.topic1.option1':
        'Kugeza ku munsi wo kwishyura',
    'auditor.academy.lesson.disputes.topic1.option2': 'Iminsi irindwi',
    'auditor.academy.lesson.disputes.topic1.option3': 'Amasaha 24',
    'auditor.academy.lesson.disputes.topic2.title': 'Isuzuma ry’ibiro',
    'auditor.academy.lesson.disputes.topic2.body':
        'Ubujurire bwohereza raporo mu isuzuma ry’ibiro rikorwa n’Umuyobozi w’Imari n’Urusobe rw’Abagenzuzi. Igihe ikijurirwa, inyandiko ntishobora gucuruzwa, kandi icyemezo n’impamvu yacyo bitangazwa hamwe na raporo.',
    'auditor.academy.lesson.disputes.topic2.q':
        'Inyandiko y’umwenda bigenda bite igihe raporo yayo ijuririrwa?',
    'auditor.academy.lesson.disputes.topic2.option1': 'Ntishobora gucuruzwa',
    'auditor.academy.lesson.disputes.topic2.option2': 'Ifatwa nk’itarishyuwe',
    'auditor.academy.lesson.disputes.topic2.option3': 'Nta gihinduka',
    'auditor.academy.lesson.flash.title': 'Igenzura ritunguranye',
    'auditor.academy.lesson.flash.sub': 'Ridateguje, mu masaha 24',
    'auditor.academy.lesson.flash.topic1.title': 'Mu masaha 24',
    'auditor.academy.lesson.flash.topic1.body':
        'Rozine ishobora kukohereza ku bucuruzi butabimenyeshejwe. Igenzura ritunguranye rigomba kurangira mu masaha 24 uhereye igihe urihawe, hakurikijwe amategeko y’ibimenyetso amwe n’aya raporo y’ibanze.',
    'auditor.academy.lesson.flash.topic1.q':
        'Ufite igihe kingana iki cyo kurangiza igenzura ritunguranye?',
    'auditor.academy.lesson.flash.topic1.option1':
        'Amasaha 24 uhereye igihe urihawe',
    'auditor.academy.lesson.flash.topic1.option2':
        'Amasaha 24 uhereye igihe uhagereye',
    'auditor.academy.lesson.flash.topic1.option3': 'Iminsi itatu',
    'auditor.academy.lesson.flash.topic2.title': 'Iyo igihe kirangiye',
    'auditor.academy.lesson.flash.topic2.body':
        'Igenzura ritunguranye ritarangiye mu masaha 24 ritera impuruza, kandi igihe umucungamari atubahirije kimenyeshwa CMA. Fata gusa ibyo ushobora kurangiza ku gihe.',
    'auditor.academy.lesson.flash.topic2.q':
        'Bigenda bite iyo igenzura ritunguranye ritarangiye mu masaha 24?',
    'auditor.academy.lesson.flash.topic2.option1': 'Igihe cyongerwa ubwacyo',
    'auditor.academy.lesson.flash.topic2.option2': 'Hatangwa impuruza',
    'auditor.academy.lesson.flash.topic2.option3': 'Ubucuruzi buhakanwa',
    'auditor.academy.lesson.arrears.title': 'Isura ry’ibirarane',
    'auditor.academy.lesson.arrears.sub': 'Iyo ubucuruzi bwatinze icyumweru',
    'auditor.academy.lesson.arrears.topic1.title': 'Umunsi wa 7 w’ibirarane',
    'auditor.academy.lesson.arrears.topic1.body':
        'Iyo ubucuruzi ugenzura bumaze iminsi irindwi butishyura, uhabwa igikorwa cyo kubusura. Uhita ujyayo kugenzura ibicuruzwa, amafaranga n’imikorere.',
    'auditor.academy.lesson.arrears.topic1.q':
        'Isura ry’ibirarane ritangira ryari?',
    'auditor.academy.lesson.arrears.topic1.option1':
        'Ku munsi wa mbere w’ubukererwe',
    'auditor.academy.lesson.arrears.topic1.option2':
        'Ku munsi wa 30 w’ibirarane',
    'auditor.academy.lesson.arrears.topic1.option3':
        'Ku munsi wa 7 w’ibirarane',
    'auditor.academy.lesson.arrears.topic2.title':
        'Inyandiko ifunze ihabwa abafite inyandiko z’umwenda',
    'auditor.academy.lesson.arrears.topic2.body':
        'Nyuma y’isura, wohereza inyandiko ifunze igenewe abafite inyandiko z’umwenda, ku buryo abashoramari bamenya ibyo wasanze babibwiwe n’uwagenzuye.',
    'auditor.academy.lesson.arrears.topic2.q':
        'Nyuma y’isura ry’ibirarane wohereza iki?',
    'auditor.academy.lesson.arrears.topic2.option1':
        'Inyandiko ifunze igenewe abafite inyandiko z’umwenda',
    'auditor.academy.lesson.arrears.topic2.option2':
        'Itangwa rishya ry’ubucuruzi',
    'auditor.academy.lesson.arrears.topic2.option3':
        'Nta cyo, Rozine yandikira abafite inyandiko',
    'auditor.academy.lesson.earnings.title': 'Ibyo winjiza',
    'auditor.academy.lesson.earnings.sub':
        'Umugabane ku byo Rozine yinjiza ku nyandiko wagenzuye',
    'auditor.academy.lesson.earnings.topic1.title':
        '40% by’amafaranga y’urubuga',
    'auditor.academy.lesson.earnings.topic1.body':
        'Ubucuruzi bwishyura amafaranga y’urubuga angana na 3% by’igishoro igihe amafaranga atanzwe. 40% by’ayo mafaranga ashyirwa kuri konti yawe, nk’Umufatanyabikorwa mu igenzura wagenzuye iyo nyandiko, igihe amafaranga atanzwe. Ku ikusanya rya RWF 12,000,000, amafaranga y’urubuga ni 360,000, umugabane wawe ukaba 144,000.',
    'auditor.academy.lesson.earnings.topic1.q':
        'Ikusanya rya RWF 10,000,000 ryatanzwe. Umugabane wawe ku mafaranga y’urubuga ni angahe?',
    'auditor.academy.lesson.earnings.topic1.option1': 'RWF 300,000',
    'auditor.academy.lesson.earnings.topic1.option2': 'RWF 40,000',
    'auditor.academy.lesson.earnings.topic1.option3': 'RWF 120,000',
    'auditor.academy.lesson.earnings.topic2.title':
        '25% by’amafaranga ku nyungu (amabwiriza y’ahazaza)',
    'auditor.academy.lesson.earnings.topic2.body':
        'Amabwiriza yanditse y’ubwishyu bwo mu gihe kizaza ateganya amafaranga ku gice cy’inyungu cya buri bwishyu bwa buri kwezi ku bashoramari, aho 25% by’ayo mafaranga yazajya ashyirwa kuri konti yawe kuri buri bwishyu bw’inyandiko ugenzura. Aya mafaranga ntaratangira gushyirwa kuri konti.',
    'auditor.academy.lesson.earnings.topic2.q':
        'Dukurikije amabwiriza yanditse y’ubwishyu bwo mu gihe kizaza, umugabane wa 25% ku mafaranga y’inyungu wazajya ugushyirwaho ryari?',
    'auditor.academy.lesson.earnings.topic2.option1':
        'Kuri buri bwishyu bwa buri kwezi bw’inyandiko ugenzura, bimaze gutangira gukurikizwa',
    'auditor.academy.lesson.earnings.topic2.option2':
        'Rimwe kuri buri genzura rirangiye',
    'auditor.academy.lesson.earnings.topic2.option3':
        'Igihe inyandiko irangiye gusa',
};

export default rw;
