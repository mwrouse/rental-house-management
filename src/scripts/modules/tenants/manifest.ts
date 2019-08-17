let permissions = {
    'ViewTenants': 'view_tenants',
    'AddTenants': 'add_tenants',
    'DeleteTenants': 'delete_tenants'
};
let allPermissions = [];
for (let permission in permissions)
    allPermissions.push(permissions[permission]);

let manifest: IManifest = {
    Title: "Tenants",
    NavIcon: "fa-person",
    Key: "tenants",
    AssociatedPermissions: allPermissions,
    DemandPermission: permissions.ViewTenants,
};

export = manifest;