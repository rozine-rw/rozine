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

    'business.apply.head_title': 'Saba igishoro',
    'business.apply.label': "Ubusabe bw'igishoro",
    'business.apply.close': 'Funga',
    'business.apply.back': 'Subira inyuma',
    'business.apply.progress': 'Aho ubusabe bugeze',
    'business.apply.step_of': 'Intambwe ya {step} kuri {total}',
    'business.apply.continue': 'Komeza',
    'business.apply.saving': 'Birabikwa…',
    'business.apply.submit': 'Ohereza urupapuro',
    'business.apply.submitting': 'Birimo koherezwa…',
    'business.apply.incomplete': 'Banza urangize iyi ntambwe',
    'business.apply.business.title': "Ubucuruzi n'imari",
    'business.apply.business.subtitle':
        "Bivuye ku cyemezo cyawe cya RDB n'imyaka 5 y'inyandiko za banki na mobile money, byagenzuwe na OCR. Bisuzume — niba ari byo, komeza.",
    'business.apply.business.rdb_verified': '✓ Byemejwe na RDB',
    'business.apply.business.statements_verified': '✓ Inyandiko zemejwe · OCR',
    'business.apply.business.active': '● Irakora',
    'business.apply.business.established': 'Yashinzwe {year}',
    'business.apply.business.officer.ceo': 'Umuyobozi mukuru',
    'business.apply.business.officer.board_chair':
        "Perezida w'inama y'ubuyobozi",
    'business.apply.business.standing': 'Uko imari ihagaze · imyaka {years}',
    'business.apply.business.ocr_verified': 'OCR · byemejwe',
    'business.apply.business.revenue': 'Amafaranga yinjiye',
    'business.apply.business.costs': 'Ibyakoreshejwe',
    'business.apply.business.net_profit': 'Inyungu nyayo',
    'business.apply.business.existing_debt': 'Umwenda usanzwe',
    'business.apply.business.crb_verified': '✓ Byemejwe na CRB',
    'business.apply.business.year_by_year': 'Umwaka ku wundi',
    'business.apply.business.capacity': 'Ubushobozi bwemejwe',
    'business.apply.business.capacity_basis': '· amafaranga × ububiko',
    'business.apply.business.capacity_pending': 'Igenzura ritegerejwe',
    'business.apply.business.capacity_body':
        'Igishoro kinini amafaranga ubucuruzi bwawe bwinjiza ashobora kwishyura hakurikijwe ububiko bwagenzuwe. Ubusabe burenze aha bwangwa mu buryo bwikora.',
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
    'business.apply.raise.rate_term_detail':
        '+{points} ku gipimo kuri iki gihe',
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
    'business.apply.review.disclosure.accuracy':
        'Nemeza ko amakuru yose natanze ari ukuri.',
    'business.apply.review.disclosure.obligations':
        "Nsobanukiwe inshingano z'amategeko zo gutanga uru rupapuro.",
    'business.apply.review.disclosure.statements':
        'Nzashyiraho ku gihe inyandiko za banki na MoMo za buri kwezi kugira ngo zigenzurwe.',
    'business.apply.review.disclosure.repayment':
        'Nemera inshingano zo kwishyura abashoramari.',
    'business.apply.review.agreements': 'Amasezerano',
    'business.apply.review.agree_terms_prefix': 'Nemeye',
    'business.apply.review.agree_privacy_prefix': 'Nasomye',
    'business.apply.review.document.terms': "Amabwiriza n'amasezerano",
    'business.apply.review.document.privacy': "Itangazo ry'ibanga",
    'business.apply.review.read': 'Soma',
    'business.apply.review.document_intro':
        "Rozine · Repubulika y'u Rwanda · verisiyo {version}. Incamake y'ingingo z'ingenzi — amasezerano yuzuye ari muri Umwirondoro.",
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
        "Umukono wawe utegeka ubucuruzi mu mategeko kubahiriza inshingano zatangajwe. Nimero y'urupapuro itangwa iyo wohereje.",
    'business.apply.submitted.title': 'Urupapuro rwawe rwoherejwe',
    'business.apply.submitted.body':
        'Urupapuro rwawe rurimo gusuzumwa. Uzamenyeshwa buri ntambwe.',
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

    'auth.two_factor.recovery_codes_remaining': {
        one: 'Hasigaye kode {count} yo kugarura konti',
        other: 'Hasigaye kode {count} zo kugarura konti',
    },
};

export default rw;
