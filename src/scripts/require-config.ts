/**
 * Main entry point for the all the scripts.
 * Loads everything it needs.
 */
require.config({
  baseUrl: "scripts",
  paths: {
    knockout: 'lib/knockout'
  }
});

var urlParts = window.location.pathname.split("/");
var viewModel = "Home";

if (urlParts.length > 1) {
  let path = urlParts[1].split('.');

  switch (path[0]) {
    case "new":
      viewModel = "NewBill";
      break;
    case "payement":
      viewModel = "NewPayment";
      break;
  }
}


// Require everything
require(['knockout', 'viewmodels/' + viewModel + 'ViewModel'], (ko, vm) => {
  require(['knockout'], (ko) => {

    ko.applyBindings(new vm());
  });
});
