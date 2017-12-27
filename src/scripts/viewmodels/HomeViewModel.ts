import * as ko from "knockout";


class HomeViewModel {
  public Title: KnockoutObservable<String>;

  constructor() {
    this.Title = ko.observable("Dogwood");

  }
}

export = HomeViewModel;