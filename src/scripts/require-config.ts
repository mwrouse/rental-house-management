/**
 * Main entry point for the all the scripts.
 * Loads everything it needs.
 */
require.config({
  baseUrl: "scripts",
  paths: {
    knockout: 'lib/knockout',
    tenant: 'models/Tenant',
    bill: 'models/Bill',
    payment: 'models/Payment'
  }
});
