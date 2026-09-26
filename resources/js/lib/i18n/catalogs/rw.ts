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
    'identity.home.auditor_nav': "Akazi kawe k'igenzura",
    'identity.home.saving': 'Kubika aho ugeze…',
    'identity.home.failed':
        'Ntitwashoboye kubika aho ugeze. Subira aho uhitamo porogaramu urebe uburenganzira bwawe.',
    'identity.denied.title': 'Uburenganzira bugomba kugenzurwa',
    'identity.denied.body':
        'Konti yawe nticyemerewe gufungura uru rupapuro mu nshingano wahisemo. Hitamo porogaramu wongere kugenzura uburenganzira bwawe.',
    'identity.denied.expired_offer.title': 'Iki cyifuzo cyarafunzwe',
    'identity.denied.expired_offer.body':
        'Igihe cyo kwemera uyu murimo cyarangiye, ntukiwufunguriwe. Hitamo porogaramu urebe imirimo yawe iriho.',
    'errors.not_found.head_title': 'Ntibibonetse',
    'errors.not_found.title': 'Ntitwabonye uru rupapuro',
    'errors.not_found.body':
        'Birashoboka ko ihuza ryanditswe nabi, cyangwa icyo ryerekezagaho kitakiboneka. Reba ihuza, cyangwa wongere utangirire kuri Rozine.',
    'errors.not_found.home': 'Jya ku rupapuro rw’ibanze rwa Rozine',
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
    'business.today.approved.sub_free':
        'Yitangaze ku bashoramari. Nta mafaranga yo gutangaza asabwa.',
    'business.today.approved.cta_free': 'Tangaza',
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
    'business.entries.title': 'Ubusabe bwo gushaka igishoro',
    'business.entries.continue': 'Komeza ubusabe bwawe',
    'business.entries.view': 'Reba ubusabe bwawe',
    'business.entries.saved_at': 'Byabitswe kuri {step}',
    'business.entries.submitted': 'Bwoherejwe · burasuzumwa',
    'business.entries.view_only':
        'Ushobora kureba ubu bucuruzi, ariko ntiwemerewe gutangiza ubusabe bw’igishoro.',
    'business.entries.empty':
        'Nta bucuruzi ushobora gukorera buhujwe n’iyi konti.',
    'business.entries.audit_report.pending':
        'Raporo y’igenzura yiteguye: yisuzume kandi uyishyireho umukono',
    'business.entries.audit_report.published': 'Raporo y’igenzura yatangajwe',
    'business.entries.audit_report.disputed': 'Ubujurire bwawe burasuzumwa',
    'business.entries.audit_report.escalated':
        'Abakozi ba Rozine barasuzuma ubujurire bwawe',
    'business.entries.more': 'Erekana ibindi',
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
    'business.apply.raise.rate_info': 'Uko iki gipimo gishyirwaho',
    'business.apply.raise.rate_how': 'Uko igipimo cyawe gishyirwaho',
    'business.apply.raise.rate_one_charge':
        "Ikiguzi kimwe ku byo uguza. Kirimo byose — nta yandi mafaranga y'inyongera.",
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
        "Nta mafaranga ku gishoro ubona, nta n'ikirenga ku gipimo cyatanzwe. Yishyurwa urupapuro rwemejwe, mbere yo gutangazwa.",
    'business.apply.review.binding':
        "Umukono wawe utegeka ubucuruzi mu mategeko kubahiriza inshingano zatangajwe. Nimero y'ubusabe itangwa iyo bwoherejwe.",
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
        'Iyo igenzura rifunzwe, wongeraho incamake ugashyiraho umukono bitarenze tariki ya {day}, cyangwa ukajurira ufite ibimenyetso. Kubera ko utigera ukora ku mibare, raporo iba yigenga — ari cyo abashoramari bishyurira.',
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
    'business.profile.linked.unlink': 'Kuraho',
    'business.profile.linked.unlink_named': 'Kuraho {name}',
    'business.profile.linked.add': '+ Huza konti yakiriraho',
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
        'Shakisha ubusabe ukoresheje ikigo cyangwa nimero…',
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
    'admin.policy.eyebrow': 'Byashyizweho mu mabwiriza',
    'admin.policy.target_dscr': 'DSCR igamijwe',
    'admin.policy.listing_audit': 'Igenzura ryo gushyirwa ku isoko',
    'admin.policy.audit_routing': 'Urugero rwo kohereza mu igenzura',
    'admin.policy.rate_band': "Urugero rw'inyungu · rimwe, byose hamwe",
    'admin.policy.min_revenue': 'Amafaranga yinjira ku mwaka make',
    'admin.policy.min_statement_years': "Imyaka mike y'inyandiko z'imari",
    'admin.policy.rdb_certificate': 'Icyemezo cya RDB',
    'admin.policy.max_notes': 'Inyandiko nyinshi / ikigo',
    'admin.policy.dual_control_threshold': 'Abemeza babiri guhera kuri',
    'admin.time.now': 'ubu nyine',
    'admin.time.minutes': 'hashize iminota {count}',
    'admin.time.hours': 'hashize amasaha {count}',
    'admin.time.days': 'hashize iminsi {count}',
    'admin.ledger.kind.investment': 'Ishoramari',
    'admin.ledger.kind.disbursement': 'Kwishyura ikigo',
    'admin.ledger.kind.repayment': 'Kwishyura inguzanyo',
    'admin.ledger.kind.service_fee': 'Amafaranga ya serivisi',
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
    'admin.today.command.release_queue': 'Umurongo wo kwishyura',
    'admin.today.command.audit_trail': "Ububiko bw'igenzura",
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
    'admin.today.capital.all_time': 'Ibihe byose',
    'admin.today.capital.range': '{from} → {to}',
    'admin.today.capital.caption': '{range} · bishyizwe hamwe ku {grain}',
    'admin.today.capital.grain.year': 'mwaka',
    'admin.today.capital.grain.month': 'kwezi',
    'admin.today.capital.grain.day': 'munsi',
    'admin.today.capital.grain.hour': 'saha',
    'admin.today.health.title': 'Uko inyandiko zimeze',
    'admin.today.health.caption':
        'Inyandiko {count} ziri ku isoko, zikusanya, zishyurwa cyangwa zafunzwe',
    'admin.today.health.donut': "{pct}% by'inyandiko bimeze neza",
    'admin.today.health.healthy_caption': 'bimeze neza',
    'admin.today.health.healthy': 'Bimeze neza',
    'admin.today.health.watch': 'Bikurikiranwa',
    'admin.today.health.distressed': 'Biri mu bibazo',
    'admin.today.activity.title': 'Ibikorwa biri kuba',
    'admin.today.activity.caption': 'Mu rusobe rwose',
    'admin.today.activity.empty': 'Nta mafaranga arahinduranya.',
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
    'admin.maker_checker.label': 'Ibyemezo',
    'admin.maker_checker.step': 'Intambwe {n}',
    'admin.maker_checker.maker_title': 'Byemerewe',
    'admin.maker_checker.checker_title': 'Kwemeza kwa kabiri',
    'admin.maker_checker.maker_empty': 'Nta muntu uremera uku kwishyura.',
    'admin.maker_checker.checker_empty':
        'Undi wemeza agenzura uku kwishyura kumaze kwemererwa.',
    'admin.maker_checker.awaiting': 'Bitegereje uwemeza wa kabiri',
    'admin.maker_checker.self_blocked':
        'Ni wowe wemereye uku kwishyura, ntushobora no kukwemeza. Undi wemeza agomba kukugenzura.',
    'admin.maker_checker.by': '{actor} · {at}',
    'admin.disbursements.policy_title': 'Amabwiriza yo kwishyura akurikizwa',
    'admin.disbursements.title': 'Kwishyura bitegereje',
    'admin.disbursements.awaiting_count':
        '{count} bitegereje uwemeza wa kabiri',
    'admin.disbursements.table': 'Kwishyura bitegereje',
    'admin.disbursements.col.reference': 'Indango yo kwishyura',
    'admin.disbursements.col.note': 'Inyandiko',
    'admin.disbursements.col.recipient': 'Uwishyurwa',
    'admin.disbursements.col.amount': 'Amafaranga',
    'admin.disbursements.col.due': 'Igihe',
    'admin.disbursements.col.actions': 'Imiterere · Ibikorwa',
    'admin.disbursements.state.ready': 'Biteguye kwemererwa',
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
    'admin.disbursements.approvals': 'Ibyemezo',
    'admin.disbursements.trail': 'Amateka yo kwishyura',
    'admin.disbursements.trail_empty': 'Nta kiranditswe.',
    'admin.disbursements.command.authorize': 'Emerera kwishyura',
    'admin.disbursements.command.approve': 'Emeza kwishyura',
    'admin.disbursements.command.reject': 'Anga',
    'admin.disbursements.command.hold': 'Hagarika',
    'admin.disbursements.command.retry': 'Ongera ugerageze kwishyura',
    'admin.disbursements.stage.authorize.title': 'Emerera uku kwishyura',
    'admin.disbursements.stage.authorize.body':
        'Uremerera kwishyura {amount} kuri {business}. Ibi bikora igenzura ribanza; undi mukozi agomba kubyemeza nyuma.',
    'admin.disbursements.stage.authorize.cta': 'Emerera',
    'admin.disbursements.stage.authorize.placeholder':
        'urugero: Amafaranga yose yakusanyijwe; aho yoherezwa hemejwe hakurikijwe manda ya RDB.',
    'admin.disbursements.stage.approve.title': 'Emeza uku kwishyura',
    'admin.disbursements.stage.approve.body':
        "Nk'umukozi wa kabiri, wemeje kwishyura {amount} kuri {business}. Ibi byandika icyifuzo cyo kwishyura; si ukwishyura. Serivisi iracyohereza gusa nyuma yo kongera kugenzura.",
    'admin.disbursements.stage.approve.cta': 'Emeza wandike icyifuzo',
    'admin.disbursements.stage.approve.placeholder':
        "urugero: Nagenzuye ko amafaranga yakusanyijwe n'aho yoherezwa bihuye n'itangwa.",
    'admin.disbursements.stage.reject.title': 'Anga uku kwishyura',
    'admin.disbursements.stage.reject.body':
        'Ibi bikuraho kwemerera gusa kandi byandika impamvu yawe. Ubukusanye ntibuhagarikwa; kwishyura gusubira gutegereza kwemererwa.',
    'admin.disbursements.stage.reject.cta': 'Anga kwishyura',
    'admin.disbursements.stage.reject.placeholder':
        "urugero: Izina rya konti ntirihura n'ikigo.",
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
    'admin.parties.stats.capital_raised': 'Imari yakusanyijwe',
    'admin.parties.stats.total_investors': 'Abashoramari bose',
    'admin.parties.stats.kyc_verified': 'KYC yemejwe',
    'admin.parties.stats.awaiting_kyc': 'Bategereje KYC',
    'admin.parties.stats.total_aum': 'Imari yose icungwa',
    'admin.parties.stats.partners': 'Abagenzuzi bafatanya',
    'admin.parties.stats.active_partners': 'Bakora',
    'admin.parties.stats.pending_partners': 'Bategereje kwemezwa',
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
    'admin.parties.col.active_notes': 'Inyandiko zikora',
    'admin.parties.col.investors': 'Abashoramari',
    'admin.parties.col.raised': 'Byakusanyijwe',
    'admin.parties.col.capacity': 'Ubushobozi bwakoreshejwe',
    'admin.parties.col.status': 'Imiterere',
    'admin.parties.col.investor': 'Umushoramari',
    'admin.parties.col.kyc': 'KYC',
    'admin.parties.col.portfolio': 'Ishoramari',
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
    'admin.parties.kyc.verified': 'Byemejwe',
    'admin.parties.kyc.pending': 'Bitegereje',
    'admin.parties.kyc.overdue': 'Byarengeje igihe',
    'admin.parties.kyc.rejected': 'Byanzwe',
    'admin.parties.status.frozen': 'Yahagaritswe',
    'admin.parties.status.restricted': 'Ifite imbogamizi',
    'admin.parties.status.verified': 'Yemejwe',
    'admin.parties.status.pending': 'Itegereje',
    'admin.parties.status.active': 'Irakora',
    'admin.parties.standing.active': 'Arakora',
    'admin.parties.standing.pending': 'Ategereje kwemezwa',
    'admin.parties.standing.licence_expired': 'Uruhushya rwarangiye',
    'admin.parties.standing.suspended': "Yahagaritswe by'agateganyo",
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
    'admin.parties.stat.investors': 'Abashoramari',
    'admin.parties.stat.raised': 'Byakusanyijwe',
    'admin.parties.stat.rating': 'Amanota ya Rozine',
    'admin.parties.stat.capacity': 'Ubushobozi',
    'admin.parties.stat.capacity_used': 'Ubushobozi bwakoreshejwe',
    'admin.parties.stat.portfolio': 'Ishoramari',
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
    'admin.parties.note_status.draft': 'Umushinga',
    'admin.parties.note_status.active': 'Iri ku isoko',
    'admin.parties.note_status.funded': 'Yakusanyijwe',
    'admin.parties.note_status.repaying': 'Irishyurwa',
    'admin.parties.note_status.completed': 'Yarangiye',
    'admin.parties.note_status.failed': 'Yananiranye',
    'admin.parties.history': 'Amateka',
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
    'admin.ledger.empty': 'Nta cyanditswe muri iki gihe.',
    'admin.ledger.drawer_label': 'Icyanditswe {id}',
    'admin.ledger.fact.from': 'Kuva',
    'admin.ledger.fact.to': 'Kuri',
    'admin.ledger.fact.reference': 'Indango',
    'admin.ledger.fact.operation': 'Igikorwa',
    'admin.ledger.posted_by': 'Byanditswe na {actor} · {at}',
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
    'admin.events.no_reason': 'Nta mpamvu yanditswe — igikorwa cya sisitemu.',
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
    'business.wallet.status.active': 'Irakora',
    'business.wallet.status.frozen': 'Yahagaritswe',
    'business.wallet.settle_here':
        "Amafaranga y'ibikorwa, ayishyurwa n'ayoherezwa anyura hano",
    'business.wallet.brand': 'Ikofi ya Rozine',
    'business.wallet.deposit.button': 'Shyiramo',
    'business.wallet.withdraw.button': 'Bikuza',
    'business.wallet.deposit.title': 'Shyira amafaranga mu ikofi',
    'business.wallet.withdraw.title': 'Bikuza kuri konti',
    'business.wallet.deposit.method': 'Ishyura uvanye kuri',
    'business.wallet.withdraw.method': 'Bikuza kuri',
    'business.wallet.deposit.net': 'Amafaranga azaba ahari',
    'business.wallet.withdraw.net': 'Uzakira',
    'business.wallet.deposit.confirm': 'Emeza kubitsa',
    'business.wallet.withdraw.confirm': 'Emeza kubikuza',
    'business.wallet.processing': 'Birimo gukorwa…',
    'business.wallet.close_panel': 'Funga',
    'business.wallet.amount': 'Amafaranga',
    'business.wallet.fee': 'Ikiguzi cyo gukora',
    'business.wallet.network.mtn': 'MTN',
    'business.wallet.network.airtel': 'airtel',
    'business.wallet.history': "Amateka y'ibikorwa",
    'business.wallet.range.label': 'Igihe: {range}',
    'business.wallet.range.all': 'Igihe cyose',
    'business.wallet.range.span': '{from} – {to}',
    'business.wallet.range.start': 'Intangiriro',
    'business.wallet.range.now': 'Ubu',
    'business.wallet.range.quick': 'Igihe cyihuse',
    'business.wallet.range.days': 'Iminsi {count}',
    'business.wallet.range.from': 'Guhera',
    'business.wallet.range.to': 'Kugeza',
    'business.wallet.range.clear': 'Siba',
    'business.wallet.range.apply': 'Emeza',
    'business.wallet.export': 'Kuramo',
    'business.wallet.download_pdf': 'Kuramo PDF',
    'business.wallet.download_csv': 'Kuramo Excel',
    'business.wallet.empty': 'Nta bikorwa muri iki gihe.',
    'business.wallet.show_more': 'Erekana ibindi',
    'business.wallet.show_less': 'Erekana bike',
    'business.wallet.kind.deposit': 'Kubitsa',
    'business.wallet.kind.withdrawal': 'Kubikuza',
    'business.wallet.kind.disbursement': 'Kohererezwa inkunga',
    'business.wallet.kind.repayment': 'Kwishyura',
    'business.wallet.kind.services_fee': 'Ikiguzi cya serivisi',
    'business.wallet.kind.application_fee': 'Ikiguzi cyo gusaba',
    'business.wallet.tx_status.completed': 'Byarangiye',
    'business.wallet.tx_status.pending': 'Bitegereje',
    'business.wallet.tx_status.failed': 'Byanze',
    'business.wallet.detail.status': 'Uko bihagaze',
    'business.wallet.detail.reason': 'Impamvu',
    'business.wallet.detail.type': 'Ubwoko',
    'business.wallet.detail.credit': 'Ayinjiye',
    'business.wallet.detail.debit': 'Ayasohotse',
    'business.wallet.detail.when': "Itariki n'isaha",
    'business.wallet.detail.id': "Nomero y'igikorwa",
    'business.wallet.detail.reference': 'Indanganturo',
    'business.wallet.detail.before': 'Amafaranga yari ahari',
    'business.wallet.detail.after': 'Amafaranga asigaye',
    'business.wallet.detail.gross': 'Amafaranga yose',
    'business.wallet.detail.fee': 'Ikiguzi cyo gukora',
    'business.wallet.detail.net': 'Ayakiriwe',
    'business.wallet.detail.done': 'Birarangiye',

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
    'auditor.home.wallet_balance': 'Amafaranga ari mu gikapu',
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
    'auditor.file.doc_status.verified': '✓ Byemejwe',
    'auditor.file.doc_status.present': '✓ Birahari',
    'auditor.file.doc_status.missing': 'Ntibirimo',
    'auditor.file.prescreen': "Isuzuma ry'ibanze ryikora",
    'auditor.file.no_prescreen':
        "Nta gisubizo cy'isuzuma ry'ibanze ryikora kiratangazwa kuri iyi dosiye.",
    'auditor.file.check.met': 'Byujujwe',
    'auditor.file.check.flag': 'Ikimenyetso',
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
    'auditor.seal.apply': "Emeza ukoresheje porogaramu y'umutekano",
    'auditor.seal.submit': 'Shyiraho kashe wohereze kuri Rozine',
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
        "Raporo zose watanze, n'inyungu watangaje kuri dosiye ugenzura.",
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
    'auditor.reports.title': 'Raporo zatanzwe',
    'auditor.reports.count': '{count} zose',
    'auditor.reports.filters': 'Shungura raporo',
    'auditor.reports.filter.all': 'Zose',
    'auditor.reports.filter.awaiting_cosign': 'Zitegereje gusinywa',
    'auditor.reports.filter.published': 'Zatangajwe',
    'auditor.reports.filter.late': 'Zatinze',
    'auditor.reports.filter.rejected': 'Zanzwe',
    'auditor.reports.status.awaiting_cosign': 'Itegereje gusinywa',
    'auditor.reports.status.published': 'Yatangajwe',
    'auditor.reports.status.late': 'Yatinze',
    'auditor.reports.status.rejected': 'Yanzwe',
    'auditor.reports.flash': 'Igenzura ryihuse',
    'auditor.reports.monthly': 'Raporo ya {month}',
    'auditor.reports.late_by': '{title} · yatanzwe itinze iminsi {days}',
    'auditor.reports.district': 'Akarere',
    'auditor.reports.filed': 'Yatanzwe',
    'auditor.reports.due': 'Igihe ntarengwa',
    'auditor.reports.amend': 'Tangira umugereka uhujwe →',
    'auditor.reports.empty':
        "Nta raporo iratangwa. Raporo ushyiraho kashe zigaragara hano n'uko zasinywe.",
    'auditor.reports.empty_filter': "Nta raporo ihuye n'iri shungura.",
    'auditor.reports.show_all': 'Erekana raporo zose',
    'auditor.profile.head_title': 'Umwirondoro',
    'auditor.profile.title': 'Umwirondoro',
    'auditor.profile.menu': "Ibice by'umwirondoro",
    'auditor.profile.section.accreditation': "Uruhushya rw'umwuga",
    'auditor.profile.section.availability': "Igihe uboneka n'aho ukorera",
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
    'business.rating.financial.ebitda': 'EBITDA / ukwezi',
    'business.rating.financial.margin': 'Inyungu nyayo',
    'business.rating.financial.outstanding': 'Ibitarishyurwa',

    'business.repayments.title': 'Kwishyura',
    'business.repayments.progress': 'Aho kwishyura bigeze',
    'business.repayments.payments': 'Ubwishyu {made} / {total}',
    'business.repayments.remaining': 'Ibisigaye',
    'business.repayments.total': 'Igiteranyo',
    'business.repayments.total_note': 'igishoro + inyungu',
    'business.repayments.card_title.due': "Ubwishyu bw'uku kwezi",
    'business.repayments.card_title.overdue': 'Ubwishyu bwatinze',
    'business.repayments.card_title.paid': 'Ubwishyu butaha',
    'business.repayments.card_title.defaulted': 'Ayo ugomba kwishyura ubu',
    'business.repayments.overdue_day':
        'Byagombaga kwishyurwa ku wa {date} · byatinze umunsi {count}',
    'business.repayments.overdue_days':
        'Byagombaga kwishyurwa ku wa {date} · byatinze iminsi {count}',
    'business.repayments.paid_ahead':
        'Wishyuye mbere — nta kwishyura mbere ya {date}',
    'business.repayments.defaulted':
        'Iyi nyandiko yarenze igihe — gukurikirana mu mategeko byatangiye',
    'business.repayments.due_rule':
        'Kwishyura bikorwa ku wa {day} wa buri kwezi — umunsi amafaranga yawe yoherejwe — kugeza byishyuwe byose.',
    'business.repayments.source': 'Aho kwishyura biva',
    'business.repayments.confirm': 'Emeza kwishyura · {amount}',
    'business.repayments.ahead.title': "Ishyura mbere y'igihe",
    'business.repayments.ahead.subtitle':
        'Ishyura kare — nta gihano, kandi byongera amanota yawe',
    'business.repayments.ahead.choose': 'Hitamo amafaranga',
    'business.repayments.ahead.next_month': 'Ukwezi {count} gutaha',
    'business.repayments.ahead.next_months': 'Amezi {count} ataha',
    'business.repayments.ahead.upfront_one': 'Ishyura ubwishyu {count} mbere',
    'business.repayments.ahead.upfront': 'Ishyura ubwishyu {count} mbere',
    'business.repayments.ahead.full': 'Ishyura byose',
    'business.repayments.ahead.one_left': 'Hasigaye ubwishyu bumwe',
    'business.repayments.ahead.clear_note': 'Soza inyandiko yose',
    'business.repayments.ahead.custom': 'Amafaranga wihitiyemo',
    'business.repayments.ahead.placeholder': 'Andika amafaranga',
    'business.repayments.ahead.up_to':
        'Amafaranga ayo ari yo yose kugeza kuri {amount}',
    'business.repayments.ahead.too_much':
        'Amafaranga arenze ayo ugomba ({amount})',
    'business.repayments.ahead.why':
        'Kwishyura mbere bigabanya inyungu vuba, bigasiga ubushobozi bwo gukusanya ubutaha, kandi bigakomeza amateka yawe yo kwishyura — ari byo bigira uruhare runini ku manota yawe ya Rozine.',
    'business.repayments.ahead.pay_cta': 'Ishyura mbere · {amount}',
    'business.repayments.ahead.settle_cta': 'Ishyura byose · {amount}',
    'business.repayments.ahead.enter': 'Andika amafaranga',
    'business.repayments.schedule': 'Gahunda yo kwishyura',
    'business.repayments.row.paid': 'Byishyuwe · {date}',
    'business.repayments.row.due': 'Bigomba kwishyurwa · {date}',
    'business.repayments.row.overdue': 'Byatinze · {date}',
    'business.repayments.status.paid': 'Byishyuwe',
    'business.repayments.status.due': 'Bigomba kwishyurwa',
    'business.repayments.status.overdue': 'Byatinze',
    'business.repayments.status.upcoming': 'Biraje',
    'business.repayments.late.title': 'Iyo ubwishyu butinze',
    'business.repayments.late.intro':
        "Amande y'ubukererwe yongerwa ku bwishyu bwari butegerejwe. Uko bitinda kwishyurwa, amanota yawe ya Rozine agabanuka.",
    'business.repayments.late.due_day': 'Umunsi wo kwishyura warenze',
    'business.repayments.late.due_day_body':
        'Amande ya {percent}% yongerwa ku giteranyo cyari gitegerejwe iyo umunsi wo kwishyura urangiye.',
    'business.repayments.late.day_7': 'Umunsi wa 7 utishyuwe',
    'business.repayments.late.day_7_body':
        "Hongerwaho andi {percent}% y'ayari ategerejwe ku munsi wa 7 nyuma y'itariki yo kwishyura.",
    'business.repayments.late.day_30': 'Umunsi wa 30 utishyuwe',
    'business.repayments.late.day_30_body':
        "Hongerwaho andi {percent}% y'ayari ategerejwe kandi gukurikirana mu mategeko bigatangira. Itsinda ry'amategeko rya Rozine cyangwa abafatanyabikorwa bavugana nawe kuri imeli na telefoni y'ikigo mu masaha 24.",
    'business.repayments.late.fee': '+{percent}%',
    'business.repayments.late.fee_legal': '+{percent}% · amategeko',
    'business.repayments.late.total': 'Ayo ugomba kwishyura → {amount}',
    'business.repayments.late.halted': "Umwirondoro n'ibikorwa birahagarikwa",
    'business.repayments.late.halted_body':
        "Iyo umunsi wa 30 urenze nta kwishyura cyangwa gusaba isuzuma ry'abakozi ba Rozine, umwirondoro wawe n'ibikorwa biriho birahagarikwa kugeza bikemutse — cyangwa burundu niba Rozine ibibonye bikwiye.",
    'business.repayments.late.warn_title': 'Ubibonye biza? Bivuge mbere.',
    'business.repayments.late.warn_body':
        "Imura ubwishyu bumwe ubujyane ku iherezo ry'igihe. Rimwe kuri buri nyandiko, mbere y'itariki yo kwishyura. Amanota yawe amanuka urwego rumwe kandi bihenda kurusha kwishyura ku gihe.",
    'business.repayments.late.defer': 'Imura ubwishyu',
    'business.repayments.late.review': "Saba isuzuma ry'abakozi",
    'business.repayments.done.title': 'Ubwishyu bwakozwe',
    'business.repayments.done.body': '{amount} byishyuwe abashoramari {count}.',
    'business.repayments.done.outstanding': 'Ibisigaye kwishyurwa',
    'business.repayments.done.made': 'Ubwishyu bwakozwe',
    'business.repayments.done.home': 'Subira ku rupapuro rukuru',

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
    'investor.deal.term': 'IGIHE',
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
    'investor.checkout.payout_fee':
        'Ikiguzi cyo kwishyura (1% kuri buri kwishyurwa)',
    'investor.checkout.maturity_value': 'Agaciro ku iherezo · {date}',
    'investor.checkout.pay_with': 'ISHYURA UKORESHEJE',
    'investor.checkout.wallet': 'Ikofi',
    'investor.checkout.wallet_detail': 'Ikofi ya Rozine · {amount} birahari',
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
    'investor.checkout.confirm': 'Emeza · {amount}',
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
        'Gurisha kuri Rozine, cyangwa ku isoko, igihe cyose',
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
    'investor.portfolio.browse_deals': 'Reba amahirwe',
    'investor.portfolio.total_value': 'AGACIRO KOSE',
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
    'investor.portfolio.idle': 'BIDAKORA KU IKOFI',
    'investor.portfolio.idle_body': 'Bishore mu rupapuro rushya.',
    'investor.portfolio.reinvest': 'Ongera ushore ›',
    'investor.holding.frozen_title': 'Uru rupapuro rwahagaritswe',
    'investor.holding.frozen_body':
        'Rozine yahagaritse uru rupapuro mu gihe hakorwa isuzuma. Ubwishyu buzasubukurwa rurekuwe; uburenganzira bwawe ntibuhinduka.',
    'investor.holding.invested': 'BYASHOWE',
    'investor.holding.expected_profit': 'INYUNGU ITEGANYIJWE',
    'investor.holding.yield': 'Inyungu ya {rate}%',
    'investor.holding.total_at_maturity': 'BYOSE KU IHEREZO',
    'investor.holding.principal_yield': 'Igishoro + inyungu',
    'investor.holding.received': 'BYAKIRIWE KUGEZA UBU',
    'investor.holding.payments': '{made}/{total} byishyuwe',
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
    'investor.wallet.method_to': 'OHEREZA KURI',
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
    'investor.profile.member_since': 'Umunyamuryango kuva {date}',
    'investor.profile.item.linked': 'Konti zahujwe',
    'investor.profile.item.statements': "Inyandiko z'imari",
    'investor.profile.item.verification': "Igenzura ry'umwirondoro",
    'investor.profile.item.terms': "Amabwiriza n'Amategeko",
    'investor.profile.item.privacy': 'Itangazo ku Ibanga',
    'investor.profile.sign_out': 'Sohoka',
    'investor.profile.linked.unlink': 'Kuraho',
    'investor.profile.linked.unverified': 'Biragenzurwa',
    'investor.profile.linked.none':
        'Nta konti yo kwishyurirwaho. Huza imwe ushyireho amafaranga kandi wakire ubwishyu.',
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
    'settlement.refusal.NOTE_INELIGIBLE': 'Uru rupapuro ntirwemerewe ubu.',
    'settlement.refusal.DISCLOSURE_STALE':
        'Amakuru yo kumenyesha yahindutse. Soma ayariho ubu wongere uyemeze.',
    'settlement.refusal.POLICY_INPUT_REQUIRED':
        'Ibi ntibiraboneka: hari amabwiriza akenewe atarashyirwaho.',
    'settlement.refusal.DEPOSIT_METHOD_UNVERIFIED':
        'Iyo konti ntiragenzurwa ngo ikoreshwe mu kubitsa.',
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
    'investor.wallet.c3.total': "Igiteranyo cy'ikofi",
    'investor.wallet.c3.bucket.available': 'Aboneka',
    'investor.wallet.c3.bucket.held': 'Afashwe',
    'investor.wallet.c3.bucket.committed': 'Yiyemejwe',
    'investor.wallet.c3.spendable':
        'Aboneka ni yo yonyine ishobora gukoreshwa. Afashwe ari mu kwishyura gukomeje; ayiyemejwe ategereje gutangwa.',
    'investor.wallet.c3.restricted':
        'Hari ikumira rikurikizwa kuva ku wa {date}. Kubitsa biracyashoboka.',
    'investor.wallet.c3.no_pending': 'Nta kubitsa gutegereje',
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
    'investor.wallet.c3.deposit_from': 'Kubitsa uvuye kuri {method}',
    'investor.wallet.c3.intent.pending': 'Ntibiremezwa',
    'investor.wallet.c3.intent.unknown':
        "Ntibiremezwa — turi kugenzura n'utanga serivisi",
    'investor.wallet.c3.intent.succeeded': 'Byashyizwe kuri konti',
    'investor.wallet.c3.intent.failed':
        'Ntibyakunze — nta cyashyizwe kuri konti',
    'investor.wallet.c3.entry.deposit_in': 'Kubitsa uvuye kuri {counterparty}',
    'investor.wallet.c3.entry.deposit_out': 'Kohereza kuri {counterparty}',
    'investor.wallet.c3.entry.hold': 'Afashwe kuri {name}',
    'investor.wallet.c3.entry.hold_release': 'Ifatwa ryarekuwe · {name}',
    'investor.wallet.c3.entry.commitment': 'Yiyemejwe muri {name}',
    'investor.wallet.c3.entry.commitment_refund': 'Gusubizwa · {name}',
    'investor.wallet.c3.movement': "Ubwoko bw'imyimukire",
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
    'business.campaign.lifecycle.live': 'Irakusanya · irakora',
    'business.campaign.lifecycle.fully_reserved': 'Irakusanya · yose yafashwe',
    'business.campaign.restriction.RESTRICTION_ACTIVE':
        'Yashyizweho imbogamizi kuva ku wa {date}. Ibyiyemezo bishya birahagaritswe igihe imbogamizi imara; ibyamaze kwiyemezwa birahaguma.',
    'business.campaign.restriction.NOTE_INELIGIBLE':
        'Ntiyemerewe ibyiyemezo bishya kuva ku wa {date}; ibyamaze kwiyemezwa birahaguma.',
    'business.campaign.tile.committed': 'Byiyemejwe',
    'business.campaign.tile.refunded': 'Byasubijwe',
    'business.campaign.closing_now': 'Birarangira',
    'business.campaign.committed': 'Byiyemejwe',
    'business.campaign.reserved': 'Byafashwe',
    'business.campaign.reserved_note':
        "Inyandiko zafashwe ziri mu kwishyura kw'abashoramari kutararangira kandi ntiziriyemezwa; ifatwa ritemejwe rirekurwa nyuma y'iminota 5.",
    'business.campaign.units':
        'Inyandiko {committed} kuri {total} ziyemejwe · {reserved} zafashwe · {available} ziraboneka',
    'business.campaign.closes': 'Birangira ku wa {date}',
    'business.campaign.fully_reserved':
        'Inyandiko zose zafashwe mu kwishyura kutararangira. Amafatwa atemejwe mu minota 5 asubizwa ku isoko.',
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
        'Iki gikorwa cyo gukusanya ntikyashoboye kurangira: igenzura ryo mbere yo kohereza amafaranga ryananiranye ku wa {date}. {amount} byasubijwe abashoramari byose, nta kiguzi.',
    'business.campaign.cancel.open': 'Hagarika iki gikorwa cyo gukusanya',
    'business.campaign.cancel.cancelling': 'Birahagarikwa…',
    'business.campaign.cancel.title': 'Uhagarike iki gikorwa cyo gukusanya?',
    'business.campaign.cancel.body':
        'Buri mushoramari asubizwa ibyo yiyemeje byose, nta kiguzi, kandi igikorwa cyo gukusanya kirafungwa burundu. Ntibishobora gusubizwa inyuma.',
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
    'admin.disbursements.action.authorize': 'Emerera',
    'admin.disbursements.older': 'Kwishyura kwa kera',
    'admin.disbursements.deadline': 'Itariki ntarengwa',
    'admin.disbursements.deadline_unavailable': 'Nta tariki ntarengwa yemejwe',
    'admin.disbursements.rule_two_staff':
        "Hakenewe abakozi babiri batandukanye: umwe aremerera, undi akemeza. Nta rugero rw'amafaranga cyangwa uburenganzira bwo kubirengaho. Inshingano ni amazina gusa: ibyo ushobora gukora biva ku burenganzira bwawe.",
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
    'admin.disbursements.binding.title': 'Ibyo kwemeza bihuza',
    'admin.disbursements.binding.none': 'Bishyirwaho iyo kwishyura byemerewe.',
    'admin.disbursements.binding.revision': 'Ivugurura',
    'admin.disbursements.binding.amount': 'Amafaranga nyayo',
    'admin.disbursements.binding.digest': "Igikumwe cy'icyifuzo",
    'admin.disbursements.step_up.unavailable':
        "Kwemeza bisaba kwemeza kongerewe umutekano gushya guhujwe n'aya makuru. Uko kwemeza ntikuraboneka, bityo ntushobora kwemeza hano.",
    'admin.disbursements.step_up.required':
        "Kwemeza bisaba kwemeza kongerewe umutekano gushya guhujwe n'aya makuru.",
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
        'Gukuraho ihagarikwa ntibyemeza uku kwishyura kandi ntibyohereza amafaranga. Bisaba umukozi utari uwarishyizeho.',
    'admin.disbursements.hold.self':
        'Ni wowe washyizeho iri hagarikwa, bityo undi mukozi ni we ugomba kurikuraho.',
    'admin.disbursements.issue.title': 'Isohora',
    'admin.disbursements.issue.holdings': 'Imigabane yasohowe',
    'admin.disbursements.issue.issued_at': 'Byasohowe ku',
    'admin.disbursements.issue.effective_date': 'Itariki bitangira (Kigali)',
    'admin.disbursements.refund.title': 'Gusubiza amafaranga',
    'admin.disbursements.refund.commitments': 'Ibyiyemejwe byasubijwe',
    'admin.disbursements.refund.total': 'Igiteranyo cyasubijwe',
    'admin.disbursements.ledger': "Fungura mu gitabo cy'ibaruramari",
    'admin.disbursements.command.release_hold': 'Kuraho ihagarikwa',
    'admin.disbursements.command.requery': 'Ongera ubaze utanga serivisi',
    'admin.disbursements.stage.release_hold.title': 'Kuraho iri hagarikwa',
    'admin.disbursements.stage.release_hold.body':
        "Gukuraho ihagarikwa ntibyemeza uku kwishyura kandi ntibyohereza amafaranga. Bisubira aho byari biri mbere y'ihagarikwa.",
    'admin.disbursements.stage.release_hold.cta': 'Kuraho ihagarikwa',
    'admin.disbursements.stage.release_hold.placeholder':
        'urugero: Ikigo cyemeje nimero yacyo nshya ya MoMo.',
    'admin.disbursements.stage.requery.title': 'Ongera ubaze utanga serivisi',
    'admin.disbursements.stage.requery.body':
        'Ibi bibaza utanga serivisi ku gikorwa kimwe. Ntibyongera kohereza ukwishyura.',
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
    'investor.deal.lifecycle.funded': 'Imari yose yabonetse',
    'investor.deal.lifecycle.disbursing': 'Birimo kwishyurwa',
    'investor.deal.lifecycle.issued': 'Impapuro zatanzwe',
    'investor.deal.lifecycle.expired': 'Ntibyuzuye',
    'investor.deal.lifecycle.cancelled': 'Byahagaritswe',
    'investor.deal.lifecycle.failed_closing': 'Byafunzwe, amafaranga asubizwa',
    'investor.deal.notice.fully_reserved.title': 'Impapuro zose zafashwe ubu',
    'investor.deal.notice.fully_reserved.body':
        'Kwishyura bifata impapuro kugeza ku minota 5. Izo igihe cyazo kirangiye zigaruka muri iki gikorwa.',
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
        'Igenzura rya nyuma mbere yo kwishyura ntiryatsinze, buri cyiyemezo cyasubijwe cyose.',
    'investor.deals.gated_title':
        'Genzura umwirondoro kugira ngo ubone amahirwe afunguye',
    'investor.deals.gated_body':
        "Amahirwe n'ibigo byayo yerekwa gusa abashoramari bagenzuwe.",
    'investor.deals.paused': 'Byahagaritswe',
    'investor.deal.cap.max': {
        one: 'Kugeza ku rupapuro {count}: {reason}.',
        other: 'Kugeza ku mpapuro {count}: {reason}.',
    },
    'investor.deal.cap.none':
        'Ntushobora gufata izindi mpapuro hano: {reason}.',
    'investor.deal.cap.reason.transaction': 'umupaka wawe kuri buri gikorwa',
    'investor.deal.cap.reason.note': 'umupaka wawe kuri uru rupapuro',
    'investor.deal.cap.reason.business': 'umupaka wawe kuri iki kigo',
    'investor.deal.cap.reason.aggregate': "umupaka wawe rusange w'ishoramari",
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
        'Igenzura rya nyuma mbere yo kwishyura ntiryatsinze. Igishoro cyawe cyasubijwe cyose, nta kiguzi.',
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
        'Igishoro cyawe gisubira muri aboneka cyose, nta kiguzi, kandi izi mpapuro zirarekurwa.',
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
};

export default rw;
