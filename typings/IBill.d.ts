interface IBill {
  Id: Guid;
  Title: String;
  DueDate: Date;
  CreationDate: Date;
  CreatedBy: ITenant;
  Amount: Number;    // Original bill amount
  Remaining: Number; // Amount remaining
  Split: Number;    // Amount per tenant
  IsPaid: Boolean;

  Payments: IPayment[]; // Array of paymenets made for this bill
}