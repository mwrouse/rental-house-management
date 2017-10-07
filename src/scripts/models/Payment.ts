class Payment implements IPayment {
  public Bill: Guid;
  public Tenant: ITenant;
  public Amount: Number;
  public Date: Date;

  constructor() {

  }
}

export = Payment;