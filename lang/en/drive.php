<?php

declare(strict_types=1);

return [
    'flash' => [
        'folder_created' => 'Folder created.',
        'folder_updated' => 'Folder updated.',
        'folder_trashed' => 'Folder moved to trash.',
        'folder_restored' => 'Folder restored.',
        'folder_deleted' => 'Folder permanently deleted.',
        'file_uploaded' => 'File uploaded.',
        'file_updated' => 'File updated.',
        'file_trashed' => 'File moved to trash.',
        'file_restored' => 'File restored.',
        'file_deleted' => 'File permanently deleted.',
        'shared' => 'Item shared.',
        'share_revoked' => 'Share revoked.',
        'link_created' => 'Share link created.',
        'link_revoked' => 'Share link revoked.',
        'quota_updated' => 'Drive quota updated.',
    ],
    'errors' => [
        'quota_exceeded' => 'Department storage quota exceeded. Free up space or ask an admin to raise the limit.',
        'forbidden' => 'You do not have permission to perform this action.',
        'invalid_move' => 'Cannot move a folder into itself or one of its subfolders.',
        'upload_failed' => 'Upload failed. Please try again.',
        'cannot_share_self' => 'You cannot share an item with yourself.',
        'link_inactive' => 'This share link is expired or has been revoked.',
        'invalid_password' => 'Incorrect password.',
    ],
    'permissions' => [
        'viewer' => 'Viewer',
        'editor' => 'Editor',
    ],
    'notifications' => [
        'shared' => [
            'subject' => 'Drive item shared with you: :name',
            'line' => ':actor shared “:name” with you (:permission).',
            'action' => 'Open Drive',
        ],
    ],
];
