import * as ko from "knockout";

import Configuration from "models/Configuration";


/**
 * This is the base view model, it will load configuration from 
 * the database as well as contain access to the actual view model 
 * (for the bills page, history page, etc...)
 */
class BaseViewModel { 
    public Config: IConfiguration = new Configuration();


    constructor() { 
        
    }
}

export = BaseViewModel