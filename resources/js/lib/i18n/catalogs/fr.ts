/**
 * French catalog.
 *
 * Structurally complete against `en`. Copy is engineering-supplied and still needs a professional
 * review pass before any locale is activated under D-07.
 */
import type { Catalog } from '@/lib/i18n/types';

const fr: Catalog = {
    'identity.home.investor': 'Espace investisseur',
    'identity.home.business': 'Espace entreprise',
    'identity.home.auditor': 'Espace auditeur',
    'identity.home.admin': 'Espace personnel',
    'identity.home.overview': 'Vue générale',
    'identity.home.access': 'Accès au compte',
    'identity.home.ready': 'Votre compte a accès à cet espace.',
    'identity.home.verified': 'Votre identité est vérifiée.',
    'identity.home.staff_ready':
        'Votre compte personnel est autorisé à ouvrir cet espace.',
    'identity.home.back': 'Choisir une application',
    'identity.home.settings': 'Paramètres du compte',
    'identity.home.saving': 'Enregistrement de votre position…',
    'identity.home.failed':
        'Impossible de mettre à jour votre position. Revenez au lanceur pour vérifier votre accès.',
    'identity.denied.title': 'Votre accès doit être vérifié',
    'identity.denied.body':
        'Votre compte ne peut plus ouvrir cette page avec le rôle sélectionné. Choisissez une application pour actualiser votre accès.',
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

    'auth.two_factor.recovery_codes_remaining': {
        one: '{count} code de récupération restant',
        other: '{count} codes de récupération restants',
    },
};

export default fr;
