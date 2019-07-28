import * as ko from "knockout";

let conventions = {
    buildViewName: key => key + 'View.html',
    buildViewPath: function(key, name){ return './modules/' + key + '/Views/' + this.buildViewName(name); },

    buildViewModelName: key => key + 'ViewModel',
    buildViewModelPath: function(key, name) { return './modules/' + key + '/ViewModels/' + this.buildViewModelName(name); },
};

var vmCache = {};
var viewCache = {};


function loadViewModel(path: string, data: IManifest): JQueryPromise<any> {
    var dfd = $.Deferred<any>();

    if (vmCache[data.Key]) {
        let vm = vmCache[data.Key];
        dfd.resolve(vm);
        return dfd.promise();
    }

    require([path],
        (viewModel: IViewModel) => {
            // Success
            let vm: IViewModel = viewModel;

            if (typeof viewModel === 'function') {
                vm = new (viewModel as any)();
            }

            try {
                vm.OnLoad();
            }
            catch (e) {
            }

            vmCache[data.Key] = vm;

            dfd.resolve(vm);
        },
        (e) => {
            // Failure
            console.error('Could not load view model for module', data, e);
            dfd.reject('Error');
        }
    );

    return dfd.promise();
}

function loadView(path: string, data: IManifest): JQueryPromise<string> {
    var dfd = $.Deferred<string>();

    if (viewCache[data.Key]) {
        let view = viewCache[data.Key];
        dfd.resolve(view);
        return dfd.promise();
    }

    $.get(path).done((view) => {
        viewCache[data.Key] = view;
        dfd.resolve(view);
    });

    return dfd.promise();
}



ko.bindingHandlers['moduleView'] = {
    init: (element, valueAccessor, allBindingsAccessor, viewModel, bindingContext) => {
        let manifest: IManifest = ko.unwrap(valueAccessor());
        if (manifest == null)
            return;
        let area = manifest.Key;

        let moduleVMPath = conventions.buildViewModelPath(area, manifest.Title);
        let moduleViewPath = conventions.buildViewPath(area, manifest.Title);

        loadView(moduleViewPath, manifest)
            .done((view) => {
                element.innerHTML = view;

                loadViewModel(moduleVMPath, manifest)
                    .done((vm: IViewModel) => {
                        ko.applyBindingsToDescendants(vm, element);

                        window.location.hash = '#!/' + manifest.Key;

                        ko.utils.domNodeDisposal.addDisposeCallback(element, () => {
                            try {
                                vm.OnHide();
                            }
                            catch (e) {

                            }
                        });
                    });
            });

            return { controlsDescendantBindings: true };
    }
};