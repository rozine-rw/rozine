/**
 * French catalog.
 *
 * Structurally complete against `en`. Copy is engineering-supplied and still needs a professional
 * review pass before any locale is activated under D-07.
 */
import type { Catalog } from '@/lib/i18n/types';

const fr: Catalog = {
    'environment.demo': 'Démo — hors production',
    'environment.uat': 'UAT — hors production',
    'environment.synthetic_only':
        'Utilisez uniquement des données fictives. Aucune transaction en argent réel.',
    'common.brand.name': 'Rozine',
    'common.action.log_in': 'Se connecter',
    'common.action.sign_up': "S'inscrire",

    'auth.login.head_title': 'Connexion',
    'auth.login.layout_title': 'Connectez-vous à votre compte',
    'auth.login.layout_description':
        'Saisissez votre e-mail et votre mot de passe pour vous connecter',
    'auth.login.email_label': 'Adresse e-mail',
    'auth.login.email_placeholder': 'email@exemple.com',
    'auth.login.password_label': 'Mot de passe',
    'auth.login.password_placeholder': 'Mot de passe',
    'auth.login.forgot_password': 'Mot de passe oublié ?',
    'auth.login.remember_me': 'Se souvenir de moi',
    'auth.login.no_account': 'Vous n’avez pas de compte ?',

    'auth.forgot_password.head_title': 'Mot de passe oublié',
    'auth.forgot_password.layout_title': 'Mot de passe oublié',
    'auth.forgot_password.layout_description':
        'Saisissez votre e-mail pour recevoir un lien de réinitialisation',
    'auth.forgot_password.email_label': 'Adresse e-mail',
    'auth.forgot_password.email_placeholder': 'email@exemple.com',
    'auth.forgot_password.submit': 'Envoyer le lien de réinitialisation',
    'auth.forgot_password.return_prefix': 'Ou revenez à',
    'auth.forgot_password.return_link': 'la connexion',

    'suite.head_title': 'Vos applications',
    'suite.tagline':
        'La couche des marchés de capitaux de détail pour les économies émergentes',
    'suite.motto': 'Un seul cœur en direct · chaque surface',
    'suite.section.apps': 'Vos applications',
    'suite.app.open': "Ouvrir l'application →",
    'suite.app.investor.title': 'Investisseur',
    'suite.app.investor.description':
        'Découvrez des entreprises vérifiées, investissez, suivez vos rendements.',
    'suite.app.business.title': 'Entreprise',
    'suite.app.business.description':
        'Levez des capitaux, rendez compte chaque mois aux investisseurs.',
    'suite.app.auditor.title': 'Auditeur',
    'suite.app.auditor.description':
        'Vérifiez sur site, auditez les rapports, percevez un rendement.',
    'suite.blocker.action.verify_email': 'Vérifier votre e-mail →',
    'suite.blocker.action.contact': 'Contacter le support Rozine →',
    'suite.blocker.EMAIL_VERIFICATION_REQUIRED.title':
        "Vérifiez d'abord votre e-mail",
    'suite.blocker.EMAIL_VERIFICATION_REQUIRED.body':
        'Confirmez le lien que nous vous avons envoyé, puis vos applications apparaîtront ici.',
    'suite.blocker.IDENTITY_NOT_LINKED.title':
        "Votre identité n'est pas encore configurée",
    'suite.blocker.IDENTITY_NOT_LINKED.body':
        "Votre connexion n'est liée à aucune identité vérifiée ; aucune application ne peut encore s'ouvrir.",
    'suite.blocker.IDENTITY_VERIFICATION_REQUIRED.title':
        'Votre identité est en cours de vérification',
    'suite.blocker.IDENTITY_VERIFICATION_REQUIRED.body':
        "Vos applications s'ouvriront une fois la vérification terminée.",
    'suite.blocker.PARTY_AUTHORITY_REQUIRED.title':
        'Un pouvoir de signature est requis',
    'suite.blocker.PARTY_AUTHORITY_REQUIRED.body':
        "Ce compte d'organisation nécessite un signataire vérifié avant qu'une application puisse s'ouvrir.",
    'suite.blocker.ROLE_MEMBERSHIP_INVALID.title':
        'Votre compte nécessite une intervention',
    'suite.blocker.ROLE_MEMBERSHIP_INVALID.body':
        "L'une de vos adhésions n'a pas pu être lue. Le support peut la corriger.",
    'suite.blocker.ROLE_MEMBERSHIP_CONFLICT.title':
        'Ces applications ne peuvent pas être combinées',
    'suite.blocker.ROLE_MEMBERSHIP_CONFLICT.body':
        "Un partenaire d'audit ne peut pas aussi investir ou lever des fonds sur Rozine. Le support vous aidera à choisir.",
    'suite.blocker.ROLE_MEMBERSHIP_REQUIRED.title':
        "Aucune application pour l'instant",
    'suite.blocker.ROLE_MEMBERSHIP_REQUIRED.body':
        "Vous n'avez encore rejoint aucune application. Le support peut configurer celle qu'il vous faut.",

    'business.auth.wordmark': 'rozine',
    'business.auth.for_business': 'Pour les entreprises',
    'business.auth.rdb_verified': 'Vérifié par le RDB',
    'business.auth.secure': 'Sécurisé',
    'business.auth.please_wait': 'Veuillez patienter…',
    'business.auth.login.head_title': 'Connexion entreprise',
    'business.auth.login.title': 'Bon retour',
    'business.auth.login.subtitle':
        'Connectez-vous à votre tableau de bord émetteur.',
    'business.auth.login.email_label': 'E-mail professionnel',
    'business.auth.login.email_placeholder': 'vous@entreprise.rw',
    'business.auth.login.password_label': 'Mot de passe',
    'business.auth.login.password_placeholder': '••••••••',
    'business.auth.login.submit': 'Se connecter',
    'business.auth.login.switch_prompt': 'Nouveau sur Rozine ?',
    'business.auth.login.switch_action': 'Enregistrer une entreprise',
    'business.auth.register.head_title': 'Enregistrer votre entreprise',
    'business.auth.register.title': 'Enregistrez votre entreprise',
    'business.auth.register.subtitle':
        'Vérifiez votre entreprise auprès du Rwanda Development Board.',
    'business.auth.register.code_label': "Code d'entreprise RDB",
    'business.auth.register.code_placeholder': '102938475',
    'business.auth.register.rdb_note':
        'Nous vérifions votre entreprise directement auprès du Rwanda Development Board — aucune connexion personnelle requise.',
    'business.auth.register.matched': '✓ Entreprise trouvée au RDB',
    'business.auth.register.name_label': "Nom enregistré de l'entreprise",
    'business.auth.register.name_placeholder': 'ex. Karongi Freight Ltd',
    'business.auth.register.code_sent':
        "Saisissez le code à 6 chiffres envoyé au téléphone et à l'e-mail enregistrés pour cette entreprise au RDB.",
    'business.auth.register.otp_label': 'Code à usage unique',
    'business.auth.register.otp_placeholder': 'Code à 6 chiffres',
    'business.auth.register.resend': 'Renvoyer le code',
    'business.auth.register.verify_company': "Vérifier le code d'entreprise",
    'business.auth.register.verifying_company': 'Vérification auprès du RDB…',
    'business.auth.register.verify_code': 'Vérifier et continuer',
    'business.auth.register.verifying_code': 'Vérification du code…',
    'business.auth.register.switch_prompt': 'Déjà enregistré ?',
    'business.auth.register.switch_action': 'Se connecter',

    'common.currency.rwf': 'RWF',
    'app.nav.label': "Navigation de l'application",
    'app.nav.launcher': 'Lanceur',
    'business.nav.home': 'Accueil',
    'business.nav.reports': 'Rapports',
    'business.nav.profile': 'Profil',
    'business.home.head_title': 'Accueil',
    'business.home.wallet_balance': 'Solde du portefeuille',
    'business.home.deposit': 'Déposer',
    'business.home.withdraw': 'Retirer',
    'business.home.notifications': 'Notifications',
    'business.home.notifications_unread': 'Notifications, {count} non lues',
    'business.home.company_code': 'RDB {code}',
    'business.home.rozine_rating': 'Notation Rozine',
    'business.rating.pending': 'Audit en attente',
    'business.rating.band.strong': 'Solide',
    'business.rating.band.stable': 'Stable',
    'business.rating.band.weak': 'Faible',
    'business.rating.band.distressed': 'En difficulté',
    'business.home.raising_now': 'Levée en cours',
    'business.home.funded_progress': 'Progression du financement de {title}',
    'business.home.raised_of': '{raised} sur {target}',
    'business.home.investors_link': '{count} investisseurs ›',
    'business.today.title': "Aujourd'hui",
    'business.today.subtitle': 'Ce qui requiert votre attention maintenant.',
    'business.today.approved.kicker': 'Demande approuvée',
    'business.today.approved.sub':
        'Payez les frais de demande de {fee} pour la publier aux investisseurs.',
    'business.today.approved.cta': 'Payer et publier',
    'business.today.declined.kicker': 'Demande refusée',
    'business.today.declined.sub':
        'Au-delà de votre capacité approuvée — contactez-nous avant de soumettre à nouveau.',
    'business.today.declined.cta': 'Voir pourquoi',
    'business.today.disbursement.kicker': 'Levée terminée',
    'business.today.disbursement.cta': 'Confirmer le décaissement',
    'business.today.audit.kicker': "Période d'audit",
    'business.today.audit.title': 'Audit de {month}',
    'business.today.audit.sub_open':
        "Votre CPA passe après la fin du mois · scellé d'ici le {sealed}",
    'business.today.audit.sub_closing':
        "Le mois se termine dans {days} jours · scellé d'ici le {sealed}",
    'business.today.audit.cta': 'Se préparer',
    'business.today.repayment.kicker': 'Prochain remboursement',
    'business.today.repayment.sub': '{note} · Échéance le {date}',
    'business.today.repayment.cta': 'Payer maintenant',
    'business.today.caught_up.title': 'Vous êtes à jour',
    'business.today.caught_up.body':
        'Aucun remboursement ni action en attente. Nous vous préviendrons dès que quelque chose vous concernera.',
    'business.capital.title': 'Votre capital',
    'business.capital.subtitle':
        'Votre historique complet, sur toutes vos notes.',
    'business.capital.active_notes': '{count} active(s)',
    'business.capital.about': 'À propos de {label}',
    'business.capital.close': 'Fermer',
    'business.capital.raised.label': 'Levé',
    'business.capital.raised.about':
        'Capital total levé auprès des investisseurs sur toutes les notes que vous avez émises sur Rozine.',
    'business.capital.investors.label': 'Investisseurs',
    'business.capital.investors.about':
        'Investisseurs distincts qui soutiennent vos notes. Quiconque a investi dans plusieurs de vos notes est compté une fois.',
    'business.capital.repaid.label': 'Remboursé',
    'business.capital.repaid.about':
        'Total des rendements versés aux investisseurs sur toutes vos notes à ce jour.',
    'business.capital.on_time.label': 'À temps',
    'business.capital.on_time.about':
        "Part de vos remboursements prévus effectués à ou avant l'échéance, depuis le début.",
    'business.note.title': 'Vos notes',
    'business.note.filter_label': 'Filtrer les notes par statut',
    'business.note.filter.draft': 'Brouillon',
    'business.note.filter.active': 'Active',
    'business.note.filter.repaying': 'En remboursement',
    'business.note.filter.failed': 'Échouée',
    'business.note.filter.archived': 'Archivée',
    'business.note.status.draft': 'Brouillon',
    'business.note.status.active': 'Active',
    'business.note.status.funded': 'Financée',
    'business.note.status.repaying': 'En remboursement',
    'business.note.status.completed': 'Terminée',
    'business.note.status.failed': 'Échouée',
    'business.note.funded': 'financé',
    'business.note.investors': '{count} investisseurs',
    'business.note.continue_application': 'Reprendre la demande',
    'business.note.empty.title': 'Aucune note avec ce statut',
    'business.note.empty.body':
        'Lancez une levée pour financer votre entreprise.',
    'business.grow.title': 'Croître',
    'business.grow.subtitle': 'Levez davantage quand vous êtes prêt.',
    'business.grow.headroom': 'Marge disponible',
    'business.grow.headroom_body':
        'Vous pouvez encore lever ce montant maintenant.',
    'business.grow.apply': 'Demander une levée',

    'business.apply.head_title': 'Demander une levée',
    'business.apply.label': 'Demande de levée',
    'business.apply.close': 'Fermer',
    'business.apply.back': 'Retour',
    'business.apply.progress': 'Progression de la demande',
    'business.apply.step_of': 'Étape {step} sur {total}',
    'business.apply.continue': 'Continuer',
    'business.apply.saving': 'Enregistrement…',
    'business.apply.submit': 'Soumettre la note',
    'business.apply.submitting': 'Envoi…',
    'business.apply.incomplete': 'Terminez cette étape pour continuer',
    'business.apply.business.title': 'Entreprise et finances',
    'business.apply.business.subtitle':
        'À partir de votre certificat RDB et de 5 ans de relevés bancaires et mobile money, vérifiés par OCR. Vérifiez — si tout est correct, continuez.',
    'business.apply.business.rdb_verified': '✓ Vérifié par le RDB',
    'business.apply.business.statements_verified': '✓ Relevés vérifiés · OCR',
    'business.apply.business.active': '● Active',
    'business.apply.business.established': 'Fondée en {year}',
    'business.apply.business.officer.ceo': 'Directeur général',
    'business.apply.business.officer.board_chair': 'Président du conseil',
    'business.apply.business.standing': 'Situation financière · {years} ans',
    'business.apply.business.ocr_verified': 'OCR · vérifié',
    'business.apply.business.revenue': "Chiffre d'affaires",
    'business.apply.business.costs': 'Coûts',
    'business.apply.business.net_profit': 'Bénéfice net',
    'business.apply.business.existing_debt': 'Dette existante',
    'business.apply.business.crb_verified': '✓ Vérifié par le CRB',
    'business.apply.business.year_by_year': 'Année par année',
    'business.apply.business.capacity': 'Capacité approuvée',
    'business.apply.business.capacity_basis':
        '· trésorerie × couverture des stocks',
    'business.apply.business.capacity_pending': 'Audit en attente',
    'business.apply.business.capacity_body':
        'La plus grande levée que la trésorerie de votre entreprise peut supporter selon la couverture de stocks vérifiée. Les demandes supérieures sont refusées automatiquement.',
    'business.apply.raise.title': 'Votre levée',
    'business.apply.raise.subtitle':
        'Fixez vos conditions et présentez-vous aux investisseurs.',
    'business.apply.raise.fundraise': '1 · Levée',
    'business.apply.raise.note_title': 'Titre de la note · objet de la levée',
    'business.apply.raise.note_title_placeholder':
        'ex. Entrepôt frigorifique, Extension de flotte…',
    'business.apply.raise.note_title_help':
        'Nommez le projet précis que les investisseurs financent.',
    'business.apply.raise.target': 'Objectif de levée (RWF)',
    'business.apply.raise.term': 'Durée',
    'business.apply.raise.term_months': '{months} mois',
    'business.apply.raise.rate': 'Taux',
    'business.apply.raise.rate_info': 'Comment ce taux est fixé',
    'business.apply.raise.rate_how': 'Comment votre taux est fixé',
    'business.apply.raise.rate_one_charge':
        'Un seul coût forfaitaire sur ce que vous empruntez. Il couvre tout — aucuns frais de dossier, de gestion ni prélèvement en plus.',
    'business.apply.raise.rate_yours': 'Votre notation',
    'business.apply.raise.rate_rated': 'Notée {band}',
    'business.apply.raise.rate_best': 'Meilleure notation, 3 mois',
    'business.apply.raise.rate_best_detail': 'Meilleur taux sur Rozine',
    'business.apply.raise.rate_term_detail':
        '+{points} points pour cette durée',
    'business.apply.raise.rate_cap': 'Jamais au-dessus de {cap} %',
    'business.apply.raise.quote_pending':
        'Saisissez un objectif et une durée pour voir votre offre.',
    'business.apply.raise.notes': 'Notes',
    'business.apply.raise.notes_info': 'À propos des notes Rozine',
    'business.apply.raise.note_unit': 'Une note Rozine vaut {price}.',
    'business.apply.raise.term_label': 'Durée · mois de remboursement',
    'business.apply.raise.term_any': '· de 3 à 6',
    'business.apply.raise.you_receive': 'Vous recevez',
    'business.apply.raise.interest': '+ Intérêts',
    'business.apply.raise.interest_basis':
        '({rate} % forfaitaire · {months} mois)',
    'business.apply.raise.you_repay': 'Vous remboursez',
    'business.apply.raise.per_month': '{amount} / mois',
    'business.apply.raise.first_payment':
        "À partir d'un mois après le financement",
    'business.apply.raise.reserve': 'Réserve de protection des investisseurs',
    'business.apply.raise.reserve_detail':
        'Prélevée sur le coût · rien de plus à payer',
    'business.apply.raise.use_of_funds': 'Utilisation des fonds',
    'business.apply.raise.use.inventory': 'Stocks',
    'business.apply.raise.use.expansion': 'Expansion',
    'business.apply.raise.use.equipment': 'Équipement',
    'business.apply.raise.use.hiring': 'Recrutement',
    'business.apply.raise.use.working_capital': 'Fonds de roulement',
    'business.apply.raise.use.other': 'Autre',
    'business.apply.raise.show_investors': '2 · Montrez qui vous êtes',
    'business.apply.raise.logo': 'Logo',
    'business.apply.raise.photos_help':
        "Ajoutez votre logo et jusqu'à 7 photos — produits, installations, équipe et activités.",
    'business.apply.raise.slot.products': 'Produits',
    'business.apply.raise.slot.facilities': 'Installations',
    'business.apply.raise.slot.team': 'Équipe',
    'business.apply.raise.slot.operations': 'Activités',
    'business.apply.raise.slot.customers': 'Clients',
    'business.apply.raise.slot.impact': 'Impact',
    'business.apply.raise.slot.brand': 'Marque',
    'business.apply.raise.story': '3 · Votre histoire',
    'business.apply.raise.story_prompt':
        'Pourquoi les investisseurs devraient-ils faire confiance à votre entreprise ?',
    'business.apply.raise.story_placeholder':
        "Sebeya Logistics transporte des marchandises en Afrique de l'Est depuis 8 ans...",
    'business.apply.raise.story_count': '{count} / 100 mots',
    'business.apply.review.title': 'Vérifier et signer',
    'business.apply.review.subtitle':
        'Reconnaissez les risques, acceptez les conditions et signez pour soumettre.',
    'business.apply.review.risk_disclosures': 'Informations sur les risques',
    'business.apply.review.disclosure.accuracy':
        "Je confirme l'exactitude de toutes les informations fournies.",
    'business.apply.review.disclosure.obligations':
        "Je comprends les obligations légales liées à l'émission de cette note.",
    'business.apply.review.disclosure.statements':
        "Je téléverserai à temps mes relevés bancaires et MoMo mensuels pour l'audit du partenaire.",
    'business.apply.review.disclosure.repayment':
        'Je reconnais les obligations de remboursement envers les investisseurs.',
    'business.apply.review.agreements': 'Accords',
    'business.apply.review.agree_terms_prefix': "J'accepte les",
    'business.apply.review.agree_privacy_prefix': "J'ai lu la",
    'business.apply.review.document.terms': 'Conditions générales',
    'business.apply.review.document.privacy': 'Note de confidentialité',
    'business.apply.review.read': 'Lire',
    'business.apply.review.document_intro':
        "Rozine · République du Rwanda · version {version}. Résumé des clauses clés — l'accord complet est disponible dans Profil.",
    'business.apply.review.got_it': 'Compris',
    'business.apply.review.sign_submit': 'Signer et soumettre',
    'business.apply.review.full_name': 'Votre nom complet',
    'business.apply.review.full_name_placeholder': 'Robert Mugisha',
    'business.apply.review.signature': 'Signature numérique',
    'business.apply.review.sign_here': 'Signez ici',
    'business.apply.review.due_on_approval': "Dû à l'approbation",
    'business.apply.review.application_fee': 'Frais de demande',
    'business.apply.review.application_fee_when':
        "Unique, facturés à l'approbation.",
    'business.apply.review.fee_note':
        'Aucuns frais sur le montant levé, et rien au-delà du taux annoncé. Facturés une fois la note approuvée, avant sa mise en ligne.',
    'business.apply.review.binding':
        "Votre signature engage légalement l'entreprise aux obligations divulguées. L'identifiant de la note est généré à la soumission.",
    'business.apply.submitted.title': 'Votre note a été soumise',
    'business.apply.submitted.body':
        "Votre note est en cours d'examen. Vous serez notifié à chaque étape.",
    'business.apply.submitted.note_id': 'ID de la note · {id}',
    'business.apply.submitted.funded_title': 'Une fois entièrement financée',
    'business.apply.submitted.funded_body':
        "Les fonds arrivent sur le compte bancaire lié de l'entreprise dans les 24 heures suivant la clôture. Ce jour devient votre date d'échéance mensuelle, chaque mois jusqu'au remboursement total.",
    'business.apply.submitted.timeline': 'Avancement de la demande',
    'business.apply.submitted.stage.submitted': 'Soumise',
    'business.apply.submitted.stage.under_review': 'En examen',
    'business.apply.submitted.stage.approved': 'Approuvée',
    'business.apply.submitted.stage.published': 'Publiée',
    'business.apply.submitted.back_home': "Retour à l'accueil",

    'app.sheet.close': 'Fermer',
    'business.publish.title': 'Publier sur le fil des investisseurs',
    'business.publish.body_fee':
        '{title} a passé la vérification. Payez les frais de demande uniques pour la rendre visible aux investisseurs.',
    'business.publish.body_free':
        '{title} a passé la vérification. Aucuns frais de demande à payer — publiez-la pour la rendre visible aux investisseurs.',
    'business.publish.target': 'Objectif de levée',
    'business.publish.fee': 'Frais de demande',
    'business.publish.pay_with': 'Payer avec',
    'business.publish.source.wallet': 'Portefeuille',
    'business.publish.source.mtn': 'MTN MoMo',
    'business.publish.source.airtel': 'Airtel',
    'business.publish.source.card': 'Carte',
    'business.publish.pay_and_publish': 'Payer et publier',
    'business.publish.publish': 'Publier',
    'business.publish.publishing': 'Publication…',
    'business.publish.not_yet': 'Pas encore',

    'business.onboarding.back': 'Retour',
    'business.onboarding.progress': 'Progression de la configuration',
    'business.onboarding.step_label.confirm': 'ÉTAPE 1 SUR 4 · RDB',
    'business.onboarding.step_label.documents': 'ÉTAPE 2 SUR 4',
    'business.onboarding.step_label.bank': 'ÉTAPE 3 SUR 4',
    'business.onboarding.step_label.finish': 'ÉTAPE 4 SUR 4',
    'business.onboarding.cta.confirm': 'Confirmer et continuer',
    'business.onboarding.cta.documents': 'Continuer',
    'business.onboarding.cta.bank': 'Continuer',
    'business.onboarding.cta.finish': 'Terminer la configuration',
    'business.onboarding.error.certificate':
        "Téléversez et vérifiez votre certificat RDB pour continuer — aucune entreprise n'est publiée sans lui",
    'business.onboarding.error.bank':
        'Liez un compte bancaire professionnel vérifié pour continuer',
    'business.onboarding.confirm.title': 'Confirmez votre entreprise',
    'business.onboarding.confirm.subtitle':
        'Issu du Rwanda Development Board. Vérifiez et confirmez que tout est exact.',
    'business.onboarding.confirm.name': 'Raison sociale',
    'business.onboarding.confirm.company_code': "Code de l'entreprise",
    'business.onboarding.confirm.legal_form': 'Forme juridique',
    'business.onboarding.confirm.registered': 'Immatriculée le',
    'business.onboarding.confirm.status': 'Statut',
    'business.onboarding.confirm.status_active': 'Active',
    'business.onboarding.confirm.status_dormant': 'En sommeil',
    'business.onboarding.confirm.status_deregistered': 'Radiée',
    'business.onboarding.confirm.staff': 'Effectif',
    'business.onboarding.confirm.staff_count': '{count} employés',
    'business.onboarding.confirm.address': 'Adresse du siège',
    'business.onboarding.confirm.industry': 'Secteur',
    'business.onboarding.confirm.auto_detected': '✓ Détecté depuis le RDB',
    'business.onboarding.confirm.industry_help':
        "Nous l'avons déduit de votre immatriculation RDB ({category}). Modifiez-le s'il ne convient pas.",
    'business.onboarding.confirm.management': 'Direction',
    'business.onboarding.confirm.shareholders': 'Actionnaires',
    'business.onboarding.documents.title': 'Certificat RDB, logo et photos',
    'business.onboarding.documents.subtitle':
        "Votre certificat de constitution est obligatoire — rien n'est publié sans lui. Montrez ensuite aux investisseurs qui vous êtes.",
    'business.onboarding.documents.certificate':
        'Certificat de constitution RDB',
    'business.onboarding.documents.certificate_required': 'Obligatoire',
    'business.onboarding.documents.certificate_uploaded': 'Téléversé',
    'business.onboarding.documents.certificate_verified': 'Vérifié',
    'business.onboarding.documents.certificate_drop':
        'Déposez votre certificat RDB (scan PDF ou photo)',
    'business.onboarding.documents.certificate_number': 'Numéro du certificat',
    'business.onboarding.documents.certificate_placeholder': 'RDB/2019/123456',
    'business.onboarding.documents.verify_certificate':
        'Vérifier le certificat',
    'business.onboarding.documents.verifying': 'Vérification…',
    'business.onboarding.documents.certificate_on_file':
        'Certificat enregistré',
    'business.onboarding.documents.logo': 'Logo',
    'business.onboarding.documents.logo_set': 'Logo ajouté',
    'business.onboarding.documents.logo_label': 'Téléverser votre logo',
    'business.onboarding.documents.logo_help':
        'Un logo soigné inspire tout de suite confiance aux investisseurs.',
    'business.onboarding.documents.photos': 'Photos justificatives',
    'business.onboarding.documents.uploaded': 'Téléversée',
    'business.onboarding.bank.title': 'Compte bancaire professionnel',
    'business.onboarding.bank.subtitle':
        "C'est là que vous recevrez les fonds levés. Il doit s'agir d'un compte professionnel rwandais au nom de votre entreprise, avec au moins {count} signataires.",
    'business.onboarding.bank.bank': 'Banque',
    'business.onboarding.bank.select': 'Choisissez votre banque…',
    'business.onboarding.bank.business_account_prefix': "Il s'agit d'un",
    'business.onboarding.bank.business_account': 'compte professionnel',
    'business.onboarding.bank.business_account_suffix':
        " enregistré, pas d'un compte personnel.",
    'business.onboarding.bank.account_name': 'Intitulé du compte',
    'business.onboarding.bank.use_company_name':
        "Utiliser le nom de l'entreprise",
    'business.onboarding.bank.account_number': 'Numéro de compte',
    'business.onboarding.bank.account_number_placeholder': '00012345678',
    'business.onboarding.bank.signatories': 'Signataires',
    'business.onboarding.bank.signatories_count':
        '{selected} choisis · {required}+ requis',
    'business.onboarding.bank.link': 'Lier le compte professionnel',
    'business.onboarding.bank.linking': 'Liaison…',
    'business.onboarding.bank.linked': '✓ Compte bancaire professionnel lié',
    'business.onboarding.bank.locked':
        "Vous ne pouvez lier qu'un seul compte de versement. Pour le modifier plus tard, contactez l'assistance Rozine — cela protège vos fonds contre tout détournement non autorisé.",
    'business.onboarding.finish.title': 'Tout est prêt',
    'business.onboarding.finish.subtitle':
        'Votre espace est prêt. Vérifiez avant de terminer.',
    'business.onboarding.finish.company': 'Entreprise',
    'business.onboarding.finish.rdb': '✓ RDB',
    'business.onboarding.finish.contact': 'Contact',
    'business.onboarding.finish.payout': 'Compte de versement',
    'business.onboarding.finish.setting_up': 'Préparation de votre espace…',

    'auth.two_factor.recovery_codes_remaining': {
        one: '{count} code de récupération restant',
        other: '{count} codes de récupération restants',
    },
};

export default fr;
