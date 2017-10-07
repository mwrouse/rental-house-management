class Bill implements IBill {
  public Id: Guid;
  public Title: String;
  public DueDate: Date;
  public CreationDate: Date;
  public CreatedBy: ITenant;
  public Amount: Number;
  public Remaining: Number;
  public Split: Number;
  public IsPaid: Boolean;

  public Payments: IPayment[];

  constructor() {

  }
}


export = Bill;