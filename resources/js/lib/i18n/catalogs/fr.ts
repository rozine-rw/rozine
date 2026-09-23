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

    'auth.two_factor.recovery_codes_remaining': {
        one: '{count} code de récupération restant',
        other: '{count} codes de récupération restants',
    },
};

export default fr;
