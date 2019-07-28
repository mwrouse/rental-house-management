let $ = require("jquery");
let ko = require("knockout");

import * as ModuleLoader from "../../modules/ModuleLoader";


/**
 * Class to represent the site configuration
 */
class Configuration implements IConfiguration {
  public SiteName: KnockoutObservable<string>;

  public Modules: KnockoutObservableArray<IManifest> = ko.observableArray([]);

  /**
   * Constructor populates defaults
   */
  constructor() {

    ModuleLoader.GetModules().then((modules) => {
      this.Modules(modules);
    });

    this.SiteName = ko.observable("");

    // Fetch Actual configuration
    $.get("/api/v1/configuration", (raw: IRawConfiguration) => {
      if (raw.Data === null)
        return; // :(

      this.SiteName(raw.Data.SiteName);
    });
  }


}

let instance = new Configuration();
export = instance;