/**
 * Main entry point for the all the scripts.
 * Loads everything it needs.
 */
require.config({
  baseUrl: "scripts",
  paths: {
    knockout: 'lib/knockout',
    pager: 'lib/pager',
    jquery: 'lib/jquery',
    metisMenu: 'lib/metisMenu',
    hashchange: 'lib/jquery-hashchange',
    sbadmin: 'lib/sb-admin-2',
    moduleView: 'lib/bindings/moduleView',
    system: 'framework/system',
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
require(['knockout', 'jquery'], (ko, vm, jQuery) => {
  require(['hashchange', 'metisMenu'], () => {
    require(['pager', 'sbadmin'], (pager, metisMenu, sbadmin) => {

      require(['system', 'moduleView'], (system: ISystem) => {
        system.WhenReady.then(() => {
          require(['viewmodels/BaseViewModel'], (vm) => {
            let viewModel = new vm();

            pager.Href.hash = '#!/';
            pager.extendWithPage(viewModel);

            ko.applyBindings(viewModel);
            pager.start();
          });
        });
      });

    });
  });
});
