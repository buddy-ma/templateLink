<?php

declare(strict_types=1);

return [
    'flash' => [
        'folder_created' => 'Dossier créé.',
        'folder_updated' => 'Dossier mis à jour.',
        'folder_trashed' => 'Dossier déplacé dans la corbeille.',
        'folder_restored' => 'Dossier restauré.',
        'folder_deleted' => 'Dossier définitivement supprimé.',
        'file_uploaded' => 'Fichier téléversé.',
        'file_updated' => 'Fichier mis à jour.',
        'file_trashed' => 'Fichier déplacé dans la corbeille.',
        'file_restored' => 'Fichier restauré.',
        'file_deleted' => 'Fichier définitivement supprimé.',
        'shared' => 'Élément partagé.',
        'share_revoked' => 'Partage révoqué.',
        'link_created' => 'Lien de partage créé.',
        'link_revoked' => 'Lien de partage révoqué.',
        'quota_updated' => 'Quota Drive mis à jour.',
    ],
    'errors' => [
        'quota_exceeded' => 'Le quota de stockage du département est dépassé. Libérez de l’espace ou demandez à un admin d’augmenter la limite.',
        'forbidden' => 'Vous n’avez pas la permission d’effectuer cette action.',
        'invalid_move' => 'Impossible de déplacer un dossier dans lui-même ou dans un de ses sous-dossiers.',
        'upload_failed' => 'Échec du téléversement. Veuillez réessayer.',
        'cannot_share_self' => 'Vous ne pouvez pas partager un élément avec vous-même.',
        'link_inactive' => 'Ce lien de partage a expiré ou a été révoqué.',
        'invalid_password' => 'Mot de passe incorrect.',
    ],
    'permissions' => [
        'viewer' => 'Lecteur',
        'editor' => 'Éditeur',
    ],
    'notifications' => [
        'shared' => [
            'subject' => 'Élément Drive partagé avec vous : :name',
            'line' => ':actor a partagé « :name » avec vous (:permission).',
            'action' => 'Ouvrir le Drive',
        ],
    ],
];
