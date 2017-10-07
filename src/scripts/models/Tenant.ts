class Tenant implements ITenant {
  public Id: Guid;
  public FirstName: String;
  public LastName: String;
  public StartDate: Date;
  public EndDate: Date;
}

export = Tenant;