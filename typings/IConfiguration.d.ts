interface IConfiguration { 
  SiteName: KnockoutObservable<string>; 

}


interface IRawConfiguration { 
  Data: IRawConfigurationData;
}

interface IRawConfigurationData { 
  SiteName: string; 
}