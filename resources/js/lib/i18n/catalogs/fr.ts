/**
 * French catalog.
 *
 * Structurally complete against `en`. Copy is engineering-supplied and still needs a professional
 * review pass before any locale is activated under D-07.
 */
import type { Catalog } from '@/lib/i18n/types';

const fr: Catalog = {
    'suite.sign_out': 'Se déconnecter',
    'site.app_entry.label': 'Applications Rozine',
    'site.app_entry.sign_in': 'Se connecter',
    'site.app_entry.create_account': 'Créer un compte',
    'site.app_entry.open_app': "Ouvrir l'application",
    'suite.preview': 'Aperçu avec des données fictives',
    'suite.mfa_required':
        'Configurez la double authentification pour ouvrir l’application Auditeur.',
    'suite.mfa_setup': 'Configurer la double authentification',
    'suite.network_error':
        'Nous n’avons pas pu confirmer le changement. Vérifiez votre connexion et réessayez.',
    'suite.command_conflict':
        'Cette demande ne peut pas être réutilisée. Actualisez votre accès avant de choisir à nouveau une application.',
    'suite.access_changed':
        'Votre accès ou application active a changé. Actualisez votre accès, puis choisissez une application.',
    'suite.retry': 'Réessayer le changement',
    'suite.refresh_access': 'Actualiser l’accès',
    'suite.admin': 'Ouvrir l’espace personnel',
    'suite.opening': 'Ouverture de votre application…',
    'identity.home.back': 'Choisir une application',
    'identity.denied.title': 'Votre accès doit être vérifié',
    'identity.denied.body':
        'Votre compte ne peut plus ouvrir cette page avec le rôle sélectionné. Choisissez une application pour actualiser votre accès.',
    'identity.denied.expired_offer.title': 'Cette offre est close',
    'identity.denied.expired_offer.body':
        'Le délai pour accepter cette mission est dépassé : elle ne vous est plus ouverte. Choisissez une application pour voir vos missions en cours.',
    'errors.not_found.head_title': 'Page introuvable',
    'errors.not_found.title': 'Nous ne trouvons pas cette page',
    'errors.not_found.body':
        "Le lien est peut-être mal saisi, ou ce vers quoi il pointait n'est plus disponible. Vérifiez le lien ou recommencez depuis Rozine.",
    'errors.not_found.home': "Aller à l'accueil de Rozine",
    'errors.unavailable.head_title': 'Momentanément indisponible',
    'errors.unavailable.title': "Rozine n'a pas pu charger cette page",
    'errors.unavailable.body':
        "Un problème est survenu de notre côté. Ce que vous avez déjà envoyé n'est pas affecté. Patientez un instant, puis réessayez.",
    'errors.unavailable.retry': 'Réessayer',
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
    'suite.app.browse': 'Voir les offres →',
    'suite.app.request_access': "Demander l'accès →",
    'suite.app.investor.preview':
        "Parcourez dès maintenant les offres ouvertes. L'investissement s'ouvre une fois votre identité vérifiée.",
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
    'suite.blocker.action.verify_identity': 'Vérifier votre identité →',
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
    'app.read_failure.server':
        "Rozine n'a pas pu charger cette page pour l'instant. Ce que vous avez déjà envoyé n'est pas affecté.",
    'app.read_failure.network':
        'Impossible de joindre Rozine. Vérifiez votre connexion, puis réessayez.',
    'app.read_failure.retry': 'Réessayer',
    'app.connectivity.offline':
        "Vous êtes hors ligne. Ce qui s'affiche peut ne plus être à jour, et rien ne peut être envoyé avant le retour de la connexion.",
    'business.nav.home': 'Accueil',
    'business.nav.reports': 'Rapports',
    'business.nav.market': 'Marché',
    'business.nav.profile': 'Profil',
    'business.market.title': 'Aperçu du marché',
    'business.market.subtitle':
        'Comment vos notes se comportent sur le marché secondaire.',
    'business.market.tile.demand': 'DEMANDE',
    'business.market.tile.price': 'PRIX SECONDAIRE MOYEN',
    'business.market.tile.volume': 'VOLUME (7 J)',
    'business.market.tile.liquidity': 'SCORE DE LIQUIDITÉ',
    'business.market.chart': 'Prix secondaire',
    'business.market.range': 'Période du prix',
    'business.market.range_7d': '7 J',
    'business.market.range_14d': '14 J',
    'business.market.range_30d': '30 J',
    'business.market.chart_empty':
        'Aucune transaction secondaire pour le moment.',
    'business.market.high': 'PLUS HAUT',
    'business.market.low': 'PLUS BAS',
    'business.market.notes': 'Vos notes sur le marché',
    'business.market.notes_empty_title':
        "Aucune de vos notes ne s'échange encore",
    'business.market.notes_empty_body':
        "Les notes revendues par les investisseurs avant l'échéance apparaîtront ici avec leur prix.",
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
    'business.today.approved.sub_free':
        'Publiez-la auprès des investisseurs. Aucuns frais de publication.',
    'business.today.approved.cta_free': 'Publier',
    'business.today.cosign.kicker': 'Rapport d’audit prêt',
    'business.today.cosign.sub':
        "Votre expert-comptable l'a scellé. Lisez les constats avant de cosigner.",
    'business.today.cosign.cta': 'Examiner et cosigner',
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
    'business.apply.submit': 'Signer la demande',
    'business.apply.submitting': 'Signature…',
    'business.apply.incomplete': 'Terminez cette étape pour continuer',
    'business.apply.business.title': 'Entreprise et finances',
    'business.apply.business.subtitle':
        'À partir de votre certificat RDB et de vos relevés bancaires et mobile money vérifiés. Vérifiez — si tout est correct, continuez.',
    'business.apply.business.rdb_verified': '✓ Vérifié par le RDB',
    'business.apply.business.statements_verified': '✓ Relevés vérifiés',
    'business.apply.business.active': '● Active',
    'business.apply.business.established': 'Fondée en {year}',
    'business.apply.business.standing': 'Situation financière',
    'business.apply.business.statements_badge': 'Relevés vérifiés',
    'business.apply.business.period': {
        one: '{from} – {through} · {count} mois',
        other: '{from} – {through} · {count} mois',
    },
    'business.apply.business.partial_year': {
        one: '{year} · {count} mois',
        other: '{year} · {count} mois',
    },
    'business.apply.business.revenue': "Chiffre d'affaires",
    'business.apply.business.costs': 'Coûts',
    'business.apply.business.net_profit': 'Trésorerie d’exploitation nette',
    'business.apply.business.existing_debt': 'Dette existante',
    'business.apply.business.crb_verified': '✓ Vérifié par le CRB',
    'business.apply.business.year_by_year': 'Année par année',
    'business.apply.business.capacity': 'Capacité approuvée',
    'business.apply.business.capacity_basis':
        '· trésorerie × couverture des stocks',
    'business.apply.business.capacity_pending': 'Audit en attente',
    'business.apply.business.capacity_body':
        'La plus grande levée que la trésorerie de votre entreprise peut supporter selon la couverture de stocks vérifiée. Chaque offre est calibrée dans cette limite.',
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
    'business.apply.raise.rate_term_detail': '+{ratio} de prime de durée',
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
    'business.apply.review.agreements': 'Accords',
    'business.apply.review.agree_terms_prefix': "J'accepte les",
    'business.apply.review.agree_privacy_prefix': "J'ai lu la",
    'business.apply.review.document.terms': 'Conditions générales',
    'business.apply.review.document.privacy': 'Note de confidentialité',
    'business.apply.review.read': 'Lire',
    'business.apply.review.document_intro':
        'Rozine · République du Rwanda · version {version}. Un résumé des clauses clés, puis le texte intégral que vous acceptez.',
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
        'Aucuns frais sur le montant levé. Facturés une fois la note approuvée, avant sa mise en ligne.',
    'business.apply.review.binding':
        "Votre signature engage légalement l'entreprise aux obligations divulguées. L'identifiant de la demande est attribué à la soumission.",
    'business.apply.submitted.title': 'Votre demande a été soumise',
    'business.apply.submitted.body':
        "Votre demande est en cours d'examen. Vous serez notifié à chaque étape.",
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

    'business.note.close': "Retour à l'accueil",
    'business.note.back': 'Retour',
    'business.note.tile.outstanding': 'Restant dû',
    'business.note.tile.payments_made': 'Paiements effectués',
    'business.note.tile.investors': 'Investisseurs',
    'business.note.tile.health': 'État des remboursements',
    'business.note.tile.raised': 'Levé',
    'business.note.tile.funded': 'Financé',
    'business.note.tile.closes_in': 'Clôture dans',
    'business.note.payments_made': '{made} / {total}',
    'business.note.health.on_time': 'À jour',
    'business.note.health.late': 'En retard',
    'business.note.pct': '{pct} %',
    'business.note.day': '{count} jour',
    'business.note.days': '{count} jours',
    'business.note.tracker.repayment': 'Suivi des remboursements',
    'business.note.tracker.funding': 'Suivi du financement',
    'business.note.tracker.repaid_pct': '{pct} % remboursé',
    'business.note.tracker.funded_pct': '{pct} % financé',
    'business.note.tracker.repaid': 'Remboursé',
    'business.note.tracker.left_to_pay': 'Reste à payer',
    'business.note.tracker.over_month': 'sur {count} mois',
    'business.note.tracker.over_months': 'sur {count} mois',
    'business.note.tracker.fully_repaid': 'Entièrement remboursé',
    'business.note.tracker.raised': 'Levé',
    'business.note.tracker.left_to_raise': 'Reste à lever',
    'business.note.tracker.closes': 'clôture le {date}',
    'business.note.upcoming': 'Prochain paiement',
    'business.note.due_in_day': 'Échéance le {date} · dans {count} jour',
    'business.note.due_in_days': 'Échéance le {date} · dans {count} jours',
    'business.note.pay': 'Payer',
    'business.note.funded_notice':
        "Entièrement financé le {date}. Rozine prépare votre décaissement — les remboursements mensuels commencent 30 jours après l'arrivée des fonds sur votre compte.",
    'business.note.expired_notice':
        "Cette levée s'est clôturée le {date} sans atteindre {target}. Chaque investisseur a été intégralement remboursé, sans frais.",
    'business.note.photos': 'Photos',
    'business.note.reported_by_business': "Fournies par l'entreprise",
    'business.note.close_photo': 'Fermer la photo',
    'business.note.previous_photo': 'Photo précédente',
    'business.note.next_photo': 'Photo suivante',
    'business.note.trend.title': 'Tendance des performances',
    'business.note.trend.caption': 'Revenu mensuel · état des remboursements',
    'business.note.trend.range': 'Période',
    'business.note.trend.six_months': '6M',
    'business.note.trend.twelve_months': '12M',
    'business.note.trend.hint': 'Touchez une barre pour voir ce mois',
    'business.note.trend.month_on_time':
        "{month} · revenu de {revenue} · paiement à l'heure",
    'business.note.trend.month_late':
        '{month} · revenu de {revenue} · paiement en retard',
    'business.note.trend.high': 'Plus haut',
    'business.note.trend.low': 'Plus bas',
    'business.note.investors.title': 'Investisseurs récents',
    'business.note.investors.view_all': 'Voir les {count} ›',
    'business.note.investors.kind.individual': 'Particulier',
    'business.note.investors.kind.institution': 'Institution',
    'business.note.investors.kind.sacco': 'SACCO',

    'common.ordinal.zero': '{n}',
    'common.ordinal.one': '{n}er',
    'common.ordinal.two': '{n}',
    'common.ordinal.few': '{n}',
    'common.ordinal.many': '{n}',
    'common.ordinal.other': '{n}',
    'business.reports.title': 'Rapports',
    'business.reports.subtitle':
        'Vérifiés chaque mois par votre auditeur sur site.',
    'business.reports.guide.title': 'Comment fonctionnent les audits mensuels',
    'business.reports.guide.opens.title': 'Rassemblez vos documents',
    'business.reports.guide.opens.body':
        "Entre le 20 et la fin du mois, rassemblez tous vos documents financiers papier et numériques pour l'audit sur place prévu avec votre expert-comptable.",
    'business.reports.guide.visit.title': 'Préparez la visite sur site',
    'business.reports.guide.visit.body_before':
        "L'expert-comptable qui vous est attribué se rend dans vos locaux pour examiner vos documents et rapprocher vos flux de trésorerie, puis scelle le rapport avant le",
    'business.reports.guide.visit.body_after': '.',
    'business.reports.guide.cosign.title': 'Cosignez ou contestez',
    'business.reports.guide.cosign.body':
        "Une fois l'audit scellé, vous ajoutez un résumé et le cosignez avant le {day}, ou vous le contestez avec une contre-preuve. Comme vous ne touchez jamais aux chiffres, le rapport est indépendant — c'est exactement ce que paient les investisseurs.",
    'business.reports.guide.visit.body_undated':
        "L'expert-comptable qui vous est attribué se rend dans vos locaux pour examiner vos documents et rapprocher vos flux de trésorerie, puis scelle le rapport.",
    'business.reports.guide.cosign.body_undated':
        "Une fois l'audit scellé, vous l'examinez et le cosignez pendant la période de revue, ou vous le contestez avec une contre-preuve. Comme vous ne touchez jamais aux chiffres, le rapport est indépendant — c'est exactement ce que paient les investisseurs.",
    'business.reports.tabs': 'Statut des rapports',
    'business.reports.tab.verified': 'Publiés',
    'business.reports.tab.in_audit': 'En audit',
    'business.reports.tab.archived': 'Archivés',
    'business.reports.status.verified': 'Vérifié',
    'business.reports.status.in_audit': 'En audit',
    'business.reports.status.archived': 'Archivé',
    'business.reports.health.healthy': 'Sain',
    'business.reports.health.watch': 'À surveiller',
    'business.reports.health.at_risk': 'À risque',
    'business.reports.row.filed':
        'Entrées {inflow} · {health} · audité par {auditor}',
    'business.reports.row.in_audit': 'Chez {auditor} · scellé avant le {date}',
    'business.reports.row.annual': "Synthèse de l'année",
    'business.reports.annual': 'Annuel {year}',
    'business.reports.empty': "Aucun rapport pour l'instant.",
    'business.reports.percent': '{value} %',
    'business.reports.count_of': '{count} sur {of}',
    'business.reports.sheet.live': 'Visible par vos investisseurs',
    'business.reports.sheet.archived': 'Dépôt archivé',
    'business.reports.sheet.published':
        'Publié le {date} · vu par {count} investisseurs',
    'business.reports.sheet.filed': 'Déposé le {date} · archivé',
    'business.reports.sheet.inflow': 'Entrées',
    'business.reports.sheet.outflow': 'Sorties',
    'business.reports.sheet.health': 'Santé',
    'business.reports.sheet.recap': 'Ce que lisent les investisseurs',
    'business.reports.sheet.figures': 'Chiffres déposés',
    'business.reports.sheet.audited_by': 'Audité par {name}',
    'business.reports.sheet.disclosure_live':
        "C'est tout ce que vos investisseurs voient pour ce mois. Rien d'autre sur votre entreprise n'est publié.",
    'business.reports.sheet.disclosure_archived':
        'Les dépôts archivés restent à votre dossier mais ne sont plus affichés sur votre page investisseurs.',
    'business.reports.figure.cash_inflow': 'Entrées de trésorerie',
    'business.reports.figure.cash_outflow': 'Sorties de trésorerie',
    'business.reports.figure.net_position': 'Position nette',
    'business.reports.figure.net_margin': 'Marge nette',
    'business.reports.figure.days_cash_on_hand': 'Jours de trésorerie',
    'business.reports.figure.quarters_above_floor':
        'Trimestres au-dessus du plancher de {floor} %',

    'business.profile.title': 'Profil',
    'business.profile.verified': '✓ Vérifiée',
    'business.profile.score': 'Note {score}',
    'business.profile.menu': 'Menu du profil',
    'business.profile.section.company': "Informations sur l'entreprise",
    'business.profile.section.linked': 'Comptes liés',
    'business.profile.section.terms': 'Conditions générales',
    'business.profile.section.privacy': 'Note de confidentialité',
    'business.profile.section.security': 'Centre de sécurité',
    'business.profile.section.permissions': 'Autorisations et rôles',
    'business.profile.section.support': "Centre d'assistance",
    'business.profile.security.two_factor': 'Authentification à deux facteurs',
    'business.profile.security.two_factor_on':
        "Activée · code d'une application d'authentification à la connexion",
    'business.profile.security.two_factor_off':
        "Désactivée · ajoutez un code d'application d'authentification à la connexion",
    'business.profile.security.password': 'Changer le mot de passe',
    'business.profile.permissions.none':
        'Aucune autorisation sur cette entreprise',
    'business.profile.permissions.business.view': "Consulter l'entreprise",
    'business.profile.permissions.application.create': 'Lancer des levées',
    'business.profile.permissions.application.save': 'Modifier les demandes',
    'business.profile.permissions.application.evaluate':
        "Vérifier l'éligibilité",
    'business.profile.permissions.application.sign': 'Signer les demandes',
    'business.profile.permissions.report.cosign': 'Cosigner les rapports',
    'business.profile.permissions.business.wallet.deposit':
        'Alimenter le portefeuille',
    'business.profile.permissions.repayment.pay': 'Payer les remboursements',
    'business.profile.pending.linked_title': 'Aucun compte de versement',
    'business.profile.pending.linked_body':
        'Les comptes de versement liés à cette entreprise apparaîtront ici.',
    'business.profile.pending.support_title': "Bientôt dans l'application",
    'business.profile.pending.support_body':
        'Les réponses aux questions les plus fréquentes des entreprises apparaîtront ici.',
    'business.profile.pending.terms_title': "Bientôt dans l'application",
    'business.profile.pending.terms_body':
        'Les Conditions générales pourront être consultées ici dès leur publication.',
    'business.profile.pending.privacy_title': "Bientôt dans l'application",
    'business.profile.pending.privacy_body':
        'La Note de confidentialité pourra être consultée ici dès sa publication.',
    'business.profile.sign_out': 'Se déconnecter',
    'business.profile.back': 'Retour au profil',
    'business.profile.company.name': "Nom de l'entreprise",
    'business.profile.company.email': 'E-mail professionnel',
    'business.profile.company.phone': 'Téléphone professionnel',
    'business.profile.company.phone_placeholder': '0788 123 456',
    'business.profile.company.address': 'Adresse du siège',
    'business.profile.company.province': 'Province',
    'business.profile.company.district': 'District',
    'business.profile.company.sector': 'Secteur',
    'business.profile.company.cell': 'Cellule',
    'business.profile.company.street': 'Rue',
    'business.profile.company.choose': 'Choisir…',
    'business.profile.company.sector_placeholder': 'Nom du secteur',
    'business.profile.company.cell_placeholder': 'Nom de la cellule',
    'business.profile.company.save': 'Enregistrer',
    'business.profile.company.saving': 'Enregistrement…',
    'business.profile.company.saved':
        "Informations de l'entreprise enregistrées",
    'business.profile.records.certificate': 'Certificat RDB',
    'business.profile.records.expired_notice':
        'Votre certificat RDB a expiré. Téléversez-en un à jour pour continuer à lever des fonds.',
    'business.profile.records.number': 'Numéro du certificat',
    'business.profile.records.status': 'Statut',
    'business.profile.records.status_verified': 'Vérifié',
    'business.profile.records.status_expired': 'Expiré',
    'business.profile.records.expires': 'Expire le',
    'business.profile.records.no_expiry': 'Sans expiration',
    'business.profile.records.signatories': 'Signataires',
    'business.profile.records.mandate':
        '{count} au mandat · {required}+ requis',
    'business.profile.registration.title': 'Au dossier',
    'business.profile.registration.established': 'Année de création',
    'business.profile.registration.note':
        'Issus de votre immatriculation et de votre mandat vérifiés. Ils ne changent que lorsque Rozine vérifie de nouveaux documents.',
    'business.profile.registration.people': 'Personnes au mandat',
    'business.profile.registration.required': 'Signataire requis',
    'business.profile.registration.role.owner': 'Propriétaire',
    'business.profile.registration.role.beneficial_owner':
        'Bénéficiaire effectif',
    'business.profile.registration.role.controller': 'Contrôleur',
    'business.profile.registration.role.director': 'Administrateur',
    'business.profile.registration.role.signatory': 'Signataire',
    'business.profile.registration.role.representative': 'Représentant',
    'business.profile.linked.unlink': 'Dissocier',
    'business.profile.linked.unlink_named': 'Dissocier {name}',
    'business.profile.linked.add': '+ Lier un compte de versement',
    'business.profile.legal.updated':
        'Dernière mise à jour le {date} · Version {version}',
    'business.profile.legal.terms_intro':
        "Veuillez lire attentivement ces Conditions. En enregistrant votre entreprise, en lançant une levée ou en utilisant Rozine de toute autre manière, vous acceptez d'être lié par l'intégralité de ces Conditions. Si vous ne les acceptez pas, vous ne devez pas utiliser la plateforme.",
    'business.profile.legal.privacy_intro':
        "Cette Note de confidentialité explique quelles informations Rozine collecte sur votre entreprise, comment nous les utilisons et quels choix s'offrent à vous. En utilisant Rozine, vous consentez aux pratiques décrites ici.",
    'business.profile.legal.terms_footer':
        'Ces Conditions sont régies par les lois de la République du Rwanda. Pour toute question, contactez',
    'business.profile.legal.terms_contact': 'legal@rozine.rw',
    'business.profile.legal.privacy_footer':
        'Pour exercer un droit sur vos données ou joindre notre délégué à la protection des données, contactez',
    'business.profile.legal.privacy_contact': 'privacy@rozine.rw',
    'business.profile.legal.footer_end': '.',

    'admin.auth.head_title': 'Connexion',
    'admin.auth.title': 'Connexion',
    'admin.auth.subtitle':
        "Accès interne uniquement. Chaque action est consignée dans la piste d'audit.",
    'admin.auth.email_label': 'E-mail professionnel',
    'admin.auth.email_placeholder': 'vous@rozine.com',
    'admin.auth.password_label': 'Mot de passe',
    'admin.auth.password_placeholder': '••••••••',
    'admin.auth.submit': 'Se connecter à la console',
    'admin.auth.submitting': 'Connexion…',
    'admin.auth.back': '← Retour à toutes les applications',
    'admin.nav.label': 'Navigation de la console',
    'admin.nav.open_menu': 'Ouvrir le menu',
    'admin.nav.close_menu': 'Fermer le menu',
    'admin.nav.all_apps': '← Toutes les applications',
    'admin.nav.group.accounts': 'Comptes',
    'admin.nav.group.capital': 'Capital',
    'admin.nav.group.treasury': 'Trésorerie',
    'admin.nav.group.console': 'Console',
    'admin.nav.notes': 'Notes',
    'admin.section.notes.title': 'Notes',
    'admin.section.notes.subtitle': 'Toutes les notes émises sur Rozine',
    'admin.section.notes.search':
        'Rechercher une note par entreprise ou identifiant…',
    'admin.nav.primary_market': 'Marché primaire',
    'admin.section.primary_market.title': 'Suivi du marché primaire',
    'admin.section.primary_market.subtitle': 'Supervision des levées en cours',
    'admin.section.primary_market.search':
        'Rechercher une levée par entreprise ou note…',
    'admin.nav.secondary_market': 'Marché secondaire',
    'admin.section.secondary_market.title': 'Suivi du marché secondaire',
    'admin.section.secondary_market.subtitle': 'Surveillance des négociations',
    'admin.section.secondary_market.search':
        'Rechercher un ordre par note ou investisseur…',
    'admin.nav.risk': 'Centre des risques',
    'admin.section.risk.title': 'Centre des risques',
    'admin.section.risk.subtitle': 'Gestion des risques de toute la plateforme',
    'admin.section.risk.search': 'Rechercher un cas par entreprise ou note…',
    'admin.nav.compliance': 'Conformité',
    'admin.section.compliance.title': 'Tableau de bord de conformité',
    'admin.section.compliance.subtitle':
        'Supervision KYC, AML et réglementaire',
    'admin.section.compliance.search':
        'Rechercher un dossier par nom ou référence…',
    'admin.nav.payments': 'Paiements',
    'admin.section.payments.title': 'Centre des paiements',
    'admin.section.payments.subtitle':
        "Les mouvements d'argent sur la plateforme",
    'admin.section.payments.search':
        'Rechercher un paiement par partie ou référence…',
    'admin.nav.ratings': 'Notations',
    'admin.section.ratings.title': 'Notations',
    'admin.section.ratings.subtitle':
        'Les bandes en vigueur, ce qui les a fait évoluer et leurs pondérations',
    'admin.section.ratings.search': 'Rechercher une notation par entreprise…',
    'admin.nav.deferrals': "Reports d'échéance",
    'admin.section.deferrals.title': "Reports d'échéance",
    'admin.section.deferrals.subtitle':
        "Demandes de report d'une date de remboursement",
    'admin.section.deferrals.search':
        'Rechercher un report par entreprise ou note…',
    'admin.nav.plus': 'Rozine Plus',
    'admin.section.plus.title': 'Rozine Plus',
    'admin.section.plus.subtitle': 'Les abonnements et leurs ordres permanents',
    'admin.section.plus.search': 'Rechercher un membre par nom…',
    'admin.nav.finance': 'Finances',
    'admin.section.finance.title': 'Tableau de bord financier',
    'admin.section.finance.subtitle':
        'Chaque source de revenus, le registre RAMP et le grand livre',
    'admin.section.finance.search': 'Rechercher une écriture…',
    'admin.nav.messaging': 'Messagerie',
    'admin.section.messaging.title': 'Messagerie et automatisations',
    'admin.section.messaging.subtitle':
        'Les règles qui décident quand, pourquoi et comment chaque notification part',
    'admin.section.messaging.search': 'Rechercher un message…',
    'admin.nav.academies': 'Académies',
    'admin.section.academies.title': 'Académies',
    'admin.section.academies.subtitle':
        'Leçons et points des académies Investisseurs, Entreprises et Auditeurs',
    'admin.section.academies.search': 'Rechercher une leçon…',
    'admin.nav.app_control': 'Contrôle des applications',
    'admin.section.app_control.title': 'Centre de contrôle des applications',
    'admin.section.app_control.subtitle':
        'Tout ce qui compose les applications Investisseur, Entreprise et Auditeur',
    'admin.section.app_control.search': 'Rechercher un réglage…',
    'admin.nav.policies': 'Politiques',
    'admin.section.policies.title': 'Politiques et règles',
    'admin.section.policies.subtitle':
        "Toutes les règles qui régissent l'écosystème",
    'admin.section.policies.search': 'Rechercher une politique…',
    'admin.nav.system_health': 'État du système',
    'admin.section.system_health.title': 'État du système',
    'admin.section.system_health.subtitle':
        "Suivi des signaux de l'écosystème, avec recommandations",
    'admin.section.system_health.search': 'Rechercher un service…',
    'admin.nav.engines': 'Moteurs',
    'admin.section.engines.title': 'Moteurs',
    'admin.section.engines.subtitle':
        "Comment la plateforme décide : dimensionnement, refinancement, file d'attente, planchers partenaires, répartition CPA et courbes de pertes",
    'admin.section.engines.search': 'Rechercher un paramètre…',
    'admin.design.tabs': 'Vues : {section}',
    'admin.design.col.note': 'Note',
    'admin.design.col.issuer': 'Émetteur',
    'admin.design.col.state': 'État',
    'admin.design.col.yield': 'Rendement',
    'admin.design.col.term': 'Durée',
    'admin.design.col.funded': 'Financé',
    'admin.design.col.raised': 'Levé',
    'admin.design.col.raise': 'Levée',
    'admin.design.col.progress': 'Progression',
    'admin.design.col.closes': 'Clôture',
    'admin.design.col.orders': 'Ordres',
    'admin.design.col.avg_ask': 'Prix moyen demandé',
    'admin.design.col.spread': 'Écart',
    'admin.design.col.volume_30d': 'Vol. 30 j',
    'admin.design.col.trades': 'Transactions',
    'admin.design.col.business': 'Entreprise',
    'admin.design.col.outstanding': 'Encours',
    'admin.design.col.days_late': 'Jours de retard',
    'admin.design.col.risk': 'Risque',
    'admin.design.col.default_probability': 'Prob. de défaut',
    'admin.design.col.applicant': 'Demandeur',
    'admin.design.col.document': 'Document',
    'admin.design.col.checks': 'Contrôles automatiques',
    'admin.design.col.decision': 'Décision',
    'admin.design.col.reference': 'Réf.',
    'admin.design.col.user': 'Utilisateur',
    'admin.design.col.type': 'Type',
    'admin.design.col.amount': 'Montant',
    'admin.design.col.compliance': 'Conformité',
    'admin.design.col.sector': 'Secteur',
    'admin.design.col.band': 'Bande',
    'admin.design.col.change': 'Variation',
    'admin.design.col.reason': 'Motif',
    'admin.design.col.why': 'Pourquoi',
    'admin.design.col.ask': 'Demande',
    'admin.design.col.deferred': 'Reporté',
    'admin.design.col.opened': 'Ouvert le',
    'admin.design.col.plan': 'Plan',
    'admin.design.col.due': 'Échéance',
    'admin.design.col.decided': 'Décidé le',
    'admin.design.col.investor': 'Investisseur',
    'admin.design.col.tier': 'Niveau',
    'admin.design.col.monthly': 'Mensuel',
    'admin.design.col.min_interest': 'Intérêt min.',
    'admin.design.col.bands': 'Bandes',
    'admin.design.col.filled_mtd': 'Exécuté ce mois',
    'admin.design.col.stream': 'Source',
    'admin.design.col.paid_by': 'Payé par',
    'admin.design.col.collected': 'Perçu',
    'admin.design.col.position': 'Position',
    'admin.design.col.updated': 'Mis à jour',
    'admin.design.col.entry': 'Écriture',
    'admin.design.col.account': 'Compte',
    'admin.design.col.debit': 'Débit',
    'admin.design.col.credit': 'Crédit',
    'admin.design.col.posted': 'Comptabilisé',
    'admin.design.col.rule': 'Règle',
    'admin.design.col.fires_when': 'Déclenchée quand',
    'admin.design.col.timing': 'Délai et canaux',
    'admin.design.col.template': 'Modèle',
    'admin.design.col.channel': 'Canal',
    'admin.design.col.campaign': 'Campagne',
    'admin.design.col.audience': 'Audience',
    'admin.design.col.sent': 'Envoyé',
    'admin.design.col.message': 'Message',
    'admin.design.col.recipient': 'Destinataire',
    'admin.design.col.lesson': 'Leçon',
    'admin.design.col.time': 'Durée',
    'admin.design.col.done': 'Terminées',
    'admin.design.col.score': 'Score',
    'admin.design.col.points': 'Points',
    'admin.design.col.app': 'Application',
    'admin.design.col.status': 'Statut',
    'admin.design.col.assigned_cpa': 'CPA assigné',
    'admin.design.col.variance': 'V_total / J',
    'admin.design.col.signed': 'Signature',
    'admin.design.col.period': 'Période',
    'admin.design.col.audit_partner': "Partenaire d'audit",
    'admin.design.col.features': 'Fonctionnalités',
    'admin.design.col.entity': 'Élément',
    'admin.design.col.input': 'Paramètre',
    'admin.design.col.value': 'Valeur',
    'admin.design.col.source': 'Source',
    'admin.design.col.signal': 'Signal',
    'admin.design.col.reading': 'Mesure',
    'admin.design.col.recommendation': 'Recommandation',
    'admin.design.notes.kpi.total': 'Total des notes',
    'admin.design.notes.kpi.live': 'Levées en cours',
    'admin.design.notes.kpi.repaying': 'En remboursement',
    'admin.design.notes.kpi.raised': 'Capital levé',
    'admin.design.notes.tab.all': 'Toutes',
    'admin.design.notes.tab.live': 'En cours',
    'admin.design.notes.tab.repaying': 'En remboursement',
    'admin.design.notes.tab.matured': 'Échues',
    'admin.design.notes.tab.failed': 'Échouées',
    'admin.design.notes.notes.title': 'Notes',
    'admin.design.notes.notes.empty': 'Aucune note à afficher pour le moment.',
    'admin.design.primary_market.kpi.active': 'Levées actives',
    'admin.design.primary_market.kpi.active.sub': 'En cours',
    'admin.design.primary_market.kpi.in_flight': 'Capital en cours',
    'admin.design.primary_market.kpi.in_flight.sub': 'Levé à ce jour',
    'admin.design.primary_market.kpi.fill': 'Remplissage moyen',
    'admin.design.primary_market.kpi.fill.sub': "De l'objectif",
    'admin.design.primary_market.kpi.completion': "Taux d'achèvement",
    'admin.design.primary_market.kpi.completion.sub': "Atteignent l'objectif",
    'admin.design.primary_market.trend.title': 'Évolution du financement',
    'admin.design.primary_market.trend.empty':
        'Aucun financement à représenter pour le moment.',
    'admin.design.primary_market.raises.title': 'Levées en cours',
    'admin.design.primary_market.raises.empty':
        'Aucune levée en cours à afficher pour le moment.',
    'admin.design.secondary_market.kpi.volume': 'Volume du jour',
    'admin.design.secondary_market.kpi.orders': 'Ordres ouverts',
    'admin.design.secondary_market.kpi.orders.sub': 'Dans le carnet',
    'admin.design.secondary_market.kpi.trades': 'Total des transactions',
    'admin.design.secondary_market.kpi.trades.sub': 'Depuis le début',
    'admin.design.secondary_market.kpi.tradable': 'Notes négociables',
    'admin.design.secondary_market.kpi.tradable.sub':
        'En remboursement · financées',
    'admin.design.secondary_market.activity.title': 'Activité de négociation',
    'admin.design.secondary_market.activity.empty':
        'Aucune transaction à représenter pour le moment.',
    'admin.design.secondary_market.market.title': 'Marché par note',
    'admin.design.secondary_market.market.sub':
        'Ordres de vente ouverts, prix moyen demandé par rapport au pair et volume sur 30 jours.',
    'admin.design.secondary_market.market.empty':
        'Aucun ordre ouvert à afficher pour le moment.',
    'admin.design.risk.kpi.exposure': 'Exposition au risque',
    'admin.design.risk.kpi.late': 'Retards de paiement',
    'admin.design.risk.kpi.distressed': 'Notes en difficulté',
    'admin.design.risk.kpi.default_probability': 'Prob. moyenne de défaut',
    'admin.design.risk.distressed.title':
        'Notes en difficulté · alerte précoce',
    'admin.design.risk.distressed.empty':
        'Aucune note en difficulté à afficher pour le moment.',
    'admin.design.reports.kpi.published': 'Audités et publiés',
    'admin.design.reports.kpi.awaiting': "En attente d'audit",
    'admin.design.reports.kpi.breached': 'SLA dépassé',
    'admin.design.reports.kpi.on_time': "Taux d'audit à temps",
    'admin.design.reports.field.title':
        "Opérations d'audit flash sur le terrain",
    'admin.design.reports.field.sub':
        "Chaque visite de terrain avec son CPA, l'écart, l'heure de validation et les constats signés.",
    'admin.design.reports.field.empty':
        'Aucune mission ISRS 4400 enregistrée pour le moment.',
    'admin.design.reports.monthly.title':
        'Suivi de conformité des audits mensuels',
    'admin.design.reports.monthly.sub':
        'Relevés mensuels, vérifiés sur place avant le 7.',
    'admin.design.reports.monthly.empty': 'Rien dans cette vue.',
    'admin.design.compliance.kpi.kyc_completion': 'Achèvement KYC',
    'admin.design.compliance.kpi.aml_alerts': 'Alertes AML',
    'admin.design.compliance.kpi.sanctions': 'Correspondances sanctions',
    'admin.design.compliance.kpi.pending': 'Revues en attente',
    'admin.design.compliance.queue.title': 'File de revue KYC',
    'admin.design.compliance.queue.empty':
        'Aucune revue KYC à afficher pour le moment.',
    'admin.design.payments.kpi.deposits': 'Dépôts',
    'admin.design.payments.kpi.withdrawals': 'Retraits',
    'admin.design.payments.kpi.disbursed': 'Décaissé',
    'admin.design.payments.kpi.disbursed.sub': 'Aux entreprises',
    'admin.design.payments.kpi.awaiting': "En attente d'approbation",
    'admin.design.payments.kpi.awaiting.sub': 'Dans la file',
    'admin.design.payments.movement.title': "Mouvements d'argent",
    'admin.design.payments.movement.empty':
        "Aucun mouvement d'argent à représenter pour le moment.",
    'admin.design.payments.approvals.title': "File d'approbation",
    'admin.design.payments.approvals.empty':
        "Rien en attente d'approbation à afficher pour le moment.",
    'admin.design.ratings.kpi.rated': 'Entreprises notées',
    'admin.design.ratings.kpi.changed': 'Bande modifiée',
    'admin.design.ratings.kpi.trending_down': 'En baisse',
    'admin.design.ratings.kpi.pinned': 'Fixées par le personnel',
    'admin.design.ratings.tab.changed': 'Bande modifiée',
    'admin.design.ratings.tab.trending_down': 'En baisse',
    'admin.design.ratings.tab.all': 'Toutes les entreprises',
    'admin.design.ratings.book.title': 'Le registre des notations',
    'admin.design.ratings.book.empty':
        'Aucune notation à afficher pour le moment.',
    'admin.design.deferrals.kpi.this_month': 'Reports ce mois-ci',
    'admin.design.deferrals.kpi.live': 'En cours',
    'admin.design.deferrals.kpi.deferred': 'Reporté',
    'admin.design.deferrals.kpi.extra_paid':
        'Supplément versé aux investisseurs',
    'admin.design.deferrals.awaiting.title': 'En attente de votre décision',
    'admin.design.deferrals.awaiting.empty':
        'Aucun plan en attente de décision à afficher pour le moment.',
    'admin.design.deferrals.live_plans.title': 'Plans en cours',
    'admin.design.deferrals.live_plans.sub': 'Approuvés et en cours.',
    'admin.design.deferrals.live_plans.empty':
        'Aucun plan en cours à afficher pour le moment.',
    'admin.design.deferrals.decided.title': 'Décidés',
    'admin.design.deferrals.decided.empty':
        'Aucune décision à afficher pour le moment.',
    'admin.design.plus.tab.tiers': 'Niveaux Plus',
    'admin.design.plus.tab.institutions': 'Institutions',
    'admin.design.plus.tab.all': 'Tous les investisseurs',
    'admin.design.plus.subscribers.title': 'Abonnés Rozine Plus',
    'admin.design.plus.subscribers.empty':
        'Aucun abonné à afficher pour le moment.',
    'admin.design.plus.orders.title': 'Ordres permanents',
    'admin.design.plus.orders.sub': 'Achats automatiques des comptes Plus.',
    'admin.design.plus.orders.empty':
        'Aucun ordre permanent à afficher pour le moment.',
    'admin.design.finance.kpi.revenue': 'Tous les revenus',
    'admin.design.finance.kpi.primary': 'Marché primaire',
    'admin.design.finance.kpi.investor_fees': 'Frais investisseurs',
    'admin.design.finance.kpi.secondary': 'Marché secondaire',
    'admin.design.finance.tab.streams': 'Sources de revenus',
    'admin.design.finance.tab.ramp': 'RAMP',
    'admin.design.finance.tab.ledger': 'Grand livre et versements',
    'admin.design.finance.streams.title': 'Sources de revenus',
    'admin.design.finance.streams.empty':
        'Aucun revenu à afficher pour le moment.',
    'admin.design.finance.ramp.title': 'RAMP',
    'admin.design.finance.ramp.empty':
        'Aucune position RAMP à afficher pour le moment.',
    'admin.design.finance.ledger.title': 'Grand livre et versements',
    'admin.design.finance.ledger.empty':
        'Aucune écriture à afficher pour le moment.',
    'admin.design.messaging.tab.automations': 'Automatisations',
    'admin.design.messaging.tab.templates': 'Modèles',
    'admin.design.messaging.tab.campaigns': 'Campagnes',
    'admin.design.messaging.tab.history': 'Historique',
    'admin.design.messaging.automations.title': 'Automatisations',
    'admin.design.messaging.automations.empty':
        'Aucune automatisation à afficher pour le moment.',
    'admin.design.messaging.templates.title': 'Modèles',
    'admin.design.messaging.templates.empty':
        'Aucun modèle à afficher pour le moment.',
    'admin.design.messaging.campaigns.title': 'Campagnes',
    'admin.design.messaging.campaigns.empty':
        'Aucune campagne à afficher pour le moment.',
    'admin.design.messaging.history.title': 'Historique',
    'admin.design.messaging.history.empty':
        'Aucun message à afficher pour le moment.',
    'admin.design.academies.kpi.completed': 'Leçons terminées',
    'admin.design.academies.kpi.live': 'Leçons en ligne',
    'admin.design.academies.kpi.quiz': 'Score moyen aux quiz',
    'admin.design.academies.kpi.points': 'Passif en points',
    'admin.design.academies.tab.investor': 'Académie Investisseurs',
    'admin.design.academies.tab.business': 'Académie Entreprises',
    'admin.design.academies.tab.auditor': 'Académie Auditeurs',
    'admin.design.academies.tab.all': 'Toutes',
    'admin.design.academies.lessons.title': 'Leçons',
    'admin.design.academies.lessons.empty':
        'Aucune leçon à afficher pour le moment.',
    'admin.design.app_control.apps.title': 'Applications',
    'admin.design.app_control.apps.sub':
        'Les applications Investisseur, Entreprise et Auditeur et leurs fonctionnalités.',
    'admin.design.app_control.apps.empty':
        "Aucun réglage d'application à afficher pour le moment.",
    'admin.design.app_control.content.title': 'Contenu',
    'admin.design.app_control.content.sub':
        'Les éléments des applications Investisseur, Entreprise et Auditeur.',
    'admin.design.app_control.content.empty':
        'Aucun contenu à afficher pour le moment.',
    'admin.design.engines.tab.sizing': 'Dimensionnement',
    'admin.design.engines.tab.refinance': 'Refinancement',
    'admin.design.engines.tab.queue': "La file d'attente",
    'admin.design.engines.tab.floors': 'Planchers partenaires',
    'admin.design.engines.tab.cpa': 'Répartition CPA',
    'admin.design.engines.tab.loss': 'Courbes de pertes',
    'admin.design.engines.sizing.title': 'Dimensionnement',
    'admin.design.engines.sizing.empty':
        'Aucun paramètre de dimensionnement à afficher pour le moment.',
    'admin.design.engines.refinance.title': 'Refinancement',
    'admin.design.engines.refinance.empty':
        'Aucun paramètre de refinancement à afficher pour le moment.',
    'admin.design.engines.queue.title': "La file d'attente",
    'admin.design.engines.queue.empty':
        "Aucun paramètre de file d'attente à afficher pour le moment.",
    'admin.design.engines.floors.title': 'Planchers partenaires',
    'admin.design.engines.floors.empty':
        'Aucun plancher partenaire à afficher pour le moment.',
    'admin.design.engines.cpa.title': 'Répartition CPA',
    'admin.design.engines.cpa.empty':
        'Aucun paramètre de répartition CPA à afficher pour le moment.',
    'admin.design.engines.loss.title': 'Courbes de pertes',
    'admin.design.engines.loss.empty':
        'Aucune courbe de pertes à afficher pour le moment.',
    'admin.design.policies.tab.fees': 'Frais',
    'admin.design.policies.tab.underwriting': 'Souscription',
    'admin.design.policies.tab.business': 'Éligibilité entreprises',
    'admin.design.policies.tab.investor': 'Éligibilité investisseurs',
    'admin.design.policies.tab.kyc': 'Exigences KYC',
    'admin.design.policies.tab.penalties': 'Conformité et pénalités',
    'admin.design.policies.tab.limits': 'Limites',
    'admin.design.policies.rules.title': 'Règles',
    'admin.design.policies.rules.empty':
        'Aucune règle à afficher pour le moment.',
    'admin.design.system_health.signals.title': 'Signaux',
    'admin.design.system_health.signals.empty':
        'Aucun signal à afficher pour le moment.',
    'admin.nav.group.oversight': 'Supervision',
    'admin.nav.group.engagement': 'Engagement',
    'admin.pending.title': "Rien pour l'instant",
    'admin.pending.body':
        "Cette partie de la console n'est pas encore connectée. Elle affichera les données dès que la plateforme les enregistrera.",
    'admin.nav.today': 'Tableau de bord',
    'admin.nav.businesses': 'Entreprises',
    'admin.nav.investors': 'Investisseurs',
    'admin.nav.auditors': 'Auditeurs',
    'admin.nav.applications': 'Demandes',
    'admin.nav.disbursements': 'Décaissements',
    'admin.nav.ledger': 'Grand livre',
    'admin.nav.staff': 'Personnel et rôles',
    'admin.nav.events': 'Activité et audit',
    'admin.section.about': 'À propos de {title}',
    'admin.section.today.title': 'Centre des opérations Rozine',
    'admin.section.today.subtitle':
        "La santé de la plateforme en un coup d'œil",
    'admin.section.today.search':
        'Rechercher entreprises, titres, investisseurs…',
    'admin.section.applications.title': 'File des demandes',
    'admin.section.applications.subtitle':
        'Examiner et souscrire les nouvelles demandes de RNP',
    'admin.section.applications.search':
        'Rechercher par intitulé du titre ou identifiant de la demande',
    'admin.section.disbursements.title': 'Décaissements',
    'admin.section.disbursements.subtitle':
        'Levées approuvées en attente de versement. Au-delà du seuil, deux personnes différentes doivent approuver chaque versement.',
    'admin.section.disbursements.search':
        'Rechercher par entreprise, titre ou référence…',
    'admin.section.businesses.title': 'Annuaire des entreprises',
    'admin.section.businesses.subtitle':
        'Toutes les entreprises vérifiées de la plateforme',
    'admin.section.businesses.search': 'Rechercher par nom, secteur, statut…',
    'admin.section.investors.title': 'Annuaire des investisseurs',
    'admin.section.investors.subtitle':
        'Gérer les comptes investisseurs et le KYC',
    'admin.section.investors.search': 'Rechercher par nom ou KYC…',
    'admin.section.auditors.title': "Réseau d'auditeurs",
    'admin.section.auditors.subtitle':
        'Partenaires ICPAR, leurs licences et leur statut',
    'admin.section.auditors.search': 'Rechercher par nom, cabinet ou district…',
    'admin.section.staff.title': 'Personnel et rôles',
    'admin.section.staff.subtitle':
        "Comptes des opérateurs, leurs rôles et l'état du compte",
    'admin.section.staff.search': 'Rechercher par nom ou e-mail…',
    'admin.section.ledger.title': 'Grand livre',
    'admin.section.ledger.subtitle':
        "Chaque mouvement d'argent, le plus récent d'abord. Ouvrez-en un pour voir ses écritures.",
    'admin.section.ledger.search': 'Rechercher par type, partie, référence…',
    'admin.section.events.title': 'Activité et audit',
    'admin.section.events.subtitle':
        'Registre immuable de chaque action — filtrer par auteur, action ou objet',
    'admin.section.events.search': 'Rechercher par utilisateur, action, objet…',
    'admin.search.label': 'Rechercher sur cette page',
    'admin.search.empty_title': 'Aucun résultat sur cette page',
    'admin.search.empty_body':
        'Rien dans {section} ne correspond à « {term} ». Cette recherche ne porte que sur la page actuelle.',
    'admin.account.open': 'Compte, {name}',
    'admin.account.close': 'Fermer le menu du compte',
    'admin.account.sign_out': 'Se déconnecter',
    'admin.role.analyst': 'Analyste',
    'admin.role.approver': 'Approbateur',
    'admin.role.superadmin': 'Super-administrateur',
    'admin.role.access.analyst': 'Accès au tri',
    'admin.role.access.approver': 'Accès approbateur',
    'admin.role.access.superadmin': 'Accès complet',
    'admin.role.compliance': 'Conformité',
    'admin.role.access.compliance': 'Accès conformité',
    'admin.role.treasury': 'Trésorerie',
    'admin.role.access.treasury': 'Accès trésorerie',
    'admin.kyc.intro':
        "Demandes de vérification d'identité de personnes qui souhaitent investir. Ouvrez-en une pour vérifier ses documents, puis approuvez-la ou rejetez-la avec un motif.",
    'admin.kyc.tabs': 'États de vérification',
    'admin.kyc.tab.submitted': "En attente d'examen",
    'admin.kyc.tab.decided': 'Traitées',
    'admin.kyc.table': "Demandes de vérification d'identité",
    'admin.kyc.col.person': 'Personne',
    'admin.kyc.col.document': 'Document',
    'admin.kyc.col.submitted': 'Envoyée',
    'admin.kyc.col.status': 'Statut',
    'admin.kyc.id_type.national_id': "Carte d'identité nationale",
    'admin.kyc.id_type.passport': 'Passeport',
    'admin.kyc.id_type.drivers_license': 'Permis de conduire',
    'admin.kyc.status.draft': 'Rouverte',
    'admin.kyc.status.submitted': 'En attente',
    'admin.kyc.status.approved': 'Approuvée',
    'admin.kyc.status.rejected': 'Rejetée',
    'admin.kyc.empty.submitted': "Aucune demande n'attend d'examen.",
    'admin.kyc.empty.decided': 'Aucune décision pour le moment.',
    'admin.kyc.more': 'Afficher plus',
    'admin.kyc.drawer': "Demande de vérification d'identité",
    'admin.kyc.facts': 'Informations envoyées',
    'admin.kyc.fact.date_of_birth': 'Date de naissance',
    'admin.kyc.fact.id_type': 'Document',
    'admin.kyc.fact.id_number': 'Numéro du document',
    'admin.kyc.fact.submitted_at': 'Envoyée',
    'admin.kyc.documents': 'Documents',
    'admin.kyc.slot.front': "Recto de la pièce d'identité",
    'admin.kyc.slot.back': "Verso de la pièce d'identité",
    'admin.kyc.slot.selfie': 'Selfie',
    'admin.kyc.document.replaced': 'Remplacé',
    'admin.kyc.document.meta': '{name} · {size} Ko',
    'admin.kyc.document.open': 'Télécharger {name}',
    'admin.kyc.document.download': 'Télécharger',
    'admin.kyc.document.current': 'Documents actuels',
    'admin.kyc.document.earlier': 'Envois précédents',
    'admin.kyc.document.view': 'Voir {slot}',
    'admin.kyc.document.show': 'Voir',
    'admin.kyc.document.view_file': 'Voir {name}',
    'admin.kyc.document.pdf': 'PDF',
    'admin.kyc.viewer.position': '{index} sur {total}',
    'admin.kyc.viewer.new_tab': 'Ouvrir dans un nouvel onglet',
    'admin.kyc.viewer.close': 'Fermer la visionneuse',
    'admin.kyc.viewer.previous': 'Document précédent',
    'admin.kyc.viewer.next': 'Document suivant',
    'admin.kyc.decision': 'Décision',
    'admin.kyc.history': 'Historique',
    'admin.kyc.command.verification.save': 'Étape enregistrée',
    'admin.kyc.command.verification.upload': 'Document téléversé',
    'admin.kyc.command.verification.submit': 'Envoyée pour examen',
    'admin.kyc.command.verification.approve': 'Approuvée par la conformité',
    'admin.kyc.command.verification.reject': 'Rejetée par la conformité',
    'admin.kyc.approve': 'Approuver',
    'admin.kyc.reject': 'Rejeter',
    'admin.kyc.stage.approve.title': 'Approuver cette identité',
    'admin.kyc.stage.approve.body':
        'Cela vérifie la personne et ouvre son accès Investisseur. Indiquez ce que vous avez vérifié.',
    'admin.kyc.stage.approve.cta': "Approuver l'identité",
    'admin.kyc.stage.approve.placeholder':
        'p. ex. La photo, le numéro et le selfie correspondent au titulaire du compte',
    'admin.kyc.stage.reject.title': 'Rejeter cette demande',
    'admin.kyc.stage.reject.body':
        'La personne voit votre motif et peut corriger ses informations.',
    'admin.kyc.stage.reject.cta': 'Rejeter la demande',
    'admin.kyc.stage.reject.placeholder':
        'p. ex. La photo de la pièce est floue. Téléversez une photo plus nette.',
    'admin.nav.mail_testers': 'Testeurs e-mail de préproduction',
    'admin.section.mail_testers.title': 'Testeurs e-mail de préproduction',
    'admin.section.mail_testers.subtitle':
        'Les personnes à qui la préproduction peut envoyer de vrais e-mails. Préproduction uniquement.',
    'admin.section.mail_testers.search': 'Rechercher un testeur par e-mail…',
    'admin.mail_testers.intro':
        'La préproduction n’envoie de vrais e-mails qu’aux testeurs approuvés ; les autres messages sont abandonnés. Ajoutez ici l’adresse exacte d’un testeur, avec un motif. Chaque modification est enregistrée à votre nom.',
    'admin.mail_testers.server.title': 'Toujours approuvés sur ce serveur',
    'admin.mail_testers.server.body':
        'Définis sur le serveur de préproduction. Modifiez-les là-bas, pas ici.',
    'admin.mail_testers.add.label': 'Ajouter un testeur',
    'admin.mail_testers.add.email': 'Adresse e-mail du testeur',
    'admin.mail_testers.add.placeholder': 'nom@exemple.com',
    'admin.mail_testers.add.cta': 'Ajouter le testeur',
    'admin.mail_testers.table': 'Testeurs nommés',
    'admin.mail_testers.col.email': 'E-mail',
    'admin.mail_testers.col.added_by': 'Ajouté par',
    'admin.mail_testers.col.added_at': 'Ajouté',
    'admin.mail_testers.col.actions': 'Actions',
    'admin.mail_testers.remove': 'Retirer',
    'admin.mail_testers.remove_label': 'Retirer {email}',
    'admin.mail_testers.empty': 'Aucun testeur nommé pour le moment.',
    'admin.mail_testers.stage.add.title': 'Ajouter {email} comme testeur',
    'admin.mail_testers.stage.add.body':
        'La préproduction pourra envoyer de vrais e-mails à cette adresse, y compris les messages d’inscription et de réinitialisation du mot de passe.',
    'admin.mail_testers.stage.add.cta': 'Ajouter le testeur',
    'admin.mail_testers.stage.add.placeholder':
        'Pourquoi cette personne a-t-elle besoin des e-mails de préproduction ?',
    'admin.mail_testers.stage.remove.title': 'Retirer {email}',
    'admin.mail_testers.stage.remove.body':
        'La préproduction cessera immédiatement d’envoyer des e-mails à cette adresse.',
    'admin.mail_testers.stage.remove.cta': 'Retirer le testeur',
    'admin.mail_testers.stage.remove.placeholder':
        'Pourquoi retirer ce testeur ?',
    'admin.drawer.close': 'Fermer',
    'admin.stage.reason_label': 'Motif pour le registre',
    'admin.stage.logged_as':
        "Consigné dans la piste d'audit au nom de {name} · {role}",
    'admin.stage.cancel': 'Annuler',
    'admin.rating.pending': 'Audit en attente',
    'admin.rating.of_five': '{band} {score} / 5',
    'admin.rating.band.strong': 'Solide',
    'admin.rating.band.stable': 'Stable',
    'admin.rating.band.weak': 'Faible',
    'admin.rating.band.distressed': 'En difficulté',
    'admin.policy.eyebrow': 'Défini dans les politiques',
    'admin.policy.target_dscr': 'DSCR cible',
    'admin.policy.listing_audit': "Audit d'admission",
    'admin.policy.audit_routing': "Seuil d'orientation vers l'audit",
    'admin.policy.rate_band': 'Fourchette de taux · fixe, tout compris',
    'admin.policy.min_revenue': "Chiffre d'affaires annuel min.",
    'admin.policy.min_statement_years': 'Années de relevés min.',
    'admin.policy.rdb_certificate': 'Certificat RDB',
    'admin.policy.max_notes': 'Titres max. / entreprise',
    'admin.policy.dual_control_threshold': 'Deux approbateurs à partir de',
    'admin.time.now': "à l'instant",
    'admin.time.minutes': 'il y a {count} min',
    'admin.time.hours': 'il y a {count} h',
    'admin.time.days': 'il y a {count} j',
    'admin.ledger.kind.investment': 'Investissement',
    'admin.ledger.kind.disbursement': 'Décaissement',
    'admin.ledger.kind.repayment': 'Remboursement',
    'admin.ledger.kind.service_fee': 'Frais de service',
    'admin.ledger.kind.repayment_fee': 'Frais de remboursement',
    'admin.ledger.kind.auditor_share': "Part du partenaire d'audit",
    'admin.ledger.kind.distribution': 'Versement aux investisseurs',
    'admin.ledger.kind.recovery': 'Versement de recouvrement',
    'admin.ledger.kind.deposit': 'Dépôt au portefeuille',
    'admin.ledger.kind.withdrawal': 'Retrait',
    'admin.ledger.kind.secondary': 'Transaction secondaire',
    'admin.ledger.kind.secondary_fee': 'Frais secondaires',
    'admin.ledger.kind.contra': 'Contre-passation',
    'admin.today.commands': 'Commandes rapides',
    'admin.today.command.review_queue': "File d'examen",
    'admin.today.command.audit_desk': "Bureau d'audit",
    'admin.today.command.policies': 'Politiques',
    'admin.today.kpi.capital_raised': 'Capital total levé',
    'admin.today.kpi.capital_raised_trend': 'Sur tous les titres',
    'admin.today.kpi.active_businesses': 'Entreprises actives',
    'admin.today.kpi.active_businesses_trend': 'Sur la plateforme',
    'admin.today.kpi.verified_investors': 'Investisseurs vérifiés',
    'admin.today.kpi.verified_investors_trend': 'KYC validé',
    'admin.today.kpi.outstanding_notes': 'Titres en cours',
    'admin.today.kpi.outstanding_notes_trend':
        'En ligne · financement · remboursement',
    'admin.today.kpi.treasury_position': 'Position de trésorerie',
    'admin.today.kpi.treasury_positive':
        'Frais encaissés, moins les versements',
    'admin.today.kpi.treasury_negative': "Plus versé qu'encaissé",
    'admin.today.kpi.default_rate': 'Taux de défaut',
    'admin.today.kpi.default_rate_trend': '{failed} sur {total} titres',
    'admin.today.kpi.secondary_volume': 'Volume secondaire du jour',
    'admin.today.kpi.secondary_volume_trend': "D'investisseur à investisseur",
    'admin.today.kpi.not_tracked': 'Pas encore suivi',
    'admin.today.attention.applications_pending': 'Demandes en attente',
    'admin.today.attention.kyc_awaiting': "KYC en attente d'examen",
    'admin.today.attention.notes_late': 'Titres en retard de paiement',
    'admin.today.attention.notes_default_risk': 'Titres à risque de défaut',
    'admin.today.attention.frozen_accounts': 'Comptes gelés',
    'admin.today.breaks.title': 'Écarts de rapprochement',
    'admin.today.breaks.caption':
        "Chaque écart reste ici, daté, jusqu'à son explication. Attribuez-le ou escaladez-le.",
    'admin.today.breaks.ledger': 'Ouvrir le grand livre',
    'admin.today.breaks.empty_title': 'Grand livre entièrement rapproché',
    'admin.today.breaks.empty_body': 'Aucun écart ouvert.',
    'admin.today.breaks.untracked_title': 'Rapprochement pas encore suivi',
    'admin.today.breaks.untracked_body':
        'Les écarts apparaîtront ici une fois le rapprochement des relevés connecté.',
    'admin.today.breaks.age': 'Ouvert depuis {days} jours',
    'admin.today.breaks.unassigned': 'Non attribué',
    'admin.today.breaks.owner': 'Responsable : {name}',
    'admin.today.breaks.assign': "M'attribuer",
    'admin.today.breaks.escalate': 'Escalader',
    'admin.today.breaks.assign_title': 'Prendre en charge {reference}',
    'admin.today.breaks.assign_body':
        "Vous devenez responsable de cet écart jusqu'à son explication. Dites comment vous allez le traiter.",
    'admin.today.breaks.assign_cta': 'Prendre en charge',
    'admin.today.breaks.assign_placeholder':
        'ex. Rapprochement avec le fichier de règlement MoMo du 22 sept.',
    'admin.today.breaks.escalate_title': 'Escalader {reference}',
    'admin.today.breaks.escalate_body':
        "L'écart passe à la direction financière. Dites ce que vous avez trouvé et pourquoi elle est nécessaire.",
    'admin.today.breaks.escalate_cta': 'Escalader',
    'admin.today.breaks.escalate_placeholder':
        "ex. Le relevé du prestataire contredit notre registre d'envoi ; la banque est nécessaire.",
    'admin.today.capital.title': 'Capital levé',
    'admin.today.capital.from': 'Du',
    'admin.today.capital.to': 'Au',
    'admin.today.capital.from_date': 'Date de début',
    'admin.today.capital.from_time': 'Heure de début',
    'admin.today.capital.to_date': 'Date de fin',
    'admin.today.capital.to_time': 'Heure de fin',
    'admin.today.capital.clear': 'Effacer',
    'admin.today.capital.all_time': 'Depuis le début',
    'admin.today.capital.range': '{from} → {to}',
    'admin.today.capital.caption': '{range} · regroupé par {grain}',
    'admin.today.capital.grain.year': 'année',
    'admin.today.capital.grain.month': 'mois',
    'admin.today.capital.grain.day': 'jour',
    'admin.today.capital.grain.hour': 'heure',
    'admin.today.health.title': 'Santé du portefeuille',
    'admin.today.health.caption':
        '{count} titres en ligne, en financement, en remboursement ou clos',
    'admin.today.health.donut': '{pct} % des titres sains',
    'admin.today.health.healthy_caption': 'sains',
    'admin.today.health.healthy': 'Sain',
    'admin.today.health.watch': 'Sous surveillance',
    'admin.today.health.distressed': 'En difficulté',
    'admin.today.activity.title': 'Activité en direct',
    'admin.today.activity.caption': "Dans tout l'écosystème",
    'admin.today.activity.empty': "Aucun mouvement d'argent pour l'instant.",
    'admin.today.untracked': 'Pas encore suivi dans la console.',
    'admin.today.capital.empty': 'Aucun capital levé sur cette période.',
    'admin.today.activity.see_all': "Voir toute l'activité dans le grand livre",
    'admin.today.lifecycle.title': 'Cycle de vie des titres',
    'admin.today.lifecycle.caption': 'Où se trouve chaque titre en ce moment',
    'admin.today.lifecycle.submitted': 'Soumis',
    'admin.today.lifecycle.live': 'En ligne',
    'admin.today.lifecycle.funded': 'Financé',
    'admin.today.lifecycle.repaying': 'En remboursement',
    'admin.today.lifecycle.matured': 'Arrivé à échéance',
    'admin.today.lifecycle.failed': 'Échoué',
    'admin.today.pending.title': 'Demandes en attente',
    'admin.today.pending.empty': 'Aucune demande en attente.',
    'admin.today.pending.review': 'Examiner',
    'admin.today.pending.review_named': 'Examiner {name}',
    'admin.today.sectors.title': 'Exposition sectorielle',
    'admin.today.sectors.about': "À propos de l'exposition sectorielle",
    'admin.today.sectors.tip':
        'Capital en cours par secteur. Le rouge signale au moins un titre sous surveillance ou en difficulté.',
    'admin.today.sectors.notes': '{count} titres',
    'admin.today.treasury.title': 'Aperçu de la trésorerie',
    'admin.today.treasury.invested': 'Capital investi (primaire)',
    'admin.today.treasury.disbursed': 'Versé aux entreprises',
    'admin.today.treasury.platform_net': 'Position nette de la plateforme',
    'admin.today.treasury.paid_to_investors': 'Versé aux investisseurs',
    'admin.today.collections.title': 'Recouvrements',
    'admin.today.collections.caption': '{count} titres en remboursement',
    'admin.today.collections.outstanding': 'Encours',
    'admin.today.collections.next_due': 'Prochaines échéances',
    'admin.today.collections.on_track': 'Dans les temps',
    'admin.today.collections.late': 'En retard',
    'admin.today.collections.default_risk': 'À risque de défaut',
    'admin.trail.by': 'par {actor}',
    'admin.trail.reason': 'Motif : {reason}',
    'admin.applications.policy_title': 'Règles de souscription en vigueur',
    'admin.applications.tabs': 'États des demandes',
    'admin.applications.tab.pending': 'En attente',
    'admin.applications.tab.under_review': 'En examen',
    'admin.applications.tab.escalated': 'Escaladées',
    'admin.applications.tab.approved': 'Approuvées',
    'admin.applications.tab.rejected': 'Refusées',
    'admin.applications.table': 'Demandes',
    'admin.applications.col.business': 'Entreprise · Titre',
    'admin.applications.col.sector': 'Secteur',
    'admin.applications.col.requested': 'Demandé',
    'admin.applications.col.capacity': 'Capacité utilisée',
    'admin.applications.col.rating': 'Notation',
    'admin.applications.col.submitted': 'Soumise',
    'admin.applications.col.decision': 'Décision · Actions',
    'admin.applications.terms': '{term} mois · {rate} % fixe',
    'admin.applications.capacity_used': 'Capacité utilisée',
    'admin.applications.capacity_unavailable': 'Non disponible',
    'admin.applications.older': 'Demandes plus anciennes',
    'admin.decision.reason_code.CURRENT_RELEASE_REVIEW_REQUIRED':
        "Revue d'autorisation actuelle requise : une offre conservée n'est pas une approbation en cours. Vérifiez les contrôles d'autorisation ci-dessous.",
    'admin.applications.decision.approve': 'Approbation auto',
    'admin.applications.decision.reject': 'Signal : refus',
    'admin.applications.decision.audit': "Orienter vers l'audit",
    'admin.applications.decision.review': 'À examiner',
    'admin.applications.review': 'Examiner',
    'admin.applications.review_named': 'Examiner {name}',
    'admin.applications.approve': 'Approuver',
    'admin.applications.approve_named': 'Approuver {name}',
    'admin.applications.empty': 'Aucune demande dans cette file.',
    'admin.applications.state.submitted': 'En attente',
    'admin.applications.state.under_review': 'En examen',
    'admin.applications.state.info_requested': 'Informations demandées',
    'admin.applications.state.escalated': 'Escaladée',
    'admin.applications.state.approved': 'Approuvée',
    'admin.applications.state.rejected': 'Refusée',
    'admin.review.label': 'Examen de souscription, {business}',
    'admin.review.eyebrow': 'Examen de souscription',
    'admin.review.recommend.approve': 'Recommandation : approuver',
    'admin.review.recommend.reject': 'Recommandation : refuser',
    'admin.review.recommend.audit': "Recommandation : orienter vers l'audit",
    'admin.review.recommend.review': 'Recommandation : examen manuel',
    'admin.review.audit.missing_title': "Audit d'admission manquant",
    'admin.review.audit.missing_body':
        "L'approbation est bloquée jusqu'à ce qu'un partenaire d'audit scelle l'audit d'admission de cette entreprise. Demandez-le à l'entreprise ou escaladez.",
    'admin.review.audit.pending_title': "Audit d'admission en cours",
    'admin.review.audit.pending_body':
        "L'approbation se débloque quand le partenaire d'audit scelle l'audit. Rien ne peut être admis avant.",
    'admin.review.requested': 'Demandé',
    'admin.review.rating': 'Notation Rozine',
    'admin.review.rating_score': '· {score} / 5',
    'admin.review.term': 'Durée',
    'admin.review.term_months': '{count} mois',
    'admin.review.yield': 'Rendement investisseur',
    'admin.review.yield_value': '{rate} % fixe',
    'admin.review.capacity_title': 'Capacité utilisée par cette levée',
    'admin.review.capacity_approved': 'Capacité approuvée {amount}',
    'admin.review.capacity_existing':
        '{count} titres actifs · {amount} en cours',
    'admin.review.factors_title': 'Détail de la notation',
    'admin.review.factors_caption': 'Du moteur de souscription · lecture seule',
    'admin.review.factor.repayment_history': 'Historique de remboursement',
    'admin.review.factor.revenue_consistency':
        "Régularité du chiffre d'affaires",
    'admin.review.factor.statement_record': 'Historique des relevés',
    'admin.review.factor.capacity_headroom': 'Marge de capacité',
    'admin.review.factor.sector_risk': 'Risque sectoriel',
    'admin.review.use_of_funds': 'Utilisation des fonds',
    'admin.review.submitted': 'Soumise le {date}',
    'admin.review.reviewer': 'Examinateur : {name}',
    'admin.review.unassigned': 'Non attribué',
    'admin.review.business_profile': "Voir le profil complet de l'entreprise →",
    'admin.review.trail': 'Historique des décisions',
    'admin.review.trail_empty': 'Aucune décision enregistrée.',
    'admin.review.approve': 'Approuver et créer le titre',
    'admin.review.reject': 'Refuser',
    'admin.review.take': 'Prendre en examen',
    'admin.review.info': 'Demander des informations',
    'admin.review.escalate': 'Escalader',
    'admin.review.stage.approve.title': 'Approuver et créer le titre',
    'admin.review.stage.approve.body':
        "Cela crée un titre de {amount} à {rate} % fixe sur {term} mois et le publie aux investisseurs. L'entreprise sera avertie.",
    'admin.review.stage.approve.cta': "Confirmer l'approbation",
    'admin.review.stage.approve.placeholder':
        'ex. Preuves complètes ; DSCR et capacité conformes à la politique.',
    'admin.review.stage.reject.title': 'Refuser la demande',
    'admin.review.stage.reject.body':
        "L'entreprise sera avertie. Donnez un motif clair pour qu'elle comprenne et puisse redéposer.",
    'admin.review.stage.reject.cta': 'Confirmer le refus',
    'admin.review.stage.reject.placeholder':
        'ex. Le montant demandé dépasse la capacité approuvée ; réduisez-le et redéposez.',
    'admin.review.stage.info.title': "Demander plus d'informations",
    'admin.review.stage.info.body':
        "Dites précisément à l'entreprise quoi fournir. Elle recevra une notification avec votre demande.",
    'admin.review.stage.info.cta': 'Envoyer la demande',
    'admin.review.stage.info.placeholder':
        'ex. Téléversez les relevés bancaires et mobile money des 6 derniers mois.',
    'admin.review.stage.escalate.title': 'Escalader au comité de crédit',
    'admin.review.stage.escalate.body':
        'La demande passe en examen senior. Ajoutez du contexte pour le comité.',
    'admin.review.stage.escalate.cta': 'Escalader',
    'admin.review.stage.escalate.placeholder':
        'ex. Preuves limites mais bon historique de remboursement — avis du comité nécessaire.',
    'admin.review.stage.take.title': 'Prendre en examen',
    'admin.review.stage.take.body':
        'La demande passe en examen avec vous comme examinateur.',
    'admin.review.stage.take.cta': 'Prendre en examen',
    'admin.review.stage.take.placeholder':
        'ex. Je le prends pour la séance de crédit du jour.',
    'admin.maker_checker.label': 'Approbations',
    'admin.maker_checker.step': 'Étape {n}',
    'admin.maker_checker.maker_title': 'Autorisé',
    'admin.maker_checker.checker_title': 'Seconde approbation',
    'admin.maker_checker.maker_empty':
        "Personne n'a encore autorisé ce versement.",
    'admin.maker_checker.checker_empty':
        'Un autre approbateur vérifie le versement après son autorisation.',
    'admin.maker_checker.awaiting': 'En attente du second approbateur',
    'admin.maker_checker.self_blocked':
        "Vous avez autorisé ce versement, vous ne pouvez donc pas aussi l'approuver. Un autre approbateur doit le vérifier.",
    'admin.maker_checker.by': '{actor} · {at}',
    'admin.disbursements.policy_title': 'Règles de versement en vigueur',
    'admin.disbursements.title': 'Décaissements en attente',
    'admin.disbursements.awaiting_count':
        '{count} en attente du second approbateur',
    'admin.disbursements.table': 'Décaissements en attente',
    'admin.disbursements.col.reference': 'Réf. versement',
    'admin.disbursements.col.note': 'Titre',
    'admin.disbursements.col.recipient': 'Bénéficiaire',
    'admin.disbursements.col.amount': 'Montant',
    'admin.disbursements.col.due': 'Échéance',
    'admin.disbursements.col.actions': 'État · Actions',
    'admin.disbursements.state.ready': 'Prêt à autoriser',
    'admin.disbursements.state.awaiting_second_approver':
        'En attente du second approbateur',
    'admin.disbursements.state.on_hold': 'En suspens',
    'admin.disbursements.state.dispatched': 'Envoyé au prestataire',
    'admin.disbursements.state.paid': 'Payé',
    'admin.disbursements.state.failed': 'Versement échoué',
    'admin.disbursements.action.release': 'Verser',
    'admin.disbursements.action.check': 'Vérifier',
    'admin.disbursements.action.open': 'Ouvrir',
    'admin.disbursements.action.inspect': 'Inspecter',
    'admin.disbursements.open_named': 'Ouvrir {reference}',
    'admin.disbursements.today': "Aujourd'hui",
    'admin.disbursements.tomorrow': 'Demain',
    'admin.disbursements.in_days': 'Dans {count} jours',
    'admin.disbursements.overdue': 'En retard de {count} j',
    'admin.disbursements.empty_title': 'Tous les décaissements sont versés',
    'admin.disbursements.empty_body': "Aucun versement dû pour l'instant.",
    'admin.disbursements.drawer_label': 'Décaissement {reference}',
    'admin.disbursements.independence.title': 'Déclaration d’indépendance',
    'admin.disbursements.amount': 'Montant',
    'admin.disbursements.destination': 'Destination',
    'admin.disbursements.due': 'Échéance',
    'admin.disbursements.rule_dual':
        "À partir de {threshold}, un versement exige deux personnes différentes : l'une autorise, l'autre approuve. Personne n'approuve son propre versement.",
    'admin.disbursements.rule_single':
        'En dessous de {threshold}, un seul approbateur autorisé verse ce montant.',
    'admin.disbursements.failure_title': 'Versement échoué',
    'admin.disbursements.failure_code': 'Code',
    'admin.disbursements.failure_provider': 'Référence prestataire',
    'admin.disbursements.failure_at': 'Échec le',
    'admin.disbursements.failure_attempts': 'Tentatives',
    'admin.disbursements.failure_inspect':
        "Inspectez avant de réessayer : confirmez auprès du prestataire qu'aucun argent n'est sorti, pour ne jamais envoyer deux fois le même versement.",
    'admin.disbursements.approvals': 'Approbations',
    'admin.disbursements.trail': 'Historique du versement',
    'admin.disbursements.trail_empty': "Rien d'enregistré pour l'instant.",
    'admin.disbursements.command.authorize': 'Autoriser le versement',
    'admin.disbursements.command.approve': 'Approuver le décaissement',
    'admin.disbursements.command.reject': 'Refuser',
    'admin.disbursements.command.hold': 'Suspendre',
    'admin.disbursements.command.retry': 'Réessayer le versement',
    'admin.disbursements.stage.authorize.title': 'Autoriser ce versement',
    'admin.disbursements.stage.authorize.body':
        "Vous autorisez le paiement de {amount} à {business}. Le contrôle préalable est exécuté ; un autre membre du personnel doit ensuite l'approuver.",
    'admin.disbursements.stage.authorize.cta': 'Autoriser',
    'admin.disbursements.stage.authorize.placeholder':
        'ex. Levée entièrement financée ; destination vérifiée selon le mandat RDB.',
    'admin.disbursements.stage.approve.title': 'Approuver ce versement',
    'admin.disbursements.stage.approve.body':
        "En tant que second membre du personnel, vous approuvez le paiement de {amount} à {business}. Cela enregistre l'intention de paiement ; ce n'est pas un paiement. Le service ne l'envoie qu'après sa propre revérification.",
    'admin.disbursements.stage.approve.cta':
        "Approuver et enregistrer l'intention",
    'admin.disbursements.stage.approve.placeholder':
        "ex. Total financé et destination conformes à l'offre.",
    'admin.disbursements.stage.reject.title': 'Refuser ce versement',
    'admin.disbursements.stage.reject.body':
        "Cela annule uniquement l'autorisation et enregistre votre motif. La levée n'est pas annulée ; le décaissement revient à l'autorisation.",
    'admin.disbursements.stage.reject.cta': 'Refuser le versement',
    'admin.disbursements.stage.reject.placeholder':
        "ex. Le nom du compte de destination ne correspond pas à l'entreprise.",
    'admin.disbursements.stage.hold.title': 'Suspendre ce versement',
    'admin.disbursements.stage.hold.body':
        "Rien n'est versé pendant la suspension. Dites pourquoi, pour que la personne suivante sache quoi lever.",
    'admin.disbursements.stage.hold.cta': 'Suspendre',
    'admin.disbursements.stage.hold.placeholder':
        "ex. En attente de la confirmation du nouveau numéro MoMo de l'entreprise.",
    'admin.disbursements.stage.retry.title': 'Réessayer ce versement',
    'admin.disbursements.stage.retry.body':
        "Ne réessayez qu'une fois le prestataire ayant confirmé que la tentative échouée n'a déplacé aucun argent. {amount} sera renvoyé à {business}.",
    'admin.disbursements.stage.retry.cta': 'Réessayer',
    'admin.disbursements.stage.retry.placeholder':
        "ex. MTN confirme l'annulation complète de MM-88213 ; destination revérifiée.",
    'admin.parties.policy_title': "Règles d'éligibilité des entreprises",
    'admin.parties.filter': 'Filtrer par statut',
    'admin.parties.chip.all': 'Tous',
    'admin.parties.chip.healthy': 'Sains',
    'admin.parties.chip.watch': 'Sous surveillance',
    'admin.parties.chip.distressed': 'En difficulté',
    'admin.parties.chip.frozen': 'Gelés',
    'admin.parties.chip.verified': 'Vérifiés',
    'admin.parties.chip.pending': 'En attente',
    'admin.parties.chip.kyc_overdue': 'KYC en retard',
    'admin.parties.chip.restricted': 'Restreints',
    'admin.parties.chip.active': 'Actifs',
    'admin.parties.chip.licence_expired': 'Licence expirée',
    'admin.parties.chip.suspended': 'Suspendus',
    'admin.parties.select.sector': 'Secteur',
    'admin.parties.select.country': 'Pays',
    'admin.parties.select.sort': 'Trier',
    'admin.parties.stats.total_businesses': 'Entreprises au total',
    'admin.parties.stats.avg_rating': 'Notation moyenne',
    'admin.parties.stats.active_notes': 'Titres actifs',
    'admin.parties.stats.capital_raised': 'Capital levé',
    'admin.parties.stats.total_investors': 'Investisseurs au total',
    'admin.parties.stats.kyc_verified': 'KYC vérifié',
    'admin.parties.stats.awaiting_kyc': 'KYC en attente',
    'admin.parties.stats.total_aum': 'Encours total',
    'admin.parties.stats.partners': "Partenaires d'audit",
    'admin.parties.stats.active_partners': 'Actifs',
    'admin.parties.stats.pending_partners': 'Vérification en attente',
    'admin.parties.stats.licences_expiring': 'Licences expirant ≤30 j',
    'admin.parties.stats.operators': 'Opérateurs',
    'admin.parties.stats.approvers': 'Approbateurs',
    'admin.parties.stats.frozen_accounts': 'Gelés',
    'admin.parties.count.business': '{count} entreprises',
    'admin.parties.count.investor': '{count} investisseurs',
    'admin.parties.count.auditor': "{count} partenaires d'audit",
    'admin.parties.count.staff': '{count} opérateurs',
    'admin.parties.showing.business':
        '{shown} premières sur {total} entreprises',
    'admin.parties.showing.investor':
        '{shown} premiers sur {total} investisseurs',
    'admin.parties.showing.auditor':
        "{shown} premiers sur {total} partenaires d'audit",
    'admin.parties.showing.staff': '{shown} premiers sur {total} opérateurs',
    'admin.parties.table.business': 'Entreprises',
    'admin.parties.table.investor': 'Investisseurs',
    'admin.parties.table.auditor': "Partenaires d'audit",
    'admin.parties.table.staff': 'Opérateurs',
    'admin.parties.empty.business':
        'Aucune entreprise ne correspond à ces filtres.',
    'admin.parties.empty.investor':
        'Aucun investisseur ne correspond à ces filtres.',
    'admin.parties.empty.auditor':
        "Aucun partenaire d'audit ne correspond à ces filtres.",
    'admin.parties.empty.staff': 'Aucun opérateur ne correspond à ces filtres.',
    'admin.parties.col.business': 'Entreprise',
    'admin.parties.col.rating': 'Notation',
    'admin.parties.col.active_notes': 'Titres actifs',
    'admin.parties.col.investors': 'Investisseurs',
    'admin.parties.col.raised': 'Levé',
    'admin.parties.col.capacity': 'Capacité utilisée',
    'admin.parties.col.status': 'Statut',
    'admin.parties.col.investor': 'Investisseur',
    'admin.parties.col.kyc': 'KYC',
    'admin.parties.col.portfolio': 'Portefeuille',
    'admin.parties.col.wallet': 'Portefeuille espèces',
    'admin.parties.col.holdings': 'Positions',
    'admin.parties.col.businesses': 'Entreprises',
    'admin.parties.col.partner': 'Partenaire',
    'admin.parties.col.district': 'District',
    'admin.parties.col.engagements': 'Missions',
    'admin.parties.col.on_time': "À l'heure",
    'admin.parties.col.share_mtd': 'Part du mois',
    'admin.parties.col.operator': 'Opérateur',
    'admin.parties.col.role': 'Rôle',
    'admin.parties.badge.frozen': 'Gelé',
    'admin.parties.badge.kyc_overdue': 'KYC en retard',
    'admin.parties.badge.restricted': 'Restreint',
    'admin.parties.health.healthy': 'Sain',
    'admin.parties.health.watch': 'Sous surveillance',
    'admin.parties.health.distressed': 'En difficulté',
    'admin.parties.health.active': 'Actif',
    'admin.parties.health.kyc_pending': 'KYC en attente',
    'admin.parties.health.frozen': 'Gelé',
    'admin.parties.health.not_tracked': 'Non suivi',
    'admin.parties.kyc.verified': 'Vérifié',
    'admin.parties.kyc.pending': 'En attente',
    'admin.parties.kyc.overdue': 'En retard',
    'admin.parties.kyc.rejected': 'Refusé',
    'admin.parties.status.frozen': 'Gelé',
    'admin.parties.status.restricted': 'Restreint',
    'admin.parties.status.verified': 'Vérifié',
    'admin.parties.status.pending': 'En attente',
    'admin.parties.status.active': 'Actif',
    'admin.parties.standing.active': 'Actif',
    'admin.parties.standing.pending': 'Vérification en attente',
    'admin.parties.standing.licence_expired': 'Licence expirée',
    'admin.parties.standing.suspended': 'Suspendu',
    'admin.parties.you': 'Vous',
    'admin.parties.drawer_label': '{name}, fiche de la partie',
    'admin.parties.type.business': 'Entreprise',
    'admin.parties.type.investor': 'Investisseur',
    'admin.parties.type.auditor': "Partenaire d'audit",
    'admin.parties.type.staff': 'Personnel',
    'admin.parties.app.business': 'Entreprise',
    'admin.parties.app.investor': 'Investisseur',
    'admin.parties.app.auditor': 'Auditeur',
    'admin.parties.app.staff': 'Admin',
    'admin.parties.tabs': 'Fiche de la partie',
    'admin.parties.tab.overview': 'Aperçu',
    'admin.parties.tab.activity': 'Activité',
    'admin.parties.tab.controls': 'Contrôles',
    'admin.parties.frozen_note':
        'Gelé par {actor} le {at}. Voir Contrôles pour le motif et la levée.',
    'admin.parties.kyc_overdue_note':
        'Revérification KYC en retard depuis le {date}.',
    'admin.parties.stat.active_notes': 'Titres actifs',
    'admin.parties.stat.investors': 'Investisseurs',
    'admin.parties.stat.raised': 'Levé',
    'admin.parties.stat.rating': 'Notation Rozine',
    'admin.parties.stat.capacity': 'Capacité',
    'admin.parties.stat.capacity_used': 'Capacité utilisée',
    'admin.parties.stat.portfolio': 'Portefeuille',
    'admin.parties.stat.wallet': 'Portefeuille espèces',
    'admin.parties.stat.holdings': 'Positions',
    'admin.parties.stat.businesses': 'Entreprises',
    'admin.parties.stat.kyc_due': 'Échéance KYC',
    'admin.parties.stat.engagements': 'Missions',
    'admin.parties.stat.on_time': "À l'heure",
    'admin.parties.stat.share_mtd': 'Part du mois',
    'admin.parties.stat.role': 'Rôle',
    'admin.parties.stat.last_sign_in': 'Dernière connexion',
    'admin.parties.list.active_notes': 'Titres actifs',
    'admin.parties.list.holdings': 'Positions',
    'admin.parties.list.engagements': 'Missions',
    'admin.parties.list_empty': "Rien pour l'instant.",
    'admin.parties.note_status.draft': 'Brouillon',
    'admin.parties.note_status.active': 'En ligne',
    'admin.parties.note_status.funded': 'Financé',
    'admin.parties.note_status.repaying': 'En remboursement',
    'admin.parties.note_status.completed': 'Terminé',
    'admin.parties.note_status.failed': 'Échoué',
    'admin.parties.history': 'Historique',
    'admin.parties.history_empty': 'Aucune activité enregistrée.',
    'admin.parties.kyc_title': 'KYC et vérification',
    'admin.parties.kyc_current': 'Statut actuel',
    'admin.parties.kyc_due': 'Revérification due le {date}',
    'admin.parties.verify_kyc': '✓ Valider le KYC',
    'admin.parties.verify_licence': '✓ Valider la licence',
    'admin.parties.reject': 'Refuser',
    'admin.parties.licence_title': 'Licence ICPAR et vérification',
    'admin.parties.licence_status': 'Statut de vérification',
    'admin.parties.licence.verified': 'Vérifiée',
    'admin.parties.licence.pending': 'En attente',
    'admin.parties.licence.expired': 'Expirée',
    'admin.parties.licence_field.member_id': 'ID ICPAR',
    'admin.parties.licence_field.licence': 'Licence PPC',
    'admin.parties.licence_field.expires_on': 'Expire le',
    'admin.parties.licence_field.district': 'District',
    'admin.parties.account_state': 'État du compte',
    'admin.parties.frozen_by': 'Gelé par {actor} · {at}',
    'admin.parties.legal_hold':
        'Une retenue légale maintient ce compte gelé. Seuls la conformité ou le juridique peuvent le lever.',
    'admin.parties.freeze': 'Geler le compte',
    'admin.parties.release': 'Réactiver le compte',
    'admin.parties.freeze_caption':
        "Le gel retire immédiatement l'accès du compte à l'application {app}. Réversible à tout moment ; le gel et la levée portent un motif et un nom.",
    'admin.parties.restrictions': 'Historique des gels',
    'admin.parties.restrictions_empty': "Ce compte n'a jamais été gelé.",
    'admin.parties.stage.freeze.title': 'Geler {name}',
    'admin.parties.stage.freeze.body':
        "L'accès à l'application {app} s'arrête aussitôt. Dites pourquoi, pour que la levée puisse être jugée.",
    'admin.parties.stage.freeze.cta': 'Geler le compte',
    'admin.parties.stage.freeze.placeholder':
        'ex. Alerte fraude FA-2291 du prestataire sur la destination de versement.',
    'admin.parties.stage.release.title': 'Réactiver {name}',
    'admin.parties.stage.release.body':
        "L'accès à l'application {app} revient aussitôt. Dites ce qui a levé le motif du gel.",
    'admin.parties.stage.release.cta': 'Réactiver le compte',
    'admin.parties.stage.release.placeholder':
        'ex. Le prestataire a clos FA-2291 comme faux positif ; destination revérifiée.',
    'admin.parties.stage.verify_kyc.title': 'Valider le KYC de {name}',
    'admin.parties.stage.verify_kyc.body':
        "Une fois vérifié, le compte peut opérer dans l'application {app} dans les limites de la politique.",
    'admin.parties.stage.verify_kyc.cta': 'Valider le KYC',
    'admin.parties.stage.verify_kyc.placeholder':
        'ex. NID et selfie concordants ; adresse confirmée par la facture.',
    'admin.parties.stage.reject_kyc.title': 'Refuser le KYC de {name}',
    'admin.parties.stage.reject_kyc.body':
        "Le compte reste sans possibilité d'opérer dans l'application {app}. Dites ce qui a échoué pour qu'il corrige.",
    'admin.parties.stage.reject_kyc.cta': 'Refuser le KYC',
    'admin.parties.stage.reject_kyc.placeholder':
        'ex. Photo de la pièce illisible ; demander un nouveau téléversement.',
    'admin.parties.stage.verify_licence.title': 'Valider la licence de {name}',
    'admin.parties.stage.verify_licence.body':
        "Le partenaire rejoint le pool d'affectation de son district dans l'application {app}.",
    'admin.parties.stage.verify_licence.cta': 'Valider la licence',
    'admin.parties.stage.verify_licence.placeholder':
        "ex. Registre ICPAR vérifié aujourd'hui ; certificat valide jusqu'en 2027.",
    'admin.parties.stage.reject_licence.title': 'Refuser la licence de {name}',
    'admin.parties.stage.reject_licence.body':
        "Le partenaire ne reçoit aucune mission dans l'application {app} tant qu'une licence valide n'est pas vérifiée.",
    'admin.parties.stage.reject_licence.cta': 'Refuser la licence',
    'admin.parties.stage.reject_licence.placeholder':
        'ex. ID membre absent du registre ICPAR.',
    'admin.ledger.title': 'Grand livre',
    'admin.ledger.caption':
        "Chaque mouvement d'argent, le plus récent d'abord.",
    'admin.ledger.count': '{count} écritures',
    'admin.ledger.table': 'Grand livre',
    'admin.ledger.search_label': 'Rechercher dans le grand livre',
    'admin.ledger.search_placeholder': 'Rechercher type, partie ou référence…',
    'admin.ledger.col.time': 'Heure',
    'admin.ledger.col.type': 'Type',
    'admin.ledger.col.from_to': 'De → À',
    'admin.ledger.col.reference': 'Référence',
    'admin.ledger.col.amount': 'Montant',
    'admin.ledger.col.account': 'Compte',
    'admin.ledger.col.debit': 'Débit',
    'admin.ledger.col.credit': 'Crédit',
    'admin.ledger.open_named': "Ouvrir l'écriture {id}",
    'admin.ledger.see_all': 'Voir les {count} écritures',
    'admin.ledger.empty': 'Aucune écriture sur cette période.',
    'admin.ledger.drawer_label': 'Écriture {id}',
    'admin.ledger.fact.from': 'De',
    'admin.ledger.fact.to': 'À',
    'admin.ledger.fact.reference': 'Référence',
    'admin.ledger.fact.operation': 'Opération',
    'admin.ledger.posted_by': 'Passée par {actor} · {at}',
    'admin.ledger.postings': 'Écritures',
    'admin.ledger.total': 'Total',
    'admin.ledger.balanced': 'Équilibrée',
    'admin.ledger.unbalanced': 'Déséquilibrée',
    'admin.ledger.contra_of': 'Cette contre-passation annule',
    'admin.ledger.contra_by': 'Annulée par la contre-passation',
    'admin.ledger.immutable':
        'Les écritures ne sont jamais modifiées. Une correction est une contre-passation distincte, liée ici.',
    'admin.ledger.open_events': 'Voir cette écriture dans le journal →',
    'admin.events.immutable':
        "Les journaux d'audit sont immuables. Rien sur cet écran ne peut être modifié ni supprimé.",
    'admin.events.title': "Piste d'audit",
    'admin.events.table': "Piste d'audit",
    'admin.events.count': '{count} entrées',
    'admin.events.search_label': "Rechercher dans la piste d'audit",
    'admin.events.search_placeholder':
        'Rechercher utilisateur, action ou objet…',
    'admin.events.preset.today': "Aujourd'hui",
    'admin.events.preset.7d': '7 j',
    'admin.events.preset.30d': '30 j',
    'admin.events.from': 'Date de début',
    'admin.events.to': 'Date de fin',
    'admin.events.to_word': 'au',
    'admin.events.clear': 'Effacer',
    'admin.events.clear_filters': 'Effacer les filtres',
    'admin.events.filtered_empty':
        'Rien dans la piste ne correspond à ces filtres.',
    'admin.events.empty': 'Aucune action enregistrée.',
    'admin.events.col.when': 'Quand',
    'admin.events.col.who': 'Qui',
    'admin.events.col.action': 'Action',
    'admin.events.col.object': 'Objet',
    'admin.events.col.source': 'Source',
    'admin.events.no_reason': 'Aucun motif enregistré pour cette action.',
    'admin.events.field': 'Champ',
    'admin.events.before': 'Avant',
    'admin.events.after': 'Après',
    'admin.events.no_changes': 'Aucune valeur modifiée.',
    'admin.events.see_all': 'Voir les {count} entrées',
    'admin.events.export': 'Exporter',
    'admin.events.export_running': 'Export en cours · {actor} · {at}',
    'admin.events.export_download': "Télécharger l'export",
    'admin.events.export_title': 'Exporter cette piste',
    'admin.events.export_body':
        "L'export reprend les filtres affichés et est lui-même consigné dans la piste. Dites à qui il est destiné.",
    'admin.events.export_cta': "Lancer l'export",
    'admin.events.export_placeholder':
        'ex. Demande RCMA 2026-114, activité de septembre.',

    'business.wallet.title': 'Portefeuille',
    'business.wallet.back': "Retour à l'accueil",
    'business.wallet.available': 'Solde disponible',
    'business.wallet.brand': 'Portefeuille Rozine',
    'business.wallet.deposit.button': 'Déposer',
    'business.wallet.processing': 'Traitement…',
    'business.wallet.history': 'Historique des transactions',
    'business.wallet.detail.when': 'Date et heure',
    'business.wallet.detail.reference': 'Référence',
    'business.wallet.detail.done': 'Terminé',

    'business.servicing.wallet.status.active': 'Actif',
    'business.servicing.wallet.status.restricted': 'Restreint',
    'business.servicing.wallet.restricted':
        'Restreint depuis le {date}. Les dépôts et les remboursements restent possibles.',
    'business.servicing.wallet.no_pending': 'Aucun dépôt en attente',
    'business.servicing.wallet.pending': '{amount} en attente de confirmation',
    'business.servicing.wallet.to_repayments': 'Aller aux remboursements',
    'business.servicing.wallet.movement': 'Type de mouvement',
    'business.servicing.wallet.filter.external': 'Dépôts',
    'business.servicing.wallet.filter.internal': 'Remboursements',
    'business.servicing.wallet.entry.deposit': 'Dépôt depuis {counterparty}',
    'business.servicing.wallet.entry.repayment': 'Remboursement · {note}',
    'business.servicing.wallet.instalments': 'Échéances {list}',
    'business.servicing.wallet.empty': 'Rien pour le moment.',
    'business.servicing.wallet.older': 'Afficher plus ancien',
    'business.servicing.wallet.receipt.label': 'Reçu de la transaction',
    'business.servicing.wallet.receipt.psp_fee': 'Frais du prestataire',
    'business.servicing.wallet.receipt.policy': 'Version de la politique',
    'auditor.nav.home': 'Accueil',
    'auditor.nav.jobs': 'Missions',
    'auditor.nav.portfolio': 'Portefeuille',
    'auditor.nav.profile': 'Profil',
    'auditor.nav.conflicts': 'Conflits',
    'auditor.nav.jobs_badge': '{count} offres ouvertes',
    'auditor.clock.label': 'Temps restant pour cette mission',
    'auditor.clock.time_left': 'Temps restant',
    'auditor.time.minutes_ago': 'il y a {count} min',
    'auditor.time.hours_ago': 'il y a {count} h',
    'auditor.time.days_ago': 'il y a {count} j',
    'auditor.home.head_title': 'Accueil',
    'auditor.home.wallet_balance': 'Solde du portefeuille',
    'auditor.home.withdraw': 'Retirer',
    'auditor.home.statement': 'Relevé',
    'auditor.home.notifications': 'Notifications',
    'auditor.home.notifications_unread': 'Notifications, {count} non lues',
    'auditor.home.greeting.morning': 'Bonjour,',
    'auditor.home.greeting.afternoon': 'Bon après-midi,',
    'auditor.home.greeting.evening': 'Bonsoir,',
    'auditor.home.tile.yield': 'Rendement · {month}',
    'auditor.home.tile.deals': 'Dossiers',
    'auditor.home.tile.licence': 'Licence',
    'auditor.rating.word': 'Note',
    'auditor.rating.label': 'Note du partenaire {score} sur 100',
    'auditor.availability.accepting': 'Audits acceptés',
    'auditor.availability.paused': 'En pause',
    'auditor.availability.home_sub':
        'Rayon de {radius} km · {max} missions max à la fois',
    'auditor.availability.home_paused':
        'Touchez pour accepter à nouveau les audits flash',
    'auditor.availability.toggle': 'Accepter des audits',
    'auditor.home.nearby_one': '1 audit flash à proximité',
    'auditor.home.nearby_other': '{count} audits flash à proximité',
    'auditor.home.nearby_sub':
        'Le plus proche à {distance} km · le premier à accepter verrouille le dossier',
    'auditor.home.in_progress': 'En cours',
    'auditor.job.step_of': 'Étape {step} sur {steps}',
    'auditor.job.status.overdue': 'En retard',
    'auditor.job.status.awaiting_cosign': 'En attente de cosignature',
    'auditor.job.reassigned': 'Vous a été réattribué',
    'auditor.standing.title': 'Votre situation',
    'auditor.standing.on_time': 'Clôture à temps',
    'auditor.standing.avg_variance': 'Écart moyen',
    'auditor.standing.jobs_done': 'Missions faites',
    'auditor.standing.clock_expiries': 'Délais expirés',
    'auditor.activity.title': 'Activité récente',
    'auditor.activity.empty': "Rien ne s'est encore passé sur vos dossiers.",
    'auditor.activity.filed': 'Déposé · {business}',
    'auditor.activity.filed_sub': '{when} · écart de {variance} %',
    'auditor.activity.published': 'Rapport de {month} approuvé',
    'auditor.activity.repayment': 'Remboursement reçu',
    'auditor.activity.missed': 'Remboursement manqué · {business}',
    'auditor.activity.missed_sub': '{when} · votre part est suspendue',
    'auditor.activity.deferred': 'Paiement reporté · {business}',
    'auditor.activity.payout': 'Versement de la part de rendement',
    'auditor.sector.agriculture': 'Agriculture',
    'auditor.sector.logistics': 'Logistique',
    'auditor.sector.manufacturing': 'Industrie',
    'auditor.sector.retail': 'Commerce de détail',
    'auditor.sector.energy': 'Énergie',
    'auditor.sector.technology': 'Technologie',
    'auditor.sector.services': 'Services',
    'auditor.jobs.head_title': 'Missions',
    'auditor.jobs.title': "Missions d'audit",
    'auditor.jobs.lead':
        'Contrôles sur site ouverts dans un rayon de {radius} km. Le premier à accepter verrouille le dossier. Chaque audit flash est dû {hours} heures après son envoi.',
    'auditor.jobs.map_label':
        "Carte de votre rayon d'affectation de {radius} km avec {count} missions ouvertes à des positions approximatives",
    'auditor.jobs.map_badge': 'Rayon {radius} km · {count} ouvertes',
    'auditor.jobs.map_label_page':
        "Carte de votre rayon d'affectation de {radius} km avec {count} missions sur cette page à des positions approximatives",
    'auditor.jobs.map_badge_page': 'Rayon {radius} km · {count} sur cette page',
    'auditor.jobs.assigned': 'Qui vous sont attribuées',
    'auditor.jobs.distance': 'Distance',
    'auditor.jobs.km': '{distance} km',
    'auditor.jobs.sector_unavailable': 'Secteur indisponible',
    'auditor.jobs.kind_monthly': 'Visite mensuelle',
    'auditor.jobs.kind_flash': 'Audit flash',
    'auditor.jobs.show_more': 'Afficher plus',
    'auditor.jobs.conflicts_link': 'Vos conflits déclarés →',
    'auditor.jobs.page_empty':
        'Rien à afficher sur cette page. Des missions plus anciennes peuvent suivre.',
    'auditor.jobs.assigned_empty':
        "Aucune mission acceptée n'est en cours pour le moment.",
    'auditor.jobs.assigned_page_empty':
        'Rien ne vous est attribué sur cette page.',
    'auditor.jobs.requested': 'Demandé',
    'auditor.jobs.dscr': 'DSCR',
    'auditor.jobs.term': 'Durée',
    'auditor.jobs.term_months': '{months} mois',
    'auditor.jobs.view_file': 'Voir le dossier complet →',
    'auditor.jobs.decline': 'Refuser',
    'auditor.jobs.declare_conflict': 'Déclarer un conflit',
    'auditor.jobs.empty':
        "Aucune mission ouverte dans votre zone. Nous vous préviendrons dès qu'un dossier de niveau 2 nécessitera un contrôle sur site dans un rayon de {radius} km.",
    'auditor.monthly.title': 'Rapports mensuels',
    'auditor.monthly.lead':
        "Vous ouvrez chaque mois le dossier d'audit des entreprises que vous suivez. Auditez sur site et scellez avant le 7 — l'entreprise cosigne ensuite.",
    'auditor.monthly.to_open': 'Audits à ouvrir',
    'auditor.monthly.waiting': '{count} en attente',
    'auditor.monthly.not_opened': "{month} · dossier d'audit pas encore ouvert",
    'auditor.monthly.open': "Ouvrir l'audit",
    'auditor.monthly.show_all': 'Afficher les {count} fenêtres ouvertes',
    'auditor.monthly.show_fewer': 'Afficher moins · {shown} sur {count}',
    'auditor.monthly.due_line': '{note} · Échéance {date}',
    'auditor.monthly.status.in_progress': 'En cours',
    'auditor.monthly.status.overdue': 'En retard',
    'auditor.monthly.status.changes_requested': 'Renvoyé',
    'auditor.monthly.status.awaiting_cosign': 'Attente de cosignature',
    'auditor.monthly.inflow': 'Entrées',
    'auditor.monthly.outflow': 'Sorties',
    'auditor.monthly.cover': 'Couverture',
    'auditor.monthly.review': 'Examiner et valider sur site →',
    'auditor.monthly.empty':
        "Aucun rapport mensuel n'attend votre audit pour le moment.",
    'auditor.sheet.close': 'Fermer',
    'auditor.sheet.cancel': 'Annuler',
    'auditor.decline.title': 'Refuser {business}',
    'auditor.decline.lead':
        "La mission retourne à l'affectation. Choisissez un motif ; il est enregistré avec votre refus.",
    'auditor.decline.placeholder':
        "Ce qui vous empêche d'accepter cette mission",
    'auditor.decline.submit': 'Refuser la mission',
    'auditor.conflict.sheet_title': 'Déclarer un intérêt dans {business}',
    'auditor.conflict.body':
        "Si vous avez un intérêt dans une entreprise que vous devez vérifier, dites-le. La déclaration est consignée, et un conflit bloquant arrête aussitôt votre travail sur le dossier pendant que les Opérations d'audit organisent la réattribution. On ne vous demande jamais d'évaluer un dossier que vous avez apporté — Rozine l'interdit.",
    'auditor.conflict.kind_label': "Quel type d'intérêt",
    'auditor.conflict.kind.financial_interest': 'Intérêt financier',
    'auditor.conflict.kind.role_tie':
        'Lien de propriétaire, dirigeant, salarié ou conseil',
    'auditor.conflict.kind.family_or_business':
        "Lien familial ou d'affaires proche",
    'auditor.conflict.kind.other': 'Autre',
    'auditor.conflict.note_label': 'Explication factuelle (obligatoire)',
    'auditor.conflict.note_placeholder':
        "La nature de l'intérêt et depuis quand",
    'auditor.conflict.submit': "Déclarer l'intérêt",
    'auditor.outcome.done': 'Terminé',
    'auditor.outcome.conflict.title': 'Intérêt déclaré',
    'auditor.outcome.conflict.recorded':
        "Votre déclaration concernant {business} est consignée. Elle n'arrête pas votre travail sur cette mission.",
    'auditor.outcome.declined.title': 'Mission refusée',
    'auditor.outcome.declined.body':
        "{business} est retourné à l'affectation et votre motif est consigné.",
    'auditor.outcome.sealed.title': 'Audit scellé et déposé',
    'auditor.outcome.sealed.flash':
        "Le rapport de terrain de {business} est scellé. {business} cosigne d'ici le {date} ; le moteur note l'entreprise à partir de vos constats.",
    'auditor.outcome.sealed.monthly':
        'Le rapport de {month} pour {business} — ses relevés téléversés et vos constats factuels — est scellé. {business} contresigne avant le {date}.',
    'auditor.outcome.suggested.title': 'Modifications demandées',
    'auditor.outcome.suggested.body':
        "L'entreprise a reçu votre motif et votre explication, et peut soumettre à nouveau le dépôt pour votre audit.",
    'auditor.outcome.rejected.title': 'Dépôt rejeté',
    'auditor.outcome.rejected.body':
        "Cette version du dépôt ne peut pas être vérifiée et est close avec votre motif consigné. Ce n'est pas un jugement de crédit, et ses preuves et l'historique du rapport sont conservés.",
    'auditor.sheet.back': 'Retour',
    'auditor.file.head_title': '{business} · dossier',
    'auditor.file.label': "Dossier de l'entreprise {business}",
    'auditor.file.eyebrow_preview': 'Aperçu de la demande',
    'auditor.file.eyebrow_file': "Dossier de l'entreprise",
    'auditor.file.place': '{district} · {distance} km',
    'auditor.file.continue': "Poursuivre l'audit",
    'auditor.file.title': 'Examiner la demande',
    'auditor.file.lead':
        'Tout ce que {business} a soumis, contrôlé selon les seuils de Rozine. Votre contrôle sur site lève ce que le moteur ne peut pas confirmer à distance.',
    'auditor.file.lead_provisional':
        'La demande de {business} telle qu’elle se présente actuellement.',
    'auditor.file.lead_no_prescreen':
        'Aucune présélection automatique n’est enregistrée.',
    'auditor.file.lead_field_check':
        'Votre contrôle sur site confirme ce qui ne peut pas être vérifié à distance.',
    'auditor.file.reassigned_title': 'Ce dossier vous a été réattribué',
    'auditor.file.reassigned_body':
        "Le délai initial s'applique toujours — la réattribution ne relance pas le délai. Les preuves déjà au dossier restent consignées.",
    'auditor.file.raise': 'La levée',
    'auditor.file.term_months': '{months} mois',
    'auditor.file.return': 'Rendement total',
    'auditor.file.return_value': '{pct} % au total',
    'auditor.file.use_of_funds': 'Utilisation des fonds',
    'auditor.file.documents': 'Documents soumis',
    'auditor.file.no_documents':
        "Aucun document soumis n'est encore enregistré pour ce dossier.",
    'auditor.file.doc_status.parsed': '✓ OCR',
    'auditor.file.doc_status.verified': '✓ Vérifié',
    'auditor.file.doc_status.present': '✓ Présent',
    'auditor.file.doc_status.missing': 'Manquant',
    'auditor.file.prescreen': 'Présélection automatique',
    'auditor.file.no_prescreen':
        "Aucun résultat de présélection automatique n'a encore été publié pour ce dossier.",
    'auditor.file.check.met': 'Atteint',
    'auditor.file.check.flag': 'Signalé',
    'auditor.file.why': 'Pourquoi un audit sur site est requis',
    'auditor.file.mandate': 'Ce que vous devez lever sur site',
    'auditor.file.history': 'Historique du dossier',
    'auditor.file.first_visit': 'Première visite',
    'auditor.file.first_visit_body':
        "Aucun audit antérieur n'est au dossier de cette entreprise.",
    'auditor.file.last_audit': 'Dernier audit le {date} · {kind} · {by}',
    'auditor.file.kind.flash': 'Audit flash',
    'auditor.file.kind.monthly': 'Rapport mensuel',
    'auditor.file.flags': 'Signalements au dossier',
    'auditor.file.no_flags': 'Aucun signalement au dossier.',
    'auditor.audit.head_title': '{business} · audit',
    'auditor.audit.label': 'Audit de {business}',
    'auditor.audit.eyebrow_flash': 'Audit flash sur site',
    'auditor.audit.eyebrow_monthly': 'Rapport mensuel · audit',
    'auditor.audit.steps': "Étapes de l'audit",
    'auditor.audit.step.review': 'Examen',
    'auditor.audit.step.check_in': 'Arrivée',
    'auditor.audit.step.photos': 'Photos',
    'auditor.audit.step.ledger': 'Registre',
    'auditor.audit.step.seal': 'Sceau',
    'auditor.audit.step.statements': 'Relevés',
    'auditor.audit.step.count': 'Comptage',
    'auditor.audit.continue': 'Continuer',
    'auditor.audit.to_seal': 'Examiner et sceller',
    'auditor.audit.start_count': 'Commencer le comptage',
    'auditor.audit.to_photos': 'Continuer vers les photos',
    'auditor.audit.back': 'Retour',
    'auditor.audit.back_to_jobs': 'Retour aux missions',
    'auditor.audit.offline':
        "Vous êtes hors ligne. Rien n'est perdu : votre saisie reste sur cette page et l'application de capture garde vos preuves chiffrées et les synchronise à la reconnexion.",
    'auditor.audit.variance': 'Écart',
    'auditor.audit.within_tolerance': 'Dans la tolérance',
    'auditor.audit.outside_tolerance': "L'écart dépasse la tolérance",
    'auditor.capture.title': "Capturé dans l'application de capture Rozine",
    'auditor.capture.body':
        "Les photos et votre arrivée sur site sont prises dans l'application de capture, jamais sur le web. L'heure, la position et le contrôle de l'appareil de chaque élément s'affichent tels que le serveur les enregistre.",
    'auditor.capture.open': "Ouvrir l'application de capture",
    'auditor.capture.status.not_started':
        "L'application de capture n'a pas encore été ouverte pour cette mission",
    'auditor.capture.status.capturing':
        'Capture en cours sur votre téléphone · {received} sur {expected} reçus',
    'auditor.capture.status.syncing':
        'Synchronisation · {received} sur {expected} reçus',
    'auditor.capture.status.complete': 'Les {expected} éléments sont reçus',
    'auditor.capture.attention.no_signal':
        "Pas de réseau — l'application garde tout chiffré et synchronise à la reconnexion",
    'auditor.capture.attention.storage_full':
        "Le stockage de votre téléphone est plein — libérez de l'espace pour continuer la capture",
    'auditor.capture.attention.upload_failed':
        "Échec de l'envoi — ouvrez l'application de capture pour réessayer",
    'auditor.capture.last_sync': 'Dernière synchro {when}',
    'auditor.checkin.title': 'Signaler votre arrivée sur site',
    'auditor.checkin.lead':
        "Confirmez que vous êtes physiquement à l'entreprise. Votre position est enregistrée avec l'audit.",
    'auditor.checkin.waiting': 'En attente de votre arrivée',
    'auditor.checkin.position': '{position} · ±{accuracy} m',
    'auditor.checkin.done': 'Arrivée enregistrée · {time}',
    'auditor.checkin.open': "Signaler l'arrivée avec l'application",
    'auditor.checkin.review_title': 'Cette arrivée sera examinée',
    'auditor.checkin.review_body':
        "La précision de la position ou sa distance aux locaux enregistrés est hors politique. Continuez ; les opérations d'audit l'examinent.",
    'auditor.photos.title': 'Photos du site géolocalisées',
    'auditor.photos.lead':
        "Prises en direct dans l'application — l'import depuis la galerie est désactivé pour éviter la fraude. {captured} sur {required} requises prises.",
    'auditor.photos.extras': {
        one: 'Plus {count} photo supplémentaire.',
        other: 'Plus {count} photos supplémentaires.',
    },
    'auditor.photos.grid': 'Photos du site',
    'auditor.photos.pending': 'Pas encore prise',
    'auditor.photos.captured': 'Prise',
    'auditor.photos.untitled': 'Sans titre',
    'auditor.photos.add': 'Ajouter une photo',
    'auditor.photos.titles': 'Titrez vos photos supplémentaires',
    'auditor.photos.extra': 'Supplément {n}',
    'auditor.photos.left': '{count} restants',
    'auditor.photos.placeholder':
        "Ce qu'elle montre — ex. chambre froide n° 2 pleine",
    'auditor.ledger.title': 'Inventaire et registres',
    'auditor.ledger.lead':
        'Saisissez la valeur du stock que vous avez comptée. Nous la comparons au chiffre déclaré — un écart supérieur à {tolerance} est signalé.',
    'auditor.ledger.reported': 'Stock déclaré',
    'auditor.ledger.observed': 'Constaté sur site · valeur du stock (RWF)',
    'auditor.ledger.observed_placeholder': 'ex. 42 000 000',
    'auditor.ledger.attachments': 'Pièces des registres',
    'auditor.ledger.none': 'Aucune pièce',
    'auditor.ledger.accepted': '{parsed} sur {count} acceptées',
    'auditor.ledger.rules':
        'Téléversez le registre original en PDF ou CSV (10 Mo au plus). Un registre scanné peut être un PDF ; un scan sans texte est signalé pour une vérification manuelle de la source.',
    'auditor.ledger.doc.scanning': 'Vérification … {detail}',
    'auditor.ledger.doc.parsed': 'Acceptée · {detail}',
    'auditor.ledger.doc.failed': 'Refusée · {detail}',
    'auditor.ledger.reading': 'Lecture du document',
    'auditor.ledger.rescan': 'Numériser à nouveau ce document',
    'auditor.ledger.file_input': 'Fichier du registre',
    'auditor.ledger.attach': 'Joindre le registre (PDF ou CSV)',
    'auditor.ledger.attach_another': 'Ajouter un autre registre',
    'auditor.ledger.reconciles':
        'Les registres papier et les reçus concordent avec les relevés numériques.',
    'auditor.ledger.reconciles_blocked':
        "Joignez d'abord au moins un registre accepté.",
    'auditor.statements.title': 'Lire le mois',
    'auditor.statements.lead':
        'Ce que les relevés bancaires, MoMo et TPE au dossier indiquent pour la période. Rien à remplir ici.',
    'auditor.statements.unavailable':
        'Les relevés de cette période ne sont pas encore au dossier',
    'auditor.statements.parsed': 'Extrait des relevés bancaires et MoMo',
    'auditor.statements.inflow': 'Entrées brutes',
    'auditor.statements.outflow': 'Sorties brutes',
    'auditor.statements.net': 'Trésorerie nette',
    'auditor.statements.cover': 'Couverture de liquidité',
    'auditor.statements.view': 'Voir',
    'auditor.count.title': 'Comptage et trésorerie',
    'auditor.count.lead':
        "Cochez les preuves réellement vues, puis saisissez ce que vous avez constaté. Nous comparons chaque chiffre aux relevés au dossier — aucun chiffre ici ne vient de l'entreprise.",
    'auditor.count.financial': 'Preuves financières',
    'auditor.count.vault': 'Coffre de preuves',
    'auditor.count.cash': 'Constaté sur site · espèces (RWF)',
    'auditor.count.cash_hint':
        'Les relevés indiquent {amount} · tolérance {tolerance}',
    'auditor.count.cash_no_statement':
        'Aucun solde de relevé au dossier pour cette période.',
    'auditor.count.period': 'Période du relevé',
    'auditor.count.account': 'Compte / réf. MoMo',
    'auditor.count.inventory': "Preuves d'inventaire",
    'auditor.count.stock': 'Constaté sur site · stock (unités)',
    'auditor.count.units': 'unités',
    'auditor.count.stock_hint': 'Stock déclaré : {units} unités',
    'auditor.count.stock_no_baseline':
        'Aucune base de stock déclarée pour cette période.',
    'auditor.count.stock_tolerance': 'Tolérance de stock : {units} unités',
    'auditor.count.operational': 'Statut opérationnel',
    'auditor.count.status.active': 'Actif',
    'auditor.count.status.suspended': 'Suspendu',
    'auditor.count.status.restricted': 'Restreint',
    'auditor.seal.title': 'Valider et sceller',
    'auditor.seal.lead':
        "Vos constats sont attribués à votre licence ICPAR et publiés aux porteurs après la validation de l'entreprise.",
    'auditor.seal.note': "Note d'évaluation",
    'auditor.seal.note_optional': 'Facultatif',
    'auditor.seal.note_required': 'Obligatoire',
    'auditor.seal.note_done': 'Obligatoire · fait',
    'auditor.seal.note_placeholder': 'Dites ce que vous avez vu et pourquoi',
    'auditor.seal.note_count': '{count} / {max}',
    'auditor.seal.preview': 'Aperçu des constats',
    'auditor.seal.suggest': 'Demander des modifications',
    'auditor.seal.suggest_lead':
        "Le dépôt de {business} retourne à l'entreprise pour correction et nouvelle soumission. Choisissez le motif et exposez les faits.",
    'auditor.seal.suggest_placeholder':
        "ex. la fiche de comptage de septembre n'est pas signée",
    'auditor.seal.suggest_submit': 'Demander des modifications',
    'auditor.seal.reject': 'Rejeter le dépôt',
    'auditor.seal.reject_lead':
        "Cette version du dépôt de {business} ne peut pas être vérifiée. Choisissez le motif et exposez les faits — cela concerne le dépôt, pas le crédit de l'entreprise, et ses preuves et son historique sont conservés.",
    'auditor.seal.reject_placeholder':
        "Ce que vous avez contrôlé et ce qui n'a pas pu être vérifié",
    'auditor.seal.reject_submit': 'Rejeter le dépôt',
    'auditor.seal.findings_eyebrow': 'Constats factuels · {version}',
    'auditor.seal.digest': 'Empreinte en attente',
    'auditor.seal.digest_note':
        "Finalisée lors de l'apposition de votre sceau ICPAR. Le PDF signé est compilé par Rozine et publié aux porteurs après l'accord de l'entreprise.",
    'auditor.seal.apply': 'Confirmer avec votre authentificateur',
    'auditor.seal.submit': 'Sceller et soumettre à Rozine',
    'auditor.sealed.title': 'Scellé et déposé',
    'auditor.sealed.body':
        "Le rapport est scellé et ne peut plus être modifié. {party} cosigne d'ici le {date} ; il est ensuite publié aux porteurs.",
    'auditor.sealed.timeline': 'Avancement du dépôt',
    'auditor.sealed.stage.sealed': 'Scellé par vous',
    'auditor.sealed.stage.cosigned': "Cosignature de l'entreprise",
    'auditor.sealed.stage.published': 'Publié aux porteurs',
    'auditor.sealed.cosign.pending': 'En attente',
    'auditor.sealed.cosign.signed': 'Signé',
    'auditor.sealed.cosign.declined': 'Contesté',
    'auditor.sealed.cosign.overdue': 'En retard',
    'auditor.sealed.seal': 'Empreinte du sceau',
    'auditor.sealed.licence':
        "Scellé sous la licence {licence}. L'original reste consigné ; toute correction est un avenant lié.",
    'auditor.sealed.amend': 'Créer un avenant lié',
    'auditor.portfolio.head_title': 'Portefeuille',
    'auditor.portfolio.title': 'Portefeuille',
    'auditor.portfolio.lead':
        "Vous gagnez de deux façons : la vérification et le suivi des dossiers que vous notez, et la commission d'origination sur les opérations que vous apportez.",
    'auditor.portfolio.cpa_card': 'Votre carte CPA',
    'auditor.portfolio.cpa_card_sub': 'La preuve que vous êtes des nôtres',
    'auditor.portfolio.sourced.title': 'Opérations que vous avez apportées',
    'auditor.portfolio.sourced.tag': 'Origination',
    'auditor.portfolio.sourced.empty':
        "Les opérations que vous apportez rapportent une commission d'origination sur les remboursements. Un autre CPA les vérifie, donc votre commission ne dépend jamais de la note que vous auriez donnée.",
    'auditor.portfolio.earnings.title': 'Vérification gagnée · ce mois-ci',
    'auditor.portfolio.earnings.managed': 'Opérations gérées',
    'auditor.portfolio.earnings.next_payout': 'Prochain versement',
    'auditor.portfolio.yield.title': 'Part du rendement',
    'auditor.portfolio.yield.empty':
        'Les versements de part du rendement apparaissent ici une fois payés.',
    'auditor.portfolio.managed.title': 'Opérations gérées',
    'auditor.portfolio.managed.empty':
        "Aucune opération gérée pour l'instant. Réussissez un audit flash pour devenir gestionnaire de compte d'une entreprise.",
    'auditor.calendar.title': "Calendrier d'audit",
    'auditor.calendar.lead':
        'Chaque vérification mensuelle que vous devez, et quand.',
    'auditor.calendar.previous': 'Mois précédent',
    'auditor.calendar.next': 'Mois suivant',
    'auditor.calendar.legend.soon': 'Dans les 5 jours',
    'auditor.calendar.legend.scheduled': 'Prévue',
    'auditor.calendar.legend.passed': 'Passée',
    'auditor.calendar.day_due_one': '1 vérification à rendre',
    'auditor.calendar.day_due_other': '{count} vérifications à rendre',
    'auditor.calendar.close': 'Fermer',
    'auditor.calendar.soon_one': '1 vérification à rendre dans les 5 jours',
    'auditor.calendar.soon_other':
        '{count} vérifications à rendre dans les 5 jours',
    'auditor.calendar.soon_body_one':
        "Déposez-la avant l'échéance : {business}, le {date}.",
    'auditor.calendar.soon_body_other':
        'Déposez chacune avant son échéance. La première : {business}, le {date}.',
    'auditor.calendar.clear': 'Rien à rendre dans les cinq prochains jours',
    'auditor.calendar.clear_next':
        'Vous êtes à jour. Le prochain dépôt est {business} le {date}.',
    'auditor.calendar.clear_none':
        "Vous êtes à jour. Aucun prochain dépôt n'est encore prévu.",
    'auditor.calendar.due_in': 'À rendre en {month}',
    'auditor.calendar.count_one': '1 opération',
    'auditor.calendar.count_other': '{count} opérations',
    'auditor.calendar.empty': 'Rien à rendre ce mois-ci.',
    'auditor.calendar.partial': "Une partie de vos missions n'apparaît pas ici",
    'auditor.calendar.partial_body':
        'Ce calendrier ne montre que vos missions les plus récentes. Ouvrez Missions pour voir chaque vérification à rendre.',
    'auditor.calendar.partial_empty':
        'Rien à rendre ce mois-ci parmi vos missions les plus récentes.',
    'auditor.calendar.open_jobs': 'Ouvrir Missions',
    'auditor.calendar.share': 'Votre part',
    'auditor.calendar.on_time_by': 'Échéance',
    'auditor.calendar.due_today': "À rendre aujourd'hui",
    'auditor.calendar.in_days_one': 'Dans 1 jour',
    'auditor.calendar.in_days_other': 'Dans {count} jours',
    'auditor.calendar.overdue_one': 'En retard de 1 jour',
    'auditor.calendar.overdue_other': 'En retard de {count} jours',
    'auditor.calendar.monthly': 'Vérification mensuelle',
    'auditor.calendar.filed_on': '{title} · déposé le {date}',
    'auditor.conflict.title': 'Déclarer un intérêt',
    'auditor.conflict.none_assigned':
        'Aucun dossier ne vous est attribué pour vérification en ce moment.',
    'auditor.conflict.options_one': 'Déclarer sur votre dossier attribué',
    'auditor.conflict.options_other':
        "Déclarer sur l'un de vos {count} dossiers attribués",
    'auditor.conflict.on_record': 'Consigné',
    'auditor.conflict.business_on_record': 'Entreprise enregistrée',
    'auditor.conflict.assignment_ref': 'Réf. {reference}',
    'auditor.conflicts.head_title': 'Vos conflits',
    'auditor.conflicts.title': 'Vos conflits déclarés',
    'auditor.conflicts.lead':
        "Chaque conflit que vous avez déclaré, tel qu'il a été enregistré. Chacun a arrêté votre travail sur la mission concernée, et seul votre reçu demeure.",
    'auditor.conflicts.empty': "Vous n'avez déclaré aucun conflit.",
    'auditor.conflicts.page_empty':
        'Rien à afficher sur cette page. Des déclarations plus anciennes peuvent suivre.',
    'auditor.reports.status.awaiting_cosign': 'Attente de cosignature',
    'auditor.reports.status.published': 'Publié',
    'auditor.reports.status.late': 'En retard',
    'auditor.reports.status.rejected': 'Rejeté',
    'auditor.reports.flash': 'Audit flash',
    'auditor.reports.monthly': 'Rapport de {month}',
    'auditor.reports.late_by': '{title} · déposé avec {days} jours de retard',
    'auditor.reports.district': 'District',
    'auditor.reports.amend': 'Créer un avenant lié →',
    'auditor.profile.head_title': 'Profil',
    'auditor.profile.title': 'Profil',
    'auditor.profile.menu': 'Sections du profil',
    'auditor.profile.section.accreditation': 'Accréditation',
    'auditor.profile.section.availability': 'Disponibilité et couverture',
    'auditor.profile.section.earnings': 'Revenus',
    'auditor.profile.section.contact': 'Informations personnelles',
    'auditor.profile.section.payout': 'Compte bancaire de versement',
    'auditor.profile.section.telemetry': "Télémétrie de l'auditeur",
    'auditor.profile.section.security': 'Sécurité et appareils',
    'auditor.profile.section.learn': 'Académie des auditeurs',
    'auditor.profile.section.legal': 'Conditions et mentions légales',
    'auditor.profile.contact.title': 'Informations personnelles',
    'auditor.profile.contact.phone': 'Téléphone',
    'auditor.profile.contact.email': 'E-mail',
    'auditor.profile.contact.address': 'Adresse',
    'auditor.profile.contact.district': "District d'activité",
    'auditor.profile.contact.not_provided': 'Non renseigné',
    'auditor.profile.telemetry.title': "Télémétrie de l'auditeur",
    'auditor.profile.telemetry.clock_expiries': 'Délais expirés',
    'auditor.profile.telemetry.clock_expiries_sub':
        'Audits acceptés arrivés à 0:00',
    'auditor.profile.telemetry.variance': 'Précision des écarts',
    'auditor.profile.telemetry.variance_sub':
        "Écart moyen avec l'analyse interne",
    'auditor.profile.telemetry.on_time': 'Clôture dans les délais',
    'auditor.profile.telemetry.jobs_done': {
        one: '{count} mission terminée',
        other: '{count} missions terminées',
    },
    'auditor.profile.telemetry.strikes': 'Avertissements qualité',
    'auditor.profile.telemetry.strikes_sub': 'Litiges retenus contre vous',
    'auditor.profile.telemetry.defended': 'Litiges défendus',
    'auditor.profile.telemetry.defended_sub':
        'La revue a confirmé votre travail',
    'auditor.profile.telemetry.first_note': 'Défauts à la première note',
    'auditor.profile.telemetry.first_note_sub':
        'Opérations en défaut dès la note 1',
    'auditor.profile.telemetry.note':
        "Rozine suit la performance des partenaires pour protéger le capital des investisseurs. Une mesure affiche un tiret tant qu'elle n'est pas mesurée.",
    'auditor.profile.security.title': 'Sécurité et appareils',
    'auditor.profile.security.two_factor': 'Authentification à deux facteurs',
    'auditor.profile.security.two_factor_on':
        "Activée · code d'une application d'authentification à la connexion",
    'auditor.profile.security.two_factor_off':
        "Désactivée · ajoutez un code d'application d'authentification à la connexion",
    'auditor.profile.security.password': 'Changer le mot de passe',
    'auditor.profile.legal.title': 'Conditions et mentions légales',
    'auditor.profile.legal.engagement': "Conditions d'engagement",
    'auditor.profile.legal.current':
        'Acceptées · le contrat-cadre et les procédures convenues',
    'auditor.profile.legal.required': 'Votre acceptation est requise',
    'auditor.profile.legal.unavailable': 'Lecture impossible pour le moment',
    'auditor.profile.pending.earnings_title': 'Aucun revenu pour le moment',
    'auditor.profile.pending.earnings_body':
        'Votre part des frais de service et vos rendements apparaîtront ici dès leur accumulation.',
    'auditor.profile.pending.payout_title': 'Aucun compte de versement',
    'auditor.profile.pending.payout_body':
        'Le compte bancaire sur lequel vos revenus sont versés apparaîtra ici.',
    'auditor.profile.pending.learn_title': "Bientôt dans l'application",
    'auditor.profile.pending.learn_body':
        "Les leçons de l'Académie des auditeurs apparaîtront ici dès leur publication.",
    'auditor.profile.pending.legal_title': "Bientôt dans l'application",
    'auditor.profile.pending.legal_body':
        "Vos conditions d'engagement pourront être consultées ici.",
    'auditor.profile.on_time': 'À temps',
    'auditor.profile.jobs': 'Missions',
    'auditor.profile.since': 'Depuis',
    'auditor.accreditation.title': 'Accréditation',
    'auditor.accreditation.licence_line':
        'Licence {licence} · expire le {date}',
    'auditor.accreditation.badge.active': '✓ Active',
    'auditor.accreditation.badge.expired': 'Expirée',
    'auditor.accreditation.badge.suspended': 'Suspendue',
    'auditor.accreditation.badge.pending': 'Renouvellement en cours',
    'auditor.accreditation.standing': 'Jours de validité restants',
    'auditor.accreditation.days_left':
        "{count} jours avant l'échéance du renouvellement",
    'auditor.accreditation.expired_ago': 'Expirée depuis {count} jours',
    'auditor.accreditation.pending_title': "Renouvellement en cours d'examen",
    'auditor.accreditation.pending_line':
        "{id} · licence {licence} jusqu'au {date}, soumise le {submitted}. L'équipe Rozine la vérifie auprès du registre ICPAR.",
    'auditor.accreditation.withdraw': 'Retirer',
    'auditor.accreditation.rejected_title': 'Renouvellement refusé',
    'auditor.accreditation.rejected_line':
        '{id} a été refusée — {reason}. Corrigez les informations et soumettez à nouveau.',
    'auditor.accreditation.renew': "Renouveler l'accréditation",
    'auditor.accreditation.licence_label': 'Numéro de licence',
    'auditor.accreditation.licence_placeholder': 'ICPAR/CPA/0000',
    'auditor.accreditation.expiry_label': "Nouvelle date d'expiration",
    'auditor.accreditation.certificate_label': 'Certificat de licence',
    'auditor.accreditation.certificate_drop': 'Déposez le certificat ICPAR',
    'auditor.accreditation.submit': 'Soumettre pour examen',
    'auditor.availability.title': 'Disponibilité et couverture',
    'auditor.availability.accepting_sub':
        "Vous figurez dans le vivier d'affectation et pouvez recevoir des audits flash.",
    'auditor.availability.paused_sub':
        "Aucune nouvelle mission n'est proposée. Les missions acceptées gardent leur délai.",
    'auditor.availability.max_title': 'Missions simultanées',
    'auditor.availability.max_sub':
        "L'affectation cesse de vous proposer du travail au-delà.",
    'auditor.availability.radius_title': 'Couverture',
    'auditor.availability.radius_sub':
        "Mesurée de votre cabinet enregistré aux locaux de l'entreprise.",

    'business.rating.page_title': 'Santé financière',
    'business.rating.page_subtitle':
        'Votre note, votre capacité et vos chiffres vérifiés.',
    'business.rating.title': 'Note Rozine',
    'business.rating.out_of': 'sur 5',
    'business.rating.explainer':
        'Chaque rapport mensuel audité la met à jour. Une meilleure note débloque plus de capacité et de meilleurs taux pour votre prochaine levée.',
    'business.rating.drift': "Noté {audited} à l'audit — désormais {now}",
    'business.rating.refused':
        'Le moteur de notation ne peut pas encore noter cette entreprise',
    'business.rating.factors': 'Détail de la note',
    'business.rating.factor.financial_health': 'Santé financière',
    'business.rating.factor.repayment_history': 'Historique de remboursement',
    'business.rating.factor.statement_consistency': 'Cohérence des relevés',
    'business.rating.factor.growth_outlook': 'Croissance et perspectives',
    'business.rating.sizing.title': 'Comment votre capacité est calculée',
    'business.rating.sizing.cash': 'Trésorerie par mois',
    'business.rating.sizing.cash_note':
        "marge nette de {margin} % + {depreciation} % d'amortissement réintégré",
    'business.rating.sizing.multiplier': '× Votre multiplicateur',
    'business.rating.sizing.tier.none':
        "Aucune couverture de stock vérifiée pour l'instant",
    'business.rating.sizing.tier.cover1x':
        'Votre expert-comptable a vérifié un stock couvrant au moins 1× la levée',
    'business.rating.sizing.tier.cover2x':
        'Votre expert-comptable a vérifié un stock couvrant au moins 2× la levée',
    'business.rating.sizing.carry': 'Mensualité que vous pouvez assumer',
    'business.rating.sizing.explainer':
        'Votre capacité correspond à la trésorerie que votre entreprise génère réellement chaque mois, multipliée par ce que permet votre couverture de stock vérifiée, puis plafonnée par la plus basse des cinq limites ci-dessus. Personne chez Rozine ne fixe ce chiffre à la main. Les demandes au-delà de la capacité sont refusées automatiquement.',
    'business.rating.stock.title': 'Stock enregistré',
    'business.rating.stock.verified':
        '{value} compté sur site par votre expert-comptable',
    'business.rating.stock.indicative':
        "{value} est habituel pour votre secteur — mais seul un comptage par l'expert-comptable donne un multiplicateur plus élevé",
    'business.rating.stock.none': 'Aucune position de stock enregistrée',
    'business.rating.stock.why':
        'Un prêt qui achète du stock est remboursé par la vente de ce stock : une couverture vérifiée augmente ce que vous pouvez assumer.',
    'business.rating.limits': "Cinq limites · la plus basse s'applique",
    'business.rating.limit.capacity': 'Capacité de trésorerie',
    'business.rating.limit.capacity_detail':
        'EBITDA {ebitda}/mois × M {multiplier}',
    'business.rating.limit.capacity_lift':
        "Augmentez l'EBITDA ou faites vérifier votre stock par l'expert-comptable",
    'business.rating.limit.revenue_share': "Part du chiffre d'affaires",
    'business.rating.limit.revenue_share_detail':
        "{percent} % de {revenue} de chiffre d'affaires annuel audité",
    'business.rating.limit.revenue_share_lift':
        "Augmentez le chiffre d'affaires annuel audité",
    'business.rating.limit.book_share': 'Part du portefeuille',
    'business.rating.limit.book_share_detail':
        "{percent} % des {book} d'encours",
    'business.rating.limit.book_share_lift':
        "Augmente d'elle-même avec le portefeuille de la plateforme",
    'business.rating.limit.phase_cap': 'Plafond de la phase {phase}',
    'business.rating.limit.phase_cap_detail':
        "Plafond de la plateforme tant que l'encours est inférieur à {book}",
    'business.rating.limit.phase_cap_lift': 'Augmente à la phase suivante',
    'business.rating.limit.policy_max': 'Maximum réglementaire',
    'business.rating.limit.policy_max_detail':
        'Plafond absolu pour toute levée',
    'business.rating.limit.policy_max_lift': 'Aucun — ce plafond est absolu',
    'business.rating.limit.yours': "C'est votre limite",
    'business.rating.approved': 'Capacité approuvée',
    'business.rating.bound': 'Ce qui vous limite : {limit}',
    'business.rating.lift': "Pour l'augmenter — {how}",
    'business.rating.headroom': 'Marge disponible',
    'business.rating.headroom_note':
        'Vous pouvez encore lever ce montant maintenant.',
    'business.rating.raise': 'Lever',
    'business.rating.tiers.title':
        'Ce que permettrait un multiplicateur plus élevé',
    'business.rating.tiers.applied': 'Appliqué',
    'business.rating.tiers.not_yet': 'Pas encore',
    'business.rating.tiers.no_cover':
        'Aucune couverture de stock requise à ce niveau',
    'business.rating.tiers.needs':
        'Nécessite {need}× de couverture · votre stock donne {yours}×',
    'business.rating.financials': 'Chiffres vérifiés',
    'business.rating.sources': 'Banque · MoMo',
    'business.rating.financial.revenue': 'CA mensuel moyen',
    'business.rating.financial.ebitda': 'EBITDA / mois',
    'business.rating.financial.margin': 'Marge nette',
    'business.rating.financial.outstanding': 'Encours',

    'business.repayments.title': 'Remboursements',
    'business.repayments.progress': 'Avancement des remboursements',
    'business.repayments.payments': '{made} / {total} paiements',
    'business.repayments.remaining': 'Restant',
    'business.repayments.total': 'Total',
    'business.repayments.schedule': 'Échéancier',
    'business.repayments.late.title': 'En cas de retard de paiement',
    'business.repayments.none.title': 'Rien à rembourser pour le moment',
    'business.repayments.none.body':
        "Les remboursements commencent une fois qu'une levée est financée et que les fonds vous sont versés. Son échéancier, les montants dus et chaque paiement apparaîtront ici.",
    'business.servicing.repay.state.current': 'À jour',
    'business.servicing.repay.state.due_today': "Dû aujourd'hui",
    'business.servicing.repay.state.overdue': 'En retard',
    'business.servicing.repay.state.repaid': 'Remboursé',
    'business.servicing.repay.state.defaulted': 'En défaut',
    'business.servicing.repay.instalments_left': {
        one: '{count} échéance restante',
        other: '{count} échéances restantes',
    },
    'business.servicing.repay.repaid':
        'Ce titre est entièrement remboursé. Merci.',
    'business.servicing.repay.due_title': 'À payer maintenant',
    'business.servicing.repay.due_today': "Dû aujourd'hui",
    'business.servicing.repay.dpd': {
        one: '{count} jour de retard',
        other: '{count} jours de retard',
    },
    'business.servicing.repay.next_title': 'Prochaine échéance · n° {index}',
    'business.servicing.repay.due_on': 'Échéance le {date}',
    'business.servicing.repay.next_late_fee':
        "Si le montant n'est toujours pas payé le {date}, des frais de retard d'environ {amount} s'ajoutent. Il s'agit d'une projection.",
    'business.servicing.repay.autocollect':
        'Nous le prélèverons sur votre portefeuille Rozine le {date}.',
    'business.servicing.repay.autocollect_off':
        'Le prélèvement automatique sur votre portefeuille est désactivé.',
    'business.servicing.repay.restriction.ARREARS':
        "En arriéré depuis le {date}. Vos titres ne peuvent pas être échangés tant qu'il n'est pas payé.",
    'business.servicing.repay.restriction.RESTRICTION_ACTIVE':
        'Restreint depuis le {date}. Vous pouvez toujours rembourser.',
    'business.servicing.repay.part.principal': 'Capital',
    'business.servicing.repay.part.return': 'Rendement',
    'business.servicing.repay.part.service_fee': 'Frais de service',
    'business.servicing.repay.part.late_fees': 'Frais de retard',
    'business.servicing.repay.part.total': 'Total',
    'business.servicing.repay.pay_title': 'Payer depuis votre portefeuille',
    'business.servicing.repay.option.due_now': 'Le montant dû maintenant',
    'business.servicing.repay.option.next_instalment':
        'Prochaine échéance en avance',
    'business.servicing.repay.option_note.due_now':
        "Règle l'échéance {list}, avec les éventuels frais de retard.",
    'business.servicing.repay.option_note.next_instalment':
        "Règle l'échéance {list} en avance, au montant prévu. Aucune remise pour un paiement anticipé.",
    'business.servicing.repay.wallet_has':
        'Votre portefeuille dispose de {amount}.',
    'business.servicing.repay.pay_cta': 'Payer {amount}',
    'business.servicing.repay.short':
        'Votre portefeuille ne suffit pas pour ce paiement.',
    'business.servicing.repay.top_up': 'Alimenter votre portefeuille',
    'business.servicing.repay.receipt_title': 'Paiement enregistré',
    'business.servicing.repay.allocating':
        'En cours de répartition entre vos investisseurs',
    'business.servicing.repay.allocated': {
        one: 'Réparti entre {count} investisseur',
        other: 'Réparti entre {count} investisseurs',
    },
    'business.servicing.repay.unapplied':
        "{amount} n'était pas nécessaire et reste dans votre portefeuille.",
    'business.servicing.repay.receipt_done': 'Terminé',
    'business.servicing.repay.recent': 'Remboursements récents',
    'business.servicing.repay.row.due': 'n° {index} · échéance le {date}',
    'business.servicing.repay.row.paid': 'n° {index} · payé le {date}',
    'business.servicing.repay.status.paid': 'Payé',
    'business.servicing.repay.status.processing': 'En traitement',
    'business.servicing.repay.status.due': 'Dû',
    'business.servicing.repay.status.partially_paid': 'Payé en partie',
    'business.servicing.repay.status.overdue': 'En retard',
    'business.servicing.repay.status.upcoming': 'À venir',
    'business.servicing.repay.late_lines': 'Frais de retard sur cette échéance',
    'business.servicing.repay.late_line': '{step} · {date} · {status}',
    'business.servicing.repay.step.due_date': 'Échéance manquée',
    'business.servicing.repay.step.day_7': 'Impayé au 7e jour',
    'business.servicing.repay.step.day_30': 'Impayé au 30e jour',
    'business.servicing.repay.fee_status.projected': 'projeté',
    'business.servicing.repay.fee_status.assessed': 'appliqué',
    'business.servicing.repay.fee_status.partially_collected': 'payé en partie',
    'business.servicing.repay.fee_status.collected': 'payé',
    'business.servicing.repay.fee_status.waived': 'annulé',
    'business.servicing.repay.step_rate': '+{percent} %',
    'business.servicing.repay.ladder_unavailable':
        "La politique de frais de retard n'est pas disponible pour le moment ; aucun frais n'est affiché.",
    'business.servicing.repay.ladder_version':
        'Politique de frais de retard {version}.',
    'business.audit_prep.title': 'Préparez votre audit',
    'business.audit_prep.window_open': "Fenêtre d'audit ouverte",
    'business.audit_prep.next': 'Prochain audit',
    'business.audit_prep.first': 'Votre premier audit',
    'business.audit_prep.month': 'Audit de {month}',
    'business.audit_prep.day_left': 'Jour restant',
    'business.audit_prep.days_left': 'Jours restants',
    'business.audit_prep.intro':
        'Notification reçue : veuillez préparer tous vos relevés bancaires, historiques Mobile Money et carnets de reçus papier pour la prochaine visite de votre expert-comptable.',
    'business.audit_prep.reassigned':
        'Votre dossier est passé de {from} à {to}, avec tout votre historique.',
    'business.audit_prep.ready': 'Préparez ceci',
    'business.audit_prep.item.statements.title':
        'Relevés bancaires et historiques Mobile Money',
    'business.audit_prep.item.statements.body':
        "Le mois complet, jusqu'au dernier jour, prêt à être examiné sur place par votre expert-comptable.",
    'business.audit_prep.item.stock.title': 'Stock compté et registres à jour',
    'business.audit_prep.item.stock.body':
        "Votre auditeur fait un comptage physique. Un registre pas à jour apparaît comme un écart et réduit votre capacité d'emprunt.",
    'business.audit_prep.item.access.title':
        'Accès à chaque entrepôt, magasin et caisse',
    'business.audit_prep.item.access.body':
        'Tout ce qui est fermé ou inaccessible le jour J est compté comme manquant.',
    'business.audit_prep.item.papers.title':
        'Tickets de caisse et transactions rapprochés',
    'business.audit_prep.item.papers.body':
        "Assurez-vous que tous les tickets de caisse papier et toutes les transactions numériques sont rapprochés pour l'examen sur place de l'expert-comptable.",
    'business.audit_prep.item.person.title': 'Une personne habilitée sur place',
    'business.audit_prep.item.person.body':
        'Il faut une personne qui peut ouvrir les portes et répondre des chiffres — pas seulement le personnel de service.',
    'business.audit_prep.how': "Déroulement de l'audit",
    'business.audit_prep.flow.closes.title': 'Clôture du mois',
    'business.audit_prep.flow.closes.body':
        "Votre expert-comptable ouvre le dossier d'audit de la période. Rien n'est requis de votre part pour le lancer.",
    'business.audit_prep.flow.visit.title': 'Visite sur site',
    'business.audit_prep.flow.visit.body':
        "L'expert-comptable qui vous est attribué se rendra dans vos locaux pour examiner vos documents, rapprocher vos flux de trésorerie et établir le rapport d'audit mensuel.",
    'business.audit_prep.flow.sealed.title': 'Scellé',
    'business.audit_prep.flow.sealed.body':
        'Les constats factuels et les écarts sont scellés sous sa licence ICPAR.',
    'business.audit_prep.flow.cosign.title': 'Vous cosignez',
    'business.audit_prep.flow.cosign.body':
        'Vous ajoutez un résumé et cosignez avant le {date}, ou vous contestez avec une contre-preuve.',
    'business.audit_prep.closing':
        "Vous ne déposez jamais le rapport mensuel vous-même. Votre expert-comptable se déplace, examine vos documents sur place et scelle le rapport — votre rôle est d'être prêt, puis de cosigner ou contester ses constats.",

    'investor.nav.deals': 'Offres',
    'investor.nav.portfolio': 'Portefeuille',
    'investor.nav.profile': 'Profil',
    'investor.market.order_detail.title': "Détail de l'ordre",
    'investor.market.order_detail.empty_title': 'Aucun ordre sélectionné',
    'investor.market.order_detail.empty_body':
        'Choisissez un ordre à gauche pour voir pourquoi il a été exécuté ou non.',
    'investor.nav.market': 'Marché',
    'investor.nav.cart': 'Panier',
    'investor.market.title': 'Marché',
    'investor.market.subtitle':
        'Échangez des Rozine Notes actives avant leur échéance.',
    'investor.market.view.browse': 'Parcourir',
    'investor.market.view.orders': 'Ordres',
    'investor.market.view.saved': 'Enregistrées',
    'investor.market.filter.performance': 'Performance',
    'investor.market.filter.status': 'Statut',
    'investor.market.filter.industry': 'Secteur',
    'investor.market.performance.all': 'Toutes',
    'investor.market.performance.most_active': 'Les plus actives',
    'investor.market.performance.highest_yield': 'Rendement le plus élevé',
    'investor.market.performance.lowest_risk': 'Risque le plus faible',
    'investor.market.performance.most_liquid': 'Les plus liquides',
    'investor.market.status.all': 'Tous',
    'investor.market.status.active': 'Actives',
    'investor.market.status.sold': 'Vendues',
    'investor.market.status.complete': 'Terminées',
    'investor.market.industry.all': 'Tous',
    'investor.market.industry.agriculture': 'Agriculture',
    'investor.market.industry.manufacturing': 'Industrie',
    'investor.market.industry.energy': 'Énergie',
    'investor.market.industry.technology': 'Technologie',
    'investor.market.industry.logistics': 'Logistique',
    'investor.market.industry.finance': 'Finance',
    'investor.market.orders.label': 'Ordres',
    'investor.market.orders.buy': "Ordres d'achat",
    'investor.market.orders.sell': 'Ordres de vente',
    'investor.market.orders.completed': 'Exécutés',
    'investor.market.orders.cancelled': 'Annulés',
    'investor.market.orders.empty': 'Aucun ordre dans cet onglet.',
    'investor.market.browse.empty_title':
        'Aucune note ne correspond à ces filtres',
    'investor.market.browse.empty_body':
        'Essayez de modifier les filtres ci-dessus.',
    'investor.market.saved.empty_title':
        'Aucune note enregistrée pour le moment',
    'investor.market.saved.empty_body':
        "Faites glisser une note vers le haut pour l'enregistrer ici.",
    'investor.cart.title': 'Panier',
    'investor.cart.empty_title': 'Votre panier est vide',
    'investor.cart.empty_body':
        'Ajoutez des notes depuis les Offres, choisissez le montant de chacune et payez-les toutes en une fois.',
    'investor.cart.browse_deals': 'Parcourir les offres',
    'investor.common.back': 'Retour',
    'investor.money.rwf': 'RWF',
    'investor.rating.strong': 'Solide',
    'investor.rating.stable': 'Stable',
    'investor.rating.weak': 'Faible',
    'investor.rating.distressed': 'En difficulté',
    'investor.deals.head_title': 'Offres',
    'investor.deals.wallet_balance': 'Solde du portefeuille',
    'investor.deals.deposit': 'Déposer',
    'investor.deals.withdraw': 'Retirer',
    'investor.deals.notifications': 'Notifications',
    'investor.deals.notifications_unread': 'Notifications, {count} non lues',
    'investor.deals.sort_label': 'Trier les offres',
    'investor.deals.sort.all': 'Toutes',
    'investor.deals.sort.top_interest': 'Meilleur rendement',
    'investor.deals.sort.top_rated': 'Mieux notées',
    'investor.deals.sort.top_picks': 'Sélection',
    'investor.deals.industry_label': 'Filtrer par secteur',
    'investor.deals.industry_all': 'Tous',
    'investor.deals.audited': 'AUDITÉ',
    'investor.deals.just_listed': 'NOUVEAU',
    'investor.deals.photo_previous': 'Photo précédente de {name}',
    'investor.deals.photo_next': 'Photo suivante de {name}',
    'investor.deals.open_deal': 'Ouvrir {name}',
    'investor.deals.raised': 'LEVÉ',
    'investor.deals.avg_revenue_short': 'REV. MOY. MENS.',
    'investor.deals.avg_revenue': 'REVENU MENSUEL MOYEN',
    'investor.deals.funded_label': 'Financement de {name}',
    'investor.deals.investors': 'investisseurs',
    'investor.deals.left_to_fill': 'RESTE À LEVER',
    'investor.deals.notes': 'TITRES',
    'investor.deals.taken': 'pris',
    'investor.deals.fully_funded': 'Entièrement financé',
    'investor.deals.days_left': '{count} jours',
    'investor.deals.closing': 'Clôture',
    'investor.deals.deal_previous': 'Offre précédente',
    'investor.deals.deal_next': 'Offre suivante',
    'investor.deals.empty_title': "Aucune offre ouverte pour l'instant",
    'investor.deals.empty_body':
        'Les nouvelles levées apparaissent ici une fois auditées et publiées.',
    'investor.deals.all_closed':
        "Toutes les levées ouvertes sont entièrement réservées ou clôturées pour l'instant. Les nouvelles levées apparaissent ici dès leur ouverture.",
    'investor.deals.invest_bar': 'Investir dans {name}',
    'investor.deals.notes_quantity': 'Nombre de titres',
    'investor.deals.notes_suffix': 'TITRES',
    'investor.deals.of_left': 'sur {count} restants',
    'investor.deals.term': 'DURÉE',
    'investor.deals.months_short': '{count} mois',
    'investor.deals.bar_invest': 'INVESTIR',
    'investor.deals.bar_interest': 'INTÉRÊT',
    'investor.deals.bar_get_back': 'À RECEVOIR',
    'investor.deals.invest': 'Investir',
    'investor.deals.verify_to_invest': 'Vérifiez pour investir',
    'investor.deals.gate.verification_required':
        'Vérifiez votre identité pour investir. La consultation reste ouverte à tous.',
    'investor.deals.gate.verification_pending':
        'Nous vérifions votre identité. Vous pourrez investir une fois confirmée.',
    'investor.deals.gate.restricted':
        "L'investissement est suspendu sur ce compte. Contactez le support.",
    'investor.deal.status.open': 'EN COURS',
    'investor.deal.status.sold_out': 'COMPLET',
    'investor.deal.status.frozen': 'SUSPENDU',
    'investor.deal.status.withdrawn': 'RETIRÉ',
    'investor.deal.notice.sold_out.title':
        'Cette levée est entièrement financée',
    'investor.deal.notice.sold_out.body':
        "Tous les titres ont été pris ; il n'y a plus rien à acheter ici.",
    'investor.deal.notice.frozen.title': 'Cette levée est suspendue',
    'investor.deal.notice.frozen.body':
        "Rozine a suspendu les nouveaux investissements le temps d'un examen. Vos avoirs ne sont pas affectés.",
    'investor.deal.notice.withdrawn.title': 'Cette levée a été retirée',
    'investor.deal.notice.withdrawn.body':
        "L'entreprise l'a retirée avant le financement. Tout montant engagé est remboursé intégralement, sans frais.",
    'investor.deal.funding_progress': 'Progression du financement',
    'investor.deal.loading': "Chargement des détails de l'opportunité",
    'investor.deal.raised': 'LEVÉ',
    'investor.deal.target': 'OBJECTIF',
    'investor.deal.time_left': 'TEMPS RESTANT',
    'investor.deal.notes_sold_of': '{sold} sur {total} titres vendus',
    'investor.deal.notes_of': '{sold} sur {total} titres',
    'investor.deal.details': 'DÉTAILS',
    'investor.deal.photos': 'Photos',
    'investor.deal.reported_by_business': "Déclaré par l'entreprise",
    'investor.deal.use_of_funds': 'Utilisation des fonds',
    'investor.deal.use.inventory': 'Stock',
    'investor.deal.use.equipment': 'Équipement',
    'investor.deal.use.expansion': 'Expansion',
    'investor.deal.use.hiring': 'Recrutement',
    'investor.deal.use.working_capital': 'Fonds de roulement',
    'investor.deal.use.other': 'Autre',
    'investor.deal.financials': 'Données financières',
    'investor.deal.verified_audited': 'Vérifié · audité',
    'investor.deal.avg_monthly_revenue': 'REVENU MENSUEL MOYEN',
    'investor.deal.ebitda': 'EBITDA',
    'investor.deal.existing_debt': 'DETTE EXISTANTE',
    'investor.deal.capacity': 'CAPACITÉ ÉVALUÉE',
    'investor.deal.rozine_tag': 'Rozine',
    'investor.deal.assessment': 'Évaluation Rozine',
    'investor.deal.rozine_analysis': 'Analyse Rozine',
    'investor.deal.assessed_by_rozine': 'Évalué par Rozine',
    'investor.deal.rozine_rating': 'Note Rozine',
    'investor.deal.out_of_five': '{score} / 5',
    'investor.deal.total_return': 'Rendement total',
    'investor.deal.repayment': 'Remboursement',
    'investor.deal.monthly': 'Mensuel',
    'investor.deal.term': 'DURÉE',
    'investor.deal.months_long': '{count} mois',
    'investor.deal.track_record': 'Historique sur Rozine',
    'investor.deal.history': 'HISTORIQUE',
    'investor.deal.raises': 'LEVÉES',
    'investor.deal.raises_done': 'LEVÉES RÉALISÉES',
    'investor.deal.on_time': 'À TEMPS',
    'investor.deal.repaid': 'REMBOURSÉ',
    'investor.deal.verified': 'Vérifié',
    'investor.deal.about': "À propos de l'entreprise",
    'investor.deal.registration': "Code d'entreprise RDB",
    'investor.deal.registered': 'Enregistrée',
    'investor.deal.registered_value': '{year} · {years} ans',
    'investor.deal.team_size': 'Effectif',
    'investor.deal.industry': 'Secteur',
    'investor.deal.address': 'Adresse',
    'investor.deal.monthly_updates': 'Rapports mensuels',
    'investor.deal.audited': 'Audité',
    'investor.deal.rating': 'NOTE',
    'investor.deal.interest': 'INTÉRÊT',
    'investor.deal.return': 'RENDEMENT',
    'investor.deal.verified_pill': '✓ Vérifié',
    'investor.deal.overdue_title':
        'Rapport de {month} en retard · sous surveillance',
    'investor.deal.overdue_body':
        "Le rapport vérifié n'a pas été audité avant le 7. L'équipe conformité de Rozine assure le suivi avec l'auditeur de l'entreprise.",
    'investor.deal.your_investment': 'VOTRE INVESTISSEMENT',
    'investor.deal.repaid_monthly': 'Remboursé chaque mois · {count} mois',
    'investor.deal.unit_times_one': '{price} × {count} titre',
    'investor.deal.unit_times_other': '{price} × {count} titres',
    'investor.deal.expected_return': 'Rendement attendu',
    'investor.deal.total_at_maturity': "Total remboursé à l'échéance",
    'investor.deal.fewer_notes': 'Un titre de moins',
    'investor.deal.more_notes': 'Un titre de plus',
    'investor.checkout.title': 'Paiement',
    'investor.checkout.done': 'Terminé',
    'investor.checkout.deal_line': '{rate} % de rendement · {count} mois',
    'investor.checkout.closes_in': 'CLÔTURE DANS',
    'investor.checkout.amount': 'MONTANT INVESTI',
    'investor.checkout.units_each_one': "{count} titre · {price} l'unité",
    'investor.checkout.units_each_other': "{count} titres · {price} l'unité",
    'investor.checkout.expected_return': 'Rendement attendu ({rate} %)',
    'investor.plus.fee': 'Frais sur les gains',
    'investor.plus.fee_label': 'Frais sur les gains · {rate}',
    'investor.plus.fee_rate': '{rate} % ({tier})',
    'investor.plus.fee_note':
        'Prélevés uniquement sur votre rendement, jamais sur votre capital. Ce taux est fixé pour ce titre.',
    'investor.plus.tier.standard': 'Standard',
    'investor.plus.tier.bronze': 'Bronze',
    'investor.plus.tier.silver': 'Argent',
    'investor.plus.tier.gold': 'Or',
    'investor.plus.tier.platinum': 'Platine',
    'investor.plus.tier.diamond': 'Diamant',
    'investor.checkout.maturity_value': "Valeur à l'échéance · {date}",
    'investor.checkout.pay_with': 'PAYER AVEC',
    'investor.checkout.wallet': 'Portefeuille',
    'investor.checkout.wallet_detail':
        'Portefeuille Rozine · {amount} disponibles',
    'investor.checkout.insufficient':
        'Le montant dépasse votre solde. Déposez sur votre portefeuille ou réduisez le montant.',
    'investor.checkout.deposit': 'Déposer',
    'investor.checkout.disclosure': 'COÛTS ET RISQUES',
    'investor.checkout.acknowledge':
        "J'ai lu l'information sur les coûts et les risques ({version}).",
    'investor.checkout.refusal.TRADE_BELOW_MINIMUM':
        'Investissez au moins un titre.',
    'investor.checkout.refusal.INSUFFICIENT_AVAILABLE_FUNDS':
        'Le montant dépasse votre solde. Déposez sur votre portefeuille ou réduisez le montant.',
    'investor.checkout.refusal.EXPOSURE_LIMIT':
        "Cela dépasse votre plafond d'investissement. Réduisez le montant.",
    'investor.checkout.refusal.NOTE_INELIGIBLE':
        'Cette levée ne peut plus être achetée.',
    'investor.checkout.refusal.RESERVATION_EXPIRED':
        'Vos titres réservés ont été libérés. Vérifiez le montant et confirmez à nouveau.',
    'investor.checkout.refusal.RESTRICTION_ACTIVE':
        "L'investissement est suspendu sur ce compte. Contactez le support.",
    'investor.checkout.refusal.VERSION_CONFLICT':
        'Les conditions ont changé pendant votre lecture. Relisez-les et confirmez à nouveau.',
    'investor.checkout.processing': 'Confirmation…',
    'investor.checkout.confirm': 'Confirmer · {amount}',
    'investor.checkout.fine_print':
        'Fonds engagés immédiatement · Rendements projetés, non garantis',
    'investor.checkout.confirmed': 'Investissement confirmé',
    'investor.checkout.confirmed_body_before': 'Vous avez investi',
    'investor.checkout.confirmed_body_after':
        "dans {name}. C'est désormais dans votre portefeuille.",
    'investor.checkout.expected_return_plain': 'Rendement attendu',
    'investor.checkout.maturity_value_plain': "Valeur à l'échéance",
    'investor.checkout.maturity_date': "Date d'échéance",
    'investor.checkout.transaction_id': 'N° de transaction',
    'investor.checkout.reference': 'Référence',
    'investor.checkout.view_receipt': 'Voir le reçu',
    'investor.checkout.view_portfolio': 'Voir dans le portefeuille',
    'investor.checkout.explore': "Voir d'autres offres",
    'investor.updates.status.healthy': 'Sain',
    'investor.updates.status.watch': 'Sous surveillance',
    'investor.updates.net': 'net',
    'investor.updates.none': "Aucun rapport mensuel publié pour l'instant.",
    'investor.updates.parsed_audited': 'Relevé analysé · audité',
    'investor.updates.show_more': 'Voir plus',
    'investor.updates.show_less': 'Voir moins',
    'investor.updates.sheet_label': 'Rapport mensuel de {month}',
    'investor.updates.verified_report': 'Rapport mensuel vérifié',
    'investor.updates.audited_by': 'Audité sur place par {name}',
    'investor.updates.licence_verified': '{licence} · vérifié le {date}',
    'investor.updates.financials': 'FINANCES DU MOIS',
    'investor.updates.inflow': 'ENTRÉES',
    'investor.updates.outflow': 'SORTIES',
    'investor.updates.verified': 'VÉRIFIÉ',
    'investor.updates.from_statements': "d'après les relevés bancaires et MoMo",
    'investor.updates.net_month': 'Net du mois',
    'investor.updates.from_business': "DE L'ENTREPRISE",
    'investor.updates.auditor_note': "NOTE DE L'AUDITEUR",
    'investor.updates.proof_photos': 'PHOTOS PROBANTES',
    'investor.updates.open_photo': 'Ouvrir la photo : {caption}',
    'investor.updates.required_shot': 'Photo requise',
    'investor.updates.added_by_auditor': "Ajoutée par l'auditeur",
    'investor.updates.gps': 'COORDONNÉES GPS',
    'investor.updates.captured': 'PRISE LE',
    'investor.updates.camera_only':
        'Prise en direct avec la caméra auditeur Rozine · import depuis la galerie désactivé',
    'investor.updates.photo_previous': 'Photo précédente',
    'investor.updates.photo_next': 'Photo suivante',
    'investor.audit.kicker': 'Audité en toute indépendance · {standard}',
    'investor.audit.kicker_plain': 'Audité en toute indépendance',
    'investor.audit.verified_line': '{licence} · vérifié le {date}',
    'investor.audit.view': 'Voir',
    'investor.audit.hide': 'Masquer',
    'investor.audit.standard': 'Référentiel',
    'investor.audit.partner': 'Auditeur',
    'investor.audit.licence': 'Inscription ICPAR',
    'investor.audit.cash': 'Espèces / MoMo constatés',
    'investor.audit.inventory': "Comptage d'échantillon de stock",
    'investor.audit.units': '{count} unités',
    'investor.audit.variance': 'Écart vs télémétrie',
    'investor.audit.digest': 'Empreinte du rapport',
    'investor.audit.photos': "PHOTOS D'INSPECTION GÉOLOCALISÉES",
    'investor.audit.download': "Télécharger le rapport d'audit signé (PDF)",
    'investor.audit.disclaimer':
        "Les constats sont des observations factuelles selon des procédures convenues — pas une opinion d'audit. L'empreinte lie la licence du CPA, le GPS et l'horodatage à ce rapport.",
    'investor.auth.tagline':
        'Du capital de croissance pour des entreprises rentables',
    'investor.auth.have_account': 'Vous avez déjà un compte ?',
    'investor.auth.log_in': 'Se connecter',
    'investor.auth.new_to_rozine': 'Nouveau sur Rozine ?',
    'investor.auth.create_account': 'Créer un compte',
    'investor.auth.new_here': 'Nouveau ici ?',
    'investor.auth.create_an_account': 'Créer un compte',
    'investor.auth.create_title': 'Créez votre compte',
    'investor.auth.continue': 'Continuer',
    'investor.auth.please_wait': 'Veuillez patienter…',
    'investor.auth.email': 'E-MAIL',
    'investor.auth.email_placeholder': 'vous@exemple.rw',
    'investor.auth.first_name': 'PRÉNOM',
    'investor.auth.last_name': 'NOM',
    'investor.auth.first_placeholder': 'Robert',
    'investor.auth.last_placeholder': 'Mugisha',
    'investor.auth.entity_name': "NOM DE L'ENTITÉ",
    'investor.auth.entity_placeholder': 'Horizon Capital Partners',
    'investor.auth.representative': 'REPRÉSENTANT',
    'investor.auth.representative_placeholder': 'Aline Uwase',
    'investor.auth.password_placeholder': '6 caractères ou plus',
    'investor.auth.brand_headline':
        "Gagnez jusqu'à {max} % en soutenant des entreprises rwandaises rentables.",
    'investor.auth.brand_point_1':
        'Chaque entreprise auditée par un CPA agréé ICPAR',
    'investor.auth.brand_point_2':
        'Un taux fixe de {min} à {max} % par durée, remboursé chaque mois',
    'investor.auth.brand_point_3':
        'Revendez à Rozine, ou sur le marché, à tout moment',
    'investor.auth.type.individual': 'Particulier',
    'investor.auth.type.institution': 'Institution',
    'investor.auth.role.individual': 'Je suis un particulier',
    'investor.auth.role.institution': 'Je suis une institution',
    'investor.auth.role.individual_body':
        'Investir en votre nom, à partir de {price} le titre.',
    'investor.auth.role.institution_body':
        "Un fonds, une SACCO, un assureur ou une trésorerie d'entreprise investissant ses propres fonds.",
    'investor.auth.register_head_title': 'Commencer',
    'investor.auth.login.head_title': 'Connexion',
    'investor.auth.login.title': 'Bon retour',
    'investor.auth.login.subtitle':
        'Connectez-vous pour gérer votre portefeuille.',
    'investor.auth.login.method': 'Méthode de connexion',
    'investor.auth.login.tab_password': 'Mot de passe',
    'investor.auth.login.tab_pin': 'PIN',
    'investor.auth.login.id': 'E-MAIL OU TÉLÉPHONE',
    'investor.auth.login.password': 'MOT DE PASSE',
    'investor.auth.login.pin': 'PIN À 4 CHIFFRES',
    'investor.auth.login.use_pin': 'Utiliser plutôt le PIN',
    'investor.auth.login.use_password': 'Utiliser plutôt le mot de passe',
    'investor.auth.login.use_pin_short': 'Utiliser un PIN',
    'investor.auth.login.use_password_short': 'Utiliser un mot de passe',
    'investor.auth.login.pin_note': 'Aucun code OTP requis avec le PIN.',
    'investor.intro.head_title': 'Bienvenue',
    'investor.intro.skip': 'Passer',
    'investor.intro.back': 'Retour',
    'investor.intro.next': 'Suivant',
    'investor.intro.start': 'Commencer',
    'investor.intro.slide': 'diapositive',
    'investor.intro.slide_of': '{index} sur {count}',
    'investor.intro.audit.title':
        'Chaque entreprise est auditée avant que vous ne la voyiez.',
    'investor.intro.audit.body':
        'Rozine ne publie que les entreprises qui réussissent un audit indépendant — pas une note de crédit, une vraie inspection.',
    'investor.intro.audit.point_1':
        'Un CPA agréé ICPAR vérifie les comptes et visite les locaux.',
    'investor.intro.audit.point_2':
        "Cinq ans d'activité rentable, avec une marge de 10 % maintenue.",
    'investor.intro.audit.point_3':
        'Les rapports mensuels continuent tant que votre argent est investi.',
    'investor.intro.rate.title':
        'Un taux fixe de {min} à {max} %, remboursé chaque mois.',
    'investor.intro.rate.body':
        "Vous connaissez le rendement complet avant de vous engager. Pas d'intérêts composés, pas de taux variable, pas de frais prélevés d'avance.",
    'investor.intro.rate.point_1':
        'Les durées vont de {term_min} à {term_max} mois, remboursées chaque mois.',
    'investor.intro.rate.point_2':
        "Le taux dépend de la note et de la durée de l'entreprise, et ne change jamais.",
    'investor.intro.rate.point_3':
        "À partir de {price} le titre, dans autant d'entreprises que vous voulez.",
    'investor.intro.exit.title': 'Besoin de votre argent plus tôt ? Revendez.',
    'investor.intro.exit.body':
        "Vendez vos titres à d'autres investisseurs sur le marché — appariés en quelques secondes à un juste prix, ou fixez votre prix.",
    'investor.intro.exit.point_1':
        'Apparié aux offres existantes et payé sur votre portefeuille.',
    'investor.intro.exit.point_2':
        'Fixez un plancher pour ne jamais vendre sous votre prix.',
    'investor.intro.exit.point_3':
        'Vendez une partie et laissez le reste rapporter.',
    'investor.signup.progress': "Progression de l'inscription",
    'investor.signup.step_label': 'Étape {step} sur {count} · {type}',
    'investor.signup.create': 'Créer le compte',
    'investor.signup.identity.head_title': 'Informations personnelles',
    'investor.signup.identity.title': 'Informations personnelles',
    'investor.signup.identity.title_institution':
        "Informations sur l'institution",
    'investor.signup.identity.account_type': 'TYPE DE COMPTE',
    'investor.signup.identity.type_individual': 'Particulier',
    'investor.signup.identity.type_institution': 'Institution · Plus',
    'investor.signup.identity.institution_name': "NOM DE L'INSTITUTION",
    'investor.signup.identity.contact_person': 'PERSONNE DE CONTACT',
    'investor.signup.identity.address': 'ADRESSE / LIEU',
    'investor.signup.identity.address_placeholder':
        'KN 4 Ave, Nyarugenge, Kigali',
    'investor.signup.identity.company_code': "CODE D'ENTREPRISE RDB",
    'investor.signup.identity.company_code_hint':
        'Le code à 9 chiffres de votre certificat RDB',
    'investor.signup.identity.id_type': 'TYPE DE PIÈCE',
    'investor.signup.identity.id_number': 'N° DE PIÈCE / PASSEPORT / PERMIS',
    'investor.signup.identity.id_hint': "Tel qu'imprimé sur la pièce choisie",
    'investor.signup.id.national_id': "Carte d'identité",
    'investor.signup.id.passport': 'Passeport',
    'investor.signup.id.drivers_license': 'Permis de conduire',
    'investor.signup.id_placeholder.national_id': '1 1990 8 0012345 6 78',
    'investor.signup.id_placeholder.passport': 'PC1234567',
    'investor.signup.id_placeholder.drivers_license': 'RA-0123456',
    'investor.signup.address.head_title': 'Adresse',
    'investor.signup.address.title': 'Adresse',
    'investor.signup.address.country': 'PAYS',
    'investor.signup.address.province': 'PROVINCE',
    'investor.signup.address.district': 'DISTRICT',
    'investor.signup.address.sector': 'SECTEUR',
    'investor.signup.address.cell': 'CELLULE',
    'investor.signup.address.province_placeholder': 'Ville de Kigali',
    'investor.signup.address.district_placeholder': 'Gasabo',
    'investor.signup.address.sector_placeholder': 'Kimironko',
    'investor.signup.address.cell_placeholder': 'Bibare',
    'investor.signup.contact.head_title': 'Coordonnées',
    'investor.signup.contact.title': 'Coordonnées',
    'investor.signup.contact.email': 'ADRESSE E-MAIL',
    'investor.signup.contact.phone': 'NUMÉRO DE TÉLÉPHONE',
    'investor.signup.contact.country_code': 'Indicatif du pays',
    'investor.signup.security.head_title': 'Sécurisez votre compte',
    'investor.signup.security.title': 'Sécurisez votre compte',
    'investor.signup.security.subtitle':
        'Créez un PIN à 4 chiffres ou un mot de passe de 6 caractères ou plus.',
    'investor.signup.security.pin': 'PIN',
    'investor.signup.security.password': 'Mot de passe',
    'investor.signup.security.password_placeholder': 'Au moins 6 caractères',
    'investor.signup.payment.head_title': 'Moyen de paiement',
    'investor.signup.payment.title': 'Moyen de paiement',
    'investor.signup.payment.subtitle':
        'Liez un moyen pour alimenter votre portefeuille. Nous vérifions par OTP.',
    'investor.signup.payment.mtn': 'MTN Mobile Money',
    'investor.signup.payment.airtel': 'Airtel Money',
    'investor.signup.payment.bank': 'Compte bancaire',
    'investor.signup.payment.otp': 'Code à usage unique',
    'investor.signup.payment.otp_placeholder': "Saisir l'OTP",
    'investor.signup.payment.send_otp': "Envoyer l'OTP",
    'investor.signup.payment.otp_sent':
        '✓ OTP envoyé · saisissez le code à 6 chiffres',
    'investor.signup.agree.head_title': 'Vérifier et accepter',
    'investor.signup.agree.title': 'Vérifier et accepter',
    'investor.signup.agree.terms_before': "J'accepte les",
    'investor.signup.agree.terms': 'Conditions générales de Rozine',
    'investor.signup.agree.terms_after': '.',
    'investor.signup.agree.privacy_before': "J'ai lu la",
    'investor.signup.agree.privacy': 'Note de confidentialité',
    'investor.signup.agree.privacy_after': '.',
    'investor.kyc.progress': 'Progression de la vérification',
    'investor.kyc.kicker_identity': "VÉRIFICATION D'IDENTITÉ",
    'investor.kyc.kicker_entity': "VÉRIFICATION DE L'ENTITÉ",
    'investor.kyc.personal.title': 'Informations personnelles',
    'investor.kyc.personal.subtitle':
        'Nous vérifions chaque investisseur pour garder la plateforme sûre et conforme.',
    'investor.kyc.document.title': "Vérifiez votre pièce d'identité",
    'investor.kyc.document.subtitle':
        'Choisissez un type de document et envoyez des photos nettes.',
    'investor.kyc.liveness.title': 'Contrôle de présence',
    'investor.kyc.liveness.subtitle':
        "Un selfie rapide confirme que c'est bien vous.",
    'investor.kyc.entity.title': "Vérification de l'entité",
    'investor.kyc.entity.subtitle':
        "Nous vérifions l'entité juridique avant tout engagement de capital.",
    'investor.kyc.representative.title': 'Représentant autorisé',
    'investor.kyc.representative.subtitle':
        "La personne qui engagera des fonds au nom de l'entité.",
    'investor.kyc.declarations.title': 'Origine des fonds et déclarations',
    'investor.kyc.declarations.subtitle':
        "Requis avant l'activation d'un mandat.",
    'investor.kyc.country': 'PAYS DE RÉSIDENCE',
    'investor.kyc.dob': 'DATE DE NAISSANCE',
    'investor.kyc.dob_placeholder': 'JJ / MM / AAAA',
    'investor.kyc.id_number': 'NUMÉRO DE PIÈCE',
    'investor.kyc.upload_front': 'Envoyer le recto',
    'investor.kyc.upload_back': 'Envoyer le verso',
    'investor.kyc.uploaded_front': 'Recto envoyé',
    'investor.kyc.uploaded_back': 'Verso envoyé',
    'investor.kyc.selfie': 'Touchez pour prendre un selfie',
    'investor.kyc.selfie_done': 'Selfie enregistré',
    'investor.kyc.entity_type': "TYPE D'ENTITÉ",
    'investor.kyc.entity.fund': "Fonds d'investissement",
    'investor.kyc.entity.sacco': 'SACCO',
    'investor.kyc.entity.treasury': "Trésorerie d'entreprise",
    'investor.kyc.entity.insurer': 'Assureur',
    'investor.kyc.entity.pension': 'Fonds de pension',
    'investor.kyc.entity.other': 'Autre',
    'investor.kyc.company_code': "CODE D'ENTREPRISE RDB",
    'investor.kyc.incorporated': 'DATE DE CONSTITUTION',
    'investor.kyc.incorporated_placeholder': 'MM / AAAA',
    'investor.kyc.certificate': 'Envoyer le certificat',
    'investor.kyc.certificate_done': 'Certificat envoyé',
    'investor.kyc.certificate_hint':
        'Certificat de constitution · PDF ou photo',
    'investor.kyc.rep_name': 'NOM COMPLET',
    'investor.kyc.rep_role': 'FONCTION',
    'investor.kyc.rep_role_placeholder': 'Responsable de la trésorerie',
    'investor.kyc.rep_id': "N° DE CARTE D'IDENTITÉ OU DE PASSEPORT",
    'investor.kyc.resolution': 'Envoyer la résolution du conseil',
    'investor.kyc.resolution_done': 'Résolution du conseil envoyée',
    'investor.kyc.resolution_hint':
        'Résolution autorisant cette personne à engager des fonds',
    'investor.kyc.rep_liveness': 'Contrôle de présence du représentant',
    'investor.kyc.source': 'ORIGINE PRINCIPALE DES FONDS',
    'investor.kyc.funds.operations': 'Bilan propre',
    'investor.kyc.funds.member_savings': 'Dépôts des membres',
    'investor.kyc.funds.investment_returns': 'Capital géré pour des clients',
    'investor.kyc.funds.premiums': 'Dotation',
    'investor.kyc.funds.contributions': 'Cotisations de retraite',
    'investor.kyc.funds.other': 'Autre',
    'investor.kyc.commitment': 'ENGAGEMENT ANNUEL PRÉVU',
    'investor.kyc.commitment_hint':
        "Indicatif seulement — vous fixez l'engagement réel dans l'application.",
    'investor.kyc.aml':
        "Je confirme que les bénéficiaires effectifs de l'entité ont été déclarés et que les fonds ne proviennent pas d'un crime.",
    'investor.kyc.target':
        "Je comprends que le rendement Rozine Plus est un objectif, pas une garantie, que les titres sont détenus au nom de l'entité et que Rozine ne détient pas de capital à son bilan.",
    'investor.kyc.submit': 'Soumettre pour vérification',
    'investor.kyc.previous': 'Étape précédente',
    'investor.kyc.submitted_notice':
        "Vos informations sont entre les mains de notre équipe Conformité. Nous vous préviendrons une fois l'examen terminé.",
    'investor.kyc.rejected_notice':
        "La Conformité n'a pas pu vérifier ces informations : {reason}. Corrigez-les et soumettez-les à nouveau.",
    'investor.kyc.verifying': 'Vérification de votre identité…',
    'investor.kyc.verifying_entity': "Vérification de l'entité…",
    'investor.verified.title': 'Vous êtes vérifié',
    'investor.verified.body':
        'Bienvenue sur Rozine. Votre portefeuille est prêt et {count} opportunités vérifiées vous attendent.',
    'investor.verified.kyc': 'STATUT KYC',
    'investor.verified.verified': 'Vérifié',
    'investor.verified.wallet': 'PORTEFEUILLE',
    'investor.verified.cta': 'Commencer à explorer',
    'investor.health.healthy': 'Sain',
    'investor.health.watch': 'Surveillance',
    'investor.health.arrears': 'En retard',
    'investor.health.frozen': 'Gelé',
    'investor.health.defaulted': 'En défaut',
    'investor.health.matured': 'Échu',
    'investor.portfolio.title': 'Portefeuille',
    'investor.portfolio.tabs': 'Avoirs',
    'investor.portfolio.tab.active': 'Actifs',
    'investor.portfolio.tab.matured': 'Échus',
    'investor.portfolio.invested_line': 'Investi RWF {amount}',
    'investor.portfolio.matures_line':
        'Échéance {date} · {made}/{total} versements',
    'investor.portfolio.details': 'Détails',
    'investor.portfolio.see_all': 'Voir les {count} autres ›',
    'investor.portfolio.empty.active.title': "Aucun avoir pour l'instant",
    'investor.portfolio.empty.active.body':
        'Les titres achetés apparaissent ici avec leurs versements et rapports.',
    'investor.portfolio.empty.matured.title': "Rien pour l'instant",
    'investor.portfolio.empty.matured.body':
        'Les avoirs de cette catégorie apparaîtront ici.',
    'investor.portfolio.browse_deals': 'Voir les opportunités',
    'investor.portfolio.total_value': 'VALEUR TOTALE',
    'investor.portfolio.businesses': '{count} entreprises',
    'investor.portfolio.invested': 'INVESTI',
    'investor.portfolio.total_gain': 'GAIN TOTAL',
    'investor.portfolio.this_month': 'CE MOIS',
    'investor.portfolio.projected': 'PRÉVU · 3 PROCHAINS MOIS',
    'investor.portfolio.next_payout': 'Prochain : {amount} · {month}',
    'investor.portfolio.avg_month': 'MOY. / MOIS',
    'investor.portfolio.upcoming': 'VERSEMENTS À VENIR · RWF',
    'investor.portfolio.pays_one': '{count} entreprise paie ce mois-ci',
    'investor.portfolio.pays_other': '{count} entreprises paient ce mois-ci',
    'investor.portfolio.upcoming_note':
        'Touchez un mois pour voir quelles entreprises vous paient. Le total baisse à mesure que les titres arrivent à échéance.',
    'investor.portfolio.diversification': 'DIVERSIFICATION PAR SECTEUR',
    'investor.portfolio.risk_balance': 'ÉQUILIBRE DES RISQUES',
    'investor.portfolio.concentrated': 'Position concentrée',
    'investor.portfolio.concentrated_body':
        "{name} représente {pct} % de votre capital investi. Répartir sur plus d'entreprises réduit votre exposition si l'une sous-performe.",
    'investor.portfolio.browse': 'Diversifier',
    'investor.portfolio.balanced': 'Bien diversifié',
    'investor.portfolio.balanced_body':
        'Votre capital est réparti sur plusieurs entreprises sans position dominante. Continuez ainsi.',
    'investor.portfolio.idle': 'INUTILISÉ SUR LE PORTEFEUILLE',
    'investor.portfolio.idle_body': 'Investissez-le dans un nouveau titre.',
    'investor.portfolio.reinvest': 'Réinvestir ›',
    'investor.holding.frozen_title': 'Ce titre est gelé',
    'investor.holding.frozen_body':
        "Rozine a gelé ce titre le temps d'un examen. Les versements reprennent à sa levée ; votre créance reste inchangée.",
    'investor.holding.invested': 'INVESTI',
    'investor.holding.expected_profit': 'PROFIT ATTENDU',
    'investor.holding.yield': 'Rendement de {rate} %',
    'investor.holding.total_at_maturity': "TOTAL À L'ÉCHÉANCE",
    'investor.holding.principal_yield': 'Capital + rendement',
    'investor.holding.received': 'REÇU À CE JOUR',
    'investor.holding.payments': '{made}/{total} versements',
    'investor.holding.next_payment': 'PROCHAIN VERSEMENT',
    'investor.holding.no_next.matured': 'Entièrement remboursé',
    'investor.holding.no_next.paused': 'Suspendu',
    'investor.holding.on_time': 'PONCTUALITÉ',
    'investor.holding.all_on_time': 'Tous à temps',
    'investor.holding.late_one': '{count} versement en retard',
    'investor.holding.late_other': '{count} versements en retard',
    'investor.holding.rating': 'NOTE',
    'investor.holding.out_of_five': 'sur 5',
    'investor.holding.repaid': 'REMBOURSÉ',
    'investor.holding.month_left_one': '{count} mois restant',
    'investor.holding.month_left_other': '{count} mois restants',
    'investor.holding.rating_changed': 'La note a changé',
    'investor.holding.rating_changed_body':
        'Noté {from} lors de votre investissement · maintenant {to}',
    'investor.holding.rating_changed_note':
        'Les notes se mettent à jour à chaque rapport mensuel audité.',
    'investor.holding.plan_title': 'Un versement reporté',
    'investor.holding.plan_state.on_track': 'En bonne voie',
    'investor.holding.plan_state.off_track': 'En retard sur le plan',
    'investor.holding.plan_body':
        "{name} nous a prévenus avant l'échéance. Vérifié par rapport aux chiffres audités, approuvé, et sa note a baissé d'un cran.",
    'investor.holding.plan_reason': 'Motif invoqué',
    'investor.holding.plan_arrives': 'Arrivée des fonds',
    'investor.holding.plan_deferred': 'Reporté',
    'investor.holding.delayed': 'Paiement en retard',
    'investor.holding.defaulted': 'Paiements en défaut',
    'investor.holding.days_overdue': '{count} jours de retard',
    'investor.holding.in_recovery': 'EN RECOUVREMENT',
    'investor.holding.arrears_body':
        'Le dernier versement mensuel de {name} est en retard. Sa note Rozine a été ajustée et notre équipe de recouvrement est mobilisée.',
    'investor.holding.step.missed.title': 'Paiement manqué',
    'investor.holding.step.missed.body':
        'Les systèmes automatiques ont signalé un retard.',
    'investor.holding.step.contacted.title': 'Entreprise contactée',
    'investor.holding.step.contacted.body':
        'Rozine a pris contact et confirmé la cause.',
    'investor.holding.step.plan.title': 'Plan de recouvrement convenu',
    'investor.holding.step.plan.body':
        'Un calendrier de rattrapage est en cours de finalisation.',
    'investor.holding.step.resume.title': 'Reprise des remboursements',
    'investor.holding.step.resume.body':
        'Les versements mensuels normaux reprennent.',
    'investor.holding.claim_note':
        "Votre capital reste une créance sur l'entreprise. Rozine poursuit le recouvrement pour vous.",
    'investor.holding.photos': 'PHOTOS',
    'investor.holding.progress': 'AVANCEMENT DU REMBOURSEMENT',
    'investor.holding.payments_made': '{made} versements sur {total}',
    'investor.holding.investors': '{count} investisseurs',
    'investor.holding.updates': 'RAPPORTS MENSUELS',
    'investor.wallet.title': 'Portefeuille',
    'investor.wallet.available': 'SOLDE DISPONIBLE',
    'investor.wallet.status.active': 'Actif',
    'investor.wallet.status.restricted': 'Restreint',
    'investor.wallet.pending_withdrawal': 'Retrait de {amount} en attente',
    'investor.wallet.instant': 'Disponible immédiatement pour investir',
    'investor.wallet.rozine_wallet': 'Portefeuille Rozine',
    'investor.wallet.deposit': 'Déposer',
    'investor.wallet.withdraw': 'Retirer',
    'investor.wallet.panel.deposit': "Ajouter de l'argent",
    'investor.wallet.panel.withdraw': 'Retirer',
    'investor.wallet.amount': 'MONTANT',
    'investor.wallet.method': 'MOYEN DE PAIEMENT',
    'investor.wallet.method_to': 'ENVOYER VERS',
    'investor.wallet.fee': 'Frais de traitement',
    'investor.wallet.result.deposit': 'Nouveau solde disponible',
    'investor.wallet.result.withdraw': 'Vous recevez',
    'investor.wallet.confirm.deposit': 'Confirmer le dépôt',
    'investor.wallet.confirm.withdraw': 'Confirmer le retrait',
    'investor.wallet.processing': 'Envoi…',
    'investor.wallet.refusal.TRADE_BELOW_MINIMUM':
        'Saisissez au moins le montant minimum',
    'investor.wallet.refusal.INSUFFICIENT_AVAILABLE_FUNDS':
        'Le montant dépasse votre solde disponible',
    'investor.wallet.refusal.DAILY_LIMIT':
        'Dépasse le plafond de retrait journalier',
    'investor.wallet.refusal.RESTRICTION_ACTIVE':
        'Les retraits sont suspendus sur ce compte. Contactez le support.',
    'investor.wallet.no_methods': 'Aucun compte de versement lié.',
    'investor.wallet.link_account': 'Lier un compte',
    'investor.wallet.earn.title': 'Historique des gains',
    'investor.wallet.earn.subtitle': 'Intérêts perçus et capital remboursé.',
    'investor.wallet.earn.from': 'Date de début',
    'investor.wallet.earn.to': 'au',
    'investor.wallet.earn.to_date': 'Date de fin',
    'investor.wallet.earn.ranges': 'Période des gains',
    'investor.wallet.earn.range.3m': '3 mois',
    'investor.wallet.earn.range.6m': '6 mois',
    'investor.wallet.earn.range.12m': '12 mois',
    'investor.wallet.earn.range.ytd': 'Cette année',
    'investor.wallet.earn.interest': 'INTÉRÊTS',
    'investor.wallet.earn.received': 'REÇU',
    'investor.wallet.earn.payouts': 'VERSEMENTS',
    'investor.wallet.earn.avg': 'MOY. / MOIS',
    'investor.wallet.earn.month': 'MOIS',
    'investor.wallet.earn.paid': 'PAYÉS',
    'investor.wallet.earn.total': 'TOTAL REÇU',
    'investor.wallet.earn.empty': 'Aucun versement entre ces dates.',
    'investor.wallet.tx.title': 'Historique des transactions',
    'investor.wallet.tx.title_short': 'Transactions',
    'investor.wallet.tx.export': 'Exporter',
    'investor.wallet.tx.pdf': 'Télécharger le PDF',
    'investor.wallet.tx.csv': 'Télécharger Excel',
    'investor.wallet.tx.ranges': 'Période des transactions',
    'investor.wallet.tx.range.today': "Aujourd'hui",
    'investor.wallet.tx.range.7d': '7 jours',
    'investor.wallet.tx.range.30d': '30 jours',
    'investor.wallet.tx.range.all': 'Tout',
    'investor.wallet.tx.kind.deposit': 'Dépôt · {counterparty}',
    'investor.wallet.tx.kind.withdrawal': 'Retrait · {counterparty}',
    'investor.wallet.tx.kind.investment': 'Investissement · {counterparty}',
    'investor.wallet.tx.kind.payout': 'Versement · {counterparty}',
    'investor.wallet.tx.kind.refund': 'Remboursement · {counterparty}',
    'investor.wallet.tx.kind.fee': 'Frais · {counterparty}',
    'investor.wallet.tx.status.completed': 'Terminé',
    'investor.wallet.tx.status.pending': 'En attente',
    'investor.wallet.tx.status.failed': 'Échoué',
    'investor.wallet.tx.empty': 'Aucune transaction sur cette période.',
    'investor.wallet.receipt.label': 'Reçu de transaction',
    'investor.wallet.receipt.kind.deposit': 'Dépôt',
    'investor.wallet.receipt.kind.withdrawal': 'Retrait',
    'investor.wallet.receipt.kind.investment': 'Investissement',
    'investor.wallet.receipt.kind.payout': 'Remboursement mensuel',
    'investor.wallet.receipt.kind.refund': 'Remboursement',
    'investor.wallet.receipt.kind.fee': 'Frais',
    'investor.wallet.receipt.amount': 'MONTANT',
    'investor.wallet.receipt.status': 'Statut',
    'investor.wallet.receipt.type': 'Type',
    'investor.wallet.receipt.credit': 'Crédit',
    'investor.wallet.receipt.debit': 'Débit',
    'investor.wallet.receipt.when': 'Date et heure',
    'investor.wallet.receipt.transaction_id': 'N° de transaction',
    'investor.wallet.receipt.reference': 'Référence',
    'investor.wallet.receipt.before': 'Solde avant',
    'investor.wallet.receipt.after': 'Solde après',
    'investor.wallet.receipt.gross': 'Montant brut',
    'investor.wallet.receipt.fee': 'Frais de traitement',
    'investor.wallet.receipt.net': 'Net reçu',
    'investor.wallet.receipt.principal': 'Capital remboursé',
    'investor.wallet.receipt.return': 'Rendement versé',
    'investor.wallet.receipt.payout_fee': 'Frais investisseur (1 %)',
    'investor.wallet.receipt.done': 'Terminé',
    'investor.profile.title': 'Profil',
    'investor.profile.menu': 'Menu du profil',
    'investor.profile.kyc.verified': '✓ KYC vérifié',
    'investor.profile.kyc.pending': 'Vérification en cours',
    'investor.profile.kyc.unverified': 'Non vérifié',
    'investor.profile.kyc.expired': 'Document expiré',
    'investor.profile.type.individual': 'Particulier',
    'investor.profile.type.institution': 'Institution',
    'investor.profile.member_since': 'Membre depuis {date}',
    'investor.profile.item.linked': 'Comptes liés',
    'investor.profile.item.statements': 'Relevés et impôts',
    'investor.profile.item.automation': 'Auto-Deploy',
    'investor.profile.item.verification': "Vérification d'identité",
    'investor.profile.item.terms': 'Conditions générales',
    'investor.profile.item.privacy': 'Note de confidentialité',
    'investor.profile.item.personal': 'Informations personnelles',
    'investor.profile.item.plan': 'Rozine Plus · comment le débloquer',
    'investor.profile.item.security': 'Centre de sécurité',
    'investor.profile.item.help': "Centre d'aide",
    'investor.profile.plan_title': 'Rozine Plus',
    'investor.profile.personal.name': 'NOM COMPLET',
    'investor.profile.personal.email': 'E-MAIL',
    'investor.profile.personal.phone': 'TÉLÉPHONE',
    'investor.profile.personal.id_type': 'TYPE DE PIÈCE',
    'investor.profile.personal.id_number': 'NUMÉRO DE PIÈCE',
    'investor.profile.personal.passport_number': 'NUMÉRO DE PASSEPORT',
    'investor.profile.personal.address': 'ADRESSE',
    'investor.profile.personal.country': 'PAYS',
    'investor.profile.personal.province': 'PROVINCE',
    'investor.profile.personal.district': 'DISTRICT',
    'investor.profile.personal.sector': 'SECTEUR',
    'investor.profile.personal.cell': 'CELLULE',
    'investor.profile.personal.not_provided': 'Non renseigné',
    'investor.profile.security.two_factor': 'Authentification à deux facteurs',
    'investor.profile.security.two_factor_on':
        "Activée · code d'une application d'authentification à la connexion",
    'investor.profile.security.two_factor_off':
        "Désactivée · ajoutez un code d'application d'authentification à la connexion",
    'investor.profile.security.password': 'Changer le mot de passe',
    'investor.profile.pending.plan_title':
        "Rozine Plus n'est pas encore ouvert",
    'investor.profile.pending.plan_body':
        "Ses paliers et ses frais sont encore en cours de définition. D'ici là, vous investissez opération par opération, aux conditions affichées pour chacune.",
    'investor.profile.pending.help_title': "Bientôt dans l'application",
    'investor.profile.pending.help_body':
        'Les réponses aux questions les plus fréquentes des investisseurs apparaîtront ici.',
    'investor.profile.pending.terms_title': "Bientôt dans l'application",
    'investor.profile.pending.terms_body':
        'Les Conditions générales pourront être consultées ici dès leur publication.',
    'investor.profile.pending.privacy_title': "Bientôt dans l'application",
    'investor.profile.pending.privacy_body':
        'La Note de confidentialité pourra être consultée ici dès sa publication.',
    'investor.profile.sign_out': 'Se déconnecter',
    'investor.profile.linked.unlink': 'Délier',
    'investor.profile.linked.unverified': 'En vérification',
    'investor.profile.linked.none':
        'Aucun compte de versement. Liez-en un pour déposer et recevoir vos versements.',
    'investor.profile.linked.empty': 'Aucun compte de versement lié.',
    'investor.profile.linked.new': 'Lier un nouveau compte',
    'investor.profile.linked.add': '+ Lier un nouveau compte',
    'investor.profile.linked.type': 'TYPE',
    'investor.profile.linked.type_mobile': 'Mobile money',
    'investor.profile.linked.type_bank': 'Compte bancaire',
    'investor.profile.linked.network': 'RÉSEAU',
    'investor.profile.linked.mtn': 'MTN MoMo',
    'investor.profile.linked.airtel': 'Airtel Money',
    'investor.profile.linked.bank': 'BANQUE',
    'investor.profile.linked.mobile_number': 'NUMÉRO MOBILE',
    'investor.profile.linked.account_number': 'NUMÉRO DE COMPTE',
    'investor.profile.linked.mobile_placeholder': '07X XXX XXXX',
    'investor.profile.linked.account_placeholder': 'Numéro de compte',
    'investor.profile.linked.submit': 'Lier le compte',
    'investor.profile.linked.footnote':
        "Rozine effectue un débit de vérification unique. Les versements n'arrivent que sur un compte à votre nom.",
    'investor.profile.automation.gated_title':
        "Auto-Deploy n'est pas encore disponible",
    'investor.profile.automation.gated_body':
        'Il doit être approuvé sur le produit, les frais et le plan juridique avant que Rozine puisse investir pour vous.',
    'investor.profile.automation.intro':
        'Auto-Deploy permettrait à Rozine de réserver des titres dans de nouvelles levées pour vous, selon des règles que vous fixez. En attendant son approbation, chaque investissement est choisi et confirmé par vous.',
    'investor.profile.automation.nothing_runs':
        "Rien n'est configuré, actif ou facturé pour Auto-Deploy sur votre compte.",
    'investor.profile.automation.next': 'Choisir une opportunité vous-même',
    'investor.profile.statements.intro':
        'Téléchargez vos relevés de portefeuille et récapitulatifs fiscaux. Chacun est généré à partir de votre historique vérifié.',
    'investor.profile.statements.year': 'ANNÉE FISCALE {year}',
    'investor.profile.statements.annual': 'Récapitulatif annuel des rendements',
    'investor.profile.statements.annual_meta': 'Intérêts perçus · frais payés',
    'investor.profile.statements.incomplete':
        'Année en cours · chiffres provisoires',
    'investor.profile.statements.download_annual':
        'Télécharger le récapitulatif annuel',
    'investor.profile.statements.monthly': 'RELEVÉS MENSUELS',
    'investor.profile.statements.pdf': 'Relevé PDF',
    'investor.profile.statements.month_open':
        "{month} en cours · chiffres susceptibles d'évoluer",
    'investor.profile.statements.none':
        "Aucun relevé pour l'instant. Le premier arrive après votre premier mois complet.",
    'investor.profile.statements.disclaimer':
        'Les relevés sont fournis pour vos archives. Rozine ne fournit pas de conseil fiscal — consultez un conseiller qualifié.',

    'business.apply.business.unavailable': 'Indisponible',
    'business.apply.business.ineligible': 'Pas encore éligible à une levée',
    'business.apply.raise.resized':
        "Vous avez demandé {requested} · voici l'offre que vous acceptez en signant",
    'business.apply.raise.schedule': 'Échéancier de remboursement',
    'business.apply.raise.instalment': 'Échéance {n}',
    'business.apply.raise.instalment_final': 'Échéance {n} · dernière',
    'business.apply.review.your_offer': 'Votre offre',
    'business.apply.review.flat_rate': '{rate} % fixe',
    'business.apply.review.accept_offer':
        "J'accepte cette offre : {principal} sur {months} mois, remboursés selon l'échéancier ci-dessus.",
    'business.apply.review.no_offer':
        "Il n'y a pas encore d'offre à accepter. Revenez à votre levée pour obtenir une cotation.",
    'business.apply.review.signatories': 'Signataires',
    'business.apply.review.signatures_required':
        'Signatures exigées par le mandat de la société : {count}',
    'business.apply.review.signed_on': 'Signé · {date}',
    'business.apply.review.signer.signed': 'Signé',
    'business.apply.review.signer.pending': 'En attente',
    'business.apply.review.attestation':
        "Saisir votre nom confirme que vous signez vous-même. C'est votre compte vérifié, et non ce nom, qui signe.",
    'business.apply.review.waiting':
        'En attente de la signature de {names}. La demande est soumise dès que toutes les signatures requises sont réunies.',
    'business.apply.review.cannot_sign':
        'Seul un signataire du mandat vérifié de la société peut signer cette demande.',
    'business.apply.review.agreement_unavailable':
        "Le contrat n'est pas encore disponible.",
    'business.apply.review.agreement_unavailable_body':
        "Rozine n'a pas encore publié les conditions et les avertissements sur les risques approuvés pour cette demande : il n'y a donc rien à signer pour l'instant. Votre brouillon et votre offre restent enregistrés.",
    'business.apply.view_only':
        'Vous pouvez consulter cette demande, mais pas la modifier.',
    'business.apply.submitted.application_id': 'ID de la demande · {id}',
    'business.apply.outcome.checking.title': 'Vérification en cours',
    'business.apply.outcome.checking.body':
        'La connexion a été coupée avant la réponse du serveur. Nous vérifions si votre demande a été reçue.',
    'business.apply.outcome.unconfirmed.title':
        "Impossible de confirmer pour l'instant",
    'business.apply.outcome.unconfirmed.body':
        "Rien ne sera renvoyé tant que le serveur n'aura pas confirmé ce qu'il est advenu de votre demande.",
    'business.apply.outcome.pending.title': 'Traitement en cours',
    'business.apply.outcome.pending.body':
        'Le serveur a reçu votre demande et la traite encore.',
    'business.apply.outcome.not_recorded.title': 'Aucun résultat enregistré',
    'business.apply.outcome.not_recorded.body':
        "Le serveur n'a encore aucun résultat pour votre demande. Vous pouvez renvoyer exactement la même demande.",
    'business.apply.outcome.check_again': 'Vérifier à nouveau',
    'business.apply.outcome.try_again': 'Réessayer',
    'business.apply.outcome.refused.VERSION_CONFLICT':
        'Cette demande a changé depuis son ouverture. Nous avons chargé la dernière version — vérifiez-la et réessayez.',
    'business.apply.outcome.refused.IDEMPOTENCY_CONFLICT':
        "Cette demande a déjà été utilisée avec d'autres informations ; elle n'a donc pas été renvoyée. Nous avons chargé la dernière version.",
    'business.apply.outcome.refused.QUOTE_STALE':
        'Votre cotation a changé avant votre signature. Vérifiez la nouvelle offre et acceptez-la à nouveau.',
    'business.apply.outcome.refused.DOCUMENT_VERSION_STALE':
        'Un document ou une déclaration a changé avant votre signature. Lisez la nouvelle version et acceptez-la à nouveau.',
    'business.apply.outcome.refused.ACTION_FORBIDDEN':
        'Vous ne pouvez pas effectuer cette action pour cette entreprise.',
    'business.apply.outcome.refused.MANDATE_REQUIRED':
        'Votre mandat vérifié ne vous permet pas de signer pour cette entreprise.',
    'business.apply.outcome.refused.NOT_FOUND':
        'Cette demande ne vous est plus accessible.',
    'business.apply.outcome.refused.denied':
        'Votre accès a changé. Revenez à vos applications et réessayez.',
    'business.apply.outcome.refused.failed':
        "Le serveur n'a pas pu terminer cette action. Nous avons chargé la dernière version.",
    'business.publish.unavailable':
        "La publication s'ouvrira une fois votre demande approuvée et entièrement signée, et le processus de mise en ligne prêt.",

    'business.apply.outcome.refused.MANDATE_STALE':
        'Le mandat de signature de la société a changé avant votre signature. Vérifiez qui doit signer désormais, puis signez à nouveau.',

    'business.apply.review.document_summary': 'Résumé',
    'business.apply.outcome.refused.APPLICATION_PENDING_REVIEW':
        'Votre entreprise a déjà une demande en cours d’examen. Vous pourrez en déposer une nouvelle une fois la décision rendue.',
    'business.apply.outcome.refused.APPLICATION_STEP_INVALID':
        'Revenez à « Vérifier et signer » pour soumettre cette demande.',
    'business.apply.pending_review.link': 'Voir la demande en cours d’examen',
    'business.apply.review.document_full_text': 'Texte intégral',
    'business.apply.review.reduce.open': 'Prendre un montant inférieur',
    'business.apply.review.reduce.label': 'Montant souhaité (RWF)',
    'business.apply.review.reduce.help':
        "Jusqu'à l'offre, par billets entiers de {unit}. Nous recalculons l'offre pour ce montant et vous l'acceptez à nouveau.",
    'business.apply.review.reduce.submit': 'Recalculer',
    'business.apply.review.reduce.cancel': "Garder l'offre",
    'business.apply.review.reduced':
        'Vous avez choisi {principal} sur les {offered} proposés.',
    'business.apply.review.use_full': "Reprendre l'offre complète",
    'business.apply.raise.instalment_fee': '+ {fee} de frais de service',
    'business.apply.review.service_fee':
        'Frais de service ({rate} % de chaque remboursement)',
    'business.apply.review.projected': 'Prévisionnel',
    'business.apply.review.total_payable': 'Total à payer (prévisionnel)',
    'business.apply.review.fee_projection_note':
        'Frais de service prévisionnels : {rate} % de chaque remboursement, prélevés sur les montants effectivement remboursés (capital et intérêts, hors frais et pénalités). Ces montants supposent que chaque échéance est payée en totalité à la date prévue.',
    'business.apply.review.fee_note_service_fee':
        'Aucuns frais sur le montant levé. Facturés une fois la note approuvée, avant sa mise en ligne. Les frais de service sur chaque remboursement figurent avec votre offre ci-dessus.',
    'business.apply.review.fee_unavailable':
        'Conditions de frais indisponibles — vous ne pouvez pas encore signer',
    'business.apply.review.fee_unavailable_body':
        "Rozine n'a pas encore publié les conditions des frais de service pour cette offre : elle ne peut donc pas encore être acceptée. Votre brouillon et votre offre restent enregistrés.",

    'business.apply.recalculating': 'Recalcul…',
    'auditor.capture.unavailable':
        "L'application de capture n'est pas encore disponible pour cette mission : les photos et l'arrivée sur site ne peuvent pas être prises. Il n'existe aucun moyen de les capturer sur le web.",
    'auditor.photos.add_unavailable': 'Capture indisponible',
    'auditor.checkin.position_unavailable': 'Position indisponible',
    'auditor.ledger.ingested': 'Reçu · pas encore examiné',
    'auditor.jobs.map_approximate': 'Positions approximatives',
    'auditor.reason.label': 'Motif',
    'auditor.reason.explanation_required':
        'Explication factuelle (obligatoire)',
    'auditor.reason.explanation_optional': 'Explication (facultative)',
    'auditor.outcome.conflict.blocking.reassignment_pending':
        "Votre conflit a été enregistré. Le travail sur cette mission est arrêté pendant que les Opérations d'audit organisent la réattribution.",
    'auditor.outcome.conflict.blocking.reassigned':
        'Votre conflit a été enregistré et {business} a été réattribuée. Votre travail sur cette mission est arrêté.',
    'auditor.outcome.conflict.blocking.recorded':
        'Votre conflit a été enregistré. Le travail sur cette mission est arrêté.',
    'auditor.outcome.conflict.blocking.closed':
        "Votre conflit a été enregistré. Les Opérations d'audit ont clôturé cette mission et votre travail sur celle-ci est arrêté.",
    'auditor.seal.cites': 'Preuves : {ids}',
    'auditor.seal.evidence': 'Preuves scellées avec ce rapport',
    'auditor.seal.versions':
        'Procédure {procedure} · constats {findings} · preuves {evidence}',
    'auditor.seal.code_title': "Confirmez que c'est bien vous",
    'auditor.seal.code_lead':
        "Saisissez le code à six chiffres de votre application d'authentification pour sceller sous la licence {licence}.",
    'auditor.seal.code_label': "Code d'authentification à six chiffres",
    'auditor.seal.code_scope':
        "Ce code confirme seulement que c'est vous qui scellez. Il ne dit rien des téléphones ou appareils utilisés pour capturer les preuves.",
    'auditor.seal.code_wrong':
        'Ce code ne correspond pas. Saisissez le code actuel de votre authentificateur.',
    'auditor.seal.code_expired':
        "Votre confirmation a expiré avant l'apposition du sceau. Saisissez un nouveau code.",
    'auditor.seal.code_throttled':
        'Trop de tentatives. Vous pourrez saisir un nouveau code dans {wait}.',
    'auditor.seal.code_throttled_later':
        'Trop de tentatives. Patientez un instant, puis saisissez un nouveau code.',
    'auditor.seal.code_unreachable':
        "Impossible de joindre Rozine pour vérifier votre code. Rien n'a été scellé — saisissez un nouveau code pour réessayer.",
    'auditor.seal.sealing': 'Scellement…',
    'auditor.seal.mfa_title':
        "Activez l'authentification à deux facteurs pour sceller",
    'auditor.seal.mfa_body':
        "Le scellement demande un code d'une application d'authentification confirmée sur votre compte. Configurez-en une dans vos paramètres de sécurité, puis revenez sceller.",
    'auditor.seal.mfa_settings': 'Ouvrir les paramètres de sécurité',
    'auditor.sealed.report_id': 'Rapport',
    'auditor.sealed.signature_ref': 'Signature',
    'auditor.sealed.key_id': 'Clé',
    'auditor.sealed.amended_by':
        "Le rapport {report} modifie celui-ci ; ce rapport reste tel qu'il a été scellé.",
    'auditor.sealed.open_amendment': 'Ouvrir la modification',
    'auditor.sealed.verify': 'Vérifier le sceau',
    'auditor.audit.amends':
        'Ceci est une modification liée du rapport {report}. Ce rapport reste inchangé.',
    'auditor.audit.open_original': "Ouvrir l'original",
    'auditor.receipt.title': 'Conflit enregistré',
    'auditor.receipt.body.reassignment_pending':
        "Votre conflit a été enregistré. Le travail sur cette mission est arrêté pendant que les Opérations d'audit organisent la réattribution.",
    'auditor.receipt.body.reassigned':
        "Votre conflit a été enregistré et la mission a été réattribuée. Vous n'avez plus accès à son dossier.",
    'auditor.receipt.body.recorded':
        "Votre conflit a été enregistré. Le travail sur cette mission est arrêté et vous n'avez plus accès à son dossier.",
    'auditor.receipt.body.closed':
        "Votre conflit a été enregistré. Les Opérations d'audit ont clôturé cette mission et vous n'avez plus accès à son dossier.",
    'auditor.receipt.status': 'Mission',
    'auditor.receipt.state.reassignment_pending': 'Réattribution en attente',
    'auditor.receipt.state.reassigned': 'Réattribuée',
    'auditor.receipt.state.recorded': 'Enregistré',
    'auditor.receipt.state.closed': "Clôturée par les Opérations d'audit",
    'auditor.receipt.kind': "Type d'intérêt",
    'auditor.receipt.declared': 'Déclaré',
    'auditor.receipt.note': 'Votre explication',
    'auditor.evidence.title': 'Preuves',
    'auditor.evidence.captured': 'Capturé',
    'auditor.evidence.source': 'Source',
    'auditor.evidence.attestation': "Attestation de l'appareil",
    'auditor.evidence.position': 'Position',
    'auditor.evidence.accuracy': 'Précision',
    'auditor.evidence.metres': '±{metres} m',
    'auditor.evidence.unavailable': 'Indisponible',
    'auditor.evidence.digest': 'SHA-256 {digest}…',
    'auditor.evidence.source_companion_device': 'Application de capture',
    'auditor.evidence.source_web_upload': 'Téléversement web',
    'auditor.evidence.attestation_verified': 'Attestée',
    'auditor.evidence.attestation_unverified': 'Non attestée',
    'auditor.evidence.attestation_unavailable': 'Indisponible',
    'auditor.evidence.kind.photo': 'Photo du site',
    'auditor.evidence.kind.check_in': 'Arrivée sur site',
    'auditor.evidence.kind.ledger': 'Document de registre',
    'auditor.evidence.kind.statement': 'Relevé',
    'auditor.evidence.kind.licence_certificate': 'Certificat de licence',
    'auditor.command.checking.title': 'Vérification en cours',
    'auditor.command.checking.body':
        "La réponse à votre dernière action s'est perdue : Rozine vérifie si elle a été enregistrée. Rien n'est renvoyé entre-temps.",
    'auditor.command.unconfirmed.title':
        'Impossible de confirmer votre dernière action',
    'auditor.command.unconfirmed.body':
        'Impossible de joindre Rozine pour vérifier si elle a été enregistrée. Vérifiez à nouveau avant toute autre action — elle ne sera pas envoyée deux fois.',
    'auditor.command.pending.title': 'Enregistrée — traitement en cours',
    'auditor.command.pending.body':
        'Rozine a reçu votre dernière action et la traite encore. Vérifiez à nouveau dans un instant.',
    'auditor.command.not_recorded.title': 'Non enregistrée',
    'auditor.command.not_recorded.body':
        "Rozine n'a aucune trace de votre dernière action. Vous pouvez renvoyer la même demande ; elle ne peut pas être appliquée deux fois.",
    'auditor.command.check_again': 'Vérifier à nouveau',
    'auditor.command.try_again': 'Réessayer',
    'auditor.command.refused.VERSION_CONFLICT':
        'Cet enregistrement a changé depuis son ouverture. La page a été actualisée — vérifiez-la et réessayez.',
    'auditor.command.refused.IDEMPOTENCY_CONFLICT':
        'Cette demande a déjà servi à une autre action. Recommencez depuis la page actualisée.',
    'auditor.command.refused.DIGEST_STALE':
        'Les preuves ont changé après votre aperçu. Relisez le nouvel aperçu et confirmez avec un nouveau code.',
    'auditor.command.refused.EVIDENCE_VERSION_STALE':
        'Les preuves ont changé après votre aperçu. Relisez le nouvel aperçu et confirmez à nouveau.',
    'auditor.command.refused.FINDINGS_VERSION_STALE':
        'Les constats ont changé après votre aperçu. Relisez le nouvel aperçu et confirmez à nouveau.',
    'auditor.command.refused.PROCEDURE_VERSION_STALE':
        'La version de la procédure a changé. Relisez le nouvel aperçu et confirmez à nouveau.',
    'auditor.command.refused.MANDATE_STALE':
        'Les conditions de votre mission ont changé. Relisez la page actualisée et confirmez à nouveau.',
    'auditor.command.refused.ACTION_FORBIDDEN':
        'Vous ne pouvez plus effectuer cette action sur cette mission. Votre accès a changé.',
    'auditor.command.refused.NOT_FOUND':
        'Cet enregistrement ne vous est pas accessible.',
    'auditor.command.refused.STEP_UP_INVALID':
        "Cette confirmation n'a pas abouti. Saisissez un nouveau code de votre authentificateur.",
    'auditor.command.refused.STEP_UP_EXPIRED':
        'Cette confirmation a expiré avant le scellement. Saisissez un nouveau code de votre authentificateur.',
    'auditor.command.refused.denied':
        "Votre accès a changé : l'action n'a pas été effectuée.",
    'auditor.command.refused.failed':
        "L'action n'a pas pu être effectuée. Actualisez la page et réessayez.",
    'auditor.accreditation.badge.none': 'Non accrédité',
    'auditor.accreditation.badge.first_pending': "En cours d'examen",
    'auditor.accreditation.none_line': 'Aucune licence enregistrée',
    'auditor.accreditation.none_body':
        "Soumettez votre licence ICPAR pour examen. La soumission ne vous confère aucun statut : vous ne pourrez prendre de missions qu'après qu'un membre autorisé du personnel Rozine a enregistré le contrôle ICPAR et ses dates.",
    'auditor.accreditation.first_pending_title':
        "Première accréditation en cours d'examen",
    'auditor.accreditation.evidence_line':
        'Certificat {id} · SHA-256 {digest}…',
    'auditor.accreditation.expiry_label_first':
        "Date d'expiration de la licence",
    'auditor.accreditation.first_submit': 'Soumettre votre accréditation',

    'auditor.availability.locked':
        'Votre disponibilité ne peut pas être modifiée ici pour le moment.',
    'auditor.home.unavailable': 'Indisponible',

    'auditor.availability.home_paused_locked':
        "L'affectation ne vous propose pas de missions flash",

    'business.grow.starting': 'Démarrage…',

    'auditor.conflict.not_allowed':
        "Vous ne pouvez plus faire de déclaration sur ce dossier : votre déclaration n'a pas été envoyée.",

    'auditor.jobs.accept_due': 'Accepter · dû le {time}',
    'auditor.jobs.accept_plain': 'Accepter',
    'auditor.jobs.offer_open': "Offre ouverte jusqu'au {time} · encore {left}",
    'auditor.jobs.offer_label': 'Temps restant pour accepter cette offre',
    'auditor.jobs.offer_closed': 'Cette offre est close',
    'auditor.command.refused.ASSIGNMENT_ACCEPTANCE_EXPIRED':
        "Cette offre s'est close avant que votre acceptation n'arrive chez Rozine : elle n'a pas été acceptée. La page a été actualisée.",

    'auditor.standing.reason.ACCREDITATION_REQUIRED':
        "Vous n'avez pas encore d'accréditation approuvée.",
    'auditor.command.refused.ACCREDITATION_REQUIRED':
        "Vous n'avez pas encore d'accréditation approuvée. Aucune mission ne peut vous être proposée d'ici là : rien n'a été modifié.",
    'auditor.standing.reason.ACCREDITATION_EXPIRED':
        'Votre licence a expiré. Renouvelez-la pour recevoir à nouveau des missions.',
    'auditor.command.refused.ACCREDITATION_EXPIRED':
        "Votre licence a expiré. Renouvelez-la pour recevoir à nouveau des missions. Aucune mission ne peut vous être proposée d'ici là : rien n'a été modifié.",
    'auditor.standing.reason.ACCREDITATION_SUSPENDED':
        "Votre accréditation est suspendue par les Opérations d'audit.",
    'auditor.command.refused.ACCREDITATION_SUSPENDED':
        "Votre accréditation est suspendue par les Opérations d'audit. Aucune mission ne peut vous être proposée d'ici là : rien n'a été modifié.",
    'auditor.standing.reason.STANDING_CHECK_REQUIRED':
        "Un contrôle de statut par les Opérations d'audit est attendu.",
    'auditor.command.refused.STANDING_CHECK_REQUIRED':
        "Un contrôle de statut par les Opérations d'audit est attendu. Aucune mission ne peut vous être proposée d'ici là : rien n'a été modifié.",
    'auditor.standing.paused_until_restored':
        "L'affectation est suspendue jusqu'au rétablissement de votre statut. Votre choix d'accepter des audits est conservé.",
    'auditor.standing.turning_on':
        "L'activer n'apportera pas d'offres tant que votre statut n'est pas rétabli.",

    'auditor.accreditation.licence_title': "Licence d'exercice",
    'auditor.accreditation.view_certificate':
        'Télécharger le certificat enregistré',
    'auditor.accreditation.view_submitted': 'Télécharger le certificat soumis',

    'auditor.engagement.head_title': 'Conditions de mission',
    'auditor.engagement.title': 'Conditions de mission',
    'auditor.engagement.lead':
        'Lisez les deux documents en entier avant d’accepter. De nouvelles missions ne vous sont proposées que selon des conditions que vous avez acceptées.',
    'auditor.engagement.synthetic_title': 'Conditions de test synthétiques',
    'auditor.engagement.synthetic_body':
        'Ce sont des conditions de test, pas pour de vraies missions. Les accepter ne représente aucune mission professionnelle réelle.',
    'auditor.engagement.original_language':
        'Les conditions sont affichées dans leur langue d’origine.',
    'auditor.engagement.document.master_services': 'Contrat-cadre de services',
    'auditor.engagement.document.agreed_procedures': 'Procédures convenues',
    'auditor.engagement.document_meta': 'Version {version} · SHA-256 {hash}…',
    'auditor.engagement.acceptance_title': 'Votre acceptation',
    'auditor.engagement.release_meta':
        'Version {version} · procédure {procedure}',
    'auditor.engagement.release_hash': 'SHA-256 de la publication {hash}…',
    'auditor.engagement.accept_label':
        'J’ai lu et j’accepte le Contrat-cadre de services et les Procédures convenues',
    'auditor.engagement.accept': 'Accepter les conditions',
    'auditor.engagement.accepting': 'Acceptation…',
    'auditor.engagement.acceptance_required':
        'Cochez la case pour confirmer que vous avez lu et acceptez les deux documents.',
    'auditor.engagement.accepted':
        'Vous avez accepté la version {version} le {date}',
    'auditor.engagement.accepted_receipt': 'SHA-256 du reçu {hash}…',
    'auditor.engagement.no_accept':
        'L’acceptation de ces conditions ne vous est pas proposée pour le moment.',
    'auditor.engagement.unavailable':
        'Aucune condition de mission n’est disponible pour le moment',
    'auditor.engagement.unavailable_body':
        'Les nouvelles missions sont suspendues jusqu’à ce que Rozine publie des conditions. Rien ne vous est demandé d’ici là.',
    'auditor.engagement.refused.AUDIT_ENGAGEMENT_VERSION_CONFLICT':
        'Les conditions ont changé avant que votre acceptation n’atteigne Rozine ; rien n’a été accepté. Lisez la version actuelle en entier avant d’accepter.',
    'auditor.engagement.refused.AUDIT_ENGAGEMENT_TERMS_REQUIRED':
        'Ces conditions ont été retirées avant que votre acceptation n’atteigne Rozine ; rien n’a été accepté.',
    'auditor.engagement.banner.required':
        'Lisez et acceptez les conditions de mission pour recevoir de nouvelles missions',
    'auditor.engagement.banner.unavailable':
        'Les conditions de mission ne sont pas disponibles ; les nouvelles missions sont suspendues',
    'auditor.command.refused.AUDIT_ENGAGEMENT_ACCEPTANCE_REQUIRED':
        'Acceptez les conditions de mission actuelles pour continuer.',
    'auditor.command.review_terms': 'Lire les conditions',
    'auditor.seal.save_note': 'Enregistrer la note',
    'auditor.seal.saving_note': 'Enregistrement de la note…',
    'auditor.seal.note_unsaved':
        "Enregistrez votre note avant l'aperçu. L'aperçu, votre code et le sceau portent tous sur la note enregistrée.",
    'auditor.ledger.reported_undeclared': 'Non déclaré',
    'auditor.ledger.reported_undeclared_note':
        "L'entreprise n'a pas déclaré de valeur de stock : il n'y a donc aucun chiffre déclaré auquel comparer votre comptage. Saisissez ce que vous avez compté.",
    'auditor.ledger.reconciles_undeclared':
        "Le rapprochement reste bloqué tant que l'entreprise n'a pas déclaré son stock.",

    'auditor.statements.cover_unavailable': 'Indisponible',
    'auditor.statements.documents': 'Documents sources',
    'auditor.statements.no_documents':
        "Aucun document source n'est enregistré pour ce mois.",
    'auditor.count.period_unavailable': 'Indisponible',

    'auditor.file.start': "Commencer l'audit",
    'auditor.file.starting': 'Démarrage…',
    'auditor.file.application_unavailable':
        "Aucune demande soumise n'est encore disponible pour l'audit.",
    'auditor.command.refused.APPLICATION_VERSION_CONFLICT':
        "La demande de l'entreprise a changé depuis l'ouverture de ce dossier. La page a été actualisée — vérifiez-la et recommencez.",
    'auditor.command.refused.APPLICATION_NOT_SUBMITTED':
        "Cette demande n'a pas été soumise : il n'y a donc encore rien à auditer. La page a été actualisée.",
    'auditor.command.refused.APPLICATION_NOT_FOUND':
        "Cette demande ne vous est plus accessible : rien n'a été commencé.",
    'auditor.command.refused.AUDIT_APPLICATION_BOUND':
        "Un rapport est déjà lié à cette demande : aucun nouveau n'a été commencé. La page a été actualisée — reprenez à partir de là.",
    'auditor.command.refused.AUDIT_REPORT_REASSIGNMENT_REQUIRED':
        "Ce rapport doit être réattribué avant que le travail puisse reprendre : rien n'a été commencé. La page a été actualisée.",

    'auditor.evidence.source_isolated_synthetic':
        'Preuve de test synthétique (isolée)',

    'auditor.ledger.file_type': 'Choisissez le registre au format PDF ou CSV.',
    'auditor.ledger.file_size':
        'Ce fichier dépasse 10 Mo. Téléversez un PDF ou un CSV de 10 Mo au plus.',

    'auditor.seal.note_unsaved_unsealable':
        "Votre note n'est pas encore enregistrée. Enregistrez-la maintenant ; le scellement s'ouvrira quand le rapport sera prêt.",
    'auditor.capture.synthetic':
        'Preuve de test synthétique (isolée) — pas une capture native.',

    'auditor.returned.title.changes_requested': 'Modifications demandées',
    'auditor.returned.title.rejected': 'Déclaration rejetée',
    'auditor.returned.lead.changes_requested':
        "Cette déclaration a été renvoyée à l'entreprise pour le motif ci-dessous. Le rapport est conservé tel qu'il a été renvoyé.",
    'auditor.returned.lead.rejected':
        "Cette version de la déclaration n'a pas pu être vérifiée, pour le motif ci-dessous. Cela concerne la déclaration, pas le crédit de l'entreprise. Le rapport est conservé tel qu'il a été rejeté.",
    'auditor.returned.reason': 'Motif',
    'auditor.returned.recorded': 'Enregistré',
    'auditor.returned.amend': 'Commencer une modification liée',
    'auditor.returned.amended_by':
        'Une modification {report} a été commencée à partir de ce rapport.',
    'auditor.returned.view_amendment': 'Voir la modification',

    'auditor.reason.count': '{count} / {max}',
    'auditor.command.refused.AUDIT_REPORT_DECISION_NOT_ALLOWED':
        "Ce rapport ne peut plus être renvoyé ni rejeté : rien n'a été enregistré. La page a été actualisée.",
    'auditor.command.refused.AUDIT_REPORT_NOT_AMENDABLE':
        "Ce rapport ne peut pas être modifié pour le moment : aucune modification n'a été commencée. La page a été actualisée.",
    'auditor.ledger.download': "Télécharger l'original",

    'auditor.sealed.body_undated':
        'Le rapport est scellé et ne peut plus être modifié. {party} doit encore le cosigner ; il est ensuite publié aux porteurs.',
    'auditor.sealed.unavailable':
        "Ce sceau ne peut pas être vérifié pour le moment — sa clé de signature n'est plus en vigueur. L'enregistrement scellé et son historique restent inchangés.",

    'auditor.sealed.body_published':
        'Scellé et cosigné ; publié aux porteurs le {date}.',
    'auditor.sealed.body_signed':
        "Le rapport est scellé et {party} l'a cosigné. Il sera ensuite publié aux porteurs.",
    'auditor.sealed.body_declined':
        "Le rapport est scellé. {party} l'a contesté au lieu de le cosigner : il n'est donc pas publié.",
    'auditor.sealed.body_overdue':
        "Le rapport est scellé, mais le délai de cosignature de {party} est passé. Il ne peut plus être cosigné et n'est pas publié ; rien n'est approuvé automatiquement.",

    'auditor.command.refused.AUDIT_PROCEDURE_SOURCE_CHANGED':
        'Une source a changé après votre aperçu. Revenez en arrière pour la vérifier, puis prévisualisez à nouveau avant de sceller.',

    'auditor.sealed.body_amended':
        'Vous avez modifié ce rapport : il ne sera ni cosigné ni publié. La modification le remplace.',

    'settlement.notice.checking.title': 'Vérification en cours',
    'settlement.notice.checking.body':
        "La réponse ne nous est pas parvenue : nous vérifions si la demande a été enregistrée. Rien n'est renvoyé entre-temps.",
    'settlement.notice.unconfirmed.title': 'Pas encore confirmé',
    'settlement.notice.unconfirmed.body':
        "Impossible de joindre le serveur pour le confirmer. Rien n'a été renvoyé. Vérifiez de nouveau une fois connecté.",
    'settlement.notice.pending.title': 'Enregistré, pas encore confirmé',
    'settlement.notice.pending.body':
        'Le serveur a enregistré cette demande et la traite encore. Cette page affichera le résultat une fois confirmé.',
    'settlement.notice.not_recorded.title': "Rien n'a été enregistré",
    'settlement.notice.not_recorded.body':
        "Le serveur n'a aucune trace de cette demande. Les informations ont été actualisées ; vous pouvez renvoyer la même demande.",
    'settlement.notice.check_again': 'Vérifier de nouveau',
    'settlement.notice.try_again': 'Renvoyer la même demande',
    'settlement.notice.no_longer_allowed':
        "Rien n'a été enregistré, et cette action n'est plus disponible avec les informations actuelles.",
    'settlement.poll.stopped':
        "Pas encore confirmé. La vérification automatique s'est arrêtée — actualisez pour voir le dernier état.",
    'settlement.poll.refresh': 'Actualiser',
    'settlement.refusal.other':
        'Cette demande a été refusée. Actualisez et réessayez.',
    'settlement.refusal.VALIDATION_FAILED':
        'Certaines informations doivent être corrigées avant de continuer.',
    'settlement.refusal.EXPOSURE_LIMIT':
        "Cela dépasserait l'une de vos limites d'investissement.",
    'settlement.refusal.INSUFFICIENT_AVAILABLE_FUNDS':
        'Votre solde disponible ne couvre pas ce montant.',
    'settlement.refusal.VERSION_CONFLICT':
        'Quelque chose a changé depuis le chargement de la page. Les informations ont été actualisées — vérifiez-les et réessayez.',
    'settlement.refusal.IDEMPOTENCY_CONFLICT':
        'Cette demande a déjà servi à autre chose. Recommencez à partir des informations actuelles.',
    'settlement.refusal.RESERVATION_EXPIRED':
        'Votre réservation de 5 minutes a expiré et ses titres ont été libérés. Réservez de nouveau pour continuer.',
    'settlement.refusal.CAMPAIGN_CLOSED': "Cette levée n'est plus ouverte.",
    'settlement.refusal.UNITS_UNAVAILABLE':
        'Ces titres ne sont plus disponibles. Choisissez-en moins ou réessayez plus tard.',
    'settlement.refusal.COMMITMENT_LOCKED':
        'La levée est entièrement financée : cela ne peut plus être annulé.',
    'settlement.refusal.CAMPAIGN_SETTLEMENT_REQUIRED':
        "Des investisseurs se sont déjà engagés dans cette levée : elle ne peut donc pas être annulée ici. Leurs engagements doivent d'abord être réglés.",
    'settlement.refusal.CAMPAIGN_FUNDED':
        'Cette levée est financée et en attente de décaissement : elle ne peut pas être annulée.',
    'settlement.refusal.NOTE_INELIGIBLE':
        "Ce titre n'est pas éligible pour le moment.",
    'settlement.refusal.DISCLOSURE_STALE':
        "L'information a changé. Lisez la version actuelle et confirmez-la de nouveau.",
    'settlement.refusal.POLICY_INPUT_REQUIRED':
        "Ce n'est pas encore disponible : une règle requise n'a pas été définie.",
    'settlement.refusal.DEPOSIT_METHOD_UNVERIFIED':
        "Ce compte n'est pas encore vérifié pour les dépôts.",
    'settlement.refusal.EXPOSURE_RESERVATION_REQUIRED':
        "Aucune réservation d'emprunt n'est enregistrée pour cette demande : elle ne peut pas être publiée.",
    'settlement.refusal.SIGNATURES_REQUIRED':
        "Tous les signataires requis n'ont pas signé à l'étape Revue.",
    'settlement.refusal.STAFF_RELEASE_REQUIRED':
        "L'équipe Rozine n'a pas encore autorisé cette demande.",
    'settlement.refusal.TERMS_CHANGED':
        'Les conditions ont changé depuis votre signature.',
    'settlement.refusal.FEE_DISCLOSURE_CHANGED':
        "L'information sur les frais de publication a changé. Lisez la version actuelle avant de publier.",
    'settlement.refusal.QUOTE_STALE': "Votre offre n'est plus à jour.",
    'settlement.refusal.APPLICATION_ALREADY_RELEASED':
        'Cette demande a déjà été autorisée.',
    'settlement.refusal.LISTING_ALREADY_PUBLISHED':
        'Cette offre est déjà publiée.',
    'settlement.refusal.NOTHING_DUE':
        "Rien n'est dû sur ce titre pour le moment. La page a été actualisée.",
    'settlement.refusal.NOTE_NOT_SERVICING':
        "Ce titre n'est pas en cours de remboursement ; il n'y a rien à payer.",
    'settlement.refusal.APPLICATION_NOT_RELEASED':
        "Cette demande n'a pas encore été autorisée à la publication.",
    'settlement.refusal.DISBURSEMENT_IN_FLIGHT':
        "L'intention de paiement est déjà enregistrée : elle ne peut plus être modifiée ainsi.",
    'settlement.refusal.PROVIDER_OUTCOME_UNRESOLVED':
        "Le résultat du prestataire n'est pas encore résolu : l'action est bloquée.",
    'settlement.refusal.ACTION_FORBIDDEN':
        'Vous ne pouvez pas faire cela avec votre accès actuel.',
    'settlement.refusal.IDENTITY_VERIFICATION_REQUIRED':
        'Vérifiez votre identité pour investir.',
    'settlement.refusal.RESTRICTION_ACTIVE':
        "Une restriction est en place : ce n'est pas disponible pour le moment.",
    'settlement.refusal.CONNECTED_PARTY':
        'Vous êtes lié à cette entreprise : vous ne pouvez pas participer.',
    'settlement.refusal.SELF_APPROVAL_FORBIDDEN':
        'Un autre membre du personnel doit le faire : vous ne pouvez pas valider votre propre action.',
    'settlement.refusal.STEP_UP_REQUIRED':
        "Une nouvelle confirmation renforcée est d'abord requise.",
    'settlement.refusal.STEP_UP_INVALID':
        "Cette confirmation n'a pas abouti. Saisissez un nouveau code de votre authentificateur.",
    'settlement.refusal.STEP_UP_EXPIRED':
        'Cette confirmation a expiré. Saisissez un nouveau code de votre authentificateur.',
    'settlement.refusal.MANDATE_REQUIRED':
        "Cela nécessite une personne habilitée par le mandat de l'entreprise.",
    'settlement.refusal.STAFF_ACCESS_REQUIRED':
        'Un accès personnel est requis.',
    'settlement.refusal.STAFF_PERMISSION_REQUIRED':
        'Vos autorisations ne couvrent pas cette action.',
    'settlement.refusal.STAFF_VERIFIED_EMAIL_AND_MFA_REQUIRED':
        "Vérifiez votre e-mail et activez l'authentification à deux facteurs pour continuer.",
    'settlement.refusal.MFA_REQUIRED':
        "L'authentification à deux facteurs est requise.",
    'settlement.refusal.NOT_FOUND':
        'Vous ne pouvez plus consulter cet enregistrement.',
    'investor.wallet.c3.total': 'Total du portefeuille',
    'investor.wallet.c3.bucket.available': 'Disponible',
    'investor.wallet.c3.bucket.held': 'Réservé',
    'investor.wallet.c3.bucket.committed': 'Engagé',
    'investor.wallet.c3.spendable':
        "Seul le disponible peut être dépensé. Le réservé est dans un paiement en cours ; l'engagé attend l'émission.",
    'investor.wallet.c3.restricted':
        "Une restriction s'applique depuis le {date}. Les dépôts restent possibles.",
    'investor.wallet.c3.restricted_unavailable':
        "Une restriction s'applique depuis le {date}. Les dépôts ne sont pas disponibles pour le moment.",
    'investor.wallet.c3.no_pending': 'Aucun dépôt en attente',
    'investor.wallet.c3.pending_deposits':
        '{amount} pas encore confirmé — hors du total',
    'investor.wallet.c3.no_policy':
        "Les dépôts ne sont pas encore disponibles : aucune règle de dépôt n'a été définie.",
    'investor.wallet.c3.credited_on_success': 'Crédité une fois confirmé',
    'investor.wallet.c3.policy_synthetic':
        "Règle de dépôt fictive {version} — chiffres d'aperçu, pas une règle en vigueur.",
    'investor.wallet.c3.policy': 'Règle de dépôt {version}.',
    'investor.wallet.c3.policy_minimum': 'Minimum {amount}.',
    'investor.wallet.c3.policy_maximum': 'Maximum {amount}.',
    'investor.wallet.c3.deposit_unavailable':
        'Le dépôt ne vous est pas accessible pour le moment.',
    'investor.wallet.c3.intent_only':
        "Cela enregistre votre demande de dépôt. Rien n'est crédité avant la confirmation du paiement.",
    'investor.wallet.c3.holds': 'Réservé pour un paiement',
    'investor.wallet.c3.hold_line': {
        one: '{name} · {count} titre',
        other: '{name} · {count} titres',
    },
    'investor.wallet.c3.hold_expires': 'Libéré dans {time} sauf confirmation',
    'investor.wallet.c3.deposits': 'Dépôts',
    'investor.wallet.c3.deposit_from': 'Dépôt depuis {method}',
    'investor.wallet.c3.intent.pending': 'Pas encore confirmé',
    'investor.wallet.c3.intent.unknown':
        'Pas encore confirmé — nous vérifions auprès du prestataire',
    'investor.wallet.c3.intent.succeeded': 'Crédité',
    'investor.wallet.c3.intent.failed': "Non abouti — rien n'a été crédité",
    'investor.wallet.c3.entry.deposit_in': 'Dépôt depuis {counterparty}',
    'investor.wallet.c3.entry.deposit_out': 'Virement vers {counterparty}',
    'investor.wallet.c3.entry.hold': 'Réservé pour {name}',
    'investor.wallet.c3.entry.hold_release': 'Réservation libérée · {name}',
    'investor.wallet.c3.entry.commitment': 'Engagé dans {name}',
    'investor.wallet.c3.entry.commitment_refund': 'Remboursement · {name}',
    'investor.wallet.c3.movement': 'Type de mouvement',
    'investor.wallet.c3.filter.external': 'Entrées et sorties',
    'investor.wallet.c3.filter.internal': 'Réservations et engagements',
    'investor.wallet.c3.transfer': '{from} → {to}',
    'investor.wallet.c3.older': 'Afficher plus ancien',
    'investor.wallet.c3.receipt_policy': 'Version de la règle',
    'investor.wallet.c3.not_credited':
        "Enregistré, pas encore confirmé. Rien n'a été crédité ; ce ne sera fait qu'après confirmation du paiement.",
    'investor.wallet.c3.not_credited_failed':
        "Le paiement a été confirmé comme non effectué. Rien n'a été crédité.",
    'investor.wallet.c3.credit_receipt': 'Reçu de crédit',
    'business.publish.intro':
        "{title} sera visible des investisseurs dès que chaque étape ci-dessous sera remplie. La publication reprend les signatures données à la revue : il n'y a rien de plus à signer ici.",
    'business.publish.release.awaiting':
        "En attente de la revue du personnel Rozine. Le personnel ne libère une demande que si le moteur de notation, le pouvoir de signature et le rapport d'audit sont tous conformes.",
    'business.publish.release.released':
        'Libérée pour la mise en ligne par le personnel Rozine.',
    'business.publish.release.refused': 'Non libérée pour la mise en ligne',
    'business.publish.cause.ENGINE_GATE_FAILED':
        'Les critères de crédit du moteur de notation ne sont pas remplis.',
    'business.publish.cause.AUTHORITY_CHANGED':
        "Le pouvoir de signature de l'entreprise a changé depuis votre signature.",
    'business.publish.cause.REPORT_NOT_CURRENT':
        "Le rapport d'audit n'est plus à jour.",
    'business.publish.cause.EXPOSURE_RESERVATION_REQUIRED':
        "Aucune réservation d'emprunt n'est enregistrée pour cette demande : elle ne peut pas être publiée.",
    'business.publish.cause.SIGNATURES_REQUIRED':
        "Tous les signataires requis n'ont pas signé à l'étape Revue.",
    'business.publish.cause.STAFF_RELEASE_REQUIRED':
        "L'équipe Rozine n'a pas encore autorisé cette demande.",
    'business.publish.cause.TERMS_CHANGED':
        'Les conditions ont changé depuis votre signature.',
    'business.publish.cause.FEE_DISCLOSURE_CHANGED':
        "L'information sur les frais de publication a changé. Lisez la version actuelle avant de publier.",
    'business.publish.cause.QUOTE_STALE': "Votre offre n'est plus à jour.",
    'business.publish.cause.RESTRICTION_ACTIVE':
        "Une restriction s'applique à cette entreprise.",
    'business.publish.cause.RELEASE_CHECK_NOT_COMPLETED':
        "Une vérification d'autorisation n'a pas pu être menée à terme : la demande ne peut pas encore être autorisée.",
    'business.publish.cause.other':
        "Une vérification de libération n'a pas abouti.",
    'business.publish.prerequisites': 'Avant de publier',
    'business.publish.prerequisite.staff_release':
        'Libérée par le personnel Rozine après revue',
    'business.publish.prerequisite.signatures_retained':
        'Vos signatures de la revue sont enregistrées',
    'business.publish.prerequisite.quote_current':
        'Offre inchangée depuis votre signature',
    'business.publish.prerequisite.terms_current':
        'Conditions inchangées depuis votre signature',
    'business.publish.met': 'Fait',
    'business.publish.not_met': 'Pas encore',
    'business.publish.fee_label': 'Frais de mise en ligne',
    'business.publish.fee_waived': 'Supprimés pour le MVP',
    'business.publish.disclosure_title': 'Information sur les frais',
    'business.publish.disclosure_version': 'Information {version}',
    'business.publish.changed':
        "L'offre ou les conditions ont changé depuis votre signature. Relisez-les et signez à nouveau avant de publier.",
    'business.publish.review_again': 'Relire et signer à nouveau',
    'business.publish.blocked':
        "La publication s'ouvrira dès que chaque étape ci-dessus sera remplie.",
    'business.publish.published.title': 'En ligne pour les investisseurs',
    'business.publish.published.body':
        "{title} est en ligne sur le fil des investisseurs. Rien n'a été facturé.",
    'business.publish.published.receipt': 'Reçu de mise en ligne',
    'business.publish.published.reference': 'Référence',
    'business.publish.published.recorded': 'Enregistré',
    'business.publish.published.disclosure': 'Information sur les frais',
    'business.publish.published.campaign': 'Voir la campagne',
    'business.publish.published.home': "Retour à l'accueil",
    'business.publish.fee_unavailable':
        'Conditions de frais indisponibles — vous ne pouvez pas encore publier',
    'business.publish.fee_unavailable_body':
        "Rozine n'a pas encore publié les conditions des frais de service pour cette levée : elle ne peut donc pas encore être mise en ligne. Rien n'a été publié.",
    'business.campaign.state.live': 'En ligne',
    'business.campaign.state.fully_reserved': 'Entièrement réservée',
    'business.campaign.state.funded': 'Financée',
    'business.campaign.state.funded_pending_disbursement':
        'Financée · décaissement en attente',
    'business.campaign.state.disbursing': 'Versement en cours',
    'business.campaign.state.issued': 'Titres émis',
    'business.campaign.state.expired': 'Non remplie',
    'business.campaign.state.cancelled': 'Annulée',
    'business.campaign.state.failed_closing': 'Clôturée et remboursée',
    'business.campaign.state.sold_out_pending_settlement':
        'Entièrement engagée',
    'business.campaign.state.inventory_unavailable': 'Aucune note disponible',
    'business.campaign.state.closing_pending_settlement': 'Clôture en cours',
    'business.campaign.state.unavailable': 'Statut indisponible',
    'business.campaign.lifecycle.live': 'Levée · en cours',
    'business.campaign.lifecycle.fully_reserved':
        'Levée · entièrement réservée',
    'business.campaign.lifecycle.sold_out_pending_settlement':
        'Entièrement engagée · règlement en attente',
    'business.campaign.lifecycle.inventory_unavailable':
        'Levée · aucune note disponible',
    'business.campaign.lifecycle.closing_pending_settlement':
        'Échéance passée · clôture en cours',
    'business.campaign.restriction.RESTRICTION_ACTIVE':
        'Restreinte depuis le {date}. Les nouveaux engagements sont suspendus pendant la restriction ; ceux déjà pris restent.',
    'business.campaign.restriction.NOTE_INELIGIBLE':
        'Non éligible à de nouveaux engagements depuis le {date} ; ceux déjà pris restent.',
    'business.campaign.tile.committed': 'Engagé',
    'business.campaign.tile.refunded': 'Remboursé',
    'business.campaign.tracker.title': 'Suivi des engagements',
    'business.campaign.tracker.committed_pct': '{pct} % engagé',
    'business.campaign.not_yet_committed': 'Ni engagé ni réservé',
    'business.campaign.closing_now': 'Clôture en cours',
    'business.campaign.committed': 'Engagé',
    'business.campaign.reserved': 'Réservé',
    'business.campaign.reserved_note':
        "Notes bloquées dans le paiement d'un investisseur. Elles ne sont pas encore confirmées.",
    'business.campaign.units':
        '{committed} notes engagées sur {total} · {reserved} réservées · {available} disponibles · {unavailable} indisponibles',
    'business.campaign.closes': 'Clôture le {date}',
    'business.campaign.fully_reserved':
        'Chaque note disponible est actuellement bloquée dans un paiement. Les nouveaux investisseurs ne peuvent pas réserver pour le moment.',
    'business.campaign.unavailable_note':
        'Les notes indisponibles étaient dans un paiement ou un engagement qui a pris fin. Elles ne sont pas en vente.',
    'business.campaign.sold_out':
        "Toutes les notes sont engagées : aucun nouvel investisseur ne peut participer. La levée n'est pas financée tant que Rozine n'a pas terminé le règlement.",
    'business.campaign.inventory_unavailable':
        "Aucune note n'est disponible à la réservation et aucune n'est bloquée dans un paiement : les nouveaux investisseurs ne peuvent pas s'engager pour le moment.",
    'business.campaign.closing':
        "L'échéance est passée : les nouveaux investisseurs ne peuvent plus s'engager. Rozine clôture la levée et en affichera le résultat ici.",
    'business.campaign.progress_unavailable':
        'La progression de cette levée ne peut pas être affichée pour le moment. Actualisez la page pour réessayer.',
    'business.campaign.tile.deadline': 'Échéance',
    'business.campaign.deadline_passed': 'Échéance passée le {date}',
    'business.campaign.funded':
        'Entièrement financée le {date}. La levée ne peut plus être annulée.',
    'business.campaign.closing_title': 'Décaissement',
    'business.campaign.closing.awaiting.title': 'En attente de décaissement',
    'business.campaign.closing.awaiting.body':
        "Rozine prépare le paiement vers votre compte. Il s'affichera ici une fois envoyé et confirmé.",
    'business.campaign.closing.in_flight.title': 'Pas encore confirmé',
    'business.campaign.closing.in_flight.pending':
        "Le paiement vers votre compte est en cours. Le décaissement s'affichera ici une fois confirmé.",
    'business.campaign.closing.in_flight.unknown':
        "Le résultat du paiement n'est pas encore confirmé. Rien n'est définitif avant cela ; il s'affichera ici une fois confirmé.",
    'business.campaign.disbursed':
        '{amount} ont été décaissés vers {destination}.',
    'business.campaign.disbursed_amount': 'Décaissé',
    'business.campaign.destination': 'Vers',
    'business.campaign.effective_at': 'Effectif',
    'business.campaign.effective_date': "Date de l'échéancier (Kigali)",
    'business.campaign.receipt.title': 'Reçu de décaissement',
    'business.campaign.receipt.amount': 'Montant',
    'business.campaign.receipt.reference': 'Référence',
    'business.campaign.receipt.recorded': 'Enregistré',
    'business.campaign.receipt.view': 'Voir le reçu',
    'business.campaign.closed.expired':
        "Cette levée s'est clôturée le {date} avant d'être entièrement financée. {amount} ont été intégralement rendus aux investisseurs, sans frais.",
    'business.campaign.closed.cancelled':
        'Cette levée a été annulée le {date}. {amount} ont été intégralement rendus aux investisseurs, sans frais.',
    'business.campaign.closed.failed_closing':
        "Cette levée s'est clôturée le {date} sans versement. {amount} ont été intégralement rendus aux investisseurs, sans frais.",
    'business.campaign.closed.expired_none':
        "Cette levée s'est clôturée le {date} avant tout engagement d'investisseur : il n'y avait rien à rembourser.",
    'business.campaign.closed.cancelled_none':
        "Cette levée a été annulée le {date} avant tout engagement d'investisseur : il n'y avait rien à rembourser.",
    'business.campaign.closed.failed_closing_none':
        "Cette levée s'est clôturée le {date} sans versement. Aucun investisseur ne s'était engagé : il n'y avait rien à rembourser.",
    'business.campaign.cancel.open': 'Annuler cette levée',
    'business.campaign.cancel.cancelling': 'Annulation…',
    'business.campaign.cancel.title': 'Annuler cette levée ?',
    'business.campaign.cancel.body':
        "La levée est définitivement close et n'accepte plus d'investisseurs. Cette action est irréversible.",
    'business.campaign.cancel.reason': 'Motif (facultatif)',
    'business.campaign.cancel.confirm': 'Annuler la levée',
    'business.campaign.cancel.keep': 'Continuer la levée',
    'admin.disbursements.col.provider': 'Prestataire',
    'admin.disbursements.state.queued': 'Intention enregistrée · en file',
    'admin.disbursements.state.succeeded': 'Payé · rapproché',
    'admin.disbursements.state.failed_closing': 'Clôture en échec · remboursé',
    'admin.disbursements.provider.none': 'Non envoyé',
    'admin.disbursements.provider.pending': 'En attente · pas encore confirmé',
    'admin.disbursements.provider.unknown': 'Inconnu · pas encore confirmé',
    'admin.disbursements.provider.succeeded': 'Réussi · vérifié',
    'admin.disbursements.provider.failed': 'Échoué · vérifié',
    'admin.disbursements.action.authorize': 'Autoriser',
    'admin.disbursements.older': 'Décaissements plus anciens',
    'admin.disbursements.deadline': 'Échéance',
    'admin.disbursements.deadline_unavailable': 'Aucune échéance établie',
    'admin.disbursements.rule_two_staff':
        "Deux membres du personnel différents sont nécessaires : l'un autorise, l'autre approuve. Il n'y a ni seuil de montant ni dérogation. Les rôles ne sont que des libellés : ce que vous pouvez faire dépend de vos permissions.",
    'admin.disbursements.causes': 'Causes',
    'admin.disbursements.receipt.code': 'Reçu',
    'admin.disbursements.receipt.reference': 'Référence',
    'admin.disbursements.receipt.amount': 'Montant',
    'admin.disbursements.receipt.recorded_at': 'Enregistré le',
    'admin.disbursements.receipt.revision': 'Révision',
    'admin.disbursements.check.not_run': 'Pas encore exécuté',
    'admin.disbursements.check.passed': 'Réussi',
    'admin.disbursements.check.failed': 'Échoué',
    'admin.disbursements.precheck.title': 'Contrôle préalable',
    'admin.disbursements.checked_at': 'Contrôlé le',
    'admin.disbursements.policy_version': 'Version de la politique',
    'admin.disbursements.binding.title': "Ce que l'approbation engage",
    'admin.disbursements.binding.none':
        'Défini lorsque le décaissement est autorisé.',
    'admin.disbursements.binding.revision': 'Révision',
    'admin.disbursements.binding.amount': 'Montant exact',
    'admin.disbursements.binding.digest': "Empreinte de l'intention",
    'admin.disbursements.step_up.unavailable':
        "L'approbation exige une nouvelle confirmation renforcée liée à ces détails. Cette confirmation n'est pas encore disponible : l'approbation ne peut donc pas être donnée ici.",
    'admin.disbursements.step_up.title': "Confirmez que c'est bien vous",
    'admin.disbursements.step_up.lead':
        "Saisissez le code à six chiffres de votre application d'authentification. La confirmation est liée à cette révision, au montant exact, à la destination et à l'empreinte de l'intention, et ne sert qu'une fois.",
    'admin.disbursements.step_up.code_label':
        "Code d'authentification à six chiffres",
    'admin.disbursements.step_up.verify': 'Confirmer le code',
    'admin.disbursements.step_up.ready':
        "Confirmé jusqu'à {time}. La confirmation ne sert qu'une fois, pour cette approbation, et exige encore votre motif.",
    'admin.disbursements.step_up.wrong_code':
        'Ce code ne correspond pas. Saisissez le code actuel de votre authentificateur.',
    'admin.disbursements.step_up.lost':
        "Impossible de joindre Rozine pour vérifier votre code. Rien n'a été approuvé — saisissez un nouveau code pour une nouvelle confirmation.",
    'admin.disbursements.step_up.changed':
        'Les détails liés à cette approbation ont changé : votre confirmation a donc été effacée. Vérifiez-les, puis saisissez un nouveau code.',
    'admin.disbursements.intent.title': 'Intention de paiement',
    'admin.disbursements.intent.not_payment':
        "Intention enregistrée — ce n'est pas un paiement. Le service de paiement ne l'envoie qu'après sa propre revérification.",
    'admin.disbursements.intent.not_sent':
        "Pas encore envoyé : le service n'a pas encore envoyé ce paiement.",
    'admin.disbursements.operation': 'Opération',
    'admin.disbursements.dispatch.title': 'Envoi',
    'admin.disbursements.dispatch.sent_at': 'Envoyé le',
    'admin.disbursements.dispatch.recheck': 'Revérification du service',
    'admin.disbursements.outcome.title': 'Résultat du prestataire',
    'admin.disbursements.outcome.pending':
        "En attente : le prestataire n'a pas confirmé de résultat. Ce n'est ni payé ni en échec.",
    'admin.disbursements.outcome.unknown':
        "Inconnu : le résultat du prestataire n'est pas confirmé. Ce n'est ni payé ni en échec.",
    'admin.disbursements.outcome.succeeded':
        'Réussi : le prestataire a vérifié le paiement.',
    'admin.disbursements.outcome.failed':
        'Échoué : le prestataire a vérifié un échec définitif.',
    'admin.disbursements.outcome.failed_unreconciled':
        "Pas encore rapproché : rien n'est clôturé ni remboursé tant que l'échec n'est pas rapproché.",
    'admin.disbursements.outcome.exception':
        "Exception de rapprochement : la réponse du prestataire est contradictoire ou ne peut être résolue. Ce décaissement reste bloqué et n'est pas rapproché.",
    'admin.disbursements.outcome.reference': 'Référence du prestataire',
    'admin.disbursements.outcome.error_code': "Code d'erreur",
    'admin.disbursements.outcome.observed_at': 'Observé le',
    'admin.disbursements.outcome.effective_at': 'Effectif le',
    'admin.disbursements.outcome.reconciliation': 'Rapprochement',
    'admin.disbursements.outcome.reconciled_at': 'Rapproché le',
    'admin.disbursements.outcome.requery_note':
        "Interroger à nouveau le prestataire porte sur cette même opération. Le paiement n'est jamais renvoyé. Un rapprochement planifié l'interroge aussi.",
    'admin.disbursements.reconciliation.unreconciled': 'Pas encore rapproché',
    'admin.disbursements.reconciliation.matched': 'Rapproché',
    'admin.disbursements.reconciliation.exception':
        'Exception · bloqué, non rapproché',
    'admin.disbursements.hold.title': 'Suspension',
    'admin.disbursements.hold.release_note':
        "Lever la suspension n'approuve pas ce décaissement et n'envoie aucun paiement. Il faut un membre du personnel autre que celui qui l'a posée.",
    'admin.disbursements.hold.self':
        'Vous avez posé cette suspension : un autre membre du personnel doit la lever.',
    'admin.disbursements.issue.title': 'Émission',
    'admin.disbursements.issue.holdings': 'Titres émis',
    'admin.disbursements.issue.issued_at': 'Émis le',
    'admin.disbursements.issue.effective_date': "Date d'effet (Kigali)",
    'admin.disbursements.refund.title': 'Remboursements',
    'admin.disbursements.refund.commitments': 'Engagements remboursés',
    'admin.disbursements.refund.total': 'Total remboursé',
    'admin.disbursements.ledger': 'Ouvrir dans le grand livre',
    'admin.disbursements.command.release_hold': 'Lever la suspension',
    'admin.disbursements.command.requery':
        'Interroger à nouveau le prestataire',
    'admin.disbursements.stage.release_hold.title': 'Lever cette suspension',
    'admin.disbursements.stage.release_hold.body':
        "Lever la suspension n'approuve pas ce décaissement et n'envoie aucun paiement. Il revient là où il en était avant la suspension.",
    'admin.disbursements.stage.release_hold.cta': 'Lever la suspension',
    'admin.disbursements.stage.release_hold.placeholder':
        "ex. L'entreprise a confirmé son nouveau numéro MoMo.",
    'admin.disbursements.stage.requery.title':
        'Interroger à nouveau le prestataire',
    'admin.disbursements.stage.requery.body':
        "Cela interroge le prestataire sur la même opération. Le paiement n'est jamais renvoyé, et rien n'est marqué comme rapproché : la réponse passe par le rapprochement comme toute autre.",
    'admin.disbursements.stage.requery.cta': 'Interroger le prestataire',
    'admin.disbursements.stage.requery.placeholder':
        "ex. La page d'état du prestataire indique que la panne est terminée.",
    'admin.applications.release.title': 'Libération pour la mise en ligne',
    'admin.applications.release.state.awaiting_staff_review':
        "En attente d'examen",
    'admin.applications.release.state.released': 'Libéré',
    'admin.applications.release.state.refused': 'Refusé',
    'admin.applications.release.explain':
        "La libération permet à l'entreprise de publier cette levée. Les contrôles du moteur, du mandat et du rapport doivent réussir, et elle ne peut pas passer outre un contrôle en échec.",
    'admin.applications.release.gates': 'Contrôles de libération',
    'admin.applications.release.gate.engine': "Moteur d'évaluation",
    'admin.applications.release.gate.authority': "Mandat de l'entreprise",
    'admin.applications.release.gate.report':
        "Rapport d'audit de mise en ligne",
    'admin.applications.release.gate_state.passed': 'Réussi',
    'admin.applications.release.gate_state.failed': 'Bloqué',
    'admin.applications.release.blocked':
        'Un contrôle a échoué : cette demande ne peut pas être libérée. La libération ne passe jamais outre un contrôle en échec.',
    'admin.applications.release.receipt': 'Reçu de libération',
    'admin.applications.release.command': 'Libérer pour la mise en ligne',
    'admin.applications.release.stage.title': 'Libérer cette demande',
    'admin.applications.release.stage.body':
        "L'entreprise pourra alors publier sa levée. Rien n'est mis en ligne ni financé tant qu'elle ne l'a pas fait.",
    'admin.applications.release.stage.cta': 'Libérer',
    'admin.applications.release.stage.placeholder':
        'ex. Moteur, mandat et rapport scellé tous confirmés.',
    'investor.audit.tolerance': 'Tolérance encadrée',
    'investor.audit.reconciliation': 'DÉCLARATION DE RAPPROCHEMENT',
    'investor.deal.ebitda_unavailable': 'Indisponible',
    'investor.deal.ebitda_not_sourced': "Non établi en tant qu'EBITDA",
    'investor.deal.photos_none': "L'entreprise n'a publié aucune photo.",
    'investor.deal.photo_unavailable': 'Image indisponible',
    'investor.deal.restriction.NOTE_INELIGIBLE':
        "Investissement suspendu : ce titre n'est pas éligible pour le moment",
    'investor.deal.restriction.RESTRICTION_ACTIVE':
        "Investissement suspendu : une restriction s'applique",
    'investor.deal.restriction.body':
        "Depuis le {date}. La levée elle-même se poursuit en l'état ; les nouvelles réservations attendent la levée de la restriction.",
    'investor.deal.lifecycle.live': 'En cours',
    'investor.deal.lifecycle.fully_reserved': 'Entièrement réservé',
    'investor.deal.lifecycle.sold_out_pending_settlement':
        'Entièrement engagée',
    'investor.deal.lifecycle.inventory_unavailable': 'Aucun titre disponible',
    'investor.deal.lifecycle.closing_pending_settlement': 'Clôture en cours',
    'investor.deal.lifecycle.funded': 'Entièrement financé',
    'investor.deal.lifecycle.disbursing': 'Versement en cours',
    'investor.deal.lifecycle.issued': 'Titres émis',
    'investor.deal.lifecycle.expired': 'Non rempli',
    'investor.deal.lifecycle.cancelled': 'Annulé',
    'investor.deal.lifecycle.failed_closing': 'Clôturé et remboursé',
    'investor.deal.notice.fully_reserved.title':
        'Tous les titres sont réservés pour le moment',
    'investor.deal.notice.fully_reserved.body':
        "Tous les titres disponibles sont actuellement réservés dans les paiements d'autres investisseurs. Revenez plus tard.",
    'investor.deal.notice.sold_out_pending_settlement.title':
        'Tous les titres sont engagés',
    'investor.deal.notice.sold_out_pending_settlement.body':
        "Les investisseurs se sont engagés sur tous les titres. Le règlement n'est pas encore enregistré ; les titres ne sont émis qu'après le versement des fonds à l'entreprise.",
    'investor.deal.notice.inventory_unavailable.title':
        "Aucun titre n'est disponible pour le moment",
    'investor.deal.notice.inventory_unavailable.body':
        'Aucun titre ne peut être réservé pour le moment. Revenez plus tard.',
    'investor.deal.notice.closing_pending_settlement.title':
        'Cette levée est close',
    'investor.deal.notice.closing_pending_settlement.body':
        "L'échéance est passée et la levée est en cours de clôture. Tout remboursement dû apparaît dans votre portefeuille dès qu'il est enregistré.",
    'investor.deal.notice.funded.title': 'Entièrement financé',
    'investor.deal.notice.funded.body':
        "Les engagements sont verrouillés pendant le versement à l'entreprise. Les titres sont émis une fois ce paiement confirmé.",
    'investor.deal.notice.disbursing.title':
        "Versement à l'entreprise en cours",
    'investor.deal.notice.disbursing.body':
        "Le paiement à l'entreprise n'est pas encore confirmé. Les titres ne sont émis qu'après confirmation.",
    'investor.deal.notice.issued.title': 'Cette levée est clôturée',
    'investor.deal.notice.issued.body':
        "L'entreprise a été payée et les titres ont été émis à ses investisseurs.",
    'investor.deal.notice.expired.title':
        "Cette levée n'a pas été remplie à temps",
    'investor.deal.notice.expired.body':
        'Chaque engagement a été remboursé intégralement, sans frais.',
    'investor.deal.notice.cancelled.title': "L'entreprise a annulé cette levée",
    'investor.deal.notice.cancelled.body':
        'Chaque engagement a été remboursé intégralement, sans frais.',
    'investor.deal.notice.failed_closing.title':
        "Cette levée s'est clôturée sans versement",
    'investor.deal.notice.failed_closing.body':
        "Le versement à l'entreprise n'a pas eu lieu : chaque engagement a été remboursé intégralement.",
    'investor.deals.paused': 'Suspendu',
    'investor.deal.cap.max': {
        one: "Jusqu'à {count} titre : {reason}.",
        other: "Jusqu'à {count} titres : {reason}.",
    },
    'investor.deal.cap.none':
        'Vous ne pouvez plus prendre de titres ici : {reason}.',
    'investor.deal.cap.reason.raise_cap':
        'votre plafond par investisseur pour cette levée',
    'investor.deal.cap.reason.availability':
        'ce sont tous les titres encore disponibles',
    'investor.deal.cap.reason.restriction': "une restriction s'applique",
    'investor.deal.cap.reason.connected_party':
        'vous êtes lié à cette entreprise',
    'investor.updates.published_photos': 'PHOTOS PUBLIÉES',
    'investor.checkout.c3.processing': 'Envoi…',
    'investor.checkout.c3.hold_ended':
        'Votre réservation est terminée — ces titres ont peut-être été libérés',
    'investor.checkout.c3.hold_left':
        'Réservé pour vous · {time} pour confirmer',
    'investor.checkout.c3.committed': 'Engagé',
    'investor.checkout.c3.committed_body':
        "Engagé — vos titres sont émis après le paiement de l'entreprise. D'ici là, c'est un engagement, pas une détention.",
    'investor.checkout.c3.view_awaiting': "Voir dans « En attente d'émission »",
    'investor.checkout.c3.reserved_title': 'Confirmez vos titres',
    'investor.checkout.c3.held_amount': 'Réservé sur le disponible',
    'investor.checkout.c3.release': 'Libérer ces titres',
    'investor.checkout.c3.confirm_fine_print':
        "Confirmer engage le montant réservé. Vous pouvez annuler sans frais jusqu'au financement complet de la levée.",
    'investor.checkout.c3.at_maturity': {
        one: 'Retour sur {count} mois',
        other: 'Retour sur {count} mois',
    },
    'investor.checkout.c3.indicative':
        "Indicatif jusqu'à la réservation : les droits exacts de vos titres sont fixés à la réservation.",
    'investor.checkout.c3.reserve': 'Réserver · {amount}',
    'investor.checkout.c3.reserve_unavailable':
        "La réservation n'est pas disponible pour le moment.",
    'investor.checkout.c3.reserve_fine_print':
        'Réserver bloque les titres et le montant pendant 5 minutes, le temps de confirmer.',
    'investor.primary.status.confirmed': 'Engagé — émis après le versement',
    'investor.primary.status.awaiting_disbursement':
        'Entièrement financé — versement en attente',
    'investor.primary.status.in_flight_pending':
        'Versement pas encore confirmé',
    'investor.primary.status.in_flight_unknown':
        'Versement pas encore confirmé — vérification',
    'investor.primary.status.issued': 'Émis',
    'investor.primary.status.cancelled': 'Annulé — remboursé',
    'investor.primary.status.expired': 'Non rempli — remboursé',
    'investor.primary.status.failed_closing': 'Clôturé — remboursé',
    'investor.primary.status_body.confirmed':
        "La levée est encore ouverte. Vous pouvez annuler sans frais jusqu'à son financement complet.",
    'investor.primary.status_body.awaiting_disbursement':
        "La levée est entièrement financée : l'annulation est verrouillée. Les titres sont émis une fois le versement confirmé.",
    'investor.primary.status_body.in_flight_pending':
        "Le versement à l'entreprise a été envoyé et n'est pas encore confirmé. Rien n'est émis avant.",
    'investor.primary.status_body.in_flight_unknown':
        "Nous ne savons pas encore si le versement à l'entreprise a abouti et nous vérifions. Il n'est ni payé, ni échoué, ni remboursé ; rien n'est émis avant confirmation.",
    'investor.primary.status_body.issued':
        "L'entreprise a été payée et vos titres ont été émis.",
    'investor.primary.status_body.cancelled':
        'Annulé par {by}. Votre capital a été remboursé intégralement, sans frais.',
    'investor.primary.status_body.expired':
        "La levée n'a pas été remplie à temps. Votre capital a été remboursé intégralement, sans frais.",
    'investor.primary.status_body.failed_closing':
        "Le versement à l'entreprise n'a pas eu lieu. Votre capital a été remboursé intégralement, sans frais.",
    'investor.primary.cancelled_by.investor': 'vous',
    'investor.primary.cancelled_by.business': "l'entreprise",
    'investor.primary.cancelled_by.none': 'Rozine',
    'investor.primary.receipt.amount': 'Montant',
    'investor.primary.receipt.recorded': 'Enregistré',
    'investor.primary.receipt.reference': 'Référence',
    'investor.primary.receipt.confirmation': 'REÇU DE CONFIRMATION',
    'investor.primary.receipt.refund': 'REÇU DE REMBOURSEMENT',
    'investor.primary.units': 'Titres',
    'investor.primary.units_value': {
        one: '{count} titre · {ordinals}',
        other: '{count} titres · {ordinals}',
    },
    'investor.primary.units_short': {
        one: '{count} titre',
        other: '{count} titres',
    },
    'investor.primary.principal': 'Capital',
    'investor.primary.terms': 'Conditions',
    'investor.primary.maturity': "Date d'échéance",
    'investor.primary.maturity_at_issue': "Fixée à l'émission des titres",
    'investor.primary.versions': 'Règle · information',
    'investor.primary.view_holding': 'Voir la détention',
    'investor.primary.awaiting_issue': "En attente d'émission",
    'investor.primary.awaiting_issue_note':
        'Engagements, pas encore des détentions',
    'investor.primary.rights.title': 'Droits de vos titres',
    'investor.primary.rights.instalment': 'Échéance',
    'investor.primary.rights.principal': 'Capital',
    'investor.primary.rights.return': 'Rendement',
    'investor.primary.rights.nth': 'N° {n}',
    'investor.primary.rights.total_return': 'Rendement total',
    'investor.primary.rights.undated':
        "Les échéances sont fixées à l'émission : la première tombe un mois après la date d'effet du versement.",
    'investor.primary.commitment_title': 'Engagement',
    'investor.primary.back_to_portfolio': 'Retour au portefeuille',
    'investor.primary.cancel': "Annuler l'engagement",
    'investor.primary.cancel_title': 'Annuler cet engagement ?',
    'investor.primary.cancel_body':
        'Votre capital revient intégralement sur le disponible, sans frais. Vous ne détiendrez plus ces titres.',
    'investor.primary.cancel_confirm': 'Oui, annuler et rembourser',
    'investor.primary.cancel_keep': 'Le conserver',
    'investor.holding.issue.title': "Relevé d'émission",
    'investor.holding.issue.issued_at': 'Émis le',
    'investor.holding.issue.effective_at': 'Versement effectif',
    'investor.holding.issue.effective_date': "Date d'effet (Kigali)",
    'investor.holding.issue.schedule': 'Échéancier',
    'investor.holding.issue.due': 'Échéance',
    'auth.two_factor.recovery_codes_remaining': {
        one: '{count} code de récupération restant',
        other: '{count} codes de récupération restants',
    },
    'business.audit_cosign.head_title': "Cosigner le rapport d'audit",
    'business.audit_cosign.title': "Cosigner le rapport d'audit",
    'business.audit_cosign.back': 'Retour',
    'business.audit_cosign.lead':
        "Votre expert-comptable a scellé ce rapport après l'audit sur place. Lisez les constats factuels avant de cosigner.",
    'business.audit_cosign.kind.monthly': "Rapport d'audit mensuel",
    'business.audit_cosign.kind.flash': "Rapport d'audit flash",
    'business.audit_cosign.period': 'Période',
    'business.audit_cosign.auditor': "Partenaire d'audit",
    'business.audit_cosign.auditor_value': '{name} · licence {licence}',
    'business.audit_cosign.procedure': 'Procédure',
    'business.audit_cosign.digest': 'Empreinte du rapport',
    'business.audit_cosign.digest_short': '{digest}…',
    'business.audit_cosign.seal.title': 'Sceau',
    'business.audit_cosign.seal.valid': 'Sceau valide',
    'business.audit_cosign.seal.valid_at': 'Scellé le {date}',
    'business.audit_cosign.seal.verify': 'Vérifier le sceau',
    'business.audit_cosign.seal.unavailable':
        'Le sceau ne peut pas être vérifié pour le moment.',
    'business.audit_cosign.note_title': "Note du partenaire d'audit",
    'business.audit_cosign.findings': 'Constats factuels',
    'business.audit_cosign.findings_empty': "Aucun constat n'a été enregistré.",
    'business.audit_cosign.evidence': {
        one: '{count} élément de preuve',
        other: '{count} éléments de preuve',
    },
    'business.audit_cosign.sealed_note':
        'Ce rapport est scellé. Rien sur cette page ne le modifie.',
    'business.audit_cosign.status.title': 'Cosignatures',
    'business.audit_cosign.status.count':
        '{signed} signature(s) sur {required}',
    'business.audit_cosign.status.signers': 'Signataires',
    'business.audit_cosign.status.you': 'Vous',
    'business.audit_cosign.status.signed_on': 'Signé · {date}',
    'business.audit_cosign.status.signed': 'Signé',
    'business.audit_cosign.status.pending': 'En attente',
    'business.audit_cosign.due': 'À cosigner avant le {date}',
    'business.audit_cosign.overdue':
        'En retard — la cosignature était due avant le {date}',
    'business.audit_cosign.published': 'Publié le {date}',
    'business.audit_cosign.yours.title': 'Votre cosignature',
    'business.audit_cosign.yours.accept':
        "J'ai examiné les constats d'audit et je cosigne ce rapport.",
    'business.audit_cosign.yours.note': 'Votre résumé (facultatif)',
    'business.audit_cosign.yours.note_help':
        'Conservé avec votre signature. Il ne modifie pas le rapport scellé.',
    'business.audit_cosign.yours.identity':
        'Votre compte vérifié signe. Chaque signataire requis cosigne séparément.',
    'business.audit_cosign.yours.submit': 'Cosigner le rapport',
    'business.audit_cosign.yours.submitting': 'Cosignature…',
    'business.audit_cosign.yours.signed': 'Vous avez cosigné ce rapport.',
    'business.audit_cosign.yours.signed_on': 'Vous avez cosigné le {date}.',
    'business.audit_cosign.yours.waiting':
        'En attente de la cosignature de {names}. Le rapport est publié dès que toutes les signatures requises sont réunies.',
    'business.audit_cosign.yours.all_in':
        'Toutes les signatures requises sont réunies. Le rapport est publié dès que ses contrôles de publication sont validés.',
    'business.audit_cosign.yours.published':
        'Toutes les signatures requises sont réunies et le rapport est publié.',
    'business.audit_cosign.yours.unavailable':
        "Ce rapport n'est pas ouvert à la cosignature pour le moment.",
    'business.audit_cosign.yours.cannot':
        'Vous ne pouvez pas cosigner ce rapport.',
    'business.audit_cosign.refused.with_code': '{reason} ({code})',
    'business.audit_cosign.refused.VERSION_CONFLICT':
        'Les signatures de ce rapport ont changé pendant que vous y travailliez. Nous avons chargé la dernière version — vérifiez-la et réessayez.',
    'business.audit_cosign.refused.IDEMPOTENCY_CONFLICT':
        "Cette demande a déjà été utilisée avec d'autres informations ; elle n'a donc pas été renvoyée. Nous avons chargé la dernière version.",
    'business.audit_cosign.refused.DIGEST_STALE':
        "Le rapport que vous avez lu n'est plus la version en vigueur. Nous l'avons chargé — lisez-le et réessayez.",
    'business.audit_cosign.refused.MANDATE_STALE':
        'Le mandat de signature de la société a changé. Vérifiez qui peut signer désormais, puis réessayez.',
    'business.audit_cosign.refused.ACTION_FORBIDDEN':
        'Vous ne pouvez pas effectuer cette action pour cette entreprise.',
    'business.audit_cosign.refused.MANDATE_REQUIRED':
        'Votre mandat vérifié ne vous permet pas de signer pour cette entreprise.',
    'business.audit_cosign.refused.NOT_FOUND':
        'Ce rapport ne vous est plus accessible.',
    'business.audit_cosign.refused.denied':
        'Votre accès a changé. Revenez à vos applications et réessayez.',
    'business.audit_cosign.refused.failed':
        "Cette action n'a pas été enregistrée. Vérifiez le rapport tel qu'il est maintenant et réessayez.",
    'business.audit_prep.seal_by':
        'Votre expert-comptable scelle le rapport de {month} au plus tard le {seal}. Vous ne pouvez ni le lancer ni le modifier.',
    'business.audit_cosign.count': '{count}/{limit}',
    'business.audit_cosign.published_auto':
        'Publié automatiquement après le délai de 24 heures',
    'business.audit_cosign.dispute.open': 'Soumettre une contestation',
    'business.audit_cosign.dispute.submit': 'Soumettre la contestation',
    'business.audit_cosign.dispute.submitting': 'Envoi…',
    'business.audit_cosign.dispute.cancel': 'Annuler',
    'business.audit_cosign.dispute.files_add': 'Ajouter des fichiers',
    'business.audit_cosign.dispute.file_remove': 'Retirer {name}',
    'audit.verify_seal.head_title': "Vérifier le sceau d'audit",
    'audit.verify_seal.title': "Vérification du sceau d'audit",
    'audit.verify_seal.lead':
        "Vérifiez si un rapport d'audit Rozine porte un sceau valide.",
    'audit.verify_seal.valid': 'Sceau vérifié',
    'audit.verify_seal.valid_body':
        'Cette empreinte correspond au rapport scellé.',
    'audit.verify_seal.unavailable':
        'Ce sceau ne peut pas être vérifié pour le moment',
    'audit.verify_seal.unavailable_body': 'Réessayez plus tard.',
    'audit.verify_seal.report_id': 'Identifiant du rapport',
    'audit.verify_seal.digest': 'Empreinte du rapport',
    'audit.verify_seal.amends': 'Modifie le rapport {id}',
    'audit.verify_seal.amended_by': 'Modifié par le rapport {id}',
    'audit.verify_seal.scope':
        "Seuls l'identifiant du rapport, son empreinte et le résultat de la vérification du sceau sont affichés ici.",
    'business.audit_cosign.refused.AUDIT_REPORT_AMENDED':
        "L'auditeur a modifié ce rapport ; il ne peut donc plus être cosigné. Le rapport modifié vous sera soumis pour approbation une fois scellé.",
    'common.file_size.kb': '{size} Ko',
    'common.file_size.mb': '{size} Mo',
    'common.file_size.kind': '{kind} · {size}',
    'common.proof_file.download': 'Télécharger {name}',
    'common.proof_file.download_short': 'Télécharger',
    'business.audit_cosign.published_staff':
        'Publié par le personnel Rozine le {date}',
    'business.audit_cosign.yours.published_auto':
        "Publié automatiquement après la fenêtre de 24 heures : personne ne l'a approuvé ni contesté à temps. Aucune signature n'a été enregistrée.",
    'business.audit_cosign.yours.published_staff':
        "Le personnel Rozine a tranché la contestation et publié le rapport. Aucune signature n'a été enregistrée.",
    'business.audit_cosign.window.title': "Fenêtre d'examen de 24 heures",
    'business.audit_cosign.window.left': '{time} restantes',
    'business.audit_cosign.window.body':
        "Une fois le rapport d'audit scellé dans votre application, vous avez 24 heures pour l'approuver ou soumettre une contestation avec des justificatifs.",
    'business.audit_cosign.window.delivered':
        'Reçu dans votre application le {date}',
    'business.audit_cosign.window.due':
        'Approuvez ou contestez avant le {date}',
    'business.audit_cosign.window.ended':
        'La fenêtre de 24 heures est terminée.',
    'business.audit_cosign.window.auto':
        'Les rapports non signés sont approuvés automatiquement à la fin de la fenêtre.',
    'business.audit_cosign.dispute.intro':
        "Indiquez ce que vous contestez dans les constats et vos justificatifs. Votre contestation ne modifie pas le rapport scellé et suspend la fenêtre de 24 heures pendant l'examen par le CPA.",
    'business.audit_cosign.dispute.proof_rule':
        'Ajoutez un texte justificatif, au moins un fichier, ou les deux.',
    'business.audit_cosign.dispute.supporting': 'Texte justificatif',
    'business.audit_cosign.dispute.supporting_help':
        "Texte simple, jusqu'à 1 000 caractères.",
    'business.audit_cosign.dispute.files': 'Fichiers justificatifs',
    'business.audit_cosign.dispute.files_help':
        "Jusqu'à {limit} fichiers : PDF, JPEG ou PNG, 10 Mo maximum chacun.",
    'business.audit_cosign.dispute.file_type':
        "{name} n'a pas été ajouté : seuls les fichiers PDF, JPEG ou PNG sont acceptés.",
    'business.audit_cosign.dispute.file_size':
        "{name} n'a pas été ajouté : chaque fichier est limité à 10 Mo.",
    'business.audit_cosign.dispute.file_limit':
        "{name} n'a pas été ajouté : une contestation compte au plus {limit} fichiers.",
    'business.audit_cosign.disputed.under_review.title':
        "Contestation en cours d'examen",
    'business.audit_cosign.disputed.under_review.body':
        'Le délai est suspendu. Le CPA examine vos justificatifs.',
    'business.audit_cosign.disputed.escalated.title':
        'Le personnel Rozine examine le dossier',
    'business.audit_cosign.disputed.escalated.body':
        "Le personnel Rozine examine votre contestation. Le délai reste suspendu et le rapport n'est pas publié entre-temps.",
    'business.audit_cosign.disputed.amendment_required.title':
        'Modification requise',
    'business.audit_cosign.disputed.amendment_required.body':
        "L'auditeur doit modifier le rapport ; vous aurez une nouvelle fenêtre de 24 heures. Le délai reste suspendu d'ici là.",
    'business.audit_cosign.disputed.amended.title': 'Rapport modifié',
    'business.audit_cosign.disputed.amended.body':
        "L'auditeur a modifié le rapport. Le rapport modifié a sa propre fenêtre de 24 heures.",
    'business.audit_cosign.disputed.upheld.title':
        'Publié par le personnel Rozine',
    'business.audit_cosign.disputed.upheld.body':
        "Le personnel Rozine a examiné votre contestation, maintenu les constats et publié le rapport. Aucune signature n'a été enregistrée.",
    'business.audit_cosign.disputed.resolved.title': 'Contestation close',
    'business.audit_cosign.disputed.resolved.body':
        'Cette contestation est close.',
    'business.audit_cosign.disputed.open_amendment':
        'Ouvrir le rapport modifié ({report})',
    'business.audit_cosign.disputed.record_title': 'Votre contestation',
    'business.audit_cosign.disputed.submitted': 'Soumise le {date}',
    'business.audit_cosign.disputed.supporting_text':
        'Votre texte justificatif',
    'business.audit_cosign.disputed.files': 'Vos fichiers justificatifs',
    'auditor.sealed.body_disputed':
        "Le rapport est scellé. {party} l'a contesté : sa fenêtre d'examen est suspendue et rien n'est publié tant que la contestation est ouverte.",
    'auditor.sealed.dispute.title': "Contestation de l'entreprise",
    'auditor.sealed.dispute.status.under_review': "En cours d'examen",
    'auditor.sealed.dispute.status.escalated': 'Auprès du personnel Rozine',
    'auditor.sealed.dispute.status.resolved': 'Résolue',
    'auditor.sealed.dispute.submitted': 'Soumise le {date} · {time}',
    'auditor.sealed.dispute.guide.under_review':
        "Examinez d'abord les justificatifs. Puis lancez une modification liée ou maintenez vos constats.",
    'auditor.sealed.dispute.guide.escalated':
        "Le personnel Rozine examine cette contestation. Sceller une modification ne la résout pas tant que le personnel n'a pas indiqué qu'une modification est requise.",
    'auditor.sealed.dispute.guide.amendment_required':
        "Le personnel Rozine exige une modification. Dès que vous scellez une modification liée, l'entreprise dispose d'une nouvelle fenêtre de 24 heures.",
    'auditor.sealed.dispute.supporting_text':
        "Texte justificatif de l'entreprise",
    'auditor.sealed.dispute.files': 'Fichiers justificatifs',
    'auditor.sealed.dispute.outcome.amendment_required':
        'Issue : modification requise',
    'auditor.sealed.dispute.outcome.amended': 'Issue : rapport modifié',
    'auditor.sealed.dispute.outcome.upheld': 'Issue : constats maintenus',
    'auditor.sealed.dispute.uphold': 'Maintenir les constats',
    'auditor.dispute_uphold.lead':
        'Votre motif est transmis au personnel Rozine, qui examine la contestation de {business}. Maintenir les constats ne publie pas le rapport.',
    'auditor.dispute_uphold.label': 'Votre motif (obligatoire)',
    'auditor.dispute_uphold.placeholder':
        "Indiquez, en faits, pourquoi les constats restent valables après l'examen des justificatifs.",
    'auditor.dispute_uphold.submit': 'Transmettre au personnel Rozine',
    'business.audit_cosign.refused.REPORT_WINDOW_CLOSED':
        "La fenêtre d'examen de 24 heures est close : ce rapport ne peut plus être approuvé ni contesté. Nous l'avons rechargé tel qu'il est maintenant.",
    'business.audit_cosign.refused.REPORT_REVIEW_CLOSED':
        "Ce rapport est déjà contesté, examiné par le personnel Rozine ou publié : il ne peut plus être approuvé ni contesté. Nous l'avons rechargé tel qu'il est maintenant.",
    'auditor.sealed.body_published_auto':
        "Publié automatiquement après la fenêtre de 24 heures, le {date}. L'entreprise ne l'a pas cosigné.",
    'auditor.sealed.body_published_staff':
        "Publié par le personnel Rozine le {date}. L'entreprise ne l'a pas cosigné.",
    'auditor.sealed.cosign.not_signed': 'Non cosigné',
    'business.audit_cosign.head_title_read': "Rapport d'audit",
    'business.audit_cosign.lead_read':
        "Votre expert-comptable a scellé ce rapport après l'audit sur place. Voici ses constats factuels.",
    'business.audit_cosign.heading.signed':
        "Rapport d'audit, cosigné et publié",
    'business.audit_cosign.heading.auto_approved':
        "Rapport d'audit, publié automatiquement après la fenêtre de 24 heures",
    'business.audit_cosign.heading.staff_resolved':
        "Rapport d'audit, publié par le personnel Rozine",
    'business.audit_cosign.heading.disputed':
        "Rapport d'audit, contestation en cours d'examen",
    'business.audit_cosign.heading.amended':
        "Rapport d'audit, modifié après votre contestation",
    'business.audit_cosign.heading.dispute_closed':
        "Rapport d'audit, contestation close",
    'business.audit_cosign.heading.unavailable': "Rapport d'audit",
    'business.audit_cosign.disputed.note.cpa': 'Motif du CPA',
    'business.audit_cosign.disputed.note.staff': 'Note du personnel Rozine',
    'business.audit_cosign.disputed.note.unknown': "Note d'examen",
    'auditor.sealed.dispute.note.cpa': 'Votre motif de maintien',
    'auditor.sealed.dispute.note.staff': 'Note du personnel Rozine',
    'auditor.sealed.dispute.note.unknown': "Note d'examen",
    'business.campaign.servicing.state.current': 'Remboursement · à jour',
    'business.campaign.servicing.state.due_today': "Paiement dû aujourd'hui",
    'business.campaign.servicing.state.overdue': 'Paiement en retard',
    'business.campaign.servicing.state.repaid': 'Entièrement remboursé',
    'business.campaign.servicing.state.defaulted': 'En défaut',
    'business.campaign.servicing.dpd': {
        one: '{count} jour de retard',
        other: '{count} jours de retard',
    },
    'business.campaign.servicing.restriction.ARREARS':
        "En arriéré depuis le {date}. La revente de ce titre est suspendue jusqu'à ce que le paiement en retard soit reçu et rapproché.",
    'business.campaign.servicing.restriction.RESTRICTION_ACTIVE':
        "Une restriction s'applique depuis le {date}. Les remboursements restent acceptés.",
    'business.campaign.servicing.payments': '{made} sur {total}',
    'business.campaign.servicing.total': 'Total à rembourser',
    'business.campaign.servicing.instalments_left': {
        one: '{count} échéance restante',
        other: '{count} échéances restantes',
    },
    'business.campaign.servicing.next_title': 'Prochaine échéance',
    'business.campaign.servicing.next_due': 'Échéance {index} · due le {date}',
    'business.campaign.servicing.principal': 'Capital',
    'business.campaign.servicing.return': 'Rendement',
    'business.campaign.servicing.service_fee': 'Frais de service',
    'business.campaign.servicing.next_total': 'Total dû',
    'business.campaign.servicing.open': 'Ouvrir les remboursements',
    'business.campaign.servicing.repaid':
        'Toutes les échéances sont payées : {amount} remboursés au {date}.',
    'investor.servicing.title': 'Remboursement',
    'investor.servicing.state.current': 'À jour',
    'investor.servicing.state.due_today': "Paiement dû aujourd'hui",
    'investor.servicing.state.overdue': 'Paiement en retard',
    'investor.servicing.state.repaid': 'Entièrement remboursé',
    'investor.servicing.state.defaulted': 'En défaut',
    'investor.servicing.dpd': {
        one: '{count} jour de retard',
        other: '{count} jours de retard',
    },
    'investor.servicing.restriction.ARREARS': 'En arriéré depuis le {date}',
    'investor.servicing.restriction.DEFAULT': 'En défaut depuis le {date}',
    'investor.servicing.restriction.DISPUTED': 'Contesté depuis le {date}',
    'investor.servicing.restriction.RESTRICTION_ACTIVE':
        'Restreint depuis le {date}',
    'investor.servicing.restriction.NOTE_INELIGIBLE':
        'Titre inéligible depuis le {date}',
    'investor.servicing.received.principal': 'Capital reçu',
    'investor.servicing.received.return': 'Rendement reçu',
    'investor.servicing.received.late_fees': 'Pénalités de retard reçues',
    'investor.servicing.received.fees': 'Frais Rozine',
    'investor.servicing.received.net': 'Net reçu',
    'investor.servicing.outstanding_principal': 'Capital restant dû',
    'investor.servicing.remaining_projected': 'Encore prévu',
    'investor.servicing.projection': 'Prévu, non garanti',
    'investor.servicing.next_payment': 'Prochain paiement',
    'investor.servicing.next_payment_value': '{amount} · {date}',
    'investor.servicing.basis': 'Base de calcul',
    'investor.servicing.basis_for': 'Base de calcul : {figure}',
    'investor.servicing.instalments': 'Échéances',
    'investor.servicing.instalment': 'Échéance {index} · due le {date}',
    'investor.servicing.instalment_status.upcoming': 'À venir',
    'investor.servicing.instalment_status.due': 'Dû',
    'investor.servicing.instalment_status.overdue': 'En retard',
    'investor.servicing.instalment_status.processing':
        'En traitement · pas encore versé',
    'investor.servicing.instalment_status.partially_paid': 'Payé en partie',
    'investor.servicing.instalment_status.paid': 'Payé',
    'investor.servicing.entitled':
        'Votre part : capital {principal} · rendement {return}',
    'investor.servicing.paid':
        'Versé : capital {principal} · rendement {return}',
    'investor.servicing.payout_net': 'Versement net {amount}',
    'investor.servicing.late_fees.title':
        'Pénalités de retard sur ce placement',
    'investor.servicing.late_fees.line': 'Échéance {index} · {step} · {rate} %',
    'investor.servicing.late_fees.step.due_date': "Date d'échéance",
    'investor.servicing.late_fees.step.day_7': 'Jour 7',
    'investor.servicing.late_fees.step.day_30': 'Jour 30',
    'investor.servicing.late_fees.status.projected': 'Prévu',
    'investor.servicing.late_fees.status.assessed': 'Facturée · non encaissée',
    'investor.servicing.late_fees.status.partially_collected':
        'Encaissée en partie',
    'investor.servicing.late_fees.status.collected': 'Encaissée',
    'investor.servicing.late_fees.status.waived': 'Annulée',
    'investor.servicing.late_fees.applies': "S'applique à partir du {date}",
    'investor.servicing.late_fees.assessed': 'Votre part',
    'investor.servicing.late_fees.collected': 'Encaissé',
    'investor.servicing.late_fees.outstanding': 'Pas encore encaissé',
    'investor.servicing.late_fees.terms': 'Conditions',
    'investor.servicing.late_fees.collection_only':
        'Payable seulement si encaissée',
    'investor.servicing.late_fees.not_guaranteed': 'Non garantie par Rozine',
    'investor.servicing.late_fees.payout': 'Voir le versement',
    'investor.servicing.late_fees.total.assessed': 'Votre part, toutes lignes',
    'investor.servicing.late_fees.total.collected': 'Encaissé',
    'investor.servicing.late_fees.total.outstanding': 'Pas encore encaissé',
    'investor.servicing.late_fees.disclosure':
        "Les pénalités de retard vous sont versées seulement si et quand l'entreprise les paie. Rozine ne les garantit pas. Mention {version}.",
    'investor.servicing.payouts.title': 'Versements',
    'investor.servicing.payouts.empty': 'Aucun versement pour le moment.',
    'investor.servicing.payouts.row': 'Échéance {index} · crédité le {date}',
    'investor.servicing.payouts.gross': 'Brut',
    'investor.servicing.payouts.fee': '{name} ({rate} %)',
    'investor.servicing.payouts.fee_code.INVESTOR_REPAYMENT_FEE':
        'Frais de remboursement',
    'investor.servicing.payouts.fee_code.PLUS_EARNINGS_FEE':
        'Frais sur les gains',
    'investor.servicing.payouts.net': 'Net sur votre portefeuille',
    'investor.servicing.payouts.receipt': 'Reçu {reference}',
    'investor.servicing.exit.title': 'Options de sortie',
    'investor.servicing.exit.unavailable':
        "La vente de ce placement n'est pas disponible.",
    'investor.servicing.exit.cause.FEATURE_DISABLED':
        "La revente n'est pas encore ouverte",
    'investor.servicing.exit.cause.MARKET_HALTED': 'Le marché est suspendu',
    'investor.servicing.exit.cause.NOTE_HALTED': 'Ce titre est suspendu',
    'investor.servicing.exit.cause.ARREARS':
        "L'entreprise est en retard de paiement",
    'investor.servicing.exit.cause.DEFAULT': 'Le titre est en défaut',
    'investor.servicing.exit.cause.DISPUTED': 'Le titre est contesté',
    'investor.servicing.exit.cause.RESTRICTION_ACTIVE':
        "Une restriction s'applique",
    'investor.servicing.exit.cause.REPORT_STALE':
        "Le dernier rapport n'est plus à jour",
    'investor.servicing.exit.cause.NO_AVAILABLE_UNITS':
        'Aucune unité disponible à la vente',
    'investor.servicing.exit.cause.NOTE_INELIGIBLE':
        "Le titre n'est pas éligible",
    'investor.servicing.exit.record_date':
        "Prochaine date d'enregistrement {date}",
    'investor.earnings.title': 'Gains',
    'investor.earnings.invested': 'Investi',
    'investor.earnings.outstanding_principal': 'Capital restant dû',
    'investor.earnings.realised': 'Réalisé · versé sur votre portefeuille',
    'investor.earnings.principal_back': 'Capital remboursé',
    'investor.earnings.return': 'Rendement',
    'investor.earnings.late_fees': 'Pénalités de retard',
    'investor.earnings.fees': 'Frais Rozine',
    'investor.earnings.net_return': 'Rendement net',
    'investor.earnings.this_month': 'Ce mois-ci, net',
    'investor.earnings.avg_monthly': 'Moyenne mensuelle, nette',
    'investor.earnings.projected': 'Prévisionnel',
    'investor.earnings.remaining_return': 'Rendement encore prévu',
    'investor.earnings.next_3m': 'Les 3 prochains mois',
    'investor.earnings.next_payout': 'Prochain versement {amount} · {date}',
    'admin.nav.repayments': 'Remboursements',
    'admin.section.repayments.title': 'Remboursements',
    'admin.section.repayments.subtitle':
        'Encaissements sur les titres, rapprochés selon la tolérance de la politique et répartis par le système central. Les exceptions restent bloquées.',
    'admin.section.repayments.search':
        'Rechercher par entreprise, titre ou référence…',
    'admin.repayments.title': 'Remboursements',
    'admin.repayments.table': 'Remboursements',
    'admin.repayments.older': 'Remboursements plus anciens',
    'admin.repayments.empty_title': 'Aucun remboursement pour le moment',
    'admin.repayments.empty_body':
        "Les encaissements apparaissent ici dès qu'une entreprise paie.",
    'admin.repayments.count.due_today': {
        one: "{count} dû aujourd'hui",
        other: "{count} dus aujourd'hui",
    },
    'admin.repayments.count.overdue': {
        one: '{count} en retard',
        other: '{count} en retard',
    },
    'admin.repayments.count.exceptions': {
        one: '{count} exception',
        other: '{count} exceptions',
    },
    'admin.repayments.col.reference': 'Réf. remboursement',
    'admin.repayments.col.source': 'Source',
    'admin.repayments.dpd': 'JDR',
    'admin.repayments.received_at': 'Reçu',
    'admin.repayments.state.received': 'Reçu · répartition en cours',
    'admin.repayments.state.allocated': 'Réparti · rapproché',
    'admin.repayments.state.exception': 'Exception · bloqué',
    'admin.repayments.source.wallet': "Portefeuille de l'entreprise",
    'admin.repayments.source.inbound_receipt': 'Encaissement entrant',
    'admin.repayments.drawer_label': 'Remboursement {reference}',
    'admin.repayments.amount': 'Montant',
    'admin.repayments.dpd_at_receipt': "JDR à l'encaissement",
    'admin.repayments.source.title': 'Source',
    'admin.repayments.source.kind': 'Provenance',
    'admin.repayments.source.wallet_entry': 'Écriture du portefeuille',
    'admin.repayments.source.wallet_note':
        "Un mouvement interne au grand livre : aucun prestataire n'intervient et aucune action du personnel n'est requise.",
    'admin.repayments.source.receipt': 'Encaissement entrant',
    'admin.repayments.provider.state': 'Prestataire',
    'admin.repayments.provider.requery_note':
        "Interroger à nouveau le prestataire porte sur la même opération. Cela n'encaisse jamais l'argent une seconde fois. Un rapprochement programmé interroge aussi.",
    'admin.repayments.reconciliation.title': 'Rapprochement',
    'admin.repayments.reconciliation.unreconciled': 'Pas encore rapproché',
    'admin.repayments.reconciliation.matched': 'Concordant · rapproché',
    'admin.repayments.reconciliation.exception':
        'Exception · bloqué, non rapproché',
    'admin.repayments.reconciliation.expected': 'Attendu',
    'admin.repayments.reconciliation.observed': 'Constaté',
    'admin.repayments.reconciliation.difference': 'Écart',
    'admin.repayments.reconciliation.tolerance': 'Tolérance',
    'admin.repayments.reconciliation.checked_at': 'Vérifié le',
    'admin.repayments.reconciliation.policy': 'Politique',
    'admin.repayments.reconciliation.blocked':
        "Ce remboursement est bloqué : le rapprochement a relevé une exception. Rien n'est comptabilisé ni affiché comme rapproché, et sa résolution n'est pas encore disponible.",
    'admin.repayments.servicing.title': 'Service du titre',
    'admin.repayments.servicing.before': 'Avant cet encaissement',
    'admin.repayments.servicing.after': 'Après répartition',
    'admin.repayments.servicing.not_posted': 'Pas encore comptabilisé.',
    'admin.repayments.servicing.state': 'État',
    'admin.repayments.servicing.status.current': 'À jour',
    'admin.repayments.servicing.status.due_today': "Dû aujourd'hui",
    'admin.repayments.servicing.status.overdue': 'En retard',
    'admin.repayments.servicing.status.repaid': 'Remboursé',
    'admin.repayments.servicing.status.defaulted': 'En défaut',
    'admin.repayments.component.principal': 'Capital',
    'admin.repayments.component.return': 'Rendement',
    'admin.repayments.component.late_fees': 'Pénalités de retard',
    'admin.repayments.component.service_fee': 'Frais de service',
    'admin.repayments.component.total': 'Restant dû',
    'admin.repayments.allocation.title': 'Répartition',
    'admin.repayments.allocation.pending':
        "Pas encore réparti : l'encaissement est enregistré et la répartition n'est pas comptabilisée.",
    'admin.repayments.allocation.balanced': 'Équilibré',
    'admin.repayments.allocation.unbalanced': 'Non équilibré',
    'admin.repayments.allocation.receipt': 'Encaissement {amount}',
    'admin.repayments.allocation.paid': "Ce que l'encaissement a payé",
    'admin.repayments.allocation.distributed': 'Où il est allé',
    'admin.repayments.allocation.col.kind': 'Ligne',
    'admin.repayments.allocation.col.account': 'Compte',
    'admin.repayments.allocation.col.amount': 'Montant',
    'admin.repayments.allocation.kind.principal': 'Capital',
    'admin.repayments.allocation.kind.return': 'Rendement',
    'admin.repayments.allocation.kind.late_fee': 'Pénalité de retard',
    'admin.repayments.allocation.kind.service_fee': 'Frais de service',
    'admin.repayments.allocation.kind.steward_share': 'Part du gestionnaire',
    'admin.repayments.allocation.kind.investor_gross': 'Investisseurs, brut',
    'admin.repayments.allocation.kind.investor_fee': 'Frais investisseur',
    'admin.repayments.allocation.kind.investor_net': 'Investisseurs, net',
    'admin.repayments.allocation.kind.investor_late_fee':
        'Investisseurs, pénalités',
    'admin.repayments.allocation.kind.psp_fee': 'Frais PSP',
    'admin.repayments.allocation.kind.unapplied': 'Non affecté',
    'admin.repayments.allocation.instalment': '· échéance {index}',
    'admin.repayments.allocation.holders': {
        one: '· {count} placement',
        other: '· {count} placements',
    },
    'admin.repayments.allocation.entitlements': {
        one: "Réparti sur {count} placement au plus fort reste, date d'enregistrement {date}.",
        other: "Réparti sur {count} placements au plus fort reste, date d'enregistrement {date}.",
    },
    'admin.repayments.allocation.posted_at': 'Comptabilisé le {at}',
    'admin.repayments.allocation.ledger': 'Ouvrir les écritures',
    'admin.repayments.receipts': 'Reçus',
    'admin.repayments.business': "Ouvrir l'entreprise",
    'admin.repayments.trail': 'Historique du remboursement',
    'admin.repayments.requery_body':
        "Cela interroge le prestataire sur le même encaissement entrant. Cela n'encaisse jamais l'argent une seconde fois.",
    'admin.nav.book': 'Portefeuille',
    'admin.nav.exceptions': 'Exceptions',
    'admin.section.book.title': 'Portefeuille',
    'admin.section.book.subtitle':
        'Chaque titre en cours et sa santé, tels que le système central enregistre leur service. Lecture seule.',
    'admin.section.book.search': 'Rechercher par entreprise ou titre…',
    'admin.section.exceptions.title': 'Exceptions',
    'admin.section.exceptions.subtitle':
        "Arriérés, suspensions et écarts, chacun daté et attribué jusqu'à sa résolution. Lecture seule.",
    'admin.section.exceptions.search':
        'Rechercher par entreprise ou référence…',
    'admin.book.title': 'Titres en cours',
    'admin.book.caption':
        'État du service et jours de retard tels que le système central les enregistre. Rien ici ne modifie un titre.',
    'admin.book.table': 'Titres en cours',
    'admin.book.filter': 'Filtrer par état du service',
    'admin.book.stats.live_notes': 'Titres en cours',
    'admin.book.stats.principal_outstanding': 'Capital restant dû',
    'admin.book.stats.due_today': "Dus aujourd'hui",
    'admin.book.stats.overdue': 'En retard',
    'admin.book.chip.all': 'Tous',
    'admin.book.col.note_id': 'ID du titre',
    'admin.book.col.note': 'Entreprise · titre',
    'admin.book.col.principal': 'Capital restant dû',
    'admin.book.col.next_due': 'Prochaine échéance',
    'admin.book.col.dpd': 'Jours de retard',
    'admin.book.col.health': 'Santé',
    'admin.book.nothing_due': 'Aucune échéance',
    'admin.book.open_named': 'Ouvrir {note}',
    'admin.book.more': 'Plus de titres',
    'admin.book.empty_title': 'Aucun titre en cours',
    'admin.book.empty_body':
        'Un titre apparaît ici dès que sa levée est versée et que le service commence.',
    'admin.book.filtered_title': 'Aucun titre ne correspond à ce filtre',
    'admin.book.filtered_body':
        'Choisissez Tous pour voir tout le portefeuille.',
    'admin.exceptions.title': 'Exceptions ouvertes',
    'admin.exceptions.caption':
        "Chaque exception reste ici, datée, jusqu'à sa résolution. L'attribution, l'escalade et les remèdes ne sont pas encore disponibles.",
    'admin.exceptions.table': 'Exceptions ouvertes',
    'admin.exceptions.filter': 'Filtrer par type',
    'admin.exceptions.count.open': {
        one: '{count} ouverte',
        other: '{count} ouvertes',
    },
    'admin.exceptions.count.unassigned': {
        one: '{count} non attribuée',
        other: '{count} non attribuées',
    },
    'admin.exceptions.chip.all': 'Toutes',
    'admin.exceptions.kind.arrears': 'Arriérés',
    'admin.exceptions.kind.halt': 'Suspension',
    'admin.exceptions.kind.variance': 'Écart',
    'admin.exceptions.col.reference': 'Référence',
    'admin.exceptions.col.kind': 'Type',
    'admin.exceptions.col.subject': 'Exception',
    'admin.exceptions.col.amount': 'Montant',
    'admin.exceptions.col.owner': 'Âge · responsable',
    'admin.exceptions.col.open': 'Dossier',
    'admin.exceptions.age': {
        one: 'Ouverte depuis {count} jour',
        other: 'Ouverte depuis {count} jours',
    },
    'admin.exceptions.dpd': '{count} j de retard',
    'admin.exceptions.open_named': 'Ouvrir {reference}',
    'admin.exceptions.more': 'Exceptions plus anciennes',
    'admin.exceptions.empty_title': 'Aucune exception ouverte',
    'admin.exceptions.empty_body':
        'Les arriérés, suspensions et écarts apparaissent ici quand le système central en ouvre un.',
    'admin.exceptions.filtered_title': 'Aucune exception de ce type',
    'admin.exceptions.filtered_body':
        'Choisissez Toutes pour voir chaque exception ouverte.',
    'admin.nav.reconciliation': 'Rapprochement',
    'admin.nav.coverage': 'Couverture des partenaires',
    'admin.nav.reports': 'Rapports',
    'admin.section.reconciliation.title': 'Rapprochement',
    'admin.section.reconciliation.subtitle':
        'Le grand livre, le relevé et l’écart de chaque compte côte à côte, et chaque écart non expliqué jusqu’à son explication. Lecture seule.',
    'admin.section.reconciliation.search':
        'Rechercher par compte ou référence…',
    'admin.section.coverage.title': 'Couverture des partenaires',
    'admin.section.coverage.subtitle':
        'Partenaires d’audit et audits ouverts dans chaque district, et si le district est couvert. Lecture seule.',
    'admin.section.coverage.search': 'Rechercher par district ou province…',
    'admin.section.reports.title': 'Rapports mensuels',
    'admin.section.reports.subtitle':
        'Rapports de performance des entreprises publiés pour les investisseurs',
    'admin.section.reports.search': 'Rechercher…',
    'admin.reconciliation.day_close.label': 'Clôture de la journée',
    'admin.reconciliation.day_close.title': 'Clôture de la journée · {date}',
    'admin.reconciliation.day_close.state.reconciled': 'Rapprochée',
    'admin.reconciliation.day_close.state.open_break': 'Écart ouvert',
    'admin.reconciliation.day_close.state.not_reconciled':
        'Pas encore rapprochée',
    'admin.reconciliation.day_close.body.reconciled':
        'Chaque compte est rapproché ; la journée a été clôturée le {time}.',
    'admin.reconciliation.day_close.body.open_break':
        'La journée ne peut pas être clôturée tant qu’un écart n’est pas expliqué. Chaque écart reste ci-dessous, daté, jusqu’à son explication.',
    'admin.reconciliation.day_close.body.not_reconciled':
        'Le système central n’a pas encore rapproché cette journée ; elle ne peut donc pas être clôturée.',
    'admin.reconciliation.accounts.title': 'Soldes',
    'admin.reconciliation.accounts.caption':
        'Grand livre, relevé et écart de chaque compte, tels que le système central les établit.',
    'admin.reconciliation.accounts.table': 'Soldes des comptes',
    'admin.reconciliation.accounts.empty_title': 'Aucun compte signalé',
    'admin.reconciliation.accounts.empty_body':
        'Un compte apparaît ici dès que le système central le rapproche.',
    'admin.reconciliation.col.account': 'Compte',
    'admin.reconciliation.col.ledger': 'Grand livre',
    'admin.reconciliation.col.statement': 'Relevé',
    'admin.reconciliation.col.difference': 'Écart',
    'admin.reconciliation.col.feed': 'Flux du relevé',
    'admin.reconciliation.col.reference': 'Référence',
    'admin.reconciliation.col.break': 'Écart',
    'admin.reconciliation.col.gap': 'Montant',
    'admin.reconciliation.col.owner': 'Âge · responsable',
    'admin.reconciliation.col.record': 'Dossier',
    'admin.reconciliation.as_of': 'Au {time}',
    'admin.reconciliation.no_statement': 'Aucun relevé',
    'admin.reconciliation.feed.available': 'Reçu',
    'admin.reconciliation.feed.unavailable': 'Indisponible',
    'admin.reconciliation.feed.since': 'Depuis le {time}',
    'admin.reconciliation.breaks.title': 'Écarts ouverts',
    'admin.reconciliation.breaks.caption':
        'Chaque écart reste ici, daté, jusqu’à son explication. L’attribution et l’escalade ne sont pas encore disponibles.',
    'admin.reconciliation.breaks.table': 'Écarts ouverts',
    'admin.reconciliation.breaks.empty_title': 'Aucun écart ouvert',
    'admin.reconciliation.breaks.empty_body':
        'Un écart apparaît ici quand un relevé et le grand livre ne concordent pas.',
    'admin.reconciliation.open_named': 'Ouvrir {reference}',
    'admin.reports.title': 'Dossiers',
    'admin.reports.caption':
        'Chaque dossier pour sa période, et à quel point ses chiffres sont définitifs. Rien ici ne modifie un chiffre.',
    'admin.reports.table': 'Dossiers de rapports',
    'admin.reports.col.pack': 'Dossier',
    'admin.reports.col.period': 'Période',
    'admin.reports.col.status': 'Statut',
    'admin.reports.col.as_of': 'Au',
    'admin.reports.col.download': 'Téléchargement',
    'admin.reports.kind.regulator': 'Régulateur',
    'admin.reports.kind.board': 'Conseil',
    'admin.reports.kind.export': 'Export',
    'admin.reports.status.complete': 'Complet',
    'admin.reports.status.incomplete': 'Période non clôturée',
    'admin.reports.status.moving': 'Chiffres en mouvement',
    'admin.reports.period': '{start} – {end}',
    'admin.reports.snapshot': 'Instantané',
    'admin.reports.pending': 'En attente :',
    'admin.reports.not_ready': 'À la clôture de la période',
    'admin.reports.unavailable': 'Téléchargement indisponible',
    'admin.reports.download': 'Télécharger',
    'admin.reports.download_snapshot': 'Télécharger l’instantané',
    'admin.reports.download_named': 'Télécharger {label}',
    'admin.reports.empty_title': 'Aucun dossier pour l’instant',
    'admin.reports.empty_body':
        'Un dossier apparaît ici dès que sa période commence.',
    'admin.coverage.title': 'Districts',
    'admin.coverage.caption':
        'Partenaires d’audit actifs et audits ouverts dans chaque district, avec l’avis de couverture du système central.',
    'admin.coverage.table': 'Couverture par district',
    'admin.coverage.stats.districts': 'Districts',
    'admin.coverage.stats.uncovered': 'Non couverts',
    'admin.coverage.stats.audits_open': 'Audits ouverts',
    'admin.coverage.col.district': 'District',
    'admin.coverage.col.partners': 'Partenaires d’audit actifs',
    'admin.coverage.col.audits': 'Audits ouverts',
    'admin.coverage.col.capacity': 'Couverture',
    'admin.coverage.col.open': 'Partenaires',
    'admin.coverage.capacity.covered': 'Couvert',
    'admin.coverage.capacity.uncovered': 'Non couvert',
    'admin.coverage.open': 'Voir',
    'admin.coverage.open_named': 'Partenaires d’audit à {district}',
    'admin.coverage.empty_title': 'Aucun district signalé',
    'admin.coverage.empty_body':
        'Les districts apparaissent ici dès que le système central suit la couverture des partenaires d’audit.',
};

export default fr;
