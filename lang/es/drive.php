<?php

declare(strict_types=1);

return [
    'flash' => [
        'folder_created' => 'Carpeta creada.',
        'folder_updated' => 'Carpeta actualizada.',
        'folder_trashed' => 'Carpeta movida a la papelera.',
        'folder_restored' => 'Carpeta restaurada.',
        'folder_deleted' => 'Carpeta eliminada permanentemente.',
        'file_uploaded' => 'Archivo subido.',
        'file_updated' => 'Archivo actualizado.',
        'file_trashed' => 'Archivo movido a la papelera.',
        'file_restored' => 'Archivo restaurado.',
        'file_deleted' => 'Archivo eliminado permanentemente.',
        'shared' => 'Elemento compartido.',
        'share_revoked' => 'Compartido revocado.',
        'link_created' => 'Enlace de compartir creado.',
        'link_revoked' => 'Enlace de compartir revocado.',
        'quota_updated' => 'Cuota de Drive actualizada.',
    ],
    'errors' => [
        'quota_exceeded' => 'Se superó la cuota de almacenamiento del departamento. Libere espacio o pida a un admin aumentar el límite.',
        'forbidden' => 'No tiene permiso para realizar esta acción.',
        'invalid_move' => 'No se puede mover una carpeta dentro de sí misma o de una subcarpeta.',
        'upload_failed' => 'Error al subir. Inténtelo de nuevo.',
        'cannot_share_self' => 'No puede compartir un elemento consigo mismo.',
        'link_inactive' => 'Este enlace ha caducado o ha sido revocado.',
        'invalid_password' => 'Contraseña incorrecta.',
    ],
    'permissions' => [
        'viewer' => 'Lector',
        'editor' => 'Editor',
    ],
    'notifications' => [
        'shared' => [
            'subject' => 'Elemento de Drive compartido: :name',
            'line' => ':actor compartió “:name” contigo (:permission).',
            'action' => 'Abrir Drive',
        ],
    ],
];
