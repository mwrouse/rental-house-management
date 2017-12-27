import * as ko from "knockout";


class HomeViewModel {
  public Test: KnockoutObservable<String>;

  constructor() {
    this.Test = ko.observable("Howdy");

  }
}

export = HomeViewModel;