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

    'auth.two_factor.recovery_codes_remaining': {
        one: '{count} code de récupération restant',
        other: '{count} codes de récupération restants',
    },
};

export default fr;
