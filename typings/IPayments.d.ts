interface IPayment {
  Bill: Guid; // ID for the Bill
  Tenant: ITenant; // ID for the tenant
  Amount: Number;
  Date: Date;
}