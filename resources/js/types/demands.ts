export type DemandStatus =
    | 'draft'
    | 'pending_manager'
    | 'pending_validation'
    | 'refused'
    | 'blocked'
    | 'pending_business_dev'
    | 'pending_closure'
    | 'closed';

export type DemandUser = {
    id: number;
    name: string;
    email?: string;
};

export type DemandBrand = {
    id: number;
    name: string;
    sku?: string | null;
    dosage_form?: string | null;
    presentation?: string | null;
    label?: string;
};

/** @deprecated Use DemandBrand */
export type DemandProduct = DemandBrand;

export type DemandMaterialNature = {
    id: number;
    name: string;
};

export type DemandValidator = {
    id: number;
    user_id: number | null;
    role_name?: string | null;
    is_group?: boolean;
    position: number;
    status: 'pending' | 'approved' | 'skipped';
    acted_at: string | null;
    comment: string | null;
    acted_by?: DemandUser | null;
    user: DemandUser | null;
};

export type DemandAttachment = {
    id: number;
    collection: 'nature_materiel' | 'referentiel_produit' | 'decision';
    original_name: string;
    mime: string | null;
    size: number;
    demand_event_id?: number | null;
    created_at: string | null;
};

export type DemandEvent = {
    id: number;
    type: string;
    from_status: string | null;
    to_status: string | null;
    step: number | null;
    comment: string | null;
    created_at: string | null;
    actor: { id: number; name: string } | null;
    attachments?: DemandAttachment[];
};

export type DemandPermissions = {
    update: boolean;
    validate: boolean;
    business_validate: boolean;
    refuse_or_block: boolean;
    unblock: boolean;
    close: boolean;
};

export type Demand = {
    id: number;
    reference: string;
    description: string;
    status: DemandStatus;
    current_step: number | null;
    refused_reason: string | null;
    blocked_reason: string | null;
    closed_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    creator: DemandUser | null;
    brand: DemandBrand | null;
    /** @deprecated Use brand */
    product?: DemandBrand | null;
    material_nature: DemandMaterialNature | null;
    closed_by: { id: number; name: string } | null;
    validators: DemandValidator[];
    attachments: DemandAttachment[];
    events: DemandEvent[];
    permissions: DemandPermissions | null;
};
