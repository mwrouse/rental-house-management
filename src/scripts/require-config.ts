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
    jqueryui: 'lib/jqueryui',
    jqueryblockui: 'lib/jqueryblockUI',
    metisMenu: 'lib/metisMenu',
    hashchange: 'lib/jquery-hashchange',
    bootstrap: 'lib/bootstrap',
    sbadmin: 'lib/sb-admin-2',
    fontawesome: 'lib/fontawesome',
    moduleView: 'lib/bindings/moduleView',
    system: 'framework/system',
  }
});


// Require everything
require(['knockout', 'jquery', 'fontawesome', 'system'], (ko, vm, jQuery) => {
  require(['jqueryui', 'hashchange', 'metisMenu'], () => {
    require(['pager', 'bootstrap', 'sbadmin', 'jqueryblockui'], (pager) => {

      require(['system', 'moduleView', 'lib/bindings/block'], (system: ISystem) => {
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
