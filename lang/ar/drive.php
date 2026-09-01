<?php

declare(strict_types=1);

return [
    'flash' => [
        'folder_created' => 'تم إنشاء المجلد.',
        'folder_updated' => 'تم تحديث المجلد.',
        'folder_trashed' => 'تم نقل المجلد إلى سلة المهملات.',
        'folder_restored' => 'تم استعادة المجلد.',
        'folder_deleted' => 'تم حذف المجلد نهائيًا.',
        'file_uploaded' => 'تم رفع الملف.',
        'file_updated' => 'تم تحديث الملف.',
        'file_trashed' => 'تم نقل الملف إلى سلة المهملات.',
        'file_restored' => 'تم استعادة الملف.',
        'file_deleted' => 'تم حذف الملف نهائيًا.',
        'shared' => 'تمت مشاركة العنصر.',
        'share_revoked' => 'تم إلغاء المشاركة.',
        'link_created' => 'تم إنشاء رابط المشاركة.',
        'link_revoked' => 'تم إلغاء رابط المشاركة.',
        'quota_updated' => 'تم تحديث حصة التخزين.',
    ],
    'errors' => [
        'quota_exceeded' => 'تم تجاوز حصة تخزين القسم. حرّر مساحة أو اطلب من المسؤول رفع الحد.',
        'forbidden' => 'ليس لديك إذن لتنفيذ هذا الإجراء.',
        'invalid_move' => 'لا يمكن نقل مجلد إلى نفسه أو إلى أحد مجلداته الفرعية.',
        'upload_failed' => 'فشل الرفع. حاول مرة أخرى.',
        'cannot_share_self' => 'لا يمكنك مشاركة عنصر مع نفسك.',
        'link_inactive' => 'انتهت صلاحية رابط المشاركة أو تم إلغاؤه.',
        'invalid_password' => 'كلمة المرور غير صحيحة.',
    ],
    'permissions' => [
        'viewer' => 'عرض',
        'editor' => 'تحرير',
    ],
    'notifications' => [
        'shared' => [
            'subject' => 'تمت مشاركة عنصر Drive معك: :name',
            'line' => 'شارك :actor «:name» معك (:permission).',
            'action' => 'فتح Drive',
        ],
    ],
];
