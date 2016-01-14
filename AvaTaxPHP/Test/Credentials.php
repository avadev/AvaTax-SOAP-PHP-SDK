<?php

// ATConfig object is how credentials are set
// Tax or Address Service Objects take an argument
// which is the name of the ATConfig object ('Test' or 'Prod' below)


/* This is a configuration called 'Development'. 
 * Only values different from 'Default' need to be specified.
 * Example:
 *
 * $service = new AddressServiceSoap('Development');
 * $service = new TaxServiceSoap('Development');
 */
new ATConfig('Development', array(    
    'url'       => 'https://development.avalara.net',
    'account'   => '<Your Development Account Here>',
    'license'   => '<Your Development License Key Here>',
    'trace'     => true) // change to false for production
);

/* This is a configuration called 'Production' 
 * Example:
 *
 * $service = new AddressServiceSoap('Production');
 * $service = new TaxServiceSoap('Production');
 */
new ATConfig('Production', array(
    'url'       => 'https://avatax.avalara.net',
    'account'   => '<Your Production Account Here>',
    'license'   => '<Your Production License Key Here>',
    'trace'     => false) // change to false for production
);

/* This is Jaas enabled Developement Configuration called 'JaasDevelopement' 
 * Example:
 *
 * $service = new AddressServiceSoap('JaasDevelopement');
 * $service = new TaxServiceSoap('JaasDevelopement');
 */
new ATConfig('JaasDevelopement', array(
    'url'       => 'https://development.avalara.net',
    'account'   => '<Your Development Account Here>',
    'license'   => '<Your Development License Key Here>',
    'name'      => 'Jaas',  // If Jaas is not enabled then remove this element  
    'trace'     => true)    // change to false for production
);
?>
