let permissions = {
    'ViewBills': 'view_bills',
    'AddBills': 'add_bills',
    'DeleteBills': 'delete_bills',
    'EditBills': 'edit_bills',
};

let allPermissions = [];
for (let permission in permissions)
    allPermissions.push(permissions[permission]);

let manifest: IManifest = {
    Title: "Bills",
    NavIcon: "fa-dollar-sign",
    Key: "bills",
    AssociatedPermissions: allPermissions,
    DemandPermission: permissions.ViewBills,
};


export = manifest;