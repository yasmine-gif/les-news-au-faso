<?php
/**
 * La configuration de base de votre installation WordPress.
 *
 * Ce fichier est utilisé par le script de création de wp-config.php pendant
 * le processus d’installation. Vous n’avez pas à utiliser le site web, vous
 * pouvez simplement renommer ce fichier en « wp-config.php » et remplir les
 * valeurs.
 *
 * Ce fichier contient les réglages de configuration suivants :
 *
 * Réglages MySQL
 * Préfixe de table
 * Clés secrètes
 * Langue utilisée
 * ABSPATH
 *
 * @link https://fr.wordpress.org/support/article/editing-wp-config-php/.
 *
 * @package WordPress
 */

// ** Réglages MySQL - Votre hébergeur doit vous fournir ces informations. ** //
/** Nom de la base de données de WordPress. */
define( 'DB_NAME', 'faso-news' );

/** Utilisateur de la base de données MySQL. */
define( 'DB_USER', 'root' );

/** Mot de passe de la base de données MySQL. */
define( 'DB_PASSWORD', '' );

/** Adresse de l’hébergement MySQL. */
define( 'DB_HOST', 'localhost' );

/** Jeu de caractères à utiliser par la base de données lors de la création des tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/**
 * Type de collation de la base de données.
 * N’y touchez que si vous savez ce que vous faites.
 */
define( 'DB_COLLATE', '' );

/**#@+
 * Clés uniques d’authentification et salage.
 *
 * Remplacez les valeurs par défaut par des phrases uniques !
 * Vous pouvez générer des phrases aléatoires en utilisant
 * {@link https://api.wordpress.org/secret-key/1.1/salt/ le service de clés secrètes de WordPress.org}.
 * Vous pouvez modifier ces phrases à n’importe quel moment, afin d’invalider tous les cookies existants.
 * Cela forcera également tous les utilisateurs à se reconnecter.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'N-TVA*6{8hFHx&GUaH:<<L#(IhZZZTrI=Ia2h= vj@4eKUu99T[GQAS:OFWHp/ct' );
define( 'SECURE_AUTH_KEY',  'P}Q%D6<ib84c+EfwB~y*Fo=DN%;nagC5{/VIqXyxUdOZu>&&0OJ9-L<xphFwR},p' );
define( 'LOGGED_IN_KEY',    '>C[]S@&F^umxz}iIk%I<Co@9}L5d,tzbOY;8t<{|pQ3=1*1dEFQS^#<jhETWct71' );
define( 'NONCE_KEY',        'kf}EFiknU/v#Pr25Xlv`)!>h<pbF,r}XJ4yh,C+JE{k5Ghk>Bm2wn *]6.^mnUk5' );
define( 'AUTH_SALT',        '>?z?I<B?A4%5,/Ah6k_g1e>aWT+^v8PR|VeV$h+Mk{:]k]=/]*:(}Z ;#N61H:rz' );
define( 'SECURE_AUTH_SALT', 'O2h0@7wRy>FIc+bx*737#/v.]^:8`EQHE1Il)q4F_`#~#7Wzk>>w(aZ&^5+i:OQi' );
define( 'LOGGED_IN_SALT',   'n1d$set_{K31P)BKBU,|h$BaECYCt7)*sy2m]eN2k]V.Y:,a&4FhxC6kfw-TZG!9' );
define( 'NONCE_SALT',       'eMSMd~x|):nfDH_{#iAPGKygye+fg^s1Rt^4G`Q6./r~nIDi$OsmO/7KEM>%{:)z' );
/**#@-*/

/**
 * Préfixe de base de données pour les tables de WordPress.
 *
 * Vous pouvez installer plusieurs WordPress sur une seule base de données
 * si vous leur donnez chacune un préfixe unique.
 * N’utilisez que des chiffres, des lettres non-accentuées, et des caractères soulignés !
 */
$table_prefix = 'wp_';

/**
 * Pour les développeurs : le mode déboguage de WordPress.
 *
 * En passant la valeur suivante à "true", vous activez l’affichage des
 * notifications d’erreurs pendant vos essais.
 * Il est fortemment recommandé que les développeurs d’extensions et
 * de thèmes se servent de WP_DEBUG dans leur environnement de
 * développement.
 *
 * Pour plus d’information sur les autres constantes qui peuvent être utilisées
 * pour le déboguage, rendez-vous sur le Codex.
 *
 * @link https://fr.wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', false );

/* C’est tout, ne touchez pas à ce qui suit ! Bonne publication. */

/** Chemin absolu vers le dossier de WordPress. */
if ( ! defined( 'ABSPATH' ) )
  define( 'ABSPATH', dirname( __FILE__ ) . '/' );

/** Réglage des variables de WordPress et de ses fichiers inclus. */
require_once( ABSPATH . 'wp-settings.php' );
