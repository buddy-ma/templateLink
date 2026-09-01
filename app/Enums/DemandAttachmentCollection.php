<?php

declare(strict_types=1);

namespace App\Enums;

enum DemandAttachmentCollection: string
{
    case NatureMateriel = 'nature_materiel';
    case ReferentielProduit = 'referentiel_produit';
    case Decision = 'decision';
}
