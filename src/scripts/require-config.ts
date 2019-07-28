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
    sbadmin: 'lib/sb-admin-2',
    moduleView: 'lib/bindings/moduleView',
    system: 'framework/system',
  }
});

// Require everything
require(['knockout', 'jquery'], (ko, vm, jQuery) => {
  require(['jqueryui', 'hashchange', 'metisMenu'], () => {
    require(['pager', 'sbadmin', 'jqueryblockui'], (pager, metisMenu, sbadmin) => {

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
