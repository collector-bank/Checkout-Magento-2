define([
    'uiComponent'
], function (Element) {
    'use strict';
    return Element.extend({
        defaults: {
            isLoaded: false,
            src: "",
            dataToken: "",
            dataVersion: ""
        },
        initialize: function (config) {
            this._super();
            this.src = config.src;
            this.dataToken = config.dataToken;
            this.dataVersion = config.dataVersion;

            if (this.src && this.dataToken) {
                this.isLoaded = true;
            }
            this.loadCollectorIframe();
        },
        loadCollectorIframe: function() {
            var collectorIframe = document.createElement("script");
            collectorIframe.src = this.src;
            collectorIframe.setAttribute('data-token',this.dataToken);
            collectorIframe.setAttribute('data-version',this.dataVersion);
            document.getElementById("collectorIframe").appendChild(collectorIframe);
        }
    });
});
