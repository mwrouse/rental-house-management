var ko: KnockoutStatic = require('knockout');

class HomeViewModel {
  public Test: KnockoutObservable<String>;

  constructor() {
    this.Test = ko.observable("Howdy");

  }
}

export = HomeViewModel;