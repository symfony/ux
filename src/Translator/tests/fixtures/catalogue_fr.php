<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return [
    'messages+intl-icu' => [
        'notification.comment_created' => 'Votre article a reçu un commentaire !',
        'notification.comment_created.description' => 'Votre article "{title}" a reçu un nouveau commentaire. Vous pouvez lire le commentaire en suivant <a href="{link}">ce lien</a>',
        'post.num_comments' => '{count, plural, one {# commentaire} other {# commentaires}}',
        'post.num_comments.' => '{count, plural, one {# commentaire} other {# commentaires}} (ne doit pas rentrer en conflit avec la traduction précédente)',
    ],
    'messages' => [
        'symfony.great' => 'Symfony est génial !',
        'symfony.what' => 'Symfony est %what%!',
        'symfony.what!' => 'Symfony est %what%! (ne doit pas rentrer en conflit avec la traduction précédente)',
        'symfony.what.' => 'Symfony est %what%. (ne doit pas non plus rentrer en conflit avec la traduction précédente)',
        'apples.count.0' => 'Il y a 1 pomme|Il y a %count% pommes',
        'apples.count.1' => '{1} Il y a une pomme|]1,Inf] Il y a %count% pommes',
        'apples.count.2' => '{0} Il n\'y a pas de pommes|{1} Il y a une pomme|]1,Inf] Il y a %count% pommes',
        'apples.count.3' => 'one: Il y a une pomme|more: Il y a %count% pommes',
        'apples.count.4' => 'one: Il y a une pomme|more: Il y a plus d\'une pomme',
        'what.count.1' => '{1} Il y a une %what%|]1,Inf] Il y a %count% %what%',
        'what.count.2' => '{0} Il n\'y a pas de %what%|{1} Il y a une %what%|]1,Inf] Il y a %count% %what%',
        'what.count.3' => 'one: Il y a une %what%|more: Il y a %count% %what%',
        'what.count.4' => 'one: Il y a une %what%|more: Il y a more than one %what%',
        'animal.dog-cat' => 'Chien et chat',
        'animal.dog_cat' => 'Chien et chat (ne doit pas rentrer en conflit avec la traduction précédente)',
        '0starts.with.numeric' => 'La touche commence par un caractère numérique',
    ],
    'foobar' => [
        'post.num_comments' => 'Il y a 1 comment|Il y a %count% comments',
    ],
];
