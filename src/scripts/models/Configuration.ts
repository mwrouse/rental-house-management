let $ = require("jquery");
let ko = require("knockout");

import ModuleLoader = require("../../modules/ModuleLoader");


/**
 * Class to represent the site configuration
 */
class Configuration implements IConfiguration {
  public SiteName: KnockoutObservable<string>;

  public Modules: IManifest[];

  /**
   * Constructor populates defaults
   */
  constructor() {
    this.Modules = ModuleLoader.GetModules();

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