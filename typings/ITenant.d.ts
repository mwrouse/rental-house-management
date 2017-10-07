interface ITenant {
  Id: Guid;
  FirstName: String;
  LastName: String;
  StartDate: Date; // Date the tenant moves in
  EndDate: Date; // Date the tenants moves out
}