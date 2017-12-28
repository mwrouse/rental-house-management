let $ = require("jquery");
let ko = require("knockout");


/**
 * Class to represent the site configuration
 */
export default class Configuration implements IConfiguration { 
  public SiteName: KnockoutObservable<string>; 
  
  /**
   * Constructor populates defaults
   */
  constructor() { 
    this.SiteName = ko.observable(""); 

    // Fetch Actual configuration 
    $.get("/api/v1/configuration", (raw: IRawConfiguration) => {
      if (raw.Data === null) return; // :(
      
      this.SiteName(raw.Data.SiteName);
    });
  }


}

